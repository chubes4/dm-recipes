# Schema.org Recipe Implementation

This document outlines the Schema.org Recipe properties implemented in the DM-Recipes plugin, specifically for the `dm-recipes/recipe-schema` Gutenberg block.

## Block Name
- **Registered Name**: `dm-recipes/recipe-schema`
- **Text Domain**: `dm-recipes`

## Core Recipe Properties

### Basic Information
- `recipeName` (string) - The name of the recipe
- `description` (string) - Description of the recipe
- `images` (array) - Recipe images with URL and alt text
- `author` (object) - Recipe author with name and URL

### Timing (ISO 8601 Duration Format)
- `prepTime` (string) - Preparation time (e.g., "PT30M" for 30 minutes)
- `cookTime` (string) - Cooking time 
- `totalTime` (string) - Total time required

### Recipe Content
- `recipeIngredient` (array) - List of ingredients
- `recipeInstructions` (array) - Step-by-step instructions
- `recipeYield` (string) - Number of servings or yield

### Classification
- `recipeCategory` (array) - Recipe categories (appetizer, entree, etc.)
- `recipeCuisine` (string) - Cuisine type (French, Italian, etc.)
- `cookingMethod` (string) - Method of cooking (frying, steaming, etc.)
- `keywords` (array) - Keywords/tags for the recipe

### Nutritional Information
- `nutrition` (object) - Comprehensive nutrition data
  - `calories` - Calories per serving
  - `carbohydrateContent` - Carbohydrate content
  - `cholesterolContent` - Cholesterol content
  - `fatContent` - Fat content
  - `fiberContent` - Fiber content
  - `proteinContent` - Protein content
  - `saturatedFatContent` - Saturated fat content
  - `servingSize` - Serving size
  - `sodiumContent` - Sodium content
  - `sugarContent` - Sugar content
  - `transFatContent` - Trans fat content
  - `unsaturatedFatContent` - Unsaturated fat content

### Dietary Restrictions
- `suitableForDiet` (array) - Dietary restrictions (vegetarian, gluten-free, etc.)

### Media Content
- `video` (object) - Recipe video information
  - `name` - Video title
  - `description` - Video description
  - `thumbnailUrl` - Video thumbnail URL
  - `contentUrl` - Video content URL
  - `embedUrl` - Video embed URL
  - `uploadDate` - Video upload date
  - `duration` - Video duration (ISO 8601)

### Equipment and Supplies
- `tool` (array) - Tools required for the recipe
- `supply` (array) - Supplies needed for the recipe

### Additional Properties
- `datePublished` (string) - Publication date
- `estimatedCost` (string) - Estimated cost of recipe

## JSON-LD Output

The block automatically generates complete Schema.org Recipe structured data including:

- Basic recipe information (name, description, images)
- Timing information in ISO 8601 format
- Ingredients and instructions with proper HowToStep markup
- Author information from WordPress post author
- Aggregate rating data (if available from post meta)
- Nutritional information
- Video content with VideoObject markup
- All additional properties as structured data

## Microdata HTML Output

The block renders semantic HTML with microdata attributes:
- `itemscope itemtype="https://schema.org/Recipe"`
- Individual `itemprop` attributes for all properties
- HowToStep markup for instructions
- Person markup for author information
- AggregateRating markup for ratings

## Integration with WordPress

### Post Meta Integration
- Automatically includes `rating_value` and `review_count` post meta for aggregate ratings
- Uses WordPress post author data for Schema.org author markup with name and URL
- Falls back to post publication date for `datePublished` if not provided
- Integrates with WordPress user system for author information

### Duration Formatting
- Converts ISO 8601 duration format (PT30M) to human-readable format (30 minutes)
- Handles hours and minutes combinations with proper pluralization
- Supports proper internationalization through `dm-recipes` text domain
- Function: `dm_recipes_format_duration()` handles ISO 8601 parsing and formatting

### Block Registration
- Registered via `dm_recipes_register_recipe_schema_block()` on WordPress `init` hook
- Server-side rendering callback: `dm_recipes_render_recipe_schema_block()`
- JSON-LD generation: `dm_recipes_generate_recipe_jsonld()` for structured data output

## Text Domain Consistency
All user-facing strings use the `dm-recipes` text domain for proper internationalization support.