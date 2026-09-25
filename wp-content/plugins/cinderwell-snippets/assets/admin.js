( function () {
	'use strict';

	function initialize() {
		const config = window.cinderwellSnippetsAdmin || {};
		const code = document.getElementById( 'cw-snippet-code' );
		const typeInputs = Array.from( document.querySelectorAll( '[data-cw-snippet-types] input' ) );
		const location = document.getElementById( 'cw-snippet-location' );
		const queryConditions = document.querySelector( '[data-cw-query-conditions]' );
		const shortcode = document.querySelector( '[data-cw-shortcode]' );
		const codeHelp = document.querySelector( '[data-cw-code-help]' );
		let editor = null;

		function selectedType() {
			const input = typeInputs.find( ( candidate ) => candidate.checked );
			return input ? input.value : 'php';
		}

		function isStarterCode( value ) {
			return ! value.trim() || Object.values( config.starterCode || {} ).includes( value );
		}

		function initializeEditor( replaceStarter = false ) {
			if ( ! code || ! window.wp || ! wp.codeEditor || ! config.editorSettings ) return;
			let currentCode = code.value;
			if ( editor && editor.codemirror ) {
				currentCode = editor.codemirror.getValue();
				editor.codemirror.toTextArea();
				editor = null;
			}
			if ( replaceStarter && config.isNew && isStarterCode( currentCode ) ) {
				code.value = ( config.starterCode || {} )[ selectedType() ] || '';
			}
			const settings = config.editorSettings[ selectedType() ];
			if ( settings ) editor = wp.codeEditor.initialize( code, settings );
			if ( codeHelp && config.strings && config.strings.codeHelp ) {
				codeHelp.textContent = config.strings.codeHelp[ selectedType() ] || '';
			}
		}

		function populateLocations( keepCurrent = true ) {
		if ( ! location ) return;
		const locations = ( config.locations || {} )[ selectedType() ] || {};
		const previous = keepCurrent ? ( location.value || location.dataset.current ) : '';
		location.innerHTML = '';
		Object.entries( locations ).forEach( ( [ value, label ] ) => {
			const option = document.createElement( 'option' );
			option.value = value;
			option.textContent = label;
			location.appendChild( option );
		} );
		location.value = Object.prototype.hasOwnProperty.call( locations, previous ) ? previous : Object.keys( locations )[ 0 ];
		location.dataset.current = location.value;
		updateConditionalFields();
		}

		function updateConditionalFields() {
		if ( ! location ) return;
		if ( queryConditions ) queryConditions.hidden = ! ( config.queryLocations || [] ).includes( location.value );
		if ( shortcode ) shortcode.hidden = location.value !== 'shortcode';
		}

		typeInputs.forEach( ( input ) => {
		input.addEventListener( 'change', () => {
			populateLocations( false );
			initializeEditor( true );
		} );
		} );
		if ( location ) location.addEventListener( 'change', updateConditionalFields );

		document.querySelectorAll( '[data-cw-check-all]' ).forEach( ( control ) => {
		control.addEventListener( 'change', () => {
			document.querySelectorAll( 'input[name="snippet_ids[]"]' ).forEach( ( checkbox ) => { checkbox.checked = control.checked; } );
		} );
		} );

		populateLocations();
		initializeEditor();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initialize );
	} else {
		initialize();
	}
}() );
