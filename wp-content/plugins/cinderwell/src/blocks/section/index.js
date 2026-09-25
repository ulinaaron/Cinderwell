import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, ChildBlockInheritanceControl, LayoutControls, BackgroundControls, getBackgroundImageProps } from '../../shared/inspector-controls';
import { filterEditorAccessChanges } from '../../shared/editor-access';
import { VariationPicker, resolveBlockVariation } from '../../shared/variation-picker';
import metadata from './block.json';

const videoPositions = {
    top: 'center top', bottom: 'center bottom', left: 'left center', right: 'right center',
    'top-left': 'left top', 'top-right': 'right top', 'bottom-left': 'left bottom', 'bottom-right': 'right bottom',
};

const BackgroundVideo = ( { attributes, editor = false } ) => attributes.backgroundVideoUrl ? <>
    <video
        className="cinderwell-background-video"
        src={ attributes.backgroundVideoUrl }
        poster={ attributes.backgroundVideoPosterUrl || undefined }
        style={ { objectFit: attributes.backgroundVideoFit || 'cover', objectPosition: videoPositions[ attributes.backgroundVideoPosition ] || 'center center' } }
        autoPlay={ ! editor }
        muted
        loop
        playsInline
        aria-hidden="true"
        tabIndex="-1"
    />
    { ! editor && <button type="button" className="cinderwell-background-video__toggle" data-play-label={ __( 'Play background video', 'cinderwell' ) } data-pause-label={ __( 'Pause background video', 'cinderwell' ) } aria-label={ __( 'Pause background video', 'cinderwell' ) } aria-pressed="true"><span className="cinderwell-background-video__toggle-icon" aria-hidden="true">Ⅱ</span><span className="screen-reader-text">{ __( 'Pause background video', 'cinderwell' ) }</span></button> }
</> : null;

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const resolvedLayout = resolveBlockVariation( metadata.name, attributes.layout, 'standard' );
        const activeLayout = resolvedLayout.slug || 'standard';
        const layoutClassName = activeLayout === 'standard' ? '' : ` cinderwell-section--layout-${ activeLayout }`;
        const childBackgroundClass = attributes.childBackgroundMode === 'individual' ? ' cinderwell-section--children-own-backgrounds' : '';
        const hasVideo = Boolean( attributes.backgroundVideoUrl );
        const mediaAttributes = hasVideo ? { ...attributes, backgroundImage: 0, backgroundImageUrl: '' } : attributes;
        const blockProps = useBlockProps( getBackgroundImageProps( { className: `cinderwell-section${ layoutClassName } cinderwell-section--bg-${ attributes.background }${ childBackgroundClass }${ hasVideo ? ` cw-has-background-video cw-background-overlay-${ attributes.backgroundOverlay || 'none' }` : '' }` }, mediaAttributes ) );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="§" title={ __( 'Section', 'cinderwell' ) } description={ __( 'Flexible block container', 'cinderwell' ) } />
                    <VariationPicker blockName={ metadata.name } value={ attributes.layout } fallback="standard" title={ __( 'Variation', 'cinderwell' ) } onChange={ ( variation ) => setAttributes( filterEditorAccessChanges( variation.attributes || {}, attributes ) ) } />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-appearance">
                        <ChildBlockInheritanceControl
                            value={ attributes.childBackgroundMode || 'inherit' }
                            onChange={ ( childBackgroundMode ) => setAttributes( { childBackgroundMode } ) }
                        />
                    </PanelBody>
                    <LayoutControls controlled={ resolvedLayout?.controlled || {} } attributes={ attributes } setAttributes={ setAttributes } />
                    <BackgroundControls controlled={ resolvedLayout?.controlled || {} } value={ attributes.background } onChange={ ( v ) => setAttributes( { background: v } ) } attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <section { ...blockProps }>
                    <BackgroundVideo attributes={ attributes } editor />
                    <div className="cinderwell-section__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                        <InnerBlocks />
                    </div>
                </section>
            </>
        );
    },
    save: ( { attributes } ) => {
        const childBackgroundClass = attributes.childBackgroundMode === 'individual' ? ' cinderwell-section--children-own-backgrounds' : '';
        const hasVideo = Boolean( attributes.backgroundVideoUrl );
        const mediaAttributes = hasVideo ? { ...attributes, backgroundImage: 0, backgroundImageUrl: '' } : attributes;
        const blockProps = useBlockProps.save( getBackgroundImageProps( { className: `cinderwell-section cinderwell-section--bg-${ attributes.background }${ childBackgroundClass }${ hasVideo ? ` cw-has-background-video cw-background-overlay-${ attributes.backgroundOverlay || 'none' }` : '' }` }, mediaAttributes ) );
        return (
            <section { ...blockProps } data-cinderwell-background-video={ hasVideo ? 'true' : undefined }>
                <BackgroundVideo attributes={ attributes } />
                <div className="cinderwell-section__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    <InnerBlocks.Content />
                </div>
            </section>
        );
    },
} );
