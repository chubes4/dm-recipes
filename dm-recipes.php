<?php
/**
 * Plugin Name: Data Machine Recipes
 * Plugin URI: https://github.com/chubes4/dm-recipes
 * Description: Extends Data Machine to publish recipes with Schema.org structured data via WordPress Recipe Publish Handler and Recipe Schema Gutenberg Block.
 * Version: 1.0.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dm-recipes
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Network: false
 *
 * @package DM_Recipes
 */

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'DM_RECIPES_VERSION', '1.0.0' );
define( 'DM_RECIPES_PLUGIN_FILE', __FILE__ );
define( 'DM_RECIPES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DM_RECIPES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * PSR-4 Autoloader for Handler Classes
 * 
 * Automatically loads DM_Recipes namespace classes from the inc/handlers directory.
 * Enables WordPressRecipePublish handler discovery by Data Machine's filter system.
 *
 * @since 1.0.0
 */
spl_autoload_register( function( $class_name ) {
    // Only autoload our classes
    if ( strpos( $class_name, 'DM_Recipes\\' ) !== 0 ) {
        return;
    }
    
    // Convert namespace to file path
    $relative_class = str_replace( 'DM_Recipes\\', '', $class_name );
    $relative_class = str_replace( '\\', '/', $relative_class );
    
    // Construct file path
    $file = DM_RECIPES_PLUGIN_DIR . 'inc/handlers/' . $relative_class . '.php';
    
    // Load the file if it exists
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

/**
 * Initialize Data Machine Recipe Extension
 * 
 * Loads text domain and includes all necessary handler and block files.
 * Called on plugins_loaded to ensure all WordPress core functionality is available.
 *
 * @since 1.0.0
 */
function dm_recipes_init() {
    // Load text domain for translations
    load_plugin_textdomain( 'dm-recipes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    
    // Include handler filter registrations
    require_once DM_RECIPES_PLUGIN_DIR . 'inc/handlers/WordPressRecipePublish/WordPressRecipePublishFilters.php';
    
    // Include recipe schema block
    require_once DM_RECIPES_PLUGIN_DIR . 'inc/blocks/recipe-schema/index.php';
}

/**
 * Plugin Activation Hook
 * 
 * Verifies that Data Machine plugin is active before allowing activation.
 * Flushes rewrite rules for any new endpoints or custom post types.
 *
 * @since 1.0.0
 */
function dm_recipes_activate() {
    // Check if Data Machine is active
    if ( ! is_plugin_active( 'data-machine/data-machine.php' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 
            __( 'Data Machine Recipes requires the Data Machine plugin to be installed and activated.', 'dm-recipes' ),
            __( 'Plugin Dependency Error', 'dm-recipes' ),
            array( 'back_link' => true )
        );
    }
    
    // Flush rewrite rules for any custom post types or endpoints
    flush_rewrite_rules();
}

/**
 * Plugin Deactivation Hook
 * 
 * Cleans up any temporary data and flushes rewrite rules on deactivation.
 *
 * @since 1.0.0
 */
function dm_recipes_deactivate() {
    // Clean up any temporary data or flush rewrite rules
    flush_rewrite_rules();
}

// Register hooks
register_activation_hook( __FILE__, 'dm_recipes_activate' );
register_deactivation_hook( __FILE__, 'dm_recipes_deactivate' );

// Initialize plugin
add_action( 'plugins_loaded', 'dm_recipes_init' );