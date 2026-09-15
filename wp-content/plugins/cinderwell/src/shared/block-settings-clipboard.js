import { getBlockType } from "@wordpress/blocks";
import { dispatch, useSelect } from "@wordpress/data";
import { BlockSettingsMenuControls } from "@wordpress/block-editor";
import { MenuItem } from "@wordpress/components";
import { useState } from "@wordpress/element";
import { applyFilters } from "@wordpress/hooks";
import { __, _n, sprintf } from "@wordpress/i18n";
import {
  canEditControl,
  classifyAttribute,
  filterEditorAccessChanges,
  nestedAttributeGroups,
} from "./editor-access";
import { getBlockVariations } from "./variation-picker";

const STORAGE_KEY = "cinderwell.blockSettingsClipboard";
const PAYLOAD_TYPE = "cinderwell/block-settings";
const PAYLOAD_VERSION = 1;
const TRANSFERABLE_GROUPS = ["appearance", "spacing", "layout", "advanced"];

// These select data, content bindings, or a semantic block type rather than
// presentation. The Loop's separate `layout` attribute remains transferable;
// its People/Portfolio/Locations `variation` does not.
const EXCLUDED_ATTRIBUTES = new Set([
  "backgroundImage",
  "backgroundImageUrl",
  "bgImage",
  "bgImageUrl",
  "defaultIcon",
  "defaultIconSource",
  "defaultIconSvg",
  "defaultIconSvgId",
  "defaultIconSvgUrl",
  "defaultIconViewBox",
  "dynamicData",
  "formId",
  "icon",
  "iconSource",
  "iconSvg",
  "iconSvgId",
  "iconSvgUrl",
  "iconViewBox",
  "linkBehavior",
  "order",
  "orderBy",
  "postType",
  "postsPerPage",
  "readMoreLabel",
  "taxonomy",
  "termId",
  "url",
  "urlDynamic",
  "variation",
  "slots",
]);

const EXCLUDED_NESTED_KEYS = new Set([
  "id",
  "image",
  "imageId",
  "imageUrl",
  "imageAlt",
  "icon",
  "iconSource",
  "iconSvg",
  "iconSvgId",
  "iconSvgUrl",
  "iconViewBox",
]);

let memoryClipboard = null;

const clone = (value) =>
  value === undefined ? undefined : JSON.parse(JSON.stringify(value));

const isTransferableGroup = (group) =>
  TRANSFERABLE_GROUPS.includes(group) && canEditControl(group);

const collectNestedSettings = (attribute, items) => {
  const schema = nestedAttributeGroups[attribute];
  if (!schema || !Array.isArray(items)) return null;

  let hasSettings = false;
  const settings = items.map((item) => {
    if (!item || typeof item !== "object") return {};

    const transferred = Object.entries(item).reduce((result, [key, value]) => {
      const group = schema[key] || "content";
      if (!EXCLUDED_NESTED_KEYS.has(key) && isTransferableGroup(group)) {
        result[key] = clone(value);
        hasSettings = true;
      }
      return result;
    }, {});

    return transferred;
  });

  return hasSettings ? settings : null;
};

export const collectBlockSettings = (block) => {
  const blockType = getBlockType(block.name);
  const schema = blockType?.attributes || {};

  const attributes = Object.entries(block.attributes || {}).reduce(
    (result, [attribute, value]) => {
      if (
        !Object.prototype.hasOwnProperty.call(schema, attribute) ||
        EXCLUDED_ATTRIBUTES.has(attribute)
      ) {
        return result;
      }

      const nested = collectNestedSettings(attribute, value);
      if (nested) {
        result[attribute] = nested;
        return result;
      }

      if (isTransferableGroup(classifyAttribute(attribute))) {
        result[attribute] = clone(value);
      }

      return result;
    },
    {},
  );

  return applyFilters(
    "cinderwell.blockSettingsClipboard.copy",
    attributes,
    block,
  );
};

const typeMatches = (value, definition = {}) => {
  if (value === null || !definition.type) return true;
  if (definition.type === "array") return Array.isArray(value);
  if (definition.type === "object") {
    return typeof value === "object" && !Array.isArray(value);
  }
  if (definition.type === "integer" || definition.type === "number") {
    return typeof value === "number" && Number.isFinite(value);
  }
  return typeof value === definition.type;
};

const mergeNestedSettings = (attribute, source, target) => {
  if (
    !nestedAttributeGroups[attribute] ||
    !Array.isArray(source) ||
    !Array.isArray(target)
  ) {
    return undefined;
  }

  let changed = false;
  const merged = target.map((targetItem, index) => {
    const sourceItem = source[index];
    if (!sourceItem || typeof sourceItem !== "object") return targetItem;

    const nextItem = { ...targetItem };
    Object.entries(sourceItem).forEach(([key, value]) => {
      const group = nestedAttributeGroups[attribute][key] || "content";
      if (!EXCLUDED_NESTED_KEYS.has(key) && isTransferableGroup(group)) {
        nextItem[key] = clone(value);
        changed = true;
      }
    });
    return nextItem;
  });

  return changed ? merged : undefined;
};

