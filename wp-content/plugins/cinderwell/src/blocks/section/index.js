import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, ChildBlockInheritanceControl, LayoutControls, BackgroundControls, getBackgroundImageProps } from '../../shared/inspector-controls';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const childBackgroundClass = attributes.childBackgroundMode === 'individual' ? ' cinderwell-section--children-own-backgrounds' : '';
        const blockProps = useBlockProps( getBackgroundImageProps( { className: `cinderwell-section cinderwell-section--bg-${ attributes.background }${ childBackgroundClass }` }, attributes ) );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="§" title={ __( 'Section', 'cinderwell' ) } description={ __( 'Flexible block container', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-appearance">
                        <ChildBlockInheritanceControl
                            value={ attributes.childBackgroundMode || 'inherit' }
                            onChange={ ( childBackgroundMode ) => setAttributes( { childBackgroundMode } ) }
                        />
                    </PanelBody>
                    <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <section { ...blockProps }>
                    <div className="cinderwell-section__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        <InnerBlocks />
                    </div>
                </section>
            </>
        );
    },
    save: ( { attributes } ) => {
        const childBackgroundClass = attributes.childBackgroundMode === 'individual' ? ' cinderwell-section--children-own-backgrounds' : '';
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-section cinderwell-section--bg-${ attributes.background }${ childBackgroundClass }` }, attributes ) );
        return (
            <section { ...blockProps }>
                <div className="cinderwell-section__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    <InnerBlocks.Content />
                </div>
            </section>
        );
    },
} );
