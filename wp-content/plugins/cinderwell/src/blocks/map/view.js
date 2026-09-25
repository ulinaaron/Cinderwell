import { L, createPinIcon, createPopupContent, validLocations } from './map-runtime';

document.querySelectorAll( '[data-cw-map]' ).forEach( ( wrapper ) => {
	const canvas = wrapper.querySelector( '[data-cw-map-canvas]' );
	const dataNode = wrapper.querySelector( '[data-cw-map-data]' );
	if ( ! canvas || ! dataNode ) return;

	let data;
	try {
		data = JSON.parse( dataNode.textContent );
	} catch ( error ) {
		return;
	}

	const locations = validLocations( data.locations );
	if ( ! locations.length ) return;

	const map = L.map( canvas, { scrollWheelZoom: false, preferCanvas: true } );
	L.tileLayer( data.tileUrl, {
		attribution: data.attribution,
		maxZoom: 19,
	} ).addTo( map );

	const bounds = [];
	let initialMarker = null;
	const markers = new Map();
	locations.forEach( ( location, index ) => {
		const latLng = [ Number( location.latitude ), Number( location.longitude ) ];
		const markerLabel = location.name || location.address || 'Map location';
		const marker = L.marker( latLng, {
			icon: createPinIcon(),
			title: markerLabel,
			alt: markerLabel,
			riseOnHover: true,
		} ).addTo( map );
		marker.bindPopup( createPopupContent( location, data.directionsLabel ), { keepInView: true } );
		markers.set( String( location.id ), marker );
		if ( data.openPopup && locations.length === 1 && index === 0 ) initialMarker = marker;
		bounds.push( latLng );
	} );

	if ( bounds.length === 1 ) map.setView( bounds[0], data.zoom );
	else map.fitBounds( bounds, { padding: [ 36, 36 ], maxZoom: data.zoom } );
	initialMarker?.openPopup();

	const list = wrapper.querySelector( '[data-cw-map-list]' );
	const items = [ ...( list?.querySelectorAll( '[data-cw-map-item]' ) || [] ) ];
	items.forEach( ( item ) => {
		item.querySelector( '[data-cw-map-show]' )?.addEventListener( 'click', () => {
			const marker = markers.get( item.dataset.locationId );
			if ( ! marker ) return;
			map.setView( marker.getLatLng(), Math.max( map.getZoom(), data.zoom ) );
			marker.openPopup();
			canvas.focus( { preventScroll: true } );
			canvas.scrollIntoView( { behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth', block: 'center' } );
		} );
	} );

	const search = wrapper.querySelector( '[data-cw-map-search]' );
	const category = wrapper.querySelector( '[data-cw-map-category]' );
	const status = wrapper.querySelector( '[data-cw-map-status]' );
	const empty = wrapper.querySelector( '[data-cw-map-empty]' );
	const messages = data.messages || {};
	const visibleItems = () => items.filter( ( item ) => ! item.hidden );
	const announce = ( message ) => { if ( status ) status.textContent = message; };
	const applyFilters = () => {
		const query = search?.value.trim().toLocaleLowerCase() || '';
		const term = category?.value || '';
		items.forEach( ( item ) => {
			const terms = ( item.dataset.terms || '' ).split( ',' );
			item.hidden = !! ( query && ! ( item.dataset.search || '' ).includes( query ) ) || !! ( term && ! terms.includes( term ) );
			const marker = markers.get( item.dataset.locationId );
			if ( marker ) {
				if ( item.hidden && map.hasLayer( marker ) ) map.removeLayer( marker );
				if ( ! item.hidden && ! map.hasLayer( marker ) ) marker.addTo( map );
			}
		} );
		const count = visibleItems().length;
		if ( empty ) empty.hidden = count > 0;
		announce( count === 1 ? ( messages.singleResult || '1 location shown.' ) : ( messages.multipleResults || '%d locations shown.' ).replace( '%d', count ) );
	};
	search?.addEventListener( 'input', applyFilters );
	category?.addEventListener( 'change', applyFilters );

	const radians = ( value ) => value * Math.PI / 180;
	const distanceMiles = ( fromLatitude, fromLongitude, location ) => {
		const latitude = Number( location.latitude );
		const longitude = Number( location.longitude );
		const latitudeDelta = radians( latitude - fromLatitude );
		const longitudeDelta = radians( longitude - fromLongitude );
		const a = Math.sin( latitudeDelta / 2 ) ** 2 + Math.cos( radians( fromLatitude ) ) * Math.cos( radians( latitude ) ) * Math.sin( longitudeDelta / 2 ) ** 2;
		return 3958.8 * 2 * Math.atan2( Math.sqrt( a ), Math.sqrt( 1 - a ) );
	};
	const locate = wrapper.querySelector( '[data-cw-map-locate]' );
	locate?.addEventListener( 'click', () => {
		if ( ! navigator.geolocation ) {
			announce( messages.browserUnsupported || 'Your browser does not support location lookup.' );
			return;
		}
		locate.disabled = true;
		announce( messages.requesting || 'Requesting your location…' );
		navigator.geolocation.getCurrentPosition( ( position ) => {
			const ranked = locations.map( ( location ) => ( { location, distance: distanceMiles( position.coords.latitude, position.coords.longitude, location ) } ) ).sort( ( a, b ) => a.distance - b.distance );
			ranked.forEach( ( entry ) => {
				const item = items.find( ( candidate ) => candidate.dataset.locationId === String( entry.location.id ) );
				if ( ! item ) return;
				item.dataset.distance = String( entry.distance );
				const distance = item.querySelector( '[data-cw-map-distance]' );
				if ( distance ) distance.textContent = ( messages.distance || '%s miles away' ).replace( '%s', entry.distance.toFixed( 1 ) );
				list.querySelector( '[data-cw-map-list-items]' )?.appendChild( item );
			} );
			announce( messages.sorted || 'Locations sorted nearest first.' );
			locate.disabled = false;
			visibleItems()[0]?.querySelector( 'a, button' )?.focus();
		}, ( error ) => {
			const denied = error.code === error.PERMISSION_DENIED;
			announce( denied ? ( messages.denied || 'Location access was not allowed. You can still search the directory.' ) : ( messages.unavailable || 'Your location could not be determined. You can still search the directory.' ) );
			locate.disabled = false;
		}, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 } );
	} );

	wrapper.classList.add( 'is-enhanced' );
	requestAnimationFrame( () => map.invalidateSize() );
} );
