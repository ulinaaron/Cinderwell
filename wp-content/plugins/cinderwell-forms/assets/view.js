( () => {
	'use strict';
	const value = ( form, key ) => {
		const controls = [ ...form.querySelectorAll( `[name="fields[${ CSS.escape( key ) }]"], [name="fields[${ CSS.escape( key ) }][]"]` ) ];
		if ( ! controls.length ) return '';
		if ( controls[ 0 ].type === 'checkbox' || controls[ 0 ].type === 'radio' ) return controls.filter( ( control ) => control.checked ).map( ( control ) => control.value );
		return controls[ 0 ].value;
	};
	const matches = ( rule, current ) => {
		const list = Array.isArray( current ) ? current : [ String( current || '' ) ];
		if ( rule.operator === 'is_not' ) return ! list.includes( String( rule.value ) );
		if ( rule.operator === 'contains' ) return Array.isArray( current ) ? list.includes( String( rule.value ) ) : String( current || '' ).includes( String( rule.value ) );
		if ( rule.operator === 'is' ) return list.includes( String( rule.value ) );
		if ( rule.operator === 'empty' ) return ! list.some( Boolean );
		if ( rule.operator === 'not_empty' ) return list.some( Boolean );
		return false;
	};
	document.querySelectorAll( '[data-cw-form]' ).forEach( ( wrapper ) => {
		wrapper.querySelector( '[data-cw-form-result]' )?.focus();
		const form = wrapper.querySelector( '[data-cw-form-element]' ); if ( ! form ) return;
		wrapper.classList.add( 'is-enhanced' );
		const status = wrapper.querySelector( '[data-cw-form-status]' );
		const update = ( announce = false ) => {
			wrapper.querySelectorAll( '[data-cw-conditions]' ).forEach( ( field ) => {
				let config; try { config = JSON.parse( field.dataset.cwConditions ); } catch ( error ) { return; }
				const results = config.rules.map( ( rule ) => matches( rule, value( form, rule.field ) ) );
				const visible = config.relation === 'any' ? results.includes( true ) : ! results.includes( false );
				const wasHidden = field.hidden;
				if ( ! visible && field.contains( document.activeElement ) ) {
					const controller = form.querySelector( `[name="fields[${ CSS.escape( config.rules[ 0 ].field ) }]"], [name="fields[${ CSS.escape( config.rules[ 0 ].field ) }][]"]` ); controller?.focus();
				}
				field.hidden = ! visible; field.querySelectorAll( 'input,select,textarea' ).forEach( ( control ) => { control.disabled = ! visible; } );
				if ( announce && wasHidden !== field.hidden && status ) status.textContent = field.hidden ? 'A conditional field was hidden.' : 'A conditional field is now available.';
			} );
		};
		form.addEventListener( 'change', () => update( true ) ); update( false );
	} );
} )();
