<?php
/**
 * Runs when the plugin is deleted from Plugins → Delete.
 *
 * Removes only what the plugin created: content marked with `_elvpg_demo`
 * (demo posts, their images, the demo page), demo terms and the plugin's
 * option. Posts and terms that users created themselves stay (ADR-008).
 *
 * @package ElvpgPostsGridFilter
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-elvpg-content-types.php';

/**
 * Cleans up the current site.
 */
function elvpg_uninstall_site() {
	global $wpdb;

	// The plugin is not loaded during uninstall. Taxonomies must be registered
	// again, because wp_delete_term() refuses to work with an unknown taxonomy.
	Elvpg_Content_Types::register();

	// A direct meta lookup finds demo content of any post type, including
	// attachments, without depending on which post type is registered now.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One-time cleanup on uninstall.
	$post_ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", '_elvpg_demo' )
	);

	foreach ( array_map( 'intval', $post_ids ) as $post_id ) {
		if ( 'attachment' === get_post_type( $post_id ) ) {
			// Also deletes the image files.
			wp_delete_attachment( $post_id, true );
		} else {
			wp_delete_post( $post_id, true );
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One-time cleanup on uninstall.
	$term_ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = %s", '_elvpg_demo' )
	);

	foreach ( array_map( 'intval', $term_ids ) as $term_id ) {
		$term = get_term( $term_id );

		if ( $term instanceof WP_Term ) {
			wp_delete_term( $term_id, $term->taxonomy );
		}
	}

	delete_option( 'elvpg_demo_page_id' );
	delete_option( 'rewrite_rules' );
}

if ( is_multisite() ) {
	$elvpg_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $elvpg_site_ids as $elvpg_site_id ) {
		switch_to_blog( (int) $elvpg_site_id );
		elvpg_uninstall_site();
		restore_current_blog();
	}
} else {
	elvpg_uninstall_site();
}
