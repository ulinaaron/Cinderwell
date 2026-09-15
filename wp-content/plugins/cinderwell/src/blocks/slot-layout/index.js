import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BackgroundControls, getBackgroundImageProps, BlockIdentity, ButtonRepeater, ButtonSave, LayoutControls, ResponsiveSegmentedControl, getResponsiveModifierClassName, SortableItemCard, TypographyControls, getTextStyleClassName, getTypographyClassName, getHeadingTagName, moveArrayItem } from '../../shared/inspector-controls';
import { ImageOverlayControls, ImageSettingsControl } from '../../shared/image-controls';
import { ALLOWED_INLINE_FORMATS } from '../../shared/rich-text';
import { getImageClassName, getMediaUrl } from '../../shared/media';
import metadata from './block.json';

let slotCounter = 0;

const slotTypes = [
    { value: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
    { value: 'heading', label: __( 'Heading', 'cinderwell' ) },
    { value: 'text', label: __( 'Rich text', 'cinderwell' ) },
    { value: 'image', label: __( 'Image', 'cinderwell' ) },
    { value: 'buttons', label: __( 'Buttons', 'cinderwell' ) },
    { value: 'divider', label: __( 'Divider', 'cinderwell' ) },
    { value: 'note', label: __( 'Note', 'cinderwell' ) },
];

const textSlotTypes = [ 'eyebrow', 'heading', 'text', 'note' ];

const getSlotLabel = ( slot, index ) => slotTypes.find( ( type ) => type.value === slot.type )?.label || `${ __( 'Slot', 'cinderwell' ) } ${ index + 1 }`;

const SlotContent = ( { slot, attributes, onChange, onImageSelect, onImageRemove, isEditing = false } ) => {
    const typeClass = getTextStyleClassName( attributes, slot.id );

    switch ( slot.type ) {
        case 'eyebrow':
            return isEditing
                ? <RichText tagName="span" className={ `cinderwell-eyebrow${ typeClass }` } value={ slot.content } onChange={ ( value ) => onChange( 'content', value ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } />
                : <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ typeClass }` } value={ slot.content } />;
        case 'heading':
            return isEditing
                ? <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ typeClass }` } value={ slot.content } onChange={ ( value ) => onChange( 'content', value ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } />
                : <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ typeClass }` } value={ slot.content } />;
        case 'text':
            return isEditing
                ? <RichText tagName="div" className={ `cinderwell-slot-layout__text${ typeClass }` } value={ slot.content } onChange={ ( value ) => onChange( 'content', value ) } placeholder={ __( 'Write text…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                : <RichText.Content tagName="div" className={ `cinderwell-slot-layout__text${ typeClass }` } value={ slot.content } />;
        case 'image':
            if ( ! isEditing ) {
                return slot.imageUrl ? <img className={ `cinderwell-slot-layout__image${ getImageClassName( slot.imageFit, slot.imagePosition, slot.imageAspect ) }` } src={ slot.imageUrl } alt={ slot.imageAlt || '' } /> : null;
            }
            return slot.imageUrl ? (
                <div className="cw-image-control-host">
                    <img className={ `cinderwell-slot-layout__image${ getImageClassName( slot.imageFit, slot.imagePosition, slot.imageAspect ) }` } src={ slot.imageUrl } alt={ slot.imageAlt || '' } />
                    <ImageOverlayControls imageId={ slot.imageId } label={ __( 'slot image', 'cinderwell' ) } onSelect={ onImageSelect } onRemove={ onImageRemove } />
                </div>
            ) : (
                <div className="cinderwell-slot-layout__placeholder cw-image-control-host is-empty">
                    <span className="cw-image-control-placeholder">{ __( 'No image selected', 'cinderwell' ) }</span>
                    <ImageOverlayControls imageId={ 0 } label={ __( 'slot image', 'cinderwell' ) } onSelect={ onImageSelect } />
                </div>
            );
        case 'buttons':
            return isEditing
                ? <ButtonSave buttons={ slot.buttons || [] } onChange={ ( buttons ) => onChange( 'buttons', buttons ) } />
                : <ButtonSave buttons={ slot.buttons || [] } />;
        case 'divider':
            return <hr className="cinderwell-slot-layout__divider" />;
        case 'note':
            return isEditing
                ? <RichText tagName="p" className={ `cinderwell-footnote${ typeClass }` } value={ slot.content } onChange={ ( value ) => onChange( 'content', value ) } placeholder={ __( 'Note…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                : <RichText.Content tagName="p" className={ `cinderwell-footnote${ typeClass }` } value={ slot.content } />;
        default:
            return null;
    }
};

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes, clientId } ) => {
        const slots = attributes.slots || [];
        const [ nextType, setNextType ] = useState( 'text' );
        const responsiveColumnsClassName = getResponsiveModifierClassName( 'cinderwell-slot-layout', 'cols', { tablet: attributes.columnsTablet, mobile: attributes.columnsMobile } );
        const blockProps = useBlockProps( getBackgroundImageProps( { className: `cinderwell-slot-layout cinderwell-slot-layout--bg-${ attributes.background } cinderwell-slot-layout--cols-${ attributes.columns }${ responsiveColumnsClassName } cinderwell-slot-layout--gap-${ attributes.gap }${ getTypographyClassName( attributes ) }` }, attributes ) );
        const updateSlot = ( index, field, value ) => setAttributes( { slots: slots.map( ( slot, slotIndex ) => slotIndex === index ? { ...slot, [ field ]: value } : slot ) } );
        const removeSlot = ( index ) => setAttributes( { slots: slots.filter( ( _, slotIndex ) => slotIndex !== index ) } );
        const moveSlot = ( from, to ) => setAttributes( { slots: moveArrayItem( slots, from, to ) } );
        const addSlot = () => {
            slotCounter++;
            setAttributes( { slots: [ ...slots, { id: `slot-${ Date.now() }-${ slotCounter }`, type: nextType, span: '1', content: '', imageId: 0, imageUrl: '', imageAlt: '', imageFit: 'auto', imagePosition: 'center', imageAspect: 'auto', buttons: [] } ] } );
        };

        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="▦" title={ __( 'Slot Layout', 'cinderwell' ) } description={ __( 'Controlled flexible layout', 'cinderwell' ) } />
                    <PanelBody title={ `${ __( 'Slots', 'cinderwell' ) } (${ slots.length })` } initialOpen={ true } className="cw-panel cw-access-layout">
                        { slots.map( ( slot, index ) => (
                            <SortableItemCard
                                key={ slot.id || index }
                                index={ index }
                                total={ slots.length }
                                listId={ `${ clientId }-slots` }
                                label={ getSlotLabel( slot, index ) }
                                onMove={ moveSlot }
                                onRemove={ () => removeSlot( index ) }
                            >
                                <SelectControl label={ __( 'Slot type', 'cinderwell' ) } value={ slot.type } options={ slotTypes } onChange={ ( value ) => updateSlot( index, 'type', value ) } />
                                <SelectControl label={ __( 'Width', 'cinderwell' ) } value={ slot.span || '1' } options={ [
                                    { value: '1', label: __( 'One column', 'cinderwell' ) },
                                    { value: '2', label: __( 'Two columns', 'cinderwell' ) },
                                    { value: 'full', label: __( 'Full row', 'cinderwell' ) },
                                ] } onChange={ ( value ) => updateSlot( index, 'span', value ) } />
                                { slot.type === 'image' && (
                                    <ImageSettingsControl
                                        alt={ slot.imageAlt }
                                        fit={ slot.imageFit }
                                        position={ slot.imagePosition }
                                        aspect={ slot.imageAspect }
                                        label={ __( 'Image settings', 'cinderwell' ) }
                                        onAltChange={ ( value ) => updateSlot( index, 'imageAlt', value ) }
                                        onFitChange={ ( value ) => updateSlot( index, 'imageFit', value ) }
                                        onPositionChange={ ( value ) => updateSlot( index, 'imagePosition', value ) }
                                        onAspectChange={ ( value ) => updateSlot( index, 'imageAspect', value ) }
                                    />
                                ) }
                                { slot.type === 'buttons' && <ButtonRepeater buttons={ slot.buttons || [] } onChange={ ( value ) => updateSlot( index, 'buttons', value ) } /> }
                            </SortableItemCard>
                        ) ) }
                        <div className="cw-slot-layout__add">
                            <SelectControl label={ __( 'Add slot', 'cinderwell' ) } value={ nextType } options={ slotTypes } onChange={ setNextType } />
                            <Button variant="secondary" className="cw-add-item cw-add-item--compact" onClick={ addSlot }>+ { __( 'Add', 'cinderwell' ) }</Button>
                        </div>
                    </PanelBody>
                    <PanelBody title={ __( 'Grid', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-layout">
                        <ResponsiveSegmentedControl
                            label={ __( 'Columns', 'cinderwell' ) }
                            values={ { desktop: attributes.columns || '2', tablet: attributes.columnsTablet || 'auto', mobile: attributes.columnsMobile || 'auto' } }
                            options={ ( breakpoint ) => breakpoint === 'desktop' ? [ { value: '1', label: '1' }, { value: '2', label: '2' }, { value: '3', label: '3' } ] : [ { value: 'auto', label: __( 'Auto', 'cinderwell' ) }, { value: '1', label: '1' }, { value: '2', label: '2' }, { value: '3', label: '3' } ] }
                            autoHelp={ { tablet: __( 'Auto follows the desktop column count.', 'cinderwell' ), mobile: __( 'Auto stacks slots in one column.', 'cinderwell' ) } }
                            onChange={ ( breakpoint, value ) => setAttributes( breakpoint === 'desktop' ? { columns: value } : { [ breakpoint === 'tablet' ? 'columnsTablet' : 'columnsMobile' ]: value === 'auto' ? '' : value } ) }
                        />
                        <SelectControl label={ __( 'Gap', 'cinderwell' ) } value={ attributes.gap || 'sm' } options={ [
                            { value: 'none', label: __( 'None', 'cinderwell' ) },
                            { value: 'xs', label: __( 'Extra small', 'cinderwell' ) },
                            { value: 'sm', label: __( 'Small', 'cinderwell' ) },
                            { value: 'md', label: __( 'Medium', 'cinderwell' ) },
                            { value: 'lg', label: __( 'Large', 'cinderwell' ) },
                            { value: 'xl', label: __( 'Extra large', 'cinderwell' ) },
                        ] } onChange={ ( value ) => setAttributes( { gap: value } ) } />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( value ) => setAttributes( { background: value } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ slots.filter( ( slot ) => textSlotTypes.includes( slot.type ) ).map( ( slot, index ) => ( {
                        key: slot.id,
                        label: `${ getSlotLabel( slot, index ) } ${ index + 1 }`,
                    } ) ) } />
                </InspectorControls>
                <section { ...blockProps }>
                    <div className="cinderwell-slot-layout__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        <div className="cinderwell-slot-layout__grid">
                            { slots.map( ( slot, index ) => (
                                <div className={ `cinderwell-slot-layout__slot cinderwell-slot-layout__slot--span-${ slot.span || '1' }` } key={ slot.id || index }>
                                    <SlotContent
                                        slot={ slot }
                                        attributes={ attributes }
                                        isEditing
                                        onChange={ ( field, value ) => updateSlot( index, field, value ) }
                                        onImageSelect={ ( media ) => setAttributes( { slots: slots.map( ( current, slotIndex ) => slotIndex === index ? { ...current, imageId: media.id, imageUrl: getMediaUrl( media ), imageAlt: current.imageAlt || media.alt || '' } : current ) } ) }
                                        onImageRemove={ () => setAttributes( { slots: slots.map( ( current, slotIndex ) => slotIndex === index ? { ...current, imageId: 0, imageUrl: '', imageAlt: '' } : current ) } ) }
                                    />
                                </div>
                            ) ) }
                        </div>
                    </div>
                </section>
            </>
        );
    },
    save: ( { attributes } ) => {
        const slots = attributes.slots || [];
        const responsiveColumnsClassName = getResponsiveModifierClassName( 'cinderwell-slot-layout', 'cols', { tablet: attributes.columnsTablet, mobile: attributes.columnsMobile } );
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-slot-layout cinderwell-slot-layout--bg-${ attributes.background } cinderwell-slot-layout--cols-${ attributes.columns }${ responsiveColumnsClassName } cinderwell-slot-layout--gap-${ attributes.gap }${ getTypographyClassName( attributes ) }` }, attributes ) );

        return (
            <section { ...blockProps }>
                <div className="cinderwell-slot-layout__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    <div className="cinderwell-slot-layout__grid">
                        { slots.map( ( slot, index ) => (
                            <div className={ `cinderwell-slot-layout__slot cinderwell-slot-layout__slot--span-${ slot.span || '1' }` } key={ slot.id || index }>
                                <SlotContent slot={ slot } attributes={ attributes } onChange={ () => {} } />
                            </div>
                        ) ) }
                    </div>
                </div>
            </section>
        );
    },
} );
