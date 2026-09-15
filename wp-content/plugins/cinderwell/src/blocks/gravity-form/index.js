import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { TextControl, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, getBackgroundImageProps } from '../../shared/inspector-controls';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( getBackgroundImageProps( { className: `cinderwell-form cinderwell-form--bg-${ attributes.background }` }, attributes ) );
        const options = { title: attributes.title, description: attributes.description, ajax: attributes.ajax };
        const onOptionsChange = ( values ) => setAttributes( values );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="ƒ" title={ __( 'Gravity Form', 'cinderwell' ) } description={ __( 'Embedded form', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-content">
                        <TextControl label={ __( 'Form ID', 'cinderwell' ) } type="number" value={ attributes.formId } onChange={ ( v ) => setAttributes( { formId: parseInt( v, 10 ) || 0 } ) } />
                        <SectionToggles sections={ [
                            { key: 'title', label: __( 'Title', 'cinderwell' ) },
                            { key: 'description', label: __( 'Description', 'cinderwell' ) },
                            { key: 'ajax', label: __( 'AJAX submission', 'cinderwell' ) },
                        ] } values={ options } onChange={ onOptionsChange } />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
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
