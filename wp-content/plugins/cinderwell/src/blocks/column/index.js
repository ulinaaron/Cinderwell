import { getBlockTypes, registerBlockType } from "@wordpress/blocks";
import {
  InnerBlocks,
  InspectorControls,
  useBlockProps,
  useInnerBlocksProps,
} from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import {
  BlockIdentity,
  SegmentedControl,
} from "../../shared/inspector-controls";
import metadata from "./block.json";

const structuralBlocks = new Set([
  "cinderwell/column",
  "cinderwell/columns",
  "cinderwell/mega-menu-column",
  "cinderwell/tab-item",
]);

const getAllowedBlocks = () =>
  getBlockTypes()
    .map((blockType) => blockType.name)
    .filter(
      (name) => name.startsWith("cinderwell/") && !structuralBlocks.has(name),
    );

registerBlockType(metadata.name, {
  edit: ({ attributes, setAttributes }) => {
    const className = `cinderwell-column cinderwell-column--proportion-${
      attributes.proportion || "1"
    }`;
    const blockProps = useBlockProps({ className });
    const innerBlocksProps = useInnerBlocksProps(
      { className: "cinderwell-column__inner" },
      { allowedBlocks: getAllowedBlocks(), templateLock: false },
    );

    return (
      <>
        <InspectorControls>
          <BlockIdentity
            icon="▯"
            title={__("Column", "cinderwell")}
            description={__("Drop Cinderwell blocks here", "cinderwell")}
          />
          <div className="cw-panel cw-access-layout cw-column-proportion-control">
            <SegmentedControl
              label={__("Width proportion", "cinderwell")}
              value={attributes.proportion || "1"}
              options={[
                { value: "1", label: "1×" },
                { value: "2", label: "2×" },
                { value: "3", label: "3×" },
              ]}
              onChange={(proportion) => setAttributes({ proportion })}
              help={__(
                "Relative to the other columns. Use 1× + 2× for a one-third/two-thirds layout.",
                "cinderwell",
              )}
            />
          </div>
        </InspectorControls>
        <div {...blockProps}>
          <div {...innerBlocksProps} />
        </div>
      </>
    );
  },
  save: ({ attributes }) => {
    const blockProps = useBlockProps.save({
      className: `cinderwell-column cinderwell-column--proportion-${
        attributes.proportion || "1"
      }`,
    });

    return (
      <div {...blockProps}>
        <div className="cinderwell-column__inner">
          <InnerBlocks.Content />
        </div>
      </div>
    );
  },
});
