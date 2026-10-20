/**
 * Client-side navigation shared by the filter, the grid and the pagination.
 *
 * The URL is the only state (ADR-002). Every interaction builds a new URL and
 * asks the Interactivity Router to go there. The router fetches the
 * server-rendered page and replaces only the grid's router region, so the
 * markup of a card exists in one place: render.php.
 */
import { store } from '@wordpress/interactivity';

export const NAMESPACE = 'elvpg/posts';

export const PARAMS = {
	categories: 'elvpg_cat',
	tags: 'elvpg_tag',
	page: 'elvpg_page',
};

export const { state } = store( NAMESPACE, {
	state: {
		// True while the router is loading the next page. The grid shows it
		// with aria-busy and a dimmed style.
		isLoading: false,
		// The current URL. Updated after every navigation, including the back
		// and forward buttons, so the filter can keep its checkboxes in sync.
		url: typeof window === 'undefined' ? '' : window.location.href,
	},
} );

/**
 * Builds the URL for a filter selection. Keeps every other parameter of the
 * current URL (for example `page_id` with plain permalinks) and always starts
 * from page 1, because the old page number may not exist in the new results.
 *
 * @param {number[]} categories Selected category IDs.
 * @param {number[]} tags       Selected tag IDs.
 * @return {string} New URL.
 */
export function filterUrl( categories, tags ) {
	const url = new URL( window.location.href );

	// Remove both formats: `elvpg_cat=3,5` and `elvpg_cat[]=3` (from the no-JS form).
	Object.values( PARAMS ).forEach( ( name ) => {
		url.searchParams.delete( name );
		url.searchParams.delete( `${ name }[]` );
	} );

	if ( categories.length ) {
		url.searchParams.set( PARAMS.categories, categories.join( ',' ) );
	}
	if ( tags.length ) {
		url.searchParams.set( PARAMS.tags, tags.join( ',' ) );
	}

	return url.toString();
}

/**
 * Navigates with the Interactivity Router. The router module is loaded only
 * when it is first needed, so the initial page load stays small.
 *
 * Rapid changes are safe: the router ignores a response that arrives after a
 * newer navigation has started.
 *
 * @param {string} url Target URL.
 */
export function* navigateTo( url ) {
	state.isLoading = true;
	try {
		const { actions } = yield import( '@wordpress/interactivity-router' );
		yield actions.navigate( url );
	} finally {
		state.isLoading = false;
		state.url = window.location.href;
	}
}

/**
 * Loads a page in the background (on hover), so the click feels instant.
 *
 * @param {string} url Target URL.
 */
export function* prefetch( url ) {
	const { actions } = yield import( '@wordpress/interactivity-router' );
	yield actions.prefetch( url );
}

/**
 * Reads the selected term IDs for one parameter from a URL. Understands both
 * `elvpg_cat=3,5` and `elvpg_cat[]=3&elvpg_cat[]=5`.
 *
 * @param {string} href URL.
 * @param {string} name Parameter name.
 * @return {number[]} Term IDs.
 */
export function idsFromUrl( href, name ) {
	const params = new URL( href ).searchParams;
	return [ ...params.getAll( name ), ...params.getAll( `${ name }[]` ) ]
		.flatMap( ( value ) => value.split( ',' ) )
		.map( ( value ) => parseInt( value, 10 ) )
		.filter( ( id ) => id > 0 );
}

/**
 * Whether a click should be left to the browser: a new tab or window,
 * or a click that is not the main button.
 *
 * @param {MouseEvent} event Click event.
 * @return {boolean} True when the browser should handle the click.
 */
export function isModifiedClick( event ) {
	return (
		event.button !== 0 ||
		event.metaKey ||
		event.ctrlKey ||
		event.shiftKey ||
		event.altKey
	);
}
