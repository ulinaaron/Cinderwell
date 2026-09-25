import { registerBlockType, registerBlockVariation } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button, Notice, PanelBody, SelectControl, TextControl, TextareaControl, ToggleControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { BlockIdentity, LayoutControls, SegmentedControl, SortableItemCard, getSpacingClassName, moveArrayItem } from '../../shared/inspector-controls';
import { L, createPinIcon, createPopupContent, validLocations } from './map-runtime';
import metadata from './block.json';

let locationCounter = 0;
const newLocation = ( name = '', address = '' ) => ( {
	id: `location-${ Date.now().toString( 36 ) }-${ ++locationCounter }`,
	name,
	address,
	latitude: '',
	longitude: '',
} );
const directionsUrl = ( address ) => address ? `https://www.google.com/maps/dir/?api=1&destination=${ encodeURIComponent( address ) }` : '';

const LocationListPreview = ( { locations } ) => <div className="cinderwell-map__directory" aria-label={ __( 'Location list preview', 'cinderwell' ) }>
	{ locations.map( ( location ) => <article className="cinderwell-map__location" key={ location.id }>
		<strong>{ location.name }</strong>
		{ location.address && <address>{ location.address }</address> }
		{ location.phone && <span>{ location.phone }</span> }
	</article> ) }
</div>;

const MapPreview = ( { locations, zoom, height, mapLabel, directionsLabel, openPopup } ) => {
	const container = useRef();
	const mapRef = useRef();
	const markerLayer = useRef();

	useEffect( () => {
		if ( ! container.current ) return undefined;
		mapRef.current = L.map( container.current, {
			boxZoom: false,
			doubleClickZoom: false,
			dragging: false,
			keyboard: false,
			scrollWheelZoom: false,
			touchZoom: false,
			zoomControl: false,
		} ).setView( [ 20, 0 ], 2 );
		L.tileLayer( 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
			maxZoom: 19,
		} ).addTo( mapRef.current );
		markerLayer.current = L.layerGroup().addTo( mapRef.current );
		requestAnimationFrame( () => mapRef.current?.invalidateSize() );
		return () => {
			mapRef.current?.remove();
			mapRef.current = null;
		};
	}, [] );

	useEffect( () => {
		if ( ! mapRef.current || ! markerLayer.current ) return;
		markerLayer.current.clearLayers();
		const valid = validLocations( locations );
		const bounds = [];
		let initialMarker = null;
		valid.forEach( ( location, index ) => {
			const latLng = [ Number( location.latitude ), Number( location.longitude ) ];
			const markerLabel = location.name || location.address || __( 'Map location', 'cinderwell' );
			const marker = L.marker( latLng, { icon: createPinIcon(), title: markerLabel, alt: markerLabel } );
			marker.bindPopup( createPopupContent( { ...location, directionsUrl: directionsUrl( location.address ) }, directionsLabel ) );
			marker.addTo( markerLayer.current );
			if ( openPopup && valid.length === 1 && index === 0 ) initialMarker = marker;
			bounds.push( latLng );
		} );
		if ( bounds.length === 1 ) mapRef.current.setView( bounds[0], zoom );
		if ( bounds.length > 1 ) mapRef.current.fitBounds( bounds, { padding: [ 36, 36 ], maxZoom: zoom } );
		initialMarker?.openPopup();
	}, [ locations, zoom, directionsLabel, openPopup ] );

	return <div className={ `cinderwell-map__canvas cinderwell-map__canvas--${ height }` } ref={ container } role="region" aria-label={ mapLabel } />;
};

const LocationFields = ( { location, index, updateLocation, locate, locating, status } ) => <>
	<TextControl label={ __( 'Location name', 'cinderwell' ) } value={ location.name || '' } onChange={ ( name ) => updateLocation( index, { name } ) } />
	<TextareaControl label={ __( 'Address', 'cinderwell' ) } value={ location.address || '' } onChange={ ( address ) => updateLocation( index, { address, latitude: '', longitude: '' } ) } rows={ 3 } />
	<Button variant="secondary" onClick={ () => locate( index ) } disabled={ ! location.address?.trim() || locating } isBusy={ locating }>{ locating ? __( 'Finding address…', 'cinderwell' ) : __( 'Place pin from address', 'cinderwell' ) }</Button>
	{ status && <p className={ `cinderwell-map-editor__status is-${ status.type }` } role="status">{ status.message }</p> }
	<details className="cinderwell-map-editor__coordinates">
		<summary>{ __( 'Pin coordinates', 'cinderwell' ) }</summary>
		<div className="cinderwell-map-editor__coordinate-fields">
			<TextControl type="number" step="any" label={ __( 'Latitude', 'cinderwell' ) } value={ location.latitude } onChange={ ( latitude ) => updateLocation( index, { latitude } ) } />
			<TextControl type="number" step="any" label={ __( 'Longitude', 'cinderwell' ) } value={ location.longitude } onChange={ ( longitude ) => updateLocation( index, { longitude } ) } />
		</div>
	</details>
