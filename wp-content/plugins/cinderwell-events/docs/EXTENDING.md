# Extending Cinderwell Events

## PHP API

```php
$result = cinderwell_events_get_sessions( [
    'category_id' => 12,
    'per_page'    => 20,
] );
```

CRUD helpers are available as `cinderwell_events_get_session()`, `cinderwell_events_create_session()`, `cinderwell_events_update_session()`, and `cinderwell_events_delete_session()`.

Query arguments pass through `cinderwell_events_session_query_args`. Normalized records pass through `cinderwell_events_session`. Creation, update, and deletion fire matching `cinderwell_events_session_*` actions.

## REST API

- `GET /wp-json/cinderwell-events/v1/sessions` queries published Events and defaults to upcoming/ongoing Sessions.
- `GET /wp-json/cinderwell-events/v1/events/{event_id}/sessions` lists one Event's Sessions.
- Authenticated editors may create Sessions on the Event route and update/delete `/sessions/{id}`.
- Native Event and category content remains available under `wp/v2/events` and `wp/v2/event-categories`.

## Modules

Optional functionality registers an object implementing `Cinderwell_Events\Module_Interface` through `cinderwell_events_modules`. Dependencies are module slugs. The foundation boots modules only after all declared dependencies are available.

Sessions remain authoritative. A future recurrence module should generate Session rows and retain its recurrence-rule provenance separately, allowing any generated Session to be edited independently.

