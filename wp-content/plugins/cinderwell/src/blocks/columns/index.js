import { createBlock, registerBlockType } from "@wordpress/blocks";
import {
  InnerBlocks,
  InspectorControls,
  useBlockProps,
  useInnerBlocksProps,
} from "@wordpress/block-editor";
import { Button, PanelBody } from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { __, sprintf } from "@wordpress/i18n";
import {
  BackgroundControls,
  BlockIdentity,
  ChildBlockInheritanceControl,
  LayoutControls,
  SegmentedControl,
  ToggleRow,
  getBackgroundImageProps,
} from "../../shared/inspector-controls";
import { filterEditorAccessChanges } from "../../shared/editor-access";
import {
  VariationPicker,
  resolveBlockVariation,
} from "../../shared/variation-picker";
import metadata from "./block.json";

const TEMPLATE = [["cinderwell/column"], ["cinderwell/column"]];

registerBlockType(metadata.name, {
  edit: ({ attributes, setAttributes, clientId }) => {
    const resolvedLayout = resolveBlockVariation(
      metadata.name,
      attributes.layout,
      "standard",
    );
    const activeLayout = resolvedLayout.slug || "standard";
    const layoutClassName =
      activeLayout === "standard"
        ? ""
        : ` cinderwell-columns--layout-${activeLayout}`;
    const columnCount = useSelect(
      (select) => select("core/block-editor").getBlockCount(clientId),
      [clientId],
    );
    const { insertBlock } = useDispatch("core/block-editor");
    const childBackgroundClass =
      attributes.childBackgroundMode === "individual"
        ? " cinderwell-columns--children-own-backgrounds"
        : "";
    const className = `cinderwell-columns${layoutClassName} cinderwell-columns--bg-${
      attributes.background
    } cinderwell-columns--gap-${attributes.gap} cinderwell-columns--stack-${
      attributes.stackAt
    } cinderwell-columns--align-${attributes.verticalAlignment}${
      attributes.reverseOnMobile ? " cinderwell-columns--reverse-mobile" : ""
    }${childBackgroundClass}`;
    const blockProps = useBlockProps(
      getBackgroundImageProps({ className }, attributes),
    );
    const innerBlocksProps = useInnerBlocksProps(
      { className: "cinderwell-columns__grid" },
      {
        allowedBlocks: ["cinderwell/column"],
        template: TEMPLATE,
        templateLock: false,
        renderAppender:
          columnCount < 4 ? InnerBlocks.ButtonBlockAppender : false,
      },
    );
    const addColumn = () =>
      insertBlock(createBlock("cinderwell/column"), columnCount, clientId);

    return (
      <>
        <InspectorControls>
          <BlockIdentity
            icon="▥"
            title={__("Columns", "cinderwell")}
            description={__(
              "Drag Cinderwell blocks into responsive columns",
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
            title={__("Columns", "cinderwell")}
            initialOpen={true}
            className="cw-panel cw-access-layout"
          >
            <p className="cw-columns-count">
              {sprintf(__("%d of 4 columns", "cinderwell"), columnCount)}
            </p>
            <Button
              variant="secondary"
              onClick={addColumn}
              disabled={columnCount >= 4}
            >
              {__("Add column", "cinderwell")}
            </Button>
            <SegmentedControl
              label={__("Vertical alignment", "cinderwell")}
              value={attributes.verticalAlignment || "stretch"}
              options={[
                { value: "start", label: __("Top", "cinderwell") },
                { value: "center", label: __("Center", "cinderwell") },
                { value: "end", label: __("Bottom", "cinderwell") },
                { value: "stretch", label: __("Stretch", "cinderwell") },
              ]}
              onChange={(verticalAlignment) =>
                setAttributes({ verticalAlignment })
              }
            />
            <SegmentedControl
              label={__("Stack columns", "cinderwell")}
              value={attributes.stackAt || "mobile"}
              options={[
                { value: "mobile", label: __("Mobile", "cinderwell") },
                { value: "tablet", label: __("Tablet", "cinderwell") },
                { value: "never", label: __("Never", "cinderwell") },
              ]}
              onChange={(stackAt) => setAttributes({ stackAt })}
            />
            {attributes.stackAt !== "never" && (
              <ToggleRow
                label={__("Reverse when stacked", "cinderwell")}
                checked={attributes.reverseOnMobile}
                onChange={(reverseOnMobile) =>
                  setAttributes({ reverseOnMobile })
                }
              />
            )}
          </PanelBody>
          <PanelBody
            title={__("Gap", "cinderwell")}
            initialOpen={false}
            className="cw-panel cw-access-spacing"
          >
            <SegmentedControl
              label={__("Column gap", "cinderwell")}
              value={attributes.gap || "md"}
              options={[
                { value: "none", label: __("None", "cinderwell") },
                { value: "sm", label: __("Small", "cinderwell") },
                { value: "md", label: __("Medium", "cinderwell") },
                { value: "lg", label: __("Large", "cinderwell") },
                { value: "xl", label: __("XL", "cinderwell") },
              ]}
              onChange={(gap) => setAttributes({ gap })}
            />
          </PanelBody>
          <PanelBody
            title={__("Content surface", "cinderwell")}
            initialOpen={false}
            className="cw-panel cw-access-appearance"
          >
            <ChildBlockInheritanceControl
              value={attributes.childBackgroundMode || "inherit"}
              onChange={(childBackgroundMode) =>
                setAttributes({ childBackgroundMode })
              }
            />
          </PanelBody>
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
        </InspectorControls>
        <section {...blockProps}>
          <div
            className="cinderwell-columns__inner"
            style={{ maxWidth: `var(--cw-width-${attributes.width})` }}
          >
            <div {...innerBlocksProps} />
          </div>
        </section>
      </>
    );
  },
  save: ({ attributes }) => {
    const childBackgroundClass =
      attributes.childBackgroundMode === "individual"
        ? " cinderwell-columns--children-own-backgrounds"
        : "";
    const className = `cinderwell-columns cinderwell-columns--bg-${
      attributes.background
    } cinderwell-columns--gap-${attributes.gap} cinderwell-columns--stack-${
      attributes.stackAt
    } cinderwell-columns--align-${attributes.verticalAlignment}${
      attributes.reverseOnMobile ? " cinderwell-columns--reverse-mobile" : ""
    }${childBackgroundClass}`;
    const blockProps = useBlockProps.save(
      getBackgroundImageProps({ className }, attributes),
    );

    return (
      <section {...blockProps}>
        <div
          className="cinderwell-columns__inner"
          style={{ maxWidth: `var(--cw-width-${attributes.width})` }}
        >
          <div className="cinderwell-columns__grid">
            <InnerBlocks.Content />
          </div>
        </div>
      </section>
    );
  },
});
