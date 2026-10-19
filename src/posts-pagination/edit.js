/**
 * Editor preview of the pagination.
 *
 * "Posts per page" comes from the parent grid through block context, so the
 * preview shows the real number of pages and updates when the grid setting
 * changes. The links do not navigate in the editor.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import { SETTINGS } from '../posts-grid/constants';

export default function Edit( { context } ) {
	const perPage = context[ 'elvpg/postsPerPage' ] ?? 6;

	const total = useSelect( ( select ) => {
		const ids = select( coreStore ).getEntityRecords(
			'postType',
			SETTINGS.postType,
			{ per_page: -1, status: 'publish', _fields: 'id' }
		);
		return ids?.length ?? 0;
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
			<ul className="elvpg-pagination__list">
				{ Array.from(
					{ length: pages },
					( _, index ) => index + 1
				).map( ( page ) => (
					<li key={ page }>
						<span
							className={
								page === 1
									? 'elvpg-pagination__link is-current'
									: 'elvpg-pagination__link'
							}
							aria-current={ page === 1 ? 'page' : undefined }
						>
							{ page }
						</span>
					</li>
				) ) }
				<li>
					<span className="elvpg-pagination__link elvpg-pagination__next">
						{ __( 'Next', 'elvpg-posts-grid-filter' ) }
					</span>
				</li>
			</ul>
			<p className="screen-reader-text">
				{ sprintf(
					/* translators: %d: number of pages. */
					__( 'Preview of %d pages.', 'elvpg-posts-grid-filter' ),
					pages
				) }
			</p>
		</nav>
	);
}
