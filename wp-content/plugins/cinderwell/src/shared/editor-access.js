const fullControls = [
  "content",
  "links",
  "media",
  "appearance",
  "spacing",
  "layout",
  "advanced",
];

export const editorAccessPolicy = window.cinderwellEditorSettings
  ?.editorAccess || {
  preset: "full",
  controls: fullControls,
  disabledBlocks: [],
  isRestricted: false,
};

export const canEditControl = (group) =>
  editorAccessPolicy.controls?.includes(group) !== false;

const attributeGroups = {
  align: "layout",
  alignment: "layout",
  breadcrumbs: "layout",
  columns: "layout",
  columnsTablet: "layout",
  columnsMobile: "layout",
  proportion: "layout",
  reverseOnMobile: "layout",
  stackAt: "layout",
  verticalAlignment: "layout",
  conditions: "advanced",
  cwAnimation: "appearance",
  cwAnimationTarget: "appearance",
  cwAnimationDuration: "appearance",
  cwAnimationDelay: "appearance",
  direction: "layout",
  imageSide: "layout",
  orientation: "layout",
  splitGap: "layout",
  width: "layout",
  gap: "spacing",
  spacing: "spacing",
  spacingResponsive: "spacing",
  background: "appearance",
  backgroundImage: "appearance",
  backgroundImageUrl: "appearance",
  backgroundImageFit: "appearance",
  backgroundImagePosition: "appearance",
  backgroundOverlay: "appearance",
  bgImage: "appearance",
  bgImageUrl: "appearance",
  bgImageFit: "appearance",
  bgImagePosition: "appearance",
  bgOverlay: "appearance",
  bgOverlayPreset: "appearance",
  backgroundVideo: "media",
  backgroundVideoUrl: "media",
  backgroundVideoPoster: "media",
  backgroundVideoPosterUrl: "media",
  backgroundVideoFit: "appearance",
  backgroundVideoPosition: "appearance",
  bgVideo: "media",
  bgVideoUrl: "media",
  bgVideoPoster: "media",
  bgVideoPosterUrl: "media",
  bgVideoFit: "appearance",
  bgVideoPosition: "appearance",
  cardColor: "appearance",
  childBackgroundMode: "appearance",
  color: "appearance",
  icon: "appearance",
  iconSource: "appearance",
  iconSvg: "appearance",
  iconViewBox: "appearance",
  iconSvgId: "appearance",
  iconSvgUrl: "appearance",
  iconSize: "appearance",
  iconColor: "appearance",
  iconTreatment: "appearance",
  iconAlignment: "appearance",
  defaultIcon: "appearance",
  defaultIconSource: "appearance",
  defaultIconSvg: "appearance",
  defaultIconViewBox: "appearance",
  defaultIconSvgId: "appearance",
  defaultIconSvgUrl: "appearance",
  imageAspect: "appearance",
  imageBoxAspect: "appearance",
  imageBoxOverlay: "appearance",
  imageBoxContentPosition: "layout",
  imageBoxContentVisibility: "appearance",
  imageBoxLinkStyle: "links",
  imageFit: "appearance",
  imagePosition: "appearance",
  itemHeadingLevel: "appearance",
  headingLevel: "appearance",
  level: "appearance",
  size: "appearance",
  style: "appearance",
  textColor: "appearance",
  textSize: "appearance",
  textStyles: "appearance",
  tabStyle: "appearance",
  treatment: "appearance",
  variant: "appearance",
  weight: "appearance",
  image: "media",
  imageId: "media",
  imageUrl: "media",
  imageAlt: "media",
  images: "media",
  mediaId: "media",
  mediaUrl: "media",
  videoId: "media",
  videoUrl: "media",
  embedUrl: "media",
  posterId: "media",
  posterUrl: "media",
  captionsUrl: "media",
  captionsLabel: "content",
  captionsLanguage: "content",
  sourceType: "media",
  autoplay: "appearance",
  controls: "appearance",
  loop: "appearance",
  muted: "appearance",
  preload: "appearance",
  buttons: "links",
  leftButtons: "links",
  rightButtons: "links",
  linkBehavior: "links",
  layout: "layout",
  opensInNewTab: "links",
  destinationType: "links",
  phoneNumber: "links",
  emailAddress: "links",
  iconPosition: "appearance",
  readMoreLabel: "links",
  showReadMore: "links",
  url: "links",
  urlDynamic: "links",
  dynamicData: "advanced",
  responsiveVisibility: "advanced",
  visibility: "advanced",
  visibilityConfig: "advanced",
  order: "layout",
  orderBy: "layout",
  orientationMobile: "layout",
  orientationTablet: "layout",
  postsPerPage: "layout",
  postType: "layout",
  excerptLength: "layout",
  tabPosition: "layout",
  tabPositionMobile: "layout",
  tabPositionTablet: "layout",
  taxonomy: "layout",
  termId: "layout",
  variation: "layout",
  verticalSide: "layout",
};

