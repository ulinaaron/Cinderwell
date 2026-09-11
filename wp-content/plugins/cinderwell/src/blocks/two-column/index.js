import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, getBackgroundImageProps, TypographyControls, getTypographyClassName, getTextStyleClassName, getHeadingTagName, ButtonRepeater, ButtonSave } from '../../shared/inspector-controls';
import { ALLOWED_INLINE_FORMATS, ALLOWED_BODY_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( getBackgroundImageProps( { className: `cinderwell-two-column cinderwell-two-column--bg-${ attributes.background }${ getTypographyClassName( attributes ) }` }, attributes ) );
        const secs = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, footnote: attributes.showFootnote };
        const onSecs = ( v ) => setAttributes( { showEyebrow: v.eyebrow, showHeading: v.heading, showFootnote: v.footnote } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="&#9783;" title={ __( 'Two Column', 'cinderwell' ) } description={ __( 'Two-column layout', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cinderwell-content-panel">
                        <SectionToggles sections={ [
                            { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                            { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                            { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                        ] } values={ secs } onChange={ onSecs } />
                    </PanelBody>
                    <PanelBody title={ __( 'Left column buttons', 'cinderwell' ) } initialOpen={ true } className="cinderwell-buttons-panel">
                        <ButtonRepeater buttons={ attributes.leftButtons } onChange={ ( v ) => setAttributes( { leftButtons: v } ) } />
                    </PanelBody>
                    <PanelBody title={ __( 'Right column buttons', 'cinderwell' ) } initialOpen={ true } className="cinderwell-buttons-panel">
                        <ButtonRepeater buttons={ attributes.rightButtons } onChange={ ( v ) => setAttributes( { rightButtons: v } ) } />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'columns', label: __( 'Column content', 'cinderwell' ) },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <div { ...blockProps }>
                    <div className="cinderwell-two-column__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        <div className="cinderwell-two-column__header">
                            { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( v ) => setAttributes( { eyebrow: v } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                            { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( v ) => setAttributes( { heading: v } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        </div>
                        <div className="cinderwell-two-column__grid">
                            <div className="cinderwell-two-column__left">
                                <RichText tagName="div" identifier="leftContent" className={ `cinderwell-two-column__content${ getTextStyleClassName( attributes, 'columns' ) }` } value={ attributes.leftContent } onChange={ ( v ) => setAttributes( { leftContent: v } ) } placeholder={ __( 'Left column…', 'cinderwell' ) } allowedFormats={ ALLOWED_BODY_FORMATS } />
                                <ButtonSave buttons={ attributes.leftButtons } onChange={ ( leftButtons ) => setAttributes( { leftButtons } ) } />
                            </div>
                            <div className="cinderwell-two-column__right">
                                <RichText tagName="div" identifier="rightContent" className={ `cinderwell-two-column__content${ getTextStyleClassName( attributes, 'columns' ) }` } value={ attributes.rightContent } onChange={ ( v ) => setAttributes( { rightContent: v } ) } placeholder={ __( 'Right column…', 'cinderwell' ) } allowedFormats={ ALLOWED_BODY_FORMATS } />
                                <ButtonSave buttons={ attributes.rightButtons } onChange={ ( rightButtons ) => setAttributes( { rightButtons } ) } />
                            </div>
                        </div>
                            { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( v ) => setAttributes( { footnote: v } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                    </div>
                </div>
            </>
        );
    },
    save: ( { attributes } ) => {
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-two-column cinderwell-two-column--bg-${ attributes.background }${ getTypographyClassName( attributes ) }` }, attributes ) );
        return (
            <div { ...blockProps }>
                <div className="cinderwell-two-column__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    <div className="cinderwell-two-column__header">
                    { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                    { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                    </div>
                    <div className="cinderwell-two-column__grid">
                        <div className="cinderwell-two-column__left">
                            <RichText.Content tagName="div" className={ `cinderwell-two-column__content${ getTextStyleClassName( attributes, 'columns' ) }` } value={ attributes.leftContent } />
                            <ButtonSave buttons={ attributes.leftButtons } />
                        </div>
                        <div className="cinderwell-two-column__right">
                            <RichText.Content tagName="div" className={ `cinderwell-two-column__content${ getTextStyleClassName( attributes, 'columns' ) }` } value={ attributes.rightContent } />
                            <ButtonSave buttons={ attributes.rightButtons } />
                        </div>
                    </div>
                    { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                </div>
            </div>
        );
    },
} );
