<?php
namespace DM_Recipes\WordPressRecipePublish;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings configuration for WordPress Recipe Publish handler.
 * Manages form fields, validation, and configuration options.
 */
class WordPressRecipePublishSettings {

    /**
     * Get configuration fields for recipe handler settings.
     * 
     * @param array $current_config Current configuration values
     * @return array Field configuration array
     */
    public static function get_fields( array $current_config = [] ) {
        return array_merge(
            self::get_local_fields( $current_config ),
            self::get_taxonomy_fields( $current_config )
        );
    }
    
    /**
     * Get local WordPress settings fields (post type, status, author).
     * 
     * @param array $current_config Current configuration values
     * @return array Local field configuration
     */
    private static function get_local_fields( array $current_config = [] ) {
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        $post_type_options = [];
        foreach ( $post_types as $post_type ) {
            $post_type_options[ $post_type->name ] = $post_type->label;
        }
        
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
     * Get taxonomy selection fields for available WordPress taxonomies.
     * 
     * @param array $current_config Current configuration values
     * @return array Taxonomy field configuration
     */
    private static function get_taxonomy_fields( array $current_config = [] ) {
        $fields = [];
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        
        foreach ( $taxonomies as $taxonomy ) {
            if ( in_array( $taxonomy->name, ['nav_menu', 'post_format'], true ) ) {
                continue;
            }
            
            $field_key = "taxonomy_{$taxonomy->name}_selection";
            
            $terms = get_terms( [
                'taxonomy' => $taxonomy->name,
                'hide_empty' => false,
                'number' => 200
            ] );
            
            $options = [
                'skip' => __( 'Skip', 'dm-recipes' ),
                'ai_decides' => __( 'AI Decides', 'dm-recipes' )
            ];
            
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
     * Sanitize and validate settings input.
     * 
     * @param array $raw_settings Raw settings from form submission
     * @return array Sanitized and validated settings
     */
    public static function sanitize( array $raw_settings ): array {
        return array_merge(
            self::sanitize_local_settings( $raw_settings ),
            self::sanitize_taxonomy_selections( $raw_settings )
        );
    }
    
    /**
     * Sanitize local WordPress settings.
     * 
     * @param array $raw_settings Raw settings input
     * @return array Sanitized local settings
     */
    private static function sanitize_local_settings( array $raw_settings ): array {
        $sanitized = [];
        
        if ( isset( $raw_settings['post_type'] ) ) {
            $post_type = sanitize_key( $raw_settings['post_type'] );
            if ( post_type_exists( $post_type ) ) {
                $sanitized['post_type'] = $post_type;
            }
        }
        
        if ( isset( $raw_settings['post_status'] ) ) {
            $status = sanitize_key( $raw_settings['post_status'] );
            $valid_statuses = [ 'draft', 'publish', 'pending', 'private' ];
            if ( in_array( $status, $valid_statuses, true ) ) {
                $sanitized['post_status'] = $status;
            }
        }
        
        if ( isset( $raw_settings['post_author'] ) ) {
            $author_id = intval( $raw_settings['post_author'] );
            if ( get_user_by( 'id', $author_id ) ) {
                $sanitized['post_author'] = $author_id;
            }
        }
        
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
     * Sanitize taxonomy selection settings.
     * 
     * @param array $raw_settings Raw settings input
     * @return array Sanitized taxonomy selections
     */
    private static function sanitize_taxonomy_selections( array $raw_settings ): array {
        $sanitized = [];
        $taxonomies = get_taxonomies( [ 'public' => true ] );
        
        foreach ( $taxonomies as $taxonomy_name ) {
            if ( in_array( $taxonomy_name, ['nav_menu', 'post_format'], true ) ) {
                continue;
            }
            
            $field_key = "taxonomy_{$taxonomy_name}_selection";
            
            if ( isset( $raw_settings[ $field_key ] ) ) {
                $value = $raw_settings[ $field_key ];
                
                if ( $value === 'skip' || $value === 'ai_decides' ) {
                    $sanitized[ $field_key ] = $value;
                } else {
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
     * Check if handler requires external authentication.
     * 
     * @param array $current_config Current configuration values
     * @return bool Always returns false - no external auth required
     */
    public static function requires_authentication( array $current_config = [] ): bool {
        return false;
    }
}