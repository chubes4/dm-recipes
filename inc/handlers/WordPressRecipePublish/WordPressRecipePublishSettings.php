<?php
/**
 * WordPress Recipe Publish Handler Settings
 * 
 * Settings configuration for the WordPress Recipe Publish Handler.
 * Uses identical structure to Data Machine's WordPress publish handler.
 *
 * @package DM_Recipes\WordPressRecipePublish
 * @since 1.0.0
 */

namespace DM_Recipes\WordPressRecipePublish;

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WordPressRecipePublishSettings {

    /**
     * Get Settings Fields for Data Machine UI
     * 
     * Returns the settings fields identical to Data Machine WordPress publisher.
     *
     * @param array $current_config Current handler configuration
     * @return array Settings fields
     * @since 1.0.0
     */
    public static function get_fields( array $current_config = [] ) {
        $fields = [];
        
        // Add local WordPress fields (post type, status, author, date)
        $fields = array_merge( $fields, self::get_local_fields( $current_config ) );
        
        // Add dynamic taxonomy fields (categories, tags, custom taxonomies)
        $fields = array_merge( $fields, self::get_taxonomy_fields( $current_config ) );
        
        return $fields;
    }
    
    /**
     * Get Local WordPress Fields
     * 
     * Returns core WordPress publishing fields identical to Data Machine structure.
     *
     * @param array $current_config Current configuration
     * @return array Local fields
     * @since 1.0.0
     */
    private static function get_local_fields( array $current_config = [] ) {
        // Get WordPress post types
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        $post_type_options = [];
        foreach ( $post_types as $post_type ) {
            $post_type_options[ $post_type->name ] = $post_type->label;
        }
        
        // Get WordPress users for author selection
        $users = get_users( [ 'fields' => [ 'ID', 'display_name' ] ] );
        $user_options = [];
        foreach ( $users as $user ) {
            $user_options[ $user->ID ] = $user->display_name;
        }
        
        return [
            'post_type' => [
                'type' => 'select',
                'label' => __( 'Post Type', 'dm-recipes' ),
                'description' => __( 'The WordPress post type to create', 'dm-recipes' ),
                'options' => $post_type_options,
                'default' => 'post'
            ],
            'post_status' => [
                'type' => 'select',
                'label' => __( 'Post Status', 'dm-recipes' ),
                'description' => __( 'The publication status for new posts', 'dm-recipes' ),
                'options' => [
                    'draft' => __( 'Draft', 'dm-recipes' ),
                    'publish' => __( 'Published', 'dm-recipes' ),
                    'pending' => __( 'Pending Review', 'dm-recipes' ),
                    'private' => __( 'Private', 'dm-recipes' )
                ],
                'default' => 'draft'
            ],
            'post_author' => [
                'type' => 'select',
                'label' => __( 'Post Author', 'dm-recipes' ),
                'description' => __( 'The author for new posts', 'dm-recipes' ),
                'options' => $user_options,
                'default' => (string) get_current_user_id()
            ],
            'post_date_source' => [
                'type' => 'select',
                'label' => __( 'Post Date Source', 'dm-recipes' ),
                'description' => __( 'Source for the post publication date', 'dm-recipes' ),
                'options' => [
                    'current_date' => __( 'Current Date/Time', 'dm-recipes' ),
                    'source_date' => __( 'Source Date (if available)', 'dm-recipes' )
                ],
                'default' => 'current_date'
            ]
        ];
    }
    
    /**
     * Get Dynamic Taxonomy Fields
     * 
     * Returns taxonomy selection fields identical to Data Machine structure.
     *
     * @param array $current_config Current configuration
     * @return array Taxonomy fields
     * @since 1.0.0
     */
    private static function get_taxonomy_fields( array $current_config = [] ) {
        $fields = [];
        
        // Get all public taxonomies, excluding nav_menu
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        
        foreach ( $taxonomies as $taxonomy ) {
            // Skip nav_menu taxonomy like Data Machine does
            if ( $taxonomy->name === 'nav_menu' ) {
                continue;
            }
            
            $field_key = "taxonomy_{$taxonomy->name}_selection";
            
            // Get all terms for this taxonomy
            $terms = get_terms( [
                'taxonomy' => $taxonomy->name,
                'hide_empty' => false,
                'number' => 200
            ] );
            
            // Build options array
            $options = [
                'skip' => __( 'Skip', 'dm-recipes' ),
                'ai_decides' => __( 'AI Decides', 'dm-recipes' )
            ];
            
            // Add individual terms as options
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                foreach ( $terms as $term ) {
                    $options[ $term->term_id ] = $term->name;
                }
            }
            
            $field_config = [
                'type' => 'select',
                'label' => $taxonomy->label,
                'description' => sprintf( 
                    __( 'Select %s assignment', 'dm-recipes' ), 
                    strtolower( $taxonomy->label ) 
                ),
                'options' => $options,
                'default' => 'ai_decides'
            ];
            
            $fields[ $field_key ] = $field_config;
        }
        
        return $fields;
    }
    
    /**
     * Sanitize Settings
     * 
     * Sanitizes settings identical to Data Machine WordPress publisher.
     *
     * @param array $raw_settings Raw form settings
     * @return array Sanitized settings
     * @since 1.0.0
     */
    public static function sanitize( array $raw_settings ): array {
        $sanitized = [];
        
        // Sanitize local WordPress settings
        $sanitized = array_merge( $sanitized, self::sanitize_local_settings( $raw_settings ) );
        
        // Sanitize taxonomy selections
        $sanitized = array_merge( $sanitized, self::sanitize_taxonomy_selections( $raw_settings ) );
        
        return $sanitized;
    }
    
    /**
     * Sanitize Local WordPress Settings
     *
     * @param array $raw_settings Raw settings
     * @return array Sanitized local settings
     * @since 1.0.0
     */
    private static function sanitize_local_settings( array $raw_settings ): array {
        $sanitized = [];
        
        // Post type
        if ( isset( $raw_settings['post_type'] ) ) {
            $post_type = sanitize_key( $raw_settings['post_type'] );
            if ( post_type_exists( $post_type ) ) {
                $sanitized['post_type'] = $post_type;
            }
        }
        
        // Post status
        if ( isset( $raw_settings['post_status'] ) ) {
            $status = sanitize_key( $raw_settings['post_status'] );
            $valid_statuses = [ 'draft', 'publish', 'pending', 'private' ];
            if ( in_array( $status, $valid_statuses, true ) ) {
                $sanitized['post_status'] = $status;
            }
        }
        
        // Post author
        if ( isset( $raw_settings['post_author'] ) ) {
            $author_id = intval( $raw_settings['post_author'] );
            if ( get_user_by( 'id', $author_id ) ) {
                $sanitized['post_author'] = $author_id;
            }
        }
        
        // Post date source
        if ( isset( $raw_settings['post_date_source'] ) ) {
            $date_source = sanitize_key( $raw_settings['post_date_source'] );
            $valid_sources = [ 'current_date', 'source_date' ];
            if ( in_array( $date_source, $valid_sources, true ) ) {
                $sanitized['post_date_source'] = $date_source;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize Taxonomy Selections
     *
     * @param array $raw_settings Raw settings
     * @return array Sanitized taxonomy settings
     * @since 1.0.0
     */
    private static function sanitize_taxonomy_selections( array $raw_settings ): array {
        $sanitized = [];
        
        // Get all public taxonomies (excluding nav_menu)
        $taxonomies = get_taxonomies( [ 'public' => true ] );
        
        foreach ( $taxonomies as $taxonomy_name ) {
            // Skip nav_menu taxonomy
            if ( $taxonomy_name === 'nav_menu' ) {
                continue;
            }
            
            $field_key = "taxonomy_{$taxonomy_name}_selection";
            
            if ( isset( $raw_settings[ $field_key ] ) ) {
                $value = $raw_settings[ $field_key ];
                
                // Handle 'skip' and 'ai_decides' 
                if ( $value === 'skip' || $value === 'ai_decides' ) {
                    $sanitized[ $field_key ] = $value;
                } else {
                    // Handle numeric term ID selection
                    $term_id = intval( $value );
                    if ( $term_id > 0 && term_exists( $term_id, $taxonomy_name ) ) {
                        $sanitized[ $field_key ] = $term_id;
                    }
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Requires Authentication
     * 
     * Returns whether this handler requires authentication.
     * Local WordPress publishing does not require authentication.
     *
     * @param array $current_config Current configuration
     * @return bool False - no authentication required
     * @since 1.0.0
     */
    public static function requires_authentication( array $current_config = [] ): bool {
        return false;
    }
}