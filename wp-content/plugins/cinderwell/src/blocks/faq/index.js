import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { Button, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, getBackgroundImageProps, TypographyControls, getTypographyClassName, getTextStyleClassName, getHeadingTagName, SortableItemCard, moveArrayItem } from '../../shared/inspector-controls';
import { ALLOWED_INLINE_FORMATS, ALLOWED_BODY_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes, clientId } ) => {
        const blockProps = useBlockProps( getBackgroundImageProps( { className: `cinderwell-faq cinderwell-faq--bg-${ attributes.background }${ getTypographyClassName( attributes ) }` }, attributes ) );
        const items = attributes.items || [];
        const secs = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, footnote: attributes.showFootnote };
        const onSecs = ( v ) => setAttributes( { showEyebrow: v.eyebrow, showHeading: v.heading, showFootnote: v.footnote } );
        const addItem = () => setAttributes( { items: [ ...items, { id: `faq-${ Date.now() }`, question: '', answer: '' } ] } );
        const updateItem = ( i, f, v ) => setAttributes( { items: items.map( ( it, idx ) => idx === i ? { ...it, [ f ]: v } : it ) } );
        const removeItem = ( i ) => setAttributes( { items: items.filter( ( _, idx ) => idx !== i ) } );
        const moveItem = ( from, to ) => setAttributes( { items: moveArrayItem( items, from, to ) } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="&#10068;" title={ __( 'FAQ', 'cinderwell' ) } description={ __( 'Accordion FAQ with schema', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cinderwell-content-panel">
                        <SectionToggles sections={ [
                            { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                            { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                            { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                        ] } values={ secs } onChange={ onSecs } />
                    </PanelBody>
                    <PanelBody title={ __( 'FAQ items', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
                        { items.map( ( item, i ) => (
                            <SortableItemCard
                                key={ item.id || i }
                                index={ i }
                                total={ items.length }
                                listId={ `${ clientId }-faq` }
                                label={ item.question || `${ __( 'FAQ item', 'cinderwell' ) } ${ i + 1 }` }
                                onMove={ moveItem }
                                onRemove={ () => removeItem( i ) }
                            />
                        ) ) }
                        <Button variant="secondary" className="cw-add-item" onClick={ addItem }>+ { __( 'Add item', 'cinderwell' ) }</Button>
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'question', label: __( 'Questions', 'cinderwell' ), enabled: items.length > 0 },
                        { key: 'answer', label: __( 'Answers', 'cinderwell' ), enabled: items.length > 0 },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <div { ...blockProps }>
                    <div className="cinderwell-faq__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( value ) => setAttributes( { eyebrow: value } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                        { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( value ) => setAttributes( { heading: value } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                        <div className="cinderwell-faq__items">
                            { items.map( ( item, i ) => (
                                <details key={ item.id || i } className="cinderwell-faq__item" open={ i === 0 }>
                                    <summary className={ `cinderwell-faq__question${ getTextStyleClassName( attributes, 'question' ) }` }><RichText tagName="span" value={ item.question } onChange={ ( value ) => updateItem( i, 'question', value ) } placeholder={ __( 'Question…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /></summary>
                                    <div className={ `cinderwell-faq__answer${ getTextStyleClassName( attributes, 'answer' ) }` }><RichText tagName="div" value={ item.answer } onChange={ ( value ) => updateItem( i, 'answer', value ) } placeholder={ __( 'Answer…', 'cinderwell' ) } allowedFormats={ ALLOWED_BODY_FORMATS } /></div>
                                </details>
                            ) ) }
                        </div>
                        { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( value ) => setAttributes( { footnote: value } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                    </div>
                </div>
            </>
        );
    },
    save: ( { attributes } ) => {
        const items = attributes.items || [];
        const schema = { '@context': 'https://schema.org', '@type': 'FAQPage', 'mainEntity': items.map( ( it ) => ( { '@type': 'Question', 'name': it.question, 'acceptedAnswer': { '@type': 'Answer', 'text': it.answer } } ) ) };
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-faq cinderwell-faq--bg-${ attributes.background }${ getTypographyClassName( attributes ) }`, 'data-cw-schema': JSON.stringify( schema ) }, attributes ) );
        return (
            <div { ...blockProps }>
                <div className="cinderwell-faq__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                    { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                    <div className="cinderwell-faq__items">
                        { items.map( ( item, i ) => (
                            <details key={ item.id || i } className="cinderwell-faq__item">
                                <summary className={ `cinderwell-faq__question${ getTextStyleClassName( attributes, 'question' ) }` }><RichText.Content tagName="span" value={ item.question } /></summary>
                                <div className={ `cinderwell-faq__answer${ getTextStyleClassName( attributes, 'answer' ) }` }><RichText.Content tagName="div" value={ item.answer } /></div>
                            </details>
                        ) ) }
                    </div>
                    { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                </div>
            </div>
        );
    },
} );
