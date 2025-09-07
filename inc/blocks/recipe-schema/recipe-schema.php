<?php
/**
 * Recipe Schema Block Registration and Rendering
 * 
 * Handles the server-side registration and rendering of the Recipe Schema block,
 * including complete Schema.org structured data output and integration with
 * the existing theme rating system.
 *
 * @package DM_Recipes
 * @since 1.0.0
 */

/**
 * Register Recipe Schema Block
 * 
 * Registers the block type and enqueues editor assets
 * 
 * @since 1.0.0
 */
function dm_recipes_register_recipe_schema_block() {
    // Register block type
    register_block_type( plugin_dir_path( __FILE__ ) . 'block.json', array(
        'render_callback' => 'dm_recipes_render_recipe_schema_block',
    ) );
}

/**
 * Enqueue Recipe Schema Block Editor Assets
 * 
 * Loads the React editor interface and styles for the block editor
 * 
 * @since 1.0.0
 */
function dm_recipes_enqueue_recipe_schema_block_editor_assets() {
    // Enqueue editor script
    $editor_js_version = filemtime( plugin_dir_path( __FILE__ ) . 'recipe-schema.js' );
    wp_enqueue_script(
        'recipe-schema-editor-script',
        plugin_dir_url( __FILE__ ) . 'recipe-schema.js',
        array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-data' ),
        $editor_js_version,
        true
    );

    // Enqueue editor styles
    $editor_css_version = filemtime( plugin_dir_path( __FILE__ ) . 'recipe-schema.scss' );
    wp_enqueue_style(
        'recipe-schema-editor-style',
        plugin_dir_url( __FILE__ ) . 'recipe-schema.scss',
        array(),
        $editor_css_version
    );
}

/**
 * Render Recipe Schema Block
 * 
 * Generates the complete Schema.org Recipe structured data as JSON-LD
 * and semantic HTML output for the frontend
 * 
 * @param array $attributes Block attributes
 * @return string Rendered HTML output
 * @since 1.0.0
 */
