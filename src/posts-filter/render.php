<?php
/**
 * Front-end markup of the Posts Filter block.
 *
 * A real GET form, so filtering works without JavaScript (ADR-006). With
 * JavaScript, view.js applies every change at once through the
 * Interactivity Router and hides the submit button.
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

wp_interactivity_state( 'elvpg/posts', array( 'isLoading' => false ) );

$elvpg_wrapper = get_block_wrapper_attributes(
	array(
		'class'                       => 'elvpg-filter',
		'data-wp-interactive'         => 'elvpg/posts',
		'data-wp-context'             => wp_json_encode(
			array(
				'enhanced'     => false,
				'hasSelection' => $elvpg_has_selection,
			)
		),
		'data-wp-init'                => 'callbacks.enhance',
		'data-wp-watch'               => 'callbacks.syncFromUrl',
		'data-wp-class--is-enhanced'  => 'context.enhanced',
		'data-wp-on-window--popstate' => 'actions.onPopState',
	)
);
?>
<div <?php echo $elvpg_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<form class="elvpg-filter__form" method="get" action="<?php echo esc_url( $elvpg_action ); ?>" data-wp-on--change="actions.applyFilters" data-wp-on--submit="actions.submit">
		<?php foreach ( $elvpg_keep as $elvpg_key => $elvpg_value ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $elvpg_key ); ?>" value="<?php echo esc_attr( $elvpg_value ); ?>" />
		<?php endforeach; ?>

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
			?>
			<fieldset class="elvpg-filter__group">
				<legend class="elvpg-filter__legend"><?php echo esc_html( $elvpg_group['legend'] ); ?></legend>
				<ul class="elvpg-filter__options">
					<?php foreach ( $elvpg_terms as $elvpg_term ) : ?>
						<li>
							<label class="elvpg-filter__option">
								<input
									type="checkbox"
									name="<?php echo esc_attr( $elvpg_group['param'] ); ?>[]"
									value="<?php echo esc_attr( $elvpg_term->term_id ); ?>"
									<?php checked( in_array( (int) $elvpg_term->term_id, $elvpg_group['selected'], true ) ); ?>
								/>
								<span><?php echo esc_html( $elvpg_term->name ); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</fieldset>
		<?php endforeach; ?>

		<div class="elvpg-filter__actions">
			<?php // Hidden once JavaScript applies changes instantly (see style.scss). ?>
			<button type="submit" class="elvpg-filter__submit wp-element-button">
				<?php esc_html_e( 'Apply filters', 'elvpg-posts-grid-filter' ); ?>
			</button>
			<a
				class="elvpg-filter__clear"
				href="<?php echo esc_url( Elvpg_Blocks::clear_filters_url() ); ?>"
				data-wp-on--click="actions.clearAll"
				data-wp-bind--hidden="!context.hasSelection"
			>
				<?php esc_html_e( 'Clear filters', 'elvpg-posts-grid-filter' ); ?>
			</a>
		</div>
	</form>
</div>
