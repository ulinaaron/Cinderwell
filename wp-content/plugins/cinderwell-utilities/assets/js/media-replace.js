/**
 * Media replacement controls for attachment edit and Media Library screens.
 */
( function () {
	'use strict';

	const config = window.cinderwellUtilitiesMedia;
	if ( ! config ) {
		return;
	}

	const format = ( template, first, second ) => template
		.replace( '%1$s', first )
		.replace( '%2$s', second );

	document.addEventListener( 'change', async ( event ) => {
		const input = event.target.closest( '.cinderwell-media-replace__input' );
		if ( ! input ) {
			return;
		}

		const control = input.closest( '.cinderwell-media-replace' );
		const file = input.files && input.files[ 0 ];
		if ( ! control || ! file ) {
			return;
		}

		const button = control.querySelector( '.cinderwell-media-replace__button' );
		const status = control.querySelector( '.cinderwell-media-replace__status' );
		const attachmentId = control.dataset.attachmentId;
		const currentFilename = control.dataset.filename || 'this file';

		if ( ! window.confirm( format( config.i18n.confirm, currentFilename, file.name ) ) ) {
			input.value = '';
			return;
		}

		const data = new window.FormData();
		data.append( 'action', 'cinderwell_replace_media' );
		data.append( 'nonce', config.nonce );
		data.append( 'attachment_id', attachmentId );
		data.append( 'file', file );

		control.classList.add( 'is-busy' );
		input.disabled = true;
		button.setAttribute( 'aria-disabled', 'true' );
		status.className = 'cinderwell-media-replace__status';
		status.textContent = config.i18n.replacing;

		try {
			const response = await window.fetch( config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: data,
			} );
			const result = await response.json();

			if ( ! response.ok || ! result.success ) {
				throw new Error( result.data && result.data.message ? result.data.message : config.i18n.error );
			}

			status.classList.add( 'is-success' );
			status.textContent = result.data.message || config.i18n.success;
			window.setTimeout( () => window.location.reload(), 500 );
		} catch ( error ) {
			status.classList.add( 'is-error' );
			status.textContent = error.message || config.i18n.networkError;
			control.classList.remove( 'is-busy' );
			input.disabled = false;
			button.removeAttribute( 'aria-disabled' );
			input.value = '';
		}
	} );
}() );
