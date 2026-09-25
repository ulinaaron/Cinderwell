import { Button, Notice, PanelBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

const fallbackLoopVariations = {
  cards: {
    slug: "cards",
    label: __("Cards", "cinderwell"),
    description: __("A balanced grid of image-led cards.", "cinderwell"),
    preview: "cards",
    order: 10,
    attributes: {
      layout: "cards",
      columns: "3",
      columnsTablet: "2",
      columnsMobile: "1",
      imageAspect: "landscape",
    },
  },
};

const fallbackCardGridVariations = {
  raised: {
    slug: "raised",
    label: __("Raised", "cinderwell"),
    description: __("Elevated cards with a restrained hover lift.", "cinderwell"),
    preview: "raised",
    order: 10,
    attributes: {
      layout: "raised",
      columns: "3",
      columnsTablet: "",
      columnsMobile: "",
    },
  },
};

const fallbackHeroVariations = {
  split: {
    slug: "split",
    label: __("Split", "cinderwell"),
    description: __("The dependable 50/50 content and image composition.", "cinderwell"),
    preview: "hero-split",
    order: 10,
    attributes: {
      layout: "split",
      alignment: "left",
      width: "full",
      imageSide: "right",
      splitGap: "md",
    },
  },
};

export const getBlockVariations = (blockName) => {
  const registered = window.cinderwellEditorSettings?.variations?.[blockName];
  if (registered && Object.keys(registered).length) return registered;
  if (blockName === "cinderwell/loop") return fallbackLoopVariations;
  if (blockName === "cinderwell/card-grid") return fallbackCardGridVariations;
  if (blockName === "cinderwell/hero") return fallbackHeroVariations;
  return {};
};

export const resolveBlockVariation = (blockName, slug, fallback = "cards") => {
  const variations = getBlockVariations(blockName);
  return (
    variations[slug] || variations[fallback] || Object.values(variations)[0]
  );
};

export const VariationPicker = ({
  blockName,
  value,
  onChange,
  fallback = "cards",
  title = __("Layout", "cinderwell"),
}) => {
  const variations = getBlockVariations(blockName);
  const options = Object.values(variations)
    .filter((variation) => variation.visible !== false)
    .sort((left, right) => (left.order || 100) - (right.order || 100));
  const isUnavailable = Boolean(value && !variations[value]);
  const active = isUnavailable ? fallback : value || fallback;

  if (!options.length) return null;

  return (
    <PanelBody
      title={title}
      initialOpen={true}
      className="cw-panel cw-access-layout cw-variation-panel"
    >
      {isUnavailable && (
        <Notice status="warning" isDismissible={false}>
          {__(
            "This variation is unavailable. The default is being used until another variation is selected.",
            "cinderwell",
          )}
        </Notice>
      )}
      <div className="cw-variation-picker">
        {options.map((variation) => {
          const selected = variation.slug === active;
          return (
            <Button
              key={variation.slug}
              className={`cw-variation-picker__option${
                selected ? " is-active" : ""
              }${variation.custom ? " is-custom" : ""}`}
              aria-pressed={selected}
              onClick={() => onChange(variation)}
            >
              <span
                className={`cw-variation-picker__preview cw-variation-picker__preview--${
                  variation.preview || variation.slug
                }`}
                aria-hidden="true"
              >
                <span />
                <span />
                <span />
              </span>
              <span className="cw-variation-picker__label">
                {variation.label}
              </span>
              {variation.custom && (
                <span className="cw-variation-picker__badge">
                  {__("Custom", "cinderwell")}
                </span>
              )}
              <span className="screen-reader-text">
                {variation.description}
              </span>
            </Button>
          );
        })}
      </div>
    </PanelBody>
  );
};
