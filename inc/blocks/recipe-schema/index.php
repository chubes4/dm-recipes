<?php
/**
 * Recipe Schema Block Initialization
 * 
 * Includes the Recipe Schema block registration and rendering functionality.
 * This file is loaded by the main plugin to initialize the block.
 *
 * @package DM_Recipes
 * @since 1.0.0
 */

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the block registration functions
require_once __DIR__ . '/recipe-schema.php';