import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

const Segmented = ( { label, value, options, onChange } ) => (
	<div className="cw-field">
		<span className="cw-field__label">{ label }</span>
		<div className="cw-segmented">
			{ options.map( ( option ) => (
				<button
					type="button"
					key={ option.value }
					className={ value === option.value ? 'is-active' : '' }
					aria-pressed={ value === option.value }
					onClick={ () => onChange( option.value ) }
				>
					{ option.label }
				</button>
			) ) }
		</div>
	</div>
);

const ColorChips = ( { value, onChange } ) => {
	const colors = (
		window.cinderwellEditorSettings?.colorRegistry || []
	).filter( ( color ) => color.palette?.contexts?.includes( 'background' ) );
	return (
		<div className="cw-field">
			<span className="cw-field__label">
				{ __( 'Background', 'cinderwell-events' ) }
			</span>
			<div className="cw-swatches">
				{ colors.map( ( color ) => (
					<button
						type="button"
						key={ color.palette.slug }
						className={ `cw-swatch${
							value === color.palette.slug ? ' is-active' : ''
						}` }
						style={ {
							background: `var(${ color.cssVariable }, ${
								color.resolvedCss || color.value
							})`,
						} }
						aria-label={ color.label }
						aria-pressed={ value === color.palette.slug }
						onClick={ () => onChange( color.palette.slug ) }
					/>
				) ) }
			</div>
		</div>
	);
};

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const categories = useSelect(
		( select ) =>
			select( 'core' ).getEntityRecords(
				'taxonomy',
				'cw_event_category',
				{
					per_page: 100,
					orderby: 'name',
					order: 'asc',
				}
			),
		[]
	);
	const blockProps = useBlockProps( {
		className: 'cinderwell-events-editor-preview',
	} );
	const categoryOptions = [
		{ label: __( 'All categories', 'cinderwell-events' ), value: 0 },
	].concat(
		( categories || [] ).map( ( term ) => ( {
			label: term.name,
			value: term.id,
		} ) )
	);
	const facetSettings = attributes.facets || {};
	const updateFacet = ( key, value ) =>
		setAttributes( {
			facets: {
				...facetSettings,
				[ key ]: { ...( facetSettings[ key ] || {} ), ...value },
			},
		} );
	const toggleFilters = ( filtersEnabled ) =>
		setAttributes( {
			filtersEnabled,
			...( filtersEnabled && ! attributes.facetId
				? {
						facetId: `events-${ clientId
							.replace( /-/g, '' )
							.slice( 0, 12 ) }`,
				  }
				: {} ),
		} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Content', 'cinderwell-events' ) }
					initialOpen
				>
					<Segmented
						label={ __( 'Sessions from', 'cinderwell-events' ) }
						value={ attributes.scope }
						options={ [
							{
								label: __(
									'Current Event',
									'cinderwell-events'
								),
								value: 'current',
							},
							{
								label: __( 'All Events', 'cinderwell-events' ),
								value: 'global',
							},
						] }
						onChange={ ( scope ) => setAttributes( { scope } ) }
					/>
					<ToggleControl
						label={ __( 'Show heading', 'cinderwell-events' ) }
						checked={ attributes.showHeading }
						onChange={ ( showHeading ) =>
							setAttributes( { showHeading } )
						}
					/>
					{ attributes.showHeading && (
						<TextControl
							label={ __( 'Heading', 'cinderwell-events' ) }
							value={ attributes.heading }
							onChange={ ( heading ) =>
								setAttributes( { heading } )
							}
						/>
					) }
					{ attributes.scope === 'global' && (
						<SelectControl
							label={ __(
								'Event category',
								'cinderwell-events'
							) }
							value={ attributes.categoryId }
							options={ categoryOptions }
							onChange={ ( categoryId ) =>
								setAttributes( {
									categoryId: Number( categoryId ),
								} )
							}
						/>
					) }
					<RangeControl
						label={ __( 'Sessions per page', 'cinderwell-events' ) }
						value={ attributes.perPage }
						min={ 1 }
						max={ 24 }
						onChange={ ( perPage ) => setAttributes( { perPage } ) }
					/>
					<ToggleControl
						label={ __( 'Show pagination', 'cinderwell-events' ) }
						checked={ attributes.showPagination }
						onChange={ ( showPagination ) =>
							setAttributes( { showPagination } )
						}
					/>
					<TextControl
						label={ __( 'Empty message', 'cinderwell-events' ) }
						value={ attributes.emptyMessage }
						onChange={ ( emptyMessage ) =>
							setAttributes( { emptyMessage } )
						}
					/>
				</PanelBody>
				{ attributes.scope === 'global' && (
					<PanelBody
						title={ __( 'Filters & sorting', 'cinderwell-events' ) }
						initialOpen={ false }
					>
						<Segmented
							label={ __( 'Filters', 'cinderwell-events' ) }
							value={ attributes.filtersEnabled ? 'on' : 'off' }
							options={ [
								{
									label: __( 'Off', 'cinderwell-events' ),
									value: 'off',
								},
								{
									label: __( 'On', 'cinderwell-events' ),
									value: 'on',
								},
							] }
							onChange={ ( value ) =>
								toggleFilters( value === 'on' )
							}
						/>
						{ attributes.filtersEnabled && (
							<>
								<ToggleControl
									label={ __(
										'Search',
										'cinderwell-events'
									) }
									checked={ Boolean(
										facetSettings.search?.enabled
									) }
									onChange={ ( enabled ) =>
										updateFacet( 'search', { enabled } )
									}
								/>
								{ facetSettings.search?.enabled && (
									<TextControl
										label={ __(
											'Search label',
											'cinderwell-events'
										) }
										value={
											facetSettings.search?.label || ''
										}
										placeholder={ __(
											'Search events',
											'cinderwell-events'
										) }
										onChange={ ( label ) =>
											updateFacet( 'search', { label } )
										}
									/>
								) }
								<ToggleControl
									label={ __(
										'Event category',
										'cinderwell-events'
									) }
									checked={ Boolean(
										facetSettings.category?.enabled
									) }
									onChange={ ( enabled ) =>
										updateFacet( 'category', { enabled } )
									}
								/>
								{ facetSettings.category?.enabled && (
									<>
										<TextControl
											label={ __(
												'Category label',
												'cinderwell-events'
											) }
											value={
												facetSettings.category?.label ||
												''
											}
											placeholder={ __(
												'Event type',
												'cinderwell-events'
											) }
											onChange={ ( label ) =>
												updateFacet( 'category', {
													label,
												} )
											}
										/>
										<Segmented
											label={ __(
												'Category display',
												'cinderwell-events'
											) }
											value={
												facetSettings.category
													?.display || 'pills'
											}
											options={ [
												{
													label: __(
														'Select',
														'cinderwell-events'
													),
													value: 'select',
												},
												{
													label: __(
														'Pills',
														'cinderwell-events'
													),
													value: 'pills',
												},
												{
													label: __(
														'Checks',
														'cinderwell-events'
													),
													value: 'checkboxes',
												},
											] }
											onChange={ ( display ) =>
												updateFacet( 'category', {
													display,
												} )
											}
										/>
									</>
								) }
								<ToggleControl
									label={ __(
										'Date range',
										'cinderwell-events'
									) }
									checked={ Boolean(
										facetSettings.date?.enabled
									) }
									onChange={ ( enabled ) =>
										updateFacet( 'date', { enabled } )
									}
								/>
								{ facetSettings.date?.enabled && (
									<TextControl
										label={ __(
											'Date label',
											'cinderwell-events'
										) }
										value={
											facetSettings.date?.label || ''
										}
										placeholder={ __(
											'Session date',
											'cinderwell-events'
										) }
										onChange={ ( label ) =>
											updateFacet( 'date', { label } )
										}
									/>
								) }
								<ToggleControl
									label={ __( 'Sort', 'cinderwell-events' ) }
									checked={ Boolean(
										facetSettings.sort?.enabled
									) }
									onChange={ ( enabled ) =>
										updateFacet( 'sort', { enabled } )
									}
								/>
								{ facetSettings.sort?.enabled && (
									<TextControl
										label={ __(
											'Sort label',
											'cinderwell-events'
										) }
										value={
											facetSettings.sort?.label || ''
										}
										placeholder={ __(
											'Sort by',
											'cinderwell-events'
										) }
										onChange={ ( label ) =>
											updateFacet( 'sort', { label } )
										}
									/>
								) }
								<ToggleControl
									label={ __(
										'Update results immediately',
										'cinderwell-events'
									) }
									checked={ attributes.liveFiltering }
									onChange={ ( liveFiltering ) =>
										setAttributes( { liveFiltering } )
									}
								/>
							</>
						) }
					</PanelBody>
				) }
				<PanelBody
					title={ __( 'Layout', 'cinderwell-events' ) }
					initialOpen={ false }
				>
					<Segmented
						label={ __( 'Content width', 'cinderwell-events' ) }
						value={ attributes.width }
						options={ [
							{
								label: __( 'Narrow', 'cinderwell-events' ),
								value: 'narrow',
							},
							{
								label: __( 'Standard', 'cinderwell-events' ),
								value: 'standard',
							},
							{
								label: __( 'Wide', 'cinderwell-events' ),
								value: 'wide',
							},
						] }
						onChange={ ( width ) => setAttributes( { width } ) }
					/>
					<Segmented
						label={ __( 'Vertical spacing', 'cinderwell-events' ) }
						value={ attributes.spacing }
						options={ [
							{
								label: __( 'None', 'cinderwell-events' ),
								value: 'none',
							},
							{
								label: __( 'SM', 'cinderwell-events' ),
								value: 'sm',
							},
							{
								label: __( 'MD', 'cinderwell-events' ),
								value: 'md',
							},
							{
								label: __( 'LG', 'cinderwell-events' ),
								value: 'lg',
							},
						] }
						onChange={ ( spacing ) => setAttributes( { spacing } ) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Background', 'cinderwell-events' ) }
					initialOpen={ false }
				>
					<ColorChips
						value={ attributes.background }
						onChange={ ( background ) =>
							setAttributes( { background } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
			/>
		</div>
	);
};

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: () => null,
} );
