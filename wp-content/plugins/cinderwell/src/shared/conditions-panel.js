import { Button, Modal, Notice, PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { DynamicDataPicker } from './dynamic-data-picker';

const labels = { eyebrow: 'Eyebrow', heading: 'Heading', subheading: 'Subheading', bodyContent: 'Body', caption: 'Caption', footnote: 'Footnote', byline: 'Byline', pullquote: 'Pullquote', quote: 'Quote', attribution: 'Attribution', context: 'Context', leftContent: 'Left column', rightContent: 'Right column', content: 'Content', text: 'Text' };
const group = () => ( { relation: 'and', rules: [] } );
const rule = () => ( { type: 'homepage_only', config: {} } );

const Rule = ( { item, index, registry, onChange, onRemove } ) => {
    const [ isDynamicPickerOpen, setIsDynamicPickerOpen ] = useState( false );
    const config = item.config || {};
    const definition = registry[ item.type ] || {};
    const setConfig = ( changes ) => onChange( { ...item, config: { ...config, ...changes } } );
    return <div className="cw-condition-rule">
        <div className="cw-condition-rule__head"><strong>{ sprintf( __( 'Condition %d', 'cinderwell' ), index + 1 ) }</strong><Button variant="tertiary" isDestructive className="cw-condition-rule__remove" onClick={ onRemove }>{ __( 'Remove', 'cinderwell' ) }</Button></div>
        <SelectControl label={ __( 'Condition type', 'cinderwell' ) } value={ item.type } options={ Object.entries( registry ).map( ( [ value, entry ] ) => ( { value, label: entry.label } ) ) } onChange={ ( type ) => onChange( { ...item, type, config: {} } ) } />
        { definition.requires === 'page_selector' && <TextControl label={ __( 'Page IDs', 'cinderwell' ) } value={ ( config.pages || [] ).join( ', ' ) } help={ __( 'Separate IDs with commas.', 'cinderwell' ) } onChange={ ( value ) => setConfig( { pages: value.split( ',' ).map( ( id ) => parseInt( id.trim(), 10 ) ).filter( Boolean ) } ) } /> }
        { definition.requires === 'date_range' && <div className="cw-condition-rule__dates"><TextControl label={ __( 'Start', 'cinderwell' ) } type="date" value={ config.startDate || '' } onChange={ ( startDate ) => setConfig( { startDate } ) } /><TextControl label={ __( 'End', 'cinderwell' ) } type="date" value={ config.endDate || '' } onChange={ ( endDate ) => setConfig( { endDate } ) } /></div> }
        { definition.requires === 'post_type' && <TextControl label={ __( 'Post types', 'cinderwell' ) } value={ ( config.postTypes || [] ).join( ', ' ) } help={ __( 'For example: post, page, product.', 'cinderwell' ) } onChange={ ( value ) => setConfig( { postTypes: value.split( ',' ).map( ( type ) => type.trim() ).filter( Boolean ) } ) } /> }
        { item.type === 'custom' && <TextControl label={ __( 'Custom condition key', 'cinderwell' ) } value={ config.key || '' } help={ __( 'Use the key registered by your theme or plugin.', 'cinderwell' ) } placeholder="client_has_membership" onChange={ ( key ) => setConfig( { key } ) } /> }
        { definition.requires === 'dynamic_comparison' && <><div className="cw-condition-rule__dynamic-control"><Button variant="secondary" onClick={ () => setIsDynamicPickerOpen( true ) }>{ __( 'Insert dynamic data', 'cinderwell' ) }</Button>{ config.dynamicData?.source && <span className="cw-condition-rule__dynamic">{ config.dynamicData.source }{ config.dynamicData.field ? `: ${ config.dynamicData.field }` : '' }</span> }</div>{ isDynamicPickerOpen && <DynamicDataPicker slots={ [ { value: 'conditionValue', label: __( 'Condition value', 'cinderwell' ) } ] } value={ { conditionValue: config.dynamicData || {} } } onChange={ ( bindings ) => setConfig( { dynamicData: bindings.conditionValue || {} } ) } onClose={ () => setIsDynamicPickerOpen( false ) } /> }<SelectControl label={ __( 'Comparison', 'cinderwell' ) } value={ config.operator || 'is' } options={ [ { value: 'is', label: __( 'is', 'cinderwell' ) }, { value: 'is_not', label: __( 'is not', 'cinderwell' ) }, { value: 'contains', label: __( 'contains', 'cinderwell' ) }, { value: 'not_contains', label: __( 'does not contain', 'cinderwell' ) }, { value: 'is_empty', label: __( 'is empty', 'cinderwell' ) }, { value: 'is_not_empty', label: __( 'is not empty', 'cinderwell' ) } ] } onChange={ ( operator ) => setConfig( { operator } ) } />{ ! [ 'is_empty', 'is_not_empty' ].includes( config.operator || 'is' ) && <TextControl label={ __( 'Value', 'cinderwell' ) } value={ config.value || '' } onChange={ ( value ) => setConfig( { value } ) } /> }</> }
    </div>;
};

export const ConditionsPanel = ( { attributes, setAttributes } ) => {
    const [ isOpen, setIsOpen ] = useState( false );
    const [ scope, setScope ] = useState( 'block' );
    const registry = ( window.cinderwellEditorSettings || {} ).conditions || {};
    const sections = useMemo( () => Object.keys( labels ).filter( ( key ) => Object.prototype.hasOwnProperty.call( attributes, key ) ).map( ( value ) => ( { value, label: __( labels[ value ], 'cinderwell' ) } ) ), [ attributes ] );
    const conditions = attributes.conditions || {};
    const current = scope === 'block' ? ( conditions.block || group() ) : ( conditions.sections?.[ scope ] || group() );
    const activeScopes = [
        { scope: 'block', label: __( 'Entire block', 'cinderwell' ), rules: conditions.block?.rules || [] },
        ...Object.entries( conditions.sections || {} ).map( ( [ section, item ] ) => ( { scope: section, label: __( labels[ section ] || section, 'cinderwell' ), rules: item.rules || [] } ) ),
    ].filter( ( item ) => item.rules.length );
    const active = activeScopes.flatMap( ( item ) => item.rules.map( ( entry ) => item.scope === 'block' ? ( registry[ entry.type ]?.label || entry.type ) : `${ item.label }: ${ registry[ entry.type ]?.label || entry.type }` ) );
    const open = () => {
        const activeSection = Object.entries( conditions.sections || {} ).find( ( [ , item ] ) => item?.rules?.length )?.[ 0 ];
        setScope( conditions.block?.rules?.length ? 'block' : ( activeSection || 'block' ) );
        setIsOpen( true );
    };
    const save = ( next ) => {
        if ( scope === 'block' ) {
            setAttributes( { conditions: { ...conditions, block: next } } );
        } else {
            setAttributes( { conditions: { ...conditions, sections: { ...( conditions.sections || {} ), [ scope ]: next } } } );
        }
    };
    const updateRule = ( index, next ) => save( { ...current, rules: current.rules.map( ( item, itemIndex ) => itemIndex === index ? next : item ) } );
    const removeRuleFromScope = ( targetScope, index ) => {
        const target = targetScope === 'block' ? ( conditions.block || group() ) : ( conditions.sections?.[ targetScope ] || group() );
        const rules = target.rules.filter( ( _, itemIndex ) => itemIndex !== index );
        if ( targetScope === 'block' ) {
            setAttributes( { conditions: { ...conditions, block: { ...target, rules } } } );
            return;
        }
        const nextSections = { ...( conditions.sections || {} ) };
        if ( rules.length ) nextSections[ targetScope ] = { ...target, rules };
        else delete nextSections[ targetScope ];
        setAttributes( { conditions: { ...conditions, sections: nextSections } } );
    };

    return <>
        <PanelBody title={ __( 'Conditions', 'cinderwell' ) } initialOpen={ active.length > 0 } className="cw-conditions-panel">
            <span className="cw-conditions-panel__label">{ __( 'Only show this block or its sections if', 'cinderwell' ) }</span>
            { active.length ? <div className="cw-conditions-panel__summary">{ active.map( ( label, index ) => <span key={ `${ label }-${ index }` }>{ label }</span> ) }</div> : <p className="cw-conditions-panel__empty">{ __( 'No conditions set. This block always shows.', 'cinderwell' ) }</p> }
            <Button variant="secondary" onClick={ open }>{ __( 'Edit conditions', 'cinderwell' ) }</Button>
        </PanelBody>
        { isOpen && <Modal title={ __( 'Conditions', 'cinderwell' ) } onRequestClose={ () => setIsOpen( false ) } className="cw-conditions-modal">
            <Notice status="warning" isDismissible={ false }>{ __( 'Conditions change what visitors see. Use with care.', 'cinderwell' ) }</Notice>
            <div className="cw-conditions-modal__active"><span>{ __( 'Active conditions', 'cinderwell' ) }</span>{ activeScopes.length ? <div>{ activeScopes.flatMap( ( item ) => item.rules.map( ( entry, index ) => <div className="cw-conditions-modal__active-rule" key={ `${ item.scope }-${ entry.type }-${ index }` }><button type="button" onClick={ () => setScope( item.scope ) }>{ item.scope === 'block' ? '' : `${ item.label }: ` }{ registry[ entry.type ]?.label || entry.type }</button><Button variant="tertiary" isDestructive aria-label={ sprintf( __( 'Remove %s condition', 'cinderwell' ), registry[ entry.type ]?.label || entry.type ) } onClick={ () => removeRuleFromScope( item.scope, index ) }>×</Button></div> ) ) }</div> : <p>{ __( 'No active conditions. This block always shows.', 'cinderwell' ) }</p> }</div>
            <div className="cw-conditions-modal__scope"><span>{ __( 'Apply rules to', 'cinderwell' ) }</span><div><Button variant={ scope === 'block' ? 'primary' : 'secondary' } onClick={ () => setScope( 'block' ) }>{ __( 'Entire block', 'cinderwell' ) }</Button>{ sections.map( ( item ) => <Button key={ item.value } variant={ scope === item.value ? 'primary' : 'secondary' } onClick={ () => setScope( item.value ) }>{ item.label }</Button> ) }</div></div>
            <div className="cw-conditions-modal__builder">
                <div className="cw-conditions-modal__builder-head"><strong>{ scope === 'block' ? __( 'Entire block', 'cinderwell' ) : sections.find( ( item ) => item.value === scope )?.label }</strong>{ current.rules.length > 1 && <SelectControl label={ __( 'Match', 'cinderwell' ) } value={ current.relation || 'and' } options={ [ { value: 'and', label: __( 'All rules (AND)', 'cinderwell' ) }, { value: 'or', label: __( 'Any rule (OR)', 'cinderwell' ) } ] } onChange={ ( relation ) => save( { ...current, relation } ) } /> }</div>
                { current.rules.map( ( item, index ) => <Rule key={ index } item={ item } index={ index } registry={ registry } onChange={ ( next ) => updateRule( index, next ) } onRemove={ () => removeRuleFromScope( scope, index ) } /> ) }
                <Button variant="secondary" className="cw-add-item" onClick={ () => save( { ...current, rules: [ ...current.rules, rule() ] } ) }>+ { __( 'Add condition', 'cinderwell' ) }</Button>
            </div>
            <div className="cw-conditions-modal__footer"><Button variant="tertiary" onClick={ () => save( group() ) } disabled={ ! current.rules.length }>{ __( 'Clear this scope', 'cinderwell' ) }</Button><Button variant="primary" onClick={ () => setIsOpen( false ) }>{ __( 'Done', 'cinderwell' ) }</Button></div>
        </Modal> }
    </>;
};
