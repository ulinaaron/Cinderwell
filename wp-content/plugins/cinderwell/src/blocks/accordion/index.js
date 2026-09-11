import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { Button, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, SectionToggles, LayoutControls, BackgroundControls, getBackgroundImageProps, TypographyControls, getTypographyClassName, getTextStyleClassName, getHeadingTagName, SortableItemCard, moveArrayItem } from '../../shared/inspector-controls';
import { ALLOWED_INLINE_FORMATS, ALLOWED_BODY_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes, clientId } ) => {
        const items = attributes.items || [];
        const blockProps = useBlockProps( getBackgroundImageProps( { className: `cinderwell-accordion cinderwell-accordion--bg-${ attributes.background }${ getTypographyClassName( attributes ) }` }, attributes ) );
        const sections = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, footnote: attributes.showFootnote };
        const updateSections = ( values ) => setAttributes( { showEyebrow: values.eyebrow, showHeading: values.heading, showFootnote: values.footnote } );
        const addItem = () => setAttributes( { items: [ ...items, { id: `accordion-${ Date.now() }`, title: '', content: '' } ] } );
        const updateItem = ( index, field, value ) => setAttributes( { items: items.map( ( item, itemIndex ) => itemIndex === index ? { ...item, [ field ]: value } : item ) } );
        const removeItem = ( index ) => setAttributes( { items: items.filter( ( _, itemIndex ) => itemIndex !== index ) } );
        const moveItem = ( from, to ) => setAttributes( { items: moveArrayItem( items, from, to ) } );

        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="↕" title={ __( 'Accordion', 'cinderwell' ) } description={ __( 'Expandable content panels', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cinderwell-content-panel">
                        <SectionToggles sections={ [
                            { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                            { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                            { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                        ] } values={ sections } onChange={ updateSections } />
                    </PanelBody>
                    <PanelBody title={ __( 'Accordion items', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
                        { items.map( ( item, index ) => (
                            <SortableItemCard
                                key={ item.id || index }
                                index={ index }
                                total={ items.length }
                                listId={ `${ clientId }-accordion` }
                                label={ item.title || `${ __( 'Accordion item', 'cinderwell' ) } ${ index + 1 }` }
                                onMove={ moveItem }
                                onRemove={ () => removeItem( index ) }
                            />
                        ) ) }
                        <Button variant="secondary" className="cw-add-item" onClick={ addItem }>+ { __( 'Add item', 'cinderwell' ) }</Button>
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( value ) => setAttributes( { background: value } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'itemTitle', label: __( 'Item titles', 'cinderwell' ), enabled: items.length > 0 },
                        { key: 'itemContent', label: __( 'Item content', 'cinderwell' ), enabled: items.length > 0 },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <section { ...blockProps }>
                    <div className="cinderwell-accordion__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( value ) => setAttributes( { eyebrow: value } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( value ) => setAttributes( { heading: value } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        <div className="cinderwell-accordion__items">
                            { items.map( ( item, index ) => (
                                <details className="cinderwell-accordion__item" key={ item.id || index } open={ index === 0 }>
                                    <RichText tagName="summary" className={ `cinderwell-accordion__title${ getTextStyleClassName( attributes, 'itemTitle' ) }` } value={ item.title } onChange={ ( value ) => updateItem( index, 'title', value ) } placeholder={ __( 'Accordion title…', 'cinderwell' ) } allowedFormats={ [] } />
                                    <RichText tagName="div" className={ `cinderwell-accordion__content${ getTextStyleClassName( attributes, 'itemContent' ) }` } value={ item.content } onChange={ ( value ) => updateItem( index, 'content', value ) } placeholder={ __( 'Accordion content…', 'cinderwell' ) } allowedFormats={ ALLOWED_BODY_FORMATS } />
                                </details>
                            ) ) }
                        </div>
                        { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( value ) => setAttributes( { footnote: value } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                    </div>
                </section>
            </>
        );
    },
    save: ( { attributes } ) => {
        const items = attributes.items || [];
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-accordion cinderwell-accordion--bg-${ attributes.background }${ getTypographyClassName( attributes ) }` }, attributes ) );

        return (
            <section { ...blockProps }>
                <div className="cinderwell-accordion__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    { attributes.showEyebrow && attributes.eyebrow && <RichText.Content tagName="span" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } /> }
                    { attributes.showHeading && attributes.heading && <RichText.Content tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } /> }
                    <div className="cinderwell-accordion__items">
                        { items.map( ( item, index ) => (
                            <details className="cinderwell-accordion__item" key={ item.id || index }>
                                <RichText.Content tagName="summary" className={ `cinderwell-accordion__title${ getTextStyleClassName( attributes, 'itemTitle' ) }` } value={ item.title } />
                                <RichText.Content tagName="div" className={ `cinderwell-accordion__content${ getTextStyleClassName( attributes, 'itemContent' ) }` } value={ item.content } />
                            </details>
                        ) ) }
                    </div>
                    { attributes.showFootnote && attributes.footnote && <RichText.Content tagName="p" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } /> }
                </div>
            </section>
        );
    },
} );
