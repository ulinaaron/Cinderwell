import { MediaUpload, MediaUploadCheck } from "@wordpress/block-editor";
import {
  Button,
  Popover,
  SelectControl,
  Spinner,
  TextControl,
} from "@wordpress/components";
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { IconGlyph, iconOptions, sanitizeCustomSvg } from "./icon-library";
import { ColorTokenControl } from "./inspector-controls";

const sizeOptions = [
  { value: "sm", label: __("Small", "cinderwell") },
  { value: "md", label: __("Medium", "cinderwell") },
  { value: "lg", label: __("Large", "cinderwell") },
];

const editorTokenKeys = new Set(
  (window.cinderwellEditorSettings?.tokens || []).map((token) => token.key),
);
const hasEditorToken = (key) =>
  editorTokenKeys.size === 0 || editorTokenKeys.has(key);

const colorOptions = [
  {
    value: "brand",
    label: __("Brand", "cinderwell"),
    color: "var(--cw-color-brand, #b84c00)",
  },
  {
    value: "text",
    label: __("Text", "cinderwell"),
    color: "var(--cw-color-text, #1a1a1a)",
  },
  {
    value: "muted",
    label: __("Muted", "cinderwell"),
    color: "var(--cw-color-muted, #666666)",
  },
  {
    value: "brand-light",
    label: __("Brand Light", "cinderwell"),
    color: "var(--cw-color-brand-light, #f1dbcc)",
  },
  {
    value: "brand-dark",
    label: __("Brand Dark", "cinderwell"),
    color: "var(--cw-color-brand-dark, #8a3900)",
  },
  {
    value: "info",
    label: __("Info", "cinderwell"),
    color: "var(--cw-color-info, #005ea8)",
  },
  {
    value: "success",
    label: __("Success", "cinderwell"),
    color: "var(--cw-color-success, #287d3c)",
  },
  {
    value: "danger",
    label: __("Danger", "cinderwell"),
    color: "var(--cw-color-danger, #b42318)",
  },
  ...[
    {
      value: "accent-1",
      key: "cw_color_accent_1",
      label: __("Accent 1", "cinderwell"),
      color: "var(--cw-color-accent-1, #6f42c1)",
    },
    {
      value: "accent-2",
      key: "cw_color_accent_2",
      label: __("Accent 2", "cinderwell"),
      color: "var(--cw-color-accent-2, #007c83)",
    },
    {
      value: "accent-3",
      key: "cw_color_accent_3",
      label: __("Accent 3", "cinderwell"),
      color: "var(--cw-color-accent-3, #9a6700)",
    },
  ].filter((option) => hasEditorToken(option.key)),
  {
    value: "dark",
    label: __("Dark", "cinderwell"),
    color: "var(--cw-color-dark, #1a1a1a)",
  },
  {
    value: "light",
    label: __("Light", "cinderwell"),
    color: "var(--cw-color-light, #f8f5ef)",
  },
  {
    value: "white",
    label: __("White", "cinderwell"),
    color: "var(--cw-color-white, #ffffff)",
  },
];

const treatmentOptions = [
  { value: "plain", label: __("Plain", "cinderwell") },
  { value: "soft", label: __("Soft background", "cinderwell") },
  { value: "solid", label: __("Solid background", "cinderwell") },
];

const alignmentOptions = [
  { value: "left", label: __("Left", "cinderwell") },
  { value: "center", label: __("Center", "cinderwell") },
  { value: "right", label: __("Right", "cinderwell") },
];

export const IconPicker = ({ value = "star", onChange }) => {
  const [query, setQuery] = useState("");
  const needle = query.trim().toLowerCase();
  const visibleIcons = iconOptions.filter(
    (option) =>
      !needle ||
      `${option.label} ${option.value}`.toLowerCase().includes(needle),
  );

  return (
    <div className="cw-icon-picker">
      <TextControl
        label={__("Search icons", "cinderwell")}
        value={query}
        onChange={setQuery}
        placeholder={__("Search Lucide icons…", "cinderwell")}
      />
      <div
        className="cw-icon-picker__grid"
        role="listbox"
        aria-label={__("Lucide icons", "cinderwell")}
      >
        {visibleIcons.map((option) => (
          <button
            type="button"
            role="option"
            aria-selected={value === option.value}
            className={value === option.value ? "is-selected" : ""}
            key={option.value}
            onClick={() => onChange(option.value)}
            title={option.label}
          >
            <IconGlyph icon={option.value} size={22} />
            <span>{option.label}</span>
          </button>
        ))}
      </div>
      {!visibleIcons.length && (
        <p className="cw-icon-picker__empty">
          {__("No icons found.", "cinderwell")}
        </p>
      )}
    </div>
  );
};

