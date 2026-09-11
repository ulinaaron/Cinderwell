import { registerBlockType } from "@wordpress/blocks";
import {
  InspectorControls,
  RichText,
  useBlockProps,
} from "@wordpress/block-editor";
import {
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
      (select) =>
        select("core").getEntityRecords("postType", attributes.postType, query),
      [
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
          } cinderwell-loop--cols-${attributes.columns}${getColumnClassName(
            attributes,
          )}${getTypographyClassName(attributes)}`,
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
        .map((taxonomy) => ({ label: taxonomy.name, value: taxonomy.slug })),
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
            title={__("Loop", "cinderwell")}
            description={__("Dynamic post listing", "cinderwell")}
          />
          <PanelBody
            title={__("Query", "cinderwell")}
            initialOpen={true}
            className="cw-panel"
          >
            <SelectControl
              label={__("Content type", "cinderwell")}
              value={attributes.postType}
              options={
                postTypeOptions.length
                  ? postTypeOptions
                  : [{ label: __("Posts", "cinderwell"), value: "post" }]
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
            <SelectControl
              label={__("Taxonomy filter", "cinderwell")}
              value={attributes.taxonomy}
              options={taxonomyOptions}
              onChange={(taxonomy) => setAttributes({ taxonomy, termId: 0 })}
            />
            {attributes.taxonomy && (
              <SelectControl
                label={__("Term", "cinderwell")}
                value={String(attributes.termId)}
                options={termOptions}
                onChange={(termId) =>
                  setAttributes({ termId: Number.parseInt(termId, 10) || 0 })
                }
              />
            )}
            <RangeControl
              label={__("Items", "cinderwell")}
              value={attributes.postsPerPage}
              min={1}
              max={24}
              onChange={(postsPerPage) => setAttributes({ postsPerPage })}
            />
            <SegmentedControl
              label={__("Order", "cinderwell")}
              value={attributes.order}
              options={[
                { label: __("Newest", "cinderwell"), value: "desc" },
                { label: __("Oldest", "cinderwell"), value: "asc" },
              ]}
              onChange={(order) => setAttributes({ order })}
            />
            <SelectControl
              label={__("Order by", "cinderwell")}
              value={attributes.orderBy}
              options={[
                { label: __("Date", "cinderwell"), value: "date" },
                { label: __("Modified date", "cinderwell"), value: "modified" },
                { label: __("Title", "cinderwell"), value: "title" },
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
          </PanelBody>
          <PanelBody
            title={__("Content", "cinderwell")}
            initialOpen={true}
            className="cw-panel"
          >
            <SectionToggles
              sections={[
                { key: "eyebrow", label: __("Eyebrow", "cinderwell") },
                { key: "heading", label: __("Heading", "cinderwell") },
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
                  { label: __("Wide", "cinderwell"), value: "landscape" },
                  { label: __("Square", "cinderwell"), value: "square" },
                  { label: __("Portrait", "cinderwell"), value: "portrait" },
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
            <ToggleRow
              label={__("Read more link", "cinderwell")}
              checked={attributes.showReadMore}
              onChange={(showReadMore) => setAttributes({ showReadMore })}
            />
            {attributes.showReadMore && (
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
            title={__("Loop layout", "cinderwell")}
            initialOpen={false}
            className="cw-panel"
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
            style={{ maxWidth: `var(--cw-width-${attributes.width})` }}
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
            {posts === null && (
              <p className="cinderwell-loop__status">
                {__("Loading items…", "cinderwell")}
              </p>
            )}
            {Array.isArray(posts) && posts.length === 0 && (
              <p className="cinderwell-loop__empty">
                {attributes.emptyMessage}
              </p>
            )}
            {Array.isArray(posts) && posts.length > 0 && (
              <div className="cinderwell-loop__grid">
                {posts.map((post) => {
                  const image = media[post.featured_media];
                  const ItemHeadingTag = getHeadingTagName(attributes.itemHeadingLevel, 3);
                  return (
                    <article className="cinderwell-loop__item" key={post.id}>
                      {attributes.showFeaturedImage && image?.source_url && (
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
                      )}
                      <div className="cinderwell-loop__content">
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
                          <a
                            href={post.link}
                            onClick={(event) => event.preventDefault()}
                          >
                            {decodeEntities(
                              post.title?.rendered ||
                                __("(Untitled)", "cinderwell"),
                            )}
                          </a>
                        </ItemHeadingTag>
                        {attributes.showExcerpt && post.excerpt?.rendered && (
                          <RawHTML
                            className={`cinderwell-loop__excerpt${getTextStyleClassName(
                              attributes,
                              "excerpt",
                            )}`}
                          >
                            {post.excerpt.rendered}
                          </RawHTML>
                        )}
                        {attributes.showReadMore && (
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
