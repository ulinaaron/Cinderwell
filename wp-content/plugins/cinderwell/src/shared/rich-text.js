/**
 * Inline formats available in editable body copy.
 *
 * Keeping this list shared makes rich-text behavior consistent across blocks
 * while Gutenberg supplies the editor UI, keyboard shortcuts, and semantics.
 */
export const ALLOWED_INLINE_FORMATS = [
    'core/bold',
    'core/italic',
    'core/link',
    'core/strikethrough',
];

export const ALLOWED_BODY_FORMATS = [
    ...ALLOWED_INLINE_FORMATS,
    'core/list',
    'core/quote',
    'core/heading',
];
