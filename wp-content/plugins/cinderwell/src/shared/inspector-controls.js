import {
    PanelBody,
    TextControl,
    SelectControl,
    Button,
    Dropdown,
    Popover,
    Tooltip,
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck, RichText } from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { ConditionsPanel } from './conditions-panel';
import { EditableLink, LinkSettingsControl } from './link-control';

/**
 * Block identity strip — compact, pinned at top.
 */
export const BlockIdentity = ( { icon, title, description } ) => {
    return (
        <div className="cw-identity">
            <span className="cw-identity__icon">{ icon }</span>
            <div>
                <div className="cw-identity__name">{ title }</div>
                <div className="cw-identity__desc">{ description }</div>
            </div>
        </div>
    );
};

/**
 * Section toggles — custom toggle switches, grouped together.
 */
export const SectionToggles = ( { sections, values, onChange, children } ) => {
    return (
        <div className="cw-sections">
            <div className="cw-toggle-list">
                { sections.map( ( { key, label } ) => (
                    <ToggleRow key={ key } label={ label } checked={ values[ key ] } onChange={ () => onChange( { ...values, [ key ]: ! values[ key ] } ) } />
                ) ) }
            </div>
            { children && <div className="cw-unlocked-fields">{ children }</div> }
        </div>
    );
};

/**
 * Compact toggle row — shared by section switches and item-level options.
 */
export const ToggleRow = ( { label, checked, onChange } ) => (
    <div className="cw-toggle-row">
        <span className="cw-toggle-row__label">{ label }</span>
        <button
            type="button"
            className={ `cw-toggle${ checked ? ' is-on' : '' }` }
            onClick={ () => onChange( ! checked ) }
            role="switch"
            aria-checked={ Boolean( checked ) }
            aria-label={ label }
        >
            <span className="cw-toggle__thumb" />
        </button>
    </div>
);

/**
 * Small contextual help affordance for client-facing controls.
 */
export const HelpTooltip = ( { text } ) => (
    <Tooltip text={ text } placement="top" delay={ 250 } className="cw-context-tooltip">
        <button type="button" className="cw-help-tooltip" aria-label={ __( 'More information', 'cinderwell' ) }>?</button>
    </Tooltip>
);

/**
 * Segmented control — for spacing, width, alignment.
 */
export const SegmentedControl = ( { label, value, options, onChange } ) => {
    return (
        <div className="cw-field">
            { label && <div className="cw-field__label">{ label }</div> }
            <div className="cw-segmented">
                { options.map( ( opt ) => (
                    <button
                        type="button"
                        key={ opt.value }
                        className={ value === opt.value ? 'is-active' : '' }
                        onClick={ () => onChange( opt.value ) }
                        aria-pressed={ value === opt.value }
                    >
                        { opt.label }
                    </button>
                ) ) }
            </div>
        </div>
    );
};

/**
 * Shared surface-inheritance control for blocks that contain other blocks.
 */
export const ChildBlockInheritanceControl = ( { value = 'inherit', onChange } ) => (
    <SegmentedControl
        label={ <span className="cw-label-with-help">
            <span>{ __( 'Child Block Inheritance', 'cinderwell' ) }</span>
            <HelpTooltip text={ __( 'Inherit lets this container control the background and text color of nested Cinderwell blocks. Keep own preserves each child block’s background settings.', 'cinderwell' ) } />
        </span> }
        value={ value }
        options={ [
            { value: 'inherit', label: __( 'Inherit', 'cinderwell' ) },
            { value: 'individual', label: __( 'Keep own', 'cinderwell' ) },
        ] }
        onChange={ onChange }
    />
);

const responsiveDevices = [
    { value: 'desktop', editorValue: 'Desktop', label: __( 'Desktop', 'cinderwell' ) },
    { value: 'tablet', editorValue: 'Tablet', label: __( 'Tablet', 'cinderwell' ) },
    { value: 'mobile', editorValue: 'Mobile', label: __( 'Mobile', 'cinderwell' ) },
];

const ResponsiveDeviceIcon = ( { device } ) => {
    if ( device === 'mobile' ) {
        return <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="1.5" fill="none" stroke="currentColor" strokeWidth="1.8"/><circle cx="12" cy="18.5" r="0.8" fill="currentColor"/></svg>;
    }
    if ( device === 'tablet' ) {
        return <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="4.5" y="2.5" width="15" height="19" rx="1.5" fill="none" stroke="currentColor" strokeWidth="1.8"/><circle cx="12" cy="18.5" r="0.8" fill="currentColor"/></svg>;
    }
    return <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="2.5" y="3.5" width="19" height="13" rx="1.5" fill="none" stroke="currentColor" strokeWidth="1.8"/><path d="M8 20.5h8M10 16.5v4M14 16.5v4" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"/></svg>;
};

