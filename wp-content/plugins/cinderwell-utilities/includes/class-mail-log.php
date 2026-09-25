<?php
/**
 * Metadata-only outbound mail log.
 *
 * @package Cinderwell_Utilities
 */

namespace Cinderwell_Utilities;

defined('ABSPATH') || exit;

class Mail_Log {
    const SCHEMA_VERSION = '1';
    const SCHEMA_OPTION = 'cinderwell_utilities_mail_log_schema';
    const CLEANUP_HOOK = 'cinderwell_utilities_mail_cleanup';

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'cinderwell_mail_log';
    }

    public static function maybe_install() {
        if (self::SCHEMA_VERSION !== get_option(self::SCHEMA_OPTION)) {
            self::install();
        }
    }

    public static function install() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            status varchar(20) NOT NULL,
            provider varchar(40) NOT NULL,
            source varchar(100) NOT NULL DEFAULT 'wordpress',
            to_addresses longtext NOT NULL,
            cc_addresses longtext NOT NULL,
            bcc_addresses longtext NOT NULL,
            subject text NOT NULL,
            response_code smallint(5) unsigned NOT NULL DEFAULT 0,
            message_id varchar(255) NOT NULL DEFAULT '',
            attachment_count smallint(5) unsigned NOT NULL DEFAULT 0,
            error_code varchar(100) NOT NULL DEFAULT '',
            error_message text NOT NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY status (status)
        ) {$charset_collate};";

        dbDelta($sql);
        update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION, false);
    }

    public static function sync_schedule($enabled) {
        $scheduled = wp_next_scheduled(self::CLEANUP_HOOK);
        if ($enabled && !$scheduled) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK);
        } elseif (!$enabled && $scheduled) {
            wp_clear_scheduled_hook(self::CLEANUP_HOOK);
        }
    }

    public static function insert(array $entry) {
        global $wpdb;

        self::maybe_install();
        $defaults = [
            'created_at'       => current_time('mysql', true),
            'status'           => 'failed',
            'provider'         => 'sendgrid',
            'source'           => 'wordpress',
            'to_addresses'     => [],
            'cc_addresses'     => [],
            'bcc_addresses'    => [],
            'subject'          => '',
            'response_code'    => 0,
            'message_id'       => '',
            'attachment_count' => 0,
            'error_code'       => '',
            'error_message'    => '',
        ];
        $entry = wp_parse_args($entry, $defaults);

        $wpdb->insert(
            self::table_name(),
            [
                'created_at'       => sanitize_text_field($entry['created_at']),
                'status'           => sanitize_key($entry['status']),
                'provider'         => sanitize_key($entry['provider']),
                'source'           => sanitize_key($entry['source']),
                'to_addresses'     => wp_json_encode(array_values((array) $entry['to_addresses'])),
                'cc_addresses'     => wp_json_encode(array_values((array) $entry['cc_addresses'])),
                'bcc_addresses'    => wp_json_encode(array_values((array) $entry['bcc_addresses'])),
                'subject'          => sanitize_text_field($entry['subject']),
                'response_code'    => absint($entry['response_code']),
                'message_id'       => sanitize_text_field($entry['message_id']),
                'attachment_count' => absint($entry['attachment_count']),
                'error_code'       => sanitize_key($entry['error_code']),
                'error_message'    => sanitize_textarea_field($entry['error_message']),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public static function query(array $args = []) {
        global $wpdb;

        $args = wp_parse_args($args, [
            'page'     => 1,
            'per_page' => 20,
            'status'   => '',
            'search'   => '',
        ]);
        $page = max(1, absint($args['page']));
        $per_page = min(100, max(1, absint($args['per_page'])));
        $where = ' WHERE 1=1';

        if (in_array($args['status'], ['accepted', 'validated', 'failed'], true)) {
            $where .= $wpdb->prepare(' AND status = %s', $args['status']);
        }
        if ('' !== trim((string) $args['search'])) {
            $like = '%' . $wpdb->esc_like(trim((string) $args['search'])) . '%';
            $where .= $wpdb->prepare(' AND (subject LIKE %s OR to_addresses LIKE %s OR cc_addresses LIKE %s OR bcc_addresses LIKE %s OR message_id LIKE %s)', $like, $like, $like, $like, $like);
        }

        $table = self::table_name();
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}{$where}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal and clauses are prepared above.
        $offset = ($page - 1) * $per_page;
        $sql = $wpdb->prepare("SELECT * FROM {$table}{$where} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $items = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        foreach ($items as &$item) {
            foreach (['to_addresses', 'cc_addresses', 'bcc_addresses'] as $field) {
                $decoded = json_decode($item[$field], true);
                $item[$field] = is_array($decoded) ? $decoded : [];
            }
            $item['id'] = (int) $item['id'];
            $item['response_code'] = (int) $item['response_code'];
            $item['attachment_count'] = (int) $item['attachment_count'];
        }

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => max(1, (int) ceil($total / $per_page)),
        ];
    }

    public static function clear() {
        global $wpdb;
        return false !== $wpdb->query('DELETE FROM ' . self::table_name()); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Internal table name, no user input.
    }

    public static function purge_expired() {
        global $wpdb;

        $settings = Utilities::module_settings('mail_delivery');
        $days = min(365, max(1, absint($settings['retention_days'] ?? 30)));
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        return $wpdb->query($wpdb->prepare('DELETE FROM ' . self::table_name() . ' WHERE created_at < %s', $cutoff)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }
}
