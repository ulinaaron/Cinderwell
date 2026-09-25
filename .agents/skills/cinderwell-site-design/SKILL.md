---
name: cinderwell-site-design
description: Design, build, or critique polished marketing and demonstration websites made with Cinderwell blocks. Use for page architecture, visual direction, section composition, content hierarchy, responsive presentation, and accessibility-aware design decisions in Cinderwell sites; do not use for unrelated WordPress implementation work.
---

# Cinderwell Site Design

Create expressive, coherent Cinderwell websites that serve their audiences, reflect the client's brand, and remain intuitive for the people who will maintain them. Keep the editor and frontend aligned, accessible, and resilient by using native controls and reusable presentation systems.

## Start with the assignment

Identify the audience, desired action, brand character, required pages, content depth, and trust burden. Inspect the active child theme and current Cinderwell block inventory before designing. Preserve the user's supplied brand direction and content intent.

Use these references selectively:

- Read [first-pass-direction.md](references/first-pass-direction.md) before a new full-site build, a substantial redesign, or any assignment where the user expects an expressive first draft.
- Read [design-principles.md](references/design-principles.md) when establishing visual direction, page rhythm, or reviewing a composition.
- Read [composition-patterns.md](references/composition-patterns.md) when outlining pages or choosing section sequences.
- Read [cinderwell-authoring.md](references/cinderwell-authoring.md) before implementing pages, patterns, variations, or child-theme presentation.
- Read [accessibility-qa.md](references/accessibility-qa.md) whenever implementing or validating an editor or frontend experience.
- Read [reference-library.md](references/reference-library.md) when a concrete precedent would improve a design decision.

## Tool preference

Prefer an available WordPress MCP connection for supported, structured site operations such as inspecting pages, reading block content, and making scoped content updates. Use Studio CLI or WP-CLI when MCP lacks the required capability, for filesystem and runtime work, or when deterministic bulk processing is more appropriate. Do not recreate through shell scripts what MCP already exposes directly. After any content write, verify block validity in Gutenberg and confirm the published frontend.

## Working method

1. Define a short visual and editorial thesis for the site. A thesis should be specific enough to reject attractive but inappropriate ideas.
2. Turn that thesis into a small brand recipe before page assembly: alignment system, structural signature, surface palette, type behavior, media treatment, action grammar, responsive header behavior, and one subject-specific motif. Write a short rejection list naming the hero, section sequence, and layout habits from recent builds that this site must not repeat.
3. Outline each page as a story. Give every section one primary job and identify the next action it supports. Match real content types to the relevant Cinderwell modules rather than flattening events, people, metrics, posts, or forms into generic cards.
4. Build and render a representative homepage slice before filling the whole site. It should be sufficient to judge the header and brand lockup, hero, first transition, at least one characteristic content treatment, a conversion surface, and the footer. Review it at desktop and narrow widths. If the result still depends on color or imagery to feel distinct, revise the structure now rather than polishing the generic skeleton.
5. Choose a small number of recurring visual devices: imagery treatment, surface changes, typography contrast, geometry, texture, or illustration. Do not apply every device everywhere. Give gradients and textures a brand or subject rationale rather than using them as filler.
6. Assemble pages with `cinderwell/*` blocks. Before writing variation CSS, inspect the current block controls, serialized attributes, and registered tokens; use those contracts for width, spacing, surfaces, typography, and actions before adding presentation code. Keep site-specific token registration, patterns, variations, and scoped CSS in the active child theme or a Cinderwell extension plugin. Register the client brand through Cinderwell's token manifest before styling blocks; CSS custom-property overrides alone are not a valid token integration because editor controls, reset values, and contrast logic retain the base defaults. On Cinderwell Starter, do not repeat manifest-backed palette, layout widths, canvas colors, or link colors in a child `theme.json`; reserve it for settings outside the token contract. Verify the resolved editor registry and frontend computed presentation early so parent defaults do not leak into the design.
7. Run a distinctiveness pass before polish. Compare the rendered skeleton against at least the two most recent relevant builds with branding and content mentally removed. If the hero mode, content placement, accent geometry, action arrangement, and section sequence substantially repeat a prior site or violate the rejection list, revise the composition or presentation variations; a new split ratio or palette is not enough.
8. Inspect the rendered page at representative desktop and narrow widths. Fix alignment, spacing, contrast, overflow, and reading-order problems in the composition rather than masking them with arbitrary spacers.
9. Validate the editor and published frontend. The editor must present the same named variations, brand tokens, typography, surfaces, and responsive intent as the published page. Treat visible drift or any “Attempt Recovery” state as a failed build. Accessibility is part of acceptance, not a later polish pass.

## Quality bar

A finished page should:

- communicate its purpose and primary action quickly;
- keep at least two words together on the final visual line of every headline;
- use meaningful visual accompaniment where imagery or illustration strengthens comprehension or emotion;
- maintain deliberate content widths and alignments across adjacent sections;
- establish a recognizable structural identity beyond its palette, typeface, and imagery;
- present a coherent brand lockup, navigation state, action hierarchy, and footer from the first rendered draft;
- use a deliberate link-versus-button grammar and make conversion controls belong to their actual surface;
- replace generic framework colors and treatments with a complete child-theme surface system rather than scattered overrides;
- keep routine content, layout, and presentation changes understandable and achievable through the editor; controls must accurately represent their frontend effect, and variation-owned decisions must be clearly identified rather than silently overridden;
- preserve intentional horizontal gutters and image focal points at responsive widths;
- vary section forms while preserving a coherent type, spacing, and color system;
- place proof near the claim it supports;
- use whitespace to establish hierarchy, never as unexplained dead space;
- end with a useful next step rather than merely stopping;
- remain understandable and operable without animation or pointer input.

Do not mistake novelty for quality. Prefer a clear, memorable composition over a collage of fashionable treatments.

## Product-gap rule

When the intended composition cannot be expressed through the current Cinderwell contract, stop and identify the reusable gap. Prefer, in order:

1. an existing block setting or variation;
2. a registered presentation variation with scoped child-theme CSS;
3. an extension to an existing Cinderwell block;
4. a new reusable block only when it represents a distinct content or interaction model.

Do not solve the gap with Core Group, Columns, Cover, or Custom HTML page composition.

## Evolving the skill

Add a reference only when it contributes a distinct lesson. Record what works, what problem it solves, what should not be copied, accessibility risks, and the relevant Cinderwell translation. Revise narrow guidance when real builds or QA disprove it; avoid turning one site's preference into a universal rule.
