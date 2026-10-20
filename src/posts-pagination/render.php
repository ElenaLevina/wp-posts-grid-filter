<?php
/**
 * Front-end markup of the Posts Pagination block.
 *
 * WordPress renders this inner block before the parent grid, so it cannot use
 * the grid's query object. It calls the shared query service with the same
 * "posts per page" (from block context) and gets the same cached WP_Query
 * that the grid uses (ADR-004).
 *
 * The page numbers are printed twice: a full list for wide containers and a
 * short one (first, current, last) for narrow ones. A CSS container query
 * shows one of them, so the pagination always fits one row (ADR-006).
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

if ( $elvpg_total < 2 || ! $elvpg_query->have_posts() ) {
	// One page or no posts: nothing to paginate.
	return;
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

// Client-side navigation and prefetch on hover (see posts-grid/view.js).
$elvpg_link_directives = 'data-wp-on--click="actions.goToPage" data-wp-on--mouseenter="actions.prefetchPage"';

$elvpg_layouts = array(
	'wide'   => Elvpg_Query::pagination_items( $elvpg_current, $elvpg_total, 1 ),
	'narrow' => Elvpg_Query::pagination_items( $elvpg_current, $elvpg_total, 0 ),
);

$elvpg_steps = array(
	'prev' => array(
		'label' => __( 'Previous', 'elvpg-posts-grid-filter' ),
		'page'  => $elvpg_current > 1 ? $elvpg_current - 1 : 0,
		'rel'   => 'prev',
		'path'  => 'M15 18l-6-6 6-6',
	),
	'next' => array(
		'label' => __( 'Next', 'elvpg-posts-grid-filter' ),
		'page'  => $elvpg_current < $elvpg_total ? $elvpg_current + 1 : 0,
		'rel'   => 'next',
		'path'  => 'M9 6l6 6-6 6',
	),
);

/**
 * Prints the Previous or Next control. On the first and the last page it is
 * an inactive placeholder, so the other buttons do not move between pages.
 * The placeholder is hidden from screen readers: a control that does nothing
 * would only be noise.
 *
 * @param array $step One item of $elvpg_steps.
 */
$elvpg_print_step = static function ( $step ) use ( $elvpg_page_url, $elvpg_link_directives ) {
	$icon  = '<svg class="elvpg-pagination__icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="' . esc_attr( $step['path'] ) . '"/></svg>';
	$label = '<span class="elvpg-pagination__step-label">' . esc_html( $step['label'] ) . '</span>';
	$inner = 'prev' === $step['rel'] ? $icon . $label : $label . $icon;

	if ( ! $step['page'] ) {
		printf(
			'<span class="elvpg-pagination__link elvpg-pagination__step is-%1$s is-disabled" aria-hidden="true">%2$s</span>',
			esc_attr( $step['rel'] ),
			$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts above.
		);
		return;
	}

	printf(
		'<a class="elvpg-pagination__link elvpg-pagination__step is-%1$s" href="%2$s" rel="%1$s" %3$s>%4$s</a>',
		esc_attr( $step['rel'] ),
		esc_url( $elvpg_page_url( $step['page'] ) ),
		$elvpg_link_directives, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string.
		$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts above.
	);
};

$elvpg_wrapper = get_block_wrapper_attributes(
	array(
		'class'      => 'elvpg-pagination',
		'aria-label' => __( 'Posts pagination', 'elvpg-posts-grid-filter' ),
	)
);
?>
<nav <?php echo $elvpg_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<div class="elvpg-pagination__row">
		<?php $elvpg_print_step( $elvpg_steps['prev'] ); ?>

		<?php foreach ( $elvpg_layouts as $elvpg_layout => $elvpg_items ) : ?>
			<ul class="elvpg-pagination__pages is-<?php echo esc_attr( $elvpg_layout ); ?>">
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
							<a class="elvpg-pagination__link" href="<?php echo esc_url( $elvpg_page_url( $elvpg_item ) ); ?>" <?php echo $elvpg_link_directives; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static string. ?>>
								<span class="screen-reader-text"><?php esc_html_e( 'Page', 'elvpg-posts-grid-filter' ); ?> </span><?php echo (int) $elvpg_item; ?>
							</a>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		<?php endforeach; ?>

		<?php $elvpg_print_step( $elvpg_steps['next'] ); ?>
	</div>
</nav>
