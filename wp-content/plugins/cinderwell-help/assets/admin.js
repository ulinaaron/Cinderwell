( function () {
	'use strict';

	const root = document.querySelector( '[data-cw-help-root]' );
	const search = document.querySelector( '[data-cw-help-search]' );
	if ( ! root || ! search ) {
		return;
	}

	const buttons = Array.from( root.querySelectorAll( '[data-cw-help-section]' ) );
	const topics = Array.from( root.querySelectorAll( '[data-cw-help-topic]' ) );
	const count = root.querySelector( '[data-cw-help-count]' );
	const empty = root.querySelector( '[data-cw-help-empty]' );
	let activeSection = 'all';

	const update = () => {
		const query = search.value.trim().toLocaleLowerCase();
		let visible = 0;

		topics.forEach( ( topic ) => {
			const matchesSection = activeSection === 'all' || topic.dataset.section === activeSection;
			const matchesSearch = ! query || topic.dataset.search.includes( query );
			topic.hidden = ! ( matchesSection && matchesSearch );
			visible += topic.hidden ? 0 : 1;
		} );

		count.textContent = visible === 1 ? '1 topic' : `${ visible } topics`;
		empty.hidden = visible !== 0;
	};

	buttons.forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			activeSection = button.dataset.cwHelpSection;
			buttons.forEach( ( item ) => {
				const active = item === button;
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
			linkedTopic.open = true;
			linkedTopic.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	}
}() );