</>;

const MapPreviewPlaceholder = ( { count, selected, onLoad } ) => <div className="cinderwell-map-editor__preview-placeholder" aria-label={ __( 'Map preview is paused in the editor.', 'cinderwell' ) }>
	<div>
		<strong>{ __( 'Map preview', 'cinderwell' ) }</strong>
		<span>{ count === 1 ? __( '1 mapped location', 'cinderwell' ) : sprintf( __( '%d mapped locations', 'cinderwell' ), count ) }</span>
	</div>
	{ selected && <Button variant="secondary" onClick={ onLoad }>{ __( 'Load map preview', 'cinderwell' ) }</Button> }
</div>;

const LocatorToolsPreview = ( { attributes, categoryLabel, showCategory } ) => <div className="cinderwell-map__locator-tools cinderwell-map-editor__locator-tools" aria-label={ __( 'Location finder controls preview', 'cinderwell' ) }>
	{ attributes.enableSearch && <div className="cinderwell-map__search">
		<span>{ attributes.searchLabel || __( 'Search locations', 'cinderwell' ) }</span>
		<span className="cinderwell-map-editor__preview-control" aria-hidden="true"></span>
	</div> }
	{ showCategory && <div className="cinderwell-map__category">
		<span>{ __( 'Location category', 'cinderwell' ) }</span>
		<span className="cinderwell-map-editor__preview-control cinderwell-map-editor__preview-select" aria-hidden="true">{ categoryLabel }</span>
	</div> }
	{ attributes.enableGeolocation && <span className="cinderwell-map-editor__preview-button" aria-hidden="true">{ attributes.geolocationLabel || __( 'Use my location', 'cinderwell' ) }</span> }
