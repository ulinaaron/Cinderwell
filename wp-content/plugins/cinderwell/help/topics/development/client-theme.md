Use the Cinderwell Starter theme as the parent. Cinderwell owns blocks, tokens, accessibility behavior, and shared structural styles; the child theme owns brand expression, patterns, template parts, and deliberate client overrides.

```css
/*
Theme Name: Client Name
Template: cinderwell-starter
Requires Plugins: cinderwell
*/
```

Put custom CSS, templates, parts, patterns, and PHP hooks in the child theme. Avoid copying plugin files into the theme: use the documented filters so upstream updates remain possible.