function dm_recipes_render_recipe_schema_block( $attributes ) {

    // Get current post data
    global $post;
    
    // Set default attributes
    $attributes = wp_parse_args( $attributes, array(
        'recipeName' => '',
        'description' => '',
        'images' => array(),
        'prepTime' => '',
        'cookTime' => '',
        'totalTime' => '',
        'recipeYield' => '',
        'recipeCategory' => array(),
        'recipeCuisine' => '',
        'recipeIngredient' => array(),
        'recipeInstructions' => array(),
        'nutrition' => array(),
        'suitableForDiet' => array(),
        'keywords' => array(),
        'cookingMethod' => '',
        'video' => array(),
        'author' => array('name' => '', 'url' => ''),
        'datePublished' => '',
        'estimatedCost' => '',
        'tool' => array(),
        'supply' => array()
    ) );

    // Start output buffering
    ob_start();
    ?>
    
    <div class="recipe-schema-block" itemscope itemtype="https://schema.org/Recipe">
        
        <?php if ( ! empty( $attributes['recipeName'] ) ) : ?>
            <h2 class="recipe-name" itemprop="name"><?php echo esc_html( $attributes['recipeName'] ); ?></h2>
        <?php endif; ?>
        
        <?php if ( ! empty( $attributes['description'] ) ) : ?>
            <div class="recipe-description" itemprop="description">
                <?php echo wp_kses_post( $attributes['description'] ); ?>
            </div>
        <?php endif; ?>
        
        <?php if ( ! empty( $attributes['images'] ) ) : ?>
            <div class="recipe-images">
                <?php foreach ( $attributes['images'] as $index => $image ) : ?>
                    <?php if ( $index < 6 ) : // Limit to 6 images for schema ?>
                        <img src="<?php echo esc_url( $image['url'] ); ?>" 
                             alt="<?php echo esc_attr( $image['alt'] ); ?>" 
                             itemprop="image" />
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="recipe-meta">
            <?php if ( ! empty( $attributes['prepTime'] ) ) : ?>
                <div class="prep-time">
                    <strong><?php _e( 'Prep Time:', 'dm-recipes' ); ?></strong> 
                    <span itemprop="prepTime" content="<?php echo esc_attr( $attributes['prepTime'] ); ?>">
                        <?php echo esc_html( dm_recipes_format_duration( $attributes['prepTime'] ) ); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ( ! empty( $attributes['cookTime'] ) ) : ?>
                <div class="cook-time">
                    <strong><?php _e( 'Cook Time:', 'dm-recipes' ); ?></strong> 
                    <span itemprop="cookTime" content="<?php echo esc_attr( $attributes['cookTime'] ); ?>">
                        <?php echo esc_html( dm_recipes_format_duration( $attributes['cookTime'] ) ); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ( ! empty( $attributes['totalTime'] ) ) : ?>
                <div class="total-time">
                    <strong><?php _e( 'Total Time:', 'dm-recipes' ); ?></strong> 
                    <span itemprop="totalTime" content="<?php echo esc_attr( $attributes['totalTime'] ); ?>">
                        <?php echo esc_html( dm_recipes_format_duration( $attributes['totalTime'] ) ); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ( ! empty( $attributes['recipeYield'] ) ) : ?>
                <div class="recipe-yield">
                    <strong><?php _e( 'Yield:', 'dm-recipes' ); ?></strong> 
                    <span itemprop="recipeYield"><?php echo esc_html( $attributes['recipeYield'] ); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ( ! empty( $attributes['recipeCuisine'] ) ) : ?>
                <div class="recipe-cuisine">
                    <strong><?php _e( 'Cuisine:', 'dm-recipes' ); ?></strong> 
                    <span itemprop="recipeCuisine"><?php echo esc_html( $attributes['recipeCuisine'] ); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ( ! empty( $attributes['recipeIngredient'] ) ) : ?>
            <div class="recipe-ingredients">
                <h3><?php _e( 'Ingredients', 'dm-recipes' ); ?></h3>
                <ul>
                    <?php foreach ( $attributes['recipeIngredient'] as $ingredient ) : ?>
                        <li itemprop="recipeIngredient"><?php echo esc_html( $ingredient ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ( ! empty( $attributes['recipeInstructions'] ) ) : ?>
            <div class="recipe-instructions">
                <h3><?php _e( 'Instructions', 'dm-recipes' ); ?></h3>
                <ol>
                    <?php foreach ( $attributes['recipeInstructions'] as $instruction ) : ?>
                        <li itemprop="recipeInstructions" itemscope itemtype="https://schema.org/HowToStep">
                            <span itemprop="text"><?php echo wp_kses_post( $instruction ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>
        
        <?php
        // Include rating data if available
        $rating_value = get_post_meta( $post->ID, 'rating_value', true );
        $review_count = get_post_meta( $post->ID, 'review_count', true );
        
        if ( $review_count > 0 && $rating_value >= 1 && $rating_value <= 5 ) :
            $average_rating = round( floatval( $rating_value ), 2 );
        ?>
            <div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                <meta itemprop="ratingValue" content="<?php echo esc_attr( $average_rating ); ?>" />
                <meta itemprop="reviewCount" content="<?php echo esc_attr( intval( $review_count ) ); ?>" />
            </div>
        <?php endif; ?>
        
        <!-- Author markup -->
        <div itemprop="author" itemscope itemtype="https://schema.org/Person" style="display: none;">
            <meta itemprop="name" content="<?php echo esc_attr( $attributes['author']['name'] ); ?>" />
            <?php if ( ! empty( $attributes['author']['url'] ) ) : ?>
                <meta itemprop="url" content="<?php echo esc_url( $attributes['author']['url'] ); ?>" />
            <?php endif; ?>
        </div>
        
        <!-- Additional hidden schema markup -->
        <?php if ( ! empty( $attributes['recipeCategory'] ) ) : ?>
            <?php foreach ( $attributes['recipeCategory'] as $category ) : ?>
                <meta itemprop="recipeCategory" content="<?php echo esc_attr( $category ); ?>" />
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ( ! empty( $attributes['keywords'] ) ) : ?>
            <meta itemprop="keywords" content="<?php echo esc_attr( implode( ', ', $attributes['keywords'] ) ); ?>" />
        <?php endif; ?>
        
        <?php if ( ! empty( $attributes['cookingMethod'] ) ) : ?>
            <meta itemprop="cookingMethod" content="<?php echo esc_attr( $attributes['cookingMethod'] ); ?>" />
        <?php endif; ?>
        
        <!-- Date published -->
        <?php 
        $date_published = ! empty( $attributes['datePublished'] ) ? $attributes['datePublished'] : get_the_date( 'c', $post->ID );
        ?>
        <meta itemprop="datePublished" content="<?php echo esc_attr( $date_published ); ?>" />
        
    </div>
    
    <?php
    // Generate JSON-LD structured data
    $schema_data = dm_recipes_generate_recipe_jsonld( $attributes, $post );
    if ( ! empty( $schema_data ) ) {
        echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
    }
    
    return ob_get_clean();
}

/**
 * Generate Recipe JSON-LD Structured Data
 * 
 * Creates complete Schema.org Recipe structured data for search engines
 * 
 * @param array $attributes Block attributes
 * @param WP_Post $post Current post object
 * @return array JSON-LD schema data
 * @since 1.0.0
 */
function dm_recipes_generate_recipe_jsonld( $attributes, $post ) {
    $schema = array(
        '@context' => 'https://schema.org/',
        '@type' => 'Recipe'
    );
    
    // Basic recipe information
    if ( ! empty( $attributes['recipeName'] ) ) {
        $schema['name'] = $attributes['recipeName'];
    }
    
    if ( ! empty( $attributes['description'] ) ) {
        $schema['description'] = wp_strip_all_tags( $attributes['description'] );
    }
    
    // Images
    if ( ! empty( $attributes['images'] ) ) {
        $schema['image'] = array();
        foreach ( $attributes['images'] as $image ) {
            $schema['image'][] = $image['url'];
        }
    }
    
    // Timing
    if ( ! empty( $attributes['prepTime'] ) ) {
        $schema['prepTime'] = $attributes['prepTime'];
    }
    
    if ( ! empty( $attributes['cookTime'] ) ) {
        $schema['cookTime'] = $attributes['cookTime'];
    }
    
    if ( ! empty( $attributes['totalTime'] ) ) {
        $schema['totalTime'] = $attributes['totalTime'];
    }
    
    // Recipe content
    if ( ! empty( $attributes['recipeYield'] ) ) {
        $schema['recipeYield'] = $attributes['recipeYield'];
    }
    
    if ( ! empty( $attributes['recipeCategory'] ) ) {
        $schema['recipeCategory'] = $attributes['recipeCategory'];
    }
    
    if ( ! empty( $attributes['recipeCuisine'] ) ) {
        $schema['recipeCuisine'] = $attributes['recipeCuisine'];
    }
    
    if ( ! empty( $attributes['recipeIngredient'] ) ) {
        $schema['recipeIngredient'] = $attributes['recipeIngredient'];
    }
    
    if ( ! empty( $attributes['recipeInstructions'] ) ) {
        $schema['recipeInstructions'] = array();
        foreach ( $attributes['recipeInstructions'] as $index => $instruction ) {
            $schema['recipeInstructions'][] = array(
                '@type' => 'HowToStep',
                'name' => 'Step ' . ( $index + 1 ),
                'text' => wp_strip_all_tags( $instruction )
            );
        }
    }
    
    // Author
    $schema['author'] = array(
        '@type' => 'Person',
        'name' => $attributes['author']['name']
    );
    
    if ( ! empty( $attributes['author']['url'] ) ) {
        $schema['author']['url'] = $attributes['author']['url'];
    }
    
    // Date published
    $schema['datePublished'] = ! empty( $attributes['datePublished'] ) 
        ? $attributes['datePublished'] 
        : get_the_date( 'c', $post->ID );
    
    // Rating data
    $rating_value = get_post_meta( $post->ID, 'rating_value', true );
    $review_count = get_post_meta( $post->ID, 'review_count', true );
    
    if ( $review_count > 0 && $rating_value >= 1 && $rating_value <= 5 ) {
        $schema['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => round( floatval( $rating_value ), 2 ),
            'reviewCount' => intval( $review_count )
        );
    }
    
    // Additional properties
    if ( ! empty( $attributes['keywords'] ) ) {
        $schema['keywords'] = $attributes['keywords'];
    }
    
    if ( ! empty( $attributes['cookingMethod'] ) ) {
        $schema['cookingMethod'] = $attributes['cookingMethod'];
    }
    
    if ( ! empty( $attributes['nutrition'] ) && array_filter( $attributes['nutrition'] ) ) {
        $schema['nutrition'] = array(
            '@type' => 'NutritionInformation'
        );
        
        foreach ( $attributes['nutrition'] as $key => $value ) {
            if ( ! empty( $value ) ) {
                $schema['nutrition'][$key] = $value;
            }
        }
    }
    
    if ( ! empty( $attributes['suitableForDiet'] ) ) {
        $schema['suitableForDiet'] = $attributes['suitableForDiet'];
    }
    
    if ( ! empty( $attributes['video'] ) && ! empty( $attributes['video']['contentUrl'] ) ) {
        $schema['video'] = array(
            '@type' => 'VideoObject',
            'name' => $attributes['video']['name'],
            'description' => $attributes['video']['description'],
            'contentUrl' => $attributes['video']['contentUrl']
        );
        
        if ( ! empty( $attributes['video']['thumbnailUrl'] ) ) {
            $schema['video']['thumbnailUrl'] = $attributes['video']['thumbnailUrl'];
        }
        
        if ( ! empty( $attributes['video']['duration'] ) ) {
            $schema['video']['duration'] = $attributes['video']['duration'];
        }
    }
    
    return $schema;
}

/**
 * Format Duration for Display
 * 
 * Converts ISO 8601 duration format to human-readable format
 * 
 * @param string $duration ISO 8601 duration (e.g., PT30M)
 * @return string Human-readable duration
 * @since 1.0.0
 */
function dm_recipes_format_duration( $duration ) {
    // Handle ISO 8601 format (PT30M, PT1H30M, etc.)
    if ( strpos( $duration, 'PT' ) === 0 ) {
        $duration = substr( $duration, 2 ); // Remove PT prefix
        
        $hours = 0;
        $minutes = 0;
        
        // Extract hours
        if ( preg_match( '/(\d+)H/', $duration, $matches ) ) {
            $hours = intval( $matches[1] );
        }
        
        // Extract minutes
        if ( preg_match( '/(\d+)M/', $duration, $matches ) ) {
            $minutes = intval( $matches[1] );
        }
        
        // Format output
        $parts = array();
        if ( $hours > 0 ) {
            $parts[] = $hours . ' ' . ( $hours === 1 ? 'hour' : 'hours' );
        }
        if ( $minutes > 0 ) {
            $parts[] = $minutes . ' ' . ( $minutes === 1 ? 'minute' : 'minutes' );
        }
        
        return implode( ' ', $parts );
    }
    
    // Return as-is if not ISO 8601 format
    return $duration;
}

// Hook into WordPress
add_action( 'init', 'dm_recipes_register_recipe_schema_block' );
add_action( 'enqueue_block_editor_assets', 'dm_recipes_enqueue_recipe_schema_block_editor_assets' );