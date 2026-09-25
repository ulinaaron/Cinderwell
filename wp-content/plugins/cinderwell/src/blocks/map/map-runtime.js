import L from 'leaflet';

export { L };

export const createPinIcon = () => L.divIcon( {
	className: 'cinderwell-map__marker-wrap',
	html: '<span class="cinderwell-map__marker"><span></span></span>',
	iconAnchor: [ 18, 40 ],
	iconSize: [ 36, 44 ],
	popupAnchor: [ 0, -38 ],
} );

export const createPopupContent = ( location, directionsLabel ) => {
	const popup = document.createElement( 'div' );
	popup.className = 'cinderwell-map__popup';

	if ( location.name ) {
		const name = document.createElement( 'strong' );
		name.className = 'cinderwell-map__popup-name';
		name.textContent = location.name;
		popup.appendChild( name );
	}

	if ( location.address ) {
		const address = document.createElement( 'address' );
		address.textContent = location.address;
		popup.appendChild( address );
	}

	if ( location.phone ) {
		const phone = document.createElement( 'a' );
		phone.href = `tel:${ location.phone.replace( /[^0-9+]/g, '' ) }`;
		phone.textContent = location.phone;
		popup.appendChild( phone );
	}

	// popupHtml is sanitized with wp_kses_post before it reaches the browser.
	// Keep it in a separate wrapper so client-theme additions remain scoped.
	if ( location.popupHtml ) {
		const extra = document.createElement( 'div' );
		extra.className = 'cinderwell-map__popup-extra';
		extra.innerHTML = location.popupHtml;
		popup.appendChild( extra );
	}

	if ( location.url ) {
		const detail = document.createElement( 'a' );
		detail.href = location.url;
		detail.textContent = 'View location';
		popup.appendChild( detail );
	}

	if ( location.directionsUrl ) {
		const link = document.createElement( 'a' );
		link.className = 'cinderwell-map__directions';
		link.href = location.directionsUrl;
		link.textContent = directionsLabel;
		popup.appendChild( link );
	}

	return popup;
};

export const validLocations = ( locations = [] ) => locations.filter( ( location ) => {
	const latitude = Number( location.latitude );
	const longitude = Number( location.longitude );
	return Number.isFinite( latitude ) && latitude >= -90 && latitude <= 90 && Number.isFinite( longitude ) && longitude >= -180 && longitude <= 180;
} );
