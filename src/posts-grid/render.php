<?php
/**
 * Front-end markup of the Posts Grid block.
 *
 * Runs on every request (dynamic block), so the grid always shows current
 * posts for the filter state in the URL.
 *
 * @package ElvpgPostsGridFilter
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Saved inner blocks (the pagination), already rendered.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$elvpg_per_page = Elvpg_Query::clamp_per_page( $attributes['postsPerPage'] ?? Elvpg_Query::DEFAULT_PER_PAGE );
$elvpg_columns  = min( 4, max( 2, (int) ( $attributes['columns'] ?? 3 ) ) );
// Heading level of the post titles: h2–h6, h3 by default (see ADR-003).
$elvpg_title_tag = 'h' . min( 6, max( 2, (int) ( $attributes['titleLevel'] ?? 3 ) ) );
$elvpg_query     = Elvpg_Query::get_posts( $elvpg_per_page );

$elvpg_region_id = Elvpg_Blocks::next_grid_region_id();

// Server-side values of the client state, so directives render correctly
// before JavaScript loads.
wp_interactivity_state( 'elvpg/posts', array( 'isLoading' => false ) );

$elvpg_wrapper = get_block_wrapper_attributes(
	array(
		'class'                     => 'elvpg-grid',
		'style'                     => '--elvpg-columns:' . $elvpg_columns . ';',
		// The router replaces this element after each navigation. The id must be
		// the same on every page load, so it is based on the block's position.
		'data-wp-interactive'       => 'elvpg/posts',
		'data-wp-router-region'     => $elvpg_region_id,
		'data-wp-context'           => wp_json_encode( array( 'regionId' => $elvpg_region_id ) ),
		'data-wp-bind--aria-busy'   => 'state.isLoading',
		'data-wp-class--is-loading' => 'state.isLoading',
		// Focus target after a page change (see view.js).
		'tabindex'                  => '-1',
	)
);

$elvpg_found = (int) $elvpg_query->found_posts;
$elvpg_pages = (int) $elvpg_query->max_num_pages;
$elvpg_page  = (int) $elvpg_query->get( 'paged' );

if ( $elvpg_pages > 1 ) {
	$elvpg_status = sprintf(
		/* translators: 1: number of posts, 2: current page, 3: number of pages. */
		_n( '%1$d post found. Page %2$d of %3$d.', '%1$d posts found. Page %2$d of %3$d.', $elvpg_found, 'elvpg-posts-grid-filter' ),
		$elvpg_found,
		$elvpg_page,
		$elvpg_pages
	);
} else {
	$elvpg_status = sprintf(
		/* translators: %d: number of posts. */
		_n( '%d post found.', '%d posts found.', $elvpg_found, 'elvpg-posts-grid-filter' ),
		$elvpg_found
	);
}

$elvpg_category_tax = Elvpg_Content_Types::category_taxonomy();
$elvpg_tag_tax      = Elvpg_Content_Types::tag_taxonomy();
?>
<div <?php echo $elvpg_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<?php /* Screen readers announce the new count after each client-side update. */ ?>
	<p class="screen-reader-text" aria-live="polite" aria-atomic="true"><?php echo esc_html( $elvpg_status ); ?></p>

	<?php if ( $elvpg_query->have_posts() ) : ?>
		<ul class="elvpg-grid__list">
			<?php foreach ( $elvpg_query->posts as $elvpg_post ) : ?>
				<?php
				$elvpg_link       = get_permalink( $elvpg_post );
				$elvpg_categories = get_the_terms( $elvpg_post, $elvpg_category_tax );
				$elvpg_tags       = get_the_terms( $elvpg_post, $elvpg_tag_tax );
				?>
				<li class="elvpg-card">
					<article class="elvpg-card__inner">
						<?php if ( has_post_thumbnail( $elvpg_post ) ) : ?>
							<?php /* The title link is the main link. The image link is hidden from screen readers and the tab order, so it is not announced twice. */ ?>
							<a class="elvpg-card__media" href="<?php echo esc_url( $elvpg_link ); ?>" tabindex="-1" aria-hidden="true">
								<?php echo get_the_post_thumbnail( $elvpg_post, 'medium_large', array( 'loading' => 'lazy' ) ); ?>
							</a>
						<?php endif; ?>

						<div class="elvpg-card__body">
							<?php if ( is_array( $elvpg_categories ) ) : ?>
								<p class="elvpg-card__categories">
									<?php /* A folder icon marks categories, a # marks tags. The icon is decorative; screen readers hear "Categories:". */ ?>
									<svg class="elvpg-card__category-icon" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H9l2 2h8.5A1.5 1.5 0 0 1 21 8.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5z"/></svg>
									<span>
										<span class="screen-reader-text"><?php esc_html_e( 'Categories:', 'elvpg-posts-grid-filter' ); ?> </span>
										<?php echo esc_html( implode( ', ', wp_list_pluck( $elvpg_categories, 'name' ) ) ); ?>
									</span>
								</p>
							<?php endif; ?>

							<<?php echo esc_attr( $elvpg_title_tag ); ?> class="elvpg-card__title">
								<a href="<?php echo esc_url( $elvpg_link ); ?>"><?php echo esc_html( get_the_title( $elvpg_post ) ); ?></a>
							</<?php echo esc_attr( $elvpg_title_tag ); ?>>

							<p class="elvpg-card__excerpt"><?php echo esc_html( get_the_excerpt( $elvpg_post ) ); ?></p>

							<?php if ( is_array( $elvpg_tags ) ) : ?>
								<ul class="elvpg-card__tags" aria-label="<?php esc_attr_e( 'Tags', 'elvpg-posts-grid-filter' ); ?>">
									<?php foreach ( $elvpg_tags as $elvpg_tag ) : ?>
										<?php /* Shown as a hashtag of the slug, for example #ai-tools. */ ?>
										<li class="elvpg-card__tag">#<?php echo esc_html( $elvpg_tag->slug ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					</article>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<?php echo Elvpg_Empty_State::render( Elvpg_Query::current_state() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Elvpg_Empty_State. ?>
	<?php endif; ?>

	<?php if ( '' !== trim( $content ) ) : ?>
		<div class="elvpg-grid__inner">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered inner blocks. ?>
		</div>
	<?php endif; ?>
</div>
