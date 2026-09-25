# Accessibility and QA

Reference sites are inspiration, not evidence of conformance. Independently validate every Cinderwell result.

## Authoring baseline

- Use semantic landmarks and a logical heading hierarchy.
- Give controls accessible names and visible labels.
- Write descriptive link text that remains meaningful out of context.
- Supply concise, contextual alternative text for informative images and empty alt text for decorative images.
- Do not place essential information only in an image, color, animation, hover state, or icon.
- Keep instructions next to the relevant field or control and associate errors programmatically.

## Interaction

- Operate every control with a keyboard alone.
- Provide visible focus with sufficient contrast and no clipping.
- Preserve a logical focus order that follows the content.
- For disclosures, tabs, carousels, menus, filters, forms, maps, and modals, expose state and changes to assistive technology.
- For a synchronized content slider, provide named previous and next controls, named direct-selection controls when present, current-slide state, a polite status update, and one predictable tab sequence. Do not move keyboard focus on slide changes unless the user action explicitly requests navigation.
- Restore or deliberately move focus after dynamic changes.
- Prevent stale asynchronous results from replacing newer ones.
- Keep a functional non-JavaScript baseline wherever practical.

## Visual checks

- Validate text, icon, control, focus, and state contrast against actual rendered backgrounds.
- Test at 200% zoom and narrow reflow without horizontal page scrolling.
- Confirm that responsive sections, headers, footers, forms, and cards retain intentional inner padding and do not touch viewport edges.
- Measure the actual left and right edges of adjacent headers, heroes, full-width sections, and card grids. For full-width designs, test an ultrawide viewport as well as the standard desktop size so duplicated gutters and hidden max-width constraints are visible.
- When a persistent utility such as cookie settings overlays the viewport, give the final footer content enough bottom clearance that legal text and controls remain readable and operable.
- Inspect hero crops at every supported width; keep the focal subject visible and text legible. If visibility-controlled desktop and mobile heroes are used, confirm that only the active composition is exposed visually and to assistive technology.
- Check headline wrapping at each supported width and zoom level; the final visual line must contain at least two words without forcing overflow.
- Inspect long headings, long links, validation messages, missing media, and translated-length content.
- Maintain readable body size and line height; do not rely on ultra-light weights.
- Ensure touch targets and spacing prevent accidental activation.
- Honor `prefers-reduced-motion` and avoid motion essential to understanding.

## Editor and frontend

Check both experiences. Editor affordances must not trap scrolling, hide settings, or require pointer precision. Frontend behavior must match the editor's meaningful visual preview while remaining semantically correct. Token registration is not sufficient evidence: compare computed token values and representative rendered components in both contexts, and confirm every block remains valid after programmatic content changes.

For substantial pages, inspect at least:

- a wide desktop viewport;
- a narrow mobile viewport;
- keyboard traversal;
- zoom/reflow;
- automated accessibility findings;
- console errors, overflow, and broken assets;
- actual headings, landmarks, labels, and alternative text.

Automated checks supplement manual inspection; they do not establish WCAG conformance.
