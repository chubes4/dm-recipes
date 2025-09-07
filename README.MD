# Data Machine Recipes

A WordPress plugin that extends Data Machine with comprehensive recipe publishing capabilities, featuring full Schema.org structured data support and agentic AI integration.

## Features

- **WordPress Recipe Publish Handler**: AI-powered recipe publishing through Data Machine's Pipeline+Flow system
- **Complete Schema.org Recipe Support**: Full implementation of Schema.org Recipe markup with JSON-LD and microdata
- **Gutenberg Recipe Schema Block**: Native WordPress block for recipe structured data
- **Multi-Provider AI Integration**: Compatible with OpenAI, Anthropic, Google, Grok, and OpenRouter
- **Comprehensive Recipe Attributes**: Support for ingredients, instructions, timing, nutrition, dietary restrictions, and more
- **SEO Optimized**: Rich snippets and search engine optimization through proper structured data

## Requirements

- WordPress 5.0+
- PHP 7.4+
- [Data Machine](https://github.com/chubes4/data-machine) plugin
- Composer (for development)

## Installation

### From Release ZIP
1. Download the latest release ZIP file
2. Upload to WordPress via **Plugins > Add New > Upload Plugin**
3. Activate the plugin
4. Ensure Data Machine plugin is also installed and activated

### Development Installation
```bash
# Clone the repository
git clone https://github.com/chubes4/dm-recipes.git
cd dm-recipes

# Install dependencies
composer install

# Create production build
./build.sh
```

## How It Works

### AI-Powered Recipe Publishing
1. **Data Machine Integration**: The plugin registers a `wordpress_recipe_publish` handler with Data Machine's filter-based discovery system
2. **AI Tool Registration**: Provides the `recipe_publish` AI tool with comprehensive Schema.org Recipe parameters
3. **Automated Publishing**: AI agents can create WordPress posts with embedded Recipe Schema blocks containing complete structured data
4. **Schema.org Compliance**: Generated posts include both JSON-LD and microdata markup for optimal SEO

### Recipe Schema Block
- **Complete Attribute Support**: All Schema.org Recipe properties including nutrition, dietary restrictions, cooking methods, and video content
- **Gutenberg Integration**: Native WordPress block editor integration
- **Structured Data Output**: Automatic generation of JSON-LD structured data and HTML microdata
- **Search Engine Optimization**: Rich snippets and enhanced search results

## Usage

### Through Data Machine Pipelines
Configure a Data Machine pipeline with:
1. **Fetch Handler**: Retrieve recipe data from external sources
2. **AI Processing**: Transform and enhance recipe content
3. **WordPress Recipe Publish Handler**: Create WordPress posts with Schema.org markup

### Manual Block Usage
Add the "Recipe Schema" block to any WordPress post or page and fill in the recipe details for automatic structured data generation.

## AI Tool Parameters

The `recipe_publish` tool accepts comprehensive recipe data:

- **Post Content**: `post_title`, `post_content`, `post_status`, `post_author`, `post_category`
- **Recipe Basics**: `recipeName`, `description`, `images`, `author`
- **Timing**: `prepTime`, `cookTime`, `totalTime` (ISO 8601 format)
- **Content**: `recipeIngredient[]`, `recipeInstructions[]`, `recipeYield`
- **Classification**: `recipeCategory[]`, `recipeCuisine`, `cookingMethod`, `keywords[]`
- **Dietary**: `suitableForDiet[]`, `nutrition{}`
- **Media**: `video{}`, `images[]`
- **Equipment**: `tool[]`, `supply[]`
- **Additional**: `estimatedCost`, `datePublished`

## Development

### Commands
```bash
# Install dependencies
composer install

# Run PHP linting
composer lint

# Fix coding standards
composer lint:fix

# Create production build
./build.sh
```

### Build System
- **Automated ZIP Creation**: Production-ready plugin packages
- **Dependency Management**: Composer-based PHP dependency handling
- **Code Quality**: PHP CodeSniffer with WordPress coding standards
- **File Validation**: Build process validates all required files are present

## Architecture

### Handler Registration
Self-registering via WordPress filters:
- `dm_handlers` - Handler discovery
- `ai_tools` - AI tool registration
- `dm_handler_settings` - Configuration management
- `dm_handler_directives` - AI model guidance

### File Structure
```
dm-recipes/
├── dm-recipes.php                       # Main plugin file
├── build.sh                             # Production build script
├── composer.json                        # Dependencies and autoloading
├── inc/
│   ├── handlers/WordPressRecipePublish/ # Data Machine handler
│   │   ├── WordPressRecipePublish.php   # Main handler implementation
│   │   ├── WordPressRecipePublishFilters.php  # Filter registration
│   │   └── WordPressRecipePublishSettings.php # Configuration
│   └── blocks/recipe-schema/            # Gutenberg block
│       ├── block.json                   # Schema.org Recipe attributes
│       ├── recipe-schema.php            # Block registration/rendering
│       └── index.php                    # Block initialization
```


## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow WordPress coding standards
4. Run `composer lint` before committing
5. Submit a pull request

## License

GPL-2.0-or-later - See LICENSE file for details.

## Author

**Chris Huber**
- Website: [chubes.net](https://chubes.net)
- GitHub: [@chubes4](https://github.com/chubes4) 