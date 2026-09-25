import { __, sprintf } from '@wordpress/i18n';

const initializeCarousel = ( root ) => {
    if ( root.dataset.cwTestimonialsCarouselReady === 'true' ) return;
    const viewport = root.querySelector( ':scope .cinderwell-testimonials__viewport' );
    const slides = Array.from( root.querySelectorAll( ':scope .cinderwell-testimonials__item' ) );
    const previous = root.querySelector( '[data-cw-testimonials-previous]' );
    const next = root.querySelector( '[data-cw-testimonials-next]' );
    const indicators = Array.from( root.querySelectorAll( '[data-cw-testimonials-indicator]' ) );
    const status = root.querySelector( '[data-cw-testimonials-status]' );
    if ( ! viewport || slides.length < 2 ) return;
    let activeIndex = 0;
    let scrollTimer;
    const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
    const update = ( index, announce = false ) => {
        activeIndex = Math.max( 0, Math.min( slides.length - 1, index ) );
        indicators.forEach( ( indicator, indicatorIndex ) => {
            const active = indicatorIndex === activeIndex;
            indicator.classList.toggle( 'is-active', active );
            if ( active ) indicator.setAttribute( 'aria-current', 'true' );
            else indicator.removeAttribute( 'aria-current' );
        } );
        if ( previous ) previous.disabled = activeIndex === 0;
        if ( next ) next.disabled = activeIndex === slides.length - 1;
        if ( announce && status ) status.textContent = sprintf( __( 'Testimonial %1$d of %2$d', 'cinderwell' ), activeIndex + 1, slides.length );
    };
    const goTo = ( index ) => {
        const target = Math.max( 0, Math.min( slides.length - 1, index ) );
        slides[ target ].scrollIntoView( { behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'nearest', inline: 'start' } );
        update( target, true );
    };
    previous?.addEventListener( 'click', () => goTo( activeIndex - 1 ) );
    next?.addEventListener( 'click', () => goTo( activeIndex + 1 ) );
    indicators.forEach( ( indicator, index ) => indicator.addEventListener( 'click', () => goTo( index ) ) );
    viewport.addEventListener( 'keydown', ( event ) => {
        const rtl = window.getComputedStyle( root ).direction === 'rtl';
        let target = null;
        if ( event.key === 'ArrowLeft' ) target = activeIndex + ( rtl ? 1 : -1 );
        if ( event.key === 'ArrowRight' ) target = activeIndex + ( rtl ? -1 : 1 );
        if ( event.key === 'Home' ) target = 0;
        if ( event.key === 'End' ) target = slides.length - 1;
        if ( target === null ) return;
        event.preventDefault();
        goTo( target );
    } );
    viewport.addEventListener( 'scroll', () => {
        window.clearTimeout( scrollTimer );
        scrollTimer = window.setTimeout( () => {
            const viewportRect = viewport.getBoundingClientRect();
            const index = slides.reduce( ( closest, slide, slideIndex ) => {
                const distance = Math.abs( slide.getBoundingClientRect().left - viewportRect.left );
                return distance < closest.distance ? { index: slideIndex, distance } : closest;
            }, { index: 0, distance: Number.POSITIVE_INFINITY } ).index;
            update( index, true );
        }, 100 );
    }, { passive: true } );
    update( 0 );
    root.dataset.cwTestimonialsCarouselReady = 'true';
};

const initializeAll = () => document.querySelectorAll( '[data-cw-testimonials-carousel]' ).forEach( initializeCarousel );
if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', initializeAll, { once: true } );
else initializeAll();
