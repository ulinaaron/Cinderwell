# Extending Cinderwell Performance

## Force Immediate Hydration on a Block

```html
<div data-cw-hydrate="false">
```

## Force Deferred Hydration on a Static Block

```html
<div data-cw-hydrate="true">
```

## Listen for Hydration Events

```javascript
document.addEventListener('cinderwell:hydrated', (e) => {
    console.log('Hydrated:', e.detail.element);
});
```

## Programmatic API

```javascript
// Force hydrate a single element
window.CinderwellHydration.force(element);

// Force hydrate all deferred elements
window.CinderwellHydration.forceAll();

// Check stats
window.CinderwellHydration.stats;
// { hydrated: 5, deferred: 12, errors: 0 }
```

## Custom Bot User Agents

```php
add_filter('cinderwell_performance_bot_user_agents', function($agents) {
    $agents[] = 'MyCustomBot';
    return $agents;
});
```

## REST API

```
GET   /wp-json/cinder-performance/v1/settings
PATCH /wp-json/cinder-performance/v1/settings
```

PATCH body: partial update, e.g. `{"performance": {"enabled": true, "resource_hints_enabled": true}}`.
