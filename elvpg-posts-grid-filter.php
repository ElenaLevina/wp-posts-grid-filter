<?php
/**
 * Plugin Name:       ELVPG Posts Grid & Filter
 * Description:       Two Gutenberg blocks — a dynamic Posts Grid with inner-block pagination and a Posts Filter — that stay in sync through the Interactivity API. Seeds demo content on activation.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Elena Levin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       elvpg-posts-grid-filter
 *
 * @package ElvpgPostsGridFilter
 */

defined( 'ABSPATH' ) || exit;

define( 'ELVPG_VERSION', '1.0.0' );
define( 'ELVPG_FILE', __FILE__ );
define( 'ELVPG_DIR', plugin_dir_path( __FILE__ ) );

require_once ELVPG_DIR . 'includes/class-elvpg-content-types.php';
require_once ELVPG_DIR . 'includes/class-elvpg-seeder.php';

add_action( 'init', array( 'Elvpg_Content_Types', 'register' ) );

// Multisite: seed sites created after network activation. Priority 900 runs
// after core has created the new site's tables and default content.
add_action( 'wp_initialize_site', array( 'Elvpg_Seeder', 'on_new_site' ), 900 );

register_activation_hook( __FILE__, array( 'Elvpg_Seeder', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Elvpg_Seeder', 'deactivate' ) );
