<?php
/**
 * Bootstrap for unit tests. They test pure functions only, so WordPress is
 * not loaded: ABSPATH is defined just to pass the direct-access guard.
 *
 * @package ElvpgPostsGridFilter
 */

define( 'ABSPATH', __DIR__ . '/' );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/includes/class-elvpg-query.php';
