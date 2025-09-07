<?php
/**
 * WordPress Recipe Publish Handler
 * 
 * Handles AI tool calls to create WordPress posts with Recipe Schema blocks.
 * Integrates with Data Machine's Pipeline+Flow system for agentic recipe publishing.
 *
 * @package DM_Recipes\WordPressRecipePublish
 * @since 1.0.0
 */

namespace DM_Recipes\WordPressRecipePublish;

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WordPressRecipePublish {

    /**
     * Handle AI Tool Call for Recipe Publishing
     * 
     * Processes AI agent requests to create WordPress posts with recipe schema blocks.
     * This method is called by Data Machine when the AI agent uses the recipe_publish tool.
     *
     * @param array $parameters Recipe data from AI agent
     * @param array $tool_def Tool definition from Data Machine
     * @return array Success/failure response for AI agent
     * @since 1.0.0
     */
    public function handle_tool_call( array $parameters, array $tool_def = [] ): array {
        try {
            // Get handler configuration from tool definition
            $handler_config = $tool_def['handler_config'] ?? [];
            
            // Validate required handler configuration like Data Machine WordPress handler
            if ( empty( $handler_config['post_author'] ) ) {
                return [
                    'success' => false,
                    'error' => 'Recipe publish handler missing required post_author configuration',
                    'tool_name' => 'recipe_publish'
                ];
            }
            
            if ( empty( $handler_config['post_status'] ) ) {
                return [
                    'success' => false,
                    'error' => 'Recipe publish handler missing required post_status configuration',
                    'tool_name' => 'recipe_publish'
                ];
            }
            
            if ( empty( $handler_config['post_type'] ) ) {
                return [
                    'success' => false,
                    'error' => 'Recipe publish handler missing required post_type configuration',
                    'tool_name' => 'recipe_publish'
                ];
            }
            
            // Extract post content and recipe data from parameters (processed by AIStepToolParameters)
            $post_title = sanitize_text_field( $parameters['post_title'] ?? $parameters['title'] ?? '' );
            $post_content = wp_kses_post( $parameters['post_content'] ?? $parameters['content'] ?? '' );
            
            // Validate required fields
            if ( empty( $post_title ) ) {
                return [
                    'success' => false,
                    'error' => 'Recipe title is required'
                ];
            }
            
            // Create the WordPress post
            $post_data = [
                'post_title'   => $post_title,
                'post_content' => $post_content,
                'post_status'  => $handler_config['post_status'],
                'post_author'  => $handler_config['post_author'],
                'post_type'    => $handler_config['post_type']
            ];
            
            // Add category from handler config or parameters
            $category_id = intval( $parameters['post_category'] ?? $handler_config['taxonomy_category_selection'] ?? 0 );
            if ( $category_id > 0 && term_exists( $category_id, 'category' ) ) {
                $post_data['post_category'] = [ $category_id ];
            }
            
            $post_id = wp_insert_post( $post_data );
            
            if ( is_wp_error( $post_id ) ) {
                return [
                    'success' => false,
                    'error' => 'Failed to create post: ' . $post_id->get_error_message()
                ];
            }
            
            // Add recipe schema block to the post content
            $recipe_block = $this->create_recipe_schema_block( $parameters );
            
            // Append recipe block to existing content
            $updated_content = $post_content . "\n\n" . $recipe_block;
            
            // Update post with recipe block
            $updated_post = wp_update_post( [
                'ID' => $post_id,
                'post_content' => $updated_content
            ] );
            
            if ( is_wp_error( $updated_post ) ) {
                // Delete the post if block creation failed
                wp_delete_post( $post_id, true );
                return [
                    'success' => false,
                    'error' => 'Failed to add recipe block: ' . $updated_post->get_error_message()
                ];
            }
            
            
            return [
                'success' => true,
                'message' => 'Recipe post created successfully',
                'post_id' => $post_id,
                'post_url' => get_permalink( $post_id ),
                'edit_url' => get_edit_post_link( $post_id, 'raw' )
            ];
            
        } catch ( \Exception $e ) {
            return [
                'success' => false,
                'error' => 'Recipe publishing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create Recipe Schema Block HTML
     * 
     * Generates the Gutenberg block HTML for the recipe schema block
     * with all the provided recipe data from AIStepToolParameters.
     *
     * @param array $parameters Recipe data processed by AIStepToolParameters
     * @return string Recipe schema block HTML
     * @since 1.0.0
     */
    private function create_recipe_schema_block( array $parameters ): string {
        // Build recipe data from processed parameters (no manual extraction needed)
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
        
        // Handle images array
        if ( ! empty( $parameters['images'] ) && is_array( $parameters['images'] ) ) {
            $recipe_data['images'] = array_map( function( $image ) {
                return [
                    'url' => esc_url_raw( $image['url'] ?? '' ),
                    'alt' => sanitize_text_field( $image['alt'] ?? '' )
                ];
            }, $parameters['images'] );
        }
        
        // Handle author data from WordPress post author (from handler config)
        $author_user = get_userdata( $handler_config['post_author'] );
        if ( $author_user ) {
            $recipe_data['author'] = [
                'name' => sanitize_text_field( $author_user->display_name ),
                'url' => esc_url_raw( get_author_posts_url( $author_user->ID ) )
            ];
        }
        
        // Handle nutrition data
        if ( ! empty( $parameters['nutrition'] ) && is_array( $parameters['nutrition'] ) ) {
            $recipe_data['nutrition'] = array_map( 'sanitize_text_field', $parameters['nutrition'] );
        }
        
        // Handle video data
        if ( ! empty( $parameters['video'] ) && is_array( $parameters['video'] ) ) {
            $recipe_data['video'] = [
                'name' => sanitize_text_field( $parameters['video']['name'] ?? '' ),
                'description' => sanitize_text_field( $parameters['video']['description'] ?? '' ),
                'contentUrl' => esc_url_raw( $parameters['video']['contentUrl'] ?? '' ),
                'thumbnailUrl' => esc_url_raw( $parameters['video']['thumbnailUrl'] ?? '' ),
                'duration' => sanitize_text_field( $parameters['video']['duration'] ?? '' )
            ];
        }
        
        // Set date published if not provided
        if ( empty( $recipe_data['datePublished'] ) ) {
            $recipe_data['datePublished'] = current_time( 'c' );
        }
        
        // Encode the recipe data as JSON for the block
        $block_attributes = wp_json_encode( $recipe_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        
        // Return the Gutenberg block HTML
        return '<!-- wp:dm-recipes/recipe-schema ' . $block_attributes . ' -->' . "\n" .
               '<!-- /wp:dm-recipes/recipe-schema -->';
    }
    
    /**
     * Sanitize Array of Strings
     * 
     * Helper method to sanitize arrays of text values
     *
     * @param array $array Input array
     * @return array Sanitized array
     * @since 1.0.0
     */
    private function sanitize_array( $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }
        return array_map( 'sanitize_text_field', array_filter( $input ) );
    }
    
}