Use `cinderwell_register_fields` when a client theme or add-on needs stable structured data. The code-defined schema becomes the source of truth for sanitization, native WordPress storage, REST exposure, editor controls, Dynamic Data, and optional admin-list columns.

```php
add_action( 'cinderwell_register_fields', function ( $registry ) {
    $registry->register_group( 'client/property_details', [
        'label'           => __( 'Property details', 'client-theme' ),
        'object_type'     => 'post',
        'object_subtypes' => [ 'property' ],
        'editor_location' => 'sidebar',
        'fields'          => [
            'subtitle' => [
                'label'    => __( 'Subtitle', 'client-theme' ),
                'required' => true,
            ],
            'property_type' => [
                'label'   => __( 'Property type', 'client-theme' ),
                'type'    => 'select',
                'options' => [
                    'home'       => __( 'Home', 'client-theme' ),
                    'commercial' => __( 'Commercial', 'client-theme' ),
                ],
                'admin_column' => [ 'filterable' => true ],
            ],
        ],
    ] );
} );
```

### Choose the storage scope

- `post` stores native post metadata and accepts post types in `object_subtypes`.
- `term` stores term metadata and accepts taxonomies in `object_subtypes`.
- `user` stores user metadata.
- `site` stores the group in one namespaced option array.

Post groups may use `sidebar`, `normal`, or `side` for `editor_location`. Repeater and business-hours groups use the full-width meta-box presentation automatically so their structured controls remain usable. Existing data can be adopted safely by assigning the current meta key to a field’s `storage_key` or the current site option to `option_name`.

### Use and present the values

Fields are available in the block editor’s **Content Fields** Dynamic Data source unless `dynamic` is disabled. Templates and integrations can read a value through `\Cinderwell\Content_Fields::registry()->get_value( 'client/property_details', 'subtitle', get_the_ID() )`. Set `admin_column` when a value should appear, sort, or filter in a supported WordPress list table.

Use clear labels and useful descriptions, choose the narrowest appropriate field type, and verify keyboard operation, error guidance, focus order, and screen-reader announcements in every custom workflow. ACF fields remain available as a compatibility adapter when ACF is active, but ACF and Admin Columns are not required for code-defined client models. Keep ACF for workflows that genuinely need its visual builder or flexible content, and use Admin Columns only when its inline editing, bulk editing, or export features are required.
