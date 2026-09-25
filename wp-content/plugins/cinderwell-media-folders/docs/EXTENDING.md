# Extending Cinderwell Media Folders

## Get Folder for Attachment

```php
$folders = wp_get_object_terms($attachment_id, 'cwmf_folder');
```

## Set Folder for Attachment

```php
wp_set_object_terms($attachment_id, [12, 34], 'cwmf_folder');
```

## Create Folder Programmatically

```php
wp_insert_term('My Folder', 'cwmf_folder', ['parent' => 0]);
```

## Query Media by Folder

```php
$query = new WP_Query([
    'post_type' => 'attachment',
    'tax_query' => [[
        'taxonomy' => 'cwmf_folder',
        'field'    => 'term_id',
        'terms'    => 12,
    ]],
]);
```

## REST API

See README.md for endpoints. Read permission is `upload_files`. Managing the folder structure requires `upload_files` and `manage_categories`. Moving media requires `upload_files` and edit access to every attachment in the request.

Move media into a folder:

```json
POST /wp-json/cinder-media-folders/v1/folders/12/media
{
  "attachment_ids": [101, 102],
  "replace": true
}
```

Use folder ID `-1` to remove folder assignments. When multi-folder mode is disabled, the server always replaces existing assignments even if `replace` is false.
