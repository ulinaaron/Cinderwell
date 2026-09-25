import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, TypographyControls, getSpacingClassName, getTypographyClassName, getTextStyleClassName, getHeadingTagName, getCopyMeasureClassName, SegmentedControl, ButtonRepeater, ButtonSave } from '../../shared/inspector-controls';
import { ImageOverlayControls, ImageSettingsControl } from '../../shared/image-controls';
import { getImageClassName, getMediaUrl } from '../../shared/media';
import { ALLOWED_INLINE_FORMATS } from '../../shared/rich-text';
import { canEditControl, filterEditorAccessChanges } from '../../shared/editor-access';
import { VariationPicker, resolveBlockVariation } from '../../shared/variation-picker';
import metadata from './block.json';

const backgroundPositions = { top: 'center top', bottom: 'center bottom', left: 'left center', right: 'right center', 'top-left': 'left top', 'top-right': 'right top', 'bottom-left': 'left bottom', 'bottom-right': 'right bottom' };
const contentWidthFallbacks = { narrow: '600px', standard: '960px', wide: '1200px', full: '100%' };

const saveHero = ( attributes, includeResponsiveSpacing = true, useContentLayout = true, includeImageSide = true, includeSplitGap = true ) => {
    const hasBgImage = attributes.showBgImage && attributes.bgImage > 0;
    const hasBgVideo = Boolean( attributes.bgVideoUrl );
    const hasBg = hasBgImage || hasBgVideo;
    const hasImage = attributes.showImage && attributes.image > 0;
    const backgroundOverlay = attributes.bgOverlay ? ( attributes.bgOverlayPreset || 'medium' ) : 'none';
    const backgroundOverlayClass = backgroundOverlay !== 'medium' ? ` cinderwell-hero__overlay--${ backgroundOverlay }` : '';
    const spacingClassName = includeResponsiveSpacing ? getSpacingClassName( attributes ) : '';
    const imageSideClassName = hasImage && includeImageSide ? ` cinderwell-hero--image-${ attributes.imageSide || 'right' }` : '';
    const splitGapClassName = hasImage && includeSplitGap ? ` cinderwell-hero--split-gap-${ attributes.splitGap || 'md' }` : '';
    const layoutClassName = useContentLayout ? ` cinderwell-hero--align-${ attributes.alignment || 'center' }${ hasImage ? ` cinderwell-hero--split${ imageSideClassName }${ splitGapClassName }` : '' }` : '';
    const blockProps = useBlockProps.save( {
        className: `cinderwell-hero cinderwell-hero--bg-${ attributes.background }${ layoutClassName }${ getCopyMeasureClassName( attributes ) }${ getTypographyClassName( attributes ) }${ spacingClassName }${ hasBg ? ' cinderwell-hero--has-bg' : '' }`,
    } );

    const primaryTextContent = (
        <>
            { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
            { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel, 1 ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
            { attributes.showSubheading && attributes.subheading && <RichText.Content tagName="p" className={ `cinderwell-subheading${ getTextStyleClassName( attributes, 'subheading' ) }` } value={ attributes.subheading } /> }
        </>
    );

    const actionContent = (
        <>
            { attributes.buttons?.length > 0 && <ButtonSave buttons={ attributes.buttons } /> }
            { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
        </>
    );

    const imageContent = hasImage && (
        <figure className="cinderwell-hero__figure">
            <img src={ attributes.imageUrl || `wp-content/uploads/${ attributes.image }` } alt={ attributes.imageAlt } className={ `cinderwell-hero__image${ getImageClassName( attributes.imageFit, attributes.imagePosition, attributes.imageAspect ) }` } />
            { attributes.showCaption && attributes.caption && <RichText.Content tagName="figcaption" className={ `cinderwell-caption${ getTextStyleClassName( attributes, 'caption' ) }` } value={ attributes.caption } /> }
        </figure>
    );

    return (
        <section { ...blockProps } data-cinderwell-background-video={ hasBgVideo ? 'true' : undefined }>
            { hasBgVideo && <video className="cinderwell-hero__bg-image cinderwell-background-video" src={ attributes.bgVideoUrl } poster={ attributes.bgVideoPosterUrl || undefined } style={ { objectFit: attributes.bgVideoFit || 'cover', objectPosition: backgroundPositions[ attributes.bgVideoPosition ] || 'center center' } } autoPlay muted loop playsInline aria-hidden="true" tabIndex="-1" /> }
            { hasBgImage && ! hasBgVideo && (
                <>
                    <img className={ `cinderwell-hero__bg-image${ getImageClassName( attributes.bgImageFit, attributes.bgImagePosition ) }` } src={ attributes.bgImageUrl || `wp-content/uploads/${ attributes.bgImage }` } alt="" aria-hidden="true" />
                </>
            ) }
            { hasBg && attributes.bgOverlay && <div className={ `cinderwell-hero__overlay${ backgroundOverlayClass }` } /> }
            { hasBgVideo && <button type="button" className="cinderwell-background-video__toggle" data-play-label={ __( 'Play background video', 'cinderwell' ) } data-pause-label={ __( 'Pause background video', 'cinderwell' ) } aria-label={ __( 'Pause background video', 'cinderwell' ) } aria-pressed="true"><span className="cinderwell-background-video__toggle-icon" aria-hidden="true">Ⅱ</span><span className="screen-reader-text">{ __( 'Pause background video', 'cinderwell' ) }</span></button> }
            <div className="cinderwell-hero__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                { useContentLayout ? (
                    <>
                        <div className="cinderwell-hero__content">{ primaryTextContent }{ actionContent }</div>
                        { imageContent }
                    </>
                ) : (
                    <>
                        { primaryTextContent }
                        { imageContent }
                        { actionContent }
                    </>
                ) }
            </div>
        </section>
    );
};

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const resolvedLayout = resolveBlockVariation( metadata.name, attributes.layout, 'split' );
        const activeLayout = resolvedLayout?.slug || 'split';
        const contentWidth = attributes.width || 'standard';
        const contentWidthValue = `var(--cw-width-${ contentWidth }, ${ contentWidthFallbacks[ contentWidth ] || contentWidthFallbacks.standard })`;
        const isStatementLayout = activeLayout === 'statement';
        const hasBgImage = attributes.showBgImage && attributes.bgImage > 0;
        const hasBgVideo = Boolean( attributes.bgVideoUrl );
        const hasBg = hasBgImage || hasBgVideo;
        const imageMedia = useSelect( ( select ) => attributes.image > 0 ? select( 'core' ).getMedia( attributes.image ) : null, [ attributes.image ] );
        const backgroundMedia = useSelect( ( select ) => attributes.bgImage > 0 ? select( 'core' ).getMedia( attributes.bgImage ) : null, [ attributes.bgImage ] );
        const imageUrl = attributes.imageUrl || getMediaUrl( imageMedia );
        const backgroundImageUrl = attributes.bgImageUrl || getMediaUrl( backgroundMedia );
        const backgroundOverlay = attributes.bgOverlay ? ( attributes.bgOverlayPreset || 'medium' ) : 'none';
        const backgroundOverlayClass = backgroundOverlay !== 'medium' ? ` cinderwell-hero__overlay--${ backgroundOverlay }` : '';
        const hasImage = attributes.showImage && attributes.image > 0;
        const blockProps = useBlockProps( {
            className: `cinderwell-hero cinderwell-hero--bg-${ attributes.background } cinderwell-hero--layout-${ activeLayout } cinderwell-hero--width-${ contentWidth } cinderwell-hero--align-${ attributes.alignment || 'center' }${ attributes.showImage ? ` cinderwell-hero--split cinderwell-hero--image-${ attributes.imageSide || 'right' } cinderwell-hero--split-gap-${ attributes.splitGap || 'md' }` : '' }${ getCopyMeasureClassName( attributes ) }${ getTypographyClassName( attributes ) }${ getSpacingClassName( attributes ) }${ hasBg ? ' cinderwell-hero--has-bg cw-image-control-host' : '' }`,
            style: hasBgImage && ! hasBgVideo && backgroundImageUrl ? {
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
                    { canEditControl( 'layout' ) && (
                        <VariationPicker
                            blockName={ metadata.name }
                            value={ attributes.layout }
                            fallback="split"
                            title={ __( 'Variation', 'cinderwell' ) }
                            onChange={ ( variation ) => setAttributes( filterEditorAccessChanges( variation.attributes || {}, attributes ) ) }
                        />
                    ) }
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
                            <SegmentedControl
                                label={ __( 'Image position', 'cinderwell' ) }
                                value={ isStatementLayout ? ( attributes.imageSide === 'top' ? 'top' : 'bottom' ) : ( attributes.imageSide === 'left' ? 'left' : 'right' ) }
                                options={ isStatementLayout ? [
                                    { value: 'top', label: __( 'Top', 'cinderwell' ) },
                                    { value: 'bottom', label: __( 'Bottom', 'cinderwell' ) },
                                ] : [
                                    { value: 'left', label: __( 'Left', 'cinderwell' ) },
                                    { value: 'right', label: __( 'Right', 'cinderwell' ) },
                                ] }
                                onChange={ ( imageSide ) => setAttributes( { imageSide } ) }
                            />
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
                    <PanelBody title={ __( 'Buttons', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-links">
                        <ButtonRepeater buttons={ attributes.buttons } onChange={ ( b ) => setAttributes( { buttons: b } ) } />
                    </PanelBody>
                    <LayoutControls
                        attributes={ attributes }
                        setAttributes={ setAttributes }
                        showAlignment={ true }
                        controlled={ resolvedLayout?.controlled || {} }
                    >
                        { attributes.showImage && (
                            <SegmentedControl
                                label={ __( 'Content gap', 'cinderwell' ) }
                                value={ attributes.splitGap || 'md' }
                                options={ [
                                    { value: 'none', label: __( 'None', 'cinderwell' ) },
                                    { value: 'sm', label: __( 'Small', 'cinderwell' ) },
                                    { value: 'md', label: __( 'Medium', 'cinderwell' ) },
                                    { value: 'lg', label: __( 'Large', 'cinderwell' ) },
                                ] }
                                onChange={ ( splitGap ) => setAttributes( { splitGap } ) }
                            />
                        ) }
                    </LayoutControls>
                    <BackgroundControls controlled={ resolvedLayout?.controlled || {} } value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } media={ {
                        imageId: attributes.bgImage,
                        imageUrl: backgroundImageUrl,
                        fit: attributes.bgImageFit === 'contain' ? 'contain' : 'cover',
                        position: attributes.bgImagePosition || 'center',
                        overlay: backgroundOverlay,
                        onChange: ( changes ) => setAttributes( {
                            ...( Object.prototype.hasOwnProperty.call( changes, 'imageId' ) ? { bgImage: changes.imageId, showBgImage: changes.imageId > 0 } : {} ),
                            ...( Object.prototype.hasOwnProperty.call( changes, 'imageUrl' ) ? { bgImageUrl: changes.imageUrl } : {} ),
                            ...( changes.imageId > 0 ? { bgVideo: 0, bgVideoUrl: '', bgVideoPoster: 0, bgVideoPosterUrl: '' } : {} ),
                            ...( changes.fit ? { bgImageFit: changes.fit } : {} ),
                            ...( changes.position ? { bgImagePosition: changes.position } : {} ),
                            ...( changes.overlay ? { bgOverlay: changes.overlay !== 'none', bgOverlayPreset: changes.overlay } : {} ),
                        } ),
                    } } video={ {
                        videoId: attributes.bgVideo,
                        videoUrl: attributes.bgVideoUrl,
                        posterId: attributes.bgVideoPoster,
                        posterUrl: attributes.bgVideoPosterUrl,
                        fit: attributes.bgVideoFit || 'cover',
                        position: attributes.bgVideoPosition || 'center',
                        overlay: backgroundOverlay,
                        onChange: ( changes ) => setAttributes( {
                            ...( Object.prototype.hasOwnProperty.call( changes, 'videoId' ) ? { bgVideo: changes.videoId } : {} ),
                            ...( Object.prototype.hasOwnProperty.call( changes, 'videoUrl' ) ? { bgVideoUrl: changes.videoUrl } : {} ),
                            ...( changes.videoId > 0 || changes.videoUrl ? { bgImage: 0, bgImageUrl: '', showBgImage: false } : {} ),
                            ...( Object.prototype.hasOwnProperty.call( changes, 'posterId' ) ? { bgVideoPoster: changes.posterId } : {} ),
                            ...( Object.prototype.hasOwnProperty.call( changes, 'posterUrl' ) ? { bgVideoPosterUrl: changes.posterUrl } : {} ),
                            ...( changes.fit ? { bgVideoFit: changes.fit } : {} ),
                            ...( changes.position ? { bgVideoPosition: changes.position } : {} ),
                            ...( changes.overlay ? { bgOverlay: changes.overlay !== 'none', bgOverlayPreset: changes.overlay } : {} ),
                        } ),
                    } } />
                    <TypographyControls controlled={ resolvedLayout?.controlled || {} } attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'subheading', label: __( 'Subheading', 'cinderwell' ), enabled: attributes.showSubheading },
                        { key: 'caption', label: __( 'Caption', 'cinderwell' ), enabled: attributes.showImage && attributes.showCaption },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <section { ...blockProps } data-cinderwell-background-video={ attributes.bgVideoUrl ? 'true' : undefined }>
                    { hasBgVideo && <video className="cinderwell-hero__bg-image cinderwell-background-video" src={ attributes.bgVideoUrl } poster={ attributes.bgVideoPosterUrl || undefined } style={ { objectFit: attributes.bgVideoFit || 'cover', objectPosition: backgroundPositions[ attributes.bgVideoPosition ] || 'center center' } } muted loop playsInline aria-hidden="true" tabIndex="-1" /> }
                    { hasBg && attributes.bgOverlay && <div className={ `cinderwell-hero__overlay${ backgroundOverlayClass }` } /> }
                    { hasBgImage && ! hasBgVideo && (
                        <ImageOverlayControls
                            imageId={ attributes.bgImage }
                            label={ __( 'background image', 'cinderwell' ) }
                            onSelect={ ( media ) => setAttributes( { bgImage: media.id, bgImageUrl: getMediaUrl( media ), showBgImage: true } ) }
                            onRemove={ () => setAttributes( { bgImage: 0, bgImageUrl: '', showBgImage: false } ) }
                        />
                    ) }
                    <div className="cinderwell-hero__inner" style={ { width: `min(100%, ${ contentWidthValue })`, maxWidth: contentWidthValue } }>
                        <div className="cinderwell-hero__content">
                            { attributes.showEyebrow && (
                                <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                            ) }
                            { attributes.showHeading && (
                                <RichText tagName={ getHeadingTagName( attributes.headingLevel, 1 ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( v ) => setAttributes( { heading: v } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                            ) }
                            { attributes.showSubheading && (
                                <RichText tagName="p" identifier="subheading" className={ `cinderwell-subheading${ getTextStyleClassName( attributes, 'subheading' ) }` } value={ attributes.subheading } onChange={ ( v ) => setAttributes( { subheading: v } ) } placeholder={ __( 'Subheading…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                            ) }
                            { attributes.buttons?.length > 0 && <ButtonSave buttons={ attributes.buttons } onChange={ ( buttons ) => setAttributes( { buttons } ) } /> }
                            { attributes.showFootnote && (
                                <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( v ) => setAttributes( { footnote: v } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                            ) }
                        </div>
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
                    </div>
                </section>
            </>
        );
    },

    save: ( { attributes } ) => saveHero( attributes ),
    deprecated: [
        {
            attributes: metadata.attributes,
            save: ( { attributes } ) => saveHero( attributes, true, true, true, false ),
        },
        {
            attributes: metadata.attributes,
            save: ( { attributes } ) => saveHero( attributes, true, true, false, false ),
        },
        {
            attributes: metadata.attributes,
            save: ( { attributes } ) => saveHero( attributes, true, false ),
        },
        {
            attributes: metadata.attributes,
            save: ( { attributes } ) => saveHero( attributes, false, false ),
        },
    ],
} );
