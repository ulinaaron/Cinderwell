# Design principles

## Build a point of view

Before choosing layouts, state the site's character in a short phrase such as "calm seasonal stewardship" or "precise industrial confidence." Use it to guide imagery, type, geometry, and pacing. A site with no point of view tends to become a sequence of interchangeable cards.

Translate that point of view into structure, not only decoration. Define a recognizable composition or motif that can recur across the site: an editorial rule, an offset image frame, a numbered pathway, a strong content axis, a controlled overlap, or another device tied to the subject. If removing the logo, colors, and imagery makes the page look interchangeable with a recent build, the structure is not distinctive enough.

Use a limited motif vocabulary. One or two devices repeated with discipline create identity; unrelated novelty in every section creates noise.

Before approving a hero, compare it directly with recent site heroes. Ignore the words, colors, and subject matter and compare the layout primitives: full-bleed versus contained media, light versus dark surface, content position, accent rules, headline measure, button arrangement, image crop, and transition into the next section. Reusing most of those primitives produces the same hero even when the styling feels different. Change the composition family—not merely the proportions—when similarity is obvious.

## Establish an alignment system

Choose the page's primary content grid before composing sections. Record the shared left and right axes for the hero, narrative sections, repeated items, calls to action, and footer. Narrower content is allowed, but its placement must be intentional:

- align a narrow section to a primary grid line;
- center the entire section, including its heading and supporting content; or
- use a deliberate offset that clearly belongs to the site's recurring composition.

Do not let each block's default width independently determine its starting edge. A sequence of technically valid `wide`, `standard`, and `narrow` blocks can still look misaligned when their internal content axes drift.

Inspect the page with temporary vertical guides or measured bounding boxes when the alignment is not obvious. Treat unexplained shifts as composition defects, not harmless variation.

## Tell a story through sections

Treat a page as a sequence, not a block inventory. Common narrative jobs include:

- orient the visitor;
- establish the desired outcome;
- show the work, product, or people in context;
- explain the approach;
- provide evidence;
- answer risk or uncertainty;
- offer a proportionate next action.

Alternate information density and presentation. A page may move among an image-led hero, concise value grid, editorial image-and-text story, proof, process, FAQ, and CTA. Repetition is useful when it establishes rhythm; identical cards for every idea are not.

Vary section form as well as surface color. A page does not become structurally varied merely because identical full-width bands alternate between white and tinted backgrounds. Change information density, media relationship, content measure, or spatial emphasis according to each section's narrative job.

## Use visuals deliberately

Heroes should usually have an impactful visual accompaniment unless typographic restraint is itself the concept. Prefer imagery that shows use, environment, consequence, process, or people—not generic industry symbolism.

Visuals may cross into the interface through crops, shapes, controlled overlap, texture, or color fields, but must not obscure text or create a confusing reading order. Decorative media receives empty alternative text. Informative media needs concise context-specific alternatives.

Protect the subject of responsive hero imagery. When a source image leans left or right, set an appropriate responsive focal position rather than allowing a center crop to remove the subject or place it behind text. If one image treatment cannot preserve both legibility and focus across supported widths, create an alternative mobile hero and use Cinderwell visibility settings to show the appropriate composition. Verify that only one complete hero is presented at a time and that each version retains the same message and primary action.

## Make whitespace structural

Whitespace should clarify grouping and pace. It should be traceable to section padding, content rhythm, or a deliberate transition. Large empty bands with no compositional purpose look like rendering errors.

- Keep adjacent section content on compatible grids unless a deliberate centered break is intended.
- When a narrow text section follows a wide section, either align it to the established grid or center the entire composition.
- Avoid using empty spacer blocks to repair layout.
- Let generous whitespace surround meaningful content; do not separate related heading, copy, and action excessively.

## Preserve responsive gutters

Section, header, footer, form, and card padding must remain intentional at every supported viewport. A layout may use smaller mobile spacing tokens, but content must not collapse against the viewport edge or lose the inset established elsewhere on the page. Check nested full-width surfaces carefully: full-width background color or imagery does not imply edge-to-edge inner content.

A fixed-width site shell can be a deliberate brand device. Treat it as one complete recipe: the header, main content, and footer share the same shell edge; the outer canvas remains visibly subordinate; the shell becomes fluid before it creates horizontal scrolling; and mobile returns to a full-width document with normal gutters. Do not apply fixed widths independently to sections.

## Maintain hierarchy

Use one dominant page heading and a logical heading sequence. Eyebrows may orient, but they must not replace useful headings. Body copy should remain comfortably readable; dramatic display type does not justify undersized supporting text.

Keep the type scale coherent across pages. Size, weight, line height, width, and contrast should work together. Avoid excessively thin text even when the color technically passes contrast.

Never leave a headline hanging. The final visual line of a heading must contain at least two words at every supported viewport. Check actual rendered wrapping rather than assuming the desktop composition will hold responsively. Correct a hanging word by refining the copy, text measure, font size, or responsive type treatment. `text-wrap: balance` may help, but it does not replace visual QA. If deliberately keeping the final words together, verify that the result still wraps and reflows without clipping or horizontal overflow at narrow widths and high zoom.

## Design calls to action

Make the primary action visually identifiable without making every link a button. Maintain sufficient space between navigation links and header actions. CTA sections should feel like the conclusion of the preceding story, not an unrelated banner.

Text-link actions must look and size like links. Remove button padding, minimum height, filled backgrounds, and decorative indentation when an action is intended to sit inline with editorial content. Reserve button geometry for actions that need button-level emphasis.

Resolve the CTA as one color system. Heading, body, eyebrow, controls, and decorative shapes must be evaluated together against the actual rendered background. Mixed inherited foreground colors on one surface usually look accidental even when each pair happens to pass contrast independently.

## Build credible proof

Place testimonials, measurable outcomes, case studies, credentials, and client evidence near the claim they validate. Prefer specific problem, approach, and result narratives over anonymous praise or undifferentiated logo walls.

## Finish navigation and footers

Headers must balance brand, navigation, and primary action. Footer typography should be subordinate to page headings, link groups should be compact and labeled, and the logo or organization name needs clear separation from its description. Dense footers succeed through grouping rather than oversized gaps.

An overlay header is an enhancement, not the baseline. Render an opaque, readable header without JavaScript; enable transparency only when a qualifying hero is present; verify logo, navigation, action, menu, and focus contrast over the actual image; reserve hero top space so content is not covered; and switch to a solid surface after meaningful scroll. Recheck the mobile crop and open-menu state independently.

## Avoid recurring failure modes

- A hero composed only of large text when the subject benefits from imagery.
- A responsive hero crop that removes its focal subject or compromises text legibility.
- Low-contrast text placed over light imagery or gradients.
- A headline whose last word sits alone on its final line.
- Sections whose content widths shift without a clear reason.
- Empty white gaps between sections or around curved transitions.
- Oversized CTA bands with too little content.
- Huge organization names and widely scattered footer links.
- Mobile headers, footers, forms, or sections that lose their horizontal gutters.
- Decorative card grids used where an editorial composition would tell the story better.
- Arbitrary rounded shapes applied to every image, input, and panel.
- Form controls whose visual treatment changes their expected geometry or reduces usability.
- Plain bullet lists where a semantic Cinderwell Icon List would improve scanning.
