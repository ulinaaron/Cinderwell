const prefersReducedMotion = () => window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

const animateNumber = ( element ) => {
    if ( element.dataset.cwNumberEnhanced || prefersReducedMotion() ) {
        return;
    }

    const output = element.querySelector( '[data-cw-number-output]' );
    const target = Number( element.dataset.cwNumberTarget );
    const decimals = Math.max( 0, Math.min( 4, Number( element.dataset.cwNumberDecimals ) || 0 ) );
    const duration = Math.max( 200, Number( element.dataset.cwNumberDuration ) || 1200 );

    if ( ! output || ! Number.isFinite( target ) ) {
        return;
    }

    element.dataset.cwNumberEnhanced = 'true';
    const finalValue = output.textContent;
    const formatter = new Intl.NumberFormat( document.documentElement.lang || undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    } );
    let startTime;

    const renderFrame = ( timestamp ) => {
        startTime ??= timestamp;
        const progress = Math.min( ( timestamp - startTime ) / duration, 1 );
        const easedProgress = 1 - Math.pow( 1 - progress, 3 );
        output.textContent = formatter.format( target * easedProgress );

        if ( progress < 1 ) {
            window.requestAnimationFrame( renderFrame );
            return;
        }

        output.textContent = finalValue;
    };

    output.textContent = formatter.format( 0 );
    window.requestAnimationFrame( renderFrame );
};

const init = () => {
    const numbers = Array.from( document.querySelectorAll( '[data-cw-number="true"]' ) );

    if ( ! numbers.length || prefersReducedMotion() || !( 'IntersectionObserver' in window ) ) {
        return;
    }

    const observer = new IntersectionObserver(
        ( entries ) => {
            entries.forEach( ( entry ) => {
                if ( ! entry.isIntersecting ) {
                    return;
                }

                observer.unobserve( entry.target );
                animateNumber( entry.target );
            } );
        },
        { threshold: 0.35 }
    );

    numbers.forEach( ( number ) => observer.observe( number ) );
};

if ( document.readyState === 'loading' ) {
    document.addEventListener( 'DOMContentLoaded', init, { once: true } );
} else {
    init();
}
