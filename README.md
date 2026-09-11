# Cinderwell

A WordPress block system, Full Site Editing starter theme, and add-on foundation for client websites. Cinderwell combines direct canvas editing with shared design tokens, responsive controls, and reusable content blocks.

This project is in active development.

## What's included

| Component | Location | Purpose |
| --- | --- | --- |
| Cinderwell | [`wp-content/plugins/cinderwell`](wp-content/plugins/cinderwell) | Blocks, atoms, design tokens, editor controls, dynamic data, and extension hooks |
| Cinderwell Alerts | [`wp-content/plugins/cinderwell-alerts`](wp-content/plugins/cinderwell-alerts) | Block-built alert bars with scheduling, targeting, priority, and dismissal |
| Cinderwell Starter | [`wp-content/themes/cinderwell-starter`](wp-content/themes/cinderwell-starter) | Companion block theme with FSE templates, header, footer, and theme integration |

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

To use the companion theme, copy `cinderwell-starter` into `wp-content/themes/` and activate **Cinderwell Starter**. To use alerts, copy `cinderwell-alerts` into `wp-content/plugins/` and activate it after Cinderwell.

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

For client-specific work, use a child theme and the documented extension hooks. See the [extension guide](wp-content/plugins/cinderwell/EXTENDING.md), [block plugin documentation](wp-content/plugins/cinderwell/README.md), and [Alerts documentation](wp-content/plugins/cinderwell-alerts/README.md).

## Repository scope

Only Cinderwell's source, documentation, and bundled theme assets are tracked. WordPress core, other plugins and themes, uploads, databases, credentials, browser sessions, local showcase content, generated builds, and demo videos remain outside the repository.
