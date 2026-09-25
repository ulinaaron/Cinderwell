import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { BackgroundControls, BlockIdentity, ColorTokenControl, HelpTooltip, LayoutControls, ResponsiveSegmentedControl, SectionToggles, SegmentedControl, SortableItemCard, TypographyControls, getBackgroundImageProps, getHeadingTagName, getResponsiveModifierClassName, getTextStyleClassName, getTypographyClassName, moveArrayItem } from '../../shared/inspector-controls';
import { ImageOverlayControls } from '../../shared/image-controls';
import { getMediaUrl } from '../../shared/media';
import metadata from './block.json';

let testimonialCounter = 1;
const columnOptions = [ 1, 2, 3, 4 ].map( ( value ) => ( { label: String( value ), value: String( value ) } ) );
const cardColorOptions = [
    { value: 'white', label: __( 'White', 'cinderwell' ), color: 'var(--cw-color-white, #ffffff)' },
    { value: 'light', label: __( 'Light', 'cinderwell' ), color: 'var(--cw-color-light, #f8f5ef)' },
    { value: 'dark', label: __( 'Dark', 'cinderwell' ), color: 'var(--cw-color-dark, #1a1a1a)' },
    { value: 'brand', label: __( 'Brand', 'cinderwell' ), color: 'var(--cw-color-brand, #b84c00)' },
];
const getResponsiveColumnsClassName = ( attributes ) => getResponsiveModifierClassName( 'cinderwell-testimonials', 'cols', { tablet: attributes.columnsTablet, mobile: attributes.columnsMobile } );
const getClassName = ( attributes ) => `cinderwell-testimonials cinderwell-testimonials--bg-${ attributes.background } cinderwell-testimonials--layout-${ attributes.layout } cinderwell-testimonials--cols-${ attributes.columns }${ getResponsiveColumnsClassName( attributes ) } cinderwell-testimonials--cards-${ attributes.cardColor } cinderwell-testimonials--photos-${ attributes.photoShape }${ getTypographyClassName( attributes ) }`;
const getImageDimensions = ( media ) => {
    const source = media?.sizes?.thumbnail || media?.sizes?.medium || media;
    return { imageWidth: Number( source?.width ) || 0, imageHeight: Number( source?.height ) || 0 };
};

