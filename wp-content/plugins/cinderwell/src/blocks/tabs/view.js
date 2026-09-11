const initializeTabs = ( root, rootIndex ) => {
    if ( root.dataset.cwTabsReady === 'true' ) return;

    const inner = root.querySelector( ':scope > .cinderwell-tabs__inner' );
    const layout = inner?.querySelector( ':scope > .cinderwell-tabs__layout' );
    const tablist = layout?.querySelector( ':scope > .cinderwell-tabs__tablist' );
    const panelsRoot = layout?.querySelector( ':scope > .cinderwell-tabs__panels' );
    const tabs = tablist ? Array.from( tablist.querySelectorAll( ':scope > .cinderwell-tabs__tab' ) ) : [];
    const panels = panelsRoot ? Array.from( panelsRoot.querySelectorAll( ':scope > .cinderwell-tabs__panel' ) ) : [];
    const count = Math.min( tabs.length, panels.length );

    if ( ! tablist || ! count ) return;

    const baseId = root.id || `cw-tabs-${ rootIndex + 1 }`;
    root.id = baseId;

    const updateOrientation = () => {
        tablist.setAttribute( 'aria-orientation', window.getComputedStyle( tablist ).flexDirection === 'column' ? 'vertical' : 'horizontal' );
    };

    const activate = ( nextIndex, moveFocus = false ) => {
        const index = ( nextIndex + count ) % count;
        tabs.slice( 0, count ).forEach( ( tab, tabIndex ) => {
            const isActive = tabIndex === index;
            tab.classList.toggle( 'is-active', isActive );
            tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
            tab.tabIndex = isActive ? 0 : -1;
            panels[ tabIndex ].classList.toggle( 'is-active', isActive );
            panels[ tabIndex ].hidden = ! isActive;
        } );
        if ( moveFocus ) tabs[ index ].focus();
    };

    tabs.slice( 0, count ).forEach( ( tab, index ) => {
        const tabId = `${ baseId }-tab-${ index + 1 }`;
        const panelId = `${ baseId }-panel-${ index + 1 }`;
        tab.id = tabId;
        tab.setAttribute( 'aria-controls', panelId );
        panels[ index ].id = panelId;
        panels[ index ].setAttribute( 'role', 'tabpanel' );
        panels[ index ].setAttribute( 'aria-labelledby', tabId );
        tab.addEventListener( 'click', () => activate( index ) );
        tab.addEventListener( 'keydown', ( event ) => {
            const orientation = tablist.getAttribute( 'aria-orientation' );
            const previousKey = orientation === 'vertical' ? 'ArrowUp' : 'ArrowLeft';
            const nextKey = orientation === 'vertical' ? 'ArrowDown' : 'ArrowRight';
            let nextIndex = null;

            if ( event.key === previousKey ) nextIndex = index - 1;
            if ( event.key === nextKey ) nextIndex = index + 1;
            if ( event.key === 'Home' ) nextIndex = 0;
            if ( event.key === 'End' ) nextIndex = count - 1;
            if ( null === nextIndex ) return;
            event.preventDefault();
            activate( nextIndex, true );
        } );
    } );

    updateOrientation();
    window.addEventListener( 'resize', updateOrientation, { passive: true } );
    activate( Math.max( 0, tabs.findIndex( ( tab ) => tab.classList.contains( 'is-active' ) ) ) );
    root.dataset.cwTabsReady = 'true';
};

const initializeAllTabs = () => {
    document.querySelectorAll( '[data-cw-tabs]' ).forEach( initializeTabs );
};

if ( document.readyState === 'loading' ) {
    document.addEventListener( 'DOMContentLoaded', initializeAllTabs, { once: true } );
} else {
    initializeAllTabs();
}
