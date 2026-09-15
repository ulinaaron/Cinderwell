<?php
namespace Cinderwell_Utilities\Modules;

class Terms_Order {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
        add_action('admin_menu', [$this, 'add_menus']);
        add_action('admin_menu', [$this, 'hide_menus'], 100);
        add_filter('parent_file', [$this, 'highlight_parent_menu']);
        add_filter('terms_clauses', [$this, 'apply_order'], 10, 3);
    }

    public function add_menus() {
        foreach ($this->settings['taxonomies'] ?? [] as $taxonomy) {
            $tax_obj = get_taxonomy($taxonomy);
            if (!$tax_obj) continue;
            add_submenu_page(
                'cinderwell',
                $tax_obj->label . ' Order',
                $tax_obj->label . ' Order',
                'manage_options',
                'cinderwell-terms-' . $taxonomy,
                function () { $this->render_order_page(); }
            );
        }
    }

    public function hide_menus() {
        foreach ($this->settings['taxonomies'] ?? [] as $taxonomy) {
            remove_submenu_page('cinderwell', 'cinderwell-terms-' . sanitize_key($taxonomy));
        }
    }

    public function highlight_parent_menu($parent_file) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (0 === strpos($page, 'cinderwell-terms-')) {
            return 'cinderwell';
        }
        return $parent_file;
    }

    public function apply_order($clauses, $taxonomies, $args) {
        if (is_admin() || empty($this->settings['apply_frontend'])) return $clauses;
        $tax_list = is_array($taxonomies) ? $taxonomies : [$taxonomies];
        foreach ($tax_list as $tax) {
            if (in_array($tax, $this->settings['taxonomies'] ?? [], true)) {
                global $wpdb;
                if (false === strpos($clauses['join'], 'cw_term_order')) {
                    $clauses['join'] .= $wpdb->prepare(
                        " LEFT JOIN {$wpdb->termmeta} AS cw_term_order ON t.term_id = cw_term_order.term_id AND cw_term_order.meta_key = %s",
                        'term_order'
                    );
                }
                $clauses['orderby'] = 'ORDER BY COALESCE(CAST(cw_term_order.meta_value AS UNSIGNED), 2147483647), t.name';
                return $clauses;
            }
        }
        return $clauses;
    }

    private function render_order_page() {
        $taxonomy = sanitize_key(str_replace('cinderwell-terms-', '', $_GET['page'] ?? ''));
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_taxonomy($taxonomy)->label ?? 'Terms'); ?> Order</h1>
            <p>Drag and drop to reorder. Click <strong>Save Order</strong> when done.</p>
            <div id="cinderwell-sortable" data-type="terms-order" data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                <ul class="cinderwell-sortable-list" id="cinderwell-terms-list">
                    <?php
                    $terms = get_terms([
                        'taxonomy' => $taxonomy,
                        'hide_empty' => false,
                    ]);
                    if (!is_wp_error($terms)) {
                        usort($terms, static function ($a, $b) {
                            $a_order = (int) get_term_meta($a->term_id, 'term_order', true);
                            $b_order = (int) get_term_meta($b->term_id, 'term_order', true);
                            return $a_order <=> $b_order ?: strcasecmp($a->name, $b->name);
                        });
                        foreach ($terms as $t) {
                            echo '<li class="cinderwell-sortable-item" data-id="' . esc_attr($t->term_id) . '">';
                            echo '<span class="cinderwell-sortable-handle">&#9776;</span> ';
                            echo esc_html($t->name) . ' <small>(' . intval($t->count) . ')</small>';
                            echo '</li>';
                        }
                    }
                    ?>
                </ul>
            </div>
            <button class="button button-primary" id="cinderwell-save-terms-order" style="margin-top:1rem;">Save Order</button>
            <span id="cinderwell-order-status" style="margin-left:1rem;"></span>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var list = document.getElementById('cinderwell-terms-list');
            var btn = document.getElementById('cinderwell-save-terms-order');
            var status = document.getElementById('cinderwell-order-status');
            var taxonomy = document.getElementById('cinderwell-sortable').dataset.taxonomy;
            var dragged = null;

            list.addEventListener('dragstart', function(e) {
                dragged = e.target.closest('.cinderwell-sortable-item');
                dragged.style.opacity = '0.5';
            });
            list.addEventListener('dragend', function() {
                if (dragged) dragged.style.opacity = '';
                dragged = null;
            });
            list.addEventListener('dragover', function(e) {
                e.preventDefault();
                var target = e.target.closest('.cinderwell-sortable-item');
                if (target && target !== dragged) {
                    var rect = target.getBoundingClientRect();
                    if (e.clientY < rect.top + rect.height / 2) {
                        list.insertBefore(dragged, target);
                    } else {
                        list.insertBefore(dragged, target.nextSibling);
                    }
                }
            });
            [].forEach.call(list.children, function(item) { item.draggable = true; });

            btn.addEventListener('click', function() {
                var items = list.querySelectorAll('.cinderwell-sortable-item');
                var order = [];
                items.forEach(function(item) { order.push(item.dataset.id); });
                btn.disabled = true;
                status.textContent = 'Saving...';
                fetch(cinderwell_utilities.rest_url + 'terms-order/' + taxonomy, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-WP-Nonce': cinderwell_utilities.nonce},
                    body: JSON.stringify({order: order})
                }).then(function(r) { return r.json(); }).then(function(data) {
                    status.textContent = data.success ? 'Order saved.' : 'Error.';
                    btn.disabled = false;
                }).catch(function() {
                    status.textContent = 'Network error.';
                    btn.disabled = false;
                });
            });
        });
        </script>
        <?php
    }
}
