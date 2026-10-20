<?php
/**
 * "No posts found" message of the Posts Grid.
 *
 * @package ElvpgPostsGridFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the empty state: what was searched for, and how to get results.
 *
 * The text names the selected terms, so the AND rule is visible:
 * "No posts in Design or Performance are tagged AI Tools or PHP."
 * The actions are plain links, so they also work without JavaScript; with
 * JavaScript, posts-grid/view.js follows them without a page reload.
 */
class Elvpg_Empty_State {

	/**
	 * Returns the empty state markup.
	 *
	 * @param array $state Filter state from Elvpg_Query::current_state().
	 * @return string HTML.
	 */
	public static function render( array $state ) {
		$categories = self::term_names( $state['categories'], Elvpg_Content_Types::category_taxonomy() );
		$tags       = self::term_names( $state['tags'], Elvpg_Content_Types::tag_taxonomy() );
		$filtered   = $categories || $tags;

		$actions = array();

		if ( $categories && $tags ) {
			$actions[] = self::action( __( 'Clear categories', 'elvpg-posts-grid-filter' ), array( Elvpg_Query::VAR_CATEGORIES ), 'remove' );
			$actions[] = self::action( __( 'Clear tags', 'elvpg-posts-grid-filter' ), array( Elvpg_Query::VAR_TAGS ), 'remove' );
		}

		if ( $filtered ) {
			$actions[] = self::action( __( 'Clear all filters', 'elvpg-posts-grid-filter' ), array( Elvpg_Query::VAR_CATEGORIES, Elvpg_Query::VAR_TAGS ), 'primary' );
		} elseif ( $state['page'] > 1 ) {
			// No filters, but a page number beyond the last page.
			$actions[] = self::action( __( 'Go to the first page', 'elvpg-posts-grid-filter' ), array(), 'primary' );
		}

		ob_start();
		?>
		<div class="elvpg-empty">
			<span class="elvpg-empty__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="24" height="24" focusable="false"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5M8.5 11h5"/></svg>
			</span>
			<p class="elvpg-empty__title"><?php esc_html_e( 'No posts found', 'elvpg-posts-grid-filter' ); ?></p>
			<p class="elvpg-empty__text"><?php echo wp_kses( self::message( $categories, $tags, $state['page'] ), array( 'strong' => array() ) ); ?></p>
			<?php if ( $actions ) : ?>
				<div class="elvpg-empty__actions">
					<?php echo implode( '', $actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_url() and esc_html() in action(). ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The explanation sentence. Term names are escaped and wrapped in <strong>.
	 *
	 * @param string[] $categories Selected category names.
	 * @param string[] $tags       Selected tag names.
	 * @param int      $page       Current page.
	 * @return string
	 */
	private static function message( array $categories, array $tags, $page ) {
		if ( $categories && $tags ) {
			$text = sprintf(
				/* translators: 1: category names, 2: tag names. */
				__( 'No posts in %1$s are tagged %2$s. Try removing one of the filters.', 'elvpg-posts-grid-filter' ),
				self::join_or( $categories ),
				self::join_or( $tags )
			);
		} elseif ( $categories ) {
			/* translators: %s: category names. */
			$text = sprintf( __( 'There are no posts in %s yet.', 'elvpg-posts-grid-filter' ), self::join_or( $categories ) );
		} elseif ( $tags ) {
			/* translators: %s: tag names. */
			$text = sprintf( __( 'No posts are tagged %s yet.', 'elvpg-posts-grid-filter' ), self::join_or( $tags ) );
		} elseif ( $page > 1 ) {
			$text = __( 'This page is empty. The list has fewer pages.', 'elvpg-posts-grid-filter' );
		} else {
			$text = __( 'There are no posts yet.', 'elvpg-posts-grid-filter' );
		}

		return $text;
	}

	/**
	 * "A", "A or B", "A, B or C", with every name in <strong>.
	 *
	 * @param string[] $names Term names.
	 * @return string
	 */
	private static function join_or( array $names ) {
		$names = array_map(
			static function ( $name ) {
				return '<strong>' . esc_html( $name ) . '</strong>';
			},
			$names
		);

		$last = array_pop( $names );

		if ( ! $names ) {
			return $last;
		}

		/* translators: 1: comma-separated names, 2: the last name. */
		return sprintf( __( '%1$s or %2$s', 'elvpg-posts-grid-filter' ), implode( ', ', $names ), $last );
	}

	/**
	 * Names of the selected terms that exist in the taxonomy.
	 *
	 * @param int[]  $ids      Term IDs from the URL.
	 * @param string $taxonomy Taxonomy name.
	 * @return string[]
	 */
	private static function term_names( array $ids, $taxonomy ) {
		if ( ! $ids ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'include'    => $ids,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		return is_wp_error( $terms ) ? array() : wp_list_pluck( $terms, 'name' );
	}

	/**
	 * One action link: the current URL without the given parameters (and
	 * always without the page number, because the results change).
	 *
	 * @param string   $label  Link text.
	 * @param string[] $remove URL parameters to remove.
	 * @param string   $style  "primary" or "remove" (shows a ✕ icon).
	 * @return string
	 */
	private static function action( $label, array $remove, $style ) {
		$url  = remove_query_arg( array_merge( $remove, array( Elvpg_Query::VAR_PAGE ) ) );
		$icon = 'remove' === $style
			? '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>'
			: '';

		return sprintf(
			'<a class="elvpg-empty__action is-%1$s" href="%2$s" data-wp-on--click="actions.clearFilters">%3$s%4$s</a>',
			esc_attr( $style ),
			esc_url( $url ),
			$icon,
			esc_html( $label )
		);
	}
}
