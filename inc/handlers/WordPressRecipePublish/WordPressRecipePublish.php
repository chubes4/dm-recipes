<?php
namespace DM_Recipes\WordPressRecipePublish;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WordPressRecipePublish {

    public function handle_tool_call( array $parameters, array $tool_def = [] ): array {
        if ( empty( $parameters['post_title'] ) ) {
            return [
                'success' => false,
                'error' => 'Recipe title is required'
            ];
        }

        $handler_config = $this->validate_handler_config( $tool_def['handler_config'] ?? [], $parameters );
        if ( ! $handler_config['valid'] ) {
            return [
                'success' => false,
                'error' => 'Handler configuration error: ' . $handler_config['error']
            ];
        }
        $handler_config = $handler_config['config'];

        // Create recipe schema block using AI parameters
        $recipe_block_result = $this->create_recipe_schema_block( $parameters, $handler_config );
        
        if ( ! $recipe_block_result['success'] ) {
            return [
                'success' => false,
                'error' => 'Failed to create recipe block: ' . $recipe_block_result['error']
            ];
        }

        // Append recipe block to AI-generated article content
        $content = wp_kses_post( $parameters['post_content'] ?? '' );
        $content .= "\n\n" . $recipe_block_result['block'];

        $post_data = [
            'post_title' => sanitize_text_field( $parameters['post_title'] ),
            'post_content' => $content,
            'post_status' => $handler_config['post_status'],
            'post_author' => $handler_config['post_author'],
            'post_type' => $handler_config['post_type']
        ];

        $category_id = intval( $parameters['post_category'] ?? $handler_config['taxonomy_category_selection'] ?? 0 );
        if ( $category_id > 0 && term_exists( $category_id, 'category' ) ) {
            $post_data['post_category'] = [ $category_id ];
        }

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            return [
                'success' => false,
                'error' => 'Failed to create post: ' . ( is_wp_error( $post_id ) ? $post_id->get_error_message() : 'Invalid post ID' )
            ];
        }

