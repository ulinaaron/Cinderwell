# Cinderwell Forms

Cinderwell Forms is a standalone Cinderwell add-on for accessible, single-page forms with stored submissions, conditional fields, multiple notifications, confirmation messages or pages, URL-prefilled fields, Cloudflare Turnstile, CSV export, privacy tools, and retention controls.

## Extension points

- `cinderwell_forms_field_types` filters the registered field types.
- `cinderwell_forms_sanitize_definition` filters a normalized form definition.
- `cinderwell_forms_validated_values` filters validated values before storage.
- `cinderwell_forms_notification_content` filters merged notification content.
- `cinderwell_forms_after_submission` runs after storage and notification creation.

Use `cinderwell_forms_get_form()` to retrieve a normalized definition and `cinderwell_forms_render_form()` to render a published form from PHP.
