import { createBlock, registerBlockType } from "@wordpress/blocks";
import {
  InspectorControls,
  useBlockProps,
  useInnerBlocksProps,
} from "@wordpress/block-editor";
import { Button, PanelBody } from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { ConditionsPanel } from "../../shared/conditions-panel";
import { IconSettingsControl } from "../../shared/icon-controls";
import {
  BlockIdentity,
  SegmentedControl,
} from "../../shared/inspector-controls";
import metadata from "./block.json";

const ALLOWED_BLOCKS = ["cinderwell/icon-list-item"];
const TEMPLATE = [
  ["cinderwell/icon-list-item"],
  ["cinderwell/icon-list-item"],
  ["cinderwell/icon-list-item"],
];

const getClassName = (attributes) =>
  [
    "cinderwell-icon-list",
    `cinderwell-icon-list--size-${attributes.iconSize || "md"}`,
    `cinderwell-icon-list--color-${attributes.iconColor || "brand"}`,
    `cinderwell-icon-list--treatment-${attributes.iconTreatment || "plain"}`,
    `cinderwell-icon-list--gap-${attributes.gap || "standard"}`,
  ].join(" ");

registerBlockType(metadata.name, {
  edit: ({ attributes, setAttributes, clientId }) => {
    const itemBlocks = useSelect(
      (select) => select("core/block-editor").getBlocks(clientId),
      [clientId],
    );
    const { insertBlocks, selectBlock, updateBlockAttributes } =
      useDispatch("core/block-editor");
    const blockProps = useBlockProps({ className: getClassName(attributes) });
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

    return (
      <>
        <InspectorControls>
          <BlockIdentity
            icon="✓"
            title={__("Icon List", "cinderwell")}
            description={__(
              "Semantic list with individual icons",
              "cinderwell",
            )}
          />
          <PanelBody
            title={__("Default icon", "cinderwell")}
            initialOpen={true}
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
            title={__("Layout", "cinderwell")}
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
          <ConditionsPanel
            attributes={attributes}
            setAttributes={setAttributes}
          />
        </InspectorControls>
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
      </>
    );
  },
  save: ({ attributes }) => {
    const blockProps = useBlockProps.save({
      className: getClassName(attributes),
    });
    const innerBlocksProps = useInnerBlocksProps.save({
      className: "cinderwell-icon-list__items",
    });

    return (
      <div {...blockProps}>
        <ul {...innerBlocksProps} />
      </div>
    );
  },
});
