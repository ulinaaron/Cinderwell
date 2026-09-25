# Extending Cinderwell Members Portal

## REST API Reference

All endpoints at `/wp-json/cinder-portal/v1/`.

### Public Endpoints

```
POST /login                      { email, password, remember? }
POST /logout
POST /register                   { email, first_name, last_name }
POST /password-reset/request     { email }
GET  /me                         Returns current user or 401
GET  /can-access/{post_id}       Returns { restricted, can_access }
```

### Admin Only Endpoints

```
GET    /pending                            List pending registrations
POST   /pending/{id}/approve               { send_email? }
POST   /pending/{id}/reject                { send_email? }
GET    /members                            List approved members
POST   /members                            { email, first_name, last_name, send_welcome? }
PATCH  /members/{id}                        { email?, first_name?, last_name? }
DELETE /members/{id}
```

## WordPress Roles & Capabilities

The plugin registers two custom roles on activation:

| Role | Slug | Capabilities |
|------|------|-------------|
| Pending Member | `cinderwell_pending_member` | `read` |
| Member | `cinderwell_member` | `read`, `access_portal_content` |

Roles are **not** removed on deactivation to preserve data.

### The `access_portal_content` Capability

All content gating checks use the `access_portal_content` capability instead of
checking for the `cinderwell_member` role directly. This means:

- **Administrators** always have access (via `manage_options`).
- **Members** (`cinderwell_member` role) have it by default.
- **Any other role** can be granted access by adding the capability.

Grant the capability to Editors:

```php
$role = get_role('editor');
$role->add_cap('access_portal_content');
```

Grant to a specific user only:

```php
$user = get_user_by('ID', 42);
$user->add_cap('access_portal_content');
```

Remove access from a role:

```php
$role = get_role('editor');
$role->remove_cap('access_portal_content');
```

The capability is added to the `cinderwell_member` role automatically on
activation and on each page load (to cover upgrades).

## Filters

### `cinderwell_portal_search_policy`

Members Portal exposes its whole-page search privacy decision without requiring
an SEO add-on. Restricted pages and portal utility pages are noindexed and
excluded from the native WordPress sitemap. A public page that only contains a
Member Only block is intentionally not covered by this policy.

Use the public function when another add-on or theme needs to consume the
decision:

```php
$policy = cinderwell_portal_get_search_policy( $post_id );

if ( ! empty( $policy['noindex'] ) ) {
	// Members Portal owns this page's indexing instruction.
}
```

The function returns an empty array when the add-on is not enabled. Active
policies include `noindex`, `exclude_from_sitemap`, metadata suppression flags,
an editor-facing label and description, and a `locked` flag for controls that
must not override the privacy decision.

Extend or refine the policy with the filter:

```php
add_filter( 'cinderwell_portal_search_policy', function ( $policy, $post ) {
	if ( 'private_resource' === $post->post_type ) {
		$policy['noindex'] = true;
		$policy['exclude_from_sitemap'] = true;
	}

	return $policy;
}, 10, 2 );
```

### `cinderwell_portal_email_vars`

Add custom placeholders to email templates:

```php
add_filter('cinderwell_portal_email_vars', function ($vars) {
    $user = get_user_by('email', $vars['email'] ?? '');
    if ($user) {
        $vars['membership_level'] = get_user_meta($user->ID, 'membership_level', true);
    }
    return $vars;
});
```

Now `{membership_level}` works in all email templates.

### `cinderwell_portal_member_capability`

Override what capability is checked for member content access:

```php
add_filter('cinderwell_portal_member_capability', function () {
    return 'edit_posts'; // any role with edit_posts can access
});
```

## Actions

### `cinderwell_portal_register_user`

Fires after a user is created via the registration form:

```php
add_action('cinderwell_portal_register_user', function ($user_id, $data) {
    update_user_meta($user_id, 'company_name', sanitize_text_field($data['company_name'] ?? ''));
}, 10, 2);
```

## Block Registration

The plugin registers its blocks from `build/blocks/*/block.json`. Each block is server-rendered via `render.php`.

## Theme Template Overrides

Place overrides in your theme:

```
your-theme/cinderwell-portal/
├── login-form.php
├── registration-form.php
├── member-profile.php
├── pending-notice.php
└── approval-pending.php
```

The `Template_Loader` checks the theme directory first, then falls back to the plugin.

## WP All Import Compatibility

Members can be bulk-imported using WP All Import's "Create Users" feature:
- Map `user_email` to User Email
- Map `first_name` to First Name
- Map `last_name` to Last Name
- Set `role` to `cinderwell_member`

The plugin's roles are standard WordPress roles and work with any tool that creates users.
