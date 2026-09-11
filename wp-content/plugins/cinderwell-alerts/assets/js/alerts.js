( () => {
    const storagePrefix = 'cinderwell-alert:';

    const readDismissal = ( key ) => {
        try {
            const value = JSON.parse( window.localStorage.getItem( storagePrefix + key ) );
            if ( value?.expires > Date.now() ) return true;
            window.localStorage.removeItem( storagePrefix + key );
        } catch ( error ) {
            return false;
        }
        return false;
    };

    const storeDismissal = ( key, days ) => {
        try {
            window.localStorage.setItem( storagePrefix + key, JSON.stringify( {
                expires: Date.now() + ( days * 86400000 ),
            } ) );
        } catch ( error ) {
            // The alert can still be dismissed for this page view.
        }
    };

    const refreshWrapper = ( alert ) => {
        const wrapper = alert.closest( '.cw-alerts' );
        if ( wrapper && ! wrapper.querySelector( '.cw-alert:not([hidden])' ) ) wrapper.hidden = true;
    };

    const getAdjacentFocusTarget = ( alert ) => {
        const focusable = Array.from( document.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        ) ).filter( ( element ) => ! element.hidden && element.getClientRects().length );
        const activeIndex = focusable.indexOf( document.activeElement );

        return focusable.slice( activeIndex + 1 ).find( ( element ) => ! alert.contains( element ) )
            || focusable.slice( 0, activeIndex ).reverse().find( ( element ) => ! alert.contains( element ) );
    };

    const dismiss = ( alert, persist = true ) => {
        const shouldRestoreFocus = alert.contains( document.activeElement );
        const focusTarget = shouldRestoreFocus ? getAdjacentFocusTarget( alert ) : null;

        if ( persist ) {
            storeDismissal( alert.dataset.cwAlertKey, Number.parseInt( alert.dataset.cwAlertDismissDays, 10 ) || 7 );
        }
        alert.classList.add( 'is-dismissing' );
        window.setTimeout( () => {
            alert.hidden = true;
            refreshWrapper( alert );
            focusTarget?.focus( { preventScroll: true } );
            document.dispatchEvent( new CustomEvent( 'cinderwell-alert-dismissed', {
                detail: { key: alert.dataset.cwAlertKey },
            } ) );
        }, window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 0 : 220 );
    };

    const initialize = () => {
        document.querySelectorAll( '[data-cw-alert]' ).forEach( ( alert ) => {
            if ( readDismissal( alert.dataset.cwAlertKey ) ) {
                alert.hidden = true;
                refreshWrapper( alert );
                return;
            }

            alert.querySelector( '[data-cw-alert-dismiss]' )?.addEventListener( 'click', () => dismiss( alert ) );
        } );
    };

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', initialize, { once: true } );
    } else {
        initialize();
    }
} )();
