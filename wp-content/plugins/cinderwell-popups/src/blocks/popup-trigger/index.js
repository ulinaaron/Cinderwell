import { registerBlockType } from '@wordpress/blocks';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { Notice, PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

function Edit( { attributes, setAttributes } ) {
	const settings = window.cinderwellPopupsForButtons || { popups: [] };
	const blockProps = useBlockProps( {
		className: `btn btn--${ attributes.variant } btn--${ attributes.size } cinderwell-popup-trigger`,
	} );
	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Popup trigger', 'cinderwell-popups' ) }
					initialOpen={ true }
					className="cw-panel cw-access-links"
				>
					<SelectControl
						label={ __( 'Popup', 'cinderwell-popups' ) }
						value={ attributes.popupId }
						options={ [
							{
								label: __(
									'Select a published popup…',
									'cinderwell-popups'
								),
								value: 0,
							},
							...settings.popups.map( ( popup ) => ( {
								label: popup.title,
								value: popup.id,
							} ) ),
						] }
						onChange={ ( value ) =>
							setAttributes( {
								popupId: Number.parseInt( value, 10 ) || 0,
							} )
						}
					/>
					{ settings.popups.length === 0 && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'Publish a popup to connect this trigger.',
								'cinderwell-popups'
							) }
						</Notice>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Style', 'cinderwell-popups' ) }
					initialOpen={ true }
					className="cw-panel cw-access-appearance"
				>
					<SelectControl
						label={ __( 'Button style', 'cinderwell-popups' ) }
						value={ attributes.variant }
						options={ [
							{
								label: __( 'Primary', 'cinderwell-popups' ),
								value: 'primary',
							},
							{
								label: __( 'Secondary', 'cinderwell-popups' ),
								value: 'secondary',
							},
							{
								label: __( 'Ghost', 'cinderwell-popups' ),
								value: 'ghost',
							},
							{
								label: __( 'Link', 'cinderwell-popups' ),
								value: 'link',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { variant: value } )
						}
					/>
					<SelectControl
						label={ __( 'Size', 'cinderwell-popups' ) }
						value={ attributes.size }
						options={ [
							{
								label: __( 'Small', 'cinderwell-popups' ),
								value: 'sm',
							},
							{
								label: __( 'Medium', 'cinderwell-popups' ),
								value: 'md',
							},
							{
								label: __( 'Large', 'cinderwell-popups' ),
								value: 'lg',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { size: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<RichText
				{ ...blockProps }
				tagName="span"
				value={ attributes.text }
				onChange={ ( value ) => setAttributes( { text: value } ) }
				placeholder={ __( 'Open popup…', 'cinderwell-popups' ) }
				allowedFormats={ [] }
			/>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
