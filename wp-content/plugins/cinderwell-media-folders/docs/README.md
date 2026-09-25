# Cinderwell Media Folders

Hierarchical folder organization for the WordPress media library. Folder assignments do not alter attachment URLs or duplicate files.

## Creating Folders

1. **Media Library:** Use the + button in the searchable folder sidebar
2. **WordPress admin:** Media → Folders taxonomy page
3. **REST API:** `POST /wp-json/cinder-media-folders/v1/folders`

Folders can be nested, renamed, deleted, and color-coded. In grid view, drag one file or a selected group onto a folder. The same taxonomy also filters list view and WordPress media-selection modals.

## Importing

### From HappyFiles
1. Install HappyFiles first
2. Go to Cinderwell → Media Folders → Import
3. Run Dry Run to preview
4. Run Import to migrate
5. Deactivate HappyFiles when ready

### From WP Media Folder
Same flow — install, dry run, import, deactivate.

Both importers preserve nested relationships and store a source mapping. Re-running an import reuses its prior folders and assignments rather than creating duplicates. Source plugin data is left untouched.

## Settings

- **Multi-folder:** Allow attachments in multiple folders
- **Default folder:** Auto-assign uploads made outside an open folder

## REST API

```
GET    /wp-json/cinder-media-folders/v1/folders
GET    /wp-json/cinder-media-folders/v1/folders/{id}
GET    /wp-json/cinder-media-folders/v1/folders/{id}/media
POST   /wp-json/cinder-media-folders/v1/folders
PATCH  /wp-json/cinder-media-folders/v1/folders/{id}
DELETE /wp-json/cinder-media-folders/v1/folders/{id}
POST   /wp-json/cinder-media-folders/v1/folders/{id}/media
```

Authenticated users with `upload_files` can read folders. Folder creation and structure changes additionally require `manage_categories`. Media moves verify `edit_post` for every attachment in the request.
