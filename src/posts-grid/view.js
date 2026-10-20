/**
 * Front-end behaviour of the grid and its pagination.
 *
 * Without JavaScript the page links are normal links and still work (ADR-006).
 * With JavaScript a click loads only the grid, without a full page reload.
 */
import { getContext, getElement, store } from '@wordpress/interactivity';

import {
	NAMESPACE,
	isModifiedClick,
	navigateTo,
	prefetch,
} from '../shared/navigation';

/**
 * Moves focus to the grid after the router has replaced it, so keyboard and
 * screen reader users continue from the new results. The grid has
 * tabindex="-1": it can take focus but is not in the Tab order.
 *
 * @param {string}  regionId Router region id of the grid.
 * @param {boolean} scroll   Whether to scroll the grid into view.
 */
function focusGrid( regionId, scroll ) {
	const grid = document.querySelector(
		`[data-wp-router-region="${ regionId }"]`
	);
	if ( ! grid ) {
		return;
	}
	grid.focus( { preventScroll: true } );
	if ( scroll ) {
		const reduceMotion = window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		).matches;
		grid.scrollIntoView( {
			block: 'start',
			behavior: reduceMotion ? 'auto' : 'smooth',
		} );
	}
}

store( NAMESPACE, {
	actions: {
		*goToPage( event ) {
			if ( isModifiedClick( event ) ) {
				return;
			}
			event.preventDefault();

			const { ref } = getElement();
			const { regionId } = getContext();

			yield* navigateTo( ref.href );
			// After a page change the user is at the bottom of the grid.
			focusGrid( regionId, true );
		},

		// Actions of the empty state: clear one group or all filters.
		*clearFilters( event ) {
			if ( isModifiedClick( event ) ) {
				return;
			}
			event.preventDefault();

			const { regionId } = getContext();

			yield* navigateTo( getElement().ref.href );
			// The clicked link is gone; keep focus inside the grid.
			focusGrid( regionId, false );
		},

		*prefetchPage() {
			const { ref } = getElement();
			yield* prefetch( ref.href );
		},
	},
} );
