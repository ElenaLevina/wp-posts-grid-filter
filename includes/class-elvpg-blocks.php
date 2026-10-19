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
			return; // Not built yet: run `npm run build`.
		}

		foreach ( $block_files as $block_file ) {
			register_block_type( dirname( $block_file ) );
		}

		self::add_editor_settings();
	}

	/**
	 * Gives the editor script the post type and taxonomy names, because a
	 * site can change them with filters (ADR-001). The REST bases are the
	 * field names of the taxonomies on a post in the REST API.
	 */
	private static function add_editor_settings() {
		$handle = generate_block_asset_handle( 'elvpg/posts-grid', 'editorScript' );

		$settings = array(
			'postType'         => Elvpg_Content_Types::post_type(),
			'categoryTaxonomy' => Elvpg_Content_Types::category_taxonomy(),
			'tagTaxonomy'      => Elvpg_Content_Types::tag_taxonomy(),
			'categoryRestBase' => self::rest_base( Elvpg_Content_Types::category_taxonomy() ),
			'tagRestBase'      => self::rest_base( Elvpg_Content_Types::tag_taxonomy() ),
		);

		wp_add_inline_script( $handle, 'window.elvpgSettings = ' . wp_json_encode( $settings ) . ';', 'before' );
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
