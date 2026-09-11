import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, TypographyControls, getTypographyClassName } from '../../shared/inspector-controls';
import { LinkSettingsControl, getDynamicLinkValue } from '../../shared/link-control';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( { className: `cinderwell-atom-link${ getTypographyClassName( attributes ) }` } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="↗" title={ __( 'Link', 'cinderwell' ) } description={ __( 'Inline text link', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
                        <LinkSettingsControl
                            url={ attributes.url }
                            opensInNewTab={ Boolean( attributes.opensInNewTab ) }
                            dynamicData={ attributes.urlDynamic || {} }
                            onChange={ ( changes ) => setAttributes( {
                                ...( Object.prototype.hasOwnProperty.call( changes, 'url' ) ? { url: changes.url } : {} ),
                                ...( Object.prototype.hasOwnProperty.call( changes, 'opensInNewTab' ) ? { opensInNewTab: changes.opensInNewTab } : {} ),
                                ...( Object.prototype.hasOwnProperty.call( changes, 'dynamicData' ) ? { urlDynamic: changes.dynamicData } : {} ),
                            } ) }
                        />
                    </PanelBody>
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <RichText
                    { ...blockProps }
                    tagName="a"
                    identifier="text"
                    href={ getDynamicLinkValue( attributes.url, attributes.urlDynamic ) || '#' }
                    target={ attributes.opensInNewTab ? '_blank' : undefined }
                    rel={ attributes.opensInNewTab ? 'noopener noreferrer' : undefined }
                    value={ attributes.text }
                    onChange={ ( v ) => setAttributes( { text: v } ) }
                    placeholder={ __( 'Link text…', 'cinderwell' ) }
                    allowedFormats={ [] }
                    onClick={ ( event ) => event.preventDefault() }
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
