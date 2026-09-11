import { __ } from '@wordpress/i18n';

export const iconOptions = [
    [ 'star', 'Star' ], [ 'heart', 'Heart' ], [ 'check', 'Check' ], [ 'arrow-right', 'Arrow Right' ],
    [ 'arrow-left', 'Arrow Left' ], [ 'chevron-down', 'Chevron Down' ], [ 'chevron-up', 'Chevron Up' ],
    [ 'mail', 'Mail' ], [ 'phone', 'Phone' ], [ 'map-pin', 'Map Pin' ], [ 'calendar', 'Calendar' ],
    [ 'clock', 'Clock' ], [ 'user', 'User' ], [ 'users', 'Users' ], [ 'settings', 'Settings' ],
    [ 'search', 'Search' ], [ 'plus', 'Plus' ], [ 'minus', 'Minus' ], [ 'x', 'X' ], [ 'info', 'Info' ],
    [ 'alert-triangle', 'Alert Triangle' ], [ 'check-circle', 'Check Circle' ],
    [ 'external-link', 'External Link' ], [ 'download', 'Download' ],
].map( ( [ value, label ] ) => ( { value, label: __( label, 'cinderwell' ) } ) );

export const iconPaths = {
    star: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
    heart: '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
    check: '<polyline points="20 6 9 17 4 12"/>',
    'arrow-right': '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
    'arrow-left': '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
    'chevron-down': '<polyline points="6 9 12 15 18 9"/>',
    'chevron-up': '<polyline points="18 15 12 9 6 15"/>',
    mail: '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
    phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
    'map-pin': '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
    calendar: '<rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>',
    clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    user: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    settings: '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
    search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    plus: '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
    minus: '<line x1="5" y1="12" x2="19" y2="12"/>',
    x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    'alert-triangle': '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    'check-circle': '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
    'external-link': '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
    download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
};

const allowedSvgTags = new Set( [ 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon' ] );
const allowedSvgAttributes = new Set( [ 'd', 'points', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'width', 'height', 'rx', 'ry', 'cx', 'cy', 'r', 'transform', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'fill-rule', 'clip-rule', 'opacity' ] );

export const sanitizeCustomSvg = ( input ) => {
    if ( typeof window === 'undefined' || ! input?.trim() ) return { markup: '', viewBox: '0 0 24 24', error: __( 'Paste an SVG first.', 'cinderwell' ) };
    const document = new window.DOMParser().parseFromString( input, 'image/svg+xml' );
    const svg = document.documentElement;
    if ( svg.nodeName.toLowerCase() !== 'svg' || document.querySelector( 'parsererror' ) ) return { markup: '', viewBox: '0 0 24 24', error: __( 'That SVG could not be read.', 'cinderwell' ) };
    [ ...svg.querySelectorAll( '*' ) ].reverse().forEach( ( element ) => {
        if ( ! allowedSvgTags.has( element.nodeName.toLowerCase() ) ) {
            element.remove();
            return;
        }
        [ ...element.attributes ].forEach( ( attribute ) => {
            const name = attribute.name.toLowerCase();
            if ( ! allowedSvgAttributes.has( name ) || /url\s*\(/i.test( attribute.value ) ) element.removeAttribute( attribute.name );
            else if ( [ 'fill', 'stroke' ].includes( name ) && ! /^(none|currentcolor)$/i.test( attribute.value ) ) element.setAttribute( attribute.name, 'currentColor' );
        } );
    } );
    const viewBox = /^\s*-?\d*\.?\d+(?:\s+-?\d*\.?\d+){3}\s*$/.test( svg.getAttribute( 'viewBox' ) || '' ) ? svg.getAttribute( 'viewBox' ).trim() : '0 0 24 24';
    const rootPaintAttributes = [ 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'fill-rule', 'clip-rule', 'opacity' ];
    const paint = rootPaintAttributes.reduce( ( attributes, name ) => {
        const value = svg.getAttribute( name );
        if ( value && ! /url\s*\(/i.test( value ) ) attributes[ name ] = [ 'fill', 'stroke' ].includes( name ) && ! /^(none|currentcolor)$/i.test( value ) ? 'currentColor' : value;
        return attributes;
    }, {} );
    if ( ! ( 'fill' in paint ) && ! ( 'stroke' in paint ) && ! svg.querySelector( '[fill], [stroke]' ) ) {
        paint.fill = 'currentColor';
        paint.stroke = 'none';
    }
    const wrapper = document.createElementNS( 'http://www.w3.org/2000/svg', 'g' );
    Object.entries( paint ).forEach( ( [ name, value ] ) => wrapper.setAttribute( name, value ) );
    [ ...svg.children ].forEach( ( child ) => wrapper.appendChild( child.cloneNode( true ) ) );
    const markup = wrapper.hasChildNodes() ? new window.XMLSerializer().serializeToString( wrapper ) : '';
    return markup ? { markup, viewBox, error: '' } : { markup: '', viewBox, error: __( 'No supported SVG shapes were found.', 'cinderwell' ) };
};

export const IconGlyph = ( { icon = 'star', size = 48, customSvg = '', viewBox = '0 0 24 24' } ) => <svg xmlns="http://www.w3.org/2000/svg" width={ size } height={ size } viewBox={ customSvg ? viewBox : '0 0 24 24' } fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" data-cw-custom-icon={ customSvg ? 'true' : undefined } dangerouslySetInnerHTML={ { __html: customSvg || iconPaths[ icon ] || iconPaths.star } } />;
