<?php
/**
 * Front-end markup of the Posts Filter block.
 *
 * A "Filter" label and one dropdown per group (Categories, Tags). Each
 * dropdown is a disclosure button that opens a panel with checkboxes.
 *
 * It is a real GET form, so filtering works without JavaScript (ADR-006):
 * then the panels are simply shown open, with an "Apply filters" button.
 * With JavaScript, view.js turns the panels into dropdowns and applies every
 * change at once through the Interactivity Router.
 *
 * @package ElvpgPostsGridFilter
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (empty).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$elvpg_state  = Elvpg_Query::current_state();
$elvpg_groups = array();
$elvpg_uid    = wp_unique_id( 'elvpg-filter-' );

if ( ! empty( $attributes['showCategories'] ) ) {
	$elvpg_groups[] = array(
		'legend'   => __( 'Categories', 'elvpg-posts-grid-filter' ),
		'taxonomy' => Elvpg_Content_Types::category_taxonomy(),
		'param'    => Elvpg_Query::VAR_CATEGORIES,
		'selected' => $elvpg_state['categories'],
	);
}

if ( ! empty( $attributes['showTags'] ) ) {
	$elvpg_groups[] = array(
		'legend'   => __( 'Tags', 'elvpg-posts-grid-filter' ),
		'taxonomy' => Elvpg_Content_Types::tag_taxonomy(),
		'param'    => Elvpg_Query::VAR_TAGS,
		'selected' => $elvpg_state['tags'],
	);
}

$elvpg_has_selection = ! empty( $elvpg_state['categories'] ) || ! empty( $elvpg_state['tags'] );

// The form sends only its own fields. Other parameters of the current URL
// (for example `page_id` with plain permalinks) are kept as hidden fields,
// otherwise submitting the form without JavaScript would leave the page.
$elvpg_keep = array();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only GET form.
foreach ( $_GET as $elvpg_key => $elvpg_value ) {
	if ( in_array( $elvpg_key, array( Elvpg_Query::VAR_CATEGORIES, Elvpg_Query::VAR_TAGS, Elvpg_Query::VAR_PAGE ), true ) || ! is_string( $elvpg_value ) ) {
		continue;
	}
	$elvpg_keep[ sanitize_key( $elvpg_key ) ] = sanitize_text_field( wp_unslash( $elvpg_value ) );
}

$elvpg_action = strtok( Elvpg_Blocks::clear_filters_url(), '?' );

// Which dropdown is open, and how many terms are selected in each group.
$elvpg_open   = array();
$elvpg_counts = array();

foreach ( $elvpg_groups as $elvpg_group ) {
	$elvpg_open[ $elvpg_group['param'] ]   = false;
	$elvpg_counts[ $elvpg_group['param'] ] = count( $elvpg_group['selected'] );
}

wp_interactivity_state( 'elvpg/posts', array( 'isLoading' => false ) );

$elvpg_wrapper = get_block_wrapper_attributes(
	array(
		'class'                        => 'elvpg-filter',
		'data-wp-interactive'          => 'elvpg/posts',
		'data-wp-context'              => wp_json_encode(
			array(
				'enhanced'     => false,
				'hasSelection' => $elvpg_has_selection,
				'open'         => (object) $elvpg_open,
				'counts'       => (object) $elvpg_counts,
			)
		),
		'data-wp-init'                 => 'callbacks.enhance',
		'data-wp-watch'                => 'callbacks.syncFromUrl',
		'data-wp-class--is-enhanced'   => 'context.enhanced',
		'data-wp-on-window--popstate'  => 'actions.onPopState',
		'data-wp-on-document--click'   => 'actions.closeOnOutsideClick',
		'data-wp-on-document--keydown' => 'actions.closeOnEscape',
	)
);

$elvpg_filter_icon  = '<svg class="elvpg-filter__icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></svg>';
$elvpg_chevron_icon = '<svg class="elvpg-filter__chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6"/></svg>';
?>
<div <?php echo $elvpg_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<form class="elvpg-filter__form" method="get" action="<?php echo esc_url( $elvpg_action ); ?>" data-wp-on--change="actions.applyFilters" data-wp-on--submit="actions.submit">
		<?php foreach ( $elvpg_keep as $elvpg_key => $elvpg_value ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $elvpg_key ); ?>" value="<?php echo esc_attr( $elvpg_value ); ?>" />
		<?php endforeach; ?>

		<div class="elvpg-filter__bar">
			<p class="elvpg-filter__title">
				<?php echo $elvpg_filter_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
				<?php esc_html_e( 'Filter', 'elvpg-posts-grid-filter' ); ?>
			</p>

			<div class="elvpg-filter__dropdowns">
				<?php foreach ( $elvpg_groups as $elvpg_group ) : ?>
					<?php
					$elvpg_terms = get_terms(
						array(
							'taxonomy'   => $elvpg_group['taxonomy'],
							'hide_empty' => true,
							'orderby'    => 'name',
						)
					);

					if ( is_wp_error( $elvpg_terms ) || empty( $elvpg_terms ) ) {
						continue;
					}

					$elvpg_param    = $elvpg_group['param'];
					$elvpg_panel_id = $elvpg_uid . '-' . $elvpg_param;
					?>
					<div class="elvpg-filter__dropdown" data-group="<?php echo esc_attr( $elvpg_param ); ?>" data-wp-class--is-open="context.open.<?php echo esc_attr( $elvpg_param ); ?>" data-wp-on--focusout="actions.closeOnFocusOut">
						<?php /* Shown only with JavaScript (see style.scss). Without it the panel below is always visible. */ ?>
						<button
							type="button"
							class="elvpg-filter__toggle"
							aria-expanded="false"
							aria-controls="<?php echo esc_attr( $elvpg_panel_id ); ?>"
							data-wp-bind--aria-expanded="context.open.<?php echo esc_attr( $elvpg_param ); ?>"
							data-wp-on--click="actions.toggleDropdown"
						>
							<span><?php echo esc_html( $elvpg_group['legend'] ); ?></span>
							<span class="elvpg-filter__badge" data-wp-bind--hidden="!context.counts.<?php echo esc_attr( $elvpg_param ); ?>">
								<span data-wp-text="context.counts.<?php echo esc_attr( $elvpg_param ); ?>"><?php echo (int) count( $elvpg_group['selected'] ); ?></span>
								<span class="screen-reader-text"><?php esc_html_e( 'selected', 'elvpg-posts-grid-filter' ); ?></span>
							</span>
							<?php echo $elvpg_chevron_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?>
						</button>

						<fieldset class="elvpg-filter__panel" id="<?php echo esc_attr( $elvpg_panel_id ); ?>">
							<legend class="elvpg-filter__legend"><?php echo esc_html( $elvpg_group['legend'] ); ?></legend>
							<ul class="elvpg-filter__options">
								<?php foreach ( $elvpg_terms as $elvpg_term ) : ?>
									<li>
										<label class="elvpg-filter__option">
											<input
												type="checkbox"
												name="<?php echo esc_attr( $elvpg_param ); ?>[]"
												value="<?php echo esc_attr( $elvpg_term->term_id ); ?>"
												<?php checked( in_array( (int) $elvpg_term->term_id, $elvpg_group['selected'], true ) ); ?>
											/>
											<span><?php echo esc_html( $elvpg_term->name ); ?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
							<div class="elvpg-filter__panel-footer" data-wp-bind--hidden="!context.counts.<?php echo esc_attr( $elvpg_param ); ?>">
								<span class="elvpg-filter__selected">
									<?php
									printf(
										/* translators: %s: number of selected terms. */
										esc_html__( '%s selected', 'elvpg-posts-grid-filter' ),
										'<span data-wp-text="context.counts.' . esc_attr( $elvpg_param ) . '">' . (int) count( $elvpg_group['selected'] ) . '</span>'
									);
									?>
								</span>
								<button type="button" class="elvpg-filter__link" data-wp-on--click="actions.clearGroup">
									<?php esc_html_e( 'Clear', 'elvpg-posts-grid-filter' ); ?>
								</button>
							</div>
						</fieldset>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="elvpg-filter__actions">
				<?php /* Hidden once JavaScript applies changes instantly (see style.scss). */ ?>
				<button type="submit" class="elvpg-filter__submit wp-element-button">
					<?php esc_html_e( 'Apply filters', 'elvpg-posts-grid-filter' ); ?>
				</button>
				<a
					class="elvpg-filter__link elvpg-filter__clear"
					href="<?php echo esc_url( Elvpg_Blocks::clear_filters_url() ); ?>"
					data-wp-on--click="actions.clearAll"
					data-wp-bind--hidden="!context.hasSelection"
				>
					<?php esc_html_e( 'Clear all', 'elvpg-posts-grid-filter' ); ?>
				</a>
			</div>
		</div>
	</form>
</div>
