import './settings.css';

const markSettingsFormDirty = ( form ) => {
	if ( ! form || form.classList.contains( 'has-unsaved-changes' ) ) return;
	form.classList.add( 'has-unsaved-changes' );
	const status = form.querySelector( '[data-cw-settings-save-status]' );
	if ( status ) status.textContent = 'Unsaved changes';
};

const enhanceSettingsForms = () => {
	const content = document.querySelector( '.cw-settings-content' );
	if ( ! content ) return;

	content.querySelectorAll( '.card' ).forEach( ( card ) => card.classList.add( 'cw-settings-card' ) );
	content.querySelectorAll( 'form' ).forEach( ( form ) => {
		if ( form.classList.contains( 'cinderwell-utilities-form' ) ) return;

		const submitRows = Array.from( form.children ).filter( ( child ) => child.classList.contains( 'submit' ) );
		const submitRow = submitRows[ submitRows.length - 1 ];
		if ( ! submitRow ) return;

		form.classList.add( 'cw-settings-form' );
		if (
			! form.closest( '.cw-settings-card' ) &&
			! form.matches( '.cw-token-form' ) &&
			! form.querySelector( '.cw-settings-card-grid, .cw-addon-grid, .cw-editor-access-roles' )
		) {
			form.classList.add( 'cw-settings-card' );
		}

		submitRow.classList.add( 'cw-settings-save-bar' );
		const status = document.createElement( 'span' );
		status.className = 'cw-settings-save-status';
		status.dataset.cwSettingsSaveStatus = '';
		status.setAttribute( 'aria-live', 'polite' );
		status.textContent = 'No unsaved changes';
		submitRow.prepend( status );

		const handleFormChange = ( event ) => {
			if ( event.target.closest( '[data-cw-ignore-dirty]' ) ) return;
			markSettingsFormDirty( form );
		};
		form.addEventListener( 'input', handleFormChange );
		form.addEventListener( 'change', handleFormChange );
		form.addEventListener( 'submit', () => {
			form.classList.add( 'is-submitting' );
			status.textContent = 'Saving…';
		} );
	} );
};

const enhanceBlockLibrary = () => {
	const library = document.querySelector( '[data-cw-block-library]' );
	if ( ! library ) return;

	const profiles = Array.from( library.querySelectorAll( '[data-cw-library-profile]' ) );
	const blocks = Array.from( library.querySelectorAll( '[data-cw-library-block]' ) );
	const search = library.querySelector( '[data-cw-library-search]' );
	const results = library.querySelector( '[data-cw-library-results]' );
	const description = library.querySelector( '[data-cw-library-description]' );
	const descriptions = {
		curated: 'Cinderwell and active Cinderwell add-ons only.',
		essentials: 'Cinderwell plus a small set of familiar WordPress content blocks.',
		full: 'Every block registered by WordPress, plugins, and the active theme.',
	};

	const filterBlocks = () => {
		const query = search?.value.trim().toLowerCase() || '';
		let visible = 0;
		blocks.forEach( ( label ) => {
			label.hidden = query !== '' && ! label.dataset.search.includes( query );
			if ( ! label.hidden ) visible++;
		} );
		if ( results ) results.textContent = `${ visible } of ${ blocks.length } blocks`;
	};

	profiles.forEach( ( profile ) => profile.addEventListener( 'change', () => {
		if ( ! profile.checked ) return;
		blocks.forEach( ( label ) => {
			const input = label.querySelector( 'input[type="checkbox"]' );
			const defaults = ( input.dataset.defaultProfiles || '' ).split( ',' ).filter( Boolean );
			input.checked = defaults.includes( profile.value );
		} );
		if ( description ) description.textContent = descriptions[ profile.value ] || '';
	} ) );

	search?.addEventListener( 'input', filterBlocks );
	filterBlocks();
};

