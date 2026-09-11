import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ConditionsPanel } from '../../shared/conditions-panel';
import { BlockIdentity } from '../../shared/inspector-controls';
import { ImageOverlayControls, ImageSettingsControl } from '../../shared/image-controls';
import { getImageClassName, getMediaUrl } from '../../shared/media';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const imageMedia = useSelect( ( select ) => attributes.imageId > 0 ? select( 'core' ).getMedia( attributes.imageId ) : null, [ attributes.imageId ] );
        const imageUrl = attributes.imageUrl || getMediaUrl( imageMedia );
        const blockProps = useBlockProps( { className: `cinderwell-atom-image cw-image-control-host${ attributes.imageId > 0 ? '' : ' is-empty' }` } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="▧" title={ __( 'Image', 'cinderwell' ) } description={ __( 'Standalone media', 'cinderwell' ) } />
                    { attributes.imageId > 0 && (
                    <PanelBody title={ __( 'Media', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
                            <ImageSettingsControl
                                alt={ attributes.imageAlt }
                                fit={ attributes.imageFit }
                                position={ attributes.imagePosition }
                                aspect={ attributes.imageAspect }
                                label={ __( 'Image settings', 'cinderwell' ) }
                                onAltChange={ ( imageAlt ) => setAttributes( { imageAlt } ) }
                                onFitChange={ ( imageFit ) => setAttributes( { imageFit } ) }
                                onPositionChange={ ( imagePosition ) => setAttributes( { imagePosition } ) }
                                onAspectChange={ ( imageAspect ) => setAttributes( { imageAspect } ) }
                            />
                    </PanelBody>
                    ) }
                    <ConditionsPanel attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <figure { ...blockProps }>
                    { attributes.imageId > 0 ? (
                        <>
                            <img
                                className={ getImageClassName( attributes.imageFit, attributes.imagePosition, attributes.imageAspect ).trim() || undefined }
                                src={ imageUrl }
                                alt={ attributes.imageAlt }
                            />
                            <ImageOverlayControls
                                imageId={ attributes.imageId }
                                label={ __( 'image', 'cinderwell' ) }
                                onSelect={ ( media ) => setAttributes( { imageId: media.id, imageUrl: getMediaUrl( media ), imageAlt: attributes.imageAlt || media.alt || '' } ) }
                                onRemove={ () => setAttributes( { imageId: 0, imageUrl: '', imageAlt: '' } ) }
                            />
                            <RichText
                                tagName="figcaption"
                                identifier="caption"
                                className="cinderwell-atom-image__caption"
                                value={ attributes.caption }
                                onChange={ ( v ) => setAttributes( { caption: v } ) }
                                placeholder={ __( 'Caption…', 'cinderwell' ) }
                                allowedFormats={ [ 'core/italic', 'core/link' ] }
                            />
                        </>
                    ) : (
                        <>
                            <span className="cw-image-control-placeholder">{ __( 'No image selected', 'cinderwell' ) }</span>
                            <ImageOverlayControls imageId={ 0 } label={ __( 'image', 'cinderwell' ) } onSelect={ ( media ) => setAttributes( { imageId: media.id, imageUrl: getMediaUrl( media ), imageAlt: media.alt || '' } ) } />
                        </>
                    ) }
                </figure>
            </>
        );
    },
    save: ( { attributes } ) => {
        if ( ! attributes.imageId ) return null;
        const blockProps = useBlockProps.save( { className: 'cinderwell-atom-image' } );
        return (
            <figure { ...blockProps }>
                <img className={ getImageClassName( attributes.imageFit, attributes.imagePosition, attributes.imageAspect ).trim() || undefined } src={ attributes.imageUrl || `wp-content/uploads/${ attributes.imageId }` } alt={ attributes.imageAlt } />
                <RichText.Content tagName="figcaption" className="cinderwell-atom-image__caption" value={ attributes.caption } />
            </figure>
        );
    },
} );
