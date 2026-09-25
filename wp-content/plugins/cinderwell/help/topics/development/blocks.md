Register project-specific blocks from the child theme on `cinderwell_register_blocks`, using `block.json` as the source of metadata.

```php
add_action( 'cinderwell_register_blocks', function () {
    register_block_type( get_stylesheet_directory() . '/blocks/client-block' );
} );
```

Use Cinderwell tokens rather than hard-coded styles, follow the established responsive controls, preserve keyboard behavior and semantics, and provide editor styles that closely match the front end.
