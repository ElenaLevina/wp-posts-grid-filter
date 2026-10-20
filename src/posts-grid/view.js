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

			// Move focus to the top of the new results, so keyboard and
			// screen reader users continue from there, not from the footer.
			const grid = document.querySelector(
				`[data-wp-router-region="${ regionId }"]`
			);
			if ( grid ) {
				grid.focus( { preventScroll: true } );
				const reduceMotion = window.matchMedia(
					'(prefers-reduced-motion: reduce)'
				).matches;
				grid.scrollIntoView( {
					block: 'start',
					behavior: reduceMotion ? 'auto' : 'smooth',
				} );
			}
		},

		*clearFilters( event ) {
			if ( isModifiedClick( event ) ) {
				return;
			}
			event.preventDefault();
			yield* navigateTo( getElement().ref.href );
		},

		*prefetchPage() {
			const { ref } = getElement();
			yield* prefetch( ref.href );
		},
	},
} );
