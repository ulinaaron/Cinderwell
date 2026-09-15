import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Notice, PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const preview = window.cinderwellEditorSettings?.previewValues || {};
        const phone = preview.company_phone || __( 'Company phone', 'cinderwell' );
        const email = preview.company_email || __( 'Company email', 'cinderwell' );
        const blockProps = useBlockProps( {
            className: `cinderwell-utility-bar cinderwell-utility-bar--bg-${ attributes.background }${ attributes.showSocialsMobile ? ' cinderwell-utility-bar--socials-mobile' : '' }`,
        } );

        return (
            <>
                <InspectorControls>
                    <PanelBody title={ __( 'Utility Bar', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-content">
                        <Notice status="info" isDismissible={ false }>
                            { __( 'Contact details and social links update from Cinderwell → Company Details.', 'cinderwell' ) }
                        </Notice>
                        <ToggleControl label={ __( 'Show phone', 'cinderwell' ) } checked={ attributes.showPhone } onChange={ ( showPhone ) => setAttributes( { showPhone } ) } />
                        <ToggleControl label={ __( 'Show email', 'cinderwell' ) } checked={ attributes.showEmail } onChange={ ( showEmail ) => setAttributes( { showEmail } ) } />
                        <ToggleControl label={ __( 'Show social profiles', 'cinderwell' ) } checked={ attributes.showSocials } onChange={ ( showSocials ) => setAttributes( { showSocials } ) } />
                        { attributes.showSocials && <ToggleControl label={ __( 'Show social profiles on mobile', 'cinderwell' ) } checked={ attributes.showSocialsMobile } onChange={ ( showSocialsMobile ) => setAttributes( { showSocialsMobile } ) } /> }
                        <ToggleControl label={ __( 'Show contact link', 'cinderwell' ) } checked={ attributes.showContact } onChange={ ( showContact ) => setAttributes( { showContact } ) } />
                        { attributes.showContact && <TextControl label={ __( 'Contact link label', 'cinderwell' ) } value={ attributes.contactLabel } onChange={ ( contactLabel ) => setAttributes( { contactLabel } ) } /> }
                    </PanelBody>
                    <PanelBody title={ __( 'Style', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-design">
                        <SelectControl
                            label={ __( 'Background', 'cinderwell' ) }
                            value={ attributes.background }
                            options={ [
                                { label: __( 'Dark', 'cinderwell' ), value: 'dark' },
                                { label: __( 'Brand', 'cinderwell' ), value: 'brand' },
                                { label: __( 'Light', 'cinderwell' ), value: 'light' },
                                { label: __( 'White', 'cinderwell' ), value: 'white' },
                            ] }
                            onChange={ ( background ) => setAttributes( { background } ) }
                        />
                    </PanelBody>
                </InspectorControls>
                <div { ...blockProps }>
                    <div className="cinderwell-utility-bar__inner">
                        <div className="cinderwell-utility-bar__contact">
                            { attributes.showPhone && <span>{ phone }</span> }
                            { attributes.showEmail && <span>{ email }</span> }
                            { attributes.showContact && <span>{ attributes.contactLabel || __( 'Contact', 'cinderwell' ) }</span> }
                        </div>
                        { attributes.showSocials && <div className="cinderwell-utility-bar__socials"><span>{ __( 'Company social profiles', 'cinderwell' ) }</span></div> }
                    </div>
                </div>
            </>
        );
    },
    save: () => null,
} );
