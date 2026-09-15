<?php
namespace Cinderwell_Utilities\Modules;

class Content_Order {
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
        add_action('admin_menu', [$this, 'add_menus']);
        add_action('admin_menu', [$this, 'hide_menus'], 100);
        add_filter('parent_file', [$this, 'highlight_parent_menu']);
        if (!empty($settings['apply_frontend'])) {
            add_action('pre_get_posts', [$this, 'apply_order']);
        }
    }

    public function add_menus() {
        foreach ($this->settings['post_types'] ?? [] as $post_type) {
            $pt_obj = get_post_type_object($post_type);
            if (!$pt_obj) continue;
            add_submenu_page(
                'cinderwell',
                $pt_obj->label . ' Order',
                $pt_obj->label . ' Order',
                'manage_options',
                'cinderwell-order-' . $post_type,
                function () { $this->render_order_page(); }
            );
        }
    }

    /**
     * Keep task screens reachable from Utilities without cluttering the main
     * Cinderwell flyout with one entry per post type.
     */
    public function hide_menus() {
        foreach ($this->settings['post_types'] ?? [] as $post_type) {
            remove_submenu_page('cinderwell', 'cinderwell-order-' . sanitize_key($post_type));
        }
    }

    public function highlight_parent_menu($parent_file) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (0 === strpos($page, 'cinderwell-order-')) {
            return 'cinderwell';
        }
        return $parent_file;
    }

    public function apply_order($query) {
        if (is_admin() || !$query->is_main_query()) return;
        $post_type = $query->get('post_type');
        if (is_array($post_type)) $post_type = reset($post_type);
        if (in_array($post_type, $this->settings['post_types'] ?? [], true)) {
            $query->set('orderby', 'menu_order');
            $query->set('order', 'ASC');
        }
    }

    private function render_order_page() {
        $post_type = sanitize_key($_GET['page'] ?? '');
        $post_type = str_replace('cinderwell-order-', '', $post_type);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_post_type_object($post_type)->label ?? 'Content'); ?> Order</h1>
            <p>Drag and drop to reorder. Click <strong>Save Order</strong> when done.</p>
            <div id="cinderwell-sortable" data-type="content-order" data-post-type="<?php echo esc_attr($post_type); ?>">
                <ul class="cinderwell-sortable-list" id="cinderwell-order-list">
                    <?php
                    $posts = get_posts([
                        'post_type' => $post_type,
                        'post_status' => 'any',
                        'numberposts' => -1,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                    ]);
                    foreach ($posts as $p) {
                        echo '<li class="cinderwell-sortable-item" data-id="' . esc_attr($p->ID) . '">';
                        echo '<span class="cinderwell-sortable-handle">&#9776;</span> ';
                        echo esc_html($p->post_title) . ' <small>(' . esc_html($p->post_status) . ')</small>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
            <button class="button button-primary" id="cinderwell-save-order" style="margin-top:1rem;">Save Order</button>
            <span id="cinderwell-order-status" style="margin-left:1rem;"></span>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var list = document.getElementById('cinderwell-order-list');
            var btn = document.getElementById('cinderwell-save-order');
            var status = document.getElementById('cinderwell-order-status');
            var postType = document.getElementById('cinderwell-sortable').dataset.postType;
            var dragged = null;

            list.addEventListener('dragstart', function(e) {
                dragged = e.target.closest('.cinderwell-sortable-item');
                dragged.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
            });
            list.addEventListener('dragend', function(e) {
                if (dragged) dragged.style.opacity = '';
                dragged = null;
            });
            list.addEventListener('dragover', function(e) {
                e.preventDefault();
                var target = e.target.closest('.cinderwell-sortable-item');
                if (target && target !== dragged) {
                    var rect = target.getBoundingClientRect();
                    var mid = rect.top + rect.height / 2;
                    if (e.clientY < mid) {
                        list.insertBefore(dragged, target);
                    } else {
                        list.insertBefore(dragged, target.nextSibling);
                    }
                }
            });

            [].forEach.call(list.children, function(item) {
                item.draggable = true;
            });

            btn.addEventListener('click', function() {
                var items = list.querySelectorAll('.cinderwell-sortable-item');
                var order = [];
                items.forEach(function(item) { order.push(item.dataset.id); });
                btn.disabled = true;
                status.textContent = 'Saving...';
                fetch(cinderwell_utilities.rest_url + 'content-order/' + postType, {
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
