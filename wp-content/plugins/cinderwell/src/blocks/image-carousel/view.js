import { __, sprintf } from '@wordpress/i18n';

const initializeCarousel = ( root ) => {
    if ( root.dataset.cwCarouselReady === 'true' ) return;

    const viewport = root.querySelector( ':scope .cinderwell-image-carousel__viewport' );
    const slides = Array.from( root.querySelectorAll( ':scope .cinderwell-image-carousel__slide' ) );
    const previous = root.querySelector( '[data-cw-carousel-previous]' );
    const next = root.querySelector( '[data-cw-carousel-next]' );
    const indicators = Array.from( root.querySelectorAll( '[data-cw-carousel-indicator]' ) );
    const status = root.querySelector( '[data-cw-carousel-status]' );

    if ( ! viewport || slides.length < 2 ) return;

    let activeIndex = 0;
    let scrollTimer;
    const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

    const update = ( index, announce = false ) => {
        activeIndex = Math.max( 0, Math.min( slides.length - 1, index ) );
        indicators.forEach( ( indicator, indicatorIndex ) => {
            const isActive = indicatorIndex === activeIndex;
            indicator.classList.toggle( 'is-active', isActive );
            if ( isActive ) {
                indicator.setAttribute( 'aria-current', 'true' );
            } else {
                indicator.removeAttribute( 'aria-current' );
            }
        } );
        if ( previous ) previous.disabled = activeIndex === 0;
        if ( next ) next.disabled = activeIndex === slides.length - 1;
        if ( announce && status ) {
            status.textContent = sprintf(
                __( 'Slide %1$d of %2$d', 'cinderwell' ),
                activeIndex + 1,
                slides.length
            );
        }
    };

    const goTo = ( index, announce = true ) => {
        const targetIndex = Math.max( 0, Math.min( slides.length - 1, index ) );
        slides[ targetIndex ].scrollIntoView( {
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
            block: 'nearest',
            inline: 'start',
        } );
        update( targetIndex, announce );
    };

    previous?.addEventListener( 'click', () => goTo( activeIndex - 1 ) );
    next?.addEventListener( 'click', () => goTo( activeIndex + 1 ) );
    indicators.forEach( ( indicator, index ) => indicator.addEventListener( 'click', () => goTo( index ) ) );

    viewport.addEventListener( 'keydown', ( event ) => {
        let nextIndex = null;
        const isRtl = window.getComputedStyle( root ).direction === 'rtl';
        if ( event.key === 'ArrowLeft' ) nextIndex = activeIndex + ( isRtl ? 1 : -1 );
        if ( event.key === 'ArrowRight' ) nextIndex = activeIndex + ( isRtl ? -1 : 1 );
        if ( event.key === 'Home' ) nextIndex = 0;
        if ( event.key === 'End' ) nextIndex = slides.length - 1;
        if ( nextIndex === null ) return;
        event.preventDefault();
        goTo( nextIndex );
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
    root.dataset.cwCarouselReady = 'true';
};

const initializeAllCarousels = () => document.querySelectorAll( '[data-cw-carousel]' ).forEach( initializeCarousel );

if ( document.readyState === 'loading' ) {
    document.addEventListener( 'DOMContentLoaded', initializeAllCarousels, { once: true } );
} else {
    initializeAllCarousels();
}