        return [
            'success' => true,
            'message' => 'Recipe post created successfully',
            'post_id' => $post_id,
            'post_url' => get_permalink( $post_id ),
            'edit_url' => get_edit_post_link( $post_id, 'raw' )
        ];
    }
    
    private function create_recipe_schema_block( array $parameters, array $handler_config = [] ): array {
        // Build recipe data from AI parameters
        $recipe_data = [
            'recipeName' => sanitize_text_field( $parameters['recipeName'] ?? '' ),
            'description' => wp_kses_post( $parameters['description'] ?? '' ),
            'prepTime' => sanitize_text_field( $parameters['prepTime'] ?? '' ),
            'cookTime' => sanitize_text_field( $parameters['cookTime'] ?? '' ),
            'totalTime' => sanitize_text_field( $parameters['totalTime'] ?? '' ),
            'recipeYield' => sanitize_text_field( $parameters['recipeYield'] ?? '' ),
            'recipeCuisine' => sanitize_text_field( $parameters['recipeCuisine'] ?? '' ),
            'cookingMethod' => sanitize_text_field( $parameters['cookingMethod'] ?? '' ),
            'recipeIngredient' => $this->sanitize_array( $parameters['recipeIngredient'] ?? [] ),
            'recipeInstructions' => $this->sanitize_array( $parameters['recipeInstructions'] ?? [] ),
            'recipeCategory' => $this->sanitize_array( $parameters['recipeCategory'] ?? [] ),
            'keywords' => $this->sanitize_array( $parameters['keywords'] ?? [] ),
            'suitableForDiet' => $this->sanitize_array( $parameters['suitableForDiet'] ?? [] )
        ];
        
        if ( ! empty( $parameters['images'] ) && is_array( $parameters['images'] ) ) {
            $recipe_data['images'] = array_map( function( $image ) {
                return [
                    'url' => esc_url_raw( $image['url'] ?? '' ),
                    'alt' => sanitize_text_field( $image['alt'] ?? '' )
                ];
            }, $parameters['images'] );
        }
        
        // Set author data from handler configuration
        $author_user = get_userdata( $handler_config['post_author'] ?? 0 );
        if ( $author_user ) {
            $recipe_data['author'] = [
                'name' => sanitize_text_field( $author_user->display_name ),
                'url' => esc_url_raw( get_author_posts_url( $author_user->ID ) )
            ];
        }
        
        if ( ! empty( $parameters['nutrition'] ) && is_array( $parameters['nutrition'] ) ) {
            $recipe_data['nutrition'] = array_map( 'sanitize_text_field', $parameters['nutrition'] );
        }
        
        if ( ! empty( $parameters['video'] ) && is_array( $parameters['video'] ) ) {
            $recipe_data['video'] = [
                'name' => sanitize_text_field( $parameters['video']['name'] ?? '' ),
                'description' => sanitize_text_field( $parameters['video']['description'] ?? '' ),
                'contentUrl' => esc_url_raw( $parameters['video']['contentUrl'] ?? '' ),
                'thumbnailUrl' => esc_url_raw( $parameters['video']['thumbnailUrl'] ?? '' ),
                'duration' => sanitize_text_field( $parameters['video']['duration'] ?? '' )
            ];
        }
        
        if ( ! empty( $parameters['tool'] ) && is_array( $parameters['tool'] ) ) {
            $recipe_data['tool'] = $this->sanitize_array( $parameters['tool'] );
        }
        
        if ( ! empty( $parameters['supply'] ) && is_array( $parameters['supply'] ) ) {
            $recipe_data['supply'] = $this->sanitize_array( $parameters['supply'] );
        }
        
        if ( ! empty( $parameters['estimatedCost'] ) ) {
            $recipe_data['estimatedCost'] = sanitize_text_field( $parameters['estimatedCost'] );
        }
        
        $recipe_data['datePublished'] = sanitize_text_field( $parameters['datePublished'] ?? '' ) ?: current_time( 'c' );
        
        // Encode recipe data as JSON for block attributes
        $block_attributes = wp_json_encode( $recipe_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        
        if ( $block_attributes === false ) {
            return [
                'success' => false,
                'error' => 'Failed to encode recipe data as JSON: ' . json_last_error_msg()
            ];
        }
        
        // Generate Gutenberg block HTML
        $block_html = '<!-- wp:dm-recipes/recipe-schema ' . $block_attributes . ' -->' . "\n" .
                     '<!-- /wp:dm-recipes/recipe-schema -->';
        
        return [
            'success' => true,
            'block' => $block_html
        ];
    }
    
    private function sanitize_array( $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }
        return array_map( 'sanitize_text_field', array_filter( $input ) );
    }
    
    private function validate_handler_config( array $handler_config, array $parameters ): array {
        $handler_settings = $handler_config['wordpress_recipe_publish'] ?? $handler_config;
        $validated_config = [];

        $post_status = $parameters['post_status'] ?? $handler_settings['post_status'] ?? 'draft';
        if ( ! in_array( $post_status, [ 'draft', 'publish', 'private', 'pending' ], true ) ) {
            $post_status = 'draft';
        }
        $validated_config['post_status'] = $post_status;

        $post_author = intval( $parameters['post_author'] ?? $handler_settings['post_author'] ?? get_current_user_id() );
        if ( $post_author <= 0 || ! get_userdata( $post_author ) ) {
            $post_author = get_current_user_id();
            if ( $post_author <= 0 ) {
                return [
                    'valid' => false,
                    'error' => 'No valid post author available'
                ];
            }
        }
        $validated_config['post_author'] = $post_author;

        $post_type = $handler_settings['post_type'] ?? 'post';
        if ( ! post_type_exists( $post_type ) ) {
            $post_type = 'post';
        }
        $validated_config['post_type'] = $post_type;

        return [
            'valid' => true,
            'config' => $validated_config
        ];
    }
}