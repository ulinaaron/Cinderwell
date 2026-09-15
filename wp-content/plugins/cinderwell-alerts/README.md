# Cinderwell Alerts

Condition-aware alerts composed with Cinderwell blocks.

## Features

- Top-of-page and sticky-bottom placements
- Information, brand, success, warning, and critical tones
- Higher-number-wins priority at each placement
- Start and end scheduling in the WordPress site timezone
- Entire-site, front-page, content-type, archive, and wildcard path conditions
- Optional include and exclude paths
- Optional per-visitor dismissal stored locally with a configurable expiry
- Revisions and block-editor composition
- A focused Cinderwell-only block inserter for alert content
- Cinderwell admin-bar shortcuts for alerts active on the current page
- Conditional frontend assets
- Contextual documentation in Cinderwell Help while the add-on is active
- Alert management beneath the shared Cinderwell admin menu

Only the highest-priority eligible alert is shown at each placement by default.
Use `cinderwell_alerts_max_per_placement` to allow stacking.

## Extension hooks

- `cinderwell_alerts_allowed_blocks`
- `cinderwell_alerts_is_eligible`
- `cinderwell_alerts_max_per_placement`
- `cinderwell_alerts_content`
