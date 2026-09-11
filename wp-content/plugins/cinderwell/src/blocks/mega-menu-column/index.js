import { registerBlockType } from "@wordpress/blocks";
import {
  InspectorControls,
  RichText,
  useBlockProps,
  useInnerBlocksProps,
} from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import { ConditionsPanel } from "../../shared/conditions-panel";
import { BlockIdentity } from "../../shared/inspector-controls";
import metadata from "./block.json";

const ALLOWED_BLOCKS = [
  "cinderwell/link",
  "cinderwell/button",
  "cinderwell/note",
  "cinderwell/image",
];

registerBlockType(metadata.name, {
  edit: ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps({
      className: "cinderwell-mega-menu__column",
    });
    const innerBlocksProps = useInnerBlocksProps(
      { className: "cinderwell-mega-menu__column-content" },
      { allowedBlocks: ALLOWED_BLOCKS, templateLock: false },
    );

    return (
      <>
        <InspectorControls>
          <BlockIdentity
            icon="▥"
            title={__("Mega Menu Column", "cinderwell")}
            description={__("Curated navigation group", "cinderwell")}
          />
          <ConditionsPanel
            attributes={attributes}
            setAttributes={setAttributes}
          />
        </InspectorControls>
        <div {...blockProps}>
          <RichText
            tagName="h3"
            className="cinderwell-mega-menu__column-heading"
            value={attributes.heading}
            onChange={(heading) => setAttributes({ heading })}
            placeholder={__("Group heading…", "cinderwell")}
            allowedFormats={[]}
          />
          <div {...innerBlocksProps} />
        </div>
      </>
    );
  },
  save: ({ attributes }) => {
    const blockProps = useBlockProps.save({
      className: "cinderwell-mega-menu__column",
    });
    const innerBlocksProps = useInnerBlocksProps.save({
      className: "cinderwell-mega-menu__column-content",
    });

    return (
      <div {...blockProps}>
        <RichText.Content
          tagName="h3"
          className="cinderwell-mega-menu__column-heading"
          value={attributes.heading}
        />
        <div {...innerBlocksProps} />
      </div>
    );
  },
});
