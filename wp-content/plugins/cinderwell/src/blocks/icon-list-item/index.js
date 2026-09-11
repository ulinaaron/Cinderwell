import { registerBlockType } from "@wordpress/blocks";
import {
  InspectorControls,
  RichText,
  useBlockProps,
} from "@wordpress/block-editor";
import { PanelBody } from "@wordpress/components";
import { useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { IconSettingsControl } from "../../shared/icon-controls";
import { IconGlyph } from "../../shared/icon-library";
import {
  BlockIdentity,
  SegmentedControl,
} from "../../shared/inspector-controls";
import {
  LinkSettingsControl,
  getDynamicLinkValue,
} from "../../shared/link-control";
import { ALLOWED_INLINE_FORMATS } from "../../shared/rich-text";
import metadata from "./block.json";

const contextKeys = {
  icon: "cinderwell/iconListDefaultIcon",
  iconSource: "cinderwell/iconListDefaultIconSource",
  iconSvg: "cinderwell/iconListDefaultIconSvg",
  iconViewBox: "cinderwell/iconListDefaultIconViewBox",
  iconSvgId: "cinderwell/iconListDefaultIconSvgId",
  iconSvgUrl: "cinderwell/iconListDefaultIconSvgUrl",
};

const getResolvedIcon = (attributes, context) => {
  if (attributes.useDefaultIcon === false) return attributes;
  return Object.entries(contextKeys).reduce(
    (values, [attribute, contextKey]) => ({
      ...values,
      [attribute]: context[contextKey] ?? attributes[attribute],
    }),
    {},
  );
};

const ItemIcon = ({ values }) => (
  <span className="cinderwell-icon-list__icon" aria-hidden="true">
    <IconGlyph
      icon={values.icon || "check"}
      customSvg={values.iconSource === "custom" ? values.iconSvg : ""}
      viewBox={values.iconViewBox || "0 0 24 24"}
    />
  </span>
);

registerBlockType(metadata.name, {
  edit: ({ attributes, context, setAttributes }) => {
    const resolvedIcon = getResolvedIcon(attributes, context);
    const blockProps = useBlockProps({
      className: "cinderwell-icon-list__item",
    });
    const href = getDynamicLinkValue(attributes.url, attributes.urlDynamic);

    useEffect(() => {
      if (attributes.useDefaultIcon === false) return;
      const changes = {};
      Object.keys(contextKeys).forEach((key) => {
        if (attributes[key] !== resolvedIcon[key])
          changes[key] = resolvedIcon[key];
      });
      if (Object.keys(changes).length) setAttributes(changes);
    }, [
      attributes.useDefaultIcon,
      resolvedIcon.icon,
      resolvedIcon.iconSource,
      resolvedIcon.iconSvg,
      resolvedIcon.iconViewBox,
      resolvedIcon.iconSvgId,
      resolvedIcon.iconSvgUrl,
    ]);

    const text = (
      <RichText
        tagName="span"
        className="cinderwell-icon-list__content"
        value={attributes.text}
        onChange={(value) => setAttributes({ text: value })}
        placeholder={__("List item…", "cinderwell")}
        allowedFormats={ALLOWED_INLINE_FORMATS}
      />
    );

    return (
      <>
        <InspectorControls>
          <BlockIdentity
            icon="✓"
            title={__("Icon List Item", "cinderwell")}
            description={__("Individual list content and icon", "cinderwell")}
          />
          <PanelBody
            title={__("Icon", "cinderwell")}
            initialOpen={true}
            className="cw-panel"
          >
            <SegmentedControl
              label={__("Icon source", "cinderwell")}
              value={attributes.useDefaultIcon === false ? "custom" : "default"}
              options={[
                { value: "default", label: __("List default", "cinderwell") },
                { value: "custom", label: __("This item", "cinderwell") },
              ]}
              onChange={(value) =>
                setAttributes({ useDefaultIcon: value === "default" })
              }
            />
            {attributes.useDefaultIcon === false && (
              <IconSettingsControl
                icon={attributes.icon}
                source={attributes.iconSource}
                customSvg={attributes.iconSvg}
                customViewBox={attributes.iconViewBox}
                customSvgId={attributes.iconSvgId}
                customSvgUrl={attributes.iconSvgUrl}
                showAppearance={false}
                label={__("Item icon", "cinderwell")}
                onIconChange={(icon) => setAttributes({ icon })}
                onSourceChange={(iconSource) => setAttributes({ iconSource })}
                onCustomSvgChange={(
                  iconSvg,
                  iconViewBox,
                  iconSvgId,
                  iconSvgUrl,
                ) =>
                  setAttributes({ iconSvg, iconViewBox, iconSvgId, iconSvgUrl })
                }
                onSizeChange={() => {}}
                onColorChange={() => {}}
                onTreatmentChange={() => {}}
                onAlignmentChange={() => {}}
              />
            )}
          </PanelBody>
          <PanelBody
            title={__("Link", "cinderwell")}
            initialOpen={false}
            className="cw-panel"
          >
            <LinkSettingsControl
              url={attributes.url}
              opensInNewTab={attributes.opensInNewTab}
              dynamicData={attributes.urlDynamic}
              onChange={(changes) =>
                setAttributes({
                  ...(Object.prototype.hasOwnProperty.call(changes, "url")
                    ? { url: changes.url }
                    : {}),
                  ...(Object.prototype.hasOwnProperty.call(
                    changes,
                    "opensInNewTab",
                  )
                    ? { opensInNewTab: changes.opensInNewTab }
                    : {}),
                  ...(Object.prototype.hasOwnProperty.call(
                    changes,
                    "dynamicData",
                  )
                    ? { urlDynamic: changes.dynamicData }
                    : {}),
                })
              }
            />
          </PanelBody>
        </InspectorControls>
        <li {...blockProps}>
          <ItemIcon values={resolvedIcon} />
          {href ? (
            <a
              className="cinderwell-icon-list__link"
              href={href}
              onClick={(event) => event.preventDefault()}
            >
              {text}
            </a>
          ) : (
            text
          )}
        </li>
      </>
    );
  },
  save: ({ attributes }) => {
    const blockProps = useBlockProps.save({
      className: "cinderwell-icon-list__item",
    });
    const text = (
      <RichText.Content
        tagName="span"
        className="cinderwell-icon-list__content"
        value={attributes.text}
      />
    );
    const linkProps = {
      className: "cinderwell-icon-list__link",
      href: attributes.url,
      target: attributes.opensInNewTab ? "_blank" : undefined,
      rel: attributes.opensInNewTab ? "noopener noreferrer" : undefined,
      "data-cw-url-source":
        attributes.urlDynamic?.source &&
        attributes.urlDynamic.source !== "static"
          ? attributes.urlDynamic.source
          : undefined,
      "data-cw-url-field": attributes.urlDynamic?.field || undefined,
      "data-cw-url-fallback": attributes.urlDynamic?.fallback || undefined,
    };

    return (
      <li {...blockProps}>
        <ItemIcon values={attributes} />
        {attributes.url || attributes.urlDynamic?.source ? (
          <a {...linkProps}>{text}</a>
        ) : (
          text
        )}
      </li>
    );
  },
});
