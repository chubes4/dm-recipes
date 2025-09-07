<?php
namespace DM_Recipes\WordPressRecipePublish;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register WordPress Recipe Publish handler with Data Machine filter system.
 * Registers handler, AI tool, settings, and directives for recipe publishing.
 */
function dm_recipes_register_wordpress_recipe_publish_filters() {
    
    add_filter( 'dm_handlers', function( $handlers ) {
        $handlers['wordpress_recipe_publish'] = [
            'type' => 'publish',
            'class' => WordPressRecipePublish::class,
            'label' => __( 'WordPress Recipe', 'dm-recipes' ),
            'description' => __( 'Publish recipes to WordPress with Schema.org structured data markup', 'dm-recipes' )
        ];
        return $handlers;
    } );

    add_filter( 'ai_tools', function( $tools, $handler_slug = null, $handler_config = [] ) {
        if ( $handler_slug === 'wordpress_recipe_publish' ) {
            // Extract handler configuration from nested structure
            $recipe_config = $handler_config['wordpress_recipe_publish'] ?? $handler_config;
            
            $tools['recipe_publish'] = [
                'class' => WordPressRecipePublish::class,
                'method' => 'handle_tool_call',
                'handler' => 'wordpress_recipe_publish',
                'description' => 'Publish recipe content to WordPress as a comprehensive recipe blog post.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'post_title' => [
                            'type' => 'string',
                            'description' => 'The title of the blog post (required)'
                        ],
                        'post_content' => [
                            'type' => 'string',
                            'description' => 'Recipe article content formatted as WordPress Gutenberg blocks. Use <!-- wp:paragraph --><p>Content</p><!-- /wp:paragraph --> for paragraphs, <!-- wp:list --><ul><li>Item</li></ul><!-- /wp:list --> for lists. Write a comprehensive recipe article with introduction, cooking tips, and detailed instructions.'
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
                'handler_config' => $recipe_config
            ];
        }
        return $tools;
    }, 10, 3 );
    
    add_filter( 'dm_handler_settings', function( $all_settings ) {
        $all_settings['wordpress_recipe_publish'] = new WordPressRecipePublishSettings();
        return $all_settings;
    } );

    add_filter( 'dm_handler_directives', function( $directives ) {
        $directives['wordpress_recipe_publish'] = 'When publishing recipes to WordPress, create comprehensive recipe content with proper Schema.org structured data. Focus on clear, detailed ingredients with specific measurements, step-by-step instructions, accurate timing information (prep/cook/total), and helpful cooking tips. Include recipe categories, cuisine types, and dietary information when relevant. Ensure all recipe data follows Schema.org Recipe markup standards for optimal SEO and rich snippets. Use descriptive language that helps readers understand the cooking process and expected results.';
        return $directives;
    } );
    
    add_filter( 'dm_engine_parameters', function( $parameters, $data, $flow_step_config, $step_type, $flow_step_id ) {
        if ( $step_type === 'publish' && isset( $flow_step_config['handler'] ) && $flow_step_config['handler'] === 'wordpress_recipe_publish' ) {
            // Reserved for future recipe-specific parameters
        }
        return $parameters;
    }, 10, 5 );
}

add_action( 'init', __NAMESPACE__ . '\\dm_recipes_register_wordpress_recipe_publish_filters' );