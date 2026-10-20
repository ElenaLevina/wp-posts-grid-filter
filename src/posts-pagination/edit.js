/**
 * Editor preview of the pagination.
 *
 * "Posts per page" comes from the parent grid through block context, so the
 * preview shows the real number of pages and updates when the grid setting
 * changes. It mirrors render.php: the same classes and the same two lists of
 * page numbers, so the container query works in the editor too.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import { SETTINGS } from '../posts-grid/constants';
import paginationItems from './items';

const CURRENT = 1;

function Icon( { path } ) {
	return (
		<svg
			className="elvpg-pagination__icon"
			viewBox="0 0 24 24"
			width="20"
			height="20"
			aria-hidden="true"
			focusable="false"
		>
			<path d={ path } />
		</svg>
	);
}

function Pages( { layout, items } ) {
	return (
		<ul className={ `elvpg-pagination__pages is-${ layout }` }>
			{ items.map( ( item, index ) =>
				item === null ? (
					<li
						key={ `gap-${ index }` }
						className="elvpg-pagination__gap"
						aria-hidden="true"
					>
						…
					</li>
				) : (
					<li key={ item }>
						<span
							className={
								item === CURRENT
									? 'elvpg-pagination__link is-current'
									: 'elvpg-pagination__link'
							}
						>
							{ item }
						</span>
					</li>
				)
			) }
		</ul>
	);
}

export default function Edit( { context } ) {
	const perPage = context[ 'elvpg/postsPerPage' ] ?? 6;

	// Only the total is needed: one record per page, and the count comes
	// from the X-WP-Total header of the same request.
	const total = useSelect( ( select ) => {
		const core = select( coreStore );
		const args = [
			'postType',
			SETTINGS.postType,
			{ per_page: 1, status: 'publish', _fields: 'id' },
		];
		core.getEntityRecords( ...args );
		return core.getEntityRecordsTotalItems( ...args ) ?? 0;
	}, [] );

	const pages = Math.ceil( total / perPage );
	const blockProps = useBlockProps( { className: 'elvpg-pagination' } );

	if ( pages <= 1 ) {
		return (
			<div { ...blockProps }>
				<p className="elvpg-pagination__note">
					{ __(
						'Pagination appears when there is more than one page of posts.',
						'elvpg-posts-grid-filter'
					) }
				</p>
			</div>
		);
	}

	return (
		<nav
			{ ...blockProps }
			aria-label={ __( 'Posts pagination', 'elvpg-posts-grid-filter' ) }
		>
			<div className="elvpg-pagination__row">
				<span className="elvpg-pagination__link elvpg-pagination__step is-prev is-disabled">
					<Icon path="M15 18l-6-6 6-6" />
					<span className="elvpg-pagination__step-label">
						{ __( 'Previous', 'elvpg-posts-grid-filter' ) }
					</span>
				</span>
				<Pages
					layout="wide"
					items={ paginationItems( CURRENT, pages, 1 ) }
				/>
				<Pages
					layout="narrow"
					items={ paginationItems( CURRENT, pages, 0 ) }
				/>
				<span className="elvpg-pagination__link elvpg-pagination__step is-next">
					<span className="elvpg-pagination__step-label">
						{ __( 'Next', 'elvpg-posts-grid-filter' ) }
					</span>
					<Icon path="M9 6l6 6-6 6" />
				</span>
			</div>
		</nav>
	);
}
