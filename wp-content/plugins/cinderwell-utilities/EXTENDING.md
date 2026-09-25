# Extending Cinderwell Site Utilities

Site Utilities uses one filterable registry for module identity, loading, defaults, sanitization, import/export, and admin metadata. Register small administration conveniences here. Features with their own content model, substantial frontend runtime, or integration ecosystem should remain standalone Cinderwell add-ons.

## Register a module

```php
add_filter( 'cinderwell_utilities_modules', function ( $modules ) {
	$modules['client_maintenance_note'] = [
		'class'       => Client_Maintenance_Note::class,
		'label'       => __( 'Maintenance Note', 'client-theme' ),
		'description' => __( 'Show a private note to administrators.', 'client-theme' ),
		'group'       => 'admin',
		'icon'        => 'dashicons-info-outline',
		'settings'    => [
			'enabled' => [
				'type'    => 'boolean',
				'default' => false,
			],
			'note' => [
				'type'    => 'text',
				'default' => '',
			],
		],
		'render_callback' => 'client_render_maintenance_note_settings',
	];

	return $modules;
} );
```

The module class receives its sanitized settings array in the constructor and is instantiated only when `enabled` is true. A descriptor may supply `boot_callback` instead of relying on the class constructor.

The stable descriptor keys are:

- `class`: loadable class name; required even when a boot callback is used.
- `label`, `description`, `group`, and `icon`: settings-screen identity.
- `settings`: field schema; required.
- `boot_callback`: optional callable receiving sanitized settings and the descriptor.
- `render_callback`: optional callable for settings controls.
- `settings_url`: optional URL or callable returning a URL for a dedicated screen.
- `warning`: optional concise warning shown in the module panel.

Modules without custom settings controls can omit `render_callback` and expose only the standard enabled switch. The `cinderwell_utilities_render_module_settings` action is also available for late rendering.

Use `cinderwell_utilities_groups` to add an intentional settings-screen group. A module whose group is unavailable falls back to Admin Experience so it never disappears from the interface.

## Field schema

Each field requires a `type` and `default`. The same schema sanitizes page saves, REST updates, reads, migrations, and Cinderwell settings imports. Unknown modules and fields are discarded.

Supported field types:

- `boolean`
- `text`
- `email`
- `domain`
- `integer`, optionally with `choices`
- `enum`, with `choices`
- `key_list`, with `choices`
- `post_type_list`
- `hierarchical_post_type_list`
- `hierarchical_taxonomy_list`
- `role_list`
- `plugin_list`

A field may provide `sanitize_callback` for a domain-specific value. The callback receives the submitted value and field schema. It must return the final storage-safe value.

Do not store credentials in the Site Utilities settings option. Use a dedicated non-autoloaded option or a `wp-config.php` constant, omit it from exports, and present credential inputs as write-only.

## Compatibility rules

- Module IDs and setting keys are persistent API. Add a migration before renaming either.
- Keep settings migrations idempotent and increment `Schema_Migrator::CURRENT_VERSION`.
- Disabled modules must not register runtime hooks or frontend assets.
- Require capabilities as well as nonces for every mutation.
- Settings controls must have programmatic labels, keyboard access, visible focus, and announced asynchronous outcomes.
- A dedicated settings screen should preserve or explicitly resolve unsaved state before navigation.
