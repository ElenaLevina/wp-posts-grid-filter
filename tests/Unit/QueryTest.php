<?php
/**
 * Unit tests for the filter logic in Elvpg_Query.
 *
 * @package ElvpgPostsGridFilter
 */

use PHPUnit\Framework\TestCase;

/**
 * The core rule of the brief: OR inside one filter type, AND across types.
 */
class QueryTest extends TestCase {

	const CAT = 'elvpg_category';
	const TAG = 'elvpg_tag';

	/* ---------- build_tax_query ---------- */

	public function test_no_selection_adds_no_tax_query() {
		$this->assertSame( array(), Elvpg_Query::build_tax_query( array(), array(), self::CAT, self::TAG ) );
	}

	public function test_categories_only_use_one_in_clause() {
		$query = Elvpg_Query::build_tax_query( array( 3, 5 ), array(), self::CAT, self::TAG );

		$this->assertSame(
			array(
				'relation' => 'AND',
				array(
					'taxonomy' => self::CAT,
					'field'    => 'term_id',
					'terms'    => array( 3, 5 ),
					'operator' => 'IN',
				),
			),
			$query
		);
	}

	public function test_tags_only_use_one_in_clause() {
		$query = Elvpg_Query::build_tax_query( array(), array( 7 ), self::CAT, self::TAG );

		$this->assertCount( 2, $query ); // relation + one clause.
		$this->assertSame( self::TAG, $query[0]['taxonomy'] );
		$this->assertSame( array( 7 ), $query[0]['terms'] );
		$this->assertSame( 'IN', $query[0]['operator'] );
	}

	public function test_or_inside_a_type_and_across_types() {
		$query = Elvpg_Query::build_tax_query( array( 3, 5 ), array( 7, 9 ), self::CAT, self::TAG );

		$this->assertSame( 'AND', $query['relation'], 'Different filter types are combined with AND.' );
		$this->assertSame( 'IN', $query[0]['operator'], 'Categories are combined with OR.' );
		$this->assertSame( 'IN', $query[1]['operator'], 'Tags are combined with OR.' );
		$this->assertSame( array( 3, 5 ), $query[0]['terms'] );
		$this->assertSame( array( 7, 9 ), $query[1]['terms'] );
	}

	public function test_custom_taxonomy_names_are_used() {
		$query = Elvpg_Query::build_tax_query( array( 1 ), array( 2 ), 'category', 'post_tag' );

		$this->assertSame( 'category', $query[0]['taxonomy'] );
		$this->assertSame( 'post_tag', $query[1]['taxonomy'] );
	}

	/* ---------- parse_ids ---------- */

	public function test_parse_ids_reads_comma_separated_string() {
		$this->assertSame( array( 3, 5 ), Elvpg_Query::parse_ids( '5,3' ) );
	}

	public function test_parse_ids_reads_array_from_html_form() {
		$this->assertSame( array( 3, 5 ), Elvpg_Query::parse_ids( array( '5', '3', '3' ) ) );
	}

	/**
	 * @dataProvider invalid_ids
	 *
	 * @param mixed $raw Invalid input.
	 */
	public function test_parse_ids_drops_invalid_values( $raw ) {
		$this->assertSame( array(), Elvpg_Query::parse_ids( $raw ) );
	}

	public function invalid_ids() {
		return array(
			'null'           => array( null ),
			'empty string'   => array( '' ),
			'zero'           => array( '0' ),
			'negative'       => array( '-3' ),
			'text'           => array( 'abc' ),
			'mixed'          => array( '3abc' ),
			'decimal'        => array( '2.5' ),
			'sql'            => array( '1 OR 1=1' ),
			'nested array'   => array( array( array( 3 ) ) ),
			'boolean'        => array( true ),
			'only commas'    => array( ',,,' ),
		);
	}

	public function test_parse_ids_keeps_valid_values_among_invalid() {
		$this->assertSame( array( 4, 8 ), Elvpg_Query::parse_ids( '8, abc, -1, 4, ' ) );
	}

	/* ---------- parse_page / clamp_per_page ---------- */

	public function test_parse_page() {
		$this->assertSame( 2, Elvpg_Query::parse_page( '2' ) );
		$this->assertSame( 1, Elvpg_Query::parse_page( '0' ) );
		$this->assertSame( 1, Elvpg_Query::parse_page( '-4' ) );
		$this->assertSame( 1, Elvpg_Query::parse_page( 'abc' ) );
		$this->assertSame( 1, Elvpg_Query::parse_page( null ) );
		$this->assertSame( 1, Elvpg_Query::parse_page( array( '2' ) ) );
	}

	public function test_clamp_per_page() {
		$this->assertSame( 6, Elvpg_Query::clamp_per_page( 6 ) );
		$this->assertSame( 1, Elvpg_Query::clamp_per_page( 0 ) );
		$this->assertSame( 24, Elvpg_Query::clamp_per_page( 1000 ) );
		$this->assertSame( Elvpg_Query::DEFAULT_PER_PAGE, Elvpg_Query::clamp_per_page( 'abc' ) );
	}

	/* ---------- build_query_args ---------- */

	public function test_query_args_without_filters() {
		$args = Elvpg_Query::build_query_args( array(), 6, 'elvpg_post', self::CAT, self::TAG );

		$this->assertSame( 'elvpg_post', $args['post_type'] );
		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( 6, $args['posts_per_page'] );
		$this->assertSame( 1, $args['paged'] );
		$this->assertArrayNotHasKey( 'tax_query', $args, 'No selection shows every post.' );
	}

	public function test_query_args_with_filters_and_page() {
		$state = array(
			'categories' => array( 3 ),
			'tags'       => '7,9',
			'page'       => 2,
		);

		$args = Elvpg_Query::build_query_args( $state, 100, 'elvpg_post', self::CAT, self::TAG );

		$this->assertSame( 24, $args['posts_per_page'] );
		$this->assertSame( 2, $args['paged'] );
		$this->assertSame( array( 7, 9 ), $args['tax_query'][1]['terms'] );
	}

	/* ---------- pagination_items ---------- */

	/**
	 * @dataProvider pagination_cases
	 *
	 * @param int   $current  Current page.
	 * @param int   $total    Total pages.
	 * @param array $expected Expected items, null is a gap.
	 */
	public function test_pagination_items( $current, $total, $expected ) {
		$this->assertSame( $expected, Elvpg_Query::pagination_items( $current, $total ) );
	}

	public function pagination_cases() {
		return array(
			'no posts'                => array( 1, 0, array() ),
			'one page has no links'   => array( 1, 1, array() ),
			'short list shows all'    => array( 2, 7, array( 1, 2, 3, 4, 5, 6, 7 ) ),
			'long list, first page'   => array( 1, 10, array( 1, 2, null, 10 ) ),
			'long list, middle'       => array( 5, 10, array( 1, null, 4, 5, 6, null, 10 ) ),
			'long list, last page'    => array( 10, 10, array( 1, null, 9, 10 ) ),
			'one-page gap is filled'  => array( 3, 10, array( 1, 2, 3, 4, null, 10 ) ),
			'page beyond the end'     => array( 99, 10, array( 1, null, 9, 10 ) ),
		);
	}
}