export const nestedAttributeGroups = {
  buttons: {
    id: "layout",
    text: "content",
    url: "links",
    urlDynamic: "links",
    opensInNewTab: "links",
    destinationType: "links",
    phoneNumber: "links",
    emailAddress: "links",
    icon: "appearance",
    iconPosition: "appearance",
    variant: "appearance",
    size: "appearance",
  },
  leftButtons: {
    id: "layout",
    text: "content",
    url: "links",
    urlDynamic: "links",
    opensInNewTab: "links",
    destinationType: "links",
    phoneNumber: "links",
    emailAddress: "links",
    icon: "appearance",
    iconPosition: "appearance",
    variant: "appearance",
    size: "appearance",
  },
  rightButtons: {
    id: "layout",
    text: "content",
    url: "links",
    urlDynamic: "links",
    opensInNewTab: "links",
    destinationType: "links",
    phoneNumber: "links",
    emailAddress: "links",
    icon: "appearance",
    iconPosition: "appearance",
    variant: "appearance",
    size: "appearance",
  },
  cards: {
    id: "layout",
    label: "content",
    title: "content",
    description: "content",
    buttonText: "content",
    buttonUrl: "links",
    buttonUrlDynamic: "links",
    buttonNewTab: "links",
    buttonDestinationType: "links",
    buttonPhoneNumber: "links",
    buttonEmailAddress: "links",
    buttonIcon: "appearance",
    buttonIconPosition: "appearance",
    buttonVariant: "appearance",
    buttonSize: "appearance",
    image: "media",
    imageUrl: "media",
    imageAlt: "media",
    imageFit: "appearance",
    imagePosition: "appearance",
    icon: "appearance",
    iconSource: "appearance",
    iconSvg: "appearance",
    iconViewBox: "appearance",
    iconSvgId: "appearance",
    iconSvgUrl: "appearance",
    iconSize: "appearance",
    iconColor: "appearance",
    iconTreatment: "appearance",
    iconAlignment: "appearance",
    visualType: "appearance",
    showImage: "layout",
    showLabel: "layout",
  },
  images: {
    id: "layout",
    id: "media",
    url: "media",
    alt: "media",
    caption: "content",
    position: "appearance",
  },
  items: { id: "layout" },
  slots: {
    type: "layout",
    span: "layout",
  },
};

export const classifyAttribute = (attribute) => {
  if (attributeGroups[attribute]) return attributeGroups[attribute];
  if (/^show[A-Z]/.test(attribute)) return "layout";
  if (/^(column|layout|grid|orientation)/i.test(attribute)) return "layout";
  if (
    /^(background|textStyle|font|color|icon|aspect|fit|position)/i.test(
      attribute,
    )
  )
    return "appearance";
  if (/^(image|media)/i.test(attribute)) return "media";
  if (/^(url|link|button)/i.test(attribute)) return "links";
  return "content";
};

const filterNestedArray = (attribute, next, current) => {
  const schema = nestedAttributeGroups[attribute];
  if (!schema || !Array.isArray(next)) return next;

  const allowContent = canEditControl("content");
  const allowStructure = allowContent && editorAccessPolicy.preset !== "text";
  if (!allowStructure && next.length !== (current || []).length) return current;

  return next.map((nextItem, index) => {
    const currentItem = (current || [])[index] || {};
    if (!nextItem || typeof nextItem !== "object") return nextItem;
    const filtered = { ...currentItem };
    Object.entries(nextItem).forEach(([key, value]) => {
      const group = schema[key] || "content";
      if (canEditControl(group)) filtered[key] = value;
    });
    return filtered;
  });
};

export const filterEditorAccessChanges = (changes, currentAttributes = {}) => {
  if (!editorAccessPolicy.isRestricted) return changes;

  return Object.entries(changes || {}).reduce((allowed, [attribute, value]) => {
    if (nestedAttributeGroups[attribute]) {
      const filtered = filterNestedArray(
        attribute,
        value,
        currentAttributes[attribute],
      );
      if (filtered !== currentAttributes[attribute])
        allowed[attribute] = filtered;
      return allowed;
    }

    if (canEditControl(classifyAttribute(attribute)))
      allowed[attribute] = value;
    return allowed;
  }, {});
};

export const applyEditorAccessClasses = () => {
  fullControls.forEach((group) => {
    document.body.classList.toggle(
      `cw-editor-access-no-${group}`,
      !canEditControl(group),
    );
  });
  document.body.dataset.cwEditorAccessPreset =
    editorAccessPolicy.preset || "full";
};
