import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, getBackgroundImageProps, TypographyControls, getTypographyClassName, getTextStyleClassName, getHeadingTagName, ButtonRepeater, ButtonSave } from '../../shared/inspector-controls';
import { ALLOWED_INLINE_FORMATS, ALLOWED_BODY_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( getBackgroundImageProps( {
            className: `cinderwell-body cinderwell-body--bg-${ attributes.background }${ getTypographyClassName( attributes ) }`,
        }, attributes ) );
        const secs = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, body: attributes.showBody, byline: attributes.showByline, pullquote: attributes.showPullquote, footnote: attributes.showFootnote };
        const onSecs = ( v ) => setAttributes( { showEyebrow: v.eyebrow, showHeading: v.heading, showBody: v.body, showByline: v.byline, showPullquote: v.pullquote, showFootnote: v.footnote } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="&#9776;" title={ __( 'Body', 'cinderwell' ) } description={ __( 'Rich text body content', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cinderwell-content-panel">
                        <SectionToggles sections={ [
                            { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                            { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                            { key: 'body', label: __( 'Body', 'cinderwell' ) },
                            { key: 'byline', label: __( 'Byline', 'cinderwell' ) },
                            { key: 'pullquote', label: __( 'Pullquote', 'cinderwell' ) },
                            { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                        ] } values={ secs } onChange={ onSecs } />
                    </PanelBody>
                    <PanelBody title={ __( 'Buttons', 'cinderwell' ) } initialOpen={ false } className="cinderwell-buttons-panel">
                        <ButtonRepeater buttons={ attributes.buttons } onChange={ ( b ) => setAttributes( { buttons: b } ) } />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'byline', label: __( 'Byline', 'cinderwell' ), enabled: attributes.showByline },
                        { key: 'body', label: __( 'Body', 'cinderwell' ), enabled: attributes.showBody },
                        { key: 'pullquote', label: __( 'Pullquote', 'cinderwell' ), enabled: attributes.showPullquote },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <div { ...blockProps }>
                    <div className="cinderwell-body__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( v ) => setAttributes( { heading: v } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showByline && <RichText tagName="p" identifier="byline" className={ `cinderwell-byline${ getTextStyleClassName( attributes, 'byline' ) }` } value={ attributes.byline } onChange={ ( v ) => setAttributes( { byline: v } ) } placeholder={ __( 'Byline…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showBody && <RichText tagName="div" identifier="bodyContent" className={ `cinderwell-body__content${ getTextStyleClassName( attributes, 'body' ) }` } value={ attributes.bodyContent } onChange={ ( v ) => setAttributes( { bodyContent: v } ) } placeholder={ __( 'Body text…', 'cinderwell' ) } allowedFormats={ ALLOWED_BODY_FORMATS } /> }
                        { attributes.showPullquote && <RichText tagName="blockquote" identifier="pullquote" className={ `cinderwell-pullquote${ getTextStyleClassName( attributes, 'pullquote' ) }` } value={ attributes.pullquote } onChange={ ( v ) => setAttributes( { pullquote: v } ) } placeholder={ __( 'Pullquote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                        <ButtonSave buttons={ attributes.buttons } onChange={ ( buttons ) => setAttributes( { buttons } ) } />
                        { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( v ) => setAttributes( { footnote: v } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                    </div>
                </div>
            </>
        );
    },
    save: ( { attributes } ) => {
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-body cinderwell-body--bg-${ attributes.background }${ getTypographyClassName( attributes ) }` }, attributes ) );
        return (
            <div { ...blockProps }>
                <div className="cinderwell-body__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                    { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                    { attributes.showByline && attributes.byline && <RichText.Content tagName="p" className={ `cinderwell-byline${ getTextStyleClassName( attributes, 'byline' ) }` } value={ attributes.byline } /> }
                    { attributes.showBody && attributes.bodyContent && <RichText.Content tagName="div" className={ `cinderwell-body__content${ getTextStyleClassName( attributes, 'body' ) }` } value={ attributes.bodyContent } /> }
                    { attributes.showPullquote && attributes.pullquote && <RichText.Content tagName="blockquote" className={ `cinderwell-pullquote${ getTextStyleClassName( attributes, 'pullquote' ) }` } value={ attributes.pullquote } /> }
                    <ButtonSave buttons={ attributes.buttons } />
                    { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                </div>
            </div>
        );
    },
} );
