# First-pass direction

Use this workflow for a new marketing site or substantial redesign. Its purpose is to make the first rendered draft directionally strong enough that later work is refinement, not rescue.

## Define the design contract

Before assembling the full homepage, write a compact design contract that answers:

- What should the visitor feel, understand, and do?
- What visual tension gives the brand character: editorial and energetic, technical and human, quiet and tactile, or another purposeful pairing?
- What composition remains recognizable when the logo, copy, color, and photography are mentally removed?
- Which recent hero family, section sequence, card treatment, or CTA layout must this build avoid repeating?
- Which content is real and dynamic: events, people, posts, metrics, forms, products, locations, or testimonials?

Translate the answers into an implementation recipe:

- primary grid and deliberate exceptions;
- header states, logo or lockup behavior, and mobile navigation footprint;
- hero composition, image focus, headline measure, and action arrangement;
- light, tinted, dark, image, and conversion surface contracts;
- display and body type behavior, including headline wrapping and description measure;
- button, outlined action, and editorial link grammar;
- imagery crop and framing rules;
- one or two subject-specific motifs, such as route lines, contour marks, ledger rules, material cuts, or seasonal bands;
- desktop and mobile gutter rules.

If the supplied brand has no usable logo, create or propose a coherent temporary mark and live-text lockup instead of defaulting to raw initials or an oversized site title. Keep decorative marks out of the accessible name and preserve readable live text where practical.

## Build a representative slice

Do not populate every page before judging the direction. First render enough of the homepage to expose the system:

1. header and brand lockup in every intended state;
2. hero and its transition into the next section;
3. one repeated-content treatment;
4. one expressive narrative or interactive treatment;
5. one real conversion surface, including its form or action control;
6. concluding CTA and footer.

Use realistic content and representative media. A placeholder logo, empty cards, generic imagery, or fake lorem copy can conceal whether the composition works.

Render the slice at approximately 1440px and 390px before expanding the build. Inspect the whole sequence, not only the first viewport.

## First-pass gates

Review in this order:

### 1. Silhouette

Temporarily ignore brand color and imagery. The page should still have a recognizable composition. If it resembles a recent build because it repeats the same overlay panel, split hero, card row, and banded CTA sequence, change the composition family before tuning details.

### 2. Brand ownership

Confirm the child theme actually owns the rendered palette, typography, buttons, forms, links, and surface states. Inspect stylesheet order and representative computed styles. A default framework accent leaking into one component is a system defect, not a minor cleanup item.

### 3. Surface completeness

Evaluate every surface as a complete contract: heading, body, eyebrow, link, primary action, secondary action, border, form control, focus state, and decoration. Conversion sections deserve their own surface-aware button treatment; do not place a purple button on a purple gradient or inherit a light-surface link color onto a dark panel.

### 4. Content truth

Use the product's actual content models. Prefer Events for events, Team or Loop for people, Number for metrics, Form for signup or inquiry, Content Slider for synchronized narrative and media, and semantic link styles for editorial actions. Do not fake these with static cards merely to finish the layout faster.

### 5. Responsive intent

At the narrow viewport, verify the brand lockup, header action, menu control, gutters, hero crop, final headline line, form geometry, card padding, footer grouping, and section transitions. Recompose or substitute a responsive hero when the focal subject or message no longer survives.

### 6. Rhythm and transitions

Trace every major section boundary. Remove unexplained white strips, duplicate padding, dead bands, and abrupt color changes. If a separation is intentional, give one adjacent Cinderwell Section ownership through a rule, overlap, curve, texture, or other named treatment.

## Stop condition

Do not solve a weak first pass through dozens of isolated spacing and color tweaks. If the silhouette, brand ownership, or hero-to-section sequence fails, return to the design contract and revise the recipe. Continue page production only after the representative slice is coherent, distinctive, responsive, and accessible.
