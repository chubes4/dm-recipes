# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture Overview

**DM-Recipes** is a Data Machine extension plugin that adds recipe publishing capabilities with full Schema.org structured data support. It integrates with the Data Machine Pipeline+Flow system through a **filter-based discovery** architecture.

### Core Components

#### WordPress Recipe Publish Handler (`/inc/handlers/WordPressRecipePublish/`)
- **Main Handler**: `WordPressRecipePublish.php` - AI tool execution and post creation
- **Filter Registration**: `WordPressRecipePublishFilters.php` - Handler and AI tool discovery
- **Settings Management**: `WordPressRecipePublishSettings.php` - Configuration handling

#### Recipe Schema Gutenberg Block (`/inc/blocks/recipe-schema/`)
- **Block Definition**: `block.json` - Complete Schema.org Recipe attributes
- **Server Rendering**: `recipe-schema.php` - Block registration and HTML/JSON-LD output  
- **Block Initialization**: `index.php` - WordPress hook registration

## Integration with Data Machine

### Handler Registration Pattern
All handlers self-register via WordPress filters in the `*Filters.php` files:

```php
// Register handler for discovery
add_filter('dm_handlers', function($handlers) {
    $handlers['wordpress_recipe_publish'] = [
        'type' => 'publish',
        'class' => WordPressRecipePublish::class,
        'label' => __('WordPress Recipe', 'dm-recipes'),
        'description' => __('Publish recipes with Schema.org markup', 'dm-recipes')
    ];
    return $handlers;
});

// Register AI tool for agent execution
add_filter('ai_tools', function($tools, $handler_slug = null, $handler_config = []) {
    if ($handler_slug === 'wordpress_recipe_publish') {
        $tools['recipe_publish'] = [
            'class' => WordPressRecipePublish::class,
            'method' => 'handle_tool_call',
            'handler' => 'wordpress_recipe_publish',
            'description' => 'Create WordPress post with recipe schema block',
            'parameters' => [/* Schema.org Recipe parameters */]
        ];
    }
    return $tools;
}, 10, 3);
```

### AI Tool Implementation
The handler implements `handle_tool_call(array $parameters, array $tool_def = []): array` for AI agent execution:

```php
public function handle_tool_call($parameters, $tool_def = []) {
    // 1. Create WordPress post with provided content
    // 2. Add Recipe Schema block with structured data  
    // 3. Return success/failure status for AI agent
    // Fully implemented with comprehensive error handling and validation
}
```

## Schema.org Recipe Implementation

### Block Attributes (from `/inc/blocks/recipe-schema/block.json`)
The recipe block supports complete Schema.org Recipe markup including:

- **Basic Info**: `recipeName`, `description`, `images`, `author`
- **Timing**: `prepTime`, `cookTime`, `totalTime` (ISO 8601 format)
- **Content**: `recipeIngredient[]`, `recipeInstructions[]`, `recipeYield`
- **Classification**: `recipeCategory[]`, `recipeCuisine`, `keywords[]`
- **Advanced**: `nutrition{}`, `suitableForDiet[]`, `video{}`, `tool[]`, `supply[]`

### Structured Data Output
The block generates both:
1. **Microdata**: HTML with `itemscope`, `itemtype`, and `itemprop` attributes
2. **JSON-LD**: Complete Schema.org Recipe structured data for search engines

## Development Commands

### Plugin Development
```bash
# Install dependencies and run linting
composer install                         # Install development dependencies
composer lint                            # Run PHP CodeSniffer checks
composer lint:fix                        # Auto-fix PHP coding standard issues
composer lint:php                        # PHP CodeSniffer with WordPress standards
composer lint:fix:php                    # Auto-fix with WordPress standards
```

### Production Build Process
```bash
# Production deployment
./build.sh                               # Create production ZIP file

# Process:
# 1. Install production dependencies (composer install --no-dev)
# 2. Copy files using rsync with .buildignore exclusions  
# 3. Create versioned ZIP file for WordPress deployment
# 4. Validate all required files are present
# 5. Restore development dependencies
```

## File Structure

```
dm-recipes/
├── dm-recipes.php                       # Main plugin file
├── build.sh                             # Production build script
├── composer.json                        # PHP dependencies and autoloading
├── inc/
│   ├── handlers/WordPressRecipePublish/ # Data Machine handler implementation
│   │   ├── WordPressRecipePublish.php   # Main handler class
│   │   ├── WordPressRecipePublishFilters.php  # Filter registration
│   │   └── WordPressRecipePublishSettings.php # Configuration
│   └── blocks/recipe-schema/            # Gutenberg block implementation
│       ├── block.json                   # Schema.org Recipe attributes
│       ├── recipe-schema.php            # Block registration/rendering
│       └── index.php                    # Block initialization
├── README.MD                            # Plugin documentation
└── .claude/
    └── recipe-schema.md                 # Schema.org Recipe reference
```

## Implementation Status

### Handler Registration ✅
The `WordPressRecipePublishFilters.php` file is fully implemented and registers the handler with Data Machine's filter-based discovery system via `dm_handlers`, `ai_tools`, `dm_handler_settings`, and `dm_handler_directives` filters.

### AI Tool Integration ✅
The handler fully implements the `handle_tool_call()` method with comprehensive parameter processing, WordPress post creation, Recipe Schema block embedding, error handling, and detailed success/failure responses for AI agents.

### Gutenberg Block Implementation ✅ 
Recipe Schema block is fully implemented with comprehensive Schema.org support:
- `block.json` - Complete attribute definition matching Schema.org Recipe specification
- `recipe-schema.php` - Server-side rendering with microdata and JSON-LD output
- Supports all core properties: timing, ingredients, instructions, nutrition, media, equipment
- Includes rating system integration via WordPress post meta
- Duration formatting utility for human-readable time display

### Build System ✅
Production build system is fully implemented:
- `build.sh` - Complete production ZIP creation with validation
- `composer.json` - PHP dependency management with PSR-4 autoloading and linting scripts
- `.buildignore` - Development file exclusion patterns for clean distribution
- Automated file validation ensures all essential files are included in builds


## Data Machine Integration Points

### Pipeline Flow Integration
With full implementation complete, recipes can be processed through Data Machine pipelines:
1. **Fetch Handler** retrieves recipe data from external sources
2. **AI Processing** transforms and enhances recipe content  
3. **Recipe Publish Handler** creates WordPress posts with Schema.org markup
4. **Scheduling** allows automated recipe publishing workflows

The plugin provides complete agentic recipe publishing capabilities with comprehensive Schema.org structured data support.

### Multi-Provider AI Support
The handler integrates with Data Machine's AI infrastructure supporting:
- OpenAI, Anthropic, Google, Grok, OpenRouter providers
- Tool-first agentic execution
- Structured data extraction and validation