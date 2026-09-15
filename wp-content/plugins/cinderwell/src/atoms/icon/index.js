import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ConditionsPanel } from '../../shared/conditions-panel';
import { IconSettingsControl } from '../../shared/icon-controls';
import { IconGlyph } from '../../shared/icon-library';
import { BlockIdentity } from '../../shared/inspector-controls';
import metadata from './block.json';

const getIconClassName = ( attributes ) => [
    'cinderwell-atom-icon',
    `cinderwell-atom-icon--size-${ attributes.iconSize || 'md' }`,
    `cinderwell-atom-icon--color-${ attributes.iconColor || 'brand' }`,
    `cinderwell-atom-icon--treatment-${ attributes.iconTreatment || 'plain' }`,
    `cinderwell-atom-icon--align-${ attributes.iconAlignment || 'left' }`,
].join( ' ' );

const IconContent = ( { attributes } ) => (
    <span className="cinderwell-atom-icon__glyph">
        <IconGlyph
            icon={ attributes.icon || 'star' }
            customSvg={ attributes.iconSource === 'custom' ? attributes.iconSvg : '' }
            viewBox={ attributes.iconViewBox || '0 0 24 24' }
        />
    </span>
);

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( { className: getIconClassName( attributes ) } );

        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="◇" title={ __( 'Icon', 'cinderwell' ) } description={ __( 'Lucide icon or custom SVG', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Icon', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-appearance">
                        <IconSettingsControl
                            icon={ attributes.icon || 'star' }
                            source={ attributes.iconSource || 'library' }
                            customSvg={ attributes.iconSvg || '' }
                            customViewBox={ attributes.iconViewBox || '0 0 24 24' }
                            customSvgId={ attributes.iconSvgId || 0 }
                            customSvgUrl={ attributes.iconSvgUrl || '' }
                            size={ attributes.iconSize || 'md' }
                            color={ attributes.iconColor || 'brand' }
                            treatment={ attributes.iconTreatment || 'plain' }
                            alignment={ attributes.iconAlignment || 'left' }
                            onIconChange={ ( icon ) => setAttributes( { icon } ) }
                            onSourceChange={ ( iconSource ) => setAttributes( { iconSource } ) }
                            onCustomSvgChange={ ( iconSvg, iconViewBox, iconSvgId, iconSvgUrl ) => setAttributes( { iconSvg, iconViewBox, iconSvgId, iconSvgUrl } ) }
                            onSizeChange={ ( iconSize ) => setAttributes( { iconSize } ) }
                            onColorChange={ ( iconColor ) => setAttributes( { iconColor } ) }
                            onTreatmentChange={ ( iconTreatment ) => setAttributes( { iconTreatment } ) }
                            onAlignmentChange={ ( iconAlignment ) => setAttributes( { iconAlignment } ) }
                        />
                    </PanelBody>
                    <ConditionsPanel attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <div { ...blockProps }>
                    <IconContent attributes={ attributes } />
                </div>
            </>
        );
    },
    save: ( { attributes } ) => {
        const blockProps = useBlockProps.save( { className: getIconClassName( attributes ) } );

        return (
            <div { ...blockProps }>
                <IconContent attributes={ attributes } />
            </div>
        );
    },
} );
