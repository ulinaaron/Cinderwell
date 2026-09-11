<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-wrapper">

<header class="site-header" role="banner">
    <div class="site-header__inner">
        <div class="site-branding">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-title">
                    <?php bloginfo( 'name' ); ?>
                </a>
            <?php endif; ?>
        </div>

        <nav class="primary-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'cinderwell-starter' ); ?>">
            <button class="mobile-nav-toggle" aria-expanded="false" aria-controls="primary-menu">
                <span class="screen-reader-text"><?php esc_html_e( 'Menu', 'cinderwell-starter' ); ?></span>
                <span class="hamburger"></span>
            </button>
            <?php
            wp_nav_menu( [
                'theme_location' => 'primary',
                'menu_id'        => 'primary-menu',
                'menu_class'     => 'nav-menu',
                'container'      => false,
                'fallback_cb'    => false,
            ] );
            ?>
        </nav>
    </div>
</header>

<main id="main" class="site-main">