const enhanceBusinessHours = () => {
	document.querySelectorAll( '[data-cw-business-hours]' ).forEach( ( schedule ) => {
		const updateDay = ( day ) => {
			const status = day.querySelector( '[data-cw-hours-status]:checked' )?.value || 'not_set';
			const periods = day.querySelector( '[data-cw-hours-periods]' );
			const note = day.querySelector( '[data-cw-hours-note]' );
			if ( periods ) periods.hidden = status !== 'open';
			if ( note ) note.hidden = status === 'not_set';
		};

		schedule.querySelectorAll( '[data-cw-hours-day]' ).forEach( updateDay );
		schedule.addEventListener( 'change', ( event ) => {
			if ( event.target.matches( '[data-cw-hours-status]' ) ) {
				updateDay( event.target.closest( '[data-cw-hours-day]' ) );
			}
		} );
		schedule.addEventListener( 'click', ( event ) => {
			const toggle = event.target.closest( '[data-cw-hours-split-toggle]' );
			if ( ! toggle ) return;

			const split = toggle.closest( '[data-cw-hours-periods]' )?.querySelector( '[data-cw-hours-split]' );
			if ( ! split ) return;

			const showSplit = split.hidden;
			split.hidden = ! showSplit;
			split.querySelectorAll( 'input' ).forEach( ( input ) => {
				input.disabled = ! showSplit;
				if ( ! showSplit ) input.value = '';
			} );
			if ( ! showSplit ) split.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			toggle.setAttribute( 'aria-expanded', showSplit ? 'true' : 'false' );
			toggle.textContent = showSplit ? toggle.dataset.removeLabel : toggle.dataset.addLabel;

			if ( showSplit ) split.querySelector( 'input' )?.focus();
		} );
	} );
};

document.addEventListener( 'DOMContentLoaded', enhanceSettingsForms );
document.addEventListener( 'DOMContentLoaded', enhanceBlockLibrary );
document.addEventListener( 'DOMContentLoaded', enhanceBusinessHours );

const updateTokenState = ( input ) => {
	const item = input.closest( '[data-cw-token-item]' );
	if ( ! item ) return;
	const isCustom = input.value.trim() !== '';
	item.classList.toggle( 'is-custom', isCustom );
	const state = item.querySelector( '[data-cw-token-state]' );
	if ( state ) state.textContent = isCustom ? 'Custom' : 'Inherited';

	const form = input.closest( '[data-cw-token-form]' );
	const customCount = form?.querySelector( '[data-cw-token-custom-count]' );
	if ( customCount ) customCount.textContent = form.querySelectorAll( '[data-cw-token-item].is-custom' ).length;
};

const enhanceTokenForm = () => {
	const form = document.querySelector( '[data-cw-token-form]' );
	if ( ! form ) return;

	const search = form.querySelector( '[data-cw-token-search]' );
	const results = form.querySelector( '[data-cw-token-results]' );
	const items = Array.from( form.querySelectorAll( '[data-cw-token-item]' ) );
	const groups = Array.from( form.querySelectorAll( '[data-cw-token-group]' ) );

	const filterTokens = () => {
		const query = search.value.trim().toLowerCase();
		let visible = 0;
		items.forEach( ( item ) => {
			item.hidden = query !== '' && ! item.dataset.cwTokenSearchText.includes( query );
			if ( ! item.hidden ) visible++;
		} );
		groups.forEach( ( group ) => {
			group.hidden = ! group.querySelector( '[data-cw-token-item]:not([hidden])' );
		} );
		results.textContent = query ? `${ visible } of ${ items.length }` : `${ items.length } tokens`;
	};

	search?.addEventListener( 'input', filterTokens );
	filterTokens();
};

