import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls } from '../../shared/inspector-controls';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( { className: 'cinderwell-form' } );
        const options = { description: attributes.description, ajax: attributes.ajax };
        const onOptionsChange = ( values ) => setAttributes( values );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="ƒ" title={ __( 'Gravity Form', 'cinderwell' ) } description={ __( 'Embedded form', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-content">
                        <TextControl label={ __( 'Form ID', 'cinderwell' ) } type="number" value={ attributes.formId } onChange={ ( v ) => setAttributes( { formId: parseInt( v, 10 ) || 0 } ) } />
                        <SectionToggles sections={ [
                            { key: 'description', label: __( 'Description', 'cinderwell' ) },
                            { key: 'ajax', label: __( 'AJAX submission', 'cinderwell' ) },
                        ] } values={ options } onChange={ onOptionsChange } />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } showSpacing={ false } />
                </InspectorControls>
                <div { ...blockProps }>
                    <div className="cinderwell-form__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.formId > 0 ? (
                            <div className="cinderwell-form__preview">
                                <p>{ __( 'Gravity Form ID:', 'cinderwell' ) } <strong>{ attributes.formId }</strong></p>
                                <p className="cinderwell-form__note">{ __( 'Form renders on the frontend.', 'cinderwell' ) }</p>
                            </div>
                        ) : (
                            <div className="cinderwell-form__empty">
                                <p>{ __( 'Enter a Gravity Form ID in the sidebar.', 'cinderwell' ) }</p>
                            </div>
                        ) }
                    </div>
                </div>
            </>
        );
    },
    save: () => null,
} );
