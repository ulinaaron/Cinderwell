import { createBlock, registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, RichText, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { Button, PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import {
    BackgroundControls,
    BlockIdentity,
    ChildBlockInheritanceControl,
    LayoutControls,
    ResponsiveSegmentedControl,
    SectionToggles,
    SegmentedControl,
    TypographyControls,
    getBackgroundImageProps,
    getHeadingTagName,
    getResponsiveModifierClassName,
    getTextStyleClassName,
    getTypographyClassName,
} from '../../shared/inspector-controls';
import { ALLOWED_INLINE_FORMATS } from '../../shared/rich-text';
import metadata from './block.json';

const ALLOWED_BLOCKS = [ 'cinderwell/tab-item' ];
const TEMPLATE = [
    [ 'cinderwell/tab-item', { label: 'Tab 1' } ],
    [ 'cinderwell/tab-item', { label: 'Tab 2' } ],
];

const styleOptions = [
    { value: 'underline', label: __( 'Underline', 'cinderwell' ) },
    { value: 'pills', label: __( 'Pills', 'cinderwell' ) },
    { value: 'boxed', label: __( 'Boxed', 'cinderwell' ) },
];

const orientationOptions = [
    { value: 'horizontal', label: __( 'Horizontal', 'cinderwell' ) },
    { value: 'vertical', label: __( 'Vertical', 'cinderwell' ) },
];

const positionOptions = [
    { value: 'start', label: __( 'Start', 'cinderwell' ) },
    { value: 'center', label: __( 'Center', 'cinderwell' ) },
    { value: 'end', label: __( 'End', 'cinderwell' ) },
    { value: 'stretch', label: __( 'Stretch', 'cinderwell' ) },
];

const verticalSideOptions = [
    { value: 'left', label: __( 'Left', 'cinderwell' ) },
    { value: 'right', label: __( 'Right', 'cinderwell' ) },
];

const getTabsClassName = ( attributes ) => {
    const responsiveOrientation = getResponsiveModifierClassName( 'cinderwell-tabs', 'orientation', {
        tablet: attributes.orientationTablet,
        mobile: attributes.orientationMobile,
    } );
    const responsivePosition = getResponsiveModifierClassName( 'cinderwell-tabs', 'position', {
        tablet: attributes.tabPositionTablet,
        mobile: attributes.tabPositionMobile,
    } );

    const childBackgroundClass = attributes.childBackgroundMode === 'individual' ? ' cinderwell-tabs--children-own-backgrounds' : '';

    return `cinderwell-tabs cinderwell-tabs--bg-${ attributes.background } cinderwell-tabs--style-${ attributes.tabStyle || 'underline' } cinderwell-tabs--orientation-${ attributes.orientation || 'horizontal' } cinderwell-tabs--vertical-${ attributes.verticalSide || 'left' } cinderwell-tabs--position-${ attributes.tabPosition || 'start' }${ childBackgroundClass }${ responsiveOrientation }${ responsivePosition }${ getTypographyClassName( attributes ) }`;
};

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes, clientId } ) => {
        const { insertBlocks, selectBlock, updateBlockAttributes } = useDispatch( 'core/block-editor' );
        const { tabBlocks, activeTabClientId } = useSelect( ( select ) => {
            const blockEditor = select( 'core/block-editor' );
            const blocks = blockEditor.getBlocks( clientId );
            const selectedClientId = blockEditor.getSelectedBlockClientId();
            const selectedParents = selectedClientId ? blockEditor.getBlockParents( selectedClientId ) : [];
            const activeBlock = blocks.find( ( block ) => block.clientId === selectedClientId || selectedParents.includes( block.clientId ) );

            return {
                tabBlocks: blocks,
                activeTabClientId: activeBlock?.clientId || blocks[ 0 ]?.clientId || '',
            };
        }, [ clientId ] );
        const blockProps = useBlockProps( getBackgroundImageProps( { className: getTabsClassName( attributes ) }, attributes ) );
        const innerBlocksProps = useInnerBlocksProps(
            { className: 'cinderwell-tabs__editor-panels' },
            { allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE, templateLock: false, renderAppender: false }
        );
        const sections = { eyebrow: attributes.showEyebrow, heading: attributes.showHeading, footnote: attributes.showFootnote };
        const updateSections = ( values ) => setAttributes( { showEyebrow: values.eyebrow, showHeading: values.heading, showFootnote: values.footnote } );
        const addTab = () => {
            const tab = createBlock( 'cinderwell/tab-item', { label: sprintf( __( 'Tab %d', 'cinderwell' ), tabBlocks.length + 1 ) } );
            insertBlocks( tab, tabBlocks.length, clientId );
            selectBlock( tab.clientId );
        };
        const responsiveOptions = ( options ) => ( breakpoint ) => breakpoint === 'desktop'
            ? options
            : [ { value: 'auto', label: __( 'Auto', 'cinderwell' ) }, ...options ];

        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="▤" title={ __( 'Tabs', 'cinderwell' ) } description={ __( 'Tabbed block areas', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cinderwell-content-panel">
                        <SectionToggles sections={ [
                            { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ) },
                            { key: 'heading', label: __( 'Heading', 'cinderwell' ) },
                            { key: 'footnote', label: __( 'Footnote', 'cinderwell' ) },
                        ] } values={ sections } onChange={ updateSections } />
                        <ChildBlockInheritanceControl
                            value={ attributes.childBackgroundMode || 'inherit' }
                            onChange={ ( childBackgroundMode ) => setAttributes( { childBackgroundMode } ) }
                        />
                    </PanelBody>
                    <PanelBody title={ __( 'Tabs', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
                        <SegmentedControl label={ __( 'Tab style', 'cinderwell' ) } value={ attributes.tabStyle || 'underline' } options={ styleOptions } onChange={ ( tabStyle ) => setAttributes( { tabStyle } ) } />
                        <ResponsiveSegmentedControl
                            label={ __( 'Orientation', 'cinderwell' ) }
                            values={ { desktop: attributes.orientation || 'horizontal', tablet: attributes.orientationTablet || 'auto', mobile: attributes.orientationMobile || 'vertical' } }
                            options={ responsiveOptions( orientationOptions ) }
                            autoHelp={ { tablet: __( 'Auto follows desktop orientation.', 'cinderwell' ), mobile: __( 'Auto follows tablet orientation.', 'cinderwell' ) } }
                            onChange={ ( breakpoint, value ) => setAttributes( breakpoint === 'desktop' ? { orientation: value } : { [ breakpoint === 'tablet' ? 'orientationTablet' : 'orientationMobile' ]: value } ) }
                        />
                        <SegmentedControl
                            label={ __( 'Vertical side', 'cinderwell' ) }
                            value={ attributes.verticalSide || 'left' }
                            options={ verticalSideOptions }
                            onChange={ ( verticalSide ) => setAttributes( { verticalSide } ) }
                        />
                        <p className="cw-responsive-control__help">{ __( 'Used when tabs are vertical. On mobile, tabs remain above the content.', 'cinderwell' ) }</p>
                        <ResponsiveSegmentedControl
                            label={ __( 'Tab alignment', 'cinderwell' ) }
                            values={ { desktop: attributes.tabPosition || 'start', tablet: attributes.tabPositionTablet || 'auto', mobile: attributes.tabPositionMobile || 'stretch' } }
                            options={ responsiveOptions( positionOptions ) }
                            autoHelp={ { tablet: __( 'Auto follows desktop positioning.', 'cinderwell' ), mobile: __( 'Auto follows tablet positioning.', 'cinderwell' ) } }
                            onChange={ ( breakpoint, value ) => setAttributes( breakpoint === 'desktop' ? { tabPosition: value } : { [ breakpoint === 'tablet' ? 'tabPositionTablet' : 'tabPositionMobile' ]: value } ) }
                        />
                        <div className="cinderwell-tabs__sidebar-items">
                            { tabBlocks.map( ( block, index ) => (
                                <button
                                    type="button"
                                    className={ block.clientId === activeTabClientId ? 'is-active' : '' }
                                    key={ block.clientId }
                                    onClick={ () => selectBlock( block.clientId ) }
                                >
                                    { block.attributes.label || sprintf( __( 'Tab %d', 'cinderwell' ), index + 1 ) }
                                </button>
                            ) ) }
                        </div>
                        <Button variant="secondary" className="cw-add-item" onClick={ addTab }>+ { __( 'Add tab', 'cinderwell' ) }</Button>
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( background ) => setAttributes( { background } ) } attributes={ attributes } setAttributes={ setAttributes } />
                    <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                        { key: 'eyebrow', label: __( 'Eyebrow', 'cinderwell' ), enabled: attributes.showEyebrow },
                        { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                        { key: 'tabLabel', label: __( 'Tab labels', 'cinderwell' ), enabled: tabBlocks.length > 0 },
                        { key: 'footnote', label: __( 'Footnote', 'cinderwell' ), enabled: attributes.showFootnote },
                    ] } />
                </InspectorControls>
                <section { ...blockProps }>
                    <div className="cinderwell-tabs__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        { attributes.showEyebrow && <RichText tagName="span" identifier="eyebrow" className={ `cinderwell-eyebrow${ getTextStyleClassName( attributes, 'eyebrow' ) }` } value={ attributes.eyebrow } onChange={ ( eyebrow ) => setAttributes( { eyebrow } ) } placeholder={ __( 'Eyebrow…', 'cinderwell' ) } allowedFormats={ [] } /> }
                        { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } identifier="heading" className={ `cinderwell-heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( heading ) => setAttributes( { heading } ) } placeholder={ __( 'Heading…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                        <div className="cinderwell-tabs__layout">
                            <div className="cinderwell-tabs__tablist" role="tablist">
                                { tabBlocks.map( ( block, index ) => (
                                    <div
                                        role="tab"
                                        tabIndex={ block.clientId === activeTabClientId ? 0 : -1 }
                                        aria-selected={ block.clientId === activeTabClientId }
                                        className={ `cinderwell-tabs__tab${ block.clientId === activeTabClientId ? ' is-active' : '' }${ getTextStyleClassName( attributes, 'tabLabel' ) }` }
                                        key={ block.clientId }
                                        onClick={ () => selectBlock( block.clientId ) }
                                    >
                                        <RichText
                                            tagName="span"
                                            identifier={ `tab-label-${ block.clientId }` }
                                            className="cinderwell-tabs__tab-label"
                                            value={ block.attributes.label }
                                            onChange={ ( label ) => updateBlockAttributes( block.clientId, { label } ) }
                                            placeholder={ sprintf( __( 'Tab %d', 'cinderwell' ), index + 1 ) }
                                            allowedFormats={ [] }
                                        />
                                    </div>
                                ) ) }
                            </div>
                            <div { ...innerBlocksProps } />
                        </div>
                        { attributes.showFootnote && <RichText tagName="p" identifier="footnote" className={ `cinderwell-footnote${ getTextStyleClassName( attributes, 'footnote' ) }` } value={ attributes.footnote } onChange={ ( footnote ) => setAttributes( { footnote } ) } placeholder={ __( 'Footnote…', 'cinderwell' ) } allowedFormats={ ALLOWED_INLINE_FORMATS } /> }
                    </div>
                </section>
            </>
        );
    },
    save: () => <InnerBlocks.Content />,
} );
