import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { Button, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, ToggleRow, SegmentedControl, ResponsiveSegmentedControl, getResponsiveModifierClassName, ColorTokenControl, LayoutControls, BackgroundControls, getBackgroundImageProps, TypographyControls, getTypographyClassName, getTextStyleClassName, getHeadingTagName, SortableItemCard, moveArrayItem } from '../../shared/inspector-controls';
import { ImageOverlayControls, ImageSettingsControl } from '../../shared/image-controls';
import { getImageClassName, getMediaUrl } from '../../shared/media';
import { IconGlyph } from '../../shared/icon-library';
import { IconSettingsControl, getIconStyleClassName } from '../../shared/icon-controls';
import { EditableLink } from '../../shared/link-control';
import { ButtonDestinationControl, ButtonIconControl } from '../../shared/button-controls';
import { getButtonHref, getButtonIconProps } from '../../shared/button-utils';
import { canEditControl, filterEditorAccessChanges } from '../../shared/editor-access';
import { VariationPicker, resolveBlockVariation } from '../../shared/variation-picker';
import metadata from './block.json';

let cardCounter = 0;

const cardColorOptions = [
    { value: 'auto', label: __( 'Automatic', 'cinderwell' ), color: 'linear-gradient(135deg, #ffffff 0 50%, #1a1a1a 50%)' },
    { value: 'white', label: __( 'White', 'cinderwell' ), color: 'var(--cw-color-white, #ffffff)' },
    { value: 'light', label: __( 'Light', 'cinderwell' ), color: 'var(--cw-color-light, #f8f5ef)' },
    { value: 'dark', label: __( 'Dark', 'cinderwell' ), color: 'var(--cw-color-dark, #1a1a1a)' },
    { value: 'brand', label: __( 'Brand', 'cinderwell' ), color: 'var(--cw-color-brand, #b84c00)' },
];

const getCardColorClassName = ( cardColor ) => cardColor && cardColor !== 'auto' ? ` cinderwell-card-grid--cards-${ cardColor }` : '';
// Legacy cards inferred these presentation choices from populated content.
// New cards write explicit values, so undefined remains a safe compatibility
// signal rather than changing what an existing site displays after an update.
const shouldShowCardLabel = ( card ) => card.showLabel !== false && Boolean( card.label );
const getCardButtonVariant = ( card ) => card.buttonVariant || 'link';
const getResponsiveColumnsClassName = ( attributes ) => getResponsiveModifierClassName( 'cinderwell-card-grid', 'cols', { tablet: attributes.columnsTablet, mobile: attributes.columnsMobile } );
const getImageBoxClassName = ( attributes ) => [
    `cinderwell-card-grid--image-box-aspect-${ attributes.imageBoxAspect || 'landscape' }`,
    `cinderwell-card-grid--image-box-overlay-${ attributes.imageBoxOverlay || 'medium' }`,
    `cinderwell-card-grid--image-box-position-${ attributes.imageBoxContentPosition || 'bottom' }`,
    `cinderwell-card-grid--image-box-content-${ attributes.imageBoxContentVisibility || 'always' }`,
    `cinderwell-card-grid--image-box-links-${ attributes.imageBoxLinkStyle || 'button' }`,
].join( ' ' );

