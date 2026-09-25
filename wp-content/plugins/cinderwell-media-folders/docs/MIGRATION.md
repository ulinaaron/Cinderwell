# Migrating from HappyFiles

## Before You Start

- Backup your site
- Test on staging first

## Steps

1. Install Cinderwell Media Folders (HappyFiles stays active)
2. Go to Cinderwell → Media Folders → Import
3. Run **Preview import** to inspect the migration without making changes
4. Run Import to migrate folders and reassign attachments
5. Verify in Media Library
6. Deactivate HappyFiles

## Rollback

Deactivate Cinderwell Media Folders, reactivate HappyFiles. HappyFiles data is untouched.

# Migrating from WP Media Folder

Same flow as HappyFiles. The importer detects WP Media Folder's `wpmf-category` taxonomy (with legacy fallback support).

## Re-running an Import

Imported folders receive stable source metadata. Re-running the same import updates and reuses those folders, including deeply nested trees, instead of matching unrelated folders that happen to share the same name.

## Need Help?

Contact your Stevens account representative.
