import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Popover, SelectControl, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

const fitOptions = [
    { value: 'auto', label: __( 'Automatic', 'cinderwell' ) },
    { value: 'cover', label: __( 'Cover', 'cinderwell' ) },
    { value: 'contain', label: __( 'Contain', 'cinderwell' ) },
];

const positionOptions = [
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

const aspectOptions = [
    { value: 'auto', label: __( 'Original', 'cinderwell' ) },
    { value: 'square', label: __( 'Square', 'cinderwell' ) },
    { value: 'landscape', label: __( 'Landscape', 'cinderwell' ) },
    { value: 'wide', label: __( 'Wide', 'cinderwell' ) },
    { value: 'portrait', label: __( 'Portrait', 'cinderwell' ) },
];

export const ImageSettingsFields = ( {
    alt = '',
    fit = 'auto',
    position = 'center',
    aspect = 'auto',
    showAlt = true,
    showAspect = true,
    onAltChange,
    onFitChange,
    onPositionChange,
    onAspectChange,
} ) => (
    <>
        { showAlt && (
            <TextControl
                label={ __( 'Alt text', 'cinderwell' ) }
                value={ alt }
                onChange={ onAltChange }
                help={ __( 'Describe meaningful images. Leave blank when decorative.', 'cinderwell' ) }
            />
        ) }
        <SelectControl label={ __( 'Fit', 'cinderwell' ) } value={ fit || 'auto' } options={ fitOptions } onChange={ onFitChange } />
        <SelectControl label={ __( 'Position', 'cinderwell' ) } value={ position || 'center' } options={ positionOptions } onChange={ onPositionChange } />
        { showAspect && <SelectControl label={ __( 'Aspect ratio', 'cinderwell' ) } value={ aspect || 'auto' } options={ aspectOptions } onChange={ onAspectChange } /> }
    </>
);

/**
 * Compact canvas overlay for adding, replacing, and removing block images.
 * The parent must use the `cw-image-control-host` class.
 */
export const ImageOverlayControls = ( { imageId = 0, label, onSelect, onRemove } ) => {
    const hasImage = imageId > 0;
    const imageLabel = label || __( 'image', 'cinderwell' );

    return (
        <div className={ `cw-image-control-overlay${ hasImage ? '' : ' is-empty' }` }>
            <MediaUploadCheck>
                <MediaUpload
                    value={ imageId }
                    allowedTypes={ [ 'image' ] }
                    onSelect={ onSelect }
                    render={ ( { open } ) => (
                        <button
                            type="button"
                            className="cw-image-control-overlay__button"
                            onClick={ open }
                            aria-label={ hasImage
                                ? sprintf( __( 'Replace %s', 'cinderwell' ), imageLabel )
                                : sprintf( __( 'Add %s', 'cinderwell' ), imageLabel ) }
                        >
                            <span aria-hidden="true">{ hasImage ? '↻' : '+' }</span>
                            { hasImage ? __( 'Replace', 'cinderwell' ) : __( 'Add image', 'cinderwell' ) }
                        </button>
                    ) }
                />
            </MediaUploadCheck>
            { hasImage && onRemove && (
                <button
                    type="button"
                    className="cw-image-control-overlay__button is-remove"
                    onClick={ onRemove }
                    aria-label={ sprintf( __( 'Remove %s', 'cinderwell' ), imageLabel ) }
                >
                    <span aria-hidden="true">×</span>
                    { __( 'Remove', 'cinderwell' ) }
                </button>
            ) }
        </div>
    );
};

/**
 * Collapsed sidebar control for non-content image settings.
 */
export const ImageSettingsControl = ( {
    alt = '',
    fit = 'auto',
    position = 'center',
    aspect = 'auto',
    label,
    showAlt = true,
    showAspect = true,
    onAltChange,
    onFitChange,
    onPositionChange,
    onAspectChange,
} ) => {
    const [ isOpen, setIsOpen ] = useState( false );
    const [ anchor, setAnchor ] = useState( null );
    const controlLabel = label || __( 'Image settings', 'cinderwell' );
    const hasCustomTreatment = ( fit && fit !== 'auto' ) || ( position && position !== 'center' ) || ( showAspect && aspect && aspect !== 'auto' );

    const close = () => {
        setIsOpen( false );
        setAnchor( null );
    };

    return (
        <div className="cw-image-settings-control">
            <button
                type="button"
                className="cw-chip cw-image-settings-control__trigger"
                onClick={ ( event ) => {
                    if ( isOpen ) {
                        close();
                    } else {
                        setAnchor( event.currentTarget );
                        setIsOpen( true );
                    }
                } }
                aria-expanded={ isOpen }
            >
                <span aria-hidden="true">◫</span>
                <span>{ controlLabel }</span>
                <span className="cw-image-settings-control__status">{ hasCustomTreatment ? __( 'Custom', 'cinderwell' ) : __( 'Auto', 'cinderwell' ) }</span>
            </button>
            { isOpen && (
                <Popover anchor={ anchor } onClose={ close } placement="left-start" offset={ 28 } flip={ false } shift className="cw-popover">
                    <div className="cw-popover__inner">
                        <div className="cw-popover__head">
                            <span className="cw-popover__title">{ controlLabel }</span>
                            <button type="button" className="cw-popover__close" onClick={ close } aria-label={ __( 'Close', 'cinderwell' ) }>&times;</button>
                        </div>
                        <ImageSettingsFields
                            alt={ alt }
                            fit={ fit }
                            position={ position }
                            aspect={ aspect }
                            showAlt={ showAlt }
                            showAspect={ showAspect }
                            onAltChange={ onAltChange }
                            onFitChange={ onFitChange }
                            onPositionChange={ onPositionChange }
                            onAspectChange={ onAspectChange }
                        />
                        <Button variant="secondary" size="compact" onClick={ close }>{ __( 'Done', 'cinderwell' ) }</Button>
                    </div>
                </Popover>
            ) }
        </div>
    );
};
