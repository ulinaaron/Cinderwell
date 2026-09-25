import { createBlock, registerBlockType } from "@wordpress/blocks";
import {
  InspectorControls,
  RichText,
  useBlockProps,
  useInnerBlocksProps,
} from "@wordpress/block-editor";
import { Button, PanelBody } from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { ConditionsPanel } from "../../shared/conditions-panel";
import { filterEditorAccessChanges } from "../../shared/editor-access";
import { IconSettingsControl } from "../../shared/icon-controls";
import {
  BackgroundControls,
  BlockIdentity,
  LayoutControls,
  SectionToggles,
  SegmentedControl,
  TypographyControls,
  getBackgroundImageProps,
  getHeadingTagName,
  getTextStyleClassName,
  getTypographyClassName,
} from "../../shared/inspector-controls";
import {
  ALLOWED_BODY_FORMATS,
  ALLOWED_INLINE_FORMATS,
} from "../../shared/rich-text";
import {
  VariationPicker,
  resolveBlockVariation,
} from "../../shared/variation-picker";
import metadata from "./block.json";

const ALLOWED_BLOCKS = ["cinderwell/icon-list-item"];
const TEMPLATE = [
  ["cinderwell/icon-list-item"],
  ["cinderwell/icon-list-item"],
  ["cinderwell/icon-list-item"],
];

const getBaseClassName = (attributes) =>
  [
    "cinderwell-icon-list",
    `cinderwell-icon-list--size-${attributes.iconSize || "md"}`,
    `cinderwell-icon-list--color-${attributes.iconColor || "brand"}`,
    `cinderwell-icon-list--treatment-${attributes.iconTreatment || "plain"}`,
    `cinderwell-icon-list--gap-${attributes.gap || "standard"}`,
  ].join(" ");

const hasIntroduction = (attributes) =>
  Boolean(
    attributes.showEyebrow ||
      attributes.showHeading ||
      attributes.showDescription ||
      attributes.showFootnote,
  );

const usesSectionLayout = (attributes, layout = attributes.layout) =>
  (layout || "standard") !== "standard" || hasIntroduction(attributes);

const getSectionClassName = (attributes, layoutClassName = "") =>
  `${getBaseClassName(
    attributes,
  )} cinderwell-icon-list--section${layoutClassName} cinderwell-icon-list--bg-${
    attributes.background || "white"
  }${getTypographyClassName(attributes)}`;

const Introduction = ({ attributes, setAttributes }) => (
  <div className="cinderwell-icon-list__intro">
    {attributes.showEyebrow &&
      (setAttributes ? (
        <RichText
          tagName="span"
          className={`cinderwell-eyebrow${getTextStyleClassName(
            attributes,
            "eyebrow",
          )}`}
          value={attributes.eyebrow}
          onChange={(eyebrow) => setAttributes({ eyebrow })}
          placeholder={__("Eyebrow…", "cinderwell")}
          allowedFormats={[]}
        />
      ) : (
        attributes.eyebrow && (
          <RichText.Content
            tagName="span"
            className={`cinderwell-eyebrow${getTextStyleClassName(
              attributes,
              "eyebrow",
            )}`}
            value={attributes.eyebrow}
          />
        )
      ))}
    {attributes.showHeading &&
      (setAttributes ? (
        <RichText
          tagName={getHeadingTagName(attributes.headingLevel)}
          className={`cinderwell-heading${getTextStyleClassName(
            attributes,
            "heading",
          )}`}
          value={attributes.heading}
          onChange={(heading) => setAttributes({ heading })}
          placeholder={__("Heading…", "cinderwell")}
          allowedFormats={[]}
        />
      ) : (
        attributes.heading && (
          <RichText.Content
            tagName={getHeadingTagName(attributes.headingLevel)}
            className={`cinderwell-heading${getTextStyleClassName(
              attributes,
              "heading",
            )}`}
            value={attributes.heading}
          />
        )
      ))}
    {attributes.showDescription &&
      (setAttributes ? (
        <RichText
          tagName="div"
          className={`cinderwell-icon-list__description${getTextStyleClassName(
            attributes,
            "description",
          )}`}
          value={attributes.description}
          onChange={(description) => setAttributes({ description })}
          placeholder={__("Description…", "cinderwell")}
          allowedFormats={ALLOWED_BODY_FORMATS}
        />
      ) : (
        attributes.description && (
          <RichText.Content
            tagName="div"
            className={`cinderwell-icon-list__description${getTextStyleClassName(
              attributes,
              "description",
            )}`}
            value={attributes.description}
          />
        )
      ))}
    {attributes.showFootnote &&
      (setAttributes ? (
        <RichText
          tagName="p"
          className={`cinderwell-footnote${getTextStyleClassName(
            attributes,
            "footnote",
          )}`}
          value={attributes.footnote}
          onChange={(footnote) => setAttributes({ footnote })}
          placeholder={__("Footnote…", "cinderwell")}
          allowedFormats={ALLOWED_INLINE_FORMATS}
        />
      ) : (
        attributes.footnote && (
          <RichText.Content
            tagName="p"
            className={`cinderwell-footnote${getTextStyleClassName(
              attributes,
              "footnote",
            )}`}
            value={attributes.footnote}
          />
        )
      ))}
  </div>
);

