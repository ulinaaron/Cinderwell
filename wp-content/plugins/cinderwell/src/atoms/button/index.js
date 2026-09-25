import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { buttonVariantOptions, buttonSizeOptions } from '../../shared/design-system';
import { ConditionsPanel } from '../../shared/conditions-panel';
import { BlockIdentity, SegmentedControl } from '../../shared/inspector-controls';
import { EditableLink } from '../../shared/link-control';
import { ButtonDestinationControl, ButtonIconControl } from '../../shared/button-controls';
import { getButtonHref, getButtonIconProps } from '../../shared/button-utils';
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
            ...( Object.prototype.hasOwnProperty.call( changes, 'destinationType' ) ? { destinationType: changes.destinationType } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'phoneNumber' ) ? { phoneNumber: changes.phoneNumber } : {} ),
            ...( Object.prototype.hasOwnProperty.call( changes, 'emailAddress' ) ? { emailAddress: changes.emailAddress } : {} ),
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
                        <ButtonDestinationControl
                            destinationType={ attributes.destinationType || 'link' }
                            url={ attributes.url }
                            phoneNumber={ attributes.phoneNumber || '' }
                            emailAddress={ attributes.emailAddress || '' }
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
                        <ButtonIconControl
                            icon={ attributes.icon || '' }
                            iconPosition={ attributes.iconPosition || 'before' }
                            onChange={ setAttributes }
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
                    destinationType={ attributes.destinationType || 'link' }
                    phoneNumber={ attributes.phoneNumber || '' }
                    emailAddress={ attributes.emailAddress || '' }
                    icon={ attributes.icon || '' }
                    iconPosition={ attributes.iconPosition || 'before' }
                    settingsControl={ <ButtonDestinationControl
                        destinationType={ attributes.destinationType || 'link' }
                        url={ attributes.url }
                        phoneNumber={ attributes.phoneNumber || '' }
                        emailAddress={ attributes.emailAddress || '' }
                        opensInNewTab={ Boolean( attributes.opensInNewTab ) }
                        dynamicData={ attributes.urlDynamic || {} }
                        onChange={ updateLink }
                    /> }
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
        const iconProps = getButtonIconProps( attributes.icon, attributes.iconPosition );
        const href = getButtonHref( attributes ) || '#';
        const blockProps = useBlockProps.save( {
            className: `btn btn--${ attributes.variant } btn--${ attributes.size }${ iconProps.className ? ` ${ iconProps.className }` : '' }`,
            style: iconProps.style,
            href,
            target: ( attributes.destinationType || 'link' ) === 'link' && attributes.opensInNewTab ? '_blank' : undefined,
            rel: ( attributes.destinationType || 'link' ) === 'link' && attributes.opensInNewTab ? 'noopener noreferrer' : undefined,
            'data-cw-url-source': ( attributes.destinationType || 'link' ) === 'link' && attributes.urlDynamic?.source && attributes.urlDynamic.source !== 'static' ? attributes.urlDynamic.source : undefined,
            'data-cw-url-field': ( attributes.destinationType || 'link' ) === 'link' ? attributes.urlDynamic?.field || undefined : undefined,
            'data-cw-url-fallback': ( attributes.destinationType || 'link' ) === 'link' ? attributes.urlDynamic?.fallback || undefined : undefined,
        } );
        return <RichText.Content tagName="a" { ...blockProps } value={ attributes.text } />;
    },
} );
