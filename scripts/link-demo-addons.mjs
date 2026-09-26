#!/usr/bin/env node
import { lstat, mkdir, readdir, readFile, readlink, realpath, rename, symlink } from 'node:fs/promises';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const repo = resolve(fileURLToPath(new URL('..', import.meta.url)));
const addons = (await readdir(join(repo, 'wp-content/plugins'), { withFileTypes: true }))
  .filter((entry) => entry.isDirectory() && entry.name.startsWith('cinderwell-'))
  .map((entry) => entry.name).sort();

function args() {
  const out = { sites: [], apply: false };
  for (let i = 2; i < process.argv.length; i++) {
    const arg = process.argv[i];
    if (arg === '--source') out.source = process.argv[++i];
    else if (arg === '--site') out.sites.push(process.argv[++i]);
    else if (arg === '--apply') out.apply = true;
    else throw new Error(`Unknown argument: ${arg}`);
  }
  if (!out.source || !out.sites.length || out.sites.some((site) => !site)) {
    throw new Error('Usage: node scripts/link-demo-addons.mjs --source /path/to/source-wp --site /path/to/demo-wp [--site ...] [--apply]');
  }
  return out;
}

async function statOrNull(path) {
  try { return await lstat(path); } catch (error) {
    if (error.code === 'ENOENT') return null;
    throw error;
  }
}

async function identical(source, target) {
  const [a, b] = await Promise.all([lstat(source), lstat(target)]);
  if (a.isSymbolicLink() || b.isSymbolicLink()) {
    return a.isSymbolicLink() && b.isSymbolicLink() && await readlink(source) === await readlink(target);
  }
  if (a.isFile() && b.isFile()) {
    if (a.size !== b.size) return false;
    const [left, right] = await Promise.all([readFile(source), readFile(target)]);
    return left.equals(right);
  }
  if (!a.isDirectory() || !b.isDirectory()) return false;
  const [left, right] = await Promise.all([readdir(source).then((items) => items.sort()), readdir(target).then((items) => items.sort())]);
  if (left.length !== right.length || left.some((name, i) => name !== right[i])) return false;
  for (const name of left) if (!await identical(join(source, name), join(target, name))) return false;
  return true;
}

const { source, sites, apply } = args();
const sourceRoot = await realpath(source);
const sourcePlugins = join(sourceRoot, 'wp-content/plugins');
for (const addon of addons) {
  if (!(await statOrNull(join(sourcePlugins, addon)))?.isDirectory()) {
    throw new Error(`Source is missing ${addon}: ${sourcePlugins}`);
  }
}

// Complete validation precedes every write, so a mismatch never causes a partial migration.
const changes = [];
for (const site of sites) {
  const siteRoot = await realpath(site);
  if (siteRoot === sourceRoot) throw new Error('A demo site cannot be the source site');
  const plugins = join(siteRoot, 'wp-content/plugins');
  const backupDir = join(siteRoot, '.cinderwell-addon-backups');
  const backupDirStat = await statOrNull(backupDir);
  if (backupDirStat && !backupDirStat.isDirectory()) throw new Error(`Backup path is not a directory: ${backupDir}`);
  if (!(await lstat(plugins)).isDirectory()) throw new Error(`Missing plugin directory: ${plugins}`);
  for (const addon of addons) {
    const target = join(plugins, addon);
    const desired = join(sourcePlugins, addon);
    const current = await statOrNull(target);
    if (current?.isSymbolicLink()) {
      if (await realpath(target) !== await realpath(desired)) throw new Error(`Wrong symlink target: ${target}`);
      console.log(`OK       ${target}`);
      continue;
    }
    if (current && (!current.isDirectory() || !await identical(desired, target))) {
      throw new Error(`Copy differs from source; inspect and reconcile before linking: ${target}`);
    }
    const backup = join(backupDir, addon);
    if (current && await statOrNull(backup)) {
      throw new Error(`Backup already exists: ${backup}`);
    }
    changes.push({ target, desired, backup, kind: current ? 'replace' : 'create' });
  }
}

for (const { target, desired, kind } of changes) console.log(`${apply ? 'LINK' : 'PLAN'} ${kind} ${target} -> ${desired}`);
if (!apply) {
  console.log(`Dry run: ${changes.length} changes. Add --apply after reviewing the plan.`);
  process.exit(0);
}
for (const { target, desired, backup, kind } of changes) {
  if (kind === 'create') {
    await symlink(desired, target, 'dir');
    continue;
  }
  await mkdir(resolve(backup, '..'), { recursive: true });
  await rename(target, backup);
  try { await symlink(desired, target, 'dir'); }
  catch (error) { await rename(backup, target); throw error; }
  console.log(`Backup: ${backup}`);
}
console.log(`Linked ${changes.length} add-on directories. Backups remain for manual verification and cleanup.`);
