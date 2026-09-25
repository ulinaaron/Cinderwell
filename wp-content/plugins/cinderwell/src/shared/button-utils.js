import { iconPaths } from './icon-library';

export const normalizePhoneHref = ( value = '' ) => {
    const raw = String( value ).trim().replace( /^tel:\s*/i, '' );
    if ( ! raw ) return '';

    const extensionMatch = raw.match( /^(.*?)(?:\s*(?:ext(?:ension)?\.?|x)\s*[:.]?\s*(\d+))\s*$/i );
    const numberPart = extensionMatch ? extensionMatch[ 1 ] : raw;
    const extension = extensionMatch?.[ 2 ] || '';
    const hasInternationalPrefix = /^\s*\+/.test( numberPart );
    const digits = numberPart.replace( /\D/g, '' );

    if ( ! digits ) return '';
    return `tel:${ hasInternationalPrefix ? '+' : '' }${ digits }${ extension ? `;ext=${ extension }` : '' }`;
};

export const normalizeEmailHref = ( value = '' ) => {
    const address = String( value ).trim().replace( /^mailto:\s*/i, '' );
    if ( ! /^[^\s@]+@[^\s@]+$/.test( address ) ) return '';
    return `mailto:${ address }`;
};

export const getButtonHref = ( {
    destinationType = 'link',
    url = '',
    phoneNumber = '',
    emailAddress = '',
} = {} ) => {
    if ( destinationType === 'phone' ) return normalizePhoneHref( phoneNumber );
    if ( destinationType === 'email' ) return normalizeEmailHref( emailAddress );
    return url;
};

const getIconDataUri = ( icon ) => {
    const paths = iconPaths[ icon ];
    if ( ! paths ) return '';
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ paths }</svg>`;
    return `url("data:image/svg+xml,${ encodeURIComponent( svg ) }")`;
};

export const getButtonIconProps = ( icon = '', iconPosition = 'before' ) => {
    const iconUrl = getIconDataUri( icon );
    if ( ! iconUrl ) return {};

    return {
        className: `btn--has-icon btn--icon-${ iconPosition === 'after' ? 'after' : 'before' }`,
        style: { '--cw-button-icon': iconUrl },
    };
};
