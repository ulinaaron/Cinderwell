# Cinderwell Members Portal

Private member area add-on for the Cinderwell block library.

## Requirements

- WordPress 6.3+
- Cinderwell plugin (core)
- PHP 7.4+

## Installation

1. Upload to `wp-content/plugins/cinderwell-portal/`
2. Activate the plugin
3. Go to **Cinderwell > Members Portal** to configure

## Configuration

| Sub-tab | What it controls |
|---------|-----------------|
| **General** | Enable/disable, registration mode, redirect URLs, email sender |
| **Registration** | Terms/privacy checkboxes, button text |
| **Email Templates** | 5 editable transactional emails with placeholders |
| **Access Control** | How content protection works |
| **Pending** | Approve/reject registrations |
| **Members** | Manage existing members, add new ones |

## Protecting Content

### Page-Level

Edit any post/page. In the right sidebar, check **"Members only"**. Non-members see a teaser + login link.

Members Portal owns the search privacy policy for these pages. It adds a
`noindex` robots directive and removes the page from WordPress's XML sitemap.
Login, registration, and member profile utility pages receive the same
protection automatically.

### Block-Level

Use the **Member Only** block in the editor. Wrap the content you want gated. Anything outside the block is public.

Using a Member Only block does not make the entire page private or remove the
page from search. Only the content inside the block is gated.

## Blocks Provided

| Block | Description |
|-------|-------------|
| **Login** | Member login form |
| **Register** | Registration form (only shown when registration mode allows) |
| **Member Profile** | Self-service profile + password change |
| **Member Only** | Content gating wrapper (InnerBlocks) |

## REST API

All endpoints at `/wp-json/cinder-portal/v1/`. See [EXTENDING.md](EXTENDING.md) for the full reference.

## Bulk Import

WP All Import can create member accounts via its "Create Users" feature:
- Map CSV columns: `user_email`, `first_name`, `last_name`, `role`
- Set `role` to `cinderwell_member`
- Import creates accounts; send welcome emails from the Members panel

## Theme Overrides

Place template files in your theme at `cinderwell-portal/`:

```
your-theme/
└── cinderwell-portal/
    ├── login-form.php
    ├── registration-form.php
    └── member-profile.php
```

The plugin checks the theme first, then falls back to its own templates.
