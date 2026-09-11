( function () {
    var toggle = document.querySelector( '.mobile-nav-toggle' );
    var menu = document.querySelector( '#primary-menu' );

    if ( ! toggle || ! menu ) return;

    toggle.addEventListener( 'click', function () {
        var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
        toggle.setAttribute( 'aria-expanded', ! expanded );
        menu.classList.toggle( 'is-open' );
    } );

    document.addEventListener( 'click', function ( e ) {
        if ( ! toggle.contains( e.target ) && ! menu.contains( e.target ) ) {
            toggle.setAttribute( 'aria-expanded', 'false' );
            menu.classList.remove( 'is-open' );
        }
    } );
} )();
