import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, getBackgroundImageProps, TypographyControls, getTypographyClassName, getTextStyleClassName, getHeadingTagName, SegmentedControl, ButtonRepeater, ButtonSave } from '../../shared/inspector-controls';
import { ImageOverlayControls, ImageSettingsControl } from '../../shared/image-controls';
import { getImageClassName, getMediaUrl } from '../../shared/media';
import { ALLOWED_INLINE_FORMATS, ALLOWED_BODY_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const imageMedia = useSelect( ( select ) => attributes.image > 0 ? select( 'core' ).getMedia( attributes.image ) : null, [ attributes.image ] );
        const imageUrl = attributes.imageUrl || getMediaUrl( imageMedia );
        const blockProps = useBlockProps( getBackgroundImageProps( {
            className: `cinderwell-image-text cinderwell-image-text--bg-${ attributes.background } cinderwell-image-text--align-${ attributes.alignment }${ getTypographyClassName( attributes ) }`,
        }, attributes ) );
        const secs = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, image: attributes.showImage, caption: attributes.showCaption, body: attributes.showBody, footnote: attributes.showFootnote };
        const onSecs = ( v ) => setAttributes( { showEyebrow: v.eyebrow, showHeading: v.heading, showImage: v.image, showCaption: v.caption, showBody: v.body, showFootnote: v.footnote } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="&#9713;" title={ __( 'Image + Text', 'cinderwell' ) } description={ __( 'Side-by-side image and text', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cinderwell-content-panel">
                        <SectionToggles sections={ [
                            { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                            { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                            { key: 'image', label: __( 'Image', 'cinderwell' ) },
                            { key: 'caption', label: __( 'Caption', 'cinderwell' ) },
                            { key: 'body', label: __( 'Body', 'cinderwell' ) },
                            { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                        ] } values={ secs } onChange={ onSecs } />
                    </PanelBody>
                    { attributes.showImage && (
                        <PanelBody title={ __( 'Media', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-media">
                            <ImageSettingsControl
                                alt={ attributes.imageAlt }
                                fit={ attributes.imageFit }
                                position={ attributes.imagePosition }
                                aspect={ attributes.imageAspect }
                                label={ __( 'Image settings', 'cinderwell' ) }
                                onAltChange={ ( imageAlt ) => setAttributes( { imageAlt } ) }
                                onFitChange={ ( imageFit ) => setAttributes( { imageFit } ) }
                                onPositionChange={ ( imagePosition ) => setAttributes( { imagePosition } ) }
                                onAspectChange={ ( imageAspect ) => setAttributes( { imageAspect } ) }
                            />
                        </PanelBody>
                    ) }
                    <PanelBody title={ __( 'Buttons', 'cinderwell' ) } initialOpen={ false } className="cinderwell-buttons-panel">
                        <ButtonRepeater buttons={ attributes.buttons } onChange={ ( b ) => setAttributes( { buttons: b } ) } />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <PanelBody title={ __( 'Direction', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-layout">
                        <SegmentedControl
                            label={ __( 'Image position', 'cinderwell' ) }
                            value={ attributes.alignment || 'left' }
                            options={ [
                                { value: 'left', label: __( 'Image left', 'cinderwell' ) },
                                { value: 'right', label: __( 'Image right', 'cinderwell' ) },
                            ] }
                            onChange={ ( v ) => setAttributes( { alignment: v } ) }
                        />
                    </PanelBody>
                    <BackgroundControls value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'caption', label: __( 'Caption', 'cinderwell' ), enabled: attributes.showImage && attributes.showCaption },
                        { key: 'body', label: __( 'Body', 'cinderwell' ), enabled: attributes.showBody },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <div { ...blockProps }>
                    <div className="cinderwell-image-text__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        <div className="cinderwell-image-text__media">
                            { attributes.showImage && attributes.image > 0 ? (
                                <figure className="cw-image-control-host">
                                    <img className={ getImageClassName( attributes.imageFit, attributes.imagePosition, attributes.imageAspect ).trim() || undefined } src={ imageUrl } alt={ attributes.imageAlt } />
                                    <ImageOverlayControls
                                        imageId={ attributes.image }
                                        label={ __( 'content image', 'cinderwell' ) }
                                        onSelect={ ( media ) => setAttributes( { image: media.id, imageUrl: getMediaUrl( media ), imageAlt: attributes.imageAlt || media.alt || '' } ) }
                                        onRemove={ () => setAttributes( { image: 0, imageUrl: '', imageAlt: '' } ) }
                                    />
                                    { attributes.showCaption && <RichText tagName="figcaption" identifier="caption" className={ `cinderwell-caption${ getTextStyleClassName( attributes, 'caption' ) }` } value={ attributes.caption } onChange={ ( value ) => setAttributes( { caption: value } ) } placeholder={ __( 'Caption…', 'cinderwell' ) } allowedFormats={ [] } /> }
                                </figure>
                            ) : attributes.showImage ? (
                                <div className="cinderwell-image-text__placeholder cw-image-control-host is-empty">
                                    <span className="cw-image-control-placeholder">{ __( 'No image selected', 'cinderwell' ) }</span>
                                    <ImageOverlayControls imageId={ 0 } label={ __( 'content image', 'cinderwell' ) } onSelect={ ( media ) => setAttributes( { image: media.id, imageUrl: getMediaUrl( media ), imageAlt: media.alt || '' } ) } />
                                </div>
                            ) : null }
                        </div>
                        <div className="cinderwell-image-text__content">
                            { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                            { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( v ) => setAttributes( { heading: v } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
                            { attributes.showBody && <RichText tagName="div" identifier="bodyContent" className={ `cinderwell-image-text__body${ getTextStyleClassName( attributes, 'body' ) }` } value={ attributes.bodyContent } onChange={ ( v ) => setAttributes( { bodyContent: v } ) } placeholder={ __( 'Body text…', 'cinderwell' ) } allowedFormats={ ALLOWED_BODY_FORMATS } /> }
                            <ButtonSave buttons={ attributes.buttons } onChange={ ( buttons ) => setAttributes( { buttons } ) } />
                            { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( v ) => setAttributes( { footnote: v } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                        </div>
                    </div>
                </div>
            </>
        );
    },
    save: ( { attributes } ) => {
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-image-text cinderwell-image-text--bg-${ attributes.background } cinderwell-image-text--align-${ attributes.alignment }${ getTypographyClassName( attributes ) }` }, attributes ) );
        return (
            <div { ...blockProps }>
                <div className="cinderwell-image-text__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    <div className="cinderwell-image-text__media">
                        { attributes.showImage && attributes.image > 0 && (
                            <figure>
                                <img className={ getImageClassName( attributes.imageFit, attributes.imagePosition, attributes.imageAspect ).trim() || undefined } src={ attributes.imageUrl || `wp-content/uploads/${ attributes.image }` } alt={ attributes.imageAlt } />
                                { attributes.showCaption && attributes.caption && <RichText.Content tagName="figcaption" className={ `cinderwell-caption${ getTextStyleClassName( attributes, 'caption' ) }` } value={ attributes.caption } /> }
                            </figure>
                        ) }
                    </div>
                    <div className="cinderwell-image-text__content">
                        { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                        { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                        { attributes.showBody && attributes.bodyContent && <RichText.Content tagName="div" className={ `cinderwell-image-text__body${ getTextStyleClassName( attributes, 'body' ) }` } value={ attributes.bodyContent } /> }
                        <ButtonSave buttons={ attributes.buttons } />
                        { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                    </div>
                </div>
            </div>
        );
    },
} );
