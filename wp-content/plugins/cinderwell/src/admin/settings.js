import './settings.css';

document.addEventListener( 'click', ( event ) => {
	const confirmation = event.target.closest( '[data-cw-confirm]' );
	if ( confirmation && ! window.confirm( confirmation.dataset.cwConfirm ) ) {
		event.preventDefault();
		return;
	}

	const select = event.target.closest( '[data-cw-media-select]' );
	if ( select ) {
		const field = select.closest( '[data-cw-media]' );
		const frame = window.wp.media( {
			title: 'Choose an image',
			button: { text: 'Use this image' },
			multiple: false,
			library: { type: 'image' },
		} );
		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			field.querySelector( '[data-cw-media-input]' ).value = attachment.id;
			const image = document.createElement( 'img' );
			image.src = attachment.sizes?.medium?.url || attachment.url;
			image.alt = '';
			field.querySelector( '[data-cw-media-preview]' ).replaceChildren( image );
			field.querySelector( '[data-cw-media-remove]' ).hidden = false;
		} );
		frame.open();
		return;
	}

	const removeMedia = event.target.closest( '[data-cw-media-remove]' );
	if ( removeMedia ) {
		const field = removeMedia.closest( '[data-cw-media]' );
		field.querySelector( '[data-cw-media-input]' ).value = '';
		field.querySelector( '[data-cw-media-preview]' ).innerHTML = '';
		removeMedia.hidden = true;
		return;
	}

	const add = event.target.closest( '[data-cw-repeater-add]' );
	if ( add ) {
		const repeater = add.closest( '[data-cw-repeater]' );
		const rows = repeater.querySelector( '[data-cw-repeater-rows]' );
		if ( rows.children.length >= Number( repeater.dataset.maxItems || 20 ) ) return;
		const index = `${ Date.now() }-${ rows.children.length }`;
		rows.insertAdjacentHTML( 'beforeend', repeater.querySelector( 'template' ).innerHTML.replaceAll( '__index__', index ) );
		rows.lastElementChild.querySelector( 'input' )?.focus();
		return;
	}

	const removeRow = event.target.closest( '[data-cw-repeater-remove]' );
	if ( removeRow ) removeRow.closest( '[data-cw-repeater-row]' ).remove();
} );

document.addEventListener( 'change', ( event ) => {
	const preset = event.target.closest( '[data-cw-editor-access-preset]' );
	if ( ! preset ) return;

	const role = preset.closest( '[data-cw-editor-access-role]' );
	const custom = role?.querySelector( '[data-cw-editor-access-custom]' );
	const description = role?.querySelector( '[data-cw-editor-access-description]' );
	if ( custom ) custom.hidden = preset.value !== 'custom';
	if ( description ) description.textContent = preset.selectedOptions[ 0 ]?.dataset.description || '';
} );