</div>;

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes, clientId, isSelected } ) => {
		const [ lookup, setLookup ] = useState( null );
		const [ sourceLocations, setSourceLocations ] = useState( [] );
		const [ sourceStatus, setSourceStatus ] = useState( 'idle' );
		const [ previewEnabled, setPreviewEnabled ] = useState( false );
		const company = window.cinderwellEditorSettings?.previewValues || {};
		const mapSettings = window.cinderwellEditorSettings?.map || { sources: [], presentations: [] };
		const locationsSettings = window.cinderwellEditorSettings?.addons?.locations || { enabled: false, categories: [] };
		const sourceOptions = mapSettings.sources?.length ? mapSettings.sources : [ { slug: 'manual', label: __( 'Manual', 'cinderwell' ), enabled: true }, { slug: 'locations', label: __( 'Locations', 'cinderwell' ), enabled: locationsSettings.enabled, editorEndpoint: locationsSettings.mapEndpoint } ];
		const presentationOptions = mapSettings.presentations?.length ? mapSettings.presentations : [ { slug: 'map', label: __( 'Map', 'cinderwell' ), base: 'map' }, { slug: 'directory', label: __( 'Map + list', 'cinderwell' ), base: 'directory' }, { slug: 'locator', label: __( 'Locator', 'cinderwell' ), base: 'locator' } ];
		const sourceSettings = sourceOptions.find( ( source ) => source.slug === attributes.source ) || sourceOptions.find( ( source ) => source.slug === 'manual' );
		const presentationSettings = presentationOptions.find( ( presentation ) => presentation.slug === attributes.presentation ) || presentationOptions.find( ( presentation ) => presentation.slug === 'map' );
		const usesExternalSource = attributes.source !== 'manual';
		const usesLocations = attributes.source === 'locations';
		const presentationBase = usesExternalSource ? ( presentationSettings?.base || 'map' ) : 'map';
		const locations = attributes.locations?.length ? attributes.locations : [ newLocation( company.company_name || '', company.company_address || '' ) ];
		const manualLocations = attributes.mode === 'multiple' ? locations : locations.slice( 0, 1 );
		const visibleLocations = usesExternalSource ? sourceLocations : manualLocations;
		const selectedCategory = ( locationsSettings.categories || [] ).find( ( term ) => Number( term.value ) === Number( attributes.locationTerm ) );

		useEffect( () => {
			if ( ! attributes.locations?.length ) setAttributes( { locations } );
		}, [] );

		useEffect( () => {
			if ( ! isSelected ) setPreviewEnabled( false );
		}, [ isSelected ] );

		useEffect( () => {
			if ( ! usesExternalSource || ! sourceSettings?.enabled || ! sourceSettings?.editorEndpoint ) {
				setSourceLocations( [] );
				setSourceStatus( 'idle' );
				return undefined;
			}
			let current = true;
			setSourceLocations( [] );
			setSourceStatus( 'loading' );
			const termQuery = usesLocations ? `${ sourceSettings.editorEndpoint.includes( '?' ) ? '&' : '?' }term=${ Number( attributes.locationTerm ) || 0 }` : '';
			apiFetch( { path: `${ sourceSettings.editorEndpoint }${ termQuery }` } )
				.then( ( records ) => {
					if ( ! current ) return;
					setSourceLocations( Array.isArray( records ) ? records : [] );
					setSourceStatus( 'ready' );
				} )
				.catch( () => {
					if ( current ) setSourceStatus( 'error' );
				} );
			return () => { current = false; };
		}, [ usesExternalSource, usesLocations, sourceSettings?.enabled, sourceSettings?.editorEndpoint, attributes.locationTerm ] );

		const updateLocation = ( index, changes ) => setAttributes( { locations: locations.map( ( location, itemIndex ) => itemIndex === index ? { ...location, ...changes } : location ) } );
		const locate = async ( index ) => {
			const address = locations[ index ]?.address?.trim();
			if ( ! address ) return;
			setLookup( { index, type: 'loading', message: __( 'Finding address…', 'cinderwell' ) } );
			try {
				const result = await apiFetch( { path: `/cinderwell/v1/map/geocode?address=${ encodeURIComponent( address ) }` } );
				updateLocation( index, { latitude: result.latitude, longitude: result.longitude } );
				setLookup( { index, type: 'success', message: __( 'Pin placed. Confirm its position in the preview.', 'cinderwell' ) } );
			} catch ( error ) {
				setLookup( { index, type: 'error', message: error.message || __( 'The address could not be located.', 'cinderwell' ) } );
			}
		};
		const addLocation = () => setAttributes( { locations: [ ...locations, newLocation() ] } );
		const blockProps = useBlockProps( { className: `cinderwell-map cinderwell-map--height-${ attributes.height }${ getSpacingClassName( attributes ) }` } );

		return <>
			<InspectorControls>
				<BlockIdentity icon="⌖" title={ __( 'Map', 'cinderwell' ) } description={ __( 'OpenStreetMap locations and directions', 'cinderwell' ) } />
				<PanelBody title={ __( 'Locations', 'cinderwell' ) } initialOpen className="cw-panel cw-access-content">
					<SegmentedControl label={ __( 'Location source', 'cinderwell' ) } value={ attributes.source } options={ sourceOptions.map( ( source ) => ( { value: source.slug, label: source.label } ) ) } onChange={ ( source ) => setAttributes( { source, mode: source === 'manual' ? attributes.mode : 'multiple' } ) } />
					{ usesExternalSource ? <>
						{ ! sourceSettings?.enabled && <Notice status="warning" isDismissible={ false }>{ usesLocations ? __( 'The Locations add-on is off. This map will remain safely hidden on the site until Locations is enabled, or you can switch back to Manual.', 'cinderwell' ) : __( 'This map source is unavailable. The map will remain safely hidden until its integration is enabled, or you can switch back to Manual.', 'cinderwell' ) }</Notice> }
						{ sourceSettings?.enabled && ! sourceSettings?.editorEndpoint && <Notice status="info" isDismissible={ false }>{ __( 'This source does not provide an editor preview. Its locations will still resolve on the published page.', 'cinderwell' ) }</Notice> }
						{ usesLocations && locationsSettings.enabled && <SelectControl label={ __( 'Location category', 'cinderwell' ) } value={ String( attributes.locationTerm || 0 ) } options={ [ { value: '0', label: __( 'All locations', 'cinderwell' ) }, ...( locationsSettings.categories || [] ).map( ( term ) => ( { value: String( term.value ), label: term.label } ) ) ] } onChange={ ( value ) => setAttributes( { locationTerm: Number( value ) || 0 } ) } /> }
						{ sourceStatus === 'loading' && <p role="status">{ __( 'Loading locations…', 'cinderwell' ) }</p> }
						{ sourceStatus === 'error' && <Notice status="error" isDismissible={ false }>{ __( 'Locations could not be loaded for the editor preview.', 'cinderwell' ) }</Notice> }
					</> : <>
					<SegmentedControl label={ __( 'Map type', 'cinderwell' ) } value={ attributes.mode } options={ [ { value: 'single', label: __( 'Single', 'cinderwell' ) }, { value: 'multiple', label: __( 'Multiple', 'cinderwell' ) } ] } onChange={ ( mode ) => setAttributes( { mode } ) } />
					{ attributes.mode === 'single' ? <div className="cw-item-card"><div className="cw-item-card__fields"><LocationFields location={ locations[0] } index={ 0 } updateLocation={ updateLocation } locate={ locate } locating={ lookup?.index === 0 && lookup.type === 'loading' } status={ lookup?.index === 0 ? lookup : null } /></div></div> : <>
						{ locations.map( ( location, index ) => <SortableItemCard key={ location.id || index } index={ index } listId={ `${ clientId }-map-locations` } label={ location.name || sprintf( __( 'Location %d', 'cinderwell' ), index + 1 ) } onMove={ ( from, to ) => setAttributes( { locations: moveArrayItem( locations, from, to ) } ) } onRemove={ () => setAttributes( { locations: locations.filter( ( _, itemIndex ) => itemIndex !== index ) } ) }><LocationFields location={ location } index={ index } updateLocation={ updateLocation } locate={ locate } locating={ lookup?.index === index && lookup.type === 'loading' } status={ lookup?.index === index ? lookup : null } /></SortableItemCard> ) }
						<Button variant="secondary" className="cw-add-item" onClick={ addLocation }>+ { __( 'Add location', 'cinderwell' ) }</Button>
					</> }</> }
				</PanelBody>
				<PanelBody title={ __( 'Map settings', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-layout">
					{ usesExternalSource && <SegmentedControl label={ __( 'Presentation', 'cinderwell' ) } value={ attributes.presentation } options={ presentationOptions.map( ( presentation ) => ( { value: presentation.slug, label: presentation.label } ) ) } onChange={ ( presentation ) => { const base = presentationOptions.find( ( item ) => item.slug === presentation )?.base || 'map'; setAttributes( { presentation, enableSearch: base === 'locator', enableGeolocation: base === 'locator' } ); } } /> }
					<SegmentedControl label={ __( 'Height', 'cinderwell' ) } value={ attributes.height } options={ [ { value: 'small', label: __( 'Small', 'cinderwell' ) }, { value: 'medium', label: __( 'Medium', 'cinderwell' ) }, { value: 'large', label: __( 'Large', 'cinderwell' ) } ] } onChange={ ( height ) => setAttributes( { height } ) } />
					<TextControl type="number" min="1" max="19" label={ __( 'Maximum zoom', 'cinderwell' ) } value={ attributes.zoom } onChange={ ( zoom ) => setAttributes( { zoom: Math.min( 19, Math.max( 1, Number( zoom ) || 14 ) ) } ) } />
					<TextControl label={ __( 'Accessible map label', 'cinderwell' ) } value={ attributes.mapLabel } onChange={ ( mapLabel ) => setAttributes( { mapLabel } ) } />
					<TextControl label={ __( 'Directions link label', 'cinderwell' ) } value={ attributes.directionsLabel } onChange={ ( directionsLabel ) => setAttributes( { directionsLabel } ) } />
					{ ! usesExternalSource && attributes.mode === 'single' && <ToggleControl label={ __( 'Open location popup initially', 'cinderwell' ) } checked={ attributes.openPopup } onChange={ ( openPopup ) => setAttributes( { openPopup } ) } /> }
					{ usesExternalSource && presentationBase === 'locator' && <>
						<ToggleControl label={ __( 'Show keyword search', 'cinderwell' ) } checked={ attributes.enableSearch } onChange={ ( enableSearch ) => setAttributes( { enableSearch } ) } />
						<ToggleControl label={ __( 'Allow “Use my location”', 'cinderwell' ) } checked={ attributes.enableGeolocation } onChange={ ( enableGeolocation ) => setAttributes( { enableGeolocation } ) } />
					</> }
				</PanelBody>
			<LayoutControls attributes={ attributes } setAttributes={ setAttributes } />
			</InspectorControls>
			<section { ...blockProps }><div className={ `cinderwell-map__inner${ usesExternalSource && presentationBase !== 'map' ? ' cinderwell-map__inner--directory' : '' }` } style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
				{ usesExternalSource && presentationBase === 'locator' && <LocatorToolsPreview attributes={ attributes } categoryLabel={ selectedCategory?.label || __( 'All locations', 'cinderwell' ) } showCategory={ usesLocations && ( locationsSettings.categories || [] ).length > 1 } /> }
				{ validLocations( visibleLocations ).length > 0 && ( previewEnabled
					? <MapPreview locations={ visibleLocations } zoom={ attributes.zoom } height={ attributes.height } mapLabel={ attributes.mapLabel } directionsLabel={ attributes.directionsLabel } openPopup={ ! usesExternalSource && attributes.mode === 'single' && attributes.openPopup } />
					: <MapPreviewPlaceholder count={ validLocations( visibleLocations ).length } selected={ isSelected } onLoad={ () => setPreviewEnabled( true ) } /> ) }
				{ usesExternalSource && presentationBase !== 'map' && <LocationListPreview locations={ visibleLocations } /> }
				{ ! validLocations( visibleLocations ).length && <div className="cinderwell-map-editor__empty"><strong>{ usesExternalSource ? __( 'No mapped locations found.', 'cinderwell' ) : __( 'Place a pin to finish the map.', 'cinderwell' ) }</strong><span>{ usesExternalSource ? __( 'Add coordinates to the source entries, adjust its scope, or confirm the integration provides an editor preview.', 'cinderwell' ) : __( 'Add an address in the block settings, then choose “Place pin from address.”', 'cinderwell' ) }</span></div> }
			</div></section>
		</>;
	},
	save: () => null,
} );