const useResponsiveDevice = () => {
    const [ fallbackBreakpoint, setFallbackBreakpoint ] = useState( 'desktop' );
    const editorDevice = useSelect( ( select ) => select( 'core/editor' )?.getDeviceType?.(), [] );
    const editorActions = useDispatch( 'core/editor' );
    const editorBreakpoint = responsiveDevices.find( ( device ) => device.editorValue === editorDevice )?.value;
    const breakpoint = editorBreakpoint || fallbackBreakpoint;
    const changeDevice = ( device ) => {
        setFallbackBreakpoint( device.value );
        editorActions.setDeviceType?.( device.editorValue );
    };

    return { breakpoint, changeDevice };
};

const ResponsiveDeviceControl = ( { breakpoint, onChange } ) => {
    const activeDevice = responsiveDevices.find( ( device ) => device.value === breakpoint ) || responsiveDevices[ 0 ];

    return <Dropdown
        className="cw-responsive-device"
        popoverProps={ { placement: 'left-start' } }
        renderToggle={ ( { isOpen, onToggle } ) => <Button className="cw-responsive-device__toggle" icon={ <ResponsiveDeviceIcon device={ breakpoint } /> } label={ activeDevice.label } aria-expanded={ isOpen } onClick={ onToggle } /> }
        renderContent={ ( { onClose } ) => <div className="cw-responsive-device__menu">{ responsiveDevices.map( ( device ) => <Button key={ device.value } className={ device.value === breakpoint ? 'is-active' : '' } icon={ <ResponsiveDeviceIcon device={ device.value } /> } onClick={ () => { onChange( device ); onClose(); } }>{ device.label }</Button> ) }</div> }
    />;
};

/**
 * One property editor shared across Gutenberg's desktop, tablet, and mobile
 * canvas contexts. Tablet and mobile may retain curated `auto` behavior.
 */
export const ResponsiveSegmentedControl = ( { label, values, options, onChange, autoHelp = {} } ) => {
    const { breakpoint, changeDevice } = useResponsiveDevice();
    const breakpointOptions = typeof options === 'function' ? options( breakpoint ) : options;
    const currentValue = values[ breakpoint ] || ( breakpoint === 'desktop' ? breakpointOptions[ 0 ]?.value : 'auto' );

    return (
        <div className="cw-responsive-control">
            <div className="cw-responsive-control__header">
                { label && <span className="cw-field__label">{ label }</span> }
                <ResponsiveDeviceControl breakpoint={ breakpoint } onChange={ changeDevice } />
            </div>
            <SegmentedControl value={ currentValue } options={ breakpointOptions } onChange={ ( value ) => onChange( breakpoint, value ) } />
            { currentValue === 'auto' && autoHelp[ breakpoint ] && <p className="cw-responsive-control__help">{ autoHelp[ breakpoint ] }</p> }
        </div>
    );
};

const spacingScaleOptions = [
    { value: 'none', label: __( 'None', 'cinderwell' ) },
    { value: 'xs', label: __( 'XS', 'cinderwell' ) },
    { value: 'sm', label: __( 'SM', 'cinderwell' ) },
    { value: 'md', label: __( 'MD', 'cinderwell' ) },
    { value: 'lg', label: __( 'LG', 'cinderwell' ) },
    { value: 'xl', label: __( 'XL', 'cinderwell' ) },
];

const SpacingLinkIcon = () => <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M9.5 14.5l5-5M7.5 16.5H6a4 4 0 010-8h3M16.5 7.5H18a4 4 0 010 8h-3" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"/></svg>;

export const ResponsiveSpacingControl = ( { attributes, setAttributes } ) => {
    const { breakpoint, changeDevice } = useResponsiveDevice();
    const responsive = attributes.spacingResponsive || {};
    const saved = responsive[ breakpoint ] || {};
    const fallback = breakpoint === 'desktop' ? 'md' : 'auto';
    const top = saved.top || fallback;
    const bottom = saved.bottom || top;
    const linked = saved.linked !== false;
    const options = breakpoint === 'desktop' ? spacingScaleOptions : [ { value: 'auto', label: __( 'Auto', 'cinderwell' ) }, ...spacingScaleOptions ];
    const save = ( next ) => setAttributes( { spacingResponsive: { ...responsive, [ breakpoint ]: { linked, top, bottom, ...next } } } );
    const setLinked = () => save( linked ? { linked: false } : { linked: true, bottom: top } );
    const setTop = ( value ) => save( linked ? { top: value, bottom: value } : { top: value } );

    return <div className="cw-responsive-control cw-responsive-spacing">
        <div className="cw-responsive-control__header">
            <span className="cw-field__label">{ __( 'Spacing', 'cinderwell' ) }</span>
            <div className="cw-responsive-control__actions">
                <Button className={ `cw-spacing-link${ linked ? ' is-active' : '' }` } icon={ <SpacingLinkIcon /> } label={ linked ? __( 'Unlink top and bottom spacing', 'cinderwell' ) : __( 'Link top and bottom spacing', 'cinderwell' ) } aria-pressed={ linked } onClick={ setLinked } />
                <ResponsiveDeviceControl breakpoint={ breakpoint } onChange={ changeDevice } />
            </div>
        </div>
        { linked ? <SegmentedControl value={ top } options={ options } onChange={ setTop } /> : <>
            <SegmentedControl label={ __( 'Top', 'cinderwell' ) } value={ top } options={ options } onChange={ setTop } />
            <SegmentedControl label={ __( 'Bottom', 'cinderwell' ) } value={ bottom } options={ options } onChange={ ( value ) => save( { bottom: value } ) } />
        </> }
        { breakpoint !== 'desktop' && linked && top === 'auto' && <p className="cw-responsive-control__help">{ __( 'Auto inherits spacing from the previous breakpoint.', 'cinderwell' ) }</p> }
    </div>;
};

