import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { SelectControl, TextControl, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { canEditControl } from './editor-access';
import { getPaletteOptions, useColorRegistry } from './color-registry';
import { InheritedColorTokenControl, InheritedSegmentedControl } from './inspector-controls';

const keys = {
	visibility: '_cw_page_header_visibility',
	title: '_cw_page_header_title',
	descriptionMode: '_cw_page_header_description_mode',
	description: '_cw_page_header_description',
	background: '_cw_page_header_background',
	breadcrumbs: '_cw_page_header_breadcrumbs',
};

const PageHeaderPanel = () => {
	const postType = useSelect( ( select ) => select( 'core/editor' )?.getCurrentPostType(), [] );
	const meta = useSelect( ( select ) => select( 'core/editor' )?.getEditedPostAttribute( 'meta' ) || {}, [] );
	const { editPost } = useDispatch( 'core/editor' );
	const registry = useColorRegistry();
	const defaults = window.cinderwellEditorSettings?.pageHeader || {};

	if ( ! [ 'page', 'post' ].includes( postType ) ) return null;

	const update = ( key, value ) => editPost( { meta: { ...meta, [ key ]: value } } );
	const visibility = meta[ keys.visibility ] || '';
	const descriptionMode = meta[ keys.descriptionMode ] || '';
	const palette = getPaletteOptions( registry, 'background' );

	return <PluginDocumentSettingPanel name="cinderwell-page-header" title={ __( 'Page Header', 'cinderwell' ) } initialOpen={ true }>
		{ canEditControl( 'layout' ) && <InheritedSegmentedControl
			label={ __( 'Header visibility', 'cinderwell' ) }
			value={ visibility }
			defaultValue={ defaults.enabled ? 'show' : 'hide' }
			inheritValue=""
			options={ [
				{ label: __( 'Show', 'cinderwell' ), value: 'show' },
				{ label: __( 'Hide', 'cinderwell' ), value: 'hide' },
			] }
			onChange={ ( value ) => update( keys.visibility, value ) }
		/> }
		{ visibility !== 'hide' && <>
			{ canEditControl( 'content' ) && <TextControl
				label={ __( 'Title override', 'cinderwell' ) }
				value={ meta[ keys.title ] || '' }
				help={ postType === 'post'
					? __( 'Leave blank to use the WordPress post title.', 'cinderwell' )
					: __( 'Leave blank to use the WordPress page title.', 'cinderwell' ) }
				onChange={ ( value ) => update( keys.title, value ) }
			/> }
			{ canEditControl( 'content' ) && <SelectControl
				label={ __( 'Description', 'cinderwell' ) }
				value={ descriptionMode }
				options={ [
					{ label: __( 'Inherit from template', 'cinderwell' ), value: '' },
					{ label: __( 'Custom description', 'cinderwell' ), value: 'custom' },
					{ label: __( 'Hide description', 'cinderwell' ), value: 'hide' },
				] }
				onChange={ ( value ) => update( keys.descriptionMode, value ) }
			/> }
			{ canEditControl( 'content' ) && descriptionMode === 'custom' && <TextareaControl
				label={ __( 'Custom description', 'cinderwell' ) }
				value={ meta[ keys.description ] || '' }
				onChange={ ( value ) => update( keys.description, value ) }
			/> }
			{ canEditControl( 'appearance' ) && <InheritedColorTokenControl
				label={ __( 'Background', 'cinderwell' ) }
				value={ meta[ keys.background ] || 'inherit' }
				defaultValue={ defaults.background || 'light' }
				options={ palette }
				onChange={ ( value ) => update( keys.background, value === 'inherit' ? '' : value ) }
			/> }
			{ canEditControl( 'layout' ) && <InheritedSegmentedControl
				label={ __( 'Breadcrumbs', 'cinderwell' ) }
				value={ meta[ keys.breadcrumbs ] || '' }
				defaultValue={ defaults.breadcrumbs ? 'show' : 'hide' }
				inheritValue=""
				options={ [
					{ label: __( 'Show', 'cinderwell' ), value: 'show' },
					{ label: __( 'Hide', 'cinderwell' ), value: 'hide' },
				] }
				onChange={ ( value ) => update( keys.breadcrumbs, value ) }
			/> }
		</> }
	</PluginDocumentSettingPanel>;
};

registerPlugin( 'cinderwell-page-header-panel', {
	render: PageHeaderPanel,
} );
