import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, getBackgroundImageProps, TypographyControls, getTypographyClassName, getTextStyleClassName, getHeadingTagName, getCopyMeasureClassName, ButtonRepeater, ButtonSave } from '../../shared/inspector-controls';
import { ALLOWED_INLINE_FORMATS, ALLOWED_BODY_FORMATS } from '../../shared/rich-text';
import { canEditControl, filterEditorAccessChanges } from '../../shared/editor-access';
import { VariationPicker, resolveBlockVariation } from '../../shared/variation-picker';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const resolvedLayout = resolveBlockVariation( metadata.name, attributes.layout, 'standard' );
        const activeLayout = resolvedLayout?.slug || 'standard';
        const blockProps = useBlockProps( getBackgroundImageProps( {
            className: `cinderwell-cta cinderwell-cta--layout-${ activeLayout } cinderwell-cta--bg-${ attributes.background } cinderwell-cta--align-${ attributes.alignment }${ getCopyMeasureClassName( attributes ) }${ getTypographyClassName( attributes ) }`,
        }, attributes ) );
        const secs = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, body: attributes.showBody, footnote: attributes.showFootnote };
        const onSecs = ( v ) => setAttributes( { showEyebrow: v.eyebrow, showHeading: v.heading, showBody: v.body, showFootnote: v.footnote } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="&#128226;" title={ __( 'CTA', 'cinderwell' ) } description={ __( 'Call-to-action with buttons', 'cinderwell' ) } />
                    { canEditControl( 'layout' ) && (
                        <VariationPicker blockName={ metadata.name } value={ attributes.layout } fallback="standard" title={ __( 'Variation', 'cinderwell' ) } onChange={ ( variation ) => setAttributes( filterEditorAccessChanges( variation.attributes || {}, attributes ) ) } />
                    ) }
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cinderwell-content-panel">
                        <SectionToggles sections={ [
                            { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                            { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                            { key: 'body', label: __( 'Body', 'cinderwell' ) },
                            { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                        ] } values={ secs } onChange={ onSecs } />
                    </PanelBody>
                    <PanelBody title={ __( 'Buttons', 'cinderwell' ) } initialOpen={ false } className="cinderwell-buttons-panel">
                        <ButtonRepeater buttons={ attributes.buttons } onChange={ ( b ) => setAttributes( { buttons: b } ) } />
                    </PanelBody>
                    <LayoutControls controlled={ resolvedLayout?.controlled || {} } attributes={ attributes } setAttributes={ setAttributes } showAlignment={ true } />
                    <BackgroundControls controlled={ resolvedLayout?.controlled || {} } value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls controlled={ resolvedLayout?.controlled || {} } attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'body', label: __( 'Body', 'cinderwell' ), enabled: attributes.showBody },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <div { ...blockProps }>
                    <div className="cinderwell-cta__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( v ) => setAttributes( { heading: v } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showBody && <RichText tagName="div" identifier="bodyContent" className={ `cinderwell-cta__body${ getTextStyleClassName( attributes, 'body' ) }` } value={ attributes.bodyContent } onChange={ ( v ) => setAttributes( { bodyContent: v } ) } placeholder={ __( 'Body text…', 'cinderwell' ) } allowedFormats={ ALLOWED_BODY_FORMATS } /> }
                        <ButtonSave buttons={ attributes.buttons } onChange={ ( buttons ) => setAttributes( { buttons } ) } />
                        { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( v ) => setAttributes( { footnote: v } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                    </div>
                </div>
            </>
        );
    },
    save: ( { attributes } ) => {
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-cta cinderwell-cta--bg-${ attributes.background } cinderwell-cta--align-${ attributes.alignment }${ getCopyMeasureClassName( attributes ) }${ getTypographyClassName( attributes ) }` }, attributes ) );
        return (
            <div { ...blockProps }>
                <div className="cinderwell-cta__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                    { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                    { attributes.showBody && attributes.bodyContent && <RichText.Content tagName="div" className={ `cinderwell-cta__body${ getTextStyleClassName( attributes, 'body' ) }` } value={ attributes.bodyContent } /> }
                    <ButtonSave buttons={ attributes.buttons } />
                    { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                </div>
            </div>
        );
    },
} );
