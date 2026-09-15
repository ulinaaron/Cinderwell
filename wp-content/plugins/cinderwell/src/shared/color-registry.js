import { useEffect, useMemo, useState } from '@wordpress/element';

export const TOKEN_CHANGE_EVENT = 'cinderwell:tokens-changed';
export const WCAG_AA_CONTRAST = 4.5;

const clampChannel = ( value ) => Math.max( 0, Math.min( 255, value ) );

const parseCssColor = ( value ) => {
    const color = String( value || '' ).trim().toLowerCase();
    const hex = color.match( /^#([0-9a-f]{3}|[0-9a-f]{6})$/i );
    if ( hex ) {
        const expanded = hex[ 1 ].length === 3
            ? hex[ 1 ].split( '' ).map( ( channel ) => channel + channel ).join( '' )
            : hex[ 1 ];
        return [ 0, 2, 4 ].map( ( offset ) => parseInt( expanded.slice( offset, offset + 2 ), 16 ) );
    }

    const rgb = color.match( /^rgba?\(\s*([\d.]+)%?[,\s]+([\d.]+)%?[,\s]+([\d.]+)%?/ );
    if ( rgb ) {
        const percentages = color.includes( '%' );
        return rgb.slice( 1, 4 ).map( ( channel ) => clampChannel( Number( channel ) * ( percentages ? 2.55 : 1 ) ) );
    }

    const srgb = color.match( /^color\(srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/ );
    if ( srgb ) {
        return srgb.slice( 1, 4 ).map( ( channel ) => clampChannel( Number( channel ) * 255 ) );
    }

    return null;
};

const resolveTokenColor = ( token, fallback = '' ) => {
    const direct = parseCssColor( token.value );
    if ( direct ) return direct;
    if ( typeof document === 'undefined' || ! document.body || ! token.cssVariable ) return parseCssColor( fallback );

    const probe = document.createElement( 'span' );
    probe.style.cssText = 'position:fixed;visibility:hidden;pointer-events:none;';
    probe.style.color = `var(${ token.cssVariable })`;
    document.body.appendChild( probe );
    const resolved = parseCssColor( window.getComputedStyle( probe ).color );
    probe.remove();
    return resolved || parseCssColor( fallback );
};

const relativeLuminance = ( rgb ) => {
    const channels = rgb.map( ( channel ) => {
        const normalized = channel / 255;
        return normalized <= 0.04045
            ? normalized / 12.92
            : ( ( normalized + 0.055 ) / 1.055 ) ** 2.4;
    } );
    return ( 0.2126 * channels[ 0 ] ) + ( 0.7152 * channels[ 1 ] ) + ( 0.0722 * channels[ 2 ] );
};

export const getContrastRatio = ( first, second ) => {
    if ( ! first || ! second ) return 0;
    const firstLuminance = relativeLuminance( first );
    const secondLuminance = relativeLuminance( second );
    return ( Math.max( firstLuminance, secondLuminance ) + 0.05 )
        / ( Math.min( firstLuminance, secondLuminance ) + 0.05 );
};

const normalizeSlug = ( value ) => String( value || '' ).toLowerCase().replace( /[^a-z0-9-]/g, '' );
const rgbToCss = ( rgb ) => `rgb(${ rgb.map( ( channel ) => Math.round( channel ) ).join( ' ' ) })`;

export const getColorRegistry = ( tokens ) => {
    const serverRegistry = window.cinderwellEditorSettings?.colorRegistry || [];
    const serverColors = new Map( serverRegistry.map( ( color ) => [ color.key, color.resolvedColor ] ) );

    return tokens.filter( ( token ) => token.palette?.slug && token.palette?.contexts?.length ).map( ( token ) => {
        const resolvedColor = resolveTokenColor( token, serverColors.get( token.key ) );
        return {
            key: token.key,
            slug: normalizeSlug( token.palette.slug ),
            label: token.label,
            cssVariable: token.cssVariable,
            value: token.value,
            contexts: token.palette.contexts,
            automaticForeground: Boolean( token.palette.automatic_foreground ),
            resolvedColor,
            resolvedCss: resolvedColor ? rgbToCss( resolvedColor ) : '',
        };
    } ).filter( ( color ) => color.slug );
};

export const useColorRegistry = () => {
    const [ tokens, setTokens ] = useState( () => window.cinderwellEditorSettings?.tokens || [] );

    useEffect( () => {
        const update = ( event ) => setTokens( event.detail?.tokens || window.cinderwellEditorSettings?.tokens || [] );
        window.addEventListener( TOKEN_CHANGE_EVENT, update );
        return () => window.removeEventListener( TOKEN_CHANGE_EVENT, update );
    }, [] );

    return useMemo( () => getColorRegistry( tokens ), [ tokens ] );
};

export const getPaletteOptions = ( registry, context ) => registry
    .filter( ( color ) => color.contexts.includes( context ) && color.resolvedColor )
    .map( ( color ) => ( {
        slug: color.slug,
        label: color.label,
        color: `var(${ color.cssVariable }, ${ color.value })`,
        fallback: color.value,
    } ) );

export const getContrastSafeTextOptions = ( registry, backgroundSlug ) => {
    const background = registry.find( ( color ) => color.slug === backgroundSlug && color.contexts.includes( 'background' ) );
    if ( ! background?.resolvedColor ) return [];

    return registry.filter( ( color ) => (
        color.contexts.includes( 'text' )
        && color.resolvedColor
        && getContrastRatio( background.resolvedColor, color.resolvedColor ) >= WCAG_AA_CONTRAST
    ) );
};

const getAutomaticForeground = ( registry, background ) => registry
    .filter( ( color ) => color.automaticForeground && color.contexts.includes( 'text' ) && color.resolvedColor )
    .map( ( color ) => ( { color, ratio: getContrastRatio( background.resolvedColor, color.resolvedColor ) } ) )
    .sort( ( first, second ) => second.ratio - first.ratio )[ 0 ]?.color;

export const getColorRegistryCss = ( registry ) => {
    const rules = [];

    registry.filter( ( color ) => color.contexts.includes( 'text' ) && color.resolvedCss ).forEach( ( color ) => {
        rules.push( `.cinderwell-text-color-${ color.slug }{color:var(${ color.cssVariable },${ color.resolvedCss })!important;}` );
    } );

    registry.filter( ( color ) => color.contexts.includes( 'background' ) && color.resolvedColor ).forEach( ( background ) => {
        const foreground = getAutomaticForeground( registry, background );
        if ( ! foreground ) return;

        const selector = `[class*="cinderwell-"][class*="--bg-${ background.slug }"]`;
        const backgroundValue = `var(${ background.cssVariable },${ background.resolvedCss })`;
        const foregroundValue = `var(${ foreground.cssVariable },${ foreground.resolvedCss })`;
        rules.push( `${ selector }{--cw-surface-background:${ backgroundValue };--cw-surface-foreground:${ foregroundValue };--cw-tabs-accent:${ foregroundValue };--cw-tabs-accent-contrast:${ backgroundValue };background-color:${ backgroundValue };color:${ foregroundValue };}` );

        const unsafeTextSelectors = registry
            .filter( ( color ) => color.contexts.includes( 'text' ) && color.resolvedColor )
            .filter( ( color ) => getContrastRatio( background.resolvedColor, color.resolvedColor ) < WCAG_AA_CONTRAST )
            .map( ( color ) => `.cinderwell-text-color-${ color.slug }` );
        if ( unsafeTextSelectors.length ) {
            const unsafe = unsafeTextSelectors.join( ',' );
            rules.push( `${ selector }:is(${ unsafe }),${ selector } :is(${ unsafe }){color:${ foregroundValue }!important;}` );
        }
    } );

    return rules.join( '' );
};

export const applyColorRegistryPreview = ( tokens ) => {
    const css = getColorRegistryCss( getColorRegistry( tokens ) );
    const editorDocument = document.querySelector( 'iframe[name="editor-canvas"]' )?.contentDocument;

    [ document, editorDocument ].filter( Boolean ).forEach( ( targetDocument ) => {
        let style = targetDocument.getElementById( 'cinderwell-live-palette' );
        if ( ! style ) {
            style = targetDocument.createElement( 'style' );
            style.id = 'cinderwell-live-palette';
            targetDocument.head.appendChild( style );
        }
        style.textContent = css;
    } );
};