export const getSpacingClassName = ( attributes = {} ) => Object.entries( attributes.spacingResponsive || {} ).reduce( ( className, [ breakpoint, values ] ) => {
    return [ 'top', 'bottom' ].reduce( ( nextClassName, edge ) => {
        const value = values?.[ edge ];
        if ( ! value || value === 'auto' ) return nextClassName;
        return `${ nextClassName } cw-spacing-${ breakpoint }-${ edge }-${ value }`;
    }, className );
}, '' );

/**
 * Emit optional responsive classes without changing legacy markup when values
 * are unset. The same helper can support columns, spacing, alignment, and more.
 */
export const getResponsiveModifierClassName = ( blockClass, property, values = {} ) => Object.entries( values ).reduce( ( className, [ breakpoint, value ] ) => {
    if ( ! value || value === 'auto' ) return className;
    return `${ className } ${ blockClass }--${ breakpoint }-${ property }-${ value }`;
}, '' );

/**
 * Token color control — shared swatches for backgrounds, typography, and icons.
 */
export const ColorTokenControl = ( { label = __( 'Color', 'cinderwell' ), value, options, onChange } ) => (
    <div className="cw-field">
        { label && <span className="cw-field__label">{ label }</span> }
        <div className="cw-swatches">
            { options.map( ( option ) => {
                const slug = option.slug || option.value;
                return (
                    <button
                        type="button"
                        key={ slug }
                        className={ `cw-swatch${ value === slug ? ' is-active' : '' }` }
                        style={ { background: option.color } }
                        data-slug={ slug }
                        onClick={ () => onChange( slug ) }
                        aria-label={ option.label || slug }
                        aria-pressed={ value === slug }
                        title={ option.label || slug }
                    />
                );
            } ) }
        </div>
    </div>
);

const backgroundFitOptions = [
    { value: 'cover', label: __( 'Cover', 'cinderwell' ) },
    { value: 'contain', label: __( 'Contain', 'cinderwell' ) },
];

const backgroundPositionOptions = [
    { value: 'center', label: __( 'Center', 'cinderwell' ) },
    { value: 'top', label: __( 'Top', 'cinderwell' ) },
    { value: 'bottom', label: __( 'Bottom', 'cinderwell' ) },
    { value: 'left', label: __( 'Left', 'cinderwell' ) },
    { value: 'right', label: __( 'Right', 'cinderwell' ) },
    { value: 'top-left', label: __( 'Top left', 'cinderwell' ) },
    { value: 'top-right', label: __( 'Top right', 'cinderwell' ) },
    { value: 'bottom-left', label: __( 'Bottom left', 'cinderwell' ) },
    { value: 'bottom-right', label: __( 'Bottom right', 'cinderwell' ) },
];

const backgroundOverlayOptions = [
    { value: 'none', label: __( 'None', 'cinderwell' ) },
    { value: 'soft', label: __( 'Soft', 'cinderwell' ) },
    { value: 'medium', label: __( 'Medium', 'cinderwell' ) },
    { value: 'strong', label: __( 'Strong', 'cinderwell' ) },
];

const backgroundPositions = {
    top: 'center top',
    bottom: 'center bottom',
    left: 'left center',
    right: 'right center',
    'top-left': 'left top',
    'top-right': 'right top',
    'bottom-left': 'left bottom',
    'bottom-right': 'right bottom',
};

/**
 * Adds an optional background image and overlay class without changing markup
 * for blocks that still use the automatic/no-image default.
 */