document.addEventListener( 'DOMContentLoaded', enhanceTokenForm );

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
			field.querySelector( '[data-cw-media-input]' ).dispatchEvent( new Event( 'change', { bubbles: true } ) );
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
		field.querySelector( '[data-cw-media-input]' ).dispatchEvent( new Event( 'change', { bubbles: true } ) );
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
		repeater.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		return;
	}

	const removeRow = event.target.closest( '[data-cw-repeater-remove]' );
	if ( removeRow ) {
		const repeater = removeRow.closest( '[data-cw-repeater]' );
		removeRow.closest( '[data-cw-repeater-row]' ).remove();
		repeater.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		return;
	}

	const tokenReset = event.target.closest( '[data-cw-token-reset]' );
	if ( tokenReset ) {
		const item = tokenReset.closest( '[data-cw-token-item]' );
		const input = item?.querySelector( '[data-cw-token-input]' );
		if ( ! input ) return;
		input.value = '';
		const picker = item.querySelector( '[data-cw-token-picker]' );
		if ( picker ) picker.value = input.dataset.cwTokenDefault;
		input.closest( '[data-cw-token-form]' )?.style.setProperty( input.dataset.cwTokenVariable, input.dataset.cwTokenDefault );
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.focus();
	}
} );

document.addEventListener( 'input', ( event ) => {
	const picker = event.target.closest( '[data-cw-token-picker]' );
	if ( picker ) {
		const input = picker.closest( '[data-cw-token-item]' )?.querySelector( '[data-cw-token-input]' );
		if ( ! input ) return;
		input.value = picker.value;
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		return;
	}

	const tokenInput = event.target.closest( '[data-cw-token-input]' );
	if ( ! tokenInput ) return;
	const previewValue = tokenInput.value.trim() || tokenInput.dataset.cwTokenDefault;
	tokenInput.closest( '[data-cw-token-form]' )?.style.setProperty( tokenInput.dataset.cwTokenVariable, previewValue );
	const colorPicker = tokenInput.closest( '[data-cw-token-item]' )?.querySelector( '[data-cw-token-picker]' );
	if ( colorPicker && /^#[0-9a-f]{6}$/i.test( previewValue ) ) colorPicker.value = previewValue;
	updateTokenState( tokenInput );
} );

document.addEventListener( 'change', ( event ) => {
	const tokenChoice = event.target.closest( '[data-cw-token-choice] input[type="radio"]' );
	if ( tokenChoice ) {
		const choice = tokenChoice.closest( '[data-cw-token-choice]' );
		choice.closest( '[data-cw-token-form]' )?.style.setProperty( choice.dataset.cwTokenVariable, tokenChoice.value || choice.dataset.cwTokenDefault );
		const item = choice.closest( '[data-cw-token-item]' );
		item?.classList.toggle( 'is-custom', tokenChoice.value !== '' );
		const state = item?.querySelector( '[data-cw-token-state]' );
		if ( state ) state.textContent = tokenChoice.value !== '' ? 'Custom' : 'Inherited';
		const count = choice.closest( '[data-cw-token-form]' )?.querySelector( '[data-cw-token-custom-count]' );
		if ( count ) count.textContent = choice.closest( '[data-cw-token-form]' ).querySelectorAll( '[data-cw-token-item].is-custom' ).length;
	}

	const addonToggle = event.target.closest( '.cw-addon-toggle input[type="checkbox"]' );
	if ( addonToggle ) {
		const card = addonToggle.closest( '[data-cw-addon-card]' );
		card?.classList.toggle( 'is-enabled', addonToggle.checked );
		const status = card?.querySelector( '[data-cw-addon-status]' );
		if ( status ) status.textContent = addonToggle.checked ? 'Enabled' : 'Disabled';
	}

	const preset = event.target.closest( '[data-cw-editor-access-preset]' );
	if ( ! preset ) return;

	const role = preset.closest( '[data-cw-editor-access-role]' );
	const custom = role?.querySelector( '[data-cw-editor-access-custom]' );
	const description = role?.querySelector( '[data-cw-editor-access-description]' );
	if ( custom ) custom.hidden = preset.value !== 'custom';
	if ( description ) description.textContent = preset.selectedOptions[ 0 ]?.dataset.description || '';
} );
