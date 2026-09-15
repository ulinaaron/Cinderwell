import { registerBlockType, store as blocksStore } from "@wordpress/blocks";
import {
  InnerBlocks,
  InspectorControls,
  RichText,
  store as blockEditorStore,
  useBlockProps,
  useInnerBlocksProps,
} from "@wordpress/block-editor";
import { PanelBody } from "@wordpress/components";
import { dispatch, useSelect } from "@wordpress/data";
import { addFilter } from "@wordpress/hooks";
import { __ } from "@wordpress/i18n";
import { ConditionsPanel } from "../../shared/conditions-panel";
import { backgroundOptions } from "../../shared/design-system";
import {
  BlockIdentity,
  SegmentedControl,
} from "../../shared/inspector-controls";
import metadata from "./block.json";

const ALLOWED_BLOCKS = ["cinderwell/mega-menu-column"];
const TEMPLATE = [
  [
    "cinderwell/mega-menu-column",
    { heading: "Discover" },
    [
      ["cinderwell/link", { text: "Overview", url: "#" }],
      ["cinderwell/link", { text: "What we do", url: "#" }],
    ],
  ],
  [
    "cinderwell/mega-menu-column",
    { heading: "Resources" },
    [
      ["cinderwell/link", { text: "Latest updates", url: "#" }],
      ["cinderwell/link", { text: "Helpful resources", url: "#" }],
    ],
  ],
  [
    "cinderwell/mega-menu-column",
    { heading: "Featured" },
    [
      [
        "cinderwell/note",
        {
          content:
            "Use this space to guide visitors toward an important destination.",
          style: "caption",
        },
      ],
      [
        "cinderwell/button",
        { text: "Learn more", url: "#", variant: "primary", size: "sm" },
      ],
    ],
  ],
];

const addMegaMenuToNavigation = (settings, name) => {
  if (name !== "core/navigation") {
    return settings;
  }

  const allowedBlocks = Array.isArray(settings.allowedBlocks)
    ? settings.allowedBlocks
    : [];
  if (allowedBlocks.includes(metadata.name)) {
    return settings;
  }

  return { ...settings, allowedBlocks: [...allowedBlocks, metadata.name] };
};

addFilter(
  "blocks.registerBlockType",
  "cinderwell/mega-menu-navigation-child",
  addMegaMenuToNavigation,
);

// Core blocks may already be registered when an individual block asset loads.
// Reapplying the public registration filters keeps the Navigation inserter in sync.
dispatch(blocksStore).reapplyBlockTypeFilters();

const Chevron = () => (
  <span className="cinderwell-mega-menu__chevron" aria-hidden="true" />
);

registerBlockType(metadata.name, {
  edit: ({ attributes, clientId, isSelected, setAttributes }) => {
    const hasSelectedInnerBlock = useSelect(
      (select) =>
        select(blockEditorStore).hasSelectedInnerBlock(clientId, true),
      [clientId],
    );
    const isEditorOpen = isSelected || hasSelectedInnerBlock;
    const blockProps = useBlockProps({
      className: `wp-block-navigation-item cinderwell-mega-menu cinderwell-mega-menu--bg-${attributes.background} cinderwell-mega-menu--columns-${attributes.columns}${isEditorOpen ? " is-editor-open" : ""}`,
    });
    const innerBlocksProps = useInnerBlocksProps(
      { className: "cinderwell-mega-menu__panel-content" },
      {
        allowedBlocks: ALLOWED_BLOCKS,
        template: TEMPLATE,
        templateLock: false,
      },
    );

    return (
      <>
        <InspectorControls>
          <BlockIdentity
            icon="☰"
            title={__("Mega Menu", "cinderwell")}
            description={__("Wide navigation panel", "cinderwell")}
          />
          <PanelBody
            title={__("Layout", "cinderwell")}
            initialOpen={true}
            className="cw-panel cw-access-layout"
          >
            <SegmentedControl
              label={__("Columns", "cinderwell")}
              value={String(attributes.columns || 3)}
              options={[
                { label: __("Two", "cinderwell"), value: "2" },
                { label: __("Three", "cinderwell"), value: "3" },
                { label: __("Four", "cinderwell"), value: "4" },
              ]}
              onChange={(columns) =>
                setAttributes({ columns: Number(columns) })
              }
            />
            <SegmentedControl
              label={__("Panel surface", "cinderwell")}
              value={attributes.background || "white"}
              options={backgroundOptions}
              onChange={(background) => setAttributes({ background })}
            />
          </PanelBody>
          <ConditionsPanel
            attributes={attributes}
            setAttributes={setAttributes}
          />
        </InspectorControls>
        <li {...blockProps}>
          <button
            type="button"
            className="cinderwell-mega-menu__toggle wp-block-navigation-item__content"
            aria-expanded={isEditorOpen}
          >
            <RichText
              tagName="span"
              className="cinderwell-mega-menu__label"
              value={attributes.label}
              onChange={(label) => setAttributes({ label })}
              placeholder={__("Menu label…", "cinderwell")}
              allowedFormats={[]}
            />
            <Chevron />
          </button>
          <div
            className="cinderwell-mega-menu__panel"
            aria-hidden={!isEditorOpen}
          >
            <div {...innerBlocksProps} />
          </div>
        </li>
      </>
    );
  },
  save: ({ attributes }) => {
    const blockProps = useBlockProps.save({
      className: `wp-block-navigation-item cinderwell-mega-menu cinderwell-mega-menu--bg-${attributes.background} cinderwell-mega-menu--columns-${attributes.columns}`,
    });
    const innerBlocksProps = useInnerBlocksProps.save({
      className: "cinderwell-mega-menu__panel-content",
    });

    return (
      <li {...blockProps}>
        <button
          type="button"
          className="cinderwell-mega-menu__toggle wp-block-navigation-item__content"
          aria-expanded="false"
        >
          <RichText.Content
            tagName="span"
            className="cinderwell-mega-menu__label"
            value={attributes.label}
          />
          <Chevron />
        </button>
        <div className="cinderwell-mega-menu__panel">
          <div {...innerBlocksProps} />
        </div>
      </li>
    );
  },
});
