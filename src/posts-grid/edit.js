/**
 * Editor view of the Posts Grid.
 *
 * The preview reads posts from the core data store (the REST API) and draws
 * the same card markup as render.php, with the same class names, so one
 * stylesheet serves both (ADR-003). Filtering is not applied in the editor:
 * the preview always shows the first page of all posts.
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	InnerBlocks,
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, RangeControl, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import { COLUMNS, POSTS_PER_PAGE, SETTINGS } from './constants';
import PostCard from './post-card';

const ALLOWED_BLOCKS = [ 'elvpg/posts-pagination' ];
// A new grid starts with pagination. It can be removed and added back.
const TEMPLATE = [ [ 'elvpg/posts-pagination' ] ];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { columns, postsPerPage } = attributes;

	const { posts, isLoading, termNames, media } = useSelect(
		( select ) => {
			const core = select( coreStore );
			const query = {
				per_page: postsPerPage,
				status: 'publish',
				orderby: 'date',
				order: 'desc',
			};
			const args = [ 'postType', SETTINGS.postType, query ];
			const records = core.getEntityRecords( ...args );

			// All terms of both taxonomies, to turn term IDs into names.
			const allTerms = [
				...( core.getEntityRecords(
					'taxonomy',
					SETTINGS.categoryTaxonomy,
					{ per_page: -1 }
				) ?? [] ),
				...( core.getEntityRecords( 'taxonomy', SETTINGS.tagTaxonomy, {
					per_page: -1,
				} ) ?? [] ),
			];

			const mediaById = {};
			( records ?? [] ).forEach( ( post ) => {
				if ( post.featured_media ) {
					mediaById[ post.featured_media ] = core.getMedia(
						post.featured_media,
						{ context: 'view' }
					);
				}
			} );

			return {
				posts: records,
				isLoading: ! core.hasFinishedResolution(
					'getEntityRecords',
					args
				),
				termNames: Object.fromEntries(
					allTerms.map( ( term ) => [ term.id, term.name ] )
				),
				media: mediaById,
			};
		},
		[ postsPerPage ]
	);

	const blockProps = useBlockProps( {
		className: 'elvpg-grid',
		style: { '--elvpg-columns': columns },
	} );

	// The "+" button is shown only when the pagination was removed.
	const hasPagination = useSelect(
		( select ) => select( blockEditorStore ).getBlockCount( clientId ) > 0,
		[ clientId ]
	);

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'elvpg-grid__inner' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			renderAppender: hasPagination
				? false
				: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'elvpg-posts-grid-filter' ) }>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Columns', 'elvpg-posts-grid-filter' ) }
						help={ __(
							'On small screens the grid uses fewer columns automatically.',
							'elvpg-posts-grid-filter'
						) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ COLUMNS.min }
						max={ COLUMNS.max }
					/>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Posts per page',
							'elvpg-posts-grid-filter'
						) }
						value={ postsPerPage }
						onChange={ ( value ) =>
							setAttributes( { postsPerPage: value } )
						}
						min={ POSTS_PER_PAGE.min }
						max={ POSTS_PER_PAGE.max }
					/>
					<p className="elvpg-grid__editor-note">
						{ __(
							'The Posts Filter block filters this grid on the published page. The editor always shows the first page of all posts.',
							'elvpg-posts-grid-filter'
						) }
					</p>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLoading && (
					<div className="elvpg-grid__loading">
						<Spinner />
					</div>
				) }

				{ ! isLoading && ! posts?.length && (
					<p className="elvpg-grid__empty">
						{ __( 'No posts found.', 'elvpg-posts-grid-filter' ) }
					</p>
				) }

				{ ! isLoading && posts?.length > 0 && (
					<>
						<p className="screen-reader-text">
							{ sprintf(
								/* translators: %d: number of posts in the preview. */
								_n(
									'%d post in the preview',
									'%d posts in the preview',
									posts.length,
									'elvpg-posts-grid-filter'
								),
								posts.length
							) }
						</p>
						<ul className="elvpg-grid__list">
							{ posts.map( ( post ) => (
								<PostCard
									key={ post.id }
									post={ post }
									image={ media[ post.featured_media ] }
									categories={ (
										post[ SETTINGS.categoryRestBase ] ?? []
									)
										.map( ( id ) => termNames[ id ] )
										.filter( Boolean ) }
									tags={ (
										post[ SETTINGS.tagRestBase ] ?? []
									)
										.map( ( id ) => termNames[ id ] )
										.filter( Boolean ) }
								/>
							) ) }
						</ul>
					</>
				) }

				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
