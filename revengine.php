<?php
/**
 * Plugin Name: RevEngine
 * Plugin URI: https://github.com/j-norwood-young/revengine-wordpress
 * Description: Data from the reader's perspective. A Daily Maverick initiative sponsored by the Google News Initiative.
 * Author: DailyMaverick, Jason Norwood-Young
 * Author URI: https://dailymaverick.co.za
 * Version: 0.4.2
 * WC requires at least: 5.8
 * Tested up to: 6.0
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'REVENGINE_WORDPRESS_VERSION', '0.4.2' );

function revengine_init() {
    $revengine_globals = [];
    require_once(plugin_basename('includes/revengine-admin.php' ) );
    require_once(plugin_basename('includes/revengine-shared.php' ) );
    $revengine_admin = new RevEngineAdmin($revengine_globals);
    // Modules - these should eventually autoload
    require_once(plugin_basename('modules/revengine-callback/revengine-callback.php' ) );
    $revengine_callback = new RevEngineCallback($revengine_globals);
    require_once(plugin_basename('modules/content-promoter/content-promoter.php' ) );
    $revengine_content_promoter = new ContentPromoter($revengine_globals);
    require_once(plugin_basename('modules/piano-composer/piano-composer.php' ) );
    $piano_composer = new PianoComposer($revengine_globals);
    require_once(plugin_basename('modules/revengine-tracker/revengine-tracker.php' ) );
    $revengine_tracker = new RevEngineTracker($revengine_globals);
    require_once(plugin_basename('modules/revengine-api/revengine-api.php' ) );
    $revengine_api = new RevEngineAPI($revengine_globals);
    require_once(plugin_basename('modules/revengine-sync/revengine-sync.php' ) );
    $revengine_sync = new RevEngineSync($revengine_globals);
    require_once(plugin_basename('modules/revengine-user_labels/revengine-user_labels.php' ) );
    $revengine_user_labels = new RevEngineUserLabels($revengine_globals);
    
}
add_action( 'init', 'revengine_init', 5 );

// Shortcodes
// function shortcodes($atts) {
	// require(plugin_basename("templates/debicheck-form-shortcode.php"));
// }

// add_shortcode( 'debicheck-form', 'shortcodes' );

// revengine_init();
