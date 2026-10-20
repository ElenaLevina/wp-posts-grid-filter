/**
 * Front-end behaviour of the filter.
 *
 * Every change builds a new URL and navigates with the Interactivity Router.
 * The grid reads the URL on the server, so the filter and the grid never
 * talk to each other directly: they can be anywhere on the page (ADR-002).
 */
import { getContext, getElement, store } from '@wordpress/interactivity';

import {
	NAMESPACE,
	PARAMS,
	filterUrl,
	idsFromUrl,
	isModifiedClick,
	navigateTo,
	state,
} from '../shared/navigation';

/**
 * Checked values of one checkbox group.
 *
 * @param {HTMLFormElement} form Filter form.
 * @param {string}          name Parameter name.
 * @return {number[]} Term IDs.
 */
const checkedIds = ( form, name ) =>
	[ ...form.querySelectorAll( `input[name="${ name }[]"]:checked` ) ].map(
		( input ) => parseInt( input.value, 10 )
	);

store( NAMESPACE, {
	actions: {
		*applyFilters() {
			const context = getContext();
			const form = getElement().ref;
			const categories = checkedIds( form, PARAMS.categories );
			const tags = checkedIds( form, PARAMS.tags );

			context.hasSelection = categories.length > 0 || tags.length > 0;
			yield* navigateTo( filterUrl( categories, tags ) );
		},

		submit( event ) {
			// With JavaScript the changes are already applied. Enter in the
			// form must not reload the page.
			event.preventDefault();
		},

		*clearAll( event ) {
			if ( isModifiedClick( event ) ) {
				return;
			}
			event.preventDefault();
			yield* navigateTo( filterUrl( [], [] ) );
		},

		onPopState() {
			// The router updates the grid on Back/Forward by itself.
			// Here we only record the new URL, so syncFromUrl runs.
			state.url = window.location.href;
		},
	},

	callbacks: {
		enhance() {
			getContext().enhanced = true;
		},

		/**
		 * Keeps the checkboxes in line with the URL. Runs on every change of
		 * state.url: after Back/Forward, and after "Clear filters" in the grid.
		 */
		syncFromUrl() {
			const context = getContext();
			const { ref } = getElement();
			const categories = idsFromUrl( state.url, PARAMS.categories );
			const tags = idsFromUrl( state.url, PARAMS.tags );

			ref.querySelectorAll( 'input[type="checkbox"]' ).forEach(
				( input ) => {
					const list = input.name.startsWith( PARAMS.categories )
						? categories
						: tags;
					input.checked = list.includes(
						parseInt( input.value, 10 )
					);
				}
			);

			context.hasSelection = categories.length > 0 || tags.length > 0;
		},
	},
} );
