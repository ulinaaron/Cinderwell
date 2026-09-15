const selector = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled]):not([type="hidden"])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'[tabindex]:not([tabindex="-1"])',
].join( ',' );

const getFocusable = ( container ) =>
	Array.from( container.querySelectorAll( selector ) ).filter(
		( element ) => ! element.hidden && element.getClientRects().length > 0
	);

export const createFocusTrap = ( container ) => {
	const handleKeydown = ( event ) => {
		if ( event.key !== 'Tab' ) {
			return;
		}

		const focusable = getFocusable( container );
		if ( ! focusable.length ) {
			event.preventDefault();
			container.focus();
			return;
		}

		const first = focusable[ 0 ];
		const last = focusable[ focusable.length - 1 ];
		const activeElement = container.ownerDocument.activeElement;
		if (
			event.shiftKey &&
			( activeElement === first || activeElement === container )
		) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	};

	return {
		activate() {
			container.addEventListener( 'keydown', handleKeydown );
			const focusable = getFocusable( container );
			( focusable[ 0 ] || container ).focus();
		},
		deactivate() {
			container.removeEventListener( 'keydown', handleKeydown );
		},
	};
};
