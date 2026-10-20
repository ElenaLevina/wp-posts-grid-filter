/**
 * JavaScript copy of Elvpg_Query::pagination_items(), for the editor preview.
 * The PHP version is the one covered by unit tests; keep both in sync.
 *
 * @param {number} current    Current page.
 * @param {number} total      Total number of pages.
 * @param {number} neighbours Pages shown on each side of the current page.
 * @return {Array<number|null>} Page numbers; null marks a gap.
 */
export default function paginationItems( current, total, neighbours = 1 ) {
	if ( total < 2 ) {
		return [];
	}
	if ( total <= 5 + 2 * neighbours ) {
		return Array.from( { length: total }, ( _, index ) => index + 1 );
	}

	const pages = new Set( [ 1, total ] );
	for (
		let page = current - neighbours;
		page <= current + neighbours;
		page++
	) {
		if ( page >= 1 && page <= total ) {
			pages.add( page );
		}
	}

	const items = [];
	let previous = 0;
	[ ...pages ]
		.sort( ( a, b ) => a - b )
		.forEach( ( page ) => {
			if ( page - previous === 2 && neighbours > 0 ) {
				items.push( page - 1 );
			} else if ( page - previous >= 2 ) {
				items.push( null );
			}
			items.push( page );
			previous = page;
		} );

	return items;
}
