import { Button, Modal, TextControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useMemo, useState } from '@wordpress/element';
import { select } from '@wordpress/data';

const titleCase = ( value ) => value.split( '_' ).map( ( word ) => word.charAt( 0 ).toUpperCase() + word.slice( 1 ) ).join( ' ' );

const formatPreviewValue = ( value ) => {
    if ( value === null || value === undefined || value === false ) return '';
    if ( Array.isArray( value ) ) return value.map( formatPreviewValue ).filter( Boolean ).join( ', ' );
    if ( typeof value === 'object' ) return formatPreviewValue( value.label || value.title || value.name || value.url || Object.values( value ) );
    return String( value );
};

export const getPreviewValue = ( source, field = '', fallback = '', sourceLabel = '', liveValues = {} ) => {
    const settings = window.cinderwellEditorSettings || {};
    let value = '';
    if ( source === 'post_title' ) value = liveValues.post_title ?? select( 'core/editor' )?.getEditedPostAttribute( 'title' );
    else if ( source === 'post_excerpt' ) value = liveValues.post_excerpt ?? select( 'core/editor' )?.getEditedPostAttribute( 'excerpt' );
    else if ( source === 'post_permalink' ) value = select( 'core/editor' )?.getPermalink?.() || settings.previewValues?.post_permalink;
    else if ( source === 'post_date' ) value = settings.previewValues?.post_date;
    else if ( source === 'post_author' ) value = settings.previewValues?.post_author;
    else if ( source === 'current_year' ) value = String( new Date().getFullYear() );
    else if ( source === 'site_title' ) value = settings.siteName;
    else if ( source === 'site_tagline' ) value = settings.siteTagline;
    else if ( source === 'current_user_name' ) value = settings.previewValues?.current_user_name;
    else if ( source === 'acf_field' ) value = settings.acfValues?.[ field ];
    const preview = formatPreviewValue( value ) || fallback;
    return preview || sprintf( __( 'Dynamic: %s', 'cinderwell' ), sourceLabel || source );
};

