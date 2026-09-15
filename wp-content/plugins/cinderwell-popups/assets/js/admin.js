( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', () => {
		const autoRules = document.getElementById(
			'cinderwell-popup-auto-rules'
		);
		const location = document.getElementById(
			'cinderwell-popup-display-location'
		);

		const updateTrigger = () => {
			const selected = document.querySelector(
				'input[name="cinderwell_popup_trigger_type"]:checked'
			);
			if ( autoRules ) {
				autoRules.hidden = ! selected || selected.value !== 'auto';
			}
		};

		const updateLocation = () => {
			document
				.querySelectorAll( '[data-cw-popup-rule]' )
				.forEach( ( rule ) => {
					rule.hidden =
						! location ||
						rule.dataset.cwPopupRule !== location.value;
				} );
		};

		document
			.querySelectorAll( 'input[name="cinderwell_popup_trigger_type"]' )
			.forEach( ( radio ) => {
				radio.addEventListener( 'change', updateTrigger );
			} );
		if ( location ) {
			location.addEventListener( 'change', updateLocation );
		}

		document
			.querySelectorAll( '.cw-popup-post-picker' )
			.forEach( ( picker ) => {
				const select = picker.querySelector( 'select' );
				const addButton = picker.querySelector( '.cw-popup-add-post' );
				const list = picker.querySelector( '.cw-popup-post-list' );
				if ( ! select || ! addButton || ! list ) {
					return;
				}

				addButton.addEventListener( 'click', () => {
					const postId = Number.parseInt( select.value, 10 );
					if (
						! postId ||
						list.querySelector( `[data-id="${ postId }"]` )
					) {
						return;
					}

					const item = document.createElement( 'li' );
					item.dataset.id = String( postId );
					const label = document.createElement( 'span' );
					label.textContent =
						select.options[ select.selectedIndex ].text;
					const remove = document.createElement( 'button' );
					remove.type = 'button';
					remove.className =
						'button-link-delete cw-popup-remove-post';
					remove.textContent = 'Remove';
					const input = document.createElement( 'input' );
					input.type = 'hidden';
					input.name = `${ picker.dataset.listName }[]`;
					input.value = String( postId );
					item.append( label, remove, input );
					list.append( item );
					select.value = '';
				} );

				list.addEventListener( 'click', ( event ) => {
					const remove = event.target.closest(
						'.cw-popup-remove-post'
					);
					if ( remove ) {
						remove.closest( 'li' ).remove();
					}
				} );
			} );

		updateTrigger();
		updateLocation();
	} );
} )();
