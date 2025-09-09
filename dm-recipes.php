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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
define( 'DM_RECIPES_VERSION', '1.0.0' );
define( 'DM_RECIPES_PLUGIN_FILE', __FILE__ );
define( 'DM_RECIPES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DM_RECIPES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// PSR-4 autoloader for DM_Recipes namespace
spl_autoload_register( function( $class_name ) {
    if ( strpos( $class_name, 'DM_Recipes\\' ) !== 0 ) {
        return;
    }
    
    $relative_class = str_replace( 'DM_Recipes\\', '', $class_name );
    $relative_class = str_replace( '\\', '/', $relative_class );
    $file = DM_RECIPES_PLUGIN_DIR . 'inc/handlers/' . $relative_class . '.php';
    
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

/**
 * Initialize DM-Recipes plugin functionality.
 * 
 * Loads translation textdomain, registers Data Machine handler filters,
 * and initializes Recipe Schema Gutenberg block. Called on WordPress 'init' hook.
 * 
 * @since 1.0.0
 */
function dm_recipes_init() {
    load_plugin_textdomain( 'dm-recipes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    require_once DM_RECIPES_PLUGIN_DIR . 'inc/handlers/WordPressRecipePublish/WordPressRecipePublishFilters.php';
    require_once DM_RECIPES_PLUGIN_DIR . 'inc/blocks/recipe-schema/recipe-schema.php';
    
    // Initialize Recipe Schema Gutenberg block
    dm_recipes_register_recipe_schema_block();
}

/**
 * Plugin activation callback.
 * 
 * Validates Data Machine plugin dependency is active before allowing activation.
 * Deactivates self and displays error if dependency not met. Flushes rewrite
 * rules to ensure proper URL structure.
 * 
 * @since 1.0.0
 */
function dm_recipes_activate() {
    if ( ! is_plugin_active( 'data-machine/data-machine.php' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 
            __( 'Data Machine Recipes requires the Data Machine plugin to be installed and activated.', 'dm-recipes' ),
            __( 'Plugin Dependency Error', 'dm-recipes' ),
            array( 'back_link' => true )
        );
    }
    flush_rewrite_rules();
}

/**
 * Plugin deactivation callback.
 * 
 * Performs cleanup operations including flushing rewrite rules
 * to remove any custom URL structures.
 * 
 * @since 1.0.0
 */
function dm_recipes_deactivate() {
    flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'dm_recipes_activate' );
register_deactivation_hook( __FILE__, 'dm_recipes_deactivate' );

add_action( 'init', 'dm_recipes_init' );