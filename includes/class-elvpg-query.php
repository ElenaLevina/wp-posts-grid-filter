<?php
/**
 * Shared query service for the grid and the pagination blocks.
 *
 * @package ElvpgPostsGridFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds and runs the posts query from the filter state in the URL.
 *
 * The grid and its pagination inner block both call get_posts() with the same
 * arguments. WordPress renders inner blocks before their parent, so the
 * pagination cannot receive the grid's WP_Query object. Instead, the first
 * call runs the query and the second one gets the same object from a static
 * per-request cache (ADR-004).
 *
 * The parse_* and build_* methods are pure functions: they do not call
 * WordPress, so unit tests can run them without a WordPress install (ADR-009).
 */
class Elvpg_Query {

	/** URL parameter with the selected category IDs, e.g. `?elvpg_cat=3,5`. */
	const VAR_CATEGORIES = 'elvpg_cat';

	/** URL parameter with the selected tag IDs. */
	const VAR_TAGS = 'elvpg_tag';

	/** URL parameter with the page number. Not `paged`, see ADR-004. */
	const VAR_PAGE = 'elvpg_page';

	const MIN_PER_PAGE     = 1;
	const MAX_PER_PAGE     = 24;
	const DEFAULT_PER_PAGE = 6;

	/**
	 * Query results for the current request, keyed by a hash of the query args.
	 *
	 * @var array<string, WP_Query>
	 */
	private static $cache = array();

	/**
	 * Runs the posts query for the current filter state, once per request.
	 *
	 * @param int        $per_page Posts per page from the block settings.
	 * @param array|null $state    Filter state. Defaults to the current URL.
	 * @return WP_Query
	 */
	public static function get_posts( $per_page, $state = null ) {
		if ( null === $state ) {
			$state = self::current_state();
		}

		$args = self::build_query_args(
			$state,
			$per_page,
			Elvpg_Content_Types::post_type(),
			Elvpg_Content_Types::category_taxonomy(),
			Elvpg_Content_Types::tag_taxonomy()
		);

		/**
		 * Filters the WP_Query arguments used by the Posts Grid.
		 *
		 * @param array $args  WP_Query arguments.
		 * @param array $state Filter state: categories, tags, page.
		 */
		$args = (array) apply_filters( 'elvpg_query_args', $args, $state );

		$key = md5( (string) wp_json_encode( $args ) );

		if ( ! isset( self::$cache[ $key ] ) ) {
			self::$cache[ $key ] = new WP_Query( $args );
		}

		return self::$cache[ $key ];
	}

	/**
	 * Reads the filter state from the URL.
	 *
	 * No nonce check: this is a public, read-only GET request, like a search
	 * form. Every value goes through parse_ids() / parse_page(), which accept
	 * only positive integers.
	 *
	 * @return array{categories: int[], tags: int[], page: int}
	 */
	public static function current_state() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$categories = isset( $_GET[ self::VAR_CATEGORIES ] ) ? wp_unslash( $_GET[ self::VAR_CATEGORIES ] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by parse_ids().
		$tags       = isset( $_GET[ self::VAR_TAGS ] ) ? wp_unslash( $_GET[ self::VAR_TAGS ] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by parse_ids().
		$page       = isset( $_GET[ self::VAR_PAGE ] ) ? wp_unslash( $_GET[ self::VAR_PAGE ] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by parse_page().
		// phpcs:enable

		return array(
			'categories' => self::parse_ids( $categories ),
			'tags'       => self::parse_ids( $tags ),
			'page'       => self::parse_page( $page ),
		);
	}

	/**
	 * Turns a URL value into a clean list of term IDs.
	 *
	 * Accepts both formats the front end can produce:
	 * - `elvpg_cat=3,5` — the URL the JavaScript filter builds;
	 * - `elvpg_cat[]=3&elvpg_cat[]=5` — what the plain HTML form sends without JavaScript.
	 *
	 * Anything that is not a positive whole number is dropped. The result is
	 * sorted and unique, so the same selection always gives the same cache key.
	 *
	 * @param mixed $raw Raw value from $_GET.
	 * @return int[]
	 */
	public static function parse_ids( $raw ) {
		if ( is_string( $raw ) ) {
			$raw = explode( ',', $raw );
		} elseif ( is_int( $raw ) ) {
			$raw = array( $raw );
		} elseif ( ! is_array( $raw ) ) {
			return array();
		}

		$ids = array();

		foreach ( $raw as $value ) {
			if ( is_int( $value ) ) {
				$value = (string) $value;
			}

			if ( ! is_string( $value ) ) {
				continue; // Nested arrays and other types are ignored.
			}

			$value = trim( $value );

			if ( '' !== $value && ctype_digit( $value ) && (int) $value > 0 ) {
				$ids[] = (int) $value;
			}
		}

		$ids = array_unique( $ids );
		sort( $ids );

		return $ids;
	}

	/**
	 * Turns a URL value into a page number. Anything invalid means page 1.
	 *
	 * @param mixed $raw Raw value from $_GET.
	 * @return int
	 */
	public static function parse_page( $raw ) {
		if ( is_int( $raw ) ) {
			return max( 1, $raw );
		}

		if ( is_string( $raw ) && ctype_digit( $raw ) ) {
			return max( 1, (int) $raw );
		}

		return 1;
	}

	/**
	 * Keeps "posts per page" inside a safe range, so a block attribute
	 * cannot ask the database for thousands of posts.
	 *
	 * @param mixed $per_page Value from the block attributes.
	 * @return int
	 */
	public static function clamp_per_page( $per_page ) {
		if ( ! is_numeric( $per_page ) ) {
			return self::DEFAULT_PER_PAGE;
		}

		return min( self::MAX_PER_PAGE, max( self::MIN_PER_PAGE, (int) $per_page ) );
	}

	/**
	 * Builds the tax_query: OR inside one filter type, AND across types (ADR-005).
	 *
	 * - `operator => IN` means "the post has at least one of these terms" (OR).
	 * - `relation => AND` means "both clauses must match" (AND).
	 * - A type with no selection adds no clause, so it does not limit the results.
	 *
	 * @param int[]  $categories   Selected category IDs.
	 * @param int[]  $tags         Selected tag IDs.
	 * @param string $category_tax Category taxonomy name.
	 * @param string $tag_tax      Tag taxonomy name.
	 * @return array Empty array when nothing is selected.
	 */
	public static function build_tax_query( array $categories, array $tags, $category_tax, $tag_tax ) {
		$clauses = array();

		if ( ! empty( $categories ) ) {
			$clauses[] = array(
				'taxonomy' => $category_tax,
				'field'    => 'term_id',
				'terms'    => array_values( $categories ),
				'operator' => 'IN',
			);
		}

		if ( ! empty( $tags ) ) {
			$clauses[] = array(
				'taxonomy' => $tag_tax,
				'field'    => 'term_id',
				'terms'    => array_values( $tags ),
				'operator' => 'IN',
			);
		}

		if ( empty( $clauses ) ) {
			return array();
		}

		return array_merge( array( 'relation' => 'AND' ), $clauses );
	}

	/**
	 * Which page numbers the pagination shows.
	 *
	 * Short lists show every page. Long lists show the first and last page,
	 * the current page with one neighbour on each side, and a gap (null)
	 * where pages are skipped: 1 … 4 5 6 … 10.
	 *
	 * @param int $current Current page.
	 * @param int $total   Total number of pages.
	 * @return array<int|null> Page numbers; null marks a gap.
	 */
	public static function pagination_items( $current, $total ) {
		$total   = max( 0, (int) $total );
		$current = min( max( 1, (int) $current ), max( 1, $total ) );

		if ( $total <= 7 ) {
			return $total > 1 ? range( 1, $total ) : array();
		}

		$pages = array_unique( array( 1, $current - 1, $current, $current + 1, $total ) );
		$pages = array_filter(
			$pages,
			static function ( $page ) use ( $total ) {
				return $page >= 1 && $page <= $total;
			}
		);
		sort( $pages );

		$items    = array();
		$previous = 0;

		foreach ( $pages as $page ) {
			if ( $page - $previous === 2 ) {
				$items[] = $page - 1; // A gap of one page: show the page itself instead of "…".
			} elseif ( $page - $previous > 2 ) {
				$items[] = null;
			}

			$items[]  = $page;
			$previous = $page;
		}

		return $items;
	}

	/**
	 * Builds the full WP_Query arguments.
	 *
	 * @param array  $state        Filter state from current_state().
	 * @param int    $per_page     Posts per page.
	 * @param string $post_type    Post type name.
	 * @param string $category_tax Category taxonomy name.
	 * @param string $tag_tax      Tag taxonomy name.
	 * @return array
	 */
	public static function build_query_args( array $state, $per_page, $post_type, $category_tax, $tag_tax ) {
		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => self::clamp_per_page( $per_page ),
			'paged'               => self::parse_page( isset( $state['page'] ) ? $state['page'] : 1 ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);

		$tax_query = self::build_tax_query(
			self::parse_ids( isset( $state['categories'] ) ? $state['categories'] : null ),
			self::parse_ids( isset( $state['tags'] ) ? $state['tags'] : null ),
			$category_tax,
			$tag_tax
		);

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Filtering by terms is the purpose of this block.
		}

		return $args;
	}
}
