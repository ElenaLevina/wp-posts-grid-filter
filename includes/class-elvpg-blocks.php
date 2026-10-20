<?php
/**
 * Registers the blocks from the build folder.
 *
 * @package ElvpgPostsGridFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block registration and editor settings.
 */
class Elvpg_Blocks {

	/**
	 * Registers every block that has a block.json in build/.
	 * Hooked to `init`.
	 */
	public static function register() {
		$block_files = glob( ELVPG_DIR . 'build/*/block.json' );

		if ( empty( $block_files ) ) {
			// Source code without the build, for example GitHub's "Source code (zip)".
			add_action( 'admin_notices', array( __CLASS__, 'missing_build_notice' ) );
			return;
		}

		foreach ( $block_files as $block_file ) {
			register_block_type( dirname( $block_file ) );
		}

		self::add_editor_settings();
	}

	/**
	 * Admin notice when build/ is missing. Without it the plugin activates and
	 * seeds the demo content, but the blocks show as "not supported" with no
	 * explanation.
	 */
	public static function missing_build_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
			esc_html__( 'ELVPG Posts Grid & Filter: the blocks are not built.', 'elvpg-posts-grid-filter' ),
			esc_html__( 'This copy has no build/ folder. Install elvpg-posts-grid-filter.zip from the release (not "Source code"), or run npm install && npm run build.', 'elvpg-posts-grid-filter' ),
			esc_url( 'https://github.com/ElenaLevina/wp-posts-grid-filter/releases/latest' ),
			esc_html__( 'Download the release', 'elvpg-posts-grid-filter' )
		);
	}

	/**
	 * Number of grids rendered in this request.
	 *
	 * @var int
	 */
	private static $grid_count = 0;

	/**
	 * Router region id for the next grid on the page: elvpg-grid-1, elvpg-grid-2…
	 * The router matches regions by id between the old and the new page, so the
	 * id must be stable across requests. The position of the block is.
	 *
	 * @return string
	 */
	public static function next_grid_region_id() {
		++self::$grid_count;

		return 'elvpg-grid-' . self::$grid_count;
	}

	/**
	 * Current URL without the filter and page parameters.
	 *
	 * @return string
	 */
	public static function clear_filters_url() {
		return remove_query_arg(
			array(
				Elvpg_Query::VAR_CATEGORIES,
				Elvpg_Query::VAR_TAGS,
				Elvpg_Query::VAR_PAGE,
			)
		);
	}

	/**
	 * Gives the editor scripts the post type and taxonomy names, because a
	 * site can change them with filters (ADR-001). The REST bases are the
	 * field names of the taxonomies on a post in the REST API.
	 *
	 * Every block has its own editor script and reads the settings when the
	 * script loads, so each script gets the same line. With only one of them,
	 * a script that loads earlier would use the default names.
	 */
	private static function add_editor_settings() {
		$settings = array(
			'postType'         => Elvpg_Content_Types::post_type(),
			'categoryTaxonomy' => Elvpg_Content_Types::category_taxonomy(),
			'tagTaxonomy'      => Elvpg_Content_Types::tag_taxonomy(),
			'categoryRestBase' => self::rest_base( Elvpg_Content_Types::category_taxonomy() ),
			'tagRestBase'      => self::rest_base( Elvpg_Content_Types::tag_taxonomy() ),
		);

		$script = 'window.elvpgSettings = ' . wp_json_encode( $settings ) . ';';

		foreach ( array( 'elvpg/posts-filter', 'elvpg/posts-grid', 'elvpg/posts-pagination' ) as $block_name ) {
			wp_add_inline_script( generate_block_asset_handle( $block_name, 'editorScript' ), $script, 'before' );
		}
	}

	/**
	 * REST field name of a taxonomy, e.g. `categories` for `category`.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	private static function rest_base( $taxonomy ) {
		$object = get_taxonomy( $taxonomy );

		return ( $object && ! empty( $object->rest_base ) ) ? $object->rest_base : $taxonomy;
	}
}
