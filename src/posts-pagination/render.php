<?php
/**
 * Front-end markup of the Posts Pagination block.
 *
 * WordPress renders this inner block before the parent grid, so it cannot use
 * the grid's query object. It calls the shared query service with the same
 * "posts per page" (from block context) and gets the same cached WP_Query
 * that the grid uses (ADR-004).
 *
 * @package ElvpgPostsGridFilter
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (empty).
 * @var WP_Block $block      Block instance, with the context from the grid.
 */

defined( 'ABSPATH' ) || exit;

$elvpg_per_page = Elvpg_Query::clamp_per_page( $block->context['elvpg/postsPerPage'] ?? Elvpg_Query::DEFAULT_PER_PAGE );
$elvpg_state    = Elvpg_Query::current_state();
$elvpg_query    = Elvpg_Query::get_posts( $elvpg_per_page, $elvpg_state );
$elvpg_total    = (int) $elvpg_query->max_num_pages;
$elvpg_current  = min( $elvpg_state['page'], max( 1, $elvpg_total ) );
$elvpg_items    = Elvpg_Query::pagination_items( $elvpg_current, $elvpg_total );

if ( empty( $elvpg_items ) ) {
	return; // One page or no posts: nothing to paginate.
}

/**
 * URL of a page. Keeps the current filters, changes only the page number.
 * Page 1 has no page parameter, so it has one clean URL.
 *
 * @param int $page Page number.
 * @return string
 */
$elvpg_page_url = static function ( $page ) {
	return 1 === $page
		? remove_query_arg( Elvpg_Query::VAR_PAGE )
		: add_query_arg( Elvpg_Query::VAR_PAGE, $page );
};

$elvpg_wrapper = get_block_wrapper_attributes(
	array(
		'class'      => 'elvpg-pagination',
		'aria-label' => __( 'Posts pagination', 'elvpg-posts-grid-filter' ),
	)
);
?>
<nav <?php echo $elvpg_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<ul class="elvpg-pagination__list">
		<?php if ( $elvpg_current > 1 ) : ?>
			<li>
				<a class="elvpg-pagination__link elvpg-pagination__prev" href="<?php echo esc_url( $elvpg_page_url( $elvpg_current - 1 ) ); ?>" rel="prev">
					<?php esc_html_e( 'Previous', 'elvpg-posts-grid-filter' ); ?>
				</a>
			</li>
		<?php endif; ?>

		<?php foreach ( $elvpg_items as $elvpg_item ) : ?>
			<?php if ( null === $elvpg_item ) : ?>
				<li class="elvpg-pagination__gap" aria-hidden="true">…</li>
			<?php elseif ( $elvpg_item === $elvpg_current ) : ?>
				<li>
					<span class="elvpg-pagination__link is-current" aria-current="page">
						<span class="screen-reader-text"><?php esc_html_e( 'Page', 'elvpg-posts-grid-filter' ); ?> </span><?php echo (int) $elvpg_item; ?>
					</span>
				</li>
			<?php else : ?>
				<li>
					<a class="elvpg-pagination__link" href="<?php echo esc_url( $elvpg_page_url( $elvpg_item ) ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Page', 'elvpg-posts-grid-filter' ); ?> </span><?php echo (int) $elvpg_item; ?>
					</a>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php if ( $elvpg_current < $elvpg_total ) : ?>
			<li>
				<a class="elvpg-pagination__link elvpg-pagination__next" href="<?php echo esc_url( $elvpg_page_url( $elvpg_current + 1 ) ); ?>" rel="next">
					<?php esc_html_e( 'Next', 'elvpg-posts-grid-filter' ); ?>
				</a>
			</li>
		<?php endif; ?>
	</ul>
</nav>
