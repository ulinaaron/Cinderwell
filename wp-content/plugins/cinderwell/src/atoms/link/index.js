import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, TypographyControls, getTypographyClassName } from '../../shared/inspector-controls';
import { EditableLink, LinkSettingsControl } from '../../shared/link-control';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( { className: `cinderwell-atom-link${ getTypographyClassName( attributes ) }` } );
        const updateLink = ( changes ) => setAttributes( {
            ...( Object.prototype.hasOwnProperty.call( changes, 'url' ) ? { url: changes.url } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'opensInNewTab' ) ? { opensInNewTab: changes.opensInNewTab } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'dynamicData' ) ? { urlDynamic: changes.dynamicData } : {} ),
        } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="↗" title={ __( 'Link', 'cinderwell' ) } description={ __( 'Inline text link', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-links">
                        <LinkSettingsControl
                            url={ attributes.url }
                            opensInNewTab={ Boolean( attributes.opensInNewTab ) }
                            dynamicData={ attributes.urlDynamic || {} }
                            onChange={ updateLink }
                        />
                    </PanelBody>
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <EditableLink
                    { ...blockProps }
                    tagName="a"
                    identifier="text"
                    value={ attributes.text }
                    url={ attributes.url }
                    opensInNewTab={ Boolean( attributes.opensInNewTab ) }
                    dynamicData={ attributes.urlDynamic || {} }
                    onTextChange={ ( v ) => setAttributes( { text: v } ) }
                    onLinkChange={ updateLink }
                    placeholder={ __( 'Link text…', 'cinderwell' ) }
                    allowedFormats={ [] }
                />
            </>
        );
    },
    save: ( { attributes } ) => {
        const blockProps = useBlockProps.save( {
            className: `cinderwell-atom-link${ getTypographyClassName( attributes ) }`,
            href: attributes.url || '#',
            target: attributes.opensInNewTab ? '_blank' : undefined,
            rel: attributes.opensInNewTab ? 'noopener noreferrer' : undefined,
            'data-cw-url-source': attributes.urlDynamic?.source && attributes.urlDynamic.source !== 'static' ? attributes.urlDynamic.source : undefined,
            'data-cw-url-field': attributes.urlDynamic?.field || undefined,
            'data-cw-url-fallback': attributes.urlDynamic?.fallback || undefined,
        } );
        return <RichText.Content tagName="a" { ...blockProps } value={ attributes.text } />;
    },
} );