export const getIconStyleClassName = ({
  size = "md",
  color = "brand",
  treatment = "plain",
  alignment = "left",
} = {}) =>
  ` cinderwell-card-grid__card-icon--size-${size} cinderwell-card-grid__card-icon--color-${color} cinderwell-card-grid__card-icon--treatment-${treatment} cinderwell-card-grid__card-icon--align-${alignment}`;

export const IconSettingsControl = ({
  icon = "star",
  source = "library",
  customSvg = "",
  customViewBox = "0 0 24 24",
  customSvgId = 0,
  customSvgUrl = "",
  size = "md",
  color = "brand",
  treatment = "plain",
  alignment = "left",
  showAppearance = true,
  showAlignment = true,
  label,
  onIconChange,
  onSourceChange,
  onCustomSvgChange,
  onSizeChange,
  onColorChange,
  onTreatmentChange,
  onAlignmentChange,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [anchor, setAnchor] = useState(null);
  const [mode, setMode] = useState(source);
  const [svgError, setSvgError] = useState("");
  const [isLoadingSvg, setIsLoadingSvg] = useState(false);
  const controlLabel = label || __("Icon settings", "cinderwell");
  const iconLabel =
    source === "custom"
      ? __("Custom SVG", "cinderwell")
      : iconOptions.find((option) => option.value === icon)?.label || icon;
  const hasCustomTreatment =
    size !== "md" ||
    color !== "brand" ||
    treatment !== "plain" ||
    alignment !== "left";
  const close = () => {
    setIsOpen(false);
    setAnchor(null);
  };
  const selectSvg = async (media) => {
    const url = media?.url || "";
    if (media?.mime !== "image/svg+xml" && !/\.svg(?:\?.*)?$/i.test(url)) {
      setSvgError(__("Choose an SVG file.", "cinderwell"));
      return;
    }
    setIsLoadingSvg(true);
    setSvgError("");
    try {
      const response = await window.fetch(url, { credentials: "same-origin" });
      if (!response.ok) throw new Error();
      const result = sanitizeCustomSvg(await response.text());
      if (result.error) {
        setSvgError(result.error);
        return;
      }
      onCustomSvgChange(result.markup, result.viewBox, media.id || 0, url);
      onSourceChange("custom");
    } catch (error) {
      setSvgError(
        __("The SVG could not be loaded from the Media Library.", "cinderwell"),
      );
    } finally {
      setIsLoadingSvg(false);
    }
  };

  return (
    <MediaUpload
      value={customSvgId}
      allowedTypes={["image"]}
      onSelect={selectSvg}
      title={__("Choose an SVG", "cinderwell")}
      render={({ open }) => (
        <div className="cw-icon-settings-control">
          <button
            type="button"
            className="cw-chip cw-icon-settings-control__trigger"
            onClick={(event) => {
              if (isOpen) close();
              else {
                setAnchor(event.currentTarget);
                setIsOpen(true);
              }
            }}
            aria-expanded={isOpen}
          >
            <IconGlyph
              icon={icon}
              size={16}
              customSvg={source === "custom" ? customSvg : ""}
              viewBox={customViewBox}
            />
            <span>{controlLabel}</span>
            <span className="cw-icon-settings-control__status">
              {hasCustomTreatment ? __("Custom", "cinderwell") : iconLabel}
            </span>
          </button>
          {isOpen && (
            <Popover
              anchor={anchor}
              onClose={close}
              placement="left-start"
              offset={28}
              flip={false}
              shift
              className="cw-popover cw-icon-picker-popover"
            >
              <div className="cw-popover__inner">
                <div className="cw-popover__head">
                  <span className="cw-popover__title">{controlLabel}</span>
                  <button
                    type="button"
                    className="cw-popover__close"
                    onClick={close}
                    aria-label={__("Close", "cinderwell")}
                  >
                    &times;
                  </button>
                </div>
                <div className="cw-icon-settings-control__preview">
                  <span
                    className={`cinderwell-card-grid__card-icon-glyph${getIconStyleClassName(
                      { size, color, treatment, alignment },
                    )}`}
                  >
                    <IconGlyph
                      icon={icon}
                      customSvg={source === "custom" ? customSvg : ""}
                      viewBox={customViewBox}
                    />
                  </span>
                  <span>{iconLabel}</span>
                </div>
                <div className="cw-icon-settings-control__source">
                  <Button
                    variant={mode === "library" ? "primary" : "secondary"}
                    onClick={() => {
                      setMode("library");
                      onSourceChange("library");
                    }}
                  >
                    {__("Lucide library", "cinderwell")}
                  </Button>
                  <Button
                    variant={mode === "custom" ? "primary" : "secondary"}
                    onClick={() => setMode("custom")}
                  >
                    {__("Custom SVG", "cinderwell")}
                  </Button>
                </div>
                {mode === "library" ? (
                  <IconPicker
                    value={icon}
                    onChange={(value) => {
                      onSourceChange("library");
                      onIconChange(value);
                    }}
                  />
                ) : (
                  <div className="cw-icon-settings-control__custom">
                    <span className="cw-field__label">
                      {__("SVG file", "cinderwell")}
                    </span>
                    {customSvgUrl && (
                      <span className="cw-icon-settings-control__file">
                        {customSvgUrl.split("/").pop()}
                      </span>
                    )}
                    <div className="cw-icon-settings-control__media-actions">
                      <MediaUploadCheck>
                        <Button
                          variant="secondary"
                          onClick={open}
                          disabled={isLoadingSvg}
                        >
                          {customSvgId
                            ? __("Replace SVG", "cinderwell")
                            : __("Choose SVG from Media Library", "cinderwell")}
                        </Button>
                      </MediaUploadCheck>
                      {(customSvgId || customSvg) && (
                        <Button
                          variant="tertiary"
                          onClick={() => {
                            onCustomSvgChange("", "0 0 24 24", 0, "");
                            onSourceChange("library");
                            setMode("library");
                          }}
                        >
                          {__("Remove", "cinderwell")}
                        </Button>
                      )}
                    </div>
                    {isLoadingSvg && (
                      <span className="cw-icon-settings-control__loading">
                        <Spinner />
                        {__("Loading SVG…", "cinderwell")}
                      </span>
                    )}
                    {svgError && (
                      <p className="cw-icon-settings-control__error">
                        {svgError}
                      </p>
                    )}
                    <p className="cw-icon-settings-control__help">
                      {__(
                        "Choose an SVG from Media Library. Other image types are not applied. SVGs are sanitized on upload and before display.",
                        "cinderwell",
                      )}
                    </p>
                  </div>
                )}
                {showAppearance && (
                  <>
                    <SelectControl
                      label={__("Size", "cinderwell")}
                      value={size}
                      options={sizeOptions}
                      onChange={onSizeChange}
                    />
                    <ColorTokenControl
                      value={color}
                      options={colorOptions}
                      onChange={onColorChange}
                    />
                    <SelectControl
                      label={__("Treatment", "cinderwell")}
                      value={treatment}
                      options={treatmentOptions}
                      onChange={onTreatmentChange}
                    />
                  </>
                )}
                {showAppearance && showAlignment && (
                  <SelectControl
                    label={__("Alignment", "cinderwell")}
                    value={alignment}
                    options={alignmentOptions}
                    onChange={onAlignmentChange}
                  />
                )}
                <div className="cw-popover__footer">
                  <Button variant="primary" onClick={close}>
                    {__("Done", "cinderwell")}
                  </Button>
                </div>
              </div>
            </Popover>
          )}
        </div>
      )}
    />
  );
};
