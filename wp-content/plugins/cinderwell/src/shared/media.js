/**
 * Return a stable full-size URL from either the media modal or REST response.
 */
export const getMediaUrl = ( media ) => media?.url || media?.source_url || media?.sizes?.full?.url || '';

/**
 * Return optional image treatment classes without changing legacy markup when
 * controls remain at their automatic defaults.
 */
export const getImageClassName = ( fit = 'auto', position = 'center', aspect = 'auto' ) => {
    const classes = [];

    if ( fit && fit !== 'auto' ) {
        classes.push( `cw-image-fit-${ fit }` );
    }
    if ( position && position !== 'center' ) {
        classes.push( `cw-image-position-${ position }` );
    }
    if ( aspect && aspect !== 'auto' ) {
        classes.push( `cw-image-aspect-${ aspect }` );
    }

    return classes.length ? ` ${ classes.join( ' ' ) }` : '';
};
