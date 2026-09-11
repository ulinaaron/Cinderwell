import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ConditionsPanel } from '../../shared/conditions-panel';
import { BlockIdentity, SegmentedControl } from '../../shared/inspector-controls';
import metadata from './block.json';

const weightOptions = [
    { label: __( 'Light', 'cinderwell' ), value: 'light' },
    { label: __( 'Medium', 'cinderwell' ), value: 'medium' },
    { label: __( 'Heavy', 'cinderwell' ), value: 'heavy' },
];

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( {
            className: `cinderwell-atom-divider cinderwell-atom-divider--${ attributes.weight }`,
            role: 'separator',
        } );
        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="—" title={ __( 'Divider', 'cinderwell' ) } description={ __( 'Visual separator', 'cinderwell' ) } />
                    <PanelBody title={ __( 'Style', 'cinderwell' ) } initialOpen={ true } className="cw-panel">
                        <SegmentedControl
                            label={ __( 'Weight', 'cinderwell' ) }
                            value={ attributes.weight }
                            options={ weightOptions }
                            onChange={ ( v ) => setAttributes( { weight: v } ) }
                        />
                    </PanelBody>
                    <ConditionsPanel attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <hr { ...blockProps } />
            </>
        );
    },
    save: ( { attributes } ) => {
        const blockProps = useBlockProps.save( {
            className: `cinderwell-atom-divider cinderwell-atom-divider--${ attributes.weight }`,
            role: 'separator',
        } );
        return <hr { ...blockProps } />;
    },
} );
