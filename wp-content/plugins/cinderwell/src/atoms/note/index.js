import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SegmentedControl, TypographyControls, getTypographyClassName } from '../../shared/inspector-controls';
import { ALLOWED_INLINE_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

const noteStyleOptions = [
    { label: __( 'Disclaimer', 'cinderwell' ), value: 'disclaimer' },
    { label: __( 'Caption', 'cinderwell' ), value: 'caption' },
    { label: __( 'Footnote', 'cinderwell' ), value: 'footnote' },
    { label: __( 'Legal', 'cinderwell' ), value: 'legal' },
];

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( {
            className: `cinderwell-atom-note cinderwell-atom-note--${ attributes.style }${ getTypographyClassName( attributes ) }`,
        } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="i" title={ __( 'Note', 'cinderwell' ) } description={ __( 'Supporting or legal copy', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Style', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-appearance">
                        <SegmentedControl
                            label={ __( 'Note style', 'cinderwell' ) }
                            value={ attributes.style }
                            options={ noteStyleOptions }
                            onChange={ ( v ) => setAttributes( { style: v } ) }
                        />
                    </PanelBody>
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <RichText
                    { ...blockProps }
                    tagName="aside"
                    identifier="content"
                    value={ attributes.content }
                    onChange={ ( v ) => setAttributes( { content: v } ) }
                    placeholder={ __( 'Note text…', 'cinderwell' ) }
                    allowedFormats={ ALLOWED_INLINE_FORMATS }
                />
            </>
        );
    },
    save: ( { attributes } ) => {
        const blockProps = useBlockProps.save( {
            className: `cinderwell-atom-note cinderwell-atom-note--${ attributes.style }${ getTypographyClassName( attributes ) }`,
        } );
        return <RichText.Content tagName="aside" { ...blockProps } value={ attributes.content } />;
    },
} );
