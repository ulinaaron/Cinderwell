import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, MediaUploadCheck, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
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
import { getImageClassName, getMediaUrl } from '../../shared/media';
import metadata from './block.json';

const createSlide = () => ( {
	eyebrow: '',
	title: __( 'A story worth exploring', 'cinderwell' ),
	body: '',
	buttonText: '',
	buttonUrl: '',
	imageId: 0,
	imageUrl: '',
	imageAlt: '',
	imagePosition: 'center',
} );

const aspectOptions = [
	{ value: 'wide', label: __( 'Wide', 'cinderwell' ) },
	{ value: 'landscape', label: __( 'Landscape', 'cinderwell' ) },
	{ value: 'square', label: __( 'Square', 'cinderwell' ) },
	{ value: 'portrait', label: __( 'Portrait', 'cinderwell' ) },
];

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const slides = attributes.slides || [];
		const updateSlide = ( index, changes ) => setAttributes( {
			slides: slides.map( ( slide, slideIndex ) => slideIndex === index ? { ...slide, ...changes } : slide ),
		} );
		const removeSlide = ( index ) => setAttributes( { slides: slides.filter( ( slide, slideIndex ) => slideIndex !== index ) } );
		const moveSlide = ( index, direction ) => {
			const nextIndex = index + direction;
			if ( nextIndex < 0 || nextIndex >= slides.length ) return;
			const reordered = [ ...slides ];
		[ reordered[ index ], reordered[ nextIndex ] ] = [ reordered[ nextIndex ], reordered[ index ] ];
			setAttributes( { slides: reordered } );
		};
		const blockProps = useBlockProps( getBackgroundImageProps( {
			className: `cinderwell-content-slider cinderwell-content-slider--bg-${ attributes.background }${ getTypographyClassName( attributes ) }`,
		}, attributes ) );

		return (
			<>
				<InspectorControls>
					<BlockIdentity icon="&#9638;" title={ __( 'Content Slider', 'cinderwell' ) } description={ __( 'Structured stories with synchronized media', 'cinderwell' ) } />
					<PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-layout">
						<SectionToggles
							sections={ [
								{ key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
								{ key: 'heading', label: __( 'Heading', 'cinderwell' ) },
							] }
							values={ { eyebrow: attributes.showEyebrow, heading: attributes.showHeading } }
							onChange={ ( value ) => setAttributes( { showEyebrow: value.eyebrow, showHeading: value.heading } ) }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Slider', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-layout">
						<ToggleControl label={ __( 'Show slide indicators', 'cinderwell' ) } checked={ attributes.showIndicators } onChange={ ( value ) => setAttributes( { showIndicators: value } ) } />
						<SelectControl label={ __( 'Image aspect ratio', 'cinderwell' ) } value={ attributes.imageAspect } options={ aspectOptions } onChange={ ( value ) => setAttributes( { imageAspect: value } ) } />
					</PanelBody>
					<LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
					<BackgroundControls value={ attributes.background } onChange={ ( value ) => setAttributes( { background: value } ) } attributes={ attributes } setAttributes={ setAttributes } />
					<TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
						{ key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
						{ key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
					] } />
				</InspectorControls>
				<section { ...blockProps }>
					<div className="cinderwell-content-slider__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
						{ attributes.showEyebrow && <RichText tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( eyebrow ) => setAttributes( { eyebrow } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
						{ attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
						<div className="cinderwell-content-slider__editor-slides">
							{ slides.map( ( slide, index ) => (
								<article className="cinderwell-content-slider__slide is-active" key={ index }>
									<div className="cinderwell-content-slider__content">
										<RichText tagName="span" className="cinderwell-content-slider__slide-eyebrow" value={ slide.eyebrow } onChange={ ( eyebrow ) => updateSlide( index, { eyebrow } ) } placeholder={ __( 'Slide eyebrow…', 'cinderwell' ) } allowedFormats={ [] } />
										<RichText tagName="h3" className="cinderwell-content-slider__title" value={ slide.title } onChange={ ( title ) => updateSlide( index, { title } ) } placeholder={ __( 'Slide title…', 'cinderwell' ) } allowedFormats={ [] } />
										<RichText tagName="div" className="cinderwell-content-slider__body" value={ slide.body } onChange={ ( body ) => updateSlide( index, { body } ) } placeholder={ __( 'Tell this part of the story…', 'cinderwell' ) } />
										<TextControl label={ __( 'Link text', 'cinderwell' ) } value={ slide.buttonText } onChange={ ( buttonText ) => updateSlide( index, { buttonText } ) } />
										<TextControl label={ __( 'Link URL', 'cinderwell' ) } value={ slide.buttonUrl } onChange={ ( buttonUrl ) => updateSlide( index, { buttonUrl } ) } />
										<div className="cinderwell-content-slider__editor-actions">
											<Button variant="secondary" size="compact" disabled={ index === 0 } onClick={ () => moveSlide( index, -1 ) }>{ __( 'Move up', 'cinderwell' ) }</Button>
											<Button variant="secondary" size="compact" disabled={ index === slides.length - 1 } onClick={ () => moveSlide( index, 1 ) }>{ __( 'Move down', 'cinderwell' ) }</Button>
											<Button variant="tertiary" isDestructive size="compact" onClick={ () => removeSlide( index ) }>{ __( 'Remove', 'cinderwell' ) }</Button>
										</div>
									</div>
									<div className="cinderwell-content-slider__media">
										{ slide.imageUrl ? <img className={ getImageClassName( attributes.imageFit, slide.imagePosition, attributes.imageAspect ).trim() || undefined } src={ slide.imageUrl } alt="" /> : null }
										<MediaUploadCheck><MediaUpload allowedTypes={ [ 'image' ] } value={ slide.imageId } onSelect={ ( media ) => updateSlide( index, { imageId: media.id, imageUrl: getMediaUrl( media ), imageAlt: media.alt || '' } ) } render={ ( { open } ) => <Button variant={ slide.imageUrl ? 'secondary' : 'primary' } onClick={ open }>{ slide.imageUrl ? __( 'Replace image', 'cinderwell' ) : __( 'Choose image', 'cinderwell' ) }</Button> } /></MediaUploadCheck>
										<TextControl label={ __( 'Alternative text', 'cinderwell' ) } value={ slide.imageAlt } onChange={ ( imageAlt ) => updateSlide( index, { imageAlt } ) } help={ __( 'Describe the image only when it adds information beyond the nearby text.', 'cinderwell' ) } />
									</div>
								</article>
							) ) }
						</div>
						<Button variant="primary" onClick={ () => setAttributes( { slides: [ ...slides, createSlide() ] } ) }>{ __( 'Add slide', 'cinderwell' ) }</Button>
					</div>
				</section>
			</>
		);
	},
	save: ( { attributes } ) => {
		const slides = attributes.slides || [];
		const blockProps = useBlockProps.save( getBackgroundImageProps( {
			className: `cinderwell-content-slider cinderwell-content-slider--bg-${ attributes.background }${ getTypographyClassName( attributes ) }`,
			'data-cw-content-slider': true,
			role: 'region',
			'aria-roledescription': 'carousel',
			'aria-label': attributes.heading || __( 'Featured stories', 'cinderwell' ),
		}, attributes ) );
		return (
			<section { ...blockProps }>
				<div className="cinderwell-content-slider__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
					{ attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
					{ attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
					<div className="cinderwell-content-slider__slides">
						{ slides.map( ( slide, index ) => (
							<article className={ `cinderwell-content-slider__slide${ index === 0 ? ' is-active' : '' }` } key={ index } data-cw-content-slide aria-roledescription="slide" aria-label={ sprintf( __( '%1$d of %2$d', 'cinderwell' ), index + 1, slides.length ) }>
								<div className="cinderwell-content-slider__content">
									{ slide.eyebrow && <RichText.Content tagName="span" className="cinderwell-content-slider__slide-eyebrow" value={ slide.eyebrow } /> }
									<RichText.Content tagName="h3" className="cinderwell-content-slider__title" value={ slide.title } />
									{ slide.body && <RichText.Content tagName="div" className="cinderwell-content-slider__body" value={ slide.body } /> }
									{ slide.buttonText && slide.buttonUrl && <a className="btn btn--link" href={ slide.buttonUrl }>{ slide.buttonText }</a> }
								</div>
								{ slide.imageUrl && <figure className="cinderwell-content-slider__media"><img className={ getImageClassName( attributes.imageFit, slide.imagePosition, attributes.imageAspect ).trim() || undefined } src={ slide.imageUrl } alt={ slide.imageAlt || '' } /></figure> }
							</article>
						) ) }
					</div>
					{ slides.length > 1 && <div className="cinderwell-content-slider__controls">
						<button type="button" className="cinderwell-content-slider__arrow" data-cw-content-previous><span aria-hidden="true">←</span><span className="screen-reader-text">{ __( 'Previous slide', 'cinderwell' ) }</span></button>
						{ attributes.showIndicators && <div className="cinderwell-content-slider__indicators" role="group" aria-label={ __( 'Choose a slide', 'cinderwell' ) }>{ slides.map( ( slide, index ) => <button type="button" key={ index } className={ `cinderwell-content-slider__indicator${ index === 0 ? ' is-active' : '' }` } data-cw-content-indicator={ index } aria-label={ sprintf( __( 'Go to slide %d', 'cinderwell' ), index + 1 ) } aria-current={ index === 0 ? 'true' : undefined } /> ) }</div> }
						<button type="button" className="cinderwell-content-slider__arrow" data-cw-content-next><span aria-hidden="true">→</span><span className="screen-reader-text">{ __( 'Next slide', 'cinderwell' ) }</span></button>
					</div> }
					<p className="screen-reader-text" aria-live="polite" aria-atomic="true" data-cw-content-status />
				</div>
			</section>
		);
	},
} );
