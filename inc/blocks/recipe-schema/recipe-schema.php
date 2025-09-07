<?php
/**
 * Recipe Schema Gutenberg Block Implementation.
 * Handles registration, rendering, and structured data generation.
 */

/**
 * Register Recipe Schema block with WordPress.
 */
function dm_recipes_register_recipe_schema_block() {
    register_block_type( plugin_dir_path( __FILE__ ) . 'block.json', array(
        'render_callback' => 'dm_recipes_render_recipe_schema_block',
    ) );
}

/**
 * Render Recipe Schema block with microdata and JSON-LD.
 * 
 * @param array $attributes Block attributes from Gutenberg
 * @return string Block HTML output with Schema.org markup
 */
function dm_recipes_render_recipe_schema_block( $attributes ) {
    global $post;
    
    $defaults = [
        'recipeName' => '',
        'description' => '',
        'images' => [],
        'prepTime' => '',
        'cookTime' => '',
        'totalTime' => '',
        'recipeYield' => '',
        'recipeCategory' => [],
        'recipeCuisine' => '',
        'recipeIngredient' => [],
        'recipeInstructions' => [],
        'nutrition' => [],
        'suitableForDiet' => [],
        'keywords' => [],
        'cookingMethod' => '',
        'video' => [],
        'author' => ['name' => '', 'url' => ''],
        'datePublished' => '',
        'estimatedCost' => '',
        'tool' => [],
        'supply' => []
    ];
    
    $attributes = wp_parse_args( $attributes, $defaults );

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
                    <?php if ( $index < 6 ) : // Limit images for performance ?>
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
        // Display aggregate rating if available from post meta
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
        
        <div itemprop="author" itemscope itemtype="https://schema.org/Person" style="display: none;">
            <meta itemprop="name" content="<?php echo esc_attr( $attributes['author']['name'] ); ?>" />
            <?php if ( ! empty( $attributes['author']['url'] ) ) : ?>
                <meta itemprop="url" content="<?php echo esc_url( $attributes['author']['url'] ); ?>" />
            <?php endif; ?>
        </div>
        
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
        
        <?php 
        // Use provided publication date or fall back to post date
        $date_published = ! empty( $attributes['datePublished'] ) ? $attributes['datePublished'] : get_the_date( 'c', $post->ID );
        ?>
        <meta itemprop="datePublished" content="<?php echo esc_attr( $date_published ); ?>" />
        
    </div>
    
    <?php
    $schema_data = dm_recipes_generate_recipe_jsonld( $attributes, $post );
    if ( ! empty( $schema_data ) ) {
        echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
    }
    
    return ob_get_clean();
}

/**
 * Generate JSON-LD structured data for recipe.
 * 
 * @param array   $attributes Recipe block attributes
 * @param WP_Post $post       WordPress post object
 * @return array Schema.org Recipe structured data
 */
function dm_recipes_generate_recipe_jsonld( $attributes, $post ) {
    $schema = array(
        '@context' => 'https://schema.org/',
        '@type' => 'Recipe'
    );
    
    if ( ! empty( $attributes['recipeName'] ) ) {
        $schema['name'] = $attributes['recipeName'];
    }
    
    if ( ! empty( $attributes['description'] ) ) {
        $schema['description'] = wp_strip_all_tags( $attributes['description'] );
    }
    
    if ( ! empty( $attributes['images'] ) ) {
        $schema['image'] = array();
        foreach ( $attributes['images'] as $image ) {
            $schema['image'][] = $image['url'];
        }
    }
    
    if ( ! empty( $attributes['prepTime'] ) ) {
        $schema['prepTime'] = $attributes['prepTime'];
    }
    
    if ( ! empty( $attributes['cookTime'] ) ) {
        $schema['cookTime'] = $attributes['cookTime'];
    }
    
    if ( ! empty( $attributes['totalTime'] ) ) {
        $schema['totalTime'] = $attributes['totalTime'];
    }
    
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
    
    $schema['author'] = array(
        '@type' => 'Person',
        'name' => $attributes['author']['name']
    );
    
    if ( ! empty( $attributes['author']['url'] ) ) {
        $schema['author']['url'] = $attributes['author']['url'];
    }
    
    $schema['datePublished'] = ! empty( $attributes['datePublished'] ) 
        ? $attributes['datePublished'] 
        : get_the_date( 'c', $post->ID );
    
    // Add aggregate rating from post meta if available
    $rating_value = get_post_meta( $post->ID, 'rating_value', true );
    $review_count = get_post_meta( $post->ID, 'review_count', true );
    
    if ( $review_count > 0 && $rating_value >= 1 && $rating_value <= 5 ) {
        $schema['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => round( floatval( $rating_value ), 2 ),
            'reviewCount' => intval( $review_count )
        );
    }
    
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
 * Convert ISO 8601 duration to human-readable format.
 * 
 * @param string $duration ISO 8601 duration (e.g., "PT30M")
 * @return string Human-readable duration (e.g., "30 minutes")
 */
function dm_recipes_format_duration( $duration ) {
    if ( strpos( $duration, 'PT' ) === 0 ) {
        $duration = substr( $duration, 2 );
        
        $hours = 0;
        $minutes = 0;
        
        if ( preg_match( '/(\d+)H/', $duration, $matches ) ) {
            $hours = intval( $matches[1] );
        }
        
        if ( preg_match( '/(\d+)M/', $duration, $matches ) ) {
            $minutes = intval( $matches[1] );
        }
        
        $parts = array();
        if ( $hours > 0 ) {
            $parts[] = $hours . ' ' . ( $hours === 1 ? 'hour' : 'hours' );
        }
        if ( $minutes > 0 ) {
            $parts[] = $minutes . ' ' . ( $minutes === 1 ? 'minute' : 'minutes' );
        }
        
        return implode( ' ', $parts );
    }
    
    return $duration;
}

add_action( 'init', 'dm_recipes_register_recipe_schema_block' );