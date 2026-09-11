import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, TypographyControls, getTypographyClassName, getTextStyleClassName, getHeadingTagName, ButtonRepeater, ButtonSave } from '../../shared/inspector-controls';
import { ImageOverlayControls, ImageSettingsControl } from '../../shared/image-controls';
import { getImageClassName, getMediaUrl } from '../../shared/media';
import { ALLOWED_INLINE_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const hasBg = attributes.showBgImage && attributes.bgImage > 0;
        const imageMedia = useSelect( ( select ) => attributes.image > 0 ? select( 'core' ).getMedia( attributes.image ) : null, [ attributes.image ] );
        const backgroundMedia = useSelect( ( select ) => attributes.bgImage > 0 ? select( 'core' ).getMedia( attributes.bgImage ) : null, [ attributes.bgImage ] );
        const imageUrl = attributes.imageUrl || getMediaUrl( imageMedia );
        const backgroundImageUrl = attributes.bgImageUrl || getMediaUrl( backgroundMedia );
        const backgroundOverlay = attributes.bgOverlay ? ( attributes.bgOverlayPreset || 'medium' ) : 'none';
        const backgroundOverlayClass = backgroundOverlay !== 'medium' ? ` cinderwell-hero__overlay--${ backgroundOverlay }` : '';
        const backgroundPositions = { top: 'center top', bottom: 'center bottom', left: 'left center', right: 'right center', 'top-left': 'left top', 'top-right': 'right top', 'bottom-left': 'left bottom', 'bottom-right': 'right bottom' };
        const blockProps = useBlockProps( {
            className: `cinderwell-hero cinderwell-hero--bg-${ attributes.background }${ getTypographyClassName( attributes ) }${ hasBg ? ' cinderwell-hero--has-bg cw-image-control-host' : '' }`,
            style: hasBg && backgroundImageUrl ? {
                backgroundImage: `url(${ backgroundImageUrl })`,
                backgroundSize: attributes.bgImageFit && attributes.bgImageFit !== 'auto' ? attributes.bgImageFit : undefined,
                backgroundPosition: backgroundPositions[ attributes.bgImagePosition ] || undefined,
            } : undefined,
        } );

        const sectionValues = {
            eyebrow: attributes.showEyebrow,
            heading: attributes.showHeading,
            subheading: attributes.showSubheading,
            image: attributes.showImage,
            caption: attributes.showCaption,
            footnote: attributes.showFootnote,
        };
        const onSectionChange = ( vals ) => setAttributes( {
            showEyebrow: vals.eyebrow,
            showHeading: vals.heading,
            showSubheading: vals.subheading,
            showImage: vals.image,
            showCaption: vals.caption,
            showFootnote: vals.footnote,
        } );

        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="▲" title={ __( 'Hero', 'cinderwell' ) } description={ __( 'Page-level hero with heading and buttons', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Sections', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-sections-panel">
                        <SectionToggles
                            sections={ [
                                { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                                { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                                { key: 'subheading', label: __( 'Subheading', 'cinderwell' ) },
                                { key: 'image', label: __( 'Image', 'cinderwell' ) },
                                { key: 'caption', label: __( 'Caption', 'cinderwell' ) },
                                { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                            ] }
                            values={ sectionValues }
                            onChange={ onSectionChange }
                        />
                    </PanelBody>
                    { attributes.showImage && (
                    <PanelBody title={ __( 'Media', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-fields-panel">
                            <ImageSettingsControl
                                alt={ attributes.imageAlt }
                                fit={ attributes.imageFit }
                                position={ attributes.imagePosition }
                                aspect={ attributes.imageAspect }
                                label={ __( 'Hero image settings', 'cinderwell' ) }
                                onAltChange={ ( imageAlt ) => setAttributes( { imageAlt } ) }
                                onFitChange={ ( imageFit ) => setAttributes( { imageFit } ) }
                                onPositionChange={ ( imagePosition ) => setAttributes( { imagePosition } ) }
                                onAspectChange={ ( imageAspect ) => setAttributes( { imageAspect } ) }
                            />
                    </PanelBody>
                    ) }
                    <PanelBody title={ __( 'Buttons', 'cinderwell' ) } initialOpen={ false } className="cw-panel">
                        <ButtonRepeater buttons={ attributes.buttons } onChange={ ( b ) => setAttributes( { buttons: b } ) } />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } media={ {
                        imageId: attributes.bgImage,
                        imageUrl: backgroundImageUrl,
                        fit: attributes.bgImageFit === 'contain' ? 'contain' : 'cover',
                        position: attributes.bgImagePosition || 'center',
                        overlay: backgroundOverlay,
                        onChange: ( changes ) => setAttributes( {
                            ...( Object.prototype.hasOwnProperty.call( changes, 'imageId' ) ? { bgImage: changes.imageId, showBgImage: changes.imageId > 0 } : {} ),
                            ...( Object.prototype.hasOwnProperty.call( changes, 'imageUrl' ) ? { bgImageUrl: changes.imageUrl } : {} ),
                            ...( changes.fit ? { bgImageFit: changes.fit } : {} ),
                            ...( changes.position ? { bgImagePosition: changes.position } : {} ),
                            ...( changes.overlay ? { bgOverlay: changes.overlay !== 'none', bgOverlayPreset: changes.overlay } : {} ),
                        } ),
                    } } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'subheading', label: __( 'Subheading', 'cinderwell' ), enabled: attributes.showSubheading },
                        { key: 'caption', label: __( 'Caption', 'cinderwell' ), enabled: attributes.showImage && attributes.showCaption },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <section { ...blockProps }>
                    { hasBg && attributes.bgOverlay && <div className={ `cinderwell-hero__overlay${ backgroundOverlayClass }` } /> }
                    { hasBg && (
                        <ImageOverlayControls
                            imageId={ attributes.bgImage }
                            label={ __( 'background image', 'cinderwell' ) }
                            onSelect={ ( media ) => setAttributes( { bgImage: media.id, bgImageUrl: getMediaUrl( media ), showBgImage: true } ) }
                            onRemove={ () => setAttributes( { bgImage: 0, bgImageUrl: '', showBgImage: false } ) }
                        />
                    ) }
                    <div className="cinderwell-hero__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.showEyebrow && (
                            <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                        ) }
                        { attributes.showHeading && (
                            <RichText tagName={ getHeadingTagName( attributes.headingLevel, 1 ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( v ) => setAttributes( { heading: v } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                        ) }
                        { attributes.showSubheading && (
                            <RichText tagName="p" identifier="subheading" className={ `cinderwell-subheading${ getTextStyleClassName( attributes, 'subheading' ) }` } value={ attributes.subheading } onChange={ ( v ) => setAttributes( { subheading: v } ) } placeholder={ __( 'Subheading…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                        ) }
                        { attributes.showImage && attributes.image > 0 ? (
                            <figure className="cinderwell-hero__figure cw-image-control-host">
                                <img src={ imageUrl } alt={ attributes.imageAlt } className={ `cinderwell-hero__image${ getImageClassName( attributes.imageFit, attributes.imagePosition, attributes.imageAspect ) }` } />
                                <ImageOverlayControls
                                    imageId={ attributes.image }
                                    label={ __( 'hero image', 'cinderwell' ) }
                                    onSelect={ ( media ) => setAttributes( { image: media.id, imageUrl: getMediaUrl( media ), imageAlt: attributes.imageAlt || media.alt || '' } ) }
                                    onRemove={ () => setAttributes( { image: 0, imageUrl: '', imageAlt: '' } ) }
                                />
                                { attributes.showCaption && (
                                    <RichText tagName="figcaption" identifier="caption" className={ `cinderwell-caption${ getTextStyleClassName( attributes, 'caption' ) }` } value={ attributes.caption } onChange={ ( v ) => setAttributes( { caption: v } ) } placeholder={ __( 'Caption…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                                ) }
                            </figure>
                        ) : attributes.showImage ? (
                            <div className="cinderwell-hero__figure cw-image-control-host is-empty">
                                <span className="cw-image-control-placeholder">{ __( 'No image selected', 'cinderwell' ) }</span>
                                <ImageOverlayControls imageId={ 0 } label={ __( 'hero image', 'cinderwell' ) } onSelect={ ( media ) => setAttributes( { image: media.id, imageUrl: getMediaUrl( media ), imageAlt: media.alt || '' } ) } />
                            </div>
                        ) : null }
                        { attributes.buttons?.length > 0 && <ButtonSave buttons={ attributes.buttons } onChange={ ( buttons ) => setAttributes( { buttons } ) } /> }
                        { attributes.showFootnote && (
                            <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( v ) => setAttributes( { footnote: v } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                        ) }
                    </div>
                </section>
            </>
        );
    },

    save: ( { attributes } ) => {
        const hasBg = attributes.showBgImage && attributes.bgImage > 0;
        const backgroundOverlay = attributes.bgOverlay ? ( attributes.bgOverlayPreset || 'medium' ) : 'none';
        const backgroundOverlayClass = backgroundOverlay !== 'medium' ? ` cinderwell-hero__overlay--${ backgroundOverlay }` : '';
        const blockProps = useBlockProps.save( {
            className: `cinderwell-hero cinderwell-hero--bg-${ attributes.background }${ getTypographyClassName( attributes ) }${ hasBg ? ' cinderwell-hero--has-bg' : '' }`,
        } );
        return (
            <section { ...blockProps }>
                { hasBg && (
                    <>
                        <img className={ `cinderwell-hero__bg-image${ getImageClassName( attributes.bgImageFit, attributes.bgImagePosition ) }` } src={ attributes.bgImageUrl || `wp-content/uploads/${ attributes.bgImage }` } alt="" aria-hidden="true" />
                        { attributes.bgOverlay && <div className={ `cinderwell-hero__overlay${ backgroundOverlayClass }` } /> }
                    </>
                ) }
                <div className="cinderwell-hero__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                    { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel, 1 ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                    { attributes.showSubheading && attributes.subheading && <RichText.Content tagName="p" className={ `cinderwell-subheading${ getTextStyleClassName( attributes, 'subheading' ) }` } value={ attributes.subheading } /> }
                    { attributes.showImage && attributes.image > 0 && (
                        <figure className="cinderwell-hero__figure">
                            <img src={ attributes.imageUrl || `wp-content/uploads/${ attributes.image }` } alt={ attributes.imageAlt } className={ `cinderwell-hero__image${ getImageClassName( attributes.imageFit, attributes.imagePosition, attributes.imageAspect ) }` } />
                            { attributes.showCaption && attributes.caption && <RichText.Content tagName="figcaption" className={ `cinderwell-caption${ getTextStyleClassName( attributes, 'caption' ) }` } value={ attributes.caption } /> }
                        </figure>
                    ) }
                    { attributes.buttons?.length > 0 && <ButtonSave buttons={ attributes.buttons } /> }
                    { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                </div>
            </section>
        );
    },
} );
