import './facets.css';

const forms = new WeakSet();
const requests = new Map();

const setLoading = ( region, results, status, loading ) => {
	region.classList.toggle( 'is-loading', loading );
	results.setAttribute( 'aria-busy', loading ? 'true' : 'false' );
	if ( loading && status ) {
		status.textContent =
			status.closest( 'form' )?.dataset.cwFacetUpdating ||
			'Updating results…';
	}
};

const replaceResults = async ( form, url, push = true ) => {
	const id = form.dataset.cwFacetForm;
	const region = form.closest( '[data-cw-facet-region]' );
	const results = region?.querySelector( '[data-cw-facet-results]' );
	const status = form.querySelector( '[data-cw-facet-status]' );
	if ( ! region || region.dataset.cwFacetRegion !== id || ! results ) return;

	requests.get( id )?.abort();
	const controller = new AbortController();
	requests.set( id, controller );

	setLoading( region, results, status, true );

	try {
		const response = await fetch( url, {
			headers: { 'X-Cinderwell-Facets': '1' },
			signal: controller.signal,
		} );
		if ( ! response.ok ) throw new Error( 'Facet request failed.' );
		const documentCopy = new DOMParser().parseFromString(
			await response.text(),
			'text/html'
		);
		const replacement = documentCopy.querySelector(
			`[data-cw-facet-region="${ CSS.escape( id ) }"]`
		);
		if ( ! replacement )
			throw new Error( 'Facet region was not returned.' );

		const nextResults = replacement.querySelector(
			'[data-cw-facet-results]'
		);
		const nextForm = replacement.querySelector( '[data-cw-facet-form]' );
		const nextStatus = nextForm?.querySelector( '[data-cw-facet-status]' );
		const currentActions = form.querySelector(
			'.cinderwell-facets__actions'
		);
		const nextActions = nextForm?.querySelector(
			'.cinderwell-facets__actions'
		);
		if ( ! nextResults || ! nextStatus )
			throw new Error( 'Facet results were not returned.' );
		if ( requests.get( id ) !== controller ) return;

		results.replaceWith( nextResults );
		nextResults.setAttribute( 'aria-busy', 'false' );
		if ( currentActions && nextActions ) {
			currentActions.replaceWith( nextActions );
		}
		if ( status ) {
			status.textContent = nextStatus.textContent.trim();
		}
		region.classList.remove( 'is-loading' );
		if ( push )
			window.history.pushState( { cinderwellFacets: true }, '', url );
	} catch ( error ) {
		if ( error.name === 'AbortError' ) return;
		if ( requests.get( id ) === controller ) {
			setLoading( region, results, status, false );
			window.location.assign( url );
		}
	} finally {
		if ( requests.get( id ) === controller ) {
			requests.delete( id );
		}
	}
};

const requestUrl = ( form ) => {
	const url = new URL(
		form.action || window.location.href,
		window.location.href
	);
	url.search = new URLSearchParams( new FormData( form ) ).toString();
	return url.toString();
};

const initialize = ( root = document ) => {
	root.querySelectorAll(
		'[data-cw-facet-form][data-cw-facet-live="true"]'
	).forEach( ( form ) => {
		if ( forms.has( form ) ) return;
		form.classList.add( 'is-enhanced' );
		let timer;
		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			replaceResults( form, requestUrl( form ) );
		} );
		form.addEventListener( 'change', ( event ) => {
			if ( event.target.matches( 'input[type="search"]' ) ) return;
			replaceResults( form, requestUrl( form ) );
		} );
		const search = form.querySelector( 'input[type="search"]' );
		search?.addEventListener( 'input', () => {
			window.clearTimeout( timer );
			timer = window.setTimeout(
				() => replaceResults( form, requestUrl( form ) ),
				350
			);
		} );
		forms.add( form );
	} );
};

initialize();

window.addEventListener( 'popstate', () => {
	window.location.reload();
} );
