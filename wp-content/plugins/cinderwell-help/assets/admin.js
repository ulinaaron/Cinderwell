( function () {
	'use strict';

	const root = document.querySelector( '[data-cw-help-root]' );
	const search = document.querySelector( '[data-cw-help-search]' );
	if ( ! root || ! search ) {
		return;
	}

	const audienceButtons = Array.from( root.querySelectorAll( '[data-cw-help-audience]' ) );
	const sectionButtons = Array.from( root.querySelectorAll( '[data-cw-help-section]' ) );
	const categoryGroups = Array.from( root.querySelectorAll( '[data-cw-help-categories]' ) );
	const topics = Array.from( root.querySelectorAll( '[data-cw-help-topic]' ) );
	const count = root.querySelector( '[data-cw-help-count]' );
	const empty = root.querySelector( '[data-cw-help-empty]' );
	let activeAudience = audienceButtons.find( ( button ) => button.classList.contains( 'is-active' ) )?.dataset.cwHelpAudience || 'user';
	let activeSection = 'all';

	const syntaxKeywords = new Set( [
		'add_action', 'add_filter', 'array', 'as', 'break', 'case', 'catch',
		'class', 'const', 'continue', 'default', 'do', 'else', 'elseif',
		'extends', 'final', 'finally', 'for', 'foreach', 'function', 'if',
		'implements', 'import', 'in', 'interface', 'let', 'new', 'private',
		'protected', 'public', 'return', 'static', 'switch', 'throw', 'trait',
		'try', 'use', 'var', 'while',
	] );
	const syntaxConstants = new Set( [ 'false', 'null', 'true' ] );
	const tokenPattern = /\/\*[\s\S]*?\*\/|\/\/[^\n]*|#[^\n]*|&quot;(?:\\.|(?!&quot;)[\s\S])*?&quot;|'(?:\\.|[^'\\])*'|"(?:\\.|[^"\\])*"|`(?:\\.|[^`\\])*`|\$[A-Za-z_][A-Za-z0-9_]*|\b(?:[A-Za-z_][A-Za-z0-9_]*|\d+(?:\.\d+)?)\b|(?:=&gt;|-&gt;|::|===|!==|==|!=|&amp;&amp;|\|\||[{}()[\],.;:+*\/%!?&lt;&gt;=-])/g;

	const detectLanguage = ( code ) => {
		const declared = Array.from( code.classList ).find( ( className ) => className.startsWith( 'language-' ) );
		if ( declared ) return declared.replace( 'language-', '' );
		const source = code.textContent.trim();
		if ( source.startsWith( '&lt;' ) || source.startsWith( '<' ) ) return 'html';
		if ( /\$[A-Za-z_]|\b(?:add_action|add_filter|register_block_type)\s*\(/.test( source ) ) return 'php';
		if ( /^(?:\/\*)?[\s\S]*\b(?:Theme Name|Template|Requires Plugins):/m.test( source ) ) return 'css';
		if ( /\b(?:const|let|import|export)\b|=>/.test( source ) ) return 'javascript';
		return 'code';
	};

	const tokenClass = ( token ) => {
		if ( token.startsWith( '/*' ) || token.startsWith( '//' ) || token.startsWith( '#' ) ) return 'comment';
		if ( /^(?:&quot;|'|"|`)/.test( token ) ) return 'string';
		if ( token.startsWith( '$' ) ) return 'variable';
		if ( syntaxKeywords.has( token ) ) return 'keyword';
		if ( syntaxConstants.has( token ) ) return 'constant';
		if ( /^\d/.test( token ) ) return 'number';
		if ( /^[A-Za-z_]/.test( token ) ) return 'name';
		return 'operator';
	};

	const highlightCode = ( code ) => {
		if ( code.dataset.cwHighlighted === 'true' ) return;
		const source = code.textContent;
		const fragment = document.createDocumentFragment();
		let cursor = 0;
		tokenPattern.lastIndex = 0;
		let match;
		while ( ( match = tokenPattern.exec( source ) ) ) {
			if ( match.index > cursor ) fragment.append( document.createTextNode( source.slice( cursor, match.index ) ) );
			const token = document.createElement( 'span' );
			token.className = `cw-syntax-token cw-syntax-token--${ tokenClass( match[ 0 ] ) }`;
			token.textContent = match[ 0 ];
			fragment.append( token );
			cursor = tokenPattern.lastIndex;
		}
		if ( cursor < source.length ) fragment.append( document.createTextNode( source.slice( cursor ) ) );
		code.replaceChildren( fragment );
		code.dataset.cwHighlighted = 'true';
		const language = detectLanguage( code );
		code.closest( 'pre' )?.setAttribute( 'data-language', language === 'javascript' ? 'JavaScript' : language.toUpperCase() );
	};

	root.querySelectorAll( '.cw-help-topic__body pre code' ).forEach( highlightCode );

	const update = () => {
		const query = search.value.trim().toLocaleLowerCase();
		let visible = 0;

		topics.forEach( ( topic ) => {
			const matchesAudience = topic.dataset.audience === activeAudience;
			const matchesSection = activeSection === 'all' || topic.dataset.section === activeSection;
			const matchesSearch = ! query || topic.dataset.search.includes( query );
			topic.hidden = ! ( matchesAudience && matchesSection && matchesSearch );
			visible += topic.hidden ? 0 : 1;
		} );

		count.textContent = visible === 1 ? '1 topic' : `${ visible } topics`;
		empty.hidden = visible !== 0;
	};

	const selectAudience = ( audience ) => {
		activeAudience = audience;
		activeSection = 'all';
		audienceButtons.forEach( ( button ) => {
			const active = button.dataset.cwHelpAudience === audience;
			button.classList.toggle( 'is-active', active );
			button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			button.setAttribute( 'aria-expanded', active ? 'true' : 'false' );
		} );
		categoryGroups.forEach( ( group ) => {
			group.hidden = group.dataset.cwHelpCategories !== audience;
		} );
		sectionButtons.forEach( ( button ) => {
			const active = button.dataset.cwHelpSectionAudience === audience && button.dataset.cwHelpSection === 'all';
			button.classList.toggle( 'is-active', active );
			button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );
		update();
	};

	audienceButtons.forEach( ( button ) => {
		button.addEventListener( 'click', () => selectAudience( button.dataset.cwHelpAudience ) );
	} );

	sectionButtons.forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			activeAudience = button.dataset.cwHelpSectionAudience;
			activeSection = button.dataset.cwHelpSection;
			sectionButtons.forEach( ( item ) => {
				const active = item === button && item.dataset.cwHelpSectionAudience === activeAudience;
				item.classList.toggle( 'is-active', active );
				item.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );
			update();
		} );
	} );

	search.addEventListener( 'input', update );
	update();

	if ( window.location.hash ) {
		const linkedTopic = document.querySelector( window.location.hash );
		if ( linkedTopic && linkedTopic.matches( 'details' ) ) {
			const topic = linkedTopic.closest( '[data-cw-help-topic]' );
			if ( topic && topic.dataset.audience !== activeAudience ) {
				selectAudience( topic.dataset.audience );
			}
			linkedTopic.open = true;
			linkedTopic.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	}
}() );
