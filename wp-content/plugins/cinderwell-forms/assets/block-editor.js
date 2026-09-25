( () => {
	'use strict';
	const el = window.wp.element.createElement;
	const { registerBlockType, registerBlockVariation } = window.wp.blocks;
	const { useBlockProps, InspectorControls } = window.wp.blockEditor;
	const { Button, PanelBody, Placeholder, SelectControl } = window.wp.components;
	const ServerSideRender = window.wp.serverSideRender;
	const { __ } = window.wp.i18n;
	const data = window.cinderwellFormsBlock || { forms: [], newFormUrl: '' };
	const shared = window.cinderwellEditorComponents || {};
	const BlockIdentity = shared.BlockIdentity || ( ( { icon, title, description } ) => el( 'div', { className: 'cw-identity' },
		el( 'span', { className: 'cw-identity__icon', 'aria-hidden': true }, icon ),
		el( 'div', {}, el( 'div', { className: 'cw-identity__name' }, title ), el( 'div', { className: 'cw-identity__desc' }, description ) )
	) );
	const SegmentedControl = shared.SegmentedControl || ( ( { label, value, options, onChange } ) => el( 'div', { className: 'cw-field' },
		label ? el( 'div', { className: 'cw-field__label' }, label ) : null,
		el( 'div', { className: 'cw-segmented' }, ...options.map( ( option ) => el( 'button', {
			type: 'button',
			key: option.value,
			className: value === option.value ? 'is-active' : '',
			onClick: () => onChange( option.value ),
			'aria-pressed': value === option.value,
		}, option.label ) ) )
	) );
	const ConditionsPanel = shared.ConditionsPanel || ( () => null );
	const LayoutControls = shared.LayoutControls || ( ( { attributes, setAttributes } ) => el( PanelBody, { title: __( 'Layout', 'cinderwell-forms' ), initialOpen: true, className: 'cw-panel cw-access-layout' },
		el( SegmentedControl, {
			label: __( 'Content Width', 'cinderwell-forms' ),
			value: attributes.width || 'standard',
			options: [
				{ value: 'narrow', label: __( 'Narrow', 'cinderwell-forms' ) },
				{ value: 'standard', label: __( 'Standard', 'cinderwell-forms' ) },
				{ value: 'wide', label: __( 'Wide', 'cinderwell-forms' ) },
				{ value: 'full', label: __( 'Full', 'cinderwell-forms' ) },
			],
			onChange: ( width ) => setAttributes( { width } ),
		} )
	) );
	const BackgroundControls = shared.BackgroundControls || ( () => null );
	const TypographyControls = shared.TypographyControls || ( ( { attributes, setAttributes } ) => el( ConditionsPanel, { attributes, setAttributes } ) );
	const editorLink = ( url, label, variant = 'secondary' ) => url ? el( Button, {
		href: url,
		target: '_blank',
		rel: 'noopener noreferrer',
		variant,
	}, label ) : null;
	registerBlockType( 'cinderwell/form', {
		edit: ( { attributes, setAttributes } ) => {
			const selected = data.forms.find( ( form ) => Number( form.value ) === Number( attributes.formId ) );
			const presentation = attributes.presentation || 'auto';
			const isNewsletter = presentation === 'newsletter';
			const blockTitle = isNewsletter ? __( 'Newsletter Form', 'cinderwell-forms' ) : __( 'Form', 'cinderwell-forms' );
			const blockDescription = isNewsletter ? __( 'Compact email signup form', 'cinderwell-forms' ) : __( 'Published Cinderwell form', 'cinderwell-forms' );
			const newFormUrl = presentation === 'newsletter' ? ( data.newNewsletterFormUrl || data.newFormUrl ) : data.newFormUrl;
			const formOptions = [ { value: 0, label: __( 'Select a form', 'cinderwell-forms' ) }, ...data.forms ];
			const selector = el( SelectControl, {
				label: __( 'Published form', 'cinderwell-forms' ),
				value: attributes.formId,
				options: formOptions,
				onChange: ( value ) => setAttributes( { formId: Number( value ) || 0 } ),
			} );
			return el( window.wp.element.Fragment, {},
				el( InspectorControls, {},
					el( BlockIdentity, { icon: isNewsletter ? '✉' : 'ƒ', title: blockTitle, description: blockDescription } ),
					el( PanelBody, { title: blockTitle, initialOpen: true, className: 'cw-panel cw-access-content' },
						selector,
						el( 'div', { className: 'cinderwell-form-editor-actions' },
							selected ? editorLink( selected.editUrl, __( 'Edit form', 'cinderwell-forms' ) ) : null,
							editorLink( newFormUrl, presentation === 'newsletter' ? __( 'Add newsletter form', 'cinderwell-forms' ) : __( 'Add new form', 'cinderwell-forms' ), selected ? 'tertiary' : 'secondary' )
						)
					),
					el( PanelBody, { title: __( 'Presentation', 'cinderwell-forms' ), initialOpen: true, className: 'cw-panel cw-access-layout' },
						el( SegmentedControl, {
							label: __( 'Form layout', 'cinderwell-forms' ),
							value: presentation,
							options: [
								{ value: 'auto', label: __( 'Form default', 'cinderwell-forms' ) },
								{ value: 'standard', label: __( 'Standard', 'cinderwell-forms' ) },
								{ value: 'newsletter', label: __( 'Newsletter', 'cinderwell-forms' ) },
							],
							onChange: ( value ) => setAttributes( { presentation: value } ),
						} )
					),
					el( LayoutControls, { attributes, setAttributes } ),
					el( BackgroundControls, {
						value: attributes.background || 'white',
						onChange: ( background ) => setAttributes( { background } ),
						attributes,
						setAttributes,
					} ),
					el( TypographyControls, { attributes, setAttributes, sections: [] } )
				),
				el( 'div', useBlockProps( { className: 'cinderwell-form-editor-preview' } ),
					selected ? el( ServerSideRender, {
							block: 'cinderwell/form',
							attributes: { ...attributes, editorPreview: true },
						} ) : el( Placeholder, {
						icon: isNewsletter ? 'email' : 'feedback',
						label: blockTitle,
						instructions: data.forms.length ? __( 'Choose a published form to display.', 'cinderwell-forms' ) : __( 'Create a form, publish it, then select it here.', 'cinderwell-forms' ),
					},
						data.forms.length ? selector : null,
						editorLink( newFormUrl, presentation === 'newsletter' ? __( 'Add newsletter form', 'cinderwell-forms' ) : __( 'Add new form', 'cinderwell-forms' ), 'primary' )
					)
				)
			);
		},
		save: () => null,
	} );
	registerBlockVariation( 'cinderwell/form', {
		name: 'newsletter-signup',
		title: __( 'Newsletter Form', 'cinderwell-forms' ),
		description: __( 'Display a compact newsletter signup form.', 'cinderwell-forms' ),
		icon: 'email',
		attributes: { presentation: 'newsletter', width: 'standard' },
		isActive: ( attributes ) => attributes.presentation === 'newsletter',
		scope: [ 'inserter' ],
	} );
} )();
