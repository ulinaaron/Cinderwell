import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BlockIdentity,
	InheritedColorTokenControl,
	InheritedSegmentedControl,
} from '../../shared/inspector-controls';
import {
	DynamicDataPicker,
	getPreviewValue,
} from '../../shared/dynamic-data-picker';
import { DynamicDataIcon } from '../../shared/dynamic-data-icon';
import { getPaletteOptions, useColorRegistry } from '../../shared/color-registry';
import { canEditControl } from '../../shared/editor-access';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes, context } ) => {
		const [ dynamicOpen, setDynamicOpen ] = useState( false );
		const registry = useColorRegistry();
		const defaults = window.cinderwellEditorSettings?.pageHeader || {
			background: 'light',
			breadcrumbs: true,
			post_date: true,
			post_terms: true,
			alignment: 'left',
			width: 'wide',
			spacing: 'md',
		};
		const post = useSelect( ( select ) => ( {
			title: select( 'core/editor' )?.getEditedPostAttribute( 'title' ),
			postType: select( 'core/editor' )?.getCurrentPostType(),
			templateSlug: select( 'core/editor' )?.getEditedPostAttribute( 'slug' ),
			categoryIds: select( 'core/editor' )?.getEditedPostAttribute( 'categories' ) || [],
			meta: select( 'core/editor' )?.getEditedPostAttribute( 'meta' ) || {},
		} ), [] );
		const categories = useSelect( ( select ) => {
			if ( post.postType !== 'post' || ! post.categoryIds.length ) return [];
			return select( 'core' ).getEntityRecords( 'taxonomy', 'category', {
				include: post.categoryIds,
				per_page: Math.max( post.categoryIds.length, 1 ),
			} ) || [];
		}, [ post.postType, post.categoryIds ] );
		const isSearchTemplate = post.postType === 'wp_template'
			&& post.title?.toLowerCase().includes( 'search' );
		const isContentEntry = [ 'page', 'post' ].includes( post.postType );
		const contextPostType = context?.postType || post.postType;
		const isSinglePostTemplate = post.postType === 'wp_template'
			&& [ 'single', 'single-post' ].includes( post.templateSlug );
		const isBlogPost = contextPostType === 'post' || isSinglePostTemplate;
		const { editPost } = useDispatch( 'core/editor' );
		const background = attributes.background === 'inherit' ? defaults.background : attributes.background;
		const alignment = attributes.alignment === 'inherit' ? defaults.alignment : attributes.alignment;
		const width = attributes.width === 'inherit' ? defaults.width : attributes.width;
		const spacing = attributes.spacing === 'inherit' ? defaults.spacing : attributes.spacing;
		const breadcrumbValue = attributes.breadcrumbs === 'inherit'
			? ( defaults.breadcrumbs ? 'show' : 'hide' )
			: attributes.breadcrumbs;
		const postDateOverride = isContentEntry ? post.meta._cw_page_header_post_date : '';
		const postTermsOverride = isContentEntry ? post.meta._cw_page_header_post_terms : '';
		const postDateValue = postDateOverride || ( attributes.postDate === 'inherit'
			? ( defaults.post_date ? 'show' : 'hide' )
			: attributes.postDate );
		const postTermsValue = postTermsOverride || ( attributes.postTerms === 'inherit'
			? ( defaults.post_terms ? 'show' : 'hide' )
			: attributes.postTerms );
		const previewCategories = categories.length > 0
			? categories
			: ( isSinglePostTemplate ? [ { id: 'preview', name: __( 'Category', 'cinderwell' ) } ] : [] );
		const binding = attributes.dynamicData?.description || {};
		const isDynamic = binding.source && binding.source !== 'static';
		const description = isDynamic
			? getPreviewValue( binding.source, binding.field, binding.fallback, binding.source )
			: attributes.description;
		const entryTitleOverride = isContentEntry ? ( post.meta._cw_page_header_title || '' ) : '';
		const title = entryTitleOverride
			|| attributes.titleOverride
			|| ( isSearchTemplate ? __( 'Search results for “search term”', 'cinderwell' ) : post.title )
			|| __( 'Page title', 'cinderwell' );
		const blockProps = useBlockProps( {
			className: `cinderwell-page-header cinderwell-page-header--bg-${ background } cinderwell-page-header--align-${ alignment } cw-spacing-desktop-top-${ spacing } cw-spacing-desktop-bottom-${ spacing }`,
		} );
		const paletteOptions = getPaletteOptions( registry, 'background' );
		const updateTitleOverride = ( value ) => {
			if ( isContentEntry ) {
				editPost( { meta: { ...post.meta, _cw_page_header_title: value } } );
				return;
			}
			setAttributes( { titleOverride: value } );
		};

		return <>
			<InspectorControls>
				<BlockIdentity icon="H" title={ __( 'Page Header', 'cinderwell' ) } description={ __( 'Dynamic title, description, post meta, and breadcrumbs', 'cinderwell' ) } />
				<PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-content">
					{ canEditControl( 'content' ) && <TextControl
						label={ __( 'Title override', 'cinderwell' ) }
						value={ isContentEntry ? entryTitleOverride : attributes.titleOverride }
						help={ isContentEntry
							? ( post.postType === 'post' ? __( 'Leave blank to use this post title.', 'cinderwell' ) : __( 'Leave blank to use this page title.', 'cinderwell' ) )
							: __( 'Leave blank to use each Page title. A template override applies to every Page using this template.', 'cinderwell' ) }
						onChange={ updateTitleOverride }
					/> }
					{ canEditControl( 'content' ) && <Button icon={ <DynamicDataIcon /> } variant={ isDynamic ? 'primary' : 'secondary' } onClick={ () => setDynamicOpen( true ) }>
						{ isDynamic ? __( 'Edit dynamic description', 'cinderwell' ) : __( 'Use dynamic description', 'cinderwell' ) }
					</Button> }
					{ isBlogPost && canEditControl( 'content' ) && <>
						<InheritedSegmentedControl
							label={ __( 'Post date', 'cinderwell' ) }
							value={ attributes.postDate }
							defaultValue={ defaults.post_date ? 'show' : 'hide' }
							options={ [
								{ label: __( 'Show', 'cinderwell' ), value: 'show' },
								{ label: __( 'Hide', 'cinderwell' ), value: 'hide' },
							] }
							onChange={ ( value ) => setAttributes( { postDate: value } ) }
						/>
						<InheritedSegmentedControl
							label={ __( 'Categories', 'cinderwell' ) }
							value={ attributes.postTerms }
							defaultValue={ defaults.post_terms ? 'show' : 'hide' }
							options={ [
								{ label: __( 'Show', 'cinderwell' ), value: 'show' },
								{ label: __( 'Hide', 'cinderwell' ), value: 'hide' },
							] }
							onChange={ ( value ) => setAttributes( { postTerms: value } ) }
						/>
					</> }
				</PanelBody>
				{ canEditControl( 'layout' ) && <PanelBody title={ __( 'Layout', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-layout">
					<InheritedSegmentedControl
						label={ __( 'Content width', 'cinderwell' ) }
						value={ attributes.width }
						defaultValue={ defaults.width }
						options={ [
							{ label: __( 'Narrow', 'cinderwell' ), value: 'narrow' },
							{ label: __( 'Standard', 'cinderwell' ), value: 'standard' },
							{ label: __( 'Wide', 'cinderwell' ), value: 'wide' },
							{ label: __( 'Full', 'cinderwell' ), value: 'full' },
						] }
						onChange={ ( value ) => setAttributes( { width: value } ) }
					/>
					<InheritedSegmentedControl
						label={ __( 'Alignment', 'cinderwell' ) }
						value={ attributes.alignment }
						defaultValue={ defaults.alignment }
						options={ [
							{ label: '←', value: 'left' },
							{ label: '↔', value: 'center' },
							{ label: '→', value: 'right' },
						] }
						onChange={ ( value ) => setAttributes( { alignment: value } ) }
					/>
					<InheritedSegmentedControl
						label={ __( 'Breadcrumbs', 'cinderwell' ) }
						value={ attributes.breadcrumbs }
						defaultValue={ defaults.breadcrumbs ? 'show' : 'hide' }
						options={ [
							{ label: __( 'Show', 'cinderwell' ), value: 'show' },
							{ label: __( 'Hide', 'cinderwell' ), value: 'hide' },
						] }
						onChange={ ( value ) => setAttributes( { breadcrumbs: value } ) }
					/>
				</PanelBody> }
				{ canEditControl( 'spacing' ) && <PanelBody title={ __( 'Spacing', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-spacing">
					<InheritedSegmentedControl
						label={ __( 'Vertical spacing', 'cinderwell' ) }
						value={ attributes.spacing }
						defaultValue={ defaults.spacing }
						options={ [
							{ label: __( 'None', 'cinderwell' ), value: 'none' },
							{ label: __( 'XS', 'cinderwell' ), value: 'xs' },
							{ label: __( 'SM', 'cinderwell' ), value: 'sm' },
							{ label: __( 'MD', 'cinderwell' ), value: 'md' },
							{ label: __( 'LG', 'cinderwell' ), value: 'lg' },
							{ label: __( 'XL', 'cinderwell' ), value: 'xl' },
						] }
						onChange={ ( value ) => setAttributes( { spacing: value } ) }
					/>
				</PanelBody> }
				{ canEditControl( 'appearance' ) && <PanelBody title={ __( 'Background', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-appearance">
					<InheritedColorTokenControl
						label={ __( 'Background color', 'cinderwell' ) }
						value={ attributes.background }
						defaultValue={ defaults.background }
						options={ paletteOptions }
						onChange={ ( value ) => setAttributes( { background: value } ) }
					/>
				</PanelBody> }
			</InspectorControls>
			<section { ...blockProps }>
				<div className="cinderwell-page-header__inner" style={ { maxWidth: `var(--cw-width-${ width })` } }>
					{ breadcrumbValue === 'show' && <nav className="cinderwell-page-header__breadcrumbs" aria-label={ __( 'Breadcrumb', 'cinderwell' ) }><ol><li><span>{ __( 'Home', 'cinderwell' ) }</span></li><li><span aria-current="page">{ title }</span></li></ol></nav> }
					<h1 className="cinderwell-page-header__title">{ title }</h1>
					{ isBlogPost && ( postDateValue === 'show' || ( postTermsValue === 'show' && previewCategories.length ) ) && <div className="cinderwell-page-header__meta">
						{ postDateValue === 'show' && <time className="cinderwell-page-header__date">{ window.cinderwellEditorSettings?.previewValues?.post_date || __( 'Post date', 'cinderwell' ) }</time> }
						{ postTermsValue === 'show' && previewCategories.length > 0 && <ul className="cinderwell-page-header__terms" aria-label={ __( 'Categories', 'cinderwell' ) }>{ previewCategories.map( ( category ) => <li key={ category.id }><span>{ category.name }</span></li> ) }</ul> }
					</div> }
					{ isDynamic ? <div className="cinderwell-page-header__description"><p>{ description }</p><span className="cinderwell-page-header__dynamic-label"><DynamicDataIcon />{ __( 'Dynamic description', 'cinderwell' ) }</span></div> : canEditControl( 'content' ) ? <RichText
						tagName="div"
						className="cinderwell-page-header__description"
						value={ attributes.description }
						onChange={ ( value ) => setAttributes( { description: value } ) }
						placeholder={ __( 'Optional template description…', 'cinderwell' ) }
						allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
					/> : description && <div className="cinderwell-page-header__description"><p>{ description }</p></div> }
				</div>
			</section>
			{ dynamicOpen && <DynamicDataPicker
				slots={ [ { value: 'description', label: __( 'Description', 'cinderwell' ) } ] }
				initialSlot="description"
				value={ attributes.dynamicData || {} }
				slotValues={ { description: attributes.description } }
				onChange={ ( dynamicData ) => setAttributes( { dynamicData } ) }
				onClose={ () => setDynamicOpen( false ) }
			/> }
		</>;
	},
	save: () => null,
} );
