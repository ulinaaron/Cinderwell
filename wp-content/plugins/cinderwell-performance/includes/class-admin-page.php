<?php
namespace Cinderwell_Performance;

class Admin_Page {
    public function __construct() {
        add_filter('cinderwell_settings_tabs', [$this, 'register_tab']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_tab($tabs) {
        $tabs['performance'] = [
            'label'    => 'Performance',
            'group'    => 'extensions',
            'callback' => [$this, 'render'],
        ];
        return $tabs;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'cinderwell') === false) return;
        wp_enqueue_style('cinderwell-perf-admin', CINDERWELL_PERFORMANCE_URL . 'assets/css/admin.css', [], CINDERWELL_PERFORMANCE_VERSION);
    }

    public function render() {
        $settings = Settings::get_settings();
        $perf = $settings['performance'];
        $hydration = $settings['hydration'];
        $active = sanitize_key($_GET['subtab'] ?? 'performance');
        ?>
        <div class="cinderwell-perf-admin">
            <nav class="nav-tab-wrapper cinderwell-perf-subtabs">
                <a href="?page=cinderwell&tab=performance&subtab=performance" class="nav-tab <?php echo $active === 'performance' ? 'nav-tab-active' : ''; ?>">Performance</a>
                <a href="?page=cinderwell&tab=performance&subtab=hydration" class="nav-tab <?php echo $active === 'hydration' ? 'nav-tab-active' : ''; ?>">Hydration</a>
            </nav>

            <div class="cinderwell-perf-content" style="margin-top:1rem;">
                <?php if ($active === 'hydration'): ?>
                    <?php $this->render_hydration($hydration); ?>
                <?php else: ?>
                    <?php $this->render_performance($perf); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function render_performance($perf) {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('cinderwell_performance_settings_group'); ?>

            <h2>Performance Module</h2>
            <p class="description">
                Almost everything is <strong>OFF by default</strong>. Enable features one at a time and verify each works for your site.
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">Master Toggle</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[performance][enabled]" value="1" <?php checked(!empty($perf['enabled'])); ?> />
                            Enable performance optimizations
                        </label>
                        <p class="description">Master switch. When OFF, no optimizations run. When ON, each feature below runs only if its own checkbox is checked.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">HTML Cleanup</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[performance][html_cleanup]" value="1" <?php checked(!empty($perf['html_cleanup'])); ?> />
                            Remove <code>type</code> attributes, empty paragraphs, trailing whitespace
                        </label>
                        <p class="description">ON by default. Safe for most sites.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Image Optimizations</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[performance][image_async_decoding]" value="1" <?php checked(!empty($perf['image_async_decoding'])); ?> />
                            <code>decoding="async"</code> on all images
                        </label>
                        <p class="description">ON by default. No visual impact.</p><br>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[performance][image_lazy_loading]" value="1" <?php checked(!empty($perf['image_lazy_loading'])); ?> />
                            <code>loading="lazy"</code> on below-fold images
                        </label>
                        <p class="description">ON by default. LCP image is never lazy-loaded.</p><br>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[performance][image_fetchpriority]" value="1" <?php checked(!empty($perf['image_fetchpriority'])); ?> />
                            <code>fetchpriority="high"</code> on LCP image
                        </label>
                        <p class="description">OFF by default. Can cause issues with some CDN setups.</p><br>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[performance][image_width_height]" value="1" <?php checked(!empty($perf['image_width_height'])); ?> />
                            Inject <code>width</code>/<code>height</code> attributes from media library
                        </label>
                        <p class="description">OFF by default. Risky with dynamically-sized images.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Resource Hints</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[performance][resource_hints_enabled]" value="1" <?php checked(!empty($perf['resource_hints_enabled'])); ?> />
                            Enable resource hints
                        </label>
                        <p class="description">OFF by default.</p>
                        <p><label>Preconnect (one URL per line):</label></p>
                        <textarea name="cinderwell_performance_settings[performance][resource_hints_preconnect]" rows="3" class="large-text"><?php echo esc_textarea(implode("\n", $perf['resource_hints_preconnect'] ?? [])); ?></textarea>
                        <p><label>DNS Prefetch (one URL per line):</label></p>
                        <textarea name="cinderwell_performance_settings[performance][resource_hints_dns_prefetch]" rows="3" class="large-text"><?php echo esc_textarea(implode("\n", $perf['resource_hints_dns_prefetch'] ?? [])); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">CSS Delivery</th>
                    <td>
                        <strong>Block-aware loading remains enabled.</strong>
                        <p class="description">Cinderwell preserves WordPress stylesheet order and only loads block styles where they are needed. CSS combining is intentionally unavailable because it defeats conditional block loading and can break the cascade, relative asset URLs, and inline style dependencies.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Settings'); ?>
        </form>
        <?php
    }

    private function render_hydration($hydration) {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('cinderwell_performance_settings_group'); ?>

            <h2>Hydration Module</h2>
            <p class="description">
                <strong>OFF by default.</strong> Master switch for deferred hydration of interactive blocks.
                When OFF, all blocks load normally. When ON, interactive blocks (Button, FAQ, Tabs, etc.)
                hydrate as they enter the viewport. Static blocks are never affected.
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">Master Toggle</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[hydration][enabled]" value="1" <?php checked(!empty($hydration['enabled'])); ?> />
                            Enable down-the-page hydration
                        </label>
                        <p class="description">When ON, interactive Cinderwell blocks below the fold will hydrate on scroll. The settings below control how that works.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Root Margin</th>
                    <td>
                        <input type="number" name="cinderwell_performance_settings[hydration][root_margin]" value="<?php echo esc_attr($hydration['root_margin']); ?>" min="0" max="500" step="50" class="small-text" /> px
                        <p class="description">Hydrate this many pixels before the element enters the viewport. Default: 200px.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Safe Mode</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[hydration][safe_mode]" value="1" <?php checked(!empty($hydration['safe_mode'])); ?> />
                            Auto-disable hydration if JS errors are detected (recommended)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">SEO Bot Bypass</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cinderwell_performance_settings[hydration][seo_bypass]" value="1" <?php checked(!empty($hydration['seo_bypass'])); ?> />
                            Bypass hydration for search engine bots (recommended)
                        </label>
                    </td>
                </tr>
            </table>

            <h3>Per-Block Override</h3>
            <p>Any Cinderwell block can override hydration behavior:</p>
            <table class="form-table">
                <tr>
                    <th scope="row">Force immediate hydration</th>
                    <td><code>data-cw-hydrate="false"</code></td>
                </tr>
                <tr>
                    <th scope="row">Force deferred hydration</th>
                    <td><code>data-cw-hydrate="true"</code></td>
                </tr>
            </table>

            <?php submit_button('Save Settings'); ?>
        </form>
        <?php
    }
}
