/**
 * Front-end behaviour of the filter.
 *
 * Every change builds a new URL and navigates with the Interactivity Router.
 * The grid reads the URL on the server, so the filter and the grid never
 * talk to each other directly: they can be anywhere on the page (ADR-002).
 *
 * The dropdowns follow the disclosure pattern: a button with aria-expanded
 * opens a panel with normal checkboxes. One dropdown is open at a time. It
 * closes on a click outside, on Escape (focus goes back to its button) and
 * when focus leaves it.
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
 * @param {HTMLElement} root Element that contains the checkboxes.
 * @param {string}      name Parameter name.
 * @return {number[]} Term IDs.
 */
const checkedIds = ( root, name ) =>
	[ ...root.querySelectorAll( `input[name="${ name }[]"]:checked` ) ].map(
		( input ) => parseInt( input.value, 10 )
	);

/**
 * Updates the badges and the "Clear all" link from the current selection.
 *
 * @param {Object}   context    Filter context.
 * @param {number[]} categories Selected category IDs.
 * @param {number[]} tags       Selected tag IDs.
 */
function updateCounts( context, categories, tags ) {
	if ( PARAMS.categories in context.counts ) {
		context.counts[ PARAMS.categories ] = categories.length;
	}
	if ( PARAMS.tags in context.counts ) {
		context.counts[ PARAMS.tags ] = tags.length;
	}
	context.hasSelection = categories.length > 0 || tags.length > 0;
}

/**
 * Closes every dropdown.
 *
 * @param {Object} context Filter context.
 */
function closeAll( context ) {
	Object.keys( context.open ).forEach( ( group ) => {
		context.open[ group ] = false;
	} );
}

/**
 * Group of the element that triggered a directive, from data-group.
 *
 * @param {HTMLElement} element Element inside a dropdown.
 * @return {string|undefined} Group parameter name.
 */
const groupOf = ( element ) => element.closest( '[data-group]' )?.dataset.group;

store( NAMESPACE, {
	actions: {
		*applyFilters() {
			const context = getContext();
			const form = getElement().ref;
			const categories = checkedIds( form, PARAMS.categories );
			const tags = checkedIds( form, PARAMS.tags );

			updateCounts( context, categories, tags );
			// The dropdown stays open, so the user can tick more options.
			yield* navigateTo( filterUrl( categories, tags ) );
		},

		submit( event ) {
			// With JavaScript the changes are already applied. Enter in the
			// form must not reload the page.
			event.preventDefault();
		},

		toggleDropdown() {
			const context = getContext();
			const group = groupOf( getElement().ref );
			const willOpen = ! context.open[ group ];

			closeAll( context );
			context.open[ group ] = willOpen;
		},

		closeOnOutsideClick( event ) {
			const context = getContext();
			const { ref } = getElement();

			if ( ! ref.contains( event.target ) ) {
				closeAll( context );
			}
		},

		closeOnEscape( event ) {
			if ( event.key !== 'Escape' ) {
				return;
			}
			const context = getContext();
			const group = Object.keys( context.open ).find(
				( key ) => context.open[ key ]
			);
			if ( ! group ) {
				return;
			}
			closeAll( context );
			getElement()
				.ref.querySelector( `[data-group="${ group }"] button` )
				?.focus();
		},

		closeOnFocusOut( event ) {
			const dropdown = getElement().ref;
			// Focus moved outside this dropdown (for example with Tab).
			if (
				event.relatedTarget &&
				! dropdown.contains( event.relatedTarget )
			) {
				getContext().open[ dropdown.dataset.group ] = false;
			}
		},

		*clearGroup() {
			const context = getContext();
			const { ref } = getElement();
			const group = groupOf( ref );
			const form = ref.closest( 'form' );

			form.querySelectorAll( `input[name="${ group }[]"]` ).forEach(
				( input ) => {
					input.checked = false;
				}
			);

			const categories = checkedIds( form, PARAMS.categories );
			const tags = checkedIds( form, PARAMS.tags );
			updateCounts( context, categories, tags );
			yield* navigateTo( filterUrl( categories, tags ) );
		},

		*clearAll( event ) {
			if ( isModifiedClick( event ) ) {
				return;
			}
			event.preventDefault();
			closeAll( getContext() );
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
		 * Keeps the checkboxes and badges in line with the URL. Runs on every
		 * change of state.url: after Back/Forward, and after "Clear filters"
		 * in the grid's empty state.
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

			updateCounts( context, categories, tags );
		},
	},
} );
