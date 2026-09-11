import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, getBackgroundImageProps, TypographyControls, getTypographyClassName, getTextStyleClassName } from '../../shared/inspector-controls';
import { ALLOWED_INLINE_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( getBackgroundImageProps( {
            className: `cinderwell-quote cinderwell-quote--bg-${ attributes.background } cinderwell-quote--align-${ attributes.alignment }${ getTypographyClassName( attributes ) }`,
        }, attributes ) );
        const secs = { byline: attributes.showByline, context: attributes.showContext };
        const onSecs = ( v ) => setAttributes( { showByline: v.byline, showContext: v.context } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="&#10077;" title={ __( 'Quote', 'cinderwell' ) } description={ __( 'Blockquote with attribution', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cinderwell-content-panel">
                        <SectionToggles sections={ [
                            { key: 'byline', label: __( 'Byline', 'cinderwell' ) },
                            { key: 'context', label: __( 'Context', 'cinderwell' ) },
                        ] } values={ secs } onChange={ onSecs } />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } showAlignment={ true } />
                    <BackgroundControls value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'quote', label: __( 'Quote', 'cinderwell' ) },
                        { key: 'attribution', label: __( 'Attribution', 'cinderwell' ) },
                        { key: 'byline', label: __( 'Byline', 'cinderwell' ), enabled: attributes.showByline },
                        { key: 'context', label: __( 'Context', 'cinderwell' ), enabled: attributes.showContext },
                    ] } />
                </InspectorControls>
                <figure { ...blockProps }>
                    <div className="cinderwell-quote__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        <blockquote className={ `cinderwell-quote__text${ getTextStyleClassName( attributes, 'quote' ) }` }><RichText tagName="p" identifier="quote" value={ attributes.quote } onChange={ ( value ) => setAttributes( { quote: value } ) } placeholder={ __( 'Quote text…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /></blockquote>
                        <figcaption className="cinderwell-quote__figcaption">
                            <RichText tagName="cite" identifier="attribution" className={ `cinderwell-quote__attribution${ getTextStyleClassName( attributes, 'attribution' ) }` } value={ attributes.attribution } onChange={ ( value ) => setAttributes( { attribution: value } ) } placeholder={ __( 'Attribution…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } />
                            { attributes.showByline && <RichText tagName="span" identifier="byline" className={ `cinderwell-quote__byline${ getTextStyleClassName( attributes, 'byline' ) }` } value={ attributes.byline } onChange={ ( value ) => setAttributes( { byline: value } ) } placeholder={ __( 'Byline…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                            { attributes.showContext && <RichText tagName="p" identifier="context" className={ `cinderwell-quote__context${ getTextStyleClassName( attributes, 'context' ) }` } value={ attributes.context } onChange={ ( value ) => setAttributes( { context: value } ) } placeholder={ __( 'Context…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                        </figcaption>
                    </div>
                </figure>
            </>
        );
    },
    save: ( { attributes } ) => {
        const schema = { '@context': 'https://schema.org', '@type': 'Quotation', 'text': attributes.quote, 'author': attributes.attribution };
        const blockProps = useBlockProps.save( getBackgroundImageProps( {
            className: `cinderwell-quote cinderwell-quote--bg-${ attributes.background } cinderwell-quote--align-${ attributes.alignment }${ getTypographyClassName( attributes ) }`,
            'data-cw-schema': JSON.stringify( schema ),
        }, attributes ) );
        return (
            <figure { ...blockProps }>
                <div className="cinderwell-quote__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    <blockquote className={ `cinderwell-quote__text${ getTextStyleClassName( attributes, 'quote' ) }` }><RichText.Content tagName="p" value={ attributes.quote } /></blockquote>
                    <figcaption className="cinderwell-quote__figcaption">
                        <RichText.Content tagName="cite" className={ `cinderwell-quote__attribution${ getTextStyleClassName( attributes, 'attribution' ) }` } value={ attributes.attribution } />
                        { attributes.showByline && attributes.byline && <RichText.Content tagName="span" className={ `cinderwell-quote__byline${ getTextStyleClassName( attributes, 'byline' ) }` } value={ attributes.byline } /> }
                        { attributes.showContext && attributes.context && <RichText.Content tagName="p" className={ `cinderwell-quote__context${ getTextStyleClassName( attributes, 'context' ) }` } value={ attributes.context } /> }
                    </figcaption>
                </div>
            </figure>
        );
    },
} );
