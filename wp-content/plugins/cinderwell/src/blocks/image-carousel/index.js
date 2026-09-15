import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, MediaUploadCheck, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, Popover, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
    BackgroundControls,
    BlockIdentity,
    LayoutControls,
    SectionToggles,
    TypographyControls,
    getBackgroundImageProps,
    getHeadingTagName,
    getTextStyleClassName,
    getTypographyClassName,
} from '../../shared/inspector-controls';
import { ImageOverlayControls, ImageSettingsFields } from '../../shared/image-controls';
import { getImageClassName, getMediaUrl } from '../../shared/media';
import metadata from './block.json';

const imageAspectOptions = [
    { value: 'auto', label: __( 'Original', 'cinderwell' ) },
    { value: 'wide', label: __( 'Wide', 'cinderwell' ) },
    { value: 'landscape', label: __( 'Landscape', 'cinderwell' ) },
    { value: 'square', label: __( 'Square', 'cinderwell' ) },
    { value: 'portrait', label: __( 'Portrait', 'cinderwell' ) },
];

const imageFitOptions = [
    { value: 'cover', label: __( 'Cover', 'cinderwell' ) },
    { value: 'contain', label: __( 'Contain', 'cinderwell' ) },
];

const getCaption = ( media ) => {
    if ( typeof media?.caption === 'string' ) return media.caption;
    return media?.caption?.raw || '';
};

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const images = attributes.images || [];
        const [ editingIndex, setEditingIndex ] = useState( null );
        const [ popoverAnchor, setPopoverAnchor ] = useState( null );
        const blockProps = useBlockProps( getBackgroundImageProps( {
            className: `cinderwell-image-carousel cinderwell-image-carousel--bg-${ attributes.background }${ getTypographyClassName( attributes ) }`,
        }, attributes ) );
        const sections = {
            eyebrow: attributes.showEyebrow,
            heading: attributes.showHeading,
            footnote: attributes.showFootnote,
        };

        const onSelectImages = ( selection ) => {
            const selected = Array.isArray( selection ) ? selection : [ selection ];
            setAttributes( {
                images: selected.map( ( media ) => {
                    const existing = images.find( ( image ) => image.id === media.id );
                    return {
                        id: media.id,
                        url: getMediaUrl( media ),
                        alt: existing?.alt ?? media.alt ?? '',
                        caption: existing?.caption ?? getCaption( media ),
                        position: existing?.position || 'center',
                    };
                } ),
            } );
        };
        const updateImage = ( index, field, value ) => setAttributes( {
            images: images.map( ( image, imageIndex ) => imageIndex === index ? { ...image, [ field ]: value } : image ),
        } );
        const replaceImage = ( index, media ) => setAttributes( {
            images: images.map( ( image, imageIndex ) => imageIndex === index ? {
                ...image,
                id: media.id,
                url: getMediaUrl( media ),
                alt: media.alt || image.alt || '',
            } : image ),
        } );
        const closeEditor = () => {
            setEditingIndex( null );
            setPopoverAnchor( null );
        };
        const removeImage = ( index ) => {
            setAttributes( { images: images.filter( ( image, imageIndex ) => imageIndex !== index ) } );
            closeEditor();
        };

        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="&#9638;" title={ __( 'Image Carousel', 'cinderwell' ) } description={ __( 'Accessible image slider', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-layout">
                        <SectionToggles
                            sections={ [
                                { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                                { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                                { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                            ] }
                            values={ sections }
                            onChange={ ( value ) => setAttributes( {
                                showEyebrow: value.eyebrow,
                                showHeading: value.heading,
                                showFootnote: value.footnote,
                            } ) }
                        />
                    </PanelBody>
                    <PanelBody title={ `${ __( 'Images', 'cinderwell' ) } (${ images.length })` } initialOpen={ true } className="cw-panel cw-access-media">
                        <div className="cw-thumb-strip">
                            { images.map( ( image, index ) => (
                                <button
                                    type="button"
                                    key={ image.id || index }
                                    className={ `cw-thumb-tile${ editingIndex === index ? ' is-selected' : '' }` }
                                    onClick={ ( event ) => {
                                        setEditingIndex( index );
                                        setPopoverAnchor( event.currentTarget );
                                    } }
                                    aria-label={ sprintf( __( 'Edit slide %d', 'cinderwell' ), index + 1 ) }
                                >
                                    <img src={ image.url } alt="" />
                                    { ! image.alt && <span className="cw-thumb-tile__badge" title={ __( 'No alt text; image will be treated as decorative', 'cinderwell' ) }>!</span> }
                                </button>
                            ) ) }
                            <MediaUploadCheck>
                                <MediaUpload
                                    onSelect={ onSelectImages }
                                    allowedTypes={ [ 'image' ] }
                                    multiple
                                    gallery
                                    value={ images.map( ( image ) => image.id ) }
                                    render={ ( { open } ) => (
                                        <button type="button" className="cw-thumb-add" onClick={ open } aria-label={ __( 'Select and reorder carousel images', 'cinderwell' ) }>+</button>
                                    ) }
                                />
                            </MediaUploadCheck>
                        </div>
                        <p className="cw-hint">{ __( 'Use the media gallery to add or reorder slides. Select a thumbnail to edit its content.', 'cinderwell' ) }</p>
                        { editingIndex !== null && images[ editingIndex ] && (
                            <Popover anchor={ popoverAnchor } onClose={ closeEditor } placement="left-start" offset={ 28 } flip={ false } shift className="cw-popover">
                                <div className="cw-popover__inner">
                                    <div className="cw-popover__head">
                                        <span className="cw-popover__title">{ sprintf( __( 'Edit slide %d', 'cinderwell' ), editingIndex + 1 ) }</span>
                                        <button type="button" className="cw-popover__close" onClick={ closeEditor } aria-label={ __( 'Close', 'cinderwell' ) }>&times;</button>
                                    </div>
                                    <img className="cw-popover__preview" src={ images[ editingIndex ].url } alt="" />
                                    <ImageSettingsFields
                                        alt={ images[ editingIndex ].alt }
                                        fit={ attributes.imageFit }
                                        position={ images[ editingIndex ].position }
                                        showAspect={ false }
                                        onAltChange={ ( value ) => updateImage( editingIndex, 'alt', value ) }
                                        onFitChange={ ( value ) => setAttributes( { imageFit: value } ) }
                                        onPositionChange={ ( value ) => updateImage( editingIndex, 'position', value ) }
                                    />
                                    <TextControl
                                        label={ __( 'Caption', 'cinderwell' ) }
                                        value={ images[ editingIndex ].caption || '' }
                                        onChange={ ( value ) => updateImage( editingIndex, 'caption', value ) }
                                    />
                                    <Button variant="secondary" size="compact" onClick={ closeEditor }>{ __( 'Done', 'cinderwell' ) }</Button>
                                </div>
                            </Popover>
                        ) }
                    </PanelBody>
                    <PanelBody title={ __( 'Carousel', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-layout">
                        <ToggleControl label={ __( 'Show previous and next buttons', 'cinderwell' ) } checked={ attributes.showArrows } onChange={ ( value ) => setAttributes( { showArrows: value } ) } />
                        <ToggleControl label={ __( 'Show slide indicators', 'cinderwell' ) } checked={ attributes.showIndicators } onChange={ ( value ) => setAttributes( { showIndicators: value } ) } />
                        <SelectControl label={ __( 'Image aspect ratio', 'cinderwell' ) } value={ attributes.imageAspect } options={ imageAspectOptions } onChange={ ( value ) => setAttributes( { imageAspect: value } ) } />
                        <SelectControl label={ __( 'Image fit', 'cinderwell' ) } value={ attributes.imageFit } options={ imageFitOptions } onChange={ ( value ) => setAttributes( { imageFit: value } ) } />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( value ) => setAttributes( { background: value } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <section { ...blockProps }>
                    <div className="cinderwell-image-carousel__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( value ) => setAttributes( { eyebrow: value } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( value ) => setAttributes( { heading: value } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { ! images.length ? (
                            <MediaUploadCheck>
                                <MediaUpload
                                    onSelect={ onSelectImages }
                                    allowedTypes={ [ 'image' ] }
                                    multiple
                                    gallery
                                    render={ ( { open } ) => <Button variant="primary" onClick={ open }>{ __( 'Select carousel images', 'cinderwell' ) }</Button> }
                                />
                            </MediaUploadCheck>
                        ) : (
                            <div className="cinderwell-image-carousel__viewport">
                                <div className="cinderwell-image-carousel__track">
                                    { images.map( ( image, index ) => (
                                        <figure key={ image.id || index } className="cinderwell-image-carousel__slide cw-image-control-host">
                                            <img className={ getImageClassName( attributes.imageFit, image.position, attributes.imageAspect ).trim() || undefined } src={ image.url } alt={ image.alt } />
                                            { image.caption && <figcaption className="cinderwell-image-carousel__caption">{ image.caption }</figcaption> }
                                            <ImageOverlayControls imageId={ image.id } label={ sprintf( __( 'slide %d image', 'cinderwell' ), index + 1 ) } onSelect={ ( media ) => replaceImage( index, media ) } onRemove={ () => removeImage( index ) } />
                                        </figure>
                                    ) ) }
                                </div>
                            </div>
                        ) }
                        { images.length > 1 && ( attributes.showArrows || attributes.showIndicators ) && (
                            <div className="cinderwell-image-carousel__controls" aria-hidden="true">
                                { attributes.showArrows && <button type="button" className="cinderwell-image-carousel__arrow" disabled>&larr;</button> }
                                { attributes.showIndicators && <div className="cinderwell-image-carousel__indicators">{ images.map( ( image, index ) => <span key={ image.id || index } className={ `cinderwell-image-carousel__indicator${ index === 0 ? ' is-active' : '' }` } /> ) }</div> }
                                { attributes.showArrows && <button type="button" className="cinderwell-image-carousel__arrow" disabled>&rarr;</button> }
                            </div>
                        ) }
                        { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( value ) => setAttributes( { footnote: value } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ [] } /> }
                    </div>
                </section>
            </>
        );
    },
    save: ( { attributes } ) => {
        const images = attributes.images || [];
        const schema = {
            '@context': 'https://schema.org',
            '@type': 'ImageGallery',
            name: attributes.heading || __( 'Image carousel', 'cinderwell' ),
            image: images.map( ( image ) => image.url ),
        };
        const blockProps = useBlockProps.save( getBackgroundImageProps( {
            className: `cinderwell-image-carousel cinderwell-image-carousel--bg-${ attributes.background }${ getTypographyClassName( attributes ) }`,
            'data-cw-carousel': true,
            'data-cw-schema': JSON.stringify( schema ),
            role: 'region',
            'aria-roledescription': 'carousel',
            'aria-label': attributes.heading || __( 'Image carousel', 'cinderwell' ),
        }, attributes ) );

        return (
            <section { ...blockProps }>
                <div className="cinderwell-image-carousel__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                    { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                    <div className="cinderwell-image-carousel__viewport" role="group" tabIndex={ images.length > 1 ? 0 : undefined } aria-label={ __( 'Carousel slides', 'cinderwell' ) }>
                        <div className="cinderwell-image-carousel__track">
                            { images.map( ( image, index ) => (
                                <figure
                                    key={ image.id || index }
                                    className="cinderwell-image-carousel__slide"
                                    aria-roledescription="slide"
                                    aria-label={ sprintf( __( '%1$d of %2$d', 'cinderwell' ), index + 1, images.length ) }
                                >
                                    <img className={ getImageClassName( attributes.imageFit, image.position, attributes.imageAspect ).trim() || undefined } src={ image.url } alt={ image.alt } />
                                    { image.caption && <figcaption className="cinderwell-image-carousel__caption">{ image.caption }</figcaption> }
                                </figure>
                            ) ) }
                        </div>
                    </div>
                    { images.length > 1 && ( attributes.showArrows || attributes.showIndicators ) && (
                        <div className="cinderwell-image-carousel__controls">
                            { attributes.showArrows && (
                                <button type="button" className="cinderwell-image-carousel__arrow cinderwell-image-carousel__arrow--previous" data-cw-carousel-previous>
                                    <span aria-hidden="true">&larr;</span><span className="screen-reader-text">{ __( 'Previous slide', 'cinderwell' ) }</span>
                                </button>
                            ) }
                            { attributes.showIndicators && (
                                <div className="cinderwell-image-carousel__indicators" role="group" aria-label={ __( 'Choose a slide', 'cinderwell' ) }>
                                    { images.map( ( image, index ) => (
                                        <button
                                            type="button"
                                            key={ image.id || index }
                                            className={ `cinderwell-image-carousel__indicator${ index === 0 ? ' is-active' : '' }` }
                                            data-cw-carousel-indicator={ index }
                                            aria-label={ sprintf( __( 'Go to slide %d', 'cinderwell' ), index + 1 ) }
                                            aria-current={ index === 0 ? 'true' : undefined }
                                        />
                                    ) ) }
                                </div>
                            ) }
                            { attributes.showArrows && (
                                <button type="button" className="cinderwell-image-carousel__arrow cinderwell-image-carousel__arrow--next" data-cw-carousel-next>
                                    <span aria-hidden="true">&rarr;</span><span className="screen-reader-text">{ __( 'Next slide', 'cinderwell' ) }</span>
                                </button>
                            ) }
                        </div>
                    ) }
                    <span className="screen-reader-text" data-cw-carousel-status aria-live="polite" aria-atomic="true" />
                    { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                </div>
            </section>
        );
    },
} );
