</main><!-- .site-main -->

<footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">
        <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
            <div class="footer-widgets">
                <?php dynamic_sidebar( 'footer-1' ); ?>
            </div>
        <?php endif; ?>

        <div class="site-footer__bottom">
            <?php if ( has_nav_menu( 'footer' ) ) : ?>
                <nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer Menu', 'cinderwell-starter' ); ?>">
                    <?php wp_nav_menu( [ 'theme_location' => 'footer', 'container' => false, 'depth' => 1 ] ); ?>
                </nav>
            <?php endif; ?>

            <div class="site-info">
                &copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.
            </div>
        </div>
    </div>
</footer>

</div><!-- .site-wrapper -->

<?php wp_footer(); ?>
</body>
</html>
