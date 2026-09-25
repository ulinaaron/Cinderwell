import { __, sprintf } from '@wordpress/i18n';

const initializeContentSlider = ( root ) => {
	if ( root.dataset.cwContentSliderReady === 'true' ) return;
	const slides = Array.from( root.querySelectorAll( ':scope .cinderwell-content-slider__slide' ) );
	const previous = root.querySelector( '[data-cw-content-previous]' );
	const next = root.querySelector( '[data-cw-content-next]' );
	const indicators = Array.from( root.querySelectorAll( '[data-cw-content-indicator]' ) );
	const status = root.querySelector( '[data-cw-content-status]' );
	if ( slides.length < 2 ) return;
	let activeIndex = 0;

	const update = ( index, announce = true ) => {
		activeIndex = ( index + slides.length ) % slides.length;
		slides.forEach( ( slide, slideIndex ) => {
			const active = slideIndex === activeIndex;
			slide.classList.toggle( 'is-active', active );
			slide.hidden = ! active;
			slide.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
			if ( 'inert' in slide ) slide.inert = ! active;
		} );
		indicators.forEach( ( indicator, indicatorIndex ) => {
			const active = indicatorIndex === activeIndex;
			indicator.classList.toggle( 'is-active', active );
			if ( active ) indicator.setAttribute( 'aria-current', 'true' );
			else indicator.removeAttribute( 'aria-current' );
		} );
		if ( announce && status ) status.textContent = sprintf( __( 'Slide %1$d of %2$d', 'cinderwell' ), activeIndex + 1, slides.length );
	};
	previous?.addEventListener( 'click', () => update( activeIndex - 1 ) );
	next?.addEventListener( 'click', () => update( activeIndex + 1 ) );
	indicators.forEach( ( indicator, index ) => indicator.addEventListener( 'click', () => update( index ) ) );
	root.addEventListener( 'keydown', ( event ) => {
		if ( event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' ) return;
		if ( event.target.closest( 'a, button, input, textarea, select' ) ) return;
		event.preventDefault();
		update( activeIndex + ( event.key === 'ArrowRight' ? 1 : -1 ) );
	} );
	root.dataset.cwContentSliderReady = 'true';
	update( 0, false );
};

const initializeAll = () => document.querySelectorAll( '[data-cw-content-slider]' ).forEach( initializeContentSlider );
if ( document.readyState === 'loading' ) document.addEventListener( 'DOMContentLoaded', initializeAll, { once: true } );
else initializeAll();