export const DynamicDataPicker = ( { slots, initialSlot = '', value = {}, slotValues = {}, allowedSourceKeys = null, targetHelp = __( 'Choose the text field to replace.', 'cinderwell' ), onChange, onPreviewChange, onClose } ) => {
    const settings = window.cinderwellEditorSettings || {};
    const groups = settings.dataSources || {};
    const acfFields = settings.acfFields || [];
    const [ slot, setSlot ] = useState( slots.some( ( item ) => item.value === initialSlot ) ? initialSlot : ( Object.keys( value )[ 0 ] || slots[ 0 ]?.value || '' ) );
    const [ source, setSource ] = useState( value[ slot ]?.source || 'static' );
    const [ field, setField ] = useState( value[ slot ]?.field || '' );
    const [ fallback, setFallback ] = useState( value[ slot ]?.fallback || '' );
    const [ query, setQuery ] = useState( '' );
    const needle = query.trim().toLowerCase();
    const sourceConfig = useMemo( () => Object.values( groups ).flat().find( ( item ) => item.key === source ), [ groups, source ] );
    const sourceIsAllowed = ( sourceItem ) => ! allowedSourceKeys || allowedSourceKeys.includes( sourceItem.key );
    const visibleGroups = Object.entries( groups ).map( ( item ) => ( { group: item[ 0 ], items: item[ 1 ].filter( ( sourceItem ) => sourceIsAllowed( sourceItem ) && ( ! needle || sourceItem.label.toLowerCase().includes( needle ) ) ) } ) ).filter( ( item ) => item.items.length );
    const visibleAcf = ( ! allowedSourceKeys || allowedSourceKeys.includes( 'acf_field' ) ) ? acfFields.filter( ( item ) => ! needle || ( item.label + ' ' + item.name + ' ' + item.group ).toLowerCase().includes( needle ) ) : [];
    const chooseSlot = ( nextSlot ) => { setSlot( nextSlot ); setSource( value[ nextSlot ]?.source || 'static' ); setField( value[ nextSlot ]?.field || '' ); setFallback( value[ nextSlot ]?.fallback || '' ); };
    const activeBinding = value[ slot ];
    const hasDynamicBinding = activeBinding?.source && activeBinding.source !== 'static';
    const restoreStaticValue = ( binding = activeBinding ) => {
        if ( ! onPreviewChange ) return;
        onPreviewChange( slot, Object.prototype.hasOwnProperty.call( binding || {}, 'staticValue' ) ? binding.staticValue : ( slotValues[ slot ] ?? '' ) );
    };
    const clear = () => {
        const next = { ...value };
        delete next[ slot ];
        onChange( next );
        restoreStaticValue();
        onClose();
    };
    const apply = () => {
        const next = { ...value };
        if ( source === 'static' ) {
            delete next[ slot ];
            restoreStaticValue();
        } else {
            next[ slot ] = {
                source,
                field,
                fallback,
                staticValue: Object.prototype.hasOwnProperty.call( activeBinding || {}, 'staticValue' ) ? activeBinding.staticValue : ( slotValues[ slot ] ?? '' ),
            };
        }
        onChange( next );
        onClose();
    };

    return <Modal title={ __( 'Insert Dynamic Data', 'cinderwell' ) } onRequestClose={ onClose } className="cw-dynamic-data-modal">
        <div className="cw-dynamic-data-modal__target"><div className="cw-dynamic-data-modal__target-head"><strong>{ __( 'Apply dynamic data to', 'cinderwell' ) }</strong><span>{ targetHelp }</span></div><div className="cw-dynamic-data-modal__slots">{ slots.map( ( item ) => <Button key={ item.value } variant={ slot === item.value ? 'primary' : 'secondary' } aria-pressed={ slot === item.value } onClick={ () => chooseSlot( item.value ) }>{ item.label }</Button> ) }</div></div>
        <div className="cw-dynamic-data-modal__search"><TextControl label={ __( 'Find a data source', 'cinderwell' ) } value={ query } onChange={ setQuery } placeholder={ __( 'Search dynamic data…', 'cinderwell' ) } /></div>
        <div className="cw-dynamic-data-modal__sources">
            { visibleGroups.map( ( item ) => <section key={ item.group }><h3>{ titleCase( item.group ) }</h3><div>{ item.items.map( ( sourceItem ) => <Button key={ sourceItem.key } variant={ source === sourceItem.key ? 'primary' : 'secondary' } onClick={ () => setSource( sourceItem.key ) }>{ sourceItem.label }</Button> ) }</div></section> ) }
            { visibleAcf.length > 0 && <section><h3>ACF</h3><div>{ visibleAcf.map( ( acf ) => <Button key={ acf.name } variant={ source === 'acf_field' && field === acf.name ? 'primary' : 'secondary' } onClick={ () => { setSource( 'acf_field' ); setField( acf.name ); } }>{ acf.label }</Button> ) }</div></section> }
        </div>
        { sourceConfig?.requiresFieldName && <TextControl label={ __( 'ACF field name', 'cinderwell' ) } value={ field } onChange={ setField } placeholder="hero_subheading" help={ __( 'Choose a field above or enter its field name.', 'cinderwell' ) } /> }
        { source !== 'static' && <TextControl label={ __( 'Fallback text', 'cinderwell' ) } value={ fallback } onChange={ setFallback } help={ __( 'Shown if the source has no value.', 'cinderwell' ) } /> }
        <div className="cw-dynamic-data-modal__footer">{ hasDynamicBinding && <Button className="cw-dynamic-data-modal__clear" variant="tertiary" onClick={ clear }>{ __( 'Clear dynamic data', 'cinderwell' ) }</Button> }<Button variant="tertiary" onClick={ onClose }>{ __( 'Cancel', 'cinderwell' ) }</Button><Button variant="primary" onClick={ apply } disabled={ sourceConfig?.requiresFieldName && ! field }>{ sprintf( __( 'Apply to %s', 'cinderwell' ), slots.find( ( item ) => item.value === slot )?.label || __( 'slot', 'cinderwell' ) ) }</Button></div>
    </Modal>;
};
