import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { BlockIdentity } from "../../shared/inspector-controls";
import { canEditControl } from "../../shared/editor-access";
import metadata from "./block.json";

const SearchIcon = () => (
  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <circle cx="11" cy="11" r="6.5" />
    <path d="m16 16 4 4" />
  </svg>
);

registerBlockType(metadata.name, {
  edit: ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps({
      className: "cinderwell-header-search is-editor-preview",
    });

    return (
      <>
        <InspectorControls>
          <BlockIdentity
            icon="⌕"
            title={__("Header Search", "cinderwell")}
            description={__(
              "Search disclosure for the site header",
              "cinderwell",
            )}
          />
          {canEditControl("content") && (
            <PanelBody
              title={__("Content", "cinderwell")}
              initialOpen={true}
              className="cw-panel cw-access-content"
            >
              <TextControl
                label={__("Accessible label", "cinderwell")}
                value={attributes.label}
                onChange={(label) => setAttributes({ label })}
              />
              <TextControl
                label={__("Placeholder", "cinderwell")}
                value={attributes.placeholder}
                onChange={(placeholder) => setAttributes({ placeholder })}
              />
              <TextControl
                label={__("Submit button", "cinderwell")}
                value={attributes.buttonText}
                onChange={(buttonText) => setAttributes({ buttonText })}
              />
            </PanelBody>
          )}
        </InspectorControls>
        <div {...blockProps}>
          <button
            type="button"
            className="cinderwell-header-search__toggle"
            aria-label={attributes.label}
          >
            <SearchIcon />
          </button>
          <div className="cinderwell-header-search__panel">
            <span className="cinderwell-header-search__editor-label">
              {attributes.label}
            </span>
            <div className="cinderwell-header-search__form">
              <input
                type="search"
                placeholder={attributes.placeholder}
                readOnly
              />
              <button type="button" className="btn btn--primary btn--md">
                {attributes.buttonText}
              </button>
            </div>
          </div>
        </div>
      </>
    );
  },
  save: () => null,
});
