# Studio development and demo-site safety

The full WordPress source install in `~/Studio/stevens-base` supplies the Cinderwell plugin directories and `cinderwell-starter` parent theme to the eight Studio demo sites. Work on a repository branch and review a PR before changing this shared source. Do not edit `stevens-base` or the demo sites from an agent checkout. Client child themes, uploads, databases, and local WordPress configuration stay site-specific.

## Add-on distribution

All `cinderwell-*` add-on directories in each demo site's `wp-content/plugins/` are symlinks to the corresponding directories under `stevens-base/wp-content/plugins/`. Core `cinderwell` and the `cinderwell-starter` theme use the same shared-source model. Activation remains per-site in WordPress. Adding a new add-on to the source tree means linking it in every demo, even where it is inactive.

The existing add-on copies need a **one-time migration by a Studio owner**. From a clean source checkout, inspect the source and each demo first, then run:

```bash
node scripts/link-demo-addons.mjs --source "$HOME/Studio/stevens-base" \
  --site "$HOME/Studio/aster-peak-demo" \
  --site "$HOME/Studio/cedar-ledger-demo" \
  --site "$HOME/Studio/field-frost-demo" \
  --site "$HOME/Studio/goodgood-parcel-demo" \
  --site "$HOME/Studio/northline-concrete-demo" \
  --site "$HOME/Studio/sundrift-fruit-co" \
  --site "$HOME/Studio/velaform-building-systems" \
  --site "$HOME/Studio/my-shiny-website"
```

The default is a dry run. The script refuses to replace a copy whose contents differ from the source, or a link aimed somewhere else. Resolve any difference deliberately. After reviewing the plan, rerun the same command with `--apply`. Existing copies move to each site's `.cinderwell-addon-backups/` directory outside `wp-content/plugins/`; keep them until every site is checked. To roll back one add-on, remove its new symlink and move its backup directory to its original plugin path. Do not use this migration to point demos at a task checkout.

## Pre-merge blast-radius check

Before merging a change to core, a shared add-on, or the parent theme:

1. Build the affected component(s), then run their lint and tests. Confirm the source being checked is the same revision proposed for merge.
2. Confirm the demo plugin links resolve to `stevens-base`; run the migration command above without `--apply` to detect missing, incorrect, or divergent add-ons.
3. Start all eight local demo sites. From this repository, run `npm ci` and `npx playwright install chromium` once. Copy `scripts/demo-sites.example.json` to ignored `demo-sites.json`, then set each URL to its actual local home page.
4. Run `npm run smoke:demo`. The check opens each home page at 1440 and 390 pixels, fails on HTTP errors, blank title/body, horizontal overflow, and uncaught page errors. Review the 16 screenshots and `results.json` in `.artifacts/blast-radius/` for visual breakage; this automated pass does not judge design changes.
5. Record the command result and any visual findings in the PR. Fix failures before merging. The local demo network is not available in hosted CI, so this is a required manual pre-merge check.

The smoke script only reads public pages and writes local artifacts. It does not log in, submit forms, update content, or modify any demo site.
