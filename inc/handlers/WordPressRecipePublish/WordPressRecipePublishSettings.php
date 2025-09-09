<?php
namespace DM_Recipes\WordPressRecipePublish;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings configuration for WordPress Recipe Publish handler.
 * 
 * Provides Data Machine settings interface for recipe publishing configuration.
 * Manages WordPress post settings (type, status, author) and dynamic taxonomy
 * selection fields with comprehensive validation and sanitization.
 * 
 * @package DM_Recipes\WordPressRecipePublish
 * @since 1.0.0
 */
class WordPressRecipePublishSettings {

    public function __construct() {
    }

    /**
     * Get settings fields for WordPress recipe publish handler.
     *
     * @param array $current_config Current configuration values for this handler.
     * @return array Associative array defining the settings fields.
     */
    public static function get_fields(array $current_config = []): array {
        // WordPress recipe settings for local WordPress installation only
        $fields = self::get_local_fields();

        // Add common fields for all destination types
        $fields = array_merge($fields, self::get_common_fields());

        return $fields;
    }

    /**
     * Get settings fields common to all destination types.
     *
     * @return array Settings fields.
     */
    private static function get_common_fields(): array {
        return [
            'post_date_source' => [
                'type' => 'select',
                'label' => __('Post Date Setting', 'dm-recipes'),
                'description' => __('Choose whether to use the original date from the source (if available) or the current date when publishing.', 'dm-recipes'),
                'options' => [
                    'current_date' => __('Use Current Date', 'dm-recipes'),
                    'source_date' => __('Use Source Date (if available)', 'dm-recipes'),
                ],
            ],
        ];
    }
    
    /**
     * Get settings fields specific to local WordPress recipe publishing.
     *
     * @return array Settings fields.
     */
    private static function get_local_fields(): array {
        // Get available post types
        $post_type_options = [];
        $post_types = get_post_types(['public' => true], 'objects');
        $common_types = ['post' => 'Post', 'page' => 'Page'];
        foreach ($common_types as $slug => $label) {
            if (isset($post_types[$slug])) {
                $post_type_options[$slug] = $label;
                unset($post_types[$slug]);
            }
        }
        foreach ($post_types as $pt) {
            $post_type_options[$pt->name] = $pt->label;
        }

        // Get dynamic taxonomy fields for all available taxonomies
        $taxonomy_fields = self::get_taxonomy_fields();

        // Get available WordPress users for post authorship
        $user_options = [];
        $users = get_users(['fields' => ['ID', 'display_name', 'user_login']]);
        foreach ($users as $user) {
            $display_name = !empty($user->display_name) ? $user->display_name : $user->user_login;
            $user_options[$user->ID] = $display_name;
        }

        $fields = [
            'post_type' => [
                'type' => 'select',
                'label' => __('Post Type', 'dm-recipes'),
                'description' => __('Select the post type for published content.', 'dm-recipes'),
                'options' => $post_type_options,
            ],
            'post_status' => [
                'type' => 'select',
                'label' => __('Post Status', 'dm-recipes'),
                'description' => __('Select the status for the newly created post.', 'dm-recipes'),
                'options' => [
                    'draft' => __('Draft', 'dm-recipes'),
                    'publish' => __('Publish', 'dm-recipes'),
                    'pending' => __('Pending Review', 'dm-recipes'),
                    'private' => __('Private', 'dm-recipes'),
                ],
            ],
            'post_author' => [
                'type' => 'select',
                'label' => __('Post Author', 'dm-recipes'),
                'description' => __('Select which WordPress user to publish posts under.', 'dm-recipes'),
                'options' => $user_options,
            ],
        ];

        // Merge in dynamic taxonomy fields
        return array_merge($fields, $taxonomy_fields);
    }
    
