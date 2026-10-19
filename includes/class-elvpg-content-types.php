<?php
/**
 * Custom post type and taxonomies used by the blocks.
 *
 * @package ElvpgPostsGridFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the prefixed post type and its two taxonomies.
 */
class Elvpg_Content_Types {

	const POST_TYPE    = 'elvpg_post';
	const TAX_CATEGORY = 'elvpg_category';
	const TAX_TAG      = 'elvpg_tag';

	/**
	 * Registers the post type and taxonomies. Hooked to `init`, and also
	 * called directly on activation before seeding.
	 */
	public static function register() {
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
				'show_in_rest' => true, // Required for the block editor and REST previews.
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'rewrite'      => array( 'slug' => 'grid-posts' ),
			)
		);

		register_taxonomy(
			self::TAX_CATEGORY,
			self::POST_TYPE,
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

		register_taxonomy(
			self::TAX_TAG,
			self::POST_TYPE,
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
