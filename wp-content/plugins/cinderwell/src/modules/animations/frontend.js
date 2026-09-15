import "./frontend.css";

( () => {
	"use strict";

	const roots = Array.from( document.querySelectorAll( ".cw-animation-root" ) );
	if ( ! roots.length ) return;

	const reducedMotion = window.matchMedia( "(prefers-reduced-motion: reduce)" ).matches;
	const selectors = window.cinderwellAnimationSettings?.sectionSelectors || {};
	const style = getComputedStyle( document.documentElement );
	const timeInMilliseconds = ( value ) => {
		const normalized = value.trim();
		if ( normalized.endsWith( "ms" ) ) return Number.parseFloat( normalized ) || 0;
		if ( normalized.endsWith( "s" ) ) return ( Number.parseFloat( normalized ) || 0 ) * 1000;
		return 0;
	};
	const tokenTime = ( key ) => timeInMilliseconds(
		style.getPropertyValue( `--cw-duration-${ key }` ),
	);

	roots.forEach( ( root ) => {
		let targets = [ root ];
		if ( root.dataset.cwAnimationTarget === "sections" ) {
			const selector = selectors[ root.dataset.cwAnimationBlock ];
			if ( selector ) targets = Array.from( root.querySelectorAll( selector ) );

			if ( ! targets.length ) {
				const inner = Array.from( root.children ).find( ( child ) =>
					Array.from( child.classList ).some( ( className ) => className.endsWith( "__inner" ) ),
				) || root;
				targets = Array.from( inner.children ).filter( ( child ) =>
					! [ "SCRIPT", "STYLE" ].includes( child.tagName ),
				);
			}
		}

		if ( ! targets.length ) targets = [ root ];
		const baseDelay = root.dataset.cwAnimationDelay === "none"
			? 0
			: tokenTime( root.dataset.cwAnimationDelay );
		const stagger = root.dataset.cwAnimationTarget === "sections"
			? tokenTime( "fast" )
			: 0;

		targets.forEach( ( target, index ) => {
			target.classList.add( "cw-animation-item" );
			target.dataset.cwAnimationEffect = root.dataset.cwAnimation;
			target.dataset.cwAnimationDuration = root.dataset.cwAnimationDuration;
			target.style.setProperty(
				"--cw-animation-duration",
				`var(--cw-duration-${ root.dataset.cwAnimationDuration })`,
			);
			target.style.transitionDelay = `${ baseDelay + ( stagger * index ) }ms`;
		} );
		root.cinderwellAnimationTargets = targets;
	} );

	document.documentElement.classList.add( "cw-animations-ready" );
	const reveal = ( root, immediate = false ) => {
		root.cinderwellAnimationTargets.forEach( ( target ) => {
			if ( immediate ) {
				target.classList.remove( "cw-animation-item" );
				target.removeAttribute( "data-cw-animation-effect" );
				target.removeAttribute( "data-cw-animation-duration" );
				target.style.removeProperty( "--cw-animation-duration" );
				target.style.removeProperty( "transition-delay" );
				return;
			}

			target.classList.add( "is-visible" );
			const duration = timeInMilliseconds( getComputedStyle( target ).transitionDuration );
			const delay = timeInMilliseconds( getComputedStyle( target ).transitionDelay );
			window.setTimeout( () => {
				target.classList.remove( "cw-animation-item", "is-visible" );
				target.removeAttribute( "data-cw-animation-effect" );
				target.removeAttribute( "data-cw-animation-duration" );
				target.style.removeProperty( "--cw-animation-duration" );
				target.style.removeProperty( "transition-delay" );
			}, duration + delay + 80 );
		} );
	};

	if ( reducedMotion || !( "IntersectionObserver" in window ) ) {
		roots.forEach( ( root ) => reveal( root, true ) );
		return;
	}

	const observer = new IntersectionObserver( ( entries ) => {
		entries.forEach( ( entry ) => {
			if ( ! entry.isIntersecting ) return;
			reveal( entry.target );
			observer.unobserve( entry.target );
		} );
	}, { rootMargin: "0px 0px -8%", threshold: 0.12 } );

	roots.forEach( ( root ) => observer.observe( root ) );
} )();
