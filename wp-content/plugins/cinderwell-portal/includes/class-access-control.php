<?php
namespace Cinderwell_Portal;

class Access_Control {
    private static $sitemap_exclusions = [];

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_filter('the_content', [$this, 'maybe_hide_content']);
        add_filter('wp_robots', [$this, 'filter_robots'], 15);
        add_filter('wp_sitemaps_posts_query_args', [$this, 'filter_sitemap_query'], 10, 2);
    }

    public function add_meta_box() {
        $post_types = get_post_types(['public' => true]);
        foreach ($post_types as $post_type) {
            add_meta_box(
                'cinderwell_portal_restrict',
                __('Members Portal', 'cinderwell-portal'),
                [$this, 'render_meta_box'],
                $post_type,
                'side',
                'high'
            );
        }
    }

    public function render_meta_box($post) {
        wp_nonce_field('cinderwell_portal_restrict', 'cinderwell_portal_nonce');
        $restricted = get_post_meta($post->ID, '_cinderwell_portal_restricted', true);
        $redirect_page_id = get_post_meta($post->ID, '_cinderwell_portal_redirect_page', true);
        ?>
        <div class="cw-portal-access" data-cw-portal-access>
            <label class="cw-portal-access__toggle">
                <input
                    type="checkbox"
                    name="cinderwell_portal_restricted"
                    value="1"
                    data-cw-portal-restricted
                    <?php checked($restricted, '1'); ?>
                />
                <span>
                    <strong><?php esc_html_e('Members only', 'cinderwell-portal'); ?></strong>
                    <small><?php esc_html_e('Require an approved member account to view the full content.', 'cinderwell-portal'); ?></small>
                </span>
            </label>

            <div class="cw-portal-access__settings" data-cw-portal-restricted-settings <?php echo '1' === $restricted ? '' : 'hidden'; ?>>
                <label class="cw-portal-access__label" for="cinderwell_portal_redirect_page">
                    <?php esc_html_e('Login destination', 'cinderwell-portal'); ?>
                </label>
                <?php
                wp_dropdown_pages([
                    'name' => 'cinderwell_portal_redirect_page',
                    'id' => 'cinderwell_portal_redirect_page',
                    'class' => 'cw-portal-access__select',
                    'selected' => $redirect_page_id,
                    'show_option_none' => __('Default WordPress login', 'cinderwell-portal'),
                    'option_none_value' => '0',
                    'post_status' => 'publish',
                ]);
                ?>
                <p class="cw-portal-access__help">
                    <?php esc_html_e('Choose where visitors should go to sign in.', 'cinderwell-portal'); ?>
                </p>
                <p class="cw-portal-access__note">
                    <span class="dashicons dashicons-lock" aria-hidden="true"></span>
                    <?php esc_html_e('Visitors see a short preview and login prompt. Approved members see the full content.', 'cinderwell-portal'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST['cinderwell_portal_nonce']) || !wp_verify_nonce($_POST['cinderwell_portal_nonce'], 'cinderwell_portal_restrict')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $restricted = !empty($_POST['cinderwell_portal_restricted']) ? '1' : '';
        update_post_meta($post_id, '_cinderwell_portal_restricted', $restricted);

        $redirect = intval($_POST['cinderwell_portal_redirect_page'] ?? 0);
        update_post_meta($post_id, '_cinderwell_portal_redirect_page', $redirect);
    }

    public function maybe_hide_content($content) {
        if (!is_singular()) return $content;
        $post_id = get_the_ID();
        if (get_post_meta($post_id, '_cinderwell_portal_restricted', true) !== '1') return $content;

        if (!is_user_logged_in()) {
            $custom_redirect = get_post_meta($post_id, '_cinderwell_portal_redirect_page', true);
            if ($custom_redirect) {
                $login_url = get_permalink($custom_redirect);
            } else {
                $login_url = wp_login_url(get_permalink());
            }

            $teaser = wp_trim_words(strip_tags($content), 30);
            $message = '<div class="cinderwell-portal-gate">';
            $message .= '<p>' . esc_html__('This content is for members only.', 'cinderwell-portal') . '</p>';
            $message .= '<p><a href="' . esc_url($login_url) . '" class="btn btn--primary cinderwell-portal-btn">' . esc_html__('Log in to access', 'cinderwell-portal') . '</a></p>';
            $message .= '</div>';
            return $teaser . $message;
        }

        if (!Portal::is_member()) {
            $teaser = wp_trim_words(strip_tags($content), 30);
            $message = '<div class="cinderwell-portal-gate">';
            $message .= '<p>' . esc_html__('This content is for approved members only.', 'cinderwell-portal') . '</p>';
            $message .= '</div>';
            return $teaser . $message;
        }

        return $content;
    }

    /**
     * Describe the search policy owned by Members Portal for a post.
     *
     * Public pages that merely contain a Member Only block are intentionally
     * not included. The policy applies to whole-page restrictions and portal
     * utility screens such as login, registration, and member profiles.
     */
    public static function get_search_policy($post_id) {
        $post = get_post($post_id);
        $policy = [
            'noindex' => false,
            'exclude_from_sitemap' => false,
            'suppress_description' => false,
            'suppress_social' => false,
            'suppress_schema' => false,
            'locked' => false,
            'code' => '',
            'label' => '',
            'description' => '',
        ];

        if (!$post instanceof \WP_Post) {
            return $policy;
        }

        if ('1' === get_post_meta($post->ID, '_cinderwell_portal_restricted', true)) {
            $policy = [
                'noindex' => true,
                'exclude_from_sitemap' => true,
                'suppress_description' => true,
                'suppress_social' => true,
                'suppress_schema' => true,
                'locked' => true,
                'code' => 'portal-protected',
                'label' => __('Members only', 'cinderwell-portal'),
                'description' => __('Members Portal prevents search engines from indexing this restricted page.', 'cinderwell-portal'),
            ];
        } elseif (self::is_utility_post($post)) {
            $policy = [
                'noindex' => true,
                'exclude_from_sitemap' => true,
                'suppress_description' => true,
                'suppress_social' => true,
                'suppress_schema' => true,
                'locked' => true,
                'code' => 'portal-utility',
                'label' => __('Portal page', 'cinderwell-portal'),
                'description' => __('Members Portal prevents search engines from indexing login, registration, and account utility pages.', 'cinderwell-portal'),
            ];
        }

        return (array) apply_filters('cinderwell_portal_search_policy', $policy, $post);
    }

    public static function is_utility_post($post) {
        $post = get_post($post);
        if (!$post instanceof \WP_Post) {
            return false;
        }

        foreach (['cinderwell-portal/login', 'cinderwell-portal/register', 'cinderwell-portal/member-profile'] as $block_name) {
            if (has_block($block_name, $post)) {
                return true;
            }
        }
        return false;
    }

    public function filter_robots($robots) {
        if (!is_singular()) {
            return $robots;
        }
        $policy = self::get_search_policy(get_queried_object_id());
        if (!empty($policy['noindex'])) {
            unset($robots['index']);
            $robots['noindex'] = true;
        }
        return $robots;
    }

    public function filter_sitemap_query($args, $post_type) {
        if (isset(self::$sitemap_exclusions[$post_type])) {
            $excluded = self::$sitemap_exclusions[$post_type];
        } else {
            $excluded = [];
            $posts = get_posts([
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'no_found_rows' => true,
                'orderby' => 'ID',
                'order' => 'ASC',
                'suppress_filters' => true,
            ]);

            foreach ($posts as $post) {
                $policy = self::get_search_policy($post);
                if (!empty($policy['exclude_from_sitemap'])) {
                    $excluded[] = $post->ID;
                }
            }

            self::$sitemap_exclusions[$post_type] = $excluded;
        }

        if ($excluded) {
            $args['post__not_in'] = array_values(array_unique(array_merge((array) ($args['post__not_in'] ?? []), $excluded)));
        }
        return $args;
    }
}
