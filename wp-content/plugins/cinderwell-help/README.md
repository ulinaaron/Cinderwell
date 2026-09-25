# Cinderwell Help

Cinderwell Help is an admin presentation for Cinderwell's shared documentation
registry. It does not own the documentation library. Core, active add-ons, and
client themes register package-owned source directories, and Help presents the
topics available to the current user.

The Help screen is available at **Cinderwell → Help**. User and Development
audiences retain their categories, search, capability checks, accessible topic
controls, and syntax-highlighted code examples. The dashboard widget reads only
topic metadata; full Markdown bodies are loaded when the Help screen renders.

## Package documentation contract

Each owning package stores a `help/manifest.php` and Markdown topic files:

```text
help/
├── manifest.php
└── topics/
    ├── editing.md
    └── development.md
```

Register the directory from an active plugin or client theme:

```php
add_action( 'cinderwell_register_documentation', function ( $registry ) {
    $registry->register_directory(
        'client-theme',
        get_stylesheet_directory() . '/help'
    );
} );
```

A minimal manifest looks like:

```php
return [
    'version'  => wp_get_theme()->get( 'Version' ),
    'sections' => [
        'client-workflows' => [
            'title'       => __( 'Client workflows', 'client-theme' ),
            'description' => __( 'Instructions specific to this website.', 'client-theme' ),
            'audience'    => 'user',
            'order'       => 50,
        ],
    ],
    'topics' => [
        'request-review' => [
            'section'    => 'client-workflows',
            'title'      => __( 'Request a review', 'client-theme' ),
            'summary'    => __( 'Share a draft with the communications team.', 'client-theme' ),
            'file'       => 'topics/request-review.md',
            'icon'       => 'dashicons-email-alt',
            'order'      => 10,
            'visibility' => 'internal',
            'tags'       => [ 'drafts', 'review' ],
        ],
    ],
];
```

The manifest returns a package version, section definitions, and topic metadata.
Topic bodies remain in Markdown so other consumers and release tooling can use
the same authored source. Stable topic IDs, package ownership, tags, visibility,
capability, and package version are retained in the normalized registry.

Localized bodies may sit beside the source file using the WordPress locale,
such as `editing.es_ES.md`. Cinderwell prefers that file for the matching locale
and falls back to `editing.md`; manifest labels remain normal translatable PHP
strings.

Use `visibility => public` for material that may be included in a future public
documentation bundle. Use `visibility => internal` for site-specific or private
guidance. The Help interface additionally enforces each topic's WordPress
`capability`.

## Extension points

- `cinderwell_register_documentation` registers package directories lazily.
- `cinderwell_documentation_sections` filters normalized sections.
- `cinderwell_documentation_topics` filters normalized topic metadata.
- `cinderwell_documentation_topic_html` filters rendered, sanitized topic HTML.
- `cinderwell_documentation_topic_is_visible` controls topic visibility for a
  named consumer such as `help`.
- `cinderwell_help_capability` controls access to the Help screen.
- `cinderwell_help_dashboard_widget` configures dashboard shortcuts.
- `cinderwell_help_show_dashboard_widget` disables the dashboard widget.
- `cinderwell_help_screen_before` and `cinderwell_help_screen_after` surround the
  Help layout.

`Cinderwell\Documentation::instance()->get_export_data()` exposes a
transport-neutral bundle for release tooling without introducing a public
documentation-site plugin.