const Testimonial = ( { attributes, item, index, updateItem, updateItemFields, editor = false } ) => {
    const image = item.imageUrl ? <img className="cinderwell-testimonials__photo" src={ item.imageUrl } alt={ item.imageAlt || '' } width={ item.imageWidth || undefined } height={ item.imageHeight || undefined } /> : null;
    const selectPhoto = ( media ) => updateItemFields( index, { image: media.id, imageUrl: getMediaUrl( media ), imageAlt: item.imageAlt || media.alt || '', ...getImageDimensions( media ) } );
    const removePhoto = () => updateItemFields( index, { image: 0, imageUrl: '', imageAlt: '', imageWidth: 0, imageHeight: 0 } );
    const portrait = editor ? <span className={ `cinderwell-testimonials__photo-control cw-image-control-host${ item.imageUrl ? '' : ' is-empty' }` }>
        { image || <span className="cw-image-control-placeholder">{ __( 'Photo', 'cinderwell' ) }</span> }
        <ImageOverlayControls imageId={ item.image } label={ sprintf( __( 'portrait for %s', 'cinderwell' ), item.name || sprintf( __( 'testimonial %d', 'cinderwell' ), index + 1 ) ) } onSelect={ selectPhoto } onRemove={ item.imageUrl ? removePhoto : undefined } />
    </span> : image;
    const quoteClass = `cinderwell-testimonials__quote${ getTextStyleClassName( attributes, 'quote' ) }`;
    const nameClass = `cinderwell-testimonials__name${ getTextStyleClassName( attributes, 'name' ) }`;
    const detailsClass = `cinderwell-testimonials__details${ getTextStyleClassName( attributes, 'details' ) }`;
    return <figure className="cinderwell-testimonials__item" aria-roledescription={ attributes.layout === 'carousel' ? 'slide' : undefined } aria-label={ attributes.layout === 'carousel' ? sprintf( __( '%1$d of %2$d', 'cinderwell' ), index + 1, attributes.testimonials.length ) : undefined }>
        <blockquote className={ quoteClass }>{ editor ? <RichText tagName="p" value={ item.quote } onChange={ ( value ) => updateItem( index, 'quote', value ) } placeholder={ __( 'Add the testimonial…', 'cinderwell' ) } /> : <RichText.Content tagName="p" value={ item.quote } /> }</blockquote>
        <figcaption className="cinderwell-testimonials__person">
            { portrait }
            <span className="cinderwell-testimonials__person-text">
                { editor ? <RichText tagName="cite" className={ nameClass } value={ item.name } onChange={ ( value ) => updateItem( index, 'name', value ) } placeholder={ __( 'Person’s name…', 'cinderwell' ) } allowedFormats={ [] } /> : item.name && <RichText.Content tagName="cite" className={ nameClass } value={ item.name } /> }
                { editor ? <span className={ detailsClass }><RichText tagName="span" value={ item.role } onChange={ ( value ) => updateItem( index, 'role', value ) } placeholder={ __( 'Role…', 'cinderwell' ) } allowedFormats={ [] } /><RichText tagName="span" value={ item.organization } onChange={ ( value ) => updateItem( index, 'organization', value ) } placeholder={ __( 'Organization…', 'cinderwell' ) } allowedFormats={ [] } /></span> : ( item.role || item.organization ) && <span className={ detailsClass }>{ item.role && <RichText.Content tagName="span" value={ item.role } /> }{ item.role && item.organization && <span aria-hidden="true">, </span> }{ item.organization && <RichText.Content tagName="span" value={ item.organization } /> }</span> }
            </span>
        </figcaption>
    </figure>;
};

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes, clientId } ) => {
        const testimonials = attributes.testimonials || [];
        const sections = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, footnote: attributes.showFootnote };
        const updateItem = ( index, field, value ) => setAttributes( { testimonials: testimonials.map( ( item, itemIndex ) => itemIndex === index ? { ...item, [ field ]: value } : item ) } );
        const updateItemFields = ( index, values ) => setAttributes( { testimonials: testimonials.map( ( item, itemIndex ) => itemIndex === index ? { ...item, ...values } : item ) } );
        const addItem = () => { testimonialCounter++; setAttributes( { testimonials: [ ...testimonials, { id: `testimonial-${ Date.now().toString( 36 ) }-${ testimonialCounter }`, quote: '', name: '', role: '', organization: '', image: 0, imageUrl: '', imageAlt: '', imageWidth: 0, imageHeight: 0 } ] } ); };
        const blockProps = useBlockProps( getBackgroundImageProps( { className: getClassName( attributes ) }, attributes ) );
        return <>
            <InspectorControls>
                <BlockIdentity icon="&#10077;" title={ __( 'Testimonials', 'cinderwell' ) } description={ __( 'Client stories in cards or a carousel', 'cinderwell' ) } />
                <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-content"><SectionToggles sections={ [ { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) }, { key: 'heading', label: __( 'Heading', 'cinderwell' ) }, { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) } ] } values={ sections } onChange={ ( value ) => setAttributes( { showEyebrow: value.eyebrow, showHeading: value.heading, showFootnote: value.footnote } ) } /></PanelBody>
                <PanelBody title={ __( 'Presentation', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-layout">
                    <SegmentedControl label={ __( 'Layout', 'cinderwell' ) } value={ attributes.layout } options={ [ { label: __( 'Cards', 'cinderwell' ), value: 'cards' }, { label: __( 'Carousel', 'cinderwell' ), value: 'carousel' } ] } onChange={ ( layout ) => setAttributes( { layout } ) } />
                    { attributes.layout === 'cards' ? <ResponsiveSegmentedControl label={ __( 'Columns', 'cinderwell' ) } values={ { desktop: attributes.columns, tablet: attributes.columnsTablet || 'auto', mobile: attributes.columnsMobile || 'auto' } } options={ ( breakpoint ) => breakpoint === 'desktop' ? columnOptions : [ { label: __( 'Auto', 'cinderwell' ), value: 'auto' }, ...columnOptions ] } autoHelp={ { tablet: __( 'Auto uses two columns when space allows.', 'cinderwell' ), mobile: __( 'Auto stacks testimonials.', 'cinderwell' ) } } onChange={ ( breakpoint, value ) => setAttributes( breakpoint === 'desktop' ? { columns: value } : { [ breakpoint === 'tablet' ? 'columnsTablet' : 'columnsMobile' ]: value === 'auto' ? '' : value } ) } /> : <><ToggleControl label={ __( 'Show previous and next buttons', 'cinderwell' ) } checked={ attributes.showArrows } onChange={ ( showArrows ) => setAttributes( { showArrows } ) } /><ToggleControl label={ __( 'Show slide indicators', 'cinderwell' ) } checked={ attributes.showIndicators } onChange={ ( showIndicators ) => setAttributes( { showIndicators } ) } /></> }
                    <SegmentedControl label={ __( 'Photo shape', 'cinderwell' ) } value={ attributes.photoShape } options={ [ { label: __( 'Circle', 'cinderwell' ), value: 'circle' }, { label: __( 'Rounded', 'cinderwell' ), value: 'rounded' }, { label: __( 'Square', 'cinderwell' ), value: 'square' } ] } onChange={ ( photoShape ) => setAttributes( { photoShape } ) } />
                </PanelBody>
                <PanelBody title={ `${ __( 'Testimonials', 'cinderwell' ) } (${ testimonials.length })` } initialOpen={ true } className="cw-panel cw-access-content">
                    { testimonials.map( ( item, index ) => <SortableItemCard key={ item.id || index } index={ index } listId={ `${ clientId }-testimonials` } label={ item.name || sprintf( __( 'Testimonial %d', 'cinderwell' ), index + 1 ) } onMove={ ( from, to ) => setAttributes( { testimonials: moveArrayItem( testimonials, from, to ) } ) } onRemove={ () => setAttributes( { testimonials: testimonials.filter( ( _, itemIndex ) => itemIndex !== index ) } ) }>
                        { item.imageUrl && <TextControl label={ <span className="cw-label-with-help"><span>{ __( 'Image Alt Text', 'cinderwell' ) }</span><HelpTooltip text={ __( 'Describe the image when it adds meaningful information. Leave blank when the portrait is decorative.', 'cinderwell' ) } /></span> } value={ item.imageAlt || '' } onChange={ ( imageAlt ) => updateItem( index, 'imageAlt', imageAlt ) } /> }
                    </SortableItemCard> ) }
                    <Button variant="secondary" className="cw-add-item" onClick={ addItem }>+ { __( 'Add testimonial', 'cinderwell' ) }</Button>
                </PanelBody>
                <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                <BackgroundControls value={ attributes.background } onChange={ ( background ) => setAttributes( { background } ) } attributes={ attributes } setAttributes={ setAttributes }><ColorTokenControl label={ __( 'Card color', 'cinderwell' ) } value={ attributes.cardColor } options={ cardColorOptions } onChange={ ( cardColor ) => setAttributes( { cardColor } ) } /></BackgroundControls>
                <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [ { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow }, { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading }, { key: 'quote', label: __( 'Quotes', 'cinderwell' ), enabled: testimonials.length > 0, background: attributes.cardColor }, { key: 'name', label: __( 'Names', 'cinderwell' ), enabled: testimonials.length > 0, background: attributes.cardColor }, { key: 'details', label: __( 'Person details', 'cinderwell' ), enabled: testimonials.length > 0, background: attributes.cardColor }, { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote } ] } />
            </InspectorControls>
            <section { ...blockProps }><div className="cinderwell-testimonials__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                { attributes.showEyebrow && <RichText tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( eyebrow ) => setAttributes( { eyebrow } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
                <div className="cinderwell-testimonials__viewport"><div className="cinderwell-testimonials__track">{ testimonials.map( ( item, index ) => <Testimonial key={ item.id || index } attributes={ attributes } item={ item } index={ index } updateItem={ updateItem } updateItemFields={ updateItemFields } editor /> ) }</div></div>
                { attributes.layout === 'carousel' && testimonials.length > 1 && ( attributes.showArrows || attributes.showIndicators ) && <div className="cinderwell-testimonials__controls" aria-hidden="true">{ attributes.showArrows && <button type="button" className="cinderwell-testimonials__arrow" disabled>&larr;</button> }{ attributes.showIndicators && <div className="cinderwell-testimonials__indicators">{ testimonials.map( ( item, index ) => <span key={ item.id || index } className={ `cinderwell-testimonials__indicator${ index === 0 ? ' is-active' : '' }` } /> ) }</div> }{ attributes.showArrows && <button type="button" className="cinderwell-testimonials__arrow" disabled>&rarr;</button> }</div> }
                { attributes.showFootnote && <RichText tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( footnote ) => setAttributes( { footnote } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ [] } /> }
            </div></section>
        </>;
    },
    save: ( { attributes } ) => {
        const testimonials = attributes.testimonials || [];
        const carousel = attributes.layout === 'carousel';
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: getClassName( attributes ), ...( carousel ? { 'data-cw-testimonials-carousel': true, role: 'region', 'aria-roledescription': 'carousel', 'aria-label': attributes.heading || __( 'Testimonials', 'cinderwell' ) } : {} ) }, attributes ) );
        return <section { ...blockProps }><div className="cinderwell-testimonials__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
            { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
            { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
            <div className="cinderwell-testimonials__viewport" role={ carousel ? 'group' : undefined } tabIndex={ carousel && testimonials.length > 1 ? 0 : undefined } aria-label={ carousel ? __( 'Testimonials', 'cinderwell' ) : undefined }><div className="cinderwell-testimonials__track">{ testimonials.map( ( item, index ) => <Testimonial key={ item.id || index } attributes={ attributes } item={ item } index={ index } /> ) }</div></div>
            { carousel && testimonials.length > 1 && ( attributes.showArrows || attributes.showIndicators ) && <div className="cinderwell-testimonials__controls">{ attributes.showArrows && <button type="button" className="cinderwell-testimonials__arrow cinderwell-testimonials__arrow--previous" data-cw-testimonials-previous><span aria-hidden="true">&larr;</span><span className="screen-reader-text">{ __( 'Previous testimonial', 'cinderwell' ) }</span></button> }{ attributes.showIndicators && <div className="cinderwell-testimonials__indicators" role="group" aria-label={ __( 'Choose a testimonial', 'cinderwell' ) }>{ testimonials.map( ( item, index ) => <button type="button" key={ item.id || index } className={ `cinderwell-testimonials__indicator${ index === 0 ? ' is-active' : '' }` } data-cw-testimonials-indicator={ index } aria-label={ sprintf( __( 'Go to testimonial %d', 'cinderwell' ), index + 1 ) } aria-current={ index === 0 ? 'true' : undefined } /> ) }</div> }{ attributes.showArrows && <button type="button" className="cinderwell-testimonials__arrow cinderwell-testimonials__arrow--next" data-cw-testimonials-next><span aria-hidden="true">&rarr;</span><span className="screen-reader-text">{ __( 'Next testimonial', 'cinderwell' ) }</span></button> }</div> }
            { carousel && <span className="screen-reader-text" data-cw-testimonials-status aria-live="polite" aria-atomic="true" /> }
            { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
        </div></section>;
    },
} );
