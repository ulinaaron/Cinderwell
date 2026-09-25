import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, MediaUpload, MediaUploadCheck, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BlockIdentity, LayoutControls, SegmentedControl, ToggleRow } from '../../shared/inspector-controls';
import metadata from './block.json';

const aspectOptions = [
    { value: 'auto', label: __( 'Original', 'cinderwell' ) },
    { value: 'wide', label: __( '16:9', 'cinderwell' ) },
    { value: 'landscape', label: __( '4:3', 'cinderwell' ) },
    { value: 'square', label: __( '1:1', 'cinderwell' ) },
    { value: 'portrait', label: __( '3:4', 'cinderwell' ) },
];

const getEmbedPreview = ( url ) => {
    try {
        const parsed = new URL( url );
        if ( parsed.hostname.includes( 'youtu.be' ) ) return `https://www.youtube-nocookie.com/embed/${ parsed.pathname.replace( '/', '' ) }`;
        if ( parsed.hostname.includes( 'youtube.com' ) ) {
            const id = parsed.searchParams.get( 'v' ) || parsed.pathname.split( '/' ).filter( Boolean ).pop();
            return id ? `https://www.youtube-nocookie.com/embed/${ id }` : '';
        }
        if ( parsed.hostname.includes( 'vimeo.com' ) ) {
            const id = parsed.pathname.split( '/' ).filter( Boolean ).pop();
            return id ? `https://player.vimeo.com/video/${ id }` : '';
        }
    } catch ( error ) {
        return '';
    }
    return '';
};

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const isUpload = attributes.sourceType !== 'embed';
        const embedPreview = getEmbedPreview( attributes.embedUrl );
        const hasMedia = isUpload ? attributes.videoUrl : attributes.embedUrl;
        const blockProps = useBlockProps( { className: `cinderwell-video cinderwell-video--aspect-${ attributes.aspect || 'wide' }` } );

        return <>
            <InspectorControls>
                <BlockIdentity icon="▶" title={ __( 'Video', 'cinderwell' ) } description={ __( 'Uploaded or embedded responsive video', 'cinderwell' ) } />
                <PanelBody title={ __( 'Video', 'cinderwell' ) } initialOpen className="cw-panel cw-access-media">
                    <SegmentedControl label={ __( 'Source', 'cinderwell' ) } value={ attributes.sourceType || 'upload' } options={ [ { value: 'upload', label: __( 'Upload', 'cinderwell' ) }, { value: 'embed', label: __( 'Embed', 'cinderwell' ) } ] } onChange={ ( sourceType ) => setAttributes( { sourceType } ) } />
                    { isUpload ? <MediaUploadCheck><MediaUpload value={ attributes.videoId } allowedTypes={ [ 'video' ] } onSelect={ ( media ) => setAttributes( { videoId: media.id, videoUrl: media.url || media.source_url || '' } ) } render={ ( { open } ) => <Button variant="secondary" className="cw-add-item" onClick={ open }>{ attributes.videoUrl ? __( 'Replace video', 'cinderwell' ) : `+ ${ __( 'Choose video', 'cinderwell' ) }` }</Button> } /></MediaUploadCheck> : <TextControl label={ __( 'Video URL', 'cinderwell' ) } help={ __( 'Paste a YouTube, Vimeo, or another supported oEmbed URL.', 'cinderwell' ) } value={ attributes.embedUrl } onChange={ ( embedUrl ) => setAttributes( { embedUrl } ) } /> }
                    { isUpload && attributes.videoUrl && <Button variant="tertiary" isDestructive onClick={ () => setAttributes( { videoId: 0, videoUrl: '' } ) }>{ __( 'Remove video', 'cinderwell' ) }</Button> }
                </PanelBody>
                { isUpload && <PanelBody title={ __( 'Playback', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-fields-panel">
                    <div className="cw-toggle-list">
                        <ToggleRow label={ __( 'Show controls', 'cinderwell' ) } checked={ attributes.controls } onChange={ ( controls ) => setAttributes( { controls } ) } />
                        <ToggleRow label={ __( 'Autoplay', 'cinderwell' ) } checked={ attributes.autoplay } onChange={ ( autoplay ) => setAttributes( { autoplay, ...( autoplay ? { muted: true } : {} ) } ) } />
                        <ToggleRow label={ __( 'Loop', 'cinderwell' ) } checked={ attributes.loop } onChange={ ( loop ) => setAttributes( { loop } ) } />
                        <ToggleRow label={ __( 'Muted', 'cinderwell' ) } checked={ attributes.muted } onChange={ ( muted ) => setAttributes( { muted, ...( ! muted && attributes.autoplay ? { autoplay: false } : {} ) } ) } />
                    </div>
                    <SelectControl label={ __( 'Preload', 'cinderwell' ) } value={ attributes.preload } options={ [ { value: 'none', label: __( 'None', 'cinderwell' ) }, { value: 'metadata', label: __( 'Metadata', 'cinderwell' ) }, { value: 'auto', label: __( 'Auto', 'cinderwell' ) } ] } onChange={ ( preload ) => setAttributes( { preload } ) } />
                </PanelBody> }
                <PanelBody title={ __( 'Presentation', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-fields-panel">
                    <SegmentedControl label={ __( 'Aspect ratio', 'cinderwell' ) } value={ attributes.aspect || 'wide' } options={ aspectOptions } onChange={ ( aspect ) => setAttributes( { aspect } ) } />
                    { isUpload && <SegmentedControl label={ __( 'Video fit', 'cinderwell' ) } value={ attributes.fit || 'contain' } options={ [ { value: 'contain', label: __( 'Contain', 'cinderwell' ) }, { value: 'cover', label: __( 'Cover', 'cinderwell' ) } ] } onChange={ ( fit ) => setAttributes( { fit } ) } /> }
                    { isUpload && <MediaUploadCheck><MediaUpload value={ attributes.posterId } allowedTypes={ [ 'image' ] } onSelect={ ( media ) => setAttributes( { posterId: media.id, posterUrl: media.url || media.source_url || '' } ) } render={ ( { open } ) => <Button variant="secondary" className="cw-add-item" onClick={ open }>{ attributes.posterUrl ? __( 'Replace poster image', 'cinderwell' ) : `+ ${ __( 'Add poster image', 'cinderwell' ) }` }</Button> } /></MediaUploadCheck> }
                    { isUpload && attributes.posterUrl && <Button variant="tertiary" isDestructive onClick={ () => setAttributes( { posterId: 0, posterUrl: '' } ) }>{ __( 'Remove poster', 'cinderwell' ) }</Button> }
                    <TextControl label={ __( 'Caption', 'cinderwell' ) } value={ attributes.caption } onChange={ ( caption ) => setAttributes( { caption } ) } />
                </PanelBody>
                { isUpload && <PanelBody title={ __( 'Captions', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-fields-panel">
                    <TextControl label={ __( 'WebVTT file URL', 'cinderwell' ) } help={ __( 'Add a .vtt caption file for spoken content.', 'cinderwell' ) } value={ attributes.captionsUrl } onChange={ ( captionsUrl ) => setAttributes( { captionsUrl } ) } />
                    { attributes.captionsUrl && <><TextControl label={ __( 'Track label', 'cinderwell' ) } value={ attributes.captionsLabel } onChange={ ( captionsLabel ) => setAttributes( { captionsLabel } ) } /><TextControl label={ __( 'Language code', 'cinderwell' ) } value={ attributes.captionsLanguage } onChange={ ( captionsLanguage ) => setAttributes( { captionsLanguage } ) } /></> }
                </PanelBody> }
                <LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
            </InspectorControls>
            <figure { ...blockProps }>
                <div className="cinderwell-video__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width || 'wide' })` } }>
                    <div className="cinderwell-video__frame">
                        { isUpload && attributes.videoUrl && <video src={ attributes.videoUrl } poster={ attributes.posterUrl || undefined } controls={ attributes.controls } muted={ attributes.muted } loop={ attributes.loop } playsInline style={ { objectFit: attributes.fit || 'contain' } } /> }
                        { ! isUpload && embedPreview && <iframe src={ embedPreview } title={ __( 'Video preview', 'cinderwell' ) } allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowFullScreen /> }
                        { ! hasMedia && <div className="cinderwell-video__placeholder">▶<span>{ __( 'Choose a video or paste an embed URL', 'cinderwell' ) }</span></div> }
                        { ! isUpload && attributes.embedUrl && ! embedPreview && <div className="cinderwell-video__placeholder"><span>{ __( 'The provider preview will render on the site.', 'cinderwell' ) }</span></div> }
                    </div>
                    { attributes.caption && <RichText tagName="figcaption" className="cinderwell-video__caption" value={ attributes.caption } onChange={ ( caption ) => setAttributes( { caption } ) } allowedFormats={ [] } /> }
                </div>
            </figure>
        </>;
    },
    save: () => null,
} );
