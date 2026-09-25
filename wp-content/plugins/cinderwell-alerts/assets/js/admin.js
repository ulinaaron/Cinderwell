( () => {
	const initialize = () => {
		document.querySelectorAll( '[data-cw-alert-controls]' ).forEach( ( controls ) => {
			const audience = controls.querySelector( '#cw-alert-audience' );
			const updateAudience = () => {
				controls.querySelectorAll( '[data-cw-alert-audience-panel]' ).forEach( ( panel ) => {
					panel.hidden = ! audience || panel.dataset.cwAlertAudiencePanel !== audience.value;
				} );
			};
			audience?.addEventListener( 'change', updateAudience );
			updateAudience();

			const dismissible = controls.querySelector( '#cw-alert-dismissible' );
			const dismissalDays = controls.querySelector( '[data-cw-alert-dismiss-days]' );
			const updateDismissal = () => {
				if ( dismissalDays ) dismissalDays.hidden = ! dismissible?.checked;
			};
			dismissible?.addEventListener( 'change', updateDismissal );
			updateDismissal();
		} );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initialize, { once: true } );
	} else {
		initialize();
	}
} )();