const columnOptions = [
    { label: '1', value: '1' },
    { label: '2', value: '2' },
    { label: '3', value: '3' },
    { label: '4', value: '4' },
];

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes, clientId } ) => {
        const resolvedLayout = resolveBlockVariation( metadata.name, attributes.layout, 'raised' );
        const activeLayout = resolvedLayout?.slug || 'raised';
        const imageBoxClasses = activeLayout === 'image-box' ? ` ${ getImageBoxClassName( attributes ) }` : '';
        const blockProps = useBlockProps( getBackgroundImageProps( { className: `cinderwell-card-grid cinderwell-card-grid--bg-${ attributes.background } cinderwell-card-grid--cols-${ attributes.columns }${ getResponsiveColumnsClassName( attributes ) }${ getCardColorClassName( attributes.cardColor ) } cinderwell-card-grid--layout-${ activeLayout }${ imageBoxClasses }${ getTypographyClassName( attributes ) }` }, attributes ) );
        const cards = attributes.cards || [];
        const resolvedImageUrls = useSelect( ( select ) => cards.map( ( card ) => card.imageUrl || getMediaUrl( card.image > 0 ? select( 'core' ).getMedia( card.image ) : null ) ), [ cards.map( ( card ) => `${ card.image || 0 }:${ card.imageUrl || '' }` ).join( '|' ) ] );
        const secs = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, footnote: attributes.showFootnote };
        const onSecs = ( v ) => setAttributes( { showEyebrow: v.eyebrow, showHeading: v.heading, showFootnote: v.footnote } );
        const addCard = () => { cardCounter++; const useImage = activeLayout === 'image-box'; setAttributes( { cards: [ ...cards, { id: `card-${ cardCounter }`, visualType: useImage ? 'image' : 'none', icon: 'star', iconSource: 'library', iconSvg: '', iconViewBox: '0 0 24 24', iconSvgId: 0, iconSvgUrl: '', iconSize: 'md', iconColor: 'brand', iconTreatment: 'plain', iconAlignment: 'left', image: 0, imageUrl: '', imageAlt: '', imageFit: 'auto', imagePosition: 'center', showImage: useImage, label: '', showLabel: false, title: '', description: '', buttonUrl: '', buttonUrlDynamic: {}, buttonNewTab: false, buttonDestinationType: 'link', buttonPhoneNumber: '', buttonEmailAddress: '', buttonIcon: '', buttonIconPosition: 'before', buttonText: '', buttonVariant: 'primary', buttonSize: 'sm' } ] } ); };
        const updateCard = ( i, f, v ) => setAttributes( { cards: cards.map( ( c, idx ) => idx === i ? { ...c, [ f ]: v } : c ) } );
        const updateCardFields = ( i, changes ) => setAttributes( { cards: cards.map( ( c, idx ) => idx === i ? { ...c, ...changes } : c ) } );
        const updateCardLink = ( i, changes ) => updateCardFields( i, {
            ...( Object.prototype.hasOwnProperty.call( changes, 'url' ) ? { buttonUrl: changes.url } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'opensInNewTab' ) ? { buttonNewTab: changes.opensInNewTab } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'dynamicData' ) ? { buttonUrlDynamic: changes.dynamicData } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'destinationType' ) ? { buttonDestinationType: changes.destinationType } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'phoneNumber' ) ? { buttonPhoneNumber: changes.phoneNumber } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'emailAddress' ) ? { buttonEmailAddress: changes.emailAddress } : {} ),
        } );
        const getVisualType = ( card ) => card.visualType || ( card.showImage ? 'image' : 'none' );
        const removeCard = ( i ) => setAttributes( { cards: cards.filter( ( _, idx ) => idx !== i ) } );
        const moveCard = ( from, to ) => setAttributes( { cards: moveArrayItem( cards, from, to ) } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="&#9638;" title={ __( 'Card Grid', 'cinderwell' ) } description={ __( 'Responsive card grid', 'cinderwell' ) } />
                    { canEditControl( 'layout' ) && (
                        <VariationPicker
                            blockName={ metadata.name }
                            value={ attributes.layout }
                            fallback="raised"
                            title={ __( 'Variation', 'cinderwell' ) }
                            onChange={ ( variation ) => setAttributes( filterEditorAccessChanges( variation.attributes || {}, attributes ) ) }
                        />
                    ) }
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-layout">
                        <SectionToggles sections={ [
                            { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                            { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                            { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                        ] } values={ secs } onChange={ onSecs } />
                        <ResponsiveSegmentedControl
                            label={ __( 'Columns', 'cinderwell' ) }
                            values={ { desktop: attributes.columns, tablet: attributes.columnsTablet || 'auto', mobile: attributes.columnsMobile || 'auto' } }
                            options={ ( breakpoint ) => breakpoint === 'desktop' ? columnOptions : [ { label: __( 'Auto', 'cinderwell' ), value: 'auto' }, ...columnOptions ] }
                            autoHelp={ { tablet: __( 'Auto keeps the curated tablet layout.', 'cinderwell' ), mobile: __( 'Auto stacks cards in one column.', 'cinderwell' ) } }
                            onChange={ ( breakpoint, value ) => setAttributes( breakpoint === 'desktop' ? { columns: value } : { [ breakpoint === 'tablet' ? 'columnsTablet' : 'columnsMobile' ]: value === 'auto' ? '' : value } ) }
                        />
                    </PanelBody>
                    { activeLayout === 'image-box' && (
                        <PanelBody title={ __( 'Image boxes', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-layout">
                            <SegmentedControl label={ __( 'Image shape', 'cinderwell' ) } value={ attributes.imageBoxAspect || 'landscape' } options={ [
                                { label: __( 'Square', 'cinderwell' ), value: 'square' },
                                { label: __( 'Landscape', 'cinderwell' ), value: 'landscape' },
                                { label: __( 'Portrait', 'cinderwell' ), value: 'portrait' },
                                { label: __( 'Tall', 'cinderwell' ), value: 'tall' },
                            ] } onChange={ ( imageBoxAspect ) => setAttributes( { imageBoxAspect } ) } />
                            <SegmentedControl label={ __( 'Overlay', 'cinderwell' ) } value={ attributes.imageBoxOverlay || 'medium' } options={ [
                                { label: __( 'Soft', 'cinderwell' ), value: 'soft' },
                                { label: __( 'Medium', 'cinderwell' ), value: 'medium' },
                                { label: __( 'Strong', 'cinderwell' ), value: 'strong' },
                            ] } onChange={ ( imageBoxOverlay ) => setAttributes( { imageBoxOverlay } ) } />
                            <SegmentedControl label={ __( 'Content position', 'cinderwell' ) } value={ attributes.imageBoxContentPosition || 'bottom' } options={ [
                                { label: __( 'Top', 'cinderwell' ), value: 'top' },
                                { label: __( 'Center', 'cinderwell' ), value: 'center' },
                                { label: __( 'Bottom', 'cinderwell' ), value: 'bottom' },
                            ] } onChange={ ( imageBoxContentPosition ) => setAttributes( { imageBoxContentPosition } ) } />
                            <SegmentedControl label={ __( 'Content visibility', 'cinderwell' ) } value={ attributes.imageBoxContentVisibility || 'always' } options={ [
                                { label: __( 'Always', 'cinderwell' ), value: 'always' },
                                { label: __( 'Reveal', 'cinderwell' ), value: 'reveal' },
                            ] } onChange={ ( imageBoxContentVisibility ) => setAttributes( { imageBoxContentVisibility } ) } />
                            <SegmentedControl label={ __( 'Link treatment', 'cinderwell' ) } value={ attributes.imageBoxLinkStyle || 'button' } options={ [
                                { label: __( 'Button', 'cinderwell' ), value: 'button' },
                                { label: __( 'Whole card', 'cinderwell' ), value: 'card' },
                            ] } onChange={ ( imageBoxLinkStyle ) => setAttributes( { imageBoxLinkStyle } ) } />
                            <p className="components-base-control__help">{ __( 'Reveal also responds to keyboard focus and remains visible on touch devices.', 'cinderwell' ) }</p>
                        </PanelBody>
                    ) }
                    <PanelBody title={ __( 'Cards', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-content">
                        { cards.map( ( card, i ) => (
                            <SortableItemCard
                                key={ card.id || i }
                                index={ i }
                                total={ cards.length }
                                listId={ `${ clientId }-cards` }
                                label={ card.title || `${ __( 'Card', 'cinderwell' ) } ${ i + 1 }` }
                                onMove={ moveCard }
                                onRemove={ () => removeCard( i ) }
                            >
                                    <SegmentedControl label={ __( 'Card visual', 'cinderwell' ) } value={ getVisualType( card ) } options={ [ { label: __( 'None', 'cinderwell' ), value: 'none' }, { label: __( 'Image', 'cinderwell' ), value: 'image' }, { label: __( 'Icon', 'cinderwell' ), value: 'icon' } ] } onChange={ ( visualType ) => updateCardFields( i, { visualType, showImage: visualType === 'image' } ) } />
                                    { getVisualType( card ) === 'image' && (
                                        <ImageSettingsControl
                                            alt={ card.imageAlt }
                                            fit={ card.imageFit }
                                            position={ card.imagePosition }
                                            showAspect={ false }
                                            label={ __( 'Image settings', 'cinderwell' ) }
                                            onAltChange={ ( value ) => updateCard( i, 'imageAlt', value ) }
                                            onFitChange={ ( value ) => updateCard( i, 'imageFit', value ) }
                                            onPositionChange={ ( value ) => updateCard( i, 'imagePosition', value ) }
                                        />
                                    ) }
                                    { getVisualType( card ) === 'icon' && <IconSettingsControl icon={ card.icon || 'star' } source={ card.iconSource || 'library' } customSvg={ card.iconSvg || '' } customViewBox={ card.iconViewBox || '0 0 24 24' } customSvgId={ card.iconSvgId || 0 } customSvgUrl={ card.iconSvgUrl || '' } size={ card.iconSize || 'md' } color={ card.iconColor || 'brand' } treatment={ card.iconTreatment || 'plain' } alignment={ card.iconAlignment || 'left' } onIconChange={ ( value ) => updateCard( i, 'icon', value ) } onSourceChange={ ( value ) => updateCard( i, 'iconSource', value ) } onCustomSvgChange={ ( iconSvg, iconViewBox, iconSvgId, iconSvgUrl ) => updateCardFields( i, { iconSvg, iconViewBox, iconSvgId, iconSvgUrl } ) } onSizeChange={ ( value ) => updateCard( i, 'iconSize', value ) } onColorChange={ ( value ) => updateCard( i, 'iconColor', value ) } onTreatmentChange={ ( value ) => updateCard( i, 'iconTreatment', value ) } onAlignmentChange={ ( value ) => updateCard( i, 'iconAlignment', value ) } /> }
                                    <ToggleRow label={ __( 'Show eyebrow', 'cinderwell' ) } checked={ card.showLabel } onChange={ ( v ) => updateCard( i, 'showLabel', v ) } />
                                    <SegmentedControl label={ __( 'Button style', 'cinderwell' ) } value={ getCardButtonVariant( card ) } options={ [
                                        { label: __( 'Primary', 'cinderwell' ), value: 'primary' },
                                        { label: __( 'Secondary', 'cinderwell' ), value: 'secondary' },
                                        { label: __( 'Ghost', 'cinderwell' ), value: 'ghost' },
                                        { label: __( 'Link', 'cinderwell' ), value: 'link' },
                                    ] } onChange={ ( v ) => updateCard( i, 'buttonVariant', v ) } />
                                    <SegmentedControl label={ __( 'Button size', 'cinderwell' ) } value={ card.buttonSize || 'sm' } options={ [
                                        { label: __( 'Small', 'cinderwell' ), value: 'sm' },
                                        { label: __( 'Medium', 'cinderwell' ), value: 'md' },
                                        { label: __( 'Large', 'cinderwell' ), value: 'lg' },
                                    ] } onChange={ ( v ) => updateCard( i, 'buttonSize', v ) } />
                                    <ButtonDestinationControl
                                        destinationType={ card.buttonDestinationType || 'link' }
                                        url={ card.buttonUrl }
                                        phoneNumber={ card.buttonPhoneNumber || '' }
                                        emailAddress={ card.buttonEmailAddress || '' }
                                        opensInNewTab={ Boolean( card.buttonNewTab ) }
                                        dynamicData={ card.buttonUrlDynamic || {} }
                                        onChange={ ( changes ) => updateCardLink( i, changes ) }
                                    />
                                    <ButtonIconControl
                                        icon={ card.buttonIcon || '' }
                                        iconPosition={ card.buttonIconPosition || 'before' }
                                        onChange={ ( changes ) => updateCardFields( i, {
                                            ...( Object.prototype.hasOwnProperty.call( changes, 'icon' ) ? { buttonIcon: changes.icon } : {} ),
                                            ...( Object.prototype.hasOwnProperty.call( changes, 'iconPosition' ) ? { buttonIconPosition: changes.iconPosition } : {} ),
                                        } ) }
                                    />
                            </SortableItemCard>
                        ) ) }
                        <Button variant="secondary" onClick={ addCard } className="cw-add-item">+ { __( 'Add card', 'cinderwell' ) }</Button>
                    </PanelBody>
                    <LayoutControls controlled={ resolvedLayout?.controlled || {} } attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls controlled={ resolvedLayout?.controlled || {} } value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes }>
                        <ColorTokenControl label={ __( 'Card color', 'cinderwell' ) } value={ attributes.cardColor || 'auto' } options={ cardColorOptions } onChange={ ( cardColor ) => setAttributes( { cardColor } ) } />
                    </BackgroundControls>
                    <TypographyControls controlled={ resolvedLayout?.controlled || {} } attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'cardLabel', label: __( 'Card eyebrows', 'cinderwell' ), enabled: cards.some( ( card ) => card.showLabel ), background: attributes.cardColor },
                        { key: 'cardTitle', label: __( 'Card titles', 'cinderwell' ), enabled: cards.length > 0, background: attributes.cardColor },
                        { key: 'cardDescription', label: __( 'Card descriptions', 'cinderwell' ), enabled: cards.length > 0, background: attributes.cardColor },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <div { ...blockProps }>
                    <div className="cinderwell-card-grid__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( value ) => setAttributes( { eyebrow: value } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( value ) => setAttributes( { heading: value } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        <div className="cinderwell-card-grid__grid">
                            { cards.map( ( card, i ) => (
                                <article key={ card.id || i } className="cinderwell-card-grid__card">
                                    { getVisualType( card ) === 'image' && card.image > 0 ? (
                                        <div className="cinderwell-card-grid__card-image cw-image-control-host">
                                            <img className={ getImageClassName( card.imageFit, card.imagePosition ).trim() || undefined } src={ resolvedImageUrls[ i ] || '' } alt={ card.imageAlt } />
                                            <ImageOverlayControls
                                                imageId={ card.image }
                                                label={ `${ __( 'image for', 'cinderwell' ) } ${ card.title || __( 'card', 'cinderwell' ) }` }
                                                onSelect={ ( media ) => setAttributes( { cards: cards.map( ( current, index ) => index === i ? { ...current, image: media.id, imageUrl: getMediaUrl( media ), imageAlt: current.imageAlt || media.alt || '' } : current ) } ) }
                                                onRemove={ () => setAttributes( { cards: cards.map( ( current, index ) => index === i ? { ...current, image: 0, imageUrl: '', imageAlt: '' } : current ) } ) }
                                            />
                                        </div>
                                    ) : getVisualType( card ) === 'image' ? (
                                        <div className="cinderwell-card-grid__card-image cw-image-control-host is-empty">
                                            <span className="cw-image-control-placeholder">{ __( 'No image selected', 'cinderwell' ) }</span>
                                            <ImageOverlayControls imageId={ 0 } label={ __( 'card image', 'cinderwell' ) } onSelect={ ( media ) => setAttributes( { cards: cards.map( ( current, index ) => index === i ? { ...current, image: media.id, imageUrl: getMediaUrl( media ), imageAlt: media.alt || '' } : current ) } ) } />
                                        </div>
                                    ) : getVisualType( card ) === 'icon' ? <div className={ `cinderwell-card-grid__card-icon${ getIconStyleClassName( { size: card.iconSize, color: card.iconColor, treatment: card.iconTreatment, alignment: card.iconAlignment } ) }` }><span className="cinderwell-card-grid__card-icon-glyph"><IconGlyph icon={ card.icon || 'star' } customSvg={ card.iconSource === 'custom' ? card.iconSvg : '' } viewBox={ card.iconViewBox || '0 0 24 24' } /></span></div> : null }
                                    <div className="cinderwell-card-grid__card-content">
                                        { shouldShowCardLabel( card ) && <RichText tagName="span" className={ `cinderwell-card-grid__card-label${ getTextStyleClassName( attributes, 'cardLabel' ) }` } value={ card.label } onChange={ ( value ) => updateCard( i, 'label', value ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                                        <RichText tagName={ getHeadingTagName( attributes.itemHeadingLevel, 3 ) } className={ `cinderwell-card-grid__card-title${ getTextStyleClassName( attributes, 'cardTitle' ) }` } value={ card.title } onChange={ ( value ) => updateCard( i, 'title', value ) } placeholder={ __( 'Card title…', 'cinderwell' ) } allowedFormats={ [] } />
                                        <RichText tagName="p" className={ `cinderwell-card-grid__card-description${ getTextStyleClassName( attributes, 'cardDescription' ) }` } value={ card.description } onChange={ ( value ) => updateCard( i, 'description', value ) } placeholder={ __( 'Card description…', 'cinderwell' ) } allowedFormats={ [] } />
                                        <EditableLink
                                            tagName="a"
                                            className={ `btn btn--${ getCardButtonVariant( card ) } btn--${ card.buttonSize || 'sm' }` }
                                            value={ card.buttonText }
                                            url={ card.buttonUrl }
                                            opensInNewTab={ Boolean( card.buttonNewTab ) }
                                            dynamicData={ card.buttonUrlDynamic || {} }
                                            destinationType={ card.buttonDestinationType || 'link' }
                                            phoneNumber={ card.buttonPhoneNumber || '' }
                                            emailAddress={ card.buttonEmailAddress || '' }
                                            icon={ card.buttonIcon || '' }
                                            iconPosition={ card.buttonIconPosition || 'before' }
                                            settingsControl={ <ButtonDestinationControl
                                                destinationType={ card.buttonDestinationType || 'link' }
                                                url={ card.buttonUrl }
                                                phoneNumber={ card.buttonPhoneNumber || '' }
                                                emailAddress={ card.buttonEmailAddress || '' }
                                                opensInNewTab={ Boolean( card.buttonNewTab ) }
                                                dynamicData={ card.buttonUrlDynamic || {} }
                                                onChange={ ( changes ) => updateCardLink( i, changes ) }
                                            /> }
                                            onTextChange={ ( value ) => updateCard( i, 'buttonText', value ) }
                                            onLinkChange={ ( changes ) => updateCardLink( i, changes ) }
                                            contextLabel={ __( 'Button destination', 'cinderwell' ) }
                                            placeholder={ __( 'Button text…', 'cinderwell' ) }
                                            allowedFormats={ [] }
                                        />
                                    </div>
                                </article>
                            ) ) }
                        </div>
                        { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( value ) => setAttributes( { footnote: value } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ [] } /> }
                    </div>
                </div>
            </>
        );
    },
    save: ( { attributes } ) => {
        const cards = attributes.cards || [];
        const getVisualType = ( card ) => card.visualType || ( card.showImage ? 'image' : 'none' );
        const hasIconSettings = ( card ) => [ 'iconSource', 'iconSvg', 'iconSize', 'iconColor', 'iconTreatment', 'iconAlignment' ].some( ( key ) => Object.prototype.hasOwnProperty.call( card, key ) );
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-card-grid cinderwell-card-grid--bg-${ attributes.background } cinderwell-card-grid--cols-${ attributes.columns }${ getResponsiveColumnsClassName( attributes ) }${ getCardColorClassName( attributes.cardColor ) }${ getTypographyClassName( attributes ) }` }, attributes ) );
        return (
            <div { ...blockProps }>
                <div className="cinderwell-card-grid__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                    { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                    <div className="cinderwell-card-grid__grid">
                        { cards.map( ( card, i ) => (
                            <article key={ card.id || i } className="cinderwell-card-grid__card">
                                { getVisualType( card ) === 'image' && card.image > 0 && <div className="cinderwell-card-grid__card-image"><img className={ getImageClassName( card.imageFit, card.imagePosition ).trim() || undefined } src={ card.imageUrl || `wp-content/uploads/${ card.image }` } alt={ card.imageAlt } /></div> }
                                { getVisualType( card ) === 'icon' && ( hasIconSettings( card ) ? <div className={ `cinderwell-card-grid__card-icon${ getIconStyleClassName( { size: card.iconSize, color: card.iconColor, treatment: card.iconTreatment, alignment: card.iconAlignment } ) }` }><span className="cinderwell-card-grid__card-icon-glyph"><IconGlyph icon={ card.icon || 'star' } customSvg={ card.iconSource === 'custom' ? card.iconSvg : '' } viewBox={ card.iconViewBox || '0 0 24 24' } /></span></div> : <div className="cinderwell-card-grid__card-icon"><IconGlyph icon={ card.icon || 'star' } /></div> ) }
                                <div className="cinderwell-card-grid__card-content">
                                    { shouldShowCardLabel( card ) && <RichText.Content tagName="span" className={ `cinderwell-card-grid__card-label${ getTextStyleClassName( attributes, 'cardLabel' ) }` } value={ card.label } /> }
                                    { card.title && <RichText.Content tagName={ getHeadingTagName( attributes.itemHeadingLevel, 3 ) } className={ `cinderwell-card-grid__card-title${ getTextStyleClassName( attributes, 'cardTitle' ) }` } value={ card.title } /> }
                                    { card.description && <RichText.Content tagName="p" className={ `cinderwell-card-grid__card-description${ getTextStyleClassName( attributes, 'cardDescription' ) }` } value={ card.description } /> }
                                    { card.buttonText && ( () => {
                                        const destinationType = card.buttonDestinationType || 'link';
                                        const iconProps = getButtonIconProps( card.buttonIcon, card.buttonIconPosition );
                                        return <RichText.Content tagName="a" href={ getButtonHref( { destinationType, url: card.buttonUrl, phoneNumber: card.buttonPhoneNumber, emailAddress: card.buttonEmailAddress } ) || '#' } target={ destinationType === 'link' && card.buttonNewTab ? '_blank' : undefined } rel={ destinationType === 'link' && card.buttonNewTab ? 'noopener noreferrer' : undefined } data-cw-url-source={ destinationType === 'link' && card.buttonUrlDynamic?.source && card.buttonUrlDynamic.source !== 'static' ? card.buttonUrlDynamic.source : undefined } data-cw-url-field={ destinationType === 'link' ? card.buttonUrlDynamic?.field || undefined : undefined } data-cw-url-fallback={ destinationType === 'link' ? card.buttonUrlDynamic?.fallback || undefined : undefined } className={ `btn btn--${ getCardButtonVariant( card ) } btn--${ card.buttonSize || 'sm' }${ iconProps.className ? ` ${ iconProps.className }` : '' }` } style={ iconProps.style } value={ card.buttonText } />;
                                    } )() }
                                </div>
                            </article>
                        ) ) }
                    </div>
                    { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                </div>
            </div>
        );
    },
} );
