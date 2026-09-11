<?php get_header(); ?>
<div class="entry-content">
    <?php
    while ( have_posts() ) {
        the_post();
        the_content();
    }
    ?>
</div>
<?php get_footer(); ?>
