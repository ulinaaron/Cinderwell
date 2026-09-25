const updateButton = ( button, video ) => {
    const isPaused = video.paused;
    const label = isPaused ? button.dataset.playLabel : button.dataset.pauseLabel;
    const icon = button.querySelector( '.cinderwell-background-video__toggle-icon' );
    const text = button.querySelector( '.screen-reader-text' );
    button.setAttribute( 'aria-pressed', isPaused ? 'false' : 'true' );
    button.setAttribute( 'aria-label', label );
    if ( icon ) icon.textContent = isPaused ? '▶' : 'Ⅱ';
    if ( text ) text.textContent = label;
};

export const initBackgroundVideos = () => {
    const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

    document.querySelectorAll( '[data-cinderwell-background-video]' ).forEach( ( root ) => {
        if ( root.dataset.cinderwellBackgroundVideoReady ) return;
        const video = root.querySelector( '.cinderwell-background-video' );
        const button = root.querySelector( '.cinderwell-background-video__toggle' );
        if ( ! video || ! button ) return;

        root.dataset.cinderwellBackgroundVideoReady = 'true';
        if ( reduceMotion ) video.pause();
        updateButton( button, video );

        button.addEventListener( 'click', () => {
            if ( video.paused ) {
                const play = video.play();
                if ( play?.catch ) play.catch( () => updateButton( button, video ) );
            } else {
                video.pause();
            }
            updateButton( button, video );
        } );

        video.addEventListener( 'play', () => updateButton( button, video ) );
        video.addEventListener( 'pause', () => updateButton( button, video ) );
    } );
};

if ( document.readyState === 'loading' ) {
    document.addEventListener( 'DOMContentLoaded', initBackgroundVideos );
} else {
    initBackgroundVideos();
}
