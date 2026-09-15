import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { buttonVariantOptions, buttonSizeOptions } from '../../shared/design-system';
import { ConditionsPanel } from '../../shared/conditions-panel';
import { BlockIdentity, SegmentedControl } from '../../shared/inspector-controls';
import { EditableLink, LinkSettingsControl } from '../../shared/link-control';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( {
            className: `btn btn--${ attributes.variant } btn--${ attributes.size }`,
        } );
        const updateLink = ( changes ) => setAttributes( {
            ...( Object.prototype.hasOwnProperty.call( changes, 'url' ) ? { url: changes.url } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'opensInNewTab' ) ? { opensInNewTab: changes.opensInNewTab } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'dynamicData' ) ? { urlDynamic: changes.dynamicData } : {} ),
        } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity
                        icon="↗"
                        title={ __( 'Button', 'cinderwell' ) }
                        description={ __( 'Linked call to action', 'cinderwell' ) }
                    />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-links">
                        <LinkSettingsControl
                            url={ attributes.url }
                            opensInNewTab={ Boolean( attributes.opensInNewTab ) }
                            dynamicData={ attributes.urlDynamic || {} }
                            onChange={ updateLink }
                        />
                    </PanelBody>
                    <PanelBody title={ __( 'Style', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-appearance">
                        <SegmentedControl
                            label={ __( 'Button style', 'cinderwell' ) }
                            value={ attributes.variant }
                            options={ buttonVariantOptions }
                            onChange={ ( v ) => setAttributes( { variant: v } ) }
                        />
                        <SegmentedControl
                            label={ __( 'Size', 'cinderwell' ) }
                            value={ attributes.size }
                            options={ buttonSizeOptions }
                            onChange={ ( v ) => setAttributes( { size: v } ) }
                        />
                    </PanelBody>
                    <ConditionsPanel attributes={ attributes } setAttributes={ setAttributes } />
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
                    contextLabel={ __( 'Button destination', 'cinderwell' ) }
                    placeholder={ __( 'Button text…', 'cinderwell' ) }
                    allowedFormats={ [] }
                />
            </>
        );
    },
    save: ( { attributes } ) => {
        const blockProps = useBlockProps.save( {
            className: `btn btn--${ attributes.variant } btn--${ attributes.size }`,
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
