<?php
/**
 * Pattern loader — registers all Cinderwell block patterns.
 *
 * @package Cinderwell
 */

namespace Cinderwell;

defined( 'ABSPATH' ) || exit;

class Pattern_Loader {

    public function __construct() {
        add_action( 'init', [ $this, 'register_patterns' ] );
    }

    public function register_patterns() {
        register_block_pattern_category( 'cinderwell', [
            'label' => __( 'Cinderwell', 'cinderwell' ),
        ] );

        $patterns = $this->get_patterns();
        foreach ( $patterns as $pattern ) {
            register_block_pattern( 'cinderwell/' . $pattern['slug'], $pattern );
        }

        /**
         * Fires after Cinderwell patterns are registered.
         */
        do_action( 'cinderwell_register_patterns' );
    }

    private function get_patterns() {
        return [
            [
                'slug'        => 'homepage-hero',
                'title'       => __( 'Homepage Hero', 'cinderwell' ),
                'description' => __( 'Hero with eyebrow, heading, subheading, and 2 buttons.', 'cinderwell' ),
                'categories'  => [ 'cinderwell' ],
                'keywords'    => [ 'hero', 'banner', 'homepage' ],
                'content'     => '<!-- wp:cinderwell/hero {"showEyebrow":true,"showHeading":true,"showSubheading":true,"showImage":false,"showFootnote":false,"eyebrow":"Welcome","heading":"Building for Life","subheading":"Three generations of craftsmanship.","buttons":[{"text":"Contact Us","url":"/contact","variant":"primary","size":"md"},{"text":"Our Work","url":"/portfolio","variant":"secondary","size":"md"}],"spacingResponsive":{"desktop":{"top":"xl","bottom":"xl","linked":true}},"background":"white"} /-->',
            ],
            [
                'slug'        => 'about-team',
                'title'       => __( 'About + Team', 'cinderwell' ),
                'description' => __( 'Body content with pullquote and team quote.', 'cinderwell' ),
                'categories'  => [ 'cinderwell' ],
                'keywords'    => [ 'about', 'team', 'story' ],
                'content'     => '<!-- wp:cinderwell/body {"showEyebrow":true,"showHeading":true,"showBody":true,"showPullquote":true,"showFootnote":false,"eyebrow":"About Us","heading":"Our Story","bodyContent":"<p>We are a dedicated team of professionals building things that matter.</p>","pullquote":"Great things happen when people collaborate."} /--><!-- wp:cinderwell/image-text {"showEyebrow":false,"showHeading":true,"showBody":true,"showCaption":true,"showFootnote":false,"heading":"Our Team","bodyContent":"<p>Meet the people behind the work.</p>","alignment":"left"} /--><!-- wp:cinderwell/quote {"quote":"We build relationships, not just buildings.","attribution":"Aaron Mazade","showByline":true,"showContext":true,"byline":"CEO","context":"2024 Interview"} /-->',
            ],
            [
                'slug'        => 'services-grid',
                'title'       => __( 'Services Grid', 'cinderwell' ),
                'description' => __( 'Section with 3 service offering CTAs.', 'cinderwell' ),
                'categories'  => [ 'cinderwell' ],
                'keywords'    => [ 'services', 'grid', 'offerings' ],
                'content'     => '<!-- wp:cinderwell/section {"spacingResponsive":{"desktop":{"top":"xl","bottom":"xl","linked":true}},"background":"light"} --><div class="wp-block-cinderwell-section__inner"><!-- wp:cinderwell/cta {"showEyebrow":true,"showHeading":true,"showBody":true,"showFootnote":false,"eyebrow":"Service 1","heading":"Commercial Construction","bodyContent":"Full-service commercial builds from concept to completion.","buttons":[{"text":"Learn More","url":"/commercial","variant":"primary","size":"md"}]} /--><!-- wp:cinderwell/cta {"showEyebrow":true,"showHeading":true,"showBody":true,"showFootnote":false,"eyebrow":"Service 2","heading":"Residential","bodyContent":"Custom homes and renovations tailored to your vision.","buttons":[{"text":"Learn More","url":"/residential","variant":"primary","size":"md"}]} /--><!-- wp:cinderwell/cta {"showEyebrow":true,"showHeading":true,"showBody":true,"showFootnote":false,"eyebrow":"Service 3","heading":"Industrial","bodyContent":"Industrial facility construction built to last.","buttons":[{"text":"Learn More","url":"/industrial","variant":"primary","size":"md"}]} /--></div><!-- /wp:cinderwell/section -->',
            ],
            [
                'slug'        => 'contact-form',
                'title'       => __( 'Contact', 'cinderwell' ),
                'description' => __( 'Body intro with contact form.', 'cinderwell' ),
                'categories'  => [ 'cinderwell' ],
                'keywords'    => [ 'contact', 'form', 'get in touch' ],
                'content'     => '<!-- wp:cinderwell/body {"showEyebrow":true,"showHeading":true,"showBody":true,"showFootnote":false,"eyebrow":"Contact","heading":"Get in Touch","bodyContent":"<p>Fill out the form below and we will get back to you shortly.</p>"} /--><!-- wp:cinderwell/gravity-form {"formId":1,"title":false,"description":false,"ajax":true} /-->',
            ],
            [
                'slug'        => 'feature-list',
                'title'       => __( 'Feature List', 'cinderwell' ),
                'description' => __( 'Section with 3 image+text feature blocks.', 'cinderwell' ),
                'categories'  => [ 'cinderwell' ],
                'keywords'    => [ 'features', 'list', 'benefits' ],
                'content'     => '<!-- wp:cinderwell/section {"background":"white"} --><div class="wp-block-cinderwell-section__inner"><!-- wp:cinderwell/image-text {"showEyebrow":true,"showHeading":true,"showBody":true,"showCaption":true,"showFootnote":false,"eyebrow":"Feature 1","heading":"Fast Performance","bodyContent":"<p>Optimized for speed and efficiency.</p>","alignment":"left"} /--><!-- wp:cinderwell/image-text {"showEyebrow":true,"showHeading":true,"showBody":true,"showCaption":true,"showFootnote":false,"eyebrow":"Feature 2","heading":"Accessible","bodyContent":"<p>WCAG 2.1 AA compliant out of the box.</p>","alignment":"right"} /--><!-- wp:cinderwell/image-text {"showEyebrow":true,"showHeading":true,"showBody":true,"showCaption":true,"showFootnote":false,"eyebrow":"Feature 3","heading":"Extensible","bodyContent":"<p>Hooks and filters for deep customization.</p>","alignment":"left"} /--></div><!-- /wp:cinderwell/section -->',
            ],
            [
                'slug'        => 'testimonial-row',
                'title'       => __( 'Testimonial Row', 'cinderwell' ),
                'description' => __( 'Section with 3 quotes for social proof.', 'cinderwell' ),
                'categories'  => [ 'cinderwell' ],
                'keywords'    => [ 'testimonials', 'quotes', 'reviews' ],
                'content'     => '<!-- wp:cinderwell/section {"background":"light"} --><div class="wp-block-cinderwell-section__inner"><!-- wp:cinderwell/quote {"quote":"This product changed our business for the better.","attribution":"John Smith","showByline":true,"showContext":false,"byline":"Acme Corp"} /--><!-- wp:cinderwell/quote {"quote":"Exceptional quality and outstanding service every time.","attribution":"Jane Doe","showByline":true,"showContext":false,"byline":"Widget Co"} /--><!-- wp:cinderwell/quote {"quote":"Highly recommend to anyone looking for reliability.","attribution":"Bob Wilson","showByline":true,"showContext":false,"byline":"Gadget Inc"} /--></div><!-- /wp:cinderwell/section -->',
            ],
        ];
    }
}
