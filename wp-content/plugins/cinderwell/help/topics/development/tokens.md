Define client colors, typography, spacing, and component recipes through Cinderwell tokens. Use `cinderwell_design_tokens` to change resolved values and `cinderwell_token_manifest` to extend the editable token registry.

```php
add_filter( 'cinderwell_design_tokens', function ( $tokens ) {
    $tokens['color.brand'] = '#174b78';
    return $tokens;
} );
```

Prefer semantic values such as brand, surface, text, success, and button recipes. Check foreground/background pairs for WCAG AA contrast after changing a palette.