export const getCompatibleSettings = (payload, targetBlock) => {
  if (
    payload?.type !== PAYLOAD_TYPE ||
    payload?.version !== PAYLOAD_VERSION ||
    !payload.attributes
  ) {
    return {};
  }

  const targetType = getBlockType(targetBlock.name);
  const targetSchema = targetType?.attributes || {};
  const targetVariations = getBlockVariations(targetBlock.name);
  const compatible = Object.entries(payload.attributes).reduce(
    (result, [attribute, value]) => {
      if (
        attribute === "layout" &&
        Object.keys(targetVariations).length > 0 &&
        !targetVariations[value]
      ) {
        return result;
      }

      if (
        !Object.prototype.hasOwnProperty.call(targetSchema, attribute) ||
        EXCLUDED_ATTRIBUTES.has(attribute)
      ) {
        return result;
      }

      const nested = mergeNestedSettings(
        attribute,
        value,
        targetBlock.attributes?.[attribute],
      );
      if (nested) {
        result[attribute] = nested;
        return result;
      }

      if (
        isTransferableGroup(classifyAttribute(attribute)) &&
        typeMatches(value, targetSchema[attribute])
      ) {
        result[attribute] = clone(value);
      }

      return result;
    },
    {},
  );

  return filterEditorAccessChanges(
    applyFilters(
      "cinderwell.blockSettingsClipboard.paste",
      compatible,
      payload,
      targetBlock,
    ),
    targetBlock.attributes,
  );
};

const hasUnavailableLayout = (payload, targetBlock) =>
  Object.keys(getBlockVariations(targetBlock.name)).length > 0 &&
  typeof payload?.attributes?.layout === "string" &&
  !getBlockVariations(targetBlock.name)[payload.attributes.layout];

const savePayload = (payload) => {
  memoryClipboard = payload;
  try {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
  } catch {
    // The in-memory copy remains available when storage is restricted.
  }
};

const readPayload = () => {
  if (memoryClipboard) return memoryClipboard;
  try {
    const stored = JSON.parse(window.sessionStorage.getItem(STORAGE_KEY));
    if (stored?.type === PAYLOAD_TYPE && stored.version === PAYLOAD_VERSION) {
      memoryClipboard = stored;
      return stored;
    }
  } catch {
    // Treat missing, blocked, or invalid storage as an empty clipboard.
  }
  return null;
};

const notice = (status, message) => {
  dispatch("core/notices").createNotice(status, message, {
    type: "snackbar",
  });
};

export const BlockSettingsClipboardMenu = () => {
  const block = useSelect(
    (select) => select("core/block-editor").getSelectedBlock(),
    [],
  );
  const [payload, setPayload] = useState(readPayload);

  if (!block?.name?.startsWith("cinderwell/")) return null;

  const copiedSettings = collectBlockSettings(block);
  const compatibleSettings = payload
    ? getCompatibleSettings(payload, block)
    : {};
  const unavailableLayout = payload
    ? hasUnavailableLayout(payload, block)
    : false;
  const canCopy = Object.keys(copiedSettings).length > 0;
  const canPaste =
    Object.keys(compatibleSettings).length > 0 || unavailableLayout;

  return (
    <BlockSettingsMenuControls>
      {({ onClose }) => (
        <>
          <MenuItem
            icon="admin-page"
            disabled={!canCopy}
            onClick={() => {
              const nextPayload = {
                type: PAYLOAD_TYPE,
                version: PAYLOAD_VERSION,
                sourceBlock: block.name,
                attributes: copiedSettings,
              };
              savePayload(nextPayload);
              setPayload(nextPayload);
              onClose?.();
              notice(
                "success",
                __("Cinderwell settings copied.", "cinderwell"),
              );
            }}
          >
            {__("Copy settings", "cinderwell")}
          </MenuItem>
          <MenuItem
            icon="clipboard"
            disabled={!canPaste}
            onClick={() => {
              if (Object.keys(compatibleSettings).length) {
                dispatch("core/block-editor").updateBlockAttributes(
                  block.clientId,
                  compatibleSettings,
                );
              }
              onClose?.();
              const applied = Object.keys(compatibleSettings).length;
              if (applied) {
                notice(
                  "success",
                  sprintf(
                    /* translators: %d: number of block settings applied. */
                    _n(
                      "Applied %d compatible setting.",
                      "Applied %d compatible settings.",
                      applied,
                      "cinderwell",
                    ),
                    applied,
                  ),
                );
              }
              if (unavailableLayout) {
                notice(
                  "warning",
                  __(
                    "The copied layout is not available here, so it was skipped.",
                    "cinderwell",
                  ),
                );
              }
            }}
          >
            {payload?.sourceBlock
              ? sprintf(
                  /* translators: %s: source block title. */
                  __("Paste settings from %s", "cinderwell"),
                  getBlockType(payload.sourceBlock)?.title ||
                    payload.sourceBlock,
                )
              : __("Paste settings", "cinderwell")}
          </MenuItem>
        </>
      )}
    </BlockSettingsMenuControls>
  );
};
