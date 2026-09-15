# Extending Cinderwell Popups

## Browser API

```js
window.CinderwellPopups.open(123);
window.CinderwellPopups.close();
```

Opening and closing dispatches `cinderwell:popup:open` and `cinderwell:popup:close` events with the popup ID in `event.detail.id`.

Any element with `data-cinderwell-popup="123"` opens popup 123.

## REST API

- `GET /wp-json/cinder-popups/v1/active?url=/contact/&post_id=42` is public.
- `POST /wp-json/cinder-popups/v1/track` accepts a published popup ID and `view`, `click`, `dismiss`, or `convert`.
- `GET /wp-json/cinder-popups/v1/popups` requires an authenticated user who can edit posts.
- `GET /wp-json/cinder-popups/v1/popups/123` requires permission to edit that popup.
- `POST /wp-json/cinder-popups/v1/popups` creates or updates a popup. Supply `id` to update, plus `title`, block `content`, `status`, and a `settings` object.

The native `wp/v2/popups` route is also available, including registered popup meta for authenticated editors.