export const getBackgroundImageProps = ( props, attributes ) => {
    const spacingClassName = getSpacingClassName( attributes );
    const responsiveProps = spacingClassName ? { ...props, className: `${ props.className || '' }${ spacingClassName }` } : props;
    if ( ! attributes?.backgroundImage || ! attributes.backgroundImageUrl ) return responsiveProps;
    return {
        ...responsiveProps,
        className: `${ responsiveProps.className || '' } cw-has-background-image cw-background-overlay-${ attributes.backgroundOverlay || 'none' }`,
        style: {
            ...responsiveProps.style,
            backgroundImage: `url(${ attributes.backgroundImageUrl })`,
            backgroundSize: attributes.backgroundImageFit || 'cover',
            backgroundPosition: backgroundPositions[ attributes.backgroundImagePosition ] || 'center center',
            backgroundRepeat: 'no-repeat',
        },
    };
};

export const BackgroundImageControl = ( { imageId = 0, imageUrl = '', fit = 'cover', position = 'center', overlay = 'none', onChange } ) => {
    const [ isOpen, setIsOpen ] = useState( false );
    const [ anchor, setAnchor ] = useState( null );
    const close = () => { setIsOpen( false ); setAnchor( null ); };
    const hasImage = imageId > 0 && imageUrl;
    const overlayLabel = backgroundOverlayOptions.find( ( option ) => option.value === overlay )?.label;

    return <MediaUpload value={ imageId } allowedTypes={ [ 'image' ] } onSelect={ ( media ) => onChange( { imageId: media.id, imageUrl: media.url || media.source_url || '' } ) } render={ ( { open } ) => <div className="cw-background-image-control">
            { ! hasImage ? <MediaUploadCheck><Button variant="secondary" className="cw-background-image-control__add" onClick={ open }>+ { __( 'Add background image', 'cinderwell' ) }</Button></MediaUploadCheck> : <>
                <button type="button" className="cw-chip cw-background-image-control__trigger" onClick={ ( event ) => { if ( isOpen ) close(); else { setAnchor( event.currentTarget ); setIsOpen( true ); } } } aria-expanded={ isOpen }>
                    <span aria-hidden="true">▧</span><span>{ __( 'Image settings', 'cinderwell' ) }</span><span className="cw-background-image-control__status">{ overlayLabel }</span>
                </button>
                { isOpen && <Popover anchor={ anchor } onClose={ close } placement="left-start" offset={ 28 } flip={ false } shift className="cw-popover cw-background-image-popover"><div className="cw-popover__inner">
                <div className="cw-popover__head"><span className="cw-popover__title">{ __( 'Background image', 'cinderwell' ) }</span><button type="button" className="cw-popover__close" onClick={ close } aria-label={ __( 'Close', 'cinderwell' ) }>&times;</button></div>
                <img className="cw-background-image-control__preview" src={ imageUrl } alt="" />
                <div className="cw-popover__actions"><MediaUploadCheck><Button variant="secondary" onClick={ open }>{ __( 'Replace image', 'cinderwell' ) }</Button></MediaUploadCheck><Button variant="tertiary" isDestructive onClick={ () => { close(); onChange( { imageId: 0, imageUrl: '' } ); } }>{ __( 'Remove', 'cinderwell' ) }</Button></div>
                <SelectControl label={ __( 'Fit', 'cinderwell' ) } value={ fit } options={ backgroundFitOptions } onChange={ ( value ) => onChange( { fit: value } ) } /><SelectControl label={ __( 'Position', 'cinderwell' ) } value={ position } options={ backgroundPositionOptions } onChange={ ( value ) => onChange( { position: value } ) } /><SegmentedControl label={ __( 'Dark overlay', 'cinderwell' ) } value={ overlay } options={ backgroundOverlayOptions } onChange={ ( value ) => onChange( { overlay: value } ) } />
                <div className="cw-popover__footer"><Button variant="primary" onClick={ close }>{ __( 'Done', 'cinderwell' ) }</Button></div>
                </div></Popover> }
            </> }
        </div> } />;
};

/**
 * Icon group — for text alignment with arrow icons.
 */
export const IconGroup = ( { label, value, options, onChange } ) => {
    return (
        <div className="cw-field">
            { label && <label className="cw-field__label">{ label }</label> }
            <div className="cw-icon-group">
                { options.map( ( opt ) => (
                    <button
                        type="button"
                        key={ opt.value }
                        className={ value === opt.value ? 'is-active' : '' }
                        onClick={ () => onChange( opt.value ) }
                        title={ opt.label }
                        aria-label={ opt.label }
                        aria-pressed={ value === opt.value }
                    >
                        { opt.icon }
                    </button>
                ) ) }
            </div>
        </div>
    );
};

/**
 * Layout panel — spacing, width, alignment.
 */
