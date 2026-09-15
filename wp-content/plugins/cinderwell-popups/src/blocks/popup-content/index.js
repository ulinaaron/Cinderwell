import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

function Edit() {
	const blockProps = useBlockProps( {
		className: 'cinderwell-popup-content',
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		renderAppender: undefined,
		template: [
			[
				'cinderwell/body',
				{
					showHeading: false,
					bodyContent: __(
						'Add popup content…',
						'cinderwell-popups'
					),
					width: 'standard',
				},
			],
		],
		templateInsertUpdatesSelection: true,
	} );
	return <div { ...innerBlocksProps } />;
}

registerBlockType( metadata.name, {
	edit: Edit,
	save() {
		const blockProps = useBlockProps.save( {
			className: 'cinderwell-popup-content',
		} );
		const innerBlocksProps = useInnerBlocksProps.save( blockProps );
		return <div { ...innerBlocksProps } />;
	},
} );
