import { registerBlockType } from "@wordpress/blocks";
import {
  InspectorControls,
  RichText,
  useBlockProps,
} from "@wordpress/block-editor";
import {
  Button,
  Notice,
  PanelBody,
  RangeControl,
  SelectControl,
  TextControl,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { RawHTML } from "@wordpress/element";
import { decodeEntities } from "@wordpress/html-entities";
import { __ } from "@wordpress/i18n";
import {
  canEditControl,
  filterEditorAccessChanges,
} from "../../shared/editor-access";
import {
  BackgroundControls,
  BlockIdentity,
  LayoutControls,
  ResponsiveSegmentedControl,
  SectionToggles,
  SegmentedControl,
  ToggleRow,
  TypographyControls,
  getBackgroundImageProps,
  getHeadingTagName,
  getResponsiveModifierClassName,
  getTextStyleClassName,
  getTypographyClassName,
} from "../../shared/inspector-controls";
import {
  VariationPicker,
  resolveBlockVariation,
} from "../../shared/variation-picker";
import metadata from "./block.json";

const columnOptions = ["1", "2", "3", "4"].map((value) => ({
  label: value,
  value,
}));
const responsiveColumnOptions = [
  { label: __("Auto", "cinderwell"), value: "auto" },
  ...columnOptions,
];

const getColumnClassName = (attributes) =>
  getResponsiveModifierClassName("cinderwell-loop", "cols", {
    tablet: attributes.columnsTablet,
    mobile: attributes.columnsMobile,
  });

const formatDate = (value) => {
  if (!value) return "";
  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(
    new Date(value),
  );
};

registerBlockType(metadata.name, {
  edit: ({ attributes, setAttributes }) => {
    const resolvedLayout = resolveBlockVariation(
      metadata.name,
      attributes.layout,
    );
    const activeLayout = resolvedLayout?.slug || "cards";
    const teamsSettings = window.cinderwellEditorSettings?.addons?.teams;
    const isTeamLoop =
      attributes.variation === "people" && teamsSettings?.enabled;
    const portfolioSettings =
      window.cinderwellEditorSettings?.addons?.portfolio;
    const isPortfolioLoop =
      attributes.variation === "portfolio" && portfolioSettings?.enabled;
    const locationsSettings =
      window.cinderwellEditorSettings?.addons?.locations;
    const isLocationsLoop =
      attributes.variation === "locations" && locationsSettings?.enabled;
    const isFocusedLoop = isTeamLoop || isPortfolioLoop || isLocationsLoop;
    const isInheritedQuery = attributes.queryMode === "inherit";
    const loopTypes = [
      {
        value: "",
        label: __("Standard", "cinderwell"),
        description: __(
          "Posts, pages, or another public content type.",
          "cinderwell",
        ),
        icon: "screenoptions",
        attributes: {
          variation: "",
          queryMode: "custom",
          postType: "post",
          taxonomy: "",
          termId: 0,
          heading: __("Latest posts", "cinderwell"),
          order: "desc",
          orderBy: "date",
          showDate: true,
          showTerms: false,
          showExcerpt: true,
          showReadMore: true,
          readMoreLabel: __("Read more", "cinderwell"),
          showTeamPosition: false,
          showPortfolioDetails: false,
          imageAspect: "landscape",
          linkBehavior: "page",
        },
      },
      ...(teamsSettings?.enabled
        ? [
            {
              value: "people",
              label: teamsSettings.pluralLabel,
              description: __("A focused people directory.", "cinderwell"),
              icon: "groups",
              attributes: {
                variation: "people",
                queryMode: "custom",
                postType: teamsSettings.postType,
                taxonomy: teamsSettings.taxonomy,
                termId: 0,
                heading: teamsSettings.pluralLabel,
                order: "asc",
                orderBy: "menu_order",
                showDate: false,
                showTerms: false,
                showExcerpt: false,
                showReadMore: false,
                readMoreLabel: __("Read more", "cinderwell"),
                showTeamPosition: true,
                showPortfolioDetails: false,
                imageAspect: "portrait",
                linkBehavior: teamsSettings.profilesPublic ? "page" : "none",
              },
            },
          ]
        : []),
      ...(portfolioSettings?.enabled
        ? [
            {
              value: "portfolio",
              label: __("Portfolio", "cinderwell"),
              description: __("A focused project listing.", "cinderwell"),
              icon: "portfolio",
              attributes: {
                variation: "portfolio",
                queryMode: "custom",
                postType: portfolioSettings.postType,
                taxonomy: portfolioSettings.taxonomy,
                termId: 0,
                heading: __("Portfolio", "cinderwell"),
                order: "asc",
                orderBy: "menu_order",
                showDate: false,
                showTerms: true,
                showExcerpt: true,
                showReadMore: true,
                readMoreLabel: __("View project", "cinderwell"),
                showTeamPosition: false,
                showPortfolioDetails: true,
                imageAspect: "landscape",
                linkBehavior: portfolioSettings.itemsPublic ? "page" : "none",
              },
            },
          ]
        : []),
      ...(locationsSettings?.enabled
        ? [
            {
              value: "locations",
              label: __("Locations", "cinderwell"),
              description: __("A focused location listing.", "cinderwell"),
              icon: "location-alt",
              attributes: {
                variation: "locations",
                queryMode: "custom",
                postType: locationsSettings.postType,
                taxonomy: "",
                termId: 0,
                heading: __("Locations", "cinderwell"),
                order: "asc",
                orderBy: "menu_order",
                showDate: false,
                showTerms: false,
                showExcerpt: true,
                showReadMore: locationsSettings.locationsPublic,
                readMoreLabel: __("View location", "cinderwell"),
                showTeamPosition: false,
                showPortfolioDetails: false,
                imageAspect: "landscape",
                linkBehavior: locationsSettings.locationsPublic
                  ? "page"
                  : "none",
              },
            },
          ]
        : []),
    ];
    const activeLoopType = loopTypes.some(
      ({ value }) => value === attributes.variation,
    )
      ? attributes.variation
      : null;
    const postTypes = useSelect(
      (select) => select("core").getPostTypes({ per_page: -1 }),
      [],
    );
    const taxonomies = useSelect(
      (select) =>
        select("core").getTaxonomies({
          type: attributes.postType,
          per_page: -1,
        }),
      [attributes.postType],
    );
    const terms = useSelect(
      (select) =>
        attributes.taxonomy
          ? select("core").getEntityRecords("taxonomy", attributes.taxonomy, {
              per_page: 100,
              hide_empty: false,
            })
          : [],
      [attributes.taxonomy],
    );
    const selectedTaxonomy = (taxonomies || []).find(
      (item) => item.slug === attributes.taxonomy,
    );
    const selectedPostType = (postTypes || []).find(
      (item) => item.slug === attributes.postType,
    );
    const query = {
      per_page: attributes.postsPerPage,
      order: attributes.order,
      orderby: attributes.orderBy,
      _embed: true,
      ...(attributes.termId > 0 && selectedTaxonomy
        ? {
            [selectedTaxonomy.rest_base || selectedTaxonomy.slug]: [
              attributes.termId,
            ],
          }
        : {}),
    };
    const posts = useSelect(
      (select) => {
        if (isInheritedQuery) return [];
        return select("core").getEntityRecords(
          "postType",
          attributes.postType,
          query,
        );
      },
      [
        isInheritedQuery,
        attributes.postType,
        attributes.postsPerPage,
        attributes.order,
        attributes.orderBy,
        attributes.taxonomy,
        attributes.termId,
        selectedTaxonomy?.rest_base,
      ],
    );
    const media = useSelect(
      (select) =>
        Object.fromEntries(
          (posts || [])
            .filter((post) => post.featured_media)
            .map((post) => [
              post.featured_media,
              select("core").getMedia(post.featured_media),
            ]),
        ),
      [posts],
    );

    const blockProps = useBlockProps(
      getBackgroundImageProps(
        {
          className: `cinderwell-loop cinderwell-loop--bg-${
            attributes.background
          } cinderwell-loop--cols-${
            attributes.columns
          } cinderwell-loop--links-${attributes.linkBehavior}${
            attributes.variation
              ? ` cinderwell-loop--${attributes.variation}`
              : ""
          }${getColumnClassName(
            attributes,
          )} cinderwell-loop--layout-${activeLayout}${getTypographyClassName(
            attributes,
          )}`,
        },
        attributes,
      ),
    );
    const sectionValues = {
      eyebrow: attributes.showEyebrow,
      heading: attributes.showHeading,
    };
    const postTypeOptions = (postTypes || [])
      .filter(
        (type) => type.viewable && type.slug !== "attachment" && type.rest_base,
      )
      .map((type) => ({ label: type.name, value: type.slug }));
    const taxonomyOptions = [
      { label: __("All terms", "cinderwell"), value: "" },
      ...(taxonomies || [])
        .filter((taxonomy) => taxonomy.visibility?.show_ui !== false)
        .map((taxonomy) => ({
          label: taxonomy.name,
          value: taxonomy.slug,
        })),
    ];
    const termOptions = [
      { label: __("All terms", "cinderwell"), value: "0" },
      ...(terms || []).map((term) => ({
        label: decodeEntities(term.name),
        value: String(term.id),
      })),
    ];

    return (
      <>
        <InspectorControls>
          <BlockIdentity
            icon="↻"
            title={
              isTeamLoop
                ? teamsSettings.pluralLabel
                : isPortfolioLoop
                ? __("Portfolio", "cinderwell")
                : isLocationsLoop
                ? __("Locations", "cinderwell")
                : __("Loop", "cinderwell")
            }
            description={
              isTeamLoop
                ? __("Focused people directory", "cinderwell")
                : isPortfolioLoop
                ? __("Focused project listing", "cinderwell")
                : isLocationsLoop
                ? __("Focused location listing", "cinderwell")
                : __("Dynamic post listing", "cinderwell")
            }
          />
          <div
            className="cw-loop-type cw-access-layout"
            aria-label={__("Loop type", "cinderwell")}
          >
            <div className="cw-loop-type__heading">
              {__("Loop type", "cinderwell")}
            </div>
            <div className="cw-loop-type__options">
              {loopTypes.map((loopType) => {
                const isActive = loopType.value === activeLoopType;

                return (
                  <Button
                    key={loopType.value || "standard"}
                    className={`cw-loop-type__option${
                      isActive ? " is-active" : ""
                    }`}
                    aria-pressed={isActive}
                    onClick={() => {
                      if (!isActive) setAttributes(loopType.attributes);
                    }}
                  >
                    <span
                      className={`dashicons dashicons-${loopType.icon} cw-loop-type__icon`}
                      aria-hidden="true"
                    />
                    <span className="cw-loop-type__copy">
                      <span className="cw-loop-type__label">
                        {loopType.label}
                      </span>
                      <span className="screen-reader-text">
                        {loopType.description}
                      </span>
                    </span>
                  </Button>
                );
              })}
            </div>
          </div>
          {canEditControl("layout") && (
            <VariationPicker
              blockName={metadata.name}
              value={attributes.layout}
              title={__("Variation", "cinderwell")}
              onChange={(layoutVariation) =>
                setAttributes(
                  filterEditorAccessChanges(
                    layoutVariation.attributes || {},
                    attributes,
                  ),
                )
              }
            />
          )}
          <PanelBody
            title={__("Query", "cinderwell")}
            initialOpen={true}
            className="cw-panel cw-access-layout"
          >
            {!isFocusedLoop && (
              <SegmentedControl
                label={__("Query source", "cinderwell")}
                value={attributes.queryMode || "custom"}
                options={[
                  { label: __("Selected", "cinderwell"), value: "custom" },
                  { label: __("Template", "cinderwell"), value: "inherit" },
                ]}
                onChange={(queryMode) => setAttributes({ queryMode })}
                help={__(
                  "Template uses the current search or archive query.",
                  "cinderwell",
                )}
              />
            )}
            {isInheritedQuery && (
              <Notice status="info" isDismissible={false}>
                {__(
                  "Results, relevance, and pagination come from the current template query.",
                  "cinderwell",
                )}
              </Notice>
            )}
            {!isInheritedQuery && !isFocusedLoop && (
              <SelectControl
                label={__("Content type", "cinderwell")}
                value={attributes.postType}
                options={
                  postTypeOptions.length
                    ? postTypeOptions
                    : [
                        {
                          label: __("Posts", "cinderwell"),
                          value: "post",
                        },
                      ]
                }
                onChange={(postType) =>
                  setAttributes({
                    postType,
                    taxonomy: "",
                    termId: 0,
                    ...(attributes.orderBy === "menu_order"
                      ? { orderBy: "date" }
                      : {}),
                  })
                }
              />
            )}
            {!isInheritedQuery && (
              <SelectControl
                label={__("Taxonomy filter", "cinderwell")}
                value={attributes.taxonomy}
                options={taxonomyOptions}
                onChange={(taxonomy) => setAttributes({ taxonomy, termId: 0 })}
              />
            )}
            {!isInheritedQuery && attributes.taxonomy && (
              <SelectControl
                label={__("Term", "cinderwell")}
                value={String(attributes.termId)}
                options={termOptions}
                onChange={(termId) =>
                  setAttributes({
                    termId: Number.parseInt(termId, 10) || 0,
                  })
                }
              />
            )}
            {!isInheritedQuery && (
              <RangeControl
                label={__("Items", "cinderwell")}
                value={attributes.postsPerPage}
                min={1}
                max={24}
                onChange={(postsPerPage) => setAttributes({ postsPerPage })}
              />
            )}
            {!isInheritedQuery && (
              <SegmentedControl
                label={__("Order", "cinderwell")}
                value={attributes.order}
                options={[
                  {
                    label: __("Newest", "cinderwell"),
                    value: "desc",
                  },
                  {
                    label: __("Oldest", "cinderwell"),
                    value: "asc",
                  },
                ]}
                onChange={(order) => setAttributes({ order })}
              />
            )}
            {!isInheritedQuery && (
              <SelectControl
                label={__("Order by", "cinderwell")}
                value={attributes.orderBy}
                options={[
                  {
                    label: __("Date", "cinderwell"),
                    value: "date",
                  },
                  {
                    label: __("Modified date", "cinderwell"),
                    value: "modified",
                  },
                  {
                    label: __("Title", "cinderwell"),
                    value: "title",
                  },
                  ...(selectedPostType?.supports?.["page-attributes"]
                    ? [
                        {
                          label: __("Menu order", "cinderwell"),
                          value: "menu_order",
                        },
                      ]
                    : []),
                ]}
                onChange={(orderBy) => setAttributes({ orderBy })}
              />
            )}
          </PanelBody>
          <PanelBody
            title={__("Content", "cinderwell")}
            initialOpen={true}
            className="cw-panel cw-access-content"
          >
            <SectionToggles
              sections={[
                {
                  key: "eyebrow",
                  label: __("Eyebrow", "cinderwell"),
                },
                {
                  key: "heading",
                  label: __("Heading", "cinderwell"),
                },
              ]}
              values={sectionValues}
              onChange={(values) =>
                setAttributes({
                  showEyebrow: values.eyebrow,
                  showHeading: values.heading,
                })
              }
            />
            <ToggleRow
              label={__("Featured image", "cinderwell")}
              checked={attributes.showFeaturedImage}
              onChange={(showFeaturedImage) =>
                setAttributes({ showFeaturedImage })
              }
            />
            {attributes.showFeaturedImage && (
              <SegmentedControl
                label={__("Image shape", "cinderwell")}
                value={attributes.imageAspect}
                options={[
                  {
                    label: __("Wide", "cinderwell"),
                    value: "landscape",
                  },
                  {
                    label: __("Square", "cinderwell"),
                    value: "square",
                  },
                  {
                    label: __("Portrait", "cinderwell"),
                    value: "portrait",
                  },
                ]}
                onChange={(imageAspect) => setAttributes({ imageAspect })}
              />
            )}
            <ToggleRow
              label={__("Date", "cinderwell")}
              checked={attributes.showDate}
              onChange={(showDate) => setAttributes({ showDate })}
            />
            <ToggleRow
              label={__("Content type", "cinderwell")}
              checked={attributes.showContentType}
              onChange={(showContentType) => setAttributes({ showContentType })}
            />
            {isTeamLoop && (
              <ToggleRow
                label={__("Position", "cinderwell")}
                checked={attributes.showTeamPosition}
                onChange={(showTeamPosition) =>
                  setAttributes({ showTeamPosition })
                }
              />
            )}
            {isPortfolioLoop && (
              <ToggleRow
                label={__("Project details", "cinderwell")}
                checked={attributes.showPortfolioDetails}
                onChange={(showPortfolioDetails) =>
                  setAttributes({ showPortfolioDetails })
                }
              />
            )}
            <ToggleRow
              label={__("Terms", "cinderwell")}
              checked={attributes.showTerms}
              onChange={(showTerms) => setAttributes({ showTerms })}
            />
            <ToggleRow
              label={__("Excerpt", "cinderwell")}
              checked={attributes.showExcerpt}
              onChange={(showExcerpt) => setAttributes({ showExcerpt })}
            />
            {attributes.showExcerpt && (
              <RangeControl
                label={__("Excerpt words", "cinderwell")}
                value={attributes.excerptLength}
                min={8}
                max={60}
                onChange={(excerptLength) => setAttributes({ excerptLength })}
              />
            )}
            <SegmentedControl
              label={
                isTeamLoop
                  ? __("Profile links", "cinderwell")
                  : isPortfolioLoop
                  ? __("Project links", "cinderwell")
                  : isLocationsLoop
                  ? __("Location links", "cinderwell")
                  : __("Item links", "cinderwell")
              }
              value={attributes.linkBehavior}
              options={[
                {
                  label: __("Page", "cinderwell"),
                  value: "page",
                  disabled:
                    (isTeamLoop && !teamsSettings.profilesPublic) ||
                    (isPortfolioLoop && !portfolioSettings.itemsPublic) ||
                    (isLocationsLoop && !locationsSettings.locationsPublic),
                },
                {
                  label: __("None", "cinderwell"),
                  value: "none",
                },
              ]}
              onChange={(linkBehavior) => setAttributes({ linkBehavior })}
              help={
                (isTeamLoop && !teamsSettings.profilesPublic) ||
                (isPortfolioLoop && !portfolioSettings.itemsPublic) ||
                (isLocationsLoop && !locationsSettings.locationsPublic)
                  ? __(
                      isLocationsLoop
                        ? "Public location pages are disabled in Cinderwell settings."
                        : isPortfolioLoop
                        ? "Public portfolio pages are disabled in Cinderwell settings."
                        : "Public profile pages are disabled in Cinderwell settings.",
                      "cinderwell",
                    )
                  : undefined
              }
            />
            {attributes.linkBehavior === "page" && (
              <ToggleRow
                label={__("Read more link", "cinderwell")}
                checked={attributes.showReadMore}
                onChange={(showReadMore) => setAttributes({ showReadMore })}
              />
            )}
            {attributes.linkBehavior === "page" && attributes.showReadMore && (
              <TextControl
                label={__("Read more label", "cinderwell")}
                value={attributes.readMoreLabel}
                onChange={(readMoreLabel) => setAttributes({ readMoreLabel })}
              />
            )}
            <ToggleRow
              label={__("Pagination", "cinderwell")}
              checked={attributes.showPagination}
              onChange={(showPagination) => setAttributes({ showPagination })}
            />
            <TextControl
              label={__("Empty-state message", "cinderwell")}
              value={attributes.emptyMessage}
              onChange={(emptyMessage) => setAttributes({ emptyMessage })}
            />
          </PanelBody>
          <PanelBody
            title={__("Columns", "cinderwell")}
            initialOpen={false}
            className="cw-panel cw-access-layout"
          >
            <ResponsiveSegmentedControl
              label={__("Columns", "cinderwell")}
              values={{
                desktop: attributes.columns,
                tablet: attributes.columnsTablet || "auto",
                mobile: attributes.columnsMobile || "auto",
              }}
              options={(breakpoint) =>
                breakpoint === "desktop"
                  ? columnOptions
                  : responsiveColumnOptions
              }
              onChange={(breakpoint, value) =>
                setAttributes(
                  breakpoint === "desktop"
                    ? { columns: value }
                    : {
                        [breakpoint === "tablet"
                          ? "columnsTablet"
                          : "columnsMobile"]: value === "auto" ? "" : value,
                      },
                )
              }
            />
          </PanelBody>
          <LayoutControls
            attributes={attributes}
            setAttributes={setAttributes}
          />
          <BackgroundControls
            value={attributes.background}
            onChange={(background) => setAttributes({ background })}
            attributes={attributes}
            setAttributes={setAttributes}
          />
          <TypographyControls
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
                key: "itemTitle",
                label: __("Item titles", "cinderwell"),
                enabled: true,
              },
              {
                key: "excerpt",
                label: __("Excerpts", "cinderwell"),
                enabled: attributes.showExcerpt,
              },
            ]}
          />
        </InspectorControls>
        <section {...blockProps}>
          <div
            className="cinderwell-loop__inner"
            style={{
              maxWidth: `var(--cw-width-${attributes.width})`,
            }}
          >
            {attributes.showEyebrow && (
              <RichText
                tagName="span"
                identifier="eyebrow"
                className={`cinderwell-eyebrow${getTextStyleClassName(
                  attributes,
                  "eyebrow",
                )}`}
                value={attributes.eyebrow}
                onChange={(eyebrow) => setAttributes({ eyebrow })}
                placeholder={__("Eyebrow…", "cinderwell")}
                allowedFormats={[]}
              />
            )}
            {attributes.showHeading && (
              <RichText
                tagName={getHeadingTagName(attributes.headingLevel)}
                identifier="heading"
                className={`cinderwell-heading${getTextStyleClassName(
                  attributes,
                  "heading",
                )}`}
                value={attributes.heading}
                onChange={(heading) => setAttributes({ heading })}
                placeholder={__("Heading…", "cinderwell")}
                allowedFormats={[]}
              />
            )}
            {isInheritedQuery && (
              <p className="cinderwell-loop__status">
                {__(
                  "Template results appear on the frontend using the current query.",
                  "cinderwell",
                )}
              </p>
            )}
            {!isInheritedQuery && posts === null && (
              <p className="cinderwell-loop__status">
                {__("Loading items…", "cinderwell")}
              </p>
            )}
            {!isInheritedQuery &&
              Array.isArray(posts) &&
              posts.length === 0 && (
                <p className="cinderwell-loop__empty">
                  {attributes.emptyMessage}
                </p>
              )}
            {!isInheritedQuery && Array.isArray(posts) && posts.length > 0 && (
              <div className="cinderwell-loop__grid">
                {posts.map((post, index) => {
                  const image = media[post.featured_media];
                  const ItemHeadingTag = getHeadingTagName(
                    attributes.itemHeadingLevel,
                    3,
                  );
                  return (
                    <article
                      className={`cinderwell-loop__item${
                        activeLayout === "featured-lead" && index === 0
                          ? " cinderwell-loop__item--featured"
                          : ""
                      }`}
                      key={post.id}
                    >
                      {attributes.showFeaturedImage &&
                        image?.source_url &&
                        (attributes.linkBehavior === "page" ? (
                          <a
                            className={`cinderwell-loop__image cinderwell-loop__image--${attributes.imageAspect}`}
                            href={post.link}
                            onClick={(event) => event.preventDefault()}
                          >
                            <img
                              src={image.source_url}
                              alt={image.alt_text || ""}
                            />
                          </a>
                        ) : (
                          <div
                            className={`cinderwell-loop__image cinderwell-loop__image--${attributes.imageAspect}`}
                          >
                            <img
                              src={image.source_url}
                              alt={image.alt_text || ""}
                            />
                          </div>
                        ))}
                      <div className="cinderwell-loop__content">
                        {attributes.showContentType && (
                          <span className="cinderwell-loop__content-type">
                            {selectedPostType?.labels?.singular_name ||
                              selectedPostType?.name ||
                              __("Content", "cinderwell")}
                          </span>
                        )}
                        {attributes.showDate && (
                          <time
                            className="cinderwell-loop__date"
                            dateTime={post.date}
                          >
                            {formatDate(post.date)}
                          </time>
                        )}
                        <ItemHeadingTag
                          className={`cinderwell-loop__title${getTextStyleClassName(
                            attributes,
                            "itemTitle",
                          )}`}
                        >
                          {attributes.linkBehavior === "page" ? (
                            <a
                              href={post.link}
                              onClick={(event) => event.preventDefault()}
                            >
                              {decodeEntities(
                                post.title?.rendered ||
                                  __("(Untitled)", "cinderwell"),
                              )}
                            </a>
                          ) : (
                            decodeEntities(
                              post.title?.rendered ||
                                __("(Untitled)", "cinderwell"),
                            )
                          )}
                        </ItemHeadingTag>
                        {isTeamLoop &&
                          attributes.showTeamPosition &&
                          post.meta?._cw_person_position && (
                            <p className="cinderwell-loop__position">
                              {post.meta._cw_person_position}
                            </p>
                          )}
                        {isPortfolioLoop &&
                          attributes.showPortfolioDetails &&
                          Object.entries(
                            portfolioSettings.loopFields || {},
                          ).some(([key]) => post.meta?.[key]) && (
                            <dl className="cinderwell-portfolio-details cinderwell-portfolio-details--compact">
                              {Object.entries(
                                portfolioSettings.loopFields || {},
                              )
                                .filter(([key]) => post.meta?.[key])
                                .map(([key, label]) => (
                                  <div
                                    className="cinderwell-portfolio-details__item"
                                    key={label}
                                  >
                                    <dt>{label}</dt>
                                    <dd>{post.meta[key]}</dd>
                                  </div>
                                ))}
                            </dl>
                          )}
                        {attributes.showExcerpt &&
                          post.excerpt?.rendered
                            ?.replace(/<[^>]*>/g, "")
                            .trim() && (
                            <RawHTML
                              className={`cinderwell-loop__excerpt${getTextStyleClassName(
                                attributes,
                                "excerpt",
                              )}`}
                            >
                              {post.excerpt.rendered}
                            </RawHTML>
                          )}
                        {attributes.linkBehavior === "page" &&
                          attributes.showReadMore && (
                            <a
                              className="cinderwell-loop__read-more"
                              href={post.link}
                              onClick={(event) => event.preventDefault()}
                            >
                              {attributes.readMoreLabel}
                              <span className="screen-reader-text">
                                : {decodeEntities(post.title?.rendered || "")}
                              </span>
                            </a>
                          )}
                      </div>
                    </article>
                  );
                })}
              </div>
            )}
            {attributes.showPagination && (
              <p className="cinderwell-loop__pagination-preview">
                {__(
                  "Pagination appears on the frontend when needed.",
                  "cinderwell",
                )}
              </p>
            )}
          </div>
        </section>
      </>
    );
  },
  save: () => null,
});