export const LayoutControls = ( { attributes, setAttributes, showAlignment = false } ) => {
    return (
        <>
        <PanelBody title={ __( 'Layout', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
            <ResponsiveSpacingControl attributes={ attributes } setAttributes={ setAttributes } />
            <SegmentedControl
                label={ __( 'Content Width', 'cinderwell' ) }
                value={ attributes.width || 'standard' }
                options={ [
                    { value: 'narrow', label: __( 'Narrow', 'cinderwell' ) },
                    { value: 'standard', label: __( 'Standard', 'cinderwell' ) },
                    { value: 'wide', label: __( 'Wide', 'cinderwell' ) },
                    { value: 'full', label: __( 'Full', 'cinderwell' ) },
                ] }
                onChange={ ( v ) => setAttributes( { width: v } ) }
            />
            { showAlignment && (
                <IconGroup
                    label={ __( 'Alignment', 'cinderwell' ) }
                    value={ attributes.alignment || 'left' }
                    options={ [
                        { value: 'left', label: __( 'Left', 'cinderwell' ), icon: '←' },
                        { value: 'center', label: __( 'Center', 'cinderwell' ), icon: '↔' },
                        { value: 'right', label: __( 'Right', 'cinderwell' ), icon: '→' },
                    ] }
                    onChange={ ( v ) => setAttributes( { alignment: v } ) }
                />
            ) }
        </PanelBody>
        </>
    );
};

/**
 * Background panel — square swatches mapped to design tokens.
 */
export const BackgroundControls = ( { value, onChange, attributes, setAttributes, media, children } ) => {
    const swatches = [
        { slug: 'white', color: 'var(--cw-color-white, #ffffff)', fallback: '#ffffff' },
        { slug: 'light', color: 'var(--cw-color-light, #f8f5ef)', fallback: '#f8f5ef' },
        { slug: 'dark', color: 'var(--cw-color-dark, #1a1a1a)', fallback: '#1a1a1a' },
        { slug: 'brand', color: 'var(--cw-color-brand, #b84c00)', fallback: '#b84c00' },
    ];

    const backgroundMedia = media || ( attributes && setAttributes ? {
        imageId: attributes.backgroundImage || 0,
        imageUrl: attributes.backgroundImageUrl || '',
        fit: attributes.backgroundImageFit || 'cover',
        position: attributes.backgroundImagePosition || 'center',
        overlay: attributes.backgroundOverlay || 'none',
        onChange: ( changes ) => setAttributes( {
            ...( Object.prototype.hasOwnProperty.call( changes, 'imageId' ) ? { backgroundImage: changes.imageId } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'imageUrl' ) ? { backgroundImageUrl: changes.imageUrl } : {} ),
            ...( changes.fit ? { backgroundImageFit: changes.fit } : {} ),
            ...( changes.position ? { backgroundImagePosition: changes.position } : {} ),
            ...( changes.overlay ? { backgroundOverlay: changes.overlay } : {} ),
        } ),
    } : null );

    return (
        <PanelBody title={ __( 'Background', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
            <ColorTokenControl value={ value } options={ swatches } onChange={ onChange } />
            { backgroundMedia && <BackgroundImageControl { ...backgroundMedia } /> }
            { children && <div className="cw-background-media">{ children }</div> }
        </PanelBody>
    );
};

/**
 * Move one item within an array without mutating block attributes.
 */
export const moveArrayItem = ( items, fromIndex, toIndex ) => {
    if ( fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= items.length || toIndex >= items.length ) {
        return items;
    }

    const reordered = [ ...items ];
    const [ moved ] = reordered.splice( fromIndex, 1 );
    reordered.splice( toIndex, 0, moved );
    return reordered;
};

/**
 * Shared draggable inspector card with keyboard-accessible move controls.
 */
export const SortableItemCard = ( { index, listId, label, children, onMove, onRemove } ) => {
    const [ isDragOver, setIsDragOver ] = useState( false );
    const dragType = 'application/x-cinderwell-item';

    const onDragStart = ( event ) => {
        const payload = JSON.stringify( { listId, index } );
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData( dragType, payload );
        event.dataTransfer.setData( 'text/plain', payload );
    };

    const onDrop = ( event ) => {
        event.preventDefault();
        setIsDragOver( false );

        try {
            const payload = JSON.parse( event.dataTransfer.getData( dragType ) || event.dataTransfer.getData( 'text/plain' ) );
            if ( payload.listId === listId && Number.isInteger( payload.index ) ) {
                onMove( payload.index, index );
            }
        } catch ( error ) {
            // Ignore unrelated or malformed drag data.
        }
    };

    return (
        <div
            className={ `cw-item-card cw-sortable-item${ isDragOver ? ' is-drag-over' : '' }` }
            onDragOver={ ( event ) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                setIsDragOver( true );
            } }
            onDragLeave={ () => setIsDragOver( false ) }
            onDrop={ onDrop }
        >
            <div className="cw-sortable-item__header">
                <div className="cw-sortable-item__controls">
                    <button
                        type="button"
                        className="cw-sortable-item__handle"
                        draggable
                        onDragStart={ onDragStart }
                        aria-label={ `${ __( 'Drag to reorder', 'cinderwell' ) }: ${ label }` }
                        title={ __( 'Drag to reorder', 'cinderwell' ) }
                    >
                        <span aria-hidden="true">⠿</span>
                    </button>
                </div>
                <span className="cw-item-card__label">{ label }</span>
                <button type="button" className="cw-item-card__remove" onClick={ onRemove } aria-label={ `${ __( 'Remove', 'cinderwell' ) }: ${ label }` }>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div className="cw-item-card__fields">
                { children }
            </div>
        </div>
    );
};

/**
 * Return optional typography modifier classes without changing legacy markup
 * when both controls use their automatic defaults.
 */
export const getTypographyClassName = ( attributes ) => {
    const classes = [];

    if ( attributes.textSize && attributes.textSize !== 'auto' ) {
        classes.push( `cinderwell-text-size-${ attributes.textSize }` );
    }

    if ( attributes.textColor && attributes.textColor !== 'auto' ) {
        classes.push( `cinderwell-text-color-${ attributes.textColor }` );
    }

    return classes.length ? ` ${ classes.join( ' ' ) }` : '';
};

/**
 * Return typography modifier classes for one semantic text part.
 */
export const getTextStyleClassName = ( attributes, part ) => {
    const style = attributes.textStyles?.[ part ] || {};
    const classes = [];

    if ( style.size && style.size !== 'auto' ) {
        classes.push( `cinderwell-text-size-${ style.size }` );
    }

    if ( style.color && style.color !== 'auto' ) {
        classes.push( `cinderwell-text-color-${ style.color }` );
    }

    return classes.length ? ` ${ classes.join( ' ' ) }` : '';
};

export const getHeadingTagName = ( level, fallback = 2 ) => {
    const normalized = Number( level );
    return `h${ normalized >= 1 && normalized <= 6 ? normalized : fallback }`;
};

const typographySizes = [
    { value: 'auto', label: __( 'Auto', 'cinderwell' ) },
    { value: 'sm', label: __( 'Small', 'cinderwell' ) },
    { value: 'md', label: __( 'Medium', 'cinderwell' ) },
    { value: 'lg', label: __( 'Large', 'cinderwell' ) },
];

const typographyColors = [
    { slug: 'auto', label: __( 'Automatic', 'cinderwell' ), color: 'linear-gradient(135deg, #ffffff 0 50%, #1a1a1a 50%)' },
    { slug: 'text', label: __( 'Text', 'cinderwell' ), color: 'var(--cw-color-text, #1a1a1a)' },
    { slug: 'brand', label: __( 'Brand', 'cinderwell' ), color: 'var(--cw-color-brand, #b84c00)' },
    { slug: 'dark', label: __( 'Dark', 'cinderwell' ), color: 'var(--cw-color-dark, #1a1a1a)' },
    { slug: 'light', label: __( 'Light', 'cinderwell' ), color: 'var(--cw-color-light, #f8f5ef)' },
    { slug: 'white', label: __( 'White', 'cinderwell' ), color: 'var(--cw-color-white, #ffffff)' },
];

const getSafeTypographyColors = ( background = 'auto' ) => {
    if ( 'dark' === background || 'brand' === background ) {
        return typographyColors.filter( ( option ) => [ 'auto', 'light', 'white' ].includes( option.slug ) );
    }

    if ( 'white' === background || 'light' === background ) {
        return typographyColors.filter( ( option ) => [ 'auto', 'text', 'dark', 'brand' ].includes( option.slug ) );
    }

    return typographyColors.filter( ( option ) => 'auto' === option.slug );
};

const TypographyFields = ( { size, color, background, onSizeChange, onColorChange } ) => (
    <>
        <SegmentedControl
            label={ __( 'Size', 'cinderwell' ) }
            value={ size || 'auto' }
            options={ typographySizes }
            onChange={ onSizeChange }
        />
        <ColorTokenControl value={ color || 'auto' } options={ getSafeTypographyColors( background ) } onChange={ onColorChange } />
    </>
);

/**
 * Text panel — a compact, token-only type scale and color palette.
 */
export const TypographyControls = ( { attributes, setAttributes, sections = [] } ) => {
    const updatePart = ( part, property, value ) => {
        const textStyles = attributes.textStyles || {};
        setAttributes( {
            textStyles: {
                ...textStyles,
                [ part ]: {
                    ...( textStyles[ part ] || {} ),
                    [ property ]: value,
                },
            },
        } );
    };

    return (
        <>
        <PanelBody title={ __( 'Text', 'cinderwell' ) } initialOpen={ false } className="cw-panel">
            <span className="cw-type-label">{ __( 'All text', 'cinderwell' ) }</span>
            <TypographyFields
                size={ attributes.textSize }
                color={ attributes.textColor }
                background={ attributes.background || 'auto' }
                onSizeChange={ ( value ) => setAttributes( { textSize: value } ) }
                onColorChange={ ( value ) => setAttributes( { textColor: value } ) }
            />
            { Object.prototype.hasOwnProperty.call( attributes, 'headingLevel' ) && (
                <SelectControl
                    label={ __( 'Section heading level', 'cinderwell' ) }
                    value={ String( attributes.headingLevel ) }
                    options={ [ 1, 2, 3, 4, 5, 6 ].map( ( level ) => ( { label: `H${ level }`, value: String( level ) } ) ) }
                    onChange={ ( value ) => setAttributes( { headingLevel: Number( value ) } ) }
                />
            ) }
            { Object.prototype.hasOwnProperty.call( attributes, 'itemHeadingLevel' ) && (
                <SelectControl
                    label={ __( 'Item heading level', 'cinderwell' ) }
                    value={ String( attributes.itemHeadingLevel ) }
                    options={ [ 2, 3, 4, 5, 6 ].map( ( level ) => ( { label: `H${ level }`, value: String( level ) } ) ) }
                    onChange={ ( value ) => setAttributes( { itemHeadingLevel: Number( value ) } ) }
                />
            ) }
            <div className="cw-type-parts">
                { sections.filter( ( section ) => section.enabled !== false ).map( ( section ) => {
                    const style = attributes.textStyles?.[ section.key ] || {};
                    const hasOverride = ( style.size && style.size !== 'auto' ) || ( style.color && style.color !== 'auto' );

                    return (
                        <details className="cw-type-part" key={ section.key }>
                            <summary>
                                <span>{ section.label }</span>
                                <span className="cw-type-part__status">{ hasOverride ? __( 'Custom', 'cinderwell' ) : __( 'Auto', 'cinderwell' ) }</span>
                            </summary>
                            <div className="cw-type-part__controls">
                                <TypographyFields
                                    size={ style.size }
                                    color={ style.color }
                                    background={ section.background || attributes.background || 'auto' }
                                    onSizeChange={ ( value ) => updatePart( section.key, 'size', value ) }
                                    onColorChange={ ( value ) => updatePart( section.key, 'color', value ) }
                                />
                            </div>
                        </details>
                    );
                } ) }
            </div>
        </PanelBody>
        <ConditionsPanel attributes={ attributes } setAttributes={ setAttributes } />
        </>
    );
};

/**
 * Button repeater — pill-shaped chips with popover editing.
 */
export const ButtonRepeater = ( { buttons = [], onChange } ) => {
    const MAX = 3;
    const [ editingIndex, setEditingIndex ] = useState( null );
    const [ popoverAnchor, setPopoverAnchor ] = useState( null );

    const add = ( event ) => {
        if ( buttons.length >= MAX ) return;
        onChange( [ ...buttons, { text: '', url: '', urlDynamic: {}, opensInNewTab: false, variant: 'primary', size: 'md' } ] );
        setEditingIndex( buttons.length );
        setPopoverAnchor( event.currentTarget.parentElement );
    };
    const update = ( i, field, val ) => {
        onChange( buttons.map( ( b, idx ) => idx === i ? { ...b, [ field ]: val } : b ) );
    };
    const updateFields = ( i, changes ) => {
        onChange( buttons.map( ( b, idx ) => idx === i ? { ...b, ...changes } : b ) );
    };
    const remove = ( i ) => {
        onChange( buttons.filter( ( _, idx ) => idx !== i ) );
        setEditingIndex( null );
        setPopoverAnchor( null );
    };

    return (
        <div className="cw-chips">
            { buttons.map( ( btn, i ) => (
                <div key={ i } className="cw-chip-wrap">
                    <button
                        type="button"
                        className="cw-chip"
                        onClick={ ( event ) => {
                            const isClosing = editingIndex === i;
                            setEditingIndex( isClosing ? null : i );
                            setPopoverAnchor( isClosing ? null : event.currentTarget );
                        } }
                    >
                        <span className="cw-chip__dot" />
                        { btn.text || __( 'Button', 'cinderwell' ) }
                    </button>
                    { editingIndex === i && (
                        <Popover
                            anchor={ popoverAnchor }
                            onClose={ () => { setEditingIndex( null ); setPopoverAnchor( null ); } }
                            placement="left-start"
                            offset={ 28 }
                            flip={ false }
                            shift
                            className="cw-popover"
                        >
                            <div className="cw-popover__inner">
                                <div className="cw-popover__head">
                                    <span className="cw-popover__title">{ __( 'Edit button', 'cinderwell' ) }</span>
                                    <button type="button" className="cw-popover__close" onClick={ () => { setEditingIndex( null ); setPopoverAnchor( null ); } } aria-label={ __( 'Close', 'cinderwell' ) }>&times;</button>
                                </div>
                                <LinkSettingsControl
                                    url={ btn.url }
                                    opensInNewTab={ Boolean( btn.opensInNewTab ) }
                                    dynamicData={ btn.urlDynamic || {} }
                                    onChange={ ( changes ) => updateFields( i, {
                                        ...( Object.prototype.hasOwnProperty.call( changes, 'url' ) ? { url: changes.url } : {} ),
                                        ...( Object.prototype.hasOwnProperty.call( changes, 'opensInNewTab' ) ? { opensInNewTab: changes.opensInNewTab } : {} ),
                                        ...( Object.prototype.hasOwnProperty.call( changes, 'dynamicData' ) ? { urlDynamic: changes.dynamicData } : {} ),
                                    } ) }
                                />
                                <SegmentedControl
                                    label={ __( 'Style', 'cinderwell' ) }
                                    value={ btn.variant || 'primary' }
                                    options={ [
                                        { value: 'primary', label: __( 'Primary', 'cinderwell' ) },
                                        { value: 'secondary', label: __( 'Secondary', 'cinderwell' ) },
                                        { value: 'ghost', label: __( 'Ghost', 'cinderwell' ) },
                                        { value: 'link', label: __( 'Link', 'cinderwell' ) },
                                    ] }
                                    onChange={ ( v ) => update( i, 'variant', v ) }
                                />
                                <SegmentedControl
                                    label={ __( 'Size', 'cinderwell' ) }
                                    value={ btn.size || 'md' }
                                    options={ [
                                        { value: 'sm', label: __( 'Small', 'cinderwell' ) },
                                        { value: 'md', label: __( 'Medium', 'cinderwell' ) },
                                        { value: 'lg', label: __( 'Large', 'cinderwell' ) },
                                    ] }
                                    onChange={ ( v ) => update( i, 'size', v ) }
                                />
                                <div className="cw-popover__actions">
                                    <button type="button" className="cw-btn-secondary" onClick={ () => { setEditingIndex( null ); setPopoverAnchor( null ); } }>{ __( 'Done', 'cinderwell' ) }</button>
                                    <button type="button" className="cw-btn-danger" onClick={ () => remove( i ) }>{ __( 'Remove', 'cinderwell' ) }</button>
                                </div>
                            </div>
                        </Popover>
                    ) }
                </div>
            ) ) }
            { buttons.length < MAX && (
                <button type="button" className="cw-chip cw-chip--add" onClick={ add }>+ { __( 'Add', 'cinderwell' ) }</button>
            ) }
        </div>
    );
};

/**
 * Button save — renders button array on the frontend.
 */
export const ButtonSave = ( { buttons = [], onChange } ) => {
    if ( ! buttons.length ) return null;
    const updateText = ( index, text ) => onChange( buttons.map( ( button, buttonIndex ) => buttonIndex === index ? { ...button, text } : button ) );
    const updateLink = ( index, changes ) => onChange( buttons.map( ( button, buttonIndex ) => buttonIndex === index ? {
        ...button,
        ...( Object.prototype.hasOwnProperty.call( changes, 'url' ) ? { url: changes.url } : {} ),
        ...( Object.prototype.hasOwnProperty.call( changes, 'opensInNewTab' ) ? { opensInNewTab: changes.opensInNewTab } : {} ),
        ...( Object.prototype.hasOwnProperty.call( changes, 'dynamicData' ) ? { urlDynamic: changes.dynamicData } : {} ),
    } : button ) );

    return (
        <div className="cinderwell-buttons">
            { buttons.map( ( btn, i ) => onChange ? (
                <EditableLink
                    key={ i }
                    tagName="a"
                    className={ `btn btn--${ btn.variant || 'primary' } btn--${ btn.size || 'md' }` }
                    value={ btn.text }
                    url={ btn.url }
                    opensInNewTab={ Boolean( btn.opensInNewTab ) }
                    dynamicData={ btn.urlDynamic || {} }
                    onTextChange={ ( text ) => updateText( i, text ) }
                    onLinkChange={ ( changes ) => updateLink( i, changes ) }
                    contextLabel={ __( 'Button destination', 'cinderwell' ) }
                    placeholder={ __( 'Button text…', 'cinderwell' ) }
                    allowedFormats={ [] }
                />
            ) : (
                <RichText.Content
                    key={ i }
                    tagName="a"
                    href={ btn.url || '#' }
                    target={ btn.opensInNewTab ? '_blank' : undefined }
                    rel={ btn.opensInNewTab ? 'noopener noreferrer' : undefined }
                    data-cw-url-source={ btn.urlDynamic?.source && btn.urlDynamic.source !== 'static' ? btn.urlDynamic.source : undefined }
                    data-cw-url-field={ btn.urlDynamic?.field || undefined }
                    data-cw-url-fallback={ btn.urlDynamic?.fallback || undefined }
                    className={ `btn btn--${ btn.variant || 'primary' } btn--${ btn.size || 'md' }` }
                    value={ btn.text }
                />
            ) ) }
        </div>
    );
};
