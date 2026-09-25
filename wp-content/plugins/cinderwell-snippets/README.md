# Cinderwell Snippet Manager

An independent Cinderwell add-on for trusted administrators who need focused
PHP, JavaScript, CSS, and HTML customizations without editing theme files.

## Safety

- PHP syntax is validated before activation.
- Exceptions and runtime errors disable only the offending snippet.
- Fatal errors are recorded during shutdown and the snippet is disabled for the
  next request whenever WordPress can still write to the database.
- Safe Mode suspends all snippets without changing their individual status.
- `define( 'CINDERWELL_SNIPPETS_SAFE_MODE', true );` provides a recovery bypass
  from `wp-config.php`.
- WordPress Recovery Mode automatically suspends snippets.

PHP cannot be sandboxed inside WordPress. A process-ending failure can still end
the request that triggered it; the guard protects subsequent requests.

## Shortcodes

PHP and HTML snippets using the **Shortcode only** location are rendered with:

```text
[cinderwell_snippet id="snippet-slug"]
```

PHP shortcode snippets receive `$atts`, `$content`, and `$tag` variables.

## Extension hooks

- `cinderwell_snippets_run_locations`
- `cinderwell_snippet_should_run`
- `cinderwell_snippet_auto_disabled`

