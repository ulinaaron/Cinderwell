import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { BlockIdentity } from '../../shared/inspector-controls';
import metadata from './block.json';

const TEMPLATE = [ [ 'core/paragraph', { placeholder: 'Add tab content…' } ] ];

registerBlockType( metadata.name, {
    edit: ( { clientId } ) => {
        const isActive = useSelect( ( select ) => {
            const blockEditor = select( 'core/block-editor' );
            const parentClientId = blockEditor.getBlockRootClientId( clientId );
            const siblings = blockEditor.getBlocks( parentClientId );
            const selectedClientId = blockEditor.getSelectedBlockClientId();
            const selectedParents = selectedClientId ? blockEditor.getBlockParents( selectedClientId ) : [];
            const activeSibling = siblings.find( ( block ) => block.clientId === selectedClientId || selectedParents.includes( block.clientId ) );

            return activeSibling ? activeSibling.clientId === clientId : siblings[ 0 ]?.clientId === clientId;
        }, [ clientId ] );
        const blockProps = useBlockProps( { className: `cinderwell-tabs__panel${ isActive ? ' is-active' : '' }` } );
        const innerBlocksProps = useInnerBlocksProps(
            { className: 'cinderwell-tab-item__content' },
            { template: TEMPLATE, templateLock: false }
        );

        return (
            <>
                <InspectorControls>
                    <BlockIdentity icon="▤" title={ __( 'Tab Item', 'cinderwell' ) } description={ __( 'Block-based tab content', 'cinderwell' ) } />
                </InspectorControls>
                <div { ...blockProps }>
                    <div { ...innerBlocksProps } />
                </div>
            </>
        );
    },
    save: () => {
        const blockProps = useBlockProps.save( { className: 'cinderwell-tabs__panel' } );

        return (
            <div { ...blockProps }>
                <InnerBlocks.Content />
            </div>
        );
    },
} );