registerBlockVariation( metadata.name, {
	name: 'multiple-locations',
	title: __( 'Multiple Locations Map', 'cinderwell' ),
	description: __( 'Show several manually managed locations on one map.', 'cinderwell' ),
	icon: 'location-alt',
	attributes: { mode: 'multiple', openPopup: false },
	isActive: [ 'mode' ],
	scope: [ 'inserter', 'transform' ],
} );

const locationsSettings = window.cinderwellEditorSettings?.addons?.locations;
if ( locationsSettings?.enabled ) {
	registerBlockVariation( metadata.name, {
		name: 'locations-map',
		title: __( 'Locations Map', 'cinderwell' ),
		description: __( 'Show mapped entries from the Locations add-on.', 'cinderwell' ),
		icon: 'location-alt',
		attributes: { source: 'locations', mode: 'multiple', presentation: 'map', openPopup: false },
		isActive: ( attrs ) => attrs.source === 'locations' && attrs.presentation === 'map',
		scope: [ 'inserter', 'transform' ],
	} );
	registerBlockVariation( metadata.name, {
		name: 'location-directory',
		title: __( 'Location Directory', 'cinderwell' ),
		description: __( 'Pair a Locations map with an accessible directory.', 'cinderwell' ),
		icon: 'list-view',
		attributes: { source: 'locations', mode: 'multiple', presentation: 'directory', openPopup: false },
		isActive: ( attrs ) => attrs.source === 'locations' && attrs.presentation === 'directory',
		scope: [ 'inserter', 'transform' ],
	} );
	registerBlockVariation( metadata.name, {
		name: 'location-finder',
		title: __( 'Nearest Location Finder', 'cinderwell' ),
		description: __( 'Search Locations or sort them by distance after visitor permission.', 'cinderwell' ),
		icon: 'search',
		attributes: { source: 'locations', mode: 'multiple', presentation: 'locator', enableSearch: true, enableGeolocation: true, openPopup: false },
		isActive: ( attrs ) => attrs.source === 'locations' && attrs.presentation === 'locator',
		scope: [ 'inserter', 'transform' ],
	} );
}
