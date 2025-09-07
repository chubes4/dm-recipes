<?php
/**
 * WordPress Recipe Publish Handler Filters
 * 
 * Registers the WordPress Recipe Publish handler with Data Machine's filter-based
 * discovery system. This file handles both handler registration and AI tool registration.
 *
 * @package DM_Recipes\WordPressRecipePublish
 * @since 1.0.0
 */

namespace DM_Recipes\WordPressRecipePublish;

// Security: Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register WordPress Recipe Publish Handler Filters
 * 
 * Registers the handler with Data Machine's discovery system and defines
 * the AI tool for recipe publishing functionality.
 *
 * @since 1.0.0
 */
function dm_recipes_register_wordpress_recipe_publish_filters() {
    
    /**
     * Register Handler with Data Machine Discovery System
     * 
     * This filter allows Data Machine to discover and load the WordPress Recipe Publish handler.
     */
    add_filter( 'dm_handlers', function( $handlers ) {
        $handlers['wordpress_recipe_publish'] = [
            'type' => 'publish',
            'class' => WordPressRecipePublish::class,
            'label' => __( 'WordPress Recipe', 'dm-recipes' ),
            'description' => __( 'Publish recipes to WordPress with Schema.org structured data markup', 'dm-recipes' )
        ];
        return $handlers;
    } );

    /**
     * Register AI Tool for Recipe Publishing
     * 
     * This filter registers the recipe_publish AI tool that allows AI agents
     * to create WordPress posts with recipe schema blocks.
     */
    add_filter( 'ai_tools', function( $tools, $handler_slug = null, $handler_config = [] ) {
        if ( $handler_slug === 'wordpress_recipe_publish' ) {
            $tools['recipe_publish'] = [
                'class' => WordPressRecipePublish::class,
                'method' => 'handle_tool_call',
                'handler' => 'wordpress_recipe_publish',
                'description' => 'Create a WordPress post with recipe content and Schema.org structured data. Use this tool to publish recipe posts with complete recipe information including ingredients, instructions, cooking times, and nutritional data.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        // Post-level parameters
                        'post_title' => [
                            'type' => 'string',
                            'description' => 'The title of the blog post (required)'
                        ],
                        'post_content' => [
                            'type' => 'string',
                            'description' => 'The main blog post content (can be empty if only recipe schema is needed)'
                        ],
                        'post_status' => [
                            'type' => 'string',
                            'enum' => ['draft', 'publish', 'private'],
                            'description' => 'Post publication status (default: draft)'
                        ],
                        'post_author' => [
                            'type' => 'integer',
                            'description' => 'WordPress user ID for post author (optional)'
                        ],
                        'post_category' => [
                            'type' => 'integer',
                            'description' => 'WordPress category ID for the post (optional)'
                        ],
                        
                        // Recipe Schema.org parameters
                        'recipeName' => [
                            'type' => 'string',
                            'description' => 'The name of the recipe (required for schema)'
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'A description of the recipe'
                        ],
                        'images' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'url' => ['type' => 'string', 'description' => 'Image URL'],
                                    'alt' => ['type' => 'string', 'description' => 'Alt text for image']
                                ]
                            ],
                            'description' => 'Array of recipe images with URL and alt text'
                        ],
                        
                        // Timing information (ISO 8601 duration format like PT30M for 30 minutes)
                        'prepTime' => [
                            'type' => 'string',
                            'description' => 'Preparation time in ISO 8601 format (e.g., PT30M for 30 minutes)'
                        ],
                        'cookTime' => [
                            'type' => 'string', 
                            'description' => 'Cooking time in ISO 8601 format (e.g., PT1H for 1 hour)'
                        ],
                        'totalTime' => [
                            'type' => 'string',
                            'description' => 'Total time in ISO 8601 format (prep + cook time)'
                        ],
                        
                        // Recipe details
                        'recipeYield' => [
                            'type' => 'string',
                            'description' => 'Number of servings or yield (e.g., "4 servings", "12 muffins")'
                        ],
                        'recipeCategory' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Recipe categories (e.g., ["appetizer", "main course", "dessert"])'
                        ],
                        'recipeCuisine' => [
                            'type' => 'string',
                            'description' => 'The cuisine type (e.g., "Italian", "Mexican", "American")'
                        ],
                        'cookingMethod' => [
                            'type' => 'string',
                            'description' => 'Cooking method (e.g., "baking", "grilling", "frying")'
                        ],
                        
                        // Recipe content arrays
                        'recipeIngredient' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'List of ingredients with quantities (e.g., ["2 cups flour", "1 tsp salt"])'
                        ],
                        'recipeInstructions' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Step-by-step cooking instructions'
                        ],
                        
                        // Additional metadata
                        'keywords' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Keywords or tags for the recipe'
                        ],
                        'suitableForDiet' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Dietary restrictions (e.g., ["vegetarian", "gluten-free", "low-carb"])'
                        ],
                        
                        // Advanced schema properties
                        'nutrition' => [
                            'type' => 'object',
                            'properties' => [
                                'calories' => ['type' => 'string', 'description' => 'Calories per serving'],
                                'fatContent' => ['type' => 'string', 'description' => 'Fat content'],
                                'carbohydrateContent' => ['type' => 'string', 'description' => 'Carbohydrate content'],
                                'proteinContent' => ['type' => 'string', 'description' => 'Protein content'],
                                'sodiumContent' => ['type' => 'string', 'description' => 'Sodium content'],
                                'fiberContent' => ['type' => 'string', 'description' => 'Fiber content']
                            ],
                            'description' => 'Nutritional information for the recipe'
                        ],
                        
                        'video' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string', 'description' => 'Video title'],
                                'description' => ['type' => 'string', 'description' => 'Video description'],
                                'contentUrl' => ['type' => 'string', 'description' => 'Video URL'],
                                'thumbnailUrl' => ['type' => 'string', 'description' => 'Video thumbnail URL'],
                                'duration' => ['type' => 'string', 'description' => 'Video duration in ISO 8601 format']
                            ],
                            'description' => 'Recipe video information'
                        ],
                        
                        'tool' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Cooking tools or equipment needed'
                        ],
                        
                        'supply' => [
                            'type' => 'array', 
                            'items' => ['type' => 'string'],
                            'description' => 'Supplies consumed during cooking (beyond ingredients)'
                        ],
                        
                        'estimatedCost' => [
                            'type' => 'string',
                            'description' => 'Estimated cost to make the recipe'
                        ],
                        
                        'datePublished' => [
                            'type' => 'string',
                            'description' => 'Publication date in ISO 8601 format (auto-generated if not provided)'
                        ]
                    ],
                    'required' => ['post_title', 'recipeName']
                ],
                'handler_config' => $handler_config
            ];
        }
        return $tools;
    }, 10, 3 );
    
    /**
     * Register Settings Handler with Data Machine Discovery System
     * 
     * This filter allows Data Machine to discover the settings class for the handler.
     */
    add_filter( 'dm_handler_settings', function( $all_settings ) {
        $all_settings['wordpress_recipe_publish'] = new WordPressRecipePublishSettings();
        return $all_settings;
    } );

    /**
     * Register Handler Directive for AI Models
     * 
     * Provides specific guidance to AI models when publishing recipe content.
     * This directive ensures AI models create comprehensive recipe posts with
     * proper Schema.org structured data and clear, helpful recipe information.
     *
     * @since 1.0.0
     */
    add_filter( 'dm_handler_directives', function( $directives ) {
        $directives['wordpress_recipe_publish'] = 'When publishing recipes to WordPress, create comprehensive recipe content with proper Schema.org structured data. Focus on clear, detailed ingredients with specific measurements, step-by-step instructions, accurate timing information (prep/cook/total), and helpful cooking tips. Include recipe categories, cuisine types, and dietary information when relevant. Ensure all recipe data follows Schema.org Recipe markup standards for optimal SEO and rich snippets. Use descriptive language that helps readers understand the cooking process and expected results.';
        return $directives;
    } );
    
    /**
     * Register Engine Parameter Filter for Recipe Handler Integration
     * 
     * Integrates with Data Machine's unified parameter system to ensure
     * recipe publishing has access to all engine-level parameters and
     * allows extensibility for future parameter additions.
     *
     * @since 1.0.0
     */
    add_filter( 'dm_engine_parameters', function( $parameters, $data, $flow_step_config, $step_type, $flow_step_id ) {
        // Only add recipe-specific parameters when this is a publish step with wordpress_recipe_publish handler
        if ( $step_type === 'publish' && isset( $flow_step_config['handler'] ) && $flow_step_config['handler'] === 'wordpress_recipe_publish' ) {
            // Add any recipe-specific engine parameters here if needed in the future
            // Current implementation inherits all standard parameters from engine
        }
        return $parameters;
    }, 10, 5 );
}

// Register the filters on WordPress init
add_action( 'init', __NAMESPACE__ . '\\dm_recipes_register_wordpress_recipe_publish_filters' );