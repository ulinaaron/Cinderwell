import { createFocusTrap } from './focus-trap';

( function () {
	'use strict';

	const config = window.cinderwellPopupsConfig || { popups: [] };
	const oneYear = 365 * 24 * 60 * 60 * 1000;

	const controller = {
		popups: Array.isArray( config.popups ) ? config.popups : [],
		active: null,
		trigger: null,
		focusTrap: null,
		inertState: [],

		init() {
			this.container = document.getElementById(
				'cinderwell-popups-container'
			);
			if ( ! this.container ) {
				return;
			}

			document.addEventListener( 'click', ( event ) => {
				const close = event.target.closest(
					'[data-cinderwell-popup-close]'
				);
				if ( close && this.active ) {
					event.preventDefault();
					this.close();
					return;
				}

				const trigger = event.target.closest(
					'[data-cinderwell-popup]'
				);
				if ( trigger ) {
					event.preventDefault();
					this.open(
						Number.parseInt( trigger.dataset.cinderwellPopup, 10 ),
						trigger
					);
				}
			} );

			document.addEventListener( 'keydown', ( event ) => {
				if ( event.key === 'Escape' && this.active ) {
					event.preventDefault();
					this.close();
				}
			} );

			const automatic = this.popups.find(
				( popup ) => popup.auto && ! this.hasBeenShown( popup )
			);
			if ( automatic ) {
				this.open( automatic.id );
			}
		},

		open( popupId, trigger = null ) {
			const popup = this.popups.find(
				( item ) => Number( item.id ) === Number( popupId )
			);
			const modal = document.querySelector(
				`[data-cinderwell-modal="${ Number( popupId ) }"]`
			);
			if ( ! popup || ! modal || this.hasBeenShown( popup ) ) {
				return false;
			}

			if ( this.active ) {
				this.finishClose( false );
			}

			this.active = { popup, modal };
			this.trigger = trigger || modal.ownerDocument.activeElement;
			modal.hidden = false;
			document.body.classList.add( 'cinderwell-modal-open' );
			this.setPageInert( true );

			const dialog = modal.querySelector( '.cinderwell-modal__dialog' );
			this.focusTrap = createFocusTrap( dialog );
			window.requestAnimationFrame( () => {
				modal.classList.add( 'cinderwell-modal--open' );
				this.focusTrap.activate();
			} );

			this.markShown( popup );
			this.track( popup.id, 'view' );
			document.dispatchEvent(
				new window.CustomEvent( 'cinderwell:popup:open', {
					detail: { id: popup.id },
				} )
			);
			return true;
		},

		close() {
			if ( ! this.active ) {
				return;
			}

			const { popup, modal } = this.active;
			modal.classList.remove( 'cinderwell-modal--open' );
			this.track( popup.id, 'dismiss' );

			const reducedMotion = window.matchMedia(
				'(prefers-reduced-motion: reduce)'
			).matches;
			window.setTimeout(
				() => this.finishClose( true ),
				reducedMotion ? 0 : 200
			);
		},

		finishClose( restoreFocus ) {
			if ( ! this.active ) {
				return;
			}

			const { popup, modal } = this.active;
			if ( this.focusTrap ) {
				this.focusTrap.deactivate();
			}
			modal.hidden = true;
			modal.classList.remove( 'cinderwell-modal--open' );
			document.body.classList.remove( 'cinderwell-modal-open' );
			this.setPageInert( false );

			if (
				restoreFocus &&
				this.trigger &&
				document.contains( this.trigger ) &&
				typeof this.trigger.focus === 'function'
			) {
				this.trigger.focus();
			}

			document.dispatchEvent(
				new window.CustomEvent( 'cinderwell:popup:close', {
					detail: { id: popup.id },
				} )
			);
			this.active = null;
			this.trigger = null;
			this.focusTrap = null;
		},

		setPageInert( makeInert ) {
			if ( makeInert ) {
				this.inertState = Array.from( document.body.children )
					.filter(
						( element ) =>
							element !== this.container &&
							! element.contains( this.container )
					)
					.map( ( element ) => ( {
						element,
						inert: element.inert,
					} ) );
				this.inertState.forEach( ( item ) => {
					item.element.inert = true;
				} );
			} else {
				this.inertState.forEach( ( item ) => {
					item.element.inert = item.inert;
				} );
				this.inertState = [];
			}
		},

		hasBeenShown( popup ) {
			if ( popup.frequency === 'always' ) {
				return false;
			}
			try {
				const storage =
					popup.frequency === 'once_ever'
						? window.localStorage
						: window.sessionStorage;
				const value = Number.parseInt(
					storage.getItem( `cinderwell_popup_${ popup.id }` ),
					10
				);
				return (
					Number.isFinite( value ) &&
					( popup.frequency !== 'once_ever' ||
						Date.now() - value < oneYear )
				);
			} catch ( error ) {
				return false;
			}
		},

		markShown( popup ) {
			if ( popup.frequency === 'always' ) {
				return;
			}
			try {
				const storage =
					popup.frequency === 'once_ever'
						? window.localStorage
						: window.sessionStorage;
				storage.setItem(
					`cinderwell_popup_${ popup.id }`,
					String( Date.now() )
				);
			} catch ( error ) {
				// Storage may be unavailable in hardened privacy modes; the popup still works.
			}
		},

		track( popupId, event ) {
			if ( ! config.restUrl ) {
				return;
			}
			window
				.fetch( config.restUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					credentials: 'same-origin',
					keepalive: true,
					body: JSON.stringify( { popup_id: popupId, event } ),
				} )
				.catch( () => {} );
		},
	};

	document.addEventListener( 'DOMContentLoaded', () => controller.init() );
	window.CinderwellPopups = controller;
} )();