registerBlockType(metadata.name, {
  edit: ({ attributes, setAttributes, clientId }) => {
    const itemBlocks = useSelect(
      (select) => select("core/block-editor").getBlocks(clientId),
      [clientId],
    );
    const { insertBlocks, selectBlock, updateBlockAttributes } =
      useDispatch("core/block-editor");
    const resolvedLayout = resolveBlockVariation(
      metadata.name,
      attributes.layout,
      "standard",
    );
    const activeLayout = resolvedLayout?.slug || "standard";
    const sectionLayout = usesSectionLayout(attributes, activeLayout);
    const layoutClassName =
      activeLayout === "standard"
        ? ""
        : ` cinderwell-icon-list--layout-${activeLayout}`;
    const blockProps = useBlockProps(
      sectionLayout
        ? getBackgroundImageProps(
            { className: getSectionClassName(attributes, layoutClassName) },
            attributes,
          )
        : { className: getBaseClassName(attributes) },
    );
    const innerBlocksProps = useInnerBlocksProps(
      { className: "cinderwell-icon-list__items" },
      {
        allowedBlocks: ALLOWED_BLOCKS,
        template: TEMPLATE,
        templateLock: false,
        renderAppender: false,
      },
    );

    const updateDefaultIcon = (changes) => {
      setAttributes(changes);
      const childChanges = {};
      Object.entries({
        defaultIcon: "icon",
        defaultIconSource: "iconSource",
        defaultIconSvg: "iconSvg",
        defaultIconViewBox: "iconViewBox",
        defaultIconSvgId: "iconSvgId",
        defaultIconSvgUrl: "iconSvgUrl",
      }).forEach(([parentKey, childKey]) => {
        if (Object.prototype.hasOwnProperty.call(changes, parentKey))
          childChanges[childKey] = changes[parentKey];
      });
      itemBlocks
        .filter((block) => block.attributes.useDefaultIcon !== false)
        .forEach((block) =>
          updateBlockAttributes(block.clientId, childChanges),
        );
    };

    const addItem = () => {
      const item = createBlock("cinderwell/icon-list-item", {
        icon: attributes.defaultIcon,
        iconSource: attributes.defaultIconSource,
        iconSvg: attributes.defaultIconSvg,
        iconViewBox: attributes.defaultIconViewBox,
        iconSvgId: attributes.defaultIconSvgId,
        iconSvgUrl: attributes.defaultIconSvgUrl,
      });
      insertBlocks(item, itemBlocks.length, clientId);
      selectBlock(item.clientId);
    };

    const sections = {
      eyebrow: attributes.showEyebrow,
      heading: attributes.showHeading,
      description: attributes.showDescription,
      footnote: attributes.showFootnote,
    };
    const listEditor = (
      <div className="cinderwell-icon-list__list">
        <ul {...innerBlocksProps} />
        <Button
          variant="secondary"
          className="cinderwell-icon-list__add"
          onClick={addItem}
        >
          + {__("Add item", "cinderwell")}
        </Button>
      </div>
    );

    return (
      <>
        <InspectorControls>
          <BlockIdentity
            icon="✓"
            title={__("Icon List", "cinderwell")}
            description={__(
              "Semantic list with an optional editorial introduction",
              "cinderwell",
            )}
          />
          <VariationPicker
            blockName={metadata.name}
            value={attributes.layout}
            fallback="standard"
            title={__("Variation", "cinderwell")}
            onChange={(variation) =>
              setAttributes(
                filterEditorAccessChanges(
                  variation.attributes || {},
                  attributes,
                ),
              )
            }
          />
          <PanelBody
            title={__("Content", "cinderwell")}
            initialOpen={sectionLayout}
            className="cw-panel cw-access-content"
          >
            <SectionToggles
              sections={[
                { key: "eyebrow", label: __("Eyebrow", "cinderwell") },
                { key: "heading", label: __("Heading", "cinderwell") },
                {
                  key: "description",
                  label: __("Description", "cinderwell"),
                },
                { key: "footnote", label: __("Footnote", "cinderwell") },
              ]}
              values={sections}
              onChange={(value) =>
                setAttributes({
                  showEyebrow: value.eyebrow,
                  showHeading: value.heading,
                  showDescription: value.description,
                  showFootnote: value.footnote,
                })
              }
            />
          </PanelBody>
          <PanelBody
            title={__("Default icon", "cinderwell")}
            initialOpen={!sectionLayout}
            className="cw-panel cw-access-appearance"
          >
            <IconSettingsControl
              icon={attributes.defaultIcon}
              source={attributes.defaultIconSource}
              customSvg={attributes.defaultIconSvg}
              customViewBox={attributes.defaultIconViewBox}
              customSvgId={attributes.defaultIconSvgId}
              customSvgUrl={attributes.defaultIconSvgUrl}
              size={attributes.iconSize}
              color={attributes.iconColor}
              treatment={attributes.iconTreatment}
              alignment="left"
              showAlignment={false}
              label={__("List default", "cinderwell")}
              onIconChange={(defaultIcon) => updateDefaultIcon({ defaultIcon })}
              onSourceChange={(defaultIconSource) =>
                updateDefaultIcon({ defaultIconSource })
              }
              onCustomSvgChange={(
                defaultIconSvg,
                defaultIconViewBox,
                defaultIconSvgId,
                defaultIconSvgUrl,
              ) =>
                updateDefaultIcon({
                  defaultIconSvg,
                  defaultIconViewBox,
                  defaultIconSvgId,
                  defaultIconSvgUrl,
                })
              }
              onSizeChange={(iconSize) => setAttributes({ iconSize })}
              onColorChange={(iconColor) => setAttributes({ iconColor })}
              onTreatmentChange={(iconTreatment) =>
                setAttributes({ iconTreatment })
              }
              onAlignmentChange={() => {}}
            />
          </PanelBody>
          <PanelBody
            title={__("List layout", "cinderwell")}
            initialOpen={false}
            className="cw-panel cw-access-layout"
          >
            <SegmentedControl
              label={__("Item spacing", "cinderwell")}
              value={attributes.gap || "standard"}
              options={[
                { value: "compact", label: __("Compact", "cinderwell") },
                { value: "standard", label: __("Standard", "cinderwell") },
                { value: "relaxed", label: __("Relaxed", "cinderwell") },
              ]}
              onChange={(gap) => setAttributes({ gap })}
            />
          </PanelBody>
          {sectionLayout ? (
            <>
              <LayoutControls
                controlled={resolvedLayout?.controlled || {}}
                attributes={attributes}
                setAttributes={setAttributes}
              />
              <BackgroundControls
                controlled={resolvedLayout?.controlled || {}}
                value={attributes.background}
                onChange={(background) => setAttributes({ background })}
                attributes={attributes}
                setAttributes={setAttributes}
              />
              <TypographyControls
                controlled={resolvedLayout?.controlled || {}}
                attributes={attributes}
                setAttributes={setAttributes}
                sections={[
                  {
                    key: "eyebrow",
                    label: __("Eyebrow", "cinderwell"),
                    enabled: attributes.showEyebrow,
                  },
                  {
                    key: "heading",
                    label: __("Heading", "cinderwell"),
                    enabled: attributes.showHeading,
                  },
                  {
                    key: "description",
                    label: __("Description", "cinderwell"),
                    enabled: attributes.showDescription,
                  },
                  {
                    key: "footnote",
                    label: __("Footnote", "cinderwell"),
                    enabled: attributes.showFootnote,
                  },
                ]}
              />
            </>
          ) : (
            <ConditionsPanel
              attributes={attributes}
              setAttributes={setAttributes}
            />
          )}
        </InspectorControls>
        {sectionLayout ? (
          <section {...blockProps}>
            <div
              className="cinderwell-icon-list__inner"
              style={{ maxWidth: `var(--cw-width-${attributes.width})` }}
            >
              <Introduction
                attributes={attributes}
                setAttributes={setAttributes}
              />
              {listEditor}
            </div>
          </section>
        ) : (
          <div {...blockProps}>
            <ul {...innerBlocksProps} />
            <Button
              variant="secondary"
              className="cinderwell-icon-list__add"
              onClick={addItem}
            >
              + {__("Add item", "cinderwell")}
            </Button>
          </div>
        )}
      </>
    );
  },
  save: ({ attributes }) => {
    const sectionLayout = usesSectionLayout(attributes);
    const blockProps = useBlockProps.save(
      sectionLayout
        ? getBackgroundImageProps(
            { className: getSectionClassName(attributes) },
            attributes,
          )
        : { className: getBaseClassName(attributes) },
    );
    const innerBlocksProps = useInnerBlocksProps.save({
      className: "cinderwell-icon-list__items",
    });

    if (!sectionLayout) {
      return (
        <div {...blockProps}>
          <ul {...innerBlocksProps} />
        </div>
      );
    }

    return (
      <section {...blockProps}>
        <div
          className="cinderwell-icon-list__inner"
          style={{ maxWidth: `var(--cw-width-${attributes.width})` }}
        >
          <Introduction attributes={attributes} />
          <div className="cinderwell-icon-list__list">
            <ul {...innerBlocksProps} />
          </div>
        </div>
      </section>
    );
  },
});
