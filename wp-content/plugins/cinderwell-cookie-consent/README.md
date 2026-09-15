# Cinderwell Cookie Consent

An independently packaged Cinderwell add-on for accessible, category-based
cookie and similar-technology consent.

## What it enforces

- Optional categories default to denied.
- Accept and reject controls have equal visual prominence.
- Visitors can make granular choices without preselected optional categories.
- Registered optional scripts, styles, and supported embeds are inert before
  consent, including on cacheable pages.
- Consent is versioned, expires, and can be reviewed or withdrawn from the
  persistent Cookie settings control.
- Rejected first-party cookies configured by name or prefix are removed.
- A suggested disclosure is added to WordPress's Privacy Policy Guide.
- If JavaScript is unavailable, optional registered technologies remain blocked.
- WooCommerce source/order-attribution scripts are categorized as analytics by
  default; cart and checkout functionality remains necessary and unblocked.
- WordPress's optional emoji capability test and remote emoji fallback are
  disabled to avoid unnecessary session storage and CDN requests.
- Administrator documentation appears in Cinderwell Help while the add-on is
  active.

The consent preference itself is stored in the strictly necessary `cw_consent`
cookie. The plugin does not send consent choices to Cinderwell or Stevens Inc.

## Asset registration

Every optional WordPress asset must be categorized. Add this in a plugin or
client theme after registering/enqueuing the handles:

```php
add_filter('cinderwell_cookie_consent_assets', function($assets) {
    $assets['scripts']['client-analytics'] = 'analytics';
    $assets['scripts']['meta-pixel'] = 'marketing';
    $assets['styles']['third-party-widget'] = 'preferences';
    return $assets;
});
```

Supported categories are `preferences`, `analytics`, and `marketing`.
Necessary assets must not be added to this registry. The filter
`cinderwell_cookie_consent_categories` can alter category labels and
descriptions, but a required category should only contain technologies that
are strictly necessary to provide a service the visitor requested.

For inline or non-WordPress scripts, omit a live JavaScript type:

```html
<script type="text/plain" data-cw-consent-script data-cw-consent-category="analytics">
  // Initialize the analytics vendor.
</script>
```

Code can query consent or reopen preferences:

```js
window.CinderwellConsent.has('analytics');
window.CinderwellConsent.open();
window.addEventListener('cinderwell:consent', (event) => {
  console.log(event.detail);
});
```

## Embeds

YouTube, Vimeo, and Google Maps iframes rendered by blocks are prior-blocked by
default. Mark other iframes explicitly with `data-cw-consent="marketing"`, or
categorize providers with `cinderwell_cookie_consent_embed_category`.

## Compliance boundary

This plugin implements a conservative consent mechanism; activation alone is
not a legal-compliance guarantee. Before launch, inventory the production site
with a clean browser profile and verify every cookie, pixel, SDK, iframe,
external font, local-storage value, and third-party request. Hard-coded code
that bypasses WordPress and is not consent-marked cannot be blocked reliably.

The privacy/cookie policy still needs accurate controller identity, vendors,
purposes, cookie names, durations, recipients/transfers, legal bases, and data
subject rights. Have the production configuration reviewed for the countries
and audiences the client serves.
