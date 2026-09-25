<?php
namespace Cinderwell_Media_Folders;

class Import_HappyFiles extends Importer_Base {
    protected function source_taxonomy() {
        return taxonomy_exists('happyfiles_category') ? 'happyfiles_category' : 'happyfiles_folder';
    }

    protected function source_label() {
        return 'HappyFiles';
    }

    public function __construct() {
        add_action('wp_ajax_cinderwell_media_folders_import_happyfiles', [$this, 'handle_import']);
    }

    public function handle_import() {
        check_ajax_referer('cinderwell_media_folders', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $dry_run = !empty($_POST['dry_run']);
        $result  = $dry_run ? $this->dry_run() : $this->import();

        wp_send_json_success($result);
    }
}
