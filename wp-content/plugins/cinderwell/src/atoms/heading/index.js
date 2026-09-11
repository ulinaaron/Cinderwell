import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SegmentedControl, TypographyControls, getTypographyClassName } from '../../shared/inspector-controls';
import { ALLOWED_INLINE_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

const levelOptions = [
    { label: 'H1', value: 1 },
    { label: 'H2', value: 2 },
    { label: 'H3', value: 3 },
    { label: 'H4', value: 4 },
];

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const tag = `h${ attributes.level }`;
        const blockProps = useBlockProps( { className: `cinderwell-atom-heading${ getTypographyClassName( attributes ) }` } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="H" title={ __( 'Heading', 'cinderwell' ) } description={ __( 'Standalone heading', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
                        <SegmentedControl
                            label={ __( 'Heading level', 'cinderwell' ) }
                            value={ attributes.level }
                            options={ levelOptions }
                            onChange={ ( level ) => setAttributes( { level } ) }
                        />
                    </PanelBody>
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <RichText
                    { ...blockProps }
                    tagName={ tag }
                    identifier="content"
                    value={ attributes.content }
                    onChange={ ( v ) => setAttributes( { content: v } ) }
                    placeholder={ __( 'Heading text…', 'cinderwell' ) }
                    allowedFormats={ ALLOWED_INLINE_FORMATS }
                />
            </>
        );
    },
    save: ( { attributes } ) => {
        const tag = `h${ attributes.level }`;
        const blockProps = useBlockProps.save( { className: `cinderwell-atom-heading${ getTypographyClassName( attributes ) }` } );
        return <RichText.Content tagName={ tag } { ...blockProps } value={ attributes.content } />;
    },
} );
