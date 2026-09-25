import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const tool = document.querySelector( '[data-cw-location-pin-tool]' );

if ( tool ) {
	const button = tool.querySelector( '[data-cw-location-geocode]' );
	const status = tool.querySelector( '[data-cw-location-geocode-status]' );
	const value = ( id ) => document.getElementById( id )?.value.trim() || '';

	button?.addEventListener( 'click', async () => {
		const address = [
			value( 'cw-location-address_line_1' ),
			value( 'cw-location-address_line_2' ),
			value( 'cw-location-locality' ),
			value( 'cw-location-region' ),
			value( 'cw-location-postal_code' ),
			value( 'cw-location-country' ),
		].filter( Boolean ).join( ', ' );

		if ( ! address ) {
			status.textContent = __( 'Enter an address before placing the pin.', 'cinderwell' );
			document.getElementById( 'cw-location-address_line_1' )?.focus();
			return;
		}

		button.disabled = true;
		status.textContent = __( 'Finding address…', 'cinderwell' );
		try {
			const result = await apiFetch( { path: `/cinderwell/v1/map/geocode?address=${ encodeURIComponent( address ) }` } );
			const latitude = document.getElementById( 'cw-location-latitude' );
			const longitude = document.getElementById( 'cw-location-longitude' );
			if ( latitude ) latitude.value = result.latitude;
			if ( longitude ) longitude.value = result.longitude;
			status.textContent = __( 'Pin placed. Update the location to save it.', 'cinderwell' );
		} catch ( error ) {
			status.textContent = error.message || __( 'The address could not be located.', 'cinderwell' );
		} finally {
			button.disabled = false;
		}
	} );
}
