import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, MediaUpload, MediaUploadCheck, InspectorControls, RichText } from '@wordpress/block-editor';
import { Button, PanelBody, Popover } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, getBackgroundImageProps, TypographyControls, getTypographyClassName, getTextStyleClassName, getHeadingTagName } from '../../shared/inspector-controls';
import { ImageOverlayControls, ImageSettingsFields } from '../../shared/image-controls';
import { getImageClassName, getMediaUrl } from '../../shared/media';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( getBackgroundImageProps( { className: `cinderwell-gallery cinderwell-gallery--bg-${ attributes.background }${ getTypographyClassName( attributes ) }` }, attributes ) );
        const images = attributes.images || [];
        const [ editingIndex, setEditingIndex ] = useState( null );
        const [ popoverAnchor, setPopoverAnchor ] = useState( null );
        const secs = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, caption: attributes.showCaption, footnote: attributes.showFootnote };
        const onSecs = ( v ) => setAttributes( { showEyebrow: v.eyebrow, showHeading: v.heading, showCaption: v.caption, showFootnote: v.footnote } );
        const onSelectImages = ( newImages ) => setAttributes( { images: ( Array.isArray( newImages ) ? newImages : [ newImages ] ).map( ( img ) => ( { id: img.id, url: getMediaUrl( img ), alt: img.alt || '', fit: 'auto', position: 'center', aspect: 'auto' } ) ) } );
        const updateImage = ( i, field, value ) => setAttributes( { images: images.map( ( img, idx ) => idx === i ? { ...img, [ field ]: value } : img ) } );
        const replaceImg = ( i, media ) => setAttributes( { images: images.map( ( img, idx ) => idx === i ? { ...img, id: media.id, url: getMediaUrl( media ), alt: media.alt || img.alt || '' } : img ) } );
        const closeEditor = () => { setEditingIndex( null ); setPopoverAnchor( null ); };
        const removeImg = ( i ) => { setAttributes( { images: images.filter( ( _, idx ) => idx !== i ) } ); closeEditor(); };
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="&#9871;" title={ __( 'Gallery', 'cinderwell' ) } description={ __( 'Image gallery grid', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
                        <SectionToggles sections={ [
                            { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                            { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                            { key: 'caption', label: __( 'Caption', 'cinderwell' ) },
                            { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                        ] } values={ secs } onChange={ onSecs } />
                    </PanelBody>
                    <PanelBody title={ `${ __( 'Images', 'cinderwell' ) } (${ images.length })` } initialOpen={ true } className="cw-panel">
                        <div className="cw-thumb-strip">
                            { images.map( ( img, i ) => (
                                <button
                                    type="button"
                                    key={ img.id || i }
                                    className={ `cw-thumb-tile${ editingIndex === i ? ' is-selected' : '' }` }
                                    onClick={ ( event ) => { setEditingIndex( i ); setPopoverAnchor( event.currentTarget ); } }
                                    aria-label={ `${ __( 'Edit image', 'cinderwell' ) } ${ i + 1 }` }
                                >
                                    <img src={ img.url } alt="" />
                                    { ! img.alt && <span className="cw-thumb-tile__badge" title={ __( 'No alt text; image will be treated as decorative', 'cinderwell' ) }>!</span> }
                                </button>
                            ) ) }
                            <MediaUploadCheck>
                                <MediaUpload
                                    onSelect={ onSelectImages }
                                    allowedTypes={ [ 'image' ] }
                                    multiple
                                    gallery
                                    value={ images.map( ( img ) => img.id ) }
                                    render={ ( { open } ) => (
                                        <button type="button" className="cw-thumb-add" onClick={ open } aria-label={ __( 'Select images', 'cinderwell' ) }>+</button>
                                    ) }
                                />
                            </MediaUploadCheck>
                        </div>
                        <p className="cw-hint">{ __( 'Select a thumbnail to edit its alt text. Replace or remove images in the canvas.', 'cinderwell' ) }</p>
                        { editingIndex !== null && images[ editingIndex ] && (
                            <Popover anchor={ popoverAnchor } onClose={ closeEditor } placement="left-start" offset={ 28 } flip={ false } shift className="cw-popover">
                                <div className="cw-popover__inner">
                                    <div className="cw-popover__head">
                                        <span className="cw-popover__title">{ `${ __( 'Edit image', 'cinderwell' ) } ${ editingIndex + 1 }` }</span>
                                        <button type="button" className="cw-popover__close" onClick={ closeEditor } aria-label={ __( 'Close', 'cinderwell' ) }>&times;</button>
                                    </div>
                                    <img className="cw-popover__preview" src={ images[ editingIndex ].url } alt="" />
                                    <ImageSettingsFields
                                        alt={ images[ editingIndex ].alt }
                                        fit={ images[ editingIndex ].fit }
                                        position={ images[ editingIndex ].position }
                                        aspect={ images[ editingIndex ].aspect }
                                        onAltChange={ ( value ) => updateImage( editingIndex, 'alt', value ) }
                                        onFitChange={ ( value ) => updateImage( editingIndex, 'fit', value ) }
                                        onPositionChange={ ( value ) => updateImage( editingIndex, 'position', value ) }
                                        onAspectChange={ ( value ) => updateImage( editingIndex, 'aspect', value ) }
                                    />
                                    <Button variant="secondary" size="compact" onClick={ closeEditor }>{ __( 'Done', 'cinderwell' ) }</Button>
                                </div>
                            </Popover>
                        ) }
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'caption', label: __( 'Caption', 'cinderwell' ), enabled: attributes.showCaption },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <div { ...blockProps }>
                    <div className="cinderwell-gallery__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( value ) => setAttributes( { eyebrow: value } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( value ) => setAttributes( { heading: value } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        <div className="cinderwell-gallery__grid">
                            { images.map( ( img, i ) => (
                                <figure key={ i } className="cinderwell-gallery__item cw-image-control-host">
                                    <img className={ getImageClassName( img.fit, img.position, img.aspect ).trim() || undefined } src={ img.url } alt={ img.alt } />
                                    <ImageOverlayControls
                                        imageId={ img.id }
                                        label={ `${ __( 'gallery image', 'cinderwell' ) } ${ i + 1 }` }
                                        onSelect={ ( media ) => replaceImg( i, media ) }
                                        onRemove={ () => removeImg( i ) }
                                    />
                                </figure>
                            ) ) }
                        </div>
                        { attributes.showCaption && <RichText tagName="p" identifier="caption" className={ `cinderwell-caption${ getTextStyleClassName( attributes, 'caption' ) }` } value={ attributes.caption } onChange={ ( value ) => setAttributes( { caption: value } ) } placeholder={ __( 'Caption…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( value ) => setAttributes( { footnote: value } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ [] } /> }
                    </div>
                </div>
            </>
        );
    },
    save: ( { attributes } ) => {
        const images = attributes.images || [];
        const schema = { '@context': 'https://schema.org', '@type': 'ImageGallery', 'name': attributes.heading, 'image': images.map( ( img ) => img.url ) };
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-gallery cinderwell-gallery--bg-${ attributes.background }${ getTypographyClassName( attributes ) }`, 'data-cw-schema': JSON.stringify( schema ) }, attributes ) );
        return (
            <div { ...blockProps }>
                <div className="cinderwell-gallery__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                    { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                    <div className="cinderwell-gallery__grid">
                        { images.map( ( img, i ) => <figure key={ i } className="cinderwell-gallery__item"><img className={ getImageClassName( img.fit, img.position, img.aspect ).trim() || undefined } src={ img.url } alt={ img.alt } /></figure> ) }
                    </div>
                    { attributes.showCaption && attributes.caption && <RichText.Content tagName="p" className={ `cinderwell-caption${ getTextStyleClassName( attributes, 'caption' ) }` } value={ attributes.caption } /> }
                    { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                </div>
            </div>
        );
    },
} );
