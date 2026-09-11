import { PanelBody, SelectControl } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { useMemo, useState } from "@wordpress/element";
import { ResponsiveSegmentedControl } from "./inspector-controls";

export const responsiveVisibilityLabels = {
  eyebrow: __("Eyebrow", "cinderwell"),
  heading: __("Heading", "cinderwell"),
  subheading: __("Subheading", "cinderwell"),
  bodyContent: __("Body", "cinderwell"),
  caption: __("Caption", "cinderwell"),
  footnote: __("Footnote", "cinderwell"),
  byline: __("Byline", "cinderwell"),
  pullquote: __("Pullquote", "cinderwell"),
  quote: __("Quote", "cinderwell"),
  attribution: __("Attribution", "cinderwell"),
  context: __("Context", "cinderwell"),
  leftContent: __("Left column", "cinderwell"),
  rightContent: __("Right column", "cinderwell"),
  content: __("Content", "cinderwell"),
  text: __("Text", "cinderwell"),
  image: __("Image", "cinderwell"),
  buttons: __("Buttons", "cinderwell"),
  icon: __("Icon", "cinderwell"),
  label: __("Label", "cinderwell"),
};

const deviceLabels = {
  desktop: __("desktop", "cinderwell"),
  tablet: __("tablet", "cinderwell"),
  mobile: __("mobile", "cinderwell"),
};

export const getResponsiveVisibilitySections = (attributes = {}) =>
  Object.keys(responsiveVisibilityLabels)
    .filter((key) => Object.prototype.hasOwnProperty.call(attributes, key))
    .map((value) => ({ value, label: responsiveVisibilityLabels[value] }));

export const resolveResponsiveVisibility = (values = {}) => {
  const desktop = values.desktop === "hide" ? "hide" : "show";
  const tablet = ["show", "hide"].includes(values.tablet)
    ? values.tablet
    : desktop;
  const mobile = ["show", "hide"].includes(values.mobile)
    ? values.mobile
    : tablet;

  return { desktop, tablet, mobile };
};

export const isResponsiveScopeHidden = (values = {}) =>
  Object.values(resolveResponsiveVisibility(values)).includes("hide");

export const ResponsiveVisibilityPanel = ({ attributes, setAttributes }) => {
  const [scope, setScope] = useState("block");
  const sections = useMemo(
    () => getResponsiveVisibilitySections(attributes),
    [attributes],
  );
  const responsiveVisibility = attributes.responsiveVisibility || {};
  const scopeValues =
    scope === "block"
      ? responsiveVisibility.block || {}
      : responsiveVisibility.sections?.[scope] || {};
  const resolved = resolveResponsiveVisibility(scopeValues);
  const activeScopes = [
    {
      scope: "block",
      label: __("Entire block", "cinderwell"),
      values: responsiveVisibility.block || {},
    },
    ...sections.map((section) => ({
      scope: section.value,
      label: section.label,
      values: responsiveVisibility.sections?.[section.value] || {},
    })),
  ].filter((item) => isResponsiveScopeHidden(item.values));
  const hiddenSummary = activeScopes.flatMap((item) =>
    Object.entries(resolveResponsiveVisibility(item.values))
      .filter(([, value]) => value === "hide")
      .map(([device]) =>
        sprintf(
          __("%1$s: %2$s", "cinderwell"),
          item.label,
          deviceLabels[device],
        ),
      ),
  );

  const save = (breakpoint, value) => {
    const defaults = { desktop: "show", tablet: "auto", mobile: "auto" };
    const nextScope = { ...scopeValues };
    if (value === defaults[breakpoint]) delete nextScope[breakpoint];
    else nextScope[breakpoint] = value;

    if (scope === "block") {
      const next = { ...responsiveVisibility };
      if (Object.keys(nextScope).length) next.block = nextScope;
      else delete next.block;
      setAttributes({ responsiveVisibility: next });
      return;
    }

    const nextSections = { ...(responsiveVisibility.sections || {}) };
    if (Object.keys(nextScope).length) nextSections[scope] = nextScope;
    else delete nextSections[scope];
    const next = { ...responsiveVisibility };
    if (Object.keys(nextSections).length) next.sections = nextSections;
    else delete next.sections;
    setAttributes({ responsiveVisibility: next });
  };

  const options = (breakpoint) =>
    breakpoint === "desktop"
      ? [
          { value: "show", label: __("Visible", "cinderwell") },
          { value: "hide", label: __("Hidden", "cinderwell") },
        ]
      : [
          { value: "auto", label: __("Auto", "cinderwell") },
          { value: "show", label: __("Visible", "cinderwell") },
          { value: "hide", label: __("Hidden", "cinderwell") },
        ];

  return (
    <PanelBody
      title={__("Visibility", "cinderwell")}
      initialOpen={activeScopes.length > 0}
      className="cw-panel cw-responsive-visibility-panel"
    >
      <p className="cw-responsive-visibility-panel__intro">
        {__(
          "Show or hide this block—or one of its content pieces—at each device size.",
          "cinderwell",
        )}
      </p>
      {sections.length > 0 && (
        <SelectControl
          label={__("Apply to", "cinderwell")}
          value={scope}
          options={[
            { value: "block", label: __("Entire block", "cinderwell") },
            ...sections,
          ]}
          onChange={setScope}
        />
      )}
      <ResponsiveSegmentedControl
        label={
          scope === "block"
            ? __("Entire block", "cinderwell")
            : responsiveVisibilityLabels[scope]
        }
        values={{
          desktop: scopeValues.desktop || "show",
          tablet: scopeValues.tablet || "auto",
          mobile: scopeValues.mobile || "auto",
        }}
        options={options}
        autoHelp={{
          tablet: __("Auto follows the desktop setting.", "cinderwell"),
          mobile: __("Auto follows the tablet setting.", "cinderwell"),
        }}
        onChange={save}
      />
      {hiddenSummary.length > 0 && (
        <div className="cw-responsive-visibility-panel__summary">
          <strong>{__("Hidden", "cinderwell")}</strong>
          {hiddenSummary.map((summary) => (
            <span key={summary}>{summary}</span>
          ))}
        </div>
      )}
      {Object.values(resolved).every((value) => value === "hide") && (
        <p className="cw-responsive-visibility-panel__warning">
          {__("This selection is hidden at every device size.", "cinderwell")}
        </p>
      )}
    </PanelBody>
  );
};
