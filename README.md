# Cinderwell

A WordPress block system, updateable Full Site Editing parent theme, and add-on foundation for client websites. Cinderwell combines direct canvas editing with shared design tokens, responsive controls, and reusable content blocks.

This project is in active development.

## What's included

| Component | Location | Purpose |
| --- | --- | --- |
| Cinderwell | [`wp-content/plugins/cinderwell`](wp-content/plugins/cinderwell) | Blocks, atoms, design tokens, editor controls, dynamic data, and extension hooks |
| Cinderwell Alerts | [`wp-content/plugins/cinderwell-alerts`](wp-content/plugins/cinderwell-alerts) | Block-built alert bars with scheduling, targeting, priority, and dismissal |
| Cinderwell Base | [`wp-content/themes/cinderwell-starter`](wp-content/themes/cinderwell-starter) | Updateable FSE parent theme with the template hierarchy and default site frame |

The block kit includes Hero, Body, CTA, Card Grid, Image + Text, Icon List, Gallery, Image Carousel, FAQ, Accordion, Tabs, Loop, Two Column, Section, Slot Layout, Mega Menu, and Gravity Forms integration, plus standalone atoms and nested content blocks.

Shared controls cover colors, typography and line height, spacing, widths, surfaces, images, and responsive visibility. The **Visibility** panel can target an entire Cinderwell block or supported content pieces at desktop, tablet, and mobile sizes. Mega menus work inside the native Navigation block.

## Build and install

You'll need a WordPress installation, Node.js 20.9 or later, and npm. WordPress core and third-party plugins are not bundled. Gravity Forms is optional and must be installed separately to use its integration block.

```bash
git clone git@github.com:ulinaaron/Cinderwell.git
cd Cinderwell/wp-content/plugins/cinderwell
npm ci
npm run build
```

Copy the built `cinderwell` directory into your site's `wp-content/plugins/`, including its generated `build/` directory. Activate **Cinderwell** in WordPress. The source and `node_modules/` directories aren't needed on a deployment.

Install `cinderwell-starter` unchanged as the parent theme. For a client site,
create and activate a child theme with `Template: cinderwell-starter`; do not copy
or fork the parent into the client theme. This keeps parent fixes updateable
while the child owns brand tokens, patterns, parts, and intentional template
overrides. See the [parent-theme guide](wp-content/themes/cinderwell-starter/README.md).

To use alerts, copy `cinderwell-alerts` into `wp-content/plugins/` and activate it after Cinderwell.

This is a multi-component source repository. GitHub's source ZIP is not a single installable WordPress plugin ZIP; build and install the component directories described above.

## Development

Run these commands from `wp-content/plugins/cinderwell/`:

```bash
npm run start     # Watch and rebuild while editing blocks
npm run build     # Production assets
npm run lint:js
npm run lint:css
```

Block and atom entry points are discovered by the webpack configuration. Generated assets and dependencies are excluded from Git, so rebuild after cloning or updating source. Alerts and the starter theme use their PHP, CSS, and JavaScript files directly.

For client-specific work, use a child theme and the documented extension hooks.
Cinderwell's update manifest supports the core plugin, add-ons, and parent
theme; its schema is documented in
[`updates/info.example.json`](updates/info.example.json).
See the [extension guide](wp-content/plugins/cinderwell/EXTENDING.md), [block plugin documentation](wp-content/plugins/cinderwell/README.md), and [Alerts documentation](wp-content/plugins/cinderwell-alerts/README.md).

## Publishing updates

Production packages and `info.json` are published as a static site at
`https://cinderwell-updates.surge.sh`. The private source repository remains the
source of truth; client sites receive only independently installable WordPress
ZIP packages.

To configure publishing:

1. Install the Surge CLI locally and log in.
2. Create a domain-scoped token with
   `surge tokens add --domain cinderwell-updates.surge.sh -m "github actions"`.
3. Add the returned value as the `SURGE_TOKEN` Actions secret in GitHub.
4. Optionally protect the `updates-production` GitHub environment with required
   reviewers.

Update the component header versions, commit the tested changes, and either run
the **Publish Cinderwell updates** workflow manually or push a `release-*` tag.
The workflow builds the core assets, creates separate plugin/theme ZIPs,
generates checksums and the manifest, retains the output as a workflow artifact,
then atomically publishes the directory to Surge.

For a local packaging check after running the core build:

```bash
node scripts/build-update-release.mjs
unzip -Z1 dist/packages/cinderwell-*.zip | head
```

### Local source protection

The tracked `cinderwell-local-update-guard.php` must-use plugin activates only
when WordPress reports a `local` or `development` environment. It disables the
Cinderwell release client, removes cached update offers for Cinderwell, Alerts,
and Cinderwell Base, and blocks a stale/manual update before it can replace the
source directories. WordPress core and unrelated plugin/theme updates remain
available.

The release ZIP intentionally excludes development-only files such as `src/`
and `node_modules/`, so never update a Cinderwell source checkout with a packaged
release. Production sites are unaffected by the local guard.

## Repository scope

Only Cinderwell's source, documentation, and bundled theme assets are tracked. WordPress core, other plugins and themes, uploads, databases, credentials, browser sessions, local showcase content, generated builds, and demo videos remain outside the repository.