    /**
     * Get dynamic taxonomy fields for all available public taxonomies.
     *
     * @return array Taxonomy field definitions.
     */
    private static function get_taxonomy_fields(): array {
        $taxonomy_fields = [];
        
        // Get all public taxonomies
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        
        foreach ($taxonomies as $taxonomy) {
            // Skip built-in formats and other non-content taxonomies
            if (in_array($taxonomy->name, ['post_format', 'nav_menu', 'link_category'])) {
                continue;
            }
            
            $taxonomy_slug = $taxonomy->name;
            $taxonomy_label = $taxonomy->labels->name ?? $taxonomy->label;
            
            // Build options with skip as default
            $options = [
                'skip' => __('Skip', 'dm-recipes'),
                'ai_decides' => __('AI Decides', 'dm-recipes')
            ];
            
            // Get terms for this taxonomy
            $terms = get_terms(['taxonomy' => $taxonomy_slug, 'hide_empty' => false]);
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $options[$term->term_id] = $term->name;
                }
            }
            
            // Generate field definition
            $field_key = "taxonomy_{$taxonomy_slug}_selection";
            $taxonomy_fields[$field_key] = [
                'type' => 'select',
                'label' => $taxonomy_label,
                'description' => sprintf(
                    __('Configure %s assignment: Skip to exclude from AI instructions, let AI choose, or select specific %s.', 'dm-recipes'),
                    strtolower($taxonomy_label),
                    $taxonomy->hierarchical ? __('category', 'dm-recipes') : __('term', 'dm-recipes')
                ),
                'options' => $options,
            ];
        }
        
        return $taxonomy_fields;
    }
    
    /**
     * Sanitize WordPress recipe publish handler settings.
     *
     * @param array $raw_settings Raw settings input.
     * @return array Sanitized settings.
     */
    public static function sanitize(array $raw_settings): array {
        // Sanitize local WordPress settings
        $sanitized = self::sanitize_local_settings($raw_settings);

        // Sanitize common fields - provide defaults for missing values
        $valid_date_sources = ['current_date', 'source_date'];
        $date_source = sanitize_text_field($raw_settings['post_date_source'] ?? 'current_date');
        if (!in_array($date_source, $valid_date_sources)) {
            $date_source = 'current_date'; // Fall back to default
        }
        $sanitized['post_date_source'] = $date_source;

        return $sanitized;
    }
    
    /**
     * Sanitize local WordPress settings.
     *
     * @param array $raw_settings Raw settings array.
     * @return array Sanitized settings.
     */
    private static function sanitize_local_settings(array $raw_settings): array {
        $sanitized = [
            'post_type' => sanitize_text_field($raw_settings['post_type'] ?? 'post'),
            'post_status' => sanitize_text_field($raw_settings['post_status'] ?? 'draft'),
            'post_author' => absint($raw_settings['post_author']),
        ];

        // Sanitize dynamic taxonomy selections
        $sanitized = array_merge($sanitized, self::sanitize_taxonomy_selections($raw_settings));

        return $sanitized;
    }
    
    /**
     * Sanitize dynamic taxonomy selection settings.
     *
     * @param array $raw_settings Raw settings array.
     * @return array Sanitized taxonomy selections.
     */
    private static function sanitize_taxonomy_selections(array $raw_settings): array {
        $sanitized = [];
        
        // Get all public taxonomies to validate against
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        
        foreach ($taxonomies as $taxonomy) {
            // Skip built-in formats and other non-content taxonomies
            if (in_array($taxonomy->name, ['post_format', 'nav_menu', 'link_category'])) {
                continue;
            }
            
            $field_key = "taxonomy_{$taxonomy->name}_selection";
            $raw_value = $raw_settings[$field_key] ?? 'skip';
            
            // Sanitize taxonomy selection value
            if ($raw_value === 'skip' || $raw_value === 'ai_decides') {
                $sanitized[$field_key] = $raw_value;
            } else {
                // Must be a term ID - validate it exists in this taxonomy
                $term_id = absint($raw_value);
                $term = get_term($term_id, $taxonomy->name);
                if (!is_wp_error($term) && $term) {
                    $sanitized[$field_key] = $term_id;
                } else {
                    // Invalid term ID - default to skip
                    $sanitized[$field_key] = 'skip';
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Determine if authentication is required based on current configuration.
     *
     * @param array $current_config Current configuration values for this handler.
     * @return bool True if authentication is required, false otherwise.
     */
    public static function requires_authentication(array $current_config = []): bool {
        // Local WordPress does not require authentication
        return false;
    }
}