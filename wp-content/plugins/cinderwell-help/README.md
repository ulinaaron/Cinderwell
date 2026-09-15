# Cinderwell Help

An independently installable Cinderwell add-on that provides a client-facing
Help screen at **Cinderwell → Help**. Editors can read documentation without
receiving access to Cinderwell settings.

The WordPress dashboard includes a **Website help** widget with shortcuts to
common topics and the complete Help screen. It is registered at high priority
so it appears ahead of standard dashboard widgets, while remaining movable by
the user. Linked topics open automatically.

Active Cinderwell add-ons contribute their own sections and topics through the
same registry. Because each add-on registers its own filters, its documentation
disappears automatically when that plugin or bundled module is deactivated.
Sections with no topics visible to the current user are also omitted.

## Extending documentation from a client theme

Sections and topics are code-defined so documentation can be versioned with a
client theme. Both registries are associative: use a stable key to add or
replace an item, and `unset()` a key to remove it.

```php
add_filter( 'cinderwell_help_sections', function ( $sections ) {
    $sections['client-workflows'] = [
        'title'       => __( 'Client workflows', 'client-theme' ),
        'description' => __( 'Documentation specific to this website.', 'client-theme' ),
        'order'       => 15,
    ];

    // Removing a section also removes every topic assigned to it.
    unset( $sections['site-management'] );
    return $sections;
} );

add_filter( 'cinderwell_help_topics', function ( $topics, $sections ) {
    $topics['request-a-review'] = [
        'section' => 'client-workflows',
        'title'   => __( 'Request a content review', 'client-theme' ),
        'summary' => __( 'Send a draft to the communications team.', 'client-theme' ),
        'content' => __( '<p>Save the page as a draft, then share its preview link with the communications team.</p>', 'client-theme' ),
        'icon'    => 'dashicons-email-alt',
        'order'   => 10,
    ];

    unset( $topics['responsive-visibility'] );
    return $topics;
}, 10, 2 );
```

A topic may set `capability` to a WordPress capability and `condition` to a
callable. Topics are hidden when either check fails. For final visibility
decisions, use `cinderwell_help_topic_is_visible`.

Additional integration points:

- `cinderwell_help_capability` changes access from the default `edit_posts`.
- `cinderwell_help_dashboard_widget` changes the dashboard widget title,
  message, button label, and topic shortcuts.
- `cinderwell_help_show_dashboard_widget` hides the dashboard widget when it
  does not suit a client site.
- `cinderwell_help_screen_before` renders above the documentation layout.
- `cinderwell_help_screen_after` renders below the documentation layout.
