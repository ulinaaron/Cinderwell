import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { createElement, Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
	ExternalLink,
	Notice,
	PanelBody,
	RadioControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

addFilter(
	'blocks.registerBlockType',
	'cinderwell-popups/button-attributes',
	( settings, name ) => {
		if ( name !== 'cinderwell/button' ) {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				action: { type: 'string', default: 'link' },
				popupId: { type: 'integer', default: 0 },
			},
		};
	}
);

const withPopupAction = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( props.name !== 'cinderwell/button' ) {
			return createElement( BlockEdit, props );
		}

		const options = window.cinderwellPopupsForButtons || { popups: [] };
		const action = props.attributes.action || 'link';
		const selectOptions = [
			{
				label: __( 'Select a published popup…', 'cinderwell-popups' ),
				value: 0,
			},
			...options.popups.map( ( popup ) => ( {
				label: popup.title,
				value: popup.id,
			} ) ),
		];

		return createElement(
			Fragment,
			null,
			createElement( BlockEdit, props ),
			createElement(
				InspectorControls,
				null,
				createElement(
					PanelBody,
					{
						title: __( 'Popup action', 'cinderwell-popups' ),
						initialOpen: action === 'popup',
						className: 'cw-panel cw-access-links',
					},
					createElement( RadioControl, {
						label: __( 'When clicked', 'cinderwell-popups' ),
						selected: action,
						options: [
							{
								label: __( 'Follow link', 'cinderwell-popups' ),
								value: 'link',
							},
							{
								label: __( 'Open popup', 'cinderwell-popups' ),
								value: 'popup',
							},
						],
						onChange: ( value ) =>
							props.setAttributes(
								value === 'popup'
									? {
											action: value,
									  }
									: { action: value, popupId: 0 }
							),
					} ),
					action === 'popup' &&
						options.popups.length > 0 &&
						createElement( SelectControl, {
							label: __( 'Popup', 'cinderwell-popups' ),
							value: props.attributes.popupId || 0,
							options: selectOptions,
							onChange: ( value ) =>
								props.setAttributes( {
									popupId: Number.parseInt( value, 10 ) || 0,
								} ),
						} ),
					action === 'popup' &&
						options.popups.length === 0 &&
						createElement(
							Notice,
							{ status: 'warning', isDismissible: false },
							__(
								'Publish a popup before connecting this button.',
								'cinderwell-popups'
							),
							' ',
							createElement(
								ExternalLink,
								{ href: options.addNewUrl },
								__( 'Add popup', 'cinderwell-popups' )
							)
						)
				)
			)
		);
	},
	'withCinderwellPopupAction'
);

addFilter(
	'editor.BlockEdit',
	'cinderwell-popups/button-action',
	withPopupAction
);
