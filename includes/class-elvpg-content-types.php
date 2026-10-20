<?php
/**
 * Custom post type and taxonomies used by the blocks.
 *
 * @package ElvpgPostsGridFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the prefixed post type and its two taxonomies.
 *
 * Other code never uses the constants directly. It asks for the names through
 * post_type(), category_taxonomy() and tag_taxonomy(), so a site can switch the
 * blocks to core posts, categories and tags with three filters (see ADR-001):
 *
 *     add_filter( 'elvpg_post_type', fn() => 'post' );
 *     add_filter( 'elvpg_category_taxonomy', fn() => 'category' );
 *     add_filter( 'elvpg_tag_taxonomy', fn() => 'post_tag' );
 */
class Elvpg_Content_Types {

	const POST_TYPE    = 'elvpg_post';
	const TAX_CATEGORY = 'elvpg_category';
	const TAX_TAG      = 'elvpg_tag';

	/**
	 * Post type the blocks query.
	 *
	 * @return string
	 */
	public static function post_type() {
		return (string) apply_filters( 'elvpg_post_type', self::POST_TYPE );
	}

	/**
	 * Taxonomy used as "categories" in the filter.
	 *
	 * @return string
	 */
	public static function category_taxonomy() {
		return (string) apply_filters( 'elvpg_category_taxonomy', self::TAX_CATEGORY );
	}

	/**
	 * Taxonomy used as "tags" in the filter.
	 *
	 * @return string
	 */
	public static function tag_taxonomy() {
		return (string) apply_filters( 'elvpg_tag_taxonomy', self::TAX_TAG );
	}

	/**
	 * Registers the post type and taxonomies. Hooked to `init`, and also
	 * called directly on activation before seeding.
	 *
	 * Each one is registered only while its filtered name is still the
	 * plugin's own name. If a site switched to core types, the plugin
	 * does not register anything it will not use.
	 */
	public static function register() {
		if ( self::POST_TYPE === self::post_type() ) {
			self::register_post_type();
		}

		if ( self::TAX_CATEGORY === self::category_taxonomy() ) {
			self::register_category_taxonomy();
		}

		if ( self::TAX_TAG === self::tag_taxonomy() ) {
			self::register_tag_taxonomy();
		}
	}

	/**
	 * Registers `elvpg_post`.
	 */
	private static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Grid Posts', 'elvpg-posts-grid-filter' ),
					'singular_name' => __( 'Grid Post', 'elvpg-posts-grid-filter' ),
					'add_new_item'  => __( 'Add New Grid Post', 'elvpg-posts-grid-filter' ),
					'edit_item'     => __( 'Edit Grid Post', 'elvpg-posts-grid-filter' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-grid-view',
				// Required for the block editor and the REST API previews.
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'rewrite'      => array( 'slug' => 'grid-posts' ),
			)
		);
	}

	/**
	 * Registers `elvpg_category` (hierarchical, like core categories).
	 */
	private static function register_category_taxonomy() {
		register_taxonomy(
			self::TAX_CATEGORY,
			self::post_type(),
			array(
				'labels'            => array(
					'name'          => __( 'Grid Categories', 'elvpg-posts-grid-filter' ),
					'singular_name' => __( 'Grid Category', 'elvpg-posts-grid-filter' ),
				),
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'grid-category' ),
			)
		);
	}

	/**
	 * Registers `elvpg_tag` (flat, like core tags).
	 */
	private static function register_tag_taxonomy() {
		register_taxonomy(
			self::TAX_TAG,
			self::post_type(),
			array(
				'labels'            => array(
					'name'          => __( 'Grid Tags', 'elvpg-posts-grid-filter' ),
					'singular_name' => __( 'Grid Tag', 'elvpg-posts-grid-filter' ),
				),
				'hierarchical'      => false,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'grid-tag' ),
			)
		);
	}
}
