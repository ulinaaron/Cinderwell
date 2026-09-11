import { LinkControl } from '@wordpress/block-editor';
import { Button, Popover } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { DynamicDataPicker, getPreviewValue } from './dynamic-data-picker';
import { DynamicDataIcon } from './dynamic-data-icon';

const LinkIcon = () => <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M10 13a5 5 0 007.5.5l2-2a5 5 0 00-7-7l-1.15 1.15M14 11a5 5 0 00-7.5-.5l-2 2a5 5 0 007 7l1.15-1.15" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"/></svg>;

const sourceLabel = ( binding ) => Object.values( window.cinderwellEditorSettings?.dataSources || {} ).flat().find( ( source ) => source.key === binding?.source )?.label || binding?.source;

export const getDynamicLinkValue = ( url, binding ) => {
    if ( ! binding?.source || binding.source === 'static' ) return url;
    return getPreviewValue( binding.source, binding.field, binding.fallback, sourceLabel( binding ) );
};

export const LinkSettingsControl = ( { url = '', opensInNewTab = false, dynamicData = {}, onChange } ) => {
    const [ linkAnchor, setLinkAnchor ] = useState( null );
    const [ showDynamicData, setShowDynamicData ] = useState( false );
    const isDynamic = dynamicData?.source && dynamicData.source !== 'static';
    const summary = isDynamic ? sourceLabel( dynamicData ) : ( url || __( 'No link selected', 'cinderwell' ) );
    const closeLink = () => setLinkAnchor( null );

    return <div className="cw-link-setting">
        <span className="cw-field__label">{ __( 'Link', 'cinderwell' ) }</span>
        <div className={ `cw-link-setting__row${ isDynamic ? ' is-dynamic' : '' }` }>
            <Button className="cw-link-setting__edit" icon={ <LinkIcon /> } onClick={ ( event ) => setLinkAnchor( linkAnchor ? null : event.currentTarget ) } aria-expanded={ Boolean( linkAnchor ) }>
                <span><strong>{ isDynamic ? __( 'Dynamic link', 'cinderwell' ) : __( 'Insert or edit link', 'cinderwell' ) }</strong><small>{ summary }</small></span>
            </Button>
            <Button className={ `cw-link-setting__dynamic${ isDynamic ? ' is-active' : '' }` } icon={ <DynamicDataIcon /> } label={ __( 'Dynamic data', 'cinderwell' ) } onClick={ () => setShowDynamicData( true ) } />
        </div>
        <div className="cw-toggle-row cw-link-setting__new-tab">
            <span className="cw-toggle-row__label">{ __( 'Open in new tab', 'cinderwell' ) }</span>
            <button type="button" className={ `cw-toggle${ opensInNewTab ? ' is-on' : '' }` } onClick={ () => onChange( { opensInNewTab: ! opensInNewTab } ) } role="switch" aria-checked={ opensInNewTab } aria-label={ __( 'Open in new tab', 'cinderwell' ) }><span className="cw-toggle__thumb" /></button>
        </div>
        { linkAnchor && <Popover anchor={ linkAnchor } onClose={ closeLink } placement="left-start" className="cw-native-link-popover">
            <div className="cw-native-link-popover__head">
                <strong>{ __( 'Insert or edit link', 'cinderwell' ) }</strong>
                <Button variant="tertiary" size="small" onClick={ closeLink } aria-label={ __( 'Close link editor', 'cinderwell' ) }>×</Button>
            </div>
            <LinkControl
                searchInputPlaceholder={ __( 'Search pages or paste a URL', 'cinderwell' ) }
                value={ { url } }
                settings={ [] }
                forceIsEditingLink={ true }
                showInitialSuggestions={ true }
                onChange={ ( value ) => { onChange( { url: value.url || '', dynamicData: {} } ); closeLink(); } }
                onCancel={ closeLink }
            />
            { ( url || isDynamic ) && <div className="cw-native-link-popover__footer"><Button
                variant="tertiary"
                isDestructive
                onClick={ () => { onChange( { url: '', dynamicData: {} } ); closeLink(); } }
            >{ __( 'Remove link', 'cinderwell' ) }</Button></div> }
        </Popover> }
        { showDynamicData && <DynamicDataPicker
            slots={ [ { value: 'url', label: __( 'Link URL', 'cinderwell' ) } ] }
            initialSlot="url"
            value={ { url: dynamicData } }
            slotValues={ { url } }
            allowedSourceKeys={ [ 'static', 'post_permalink', 'acf_field' ] }
            targetHelp={ __( 'Choose the link URL to replace.', 'cinderwell' ) }
            onChange={ ( bindings ) => onChange( { dynamicData: bindings.url || {} } ) }
            onPreviewChange={ ( slot, value ) => onChange( { url: value } ) }
            onClose={ () => setShowDynamicData( false ) }
        /> }
    </div>;
};
