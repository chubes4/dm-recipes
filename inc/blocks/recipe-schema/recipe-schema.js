/**
 * Recipe Schema Block - Editor Interface
 * 
 * React-based Gutenberg block editor interface for Schema.org Recipe data
 * with comprehensive field groups and validation
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { 
    PanelBody, 
    TextControl, 
    TextareaControl, 
    Button, 
    SelectControl, 
    ToggleControl,
    Notice,
    __experimentalNumberControl: NumberControl
} = wp.components;
const { useState, useEffect } = wp.element;
const { __ } = wp.i18n;

/**
 * Duration Input Component
 * Helper component for ISO 8601 duration fields
 */
const DurationInput = ({ label, value, onChange }) => {
    const [hours, setHours] = useState(0);
    const [minutes, setMinutes] = useState(0);
    
    // Parse existing ISO 8601 duration on component mount
    useEffect(() => {
        if (value && value.startsWith('PT')) {
            const duration = value.substring(2);
            const hourMatch = duration.match(/(\d+)H/);
            const minuteMatch = duration.match(/(\d+)M/);
            
            if (hourMatch) setHours(parseInt(hourMatch[1]));
            if (minuteMatch) setMinutes(parseInt(minuteMatch[1]));
        }
    }, []);
    
    // Update parent when hours/minutes change
    useEffect(() => {
        let newValue = 'PT';
        if (hours > 0) newValue += hours + 'H';
        if (minutes > 0) newValue += minutes + 'M';
        if (newValue === 'PT') newValue = '';
        
        onChange(newValue);
    }, [hours, minutes]);
    
    return (
        <div className="recipe-duration-input">
            <label>{label}</label>
            <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                <NumberControl
                    label={__('Hours', 'dm-recipes')}
                    value={hours}
                    min={0}
                    onChange={(newHours) => setHours(parseInt(newHours) || 0)}
                />
                <NumberControl
                    label={__('Minutes', 'dm-recipes')}
                    value={minutes}
                    min={0}
                    max={59}
                    onChange={(newMinutes) => setMinutes(parseInt(newMinutes) || 0)}
                />
            </div>
        </div>
    );
};

/**
 * Array Input Component
 * Helper component for managing arrays of strings (ingredients, instructions, etc.)
 */
const ArrayInput = ({ label, items, onChange, placeholder }) => {
    const addItem = () => {
        onChange([...items, '']);
    };
    
    const updateItem = (index, value) => {
        const newItems = [...items];
        newItems[index] = value;
        onChange(newItems);
    };
    
    const removeItem = (index) => {
        const newItems = items.filter((_, i) => i !== index);
        onChange(newItems);
    };
    
    return (
        <div className="recipe-array-input">
            <label>{label}</label>
            {items.map((item, index) => (
                <div key={index} style={{ display: 'flex', gap: '10px', marginBottom: '10px' }}>
                    <TextareaControl
                        value={item}
                        onChange={(value) => updateItem(index, value)}
                        placeholder={placeholder}
                        rows={2}
                    />
                    <Button
                        isSecondary
                        isDestructive
                        onClick={() => removeItem(index)}
                    >
                        {__('Remove', 'dm-recipes')}
                    </Button>
                </div>
            ))}
            <Button isPrimary onClick={addItem}>
                {__('Add Item', 'dm-recipes')}
            </Button>
        </div>
    );
};

/**
 * Tag Input Component
 * Helper component for managing arrays of tags/keywords
 */
const TagInput = ({ label, tags, onChange }) => {
    const [inputValue, setInputValue] = useState('');
    
    const addTag = () => {
        if (inputValue.trim() && !tags.includes(inputValue.trim())) {
            onChange([...tags, inputValue.trim()]);
            setInputValue('');
        }
    };
    
    const removeTag = (indexToRemove) => {
        onChange(tags.filter((_, index) => index !== indexToRemove));
    };
    
    const handleKeyPress = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addTag();
        }
    };
    
    return (
        <div className="recipe-tag-input">
            <label>{label}</label>
            <div style={{ marginBottom: '10px' }}>
                {tags.map((tag, index) => (
                    <span key={index} className="recipe-tag" style={{ 
                        display: 'inline-block', 
                        background: '#e0e0e0', 
                        padding: '2px 8px', 
                        margin: '2px', 
                        borderRadius: '3px' 
                    }}>
                        {tag}
                        <button 
                            onClick={() => removeTag(index)}
                            style={{ marginLeft: '5px', background: 'none', border: 'none', cursor: 'pointer' }}
                        >
                            ×
                        </button>
                    </span>
                ))}
            </div>
            <div style={{ display: 'flex', gap: '10px' }}>
                <TextControl
                    value={inputValue}
                    onChange={setInputValue}
                    onKeyPress={handleKeyPress}
                    placeholder={__('Enter tag and press Enter', 'dm-recipes')}
                />
                <Button isSecondary onClick={addTag}>
                    {__('Add', 'dm-recipes')}
                </Button>
            </div>
        </div>
    );
};

/**
 * Register Recipe Schema Block
 */
registerBlockType('dm-recipes/recipe-schema', {
    title: __('Recipe Schema', 'dm-recipes'),
    icon: 'food',
    category: 'common',
    description: __('Complete Schema.org Recipe structured data block', 'dm-recipes'),
    
    edit: ({ attributes, setAttributes }) => {
        const {
            recipeName,
            description,
            images,
            prepTime,
            cookTime,
            totalTime,
            recipeYield,
            recipeCategory,
            recipeCuisine,
            recipeIngredient,
            recipeInstructions,
            nutrition,
            suitableForDiet,
            keywords,
            cookingMethod,
            video,
            author,
            estimatedCost,
            tool,
            supply
        } = attributes;
        
        // Cuisine options
        const cuisineOptions = [
            { label: __('Select Cuisine', 'dm-recipes'), value: '' },
            { label: __('American', 'dm-recipes'), value: 'American' },
            { label: __('Italian', 'dm-recipes'), value: 'Italian' },
            { label: __('Mexican', 'dm-recipes'), value: 'Mexican' },
            { label: __('Chinese', 'dm-recipes'), value: 'Chinese' },
            { label: __('Indian', 'dm-recipes'), value: 'Indian' },
            { label: __('French', 'dm-recipes'), value: 'French' },
            { label: __('Mediterranean', 'dm-recipes'), value: 'Mediterranean' },
            { label: __('Asian', 'dm-recipes'), value: 'Asian' },
            { label: __('European', 'dm-recipes'), value: 'European' },
            { label: __('Other', 'dm-recipes'), value: 'Other' }
        ];
        
        // Diet options
        const dietOptions = [
            'DiabeticDiet', 'GlutenFreeDiet', 'HalaalDiet', 'HinduDiet', 'KosherDiet', 
            'LowCalorieDiet', 'LowFatDiet', 'LowLactoseDiet', 'LowSaltDiet', 'VeganDiet', 
            'VegetarianDiet'
        ];
        
        return (
            <>
                <InspectorControls>
                    {/* Basic Information */}
                    <PanelBody title={__('Basic Information', 'dm-recipes')} initialOpen={true}>
                        <TextControl
                            label={__('Recipe Name', 'dm-recipes')}
                            value={recipeName}
                            onChange={(value) => setAttributes({ recipeName: value })}
                        />
                        
                        <TextareaControl
                            label={__('Description', 'dm-recipes')}
                            value={description}
                            onChange={(value) => setAttributes({ description: value })}
                            rows={4}
                        />
                        
                        <MediaUploadCheck>
                            <MediaUpload
                                onSelect={(media) => {
                                    const newImages = Array.isArray(media) ? media : [media];
                                    setAttributes({ 
                                        images: newImages.map(img => ({
                                            id: img.id,
                                            url: img.url,
                                            alt: img.alt || img.title
                                        }))
                                    });
                                }}
                                allowedTypes={['image']}
                                multiple={true}
                                value={images.map(img => img.id)}
                                render={({ open }) => (
                                    <Button isPrimary onClick={open}>
                                        {images.length > 0 
                                            ? __('Change Images', 'dm-recipes')
                                            : __('Add Images', 'dm-recipes')
                                        }
                                    </Button>
                                )}
                            />
                        </MediaUploadCheck>
                        
                        {images.length > 0 && (
                            <div style={{ marginTop: '10px' }}>
                                {images.map((image, index) => (
                                    <img key={index} src={image.url} alt={image.alt} 
                                         style={{ width: '100px', height: '100px', objectFit: 'cover', margin: '5px' }} />
                                ))}
                            </div>
                        )}
                    </PanelBody>
                    
                    {/* Timing */}
                    <PanelBody title={__('Timing', 'dm-recipes')} initialOpen={false}>
                        <DurationInput
                            label={__('Prep Time', 'dm-recipes')}
                            value={prepTime}
                            onChange={(value) => setAttributes({ prepTime: value })}
                        />
                        
                        <DurationInput
                            label={__('Cook Time', 'dm-recipes')}
                            value={cookTime}
                            onChange={(value) => setAttributes({ cookTime: value })}
                        />
                        
                        <DurationInput
                            label={__('Total Time', 'dm-recipes')}
                            value={totalTime}
                            onChange={(value) => setAttributes({ totalTime: value })}
                        />
                        
                        <TextControl
                            label={__('Yield (servings)', 'dm-recipes')}
                            value={recipeYield}
                            onChange={(value) => setAttributes({ recipeYield: value })}
                            placeholder="e.g., 4 servings, 12 cookies"
                        />
                    </PanelBody>
                    
                    {/* Categories & Cuisine */}
                    <PanelBody title={__('Categories & Cuisine', 'dm-recipes')} initialOpen={false}>
                        <TagInput
                            label={__('Recipe Categories', 'dm-recipes')}
                            tags={recipeCategory}
                            onChange={(value) => setAttributes({ recipeCategory: value })}
                        />
                        
                        <SelectControl
                            label={__('Cuisine', 'dm-recipes')}
                            value={recipeCuisine}
                            options={cuisineOptions}
                            onChange={(value) => setAttributes({ recipeCuisine: value })}
                        />
                        
                        <TextControl
                            label={__('Cooking Method', 'dm-recipes')}
                            value={cookingMethod}
                            onChange={(value) => setAttributes({ cookingMethod: value })}
                            placeholder="e.g., Baking, Frying, Grilling"
                        />
                    </PanelBody>
                    
                    {/* Ingredients */}
                    <PanelBody title={__('Ingredients', 'dm-recipes')} initialOpen={false}>
                        <ArrayInput
                            label={__('Recipe Ingredients', 'dm-recipes')}
                            items={recipeIngredient}
                            onChange={(value) => setAttributes({ recipeIngredient: value })}
                            placeholder="e.g., 1 cup flour, 2 eggs, 1/2 cup sugar"
                        />
                    </PanelBody>
                    
                    {/* Instructions */}
                    <PanelBody title={__('Instructions', 'dm-recipes')} initialOpen={false}>
                        <ArrayInput
                            label={__('Recipe Instructions', 'dm-recipes')}
                            items={recipeInstructions}
                            onChange={(value) => setAttributes({ recipeInstructions: value })}
                            placeholder="Enter each step of the recipe"
                        />
                    </PanelBody>
                    
                    {/* Nutrition */}
                    <PanelBody title={__('Nutrition Information', 'dm-recipes')} initialOpen={false}>
                        <TextControl
                            label={__('Calories', 'dm-recipes')}
                            value={nutrition.calories || ''}
                            onChange={(value) => setAttributes({ 
                                nutrition: { ...nutrition, calories: value } 
                            })}
                            placeholder="e.g., 250 calories"
                        />
                        
                        <TextControl
                            label={__('Carbohydrates', 'dm-recipes')}
                            value={nutrition.carbohydrateContent || ''}
                            onChange={(value) => setAttributes({ 
                                nutrition: { ...nutrition, carbohydrateContent: value } 
                            })}
                            placeholder="e.g., 30g"
                        />
                        
                        <TextControl
                            label={__('Protein', 'dm-recipes')}
                            value={nutrition.proteinContent || ''}
                            onChange={(value) => setAttributes({ 
                                nutrition: { ...nutrition, proteinContent: value } 
                            })}
                            placeholder="e.g., 15g"
                        />
                        
                        <TextControl
                            label={__('Fat', 'dm-recipes')}
                            value={nutrition.fatContent || ''}
                            onChange={(value) => setAttributes({ 
                                nutrition: { ...nutrition, fatContent: value } 
                            })}
                            placeholder="e.g., 10g"
                        />
                        
                        <TextControl
                            label={__('Serving Size', 'dm-recipes')}
                            value={nutrition.servingSize || ''}
                            onChange={(value) => setAttributes({ 
                                nutrition: { ...nutrition, servingSize: value } 
                            })}
                            placeholder="e.g., 1 cup"
                        />
                    </PanelBody>
                    
                    {/* Additional Information */}
                    <PanelBody title={__('Additional Information', 'dm-recipes')} initialOpen={false}>
                        <TagInput
                            label={__('Keywords/Tags', 'dm-recipes')}
                            tags={keywords}
                            onChange={(value) => setAttributes({ keywords: value })}
                        />
                        
                        <TagInput
                            label={__('Suitable for Diet', 'dm-recipes')}
                            tags={suitableForDiet}
                            onChange={(value) => setAttributes({ suitableForDiet: value })}
                        />
                        
                        <TextControl
                            label={__('Estimated Cost', 'dm-recipes')}
                            value={estimatedCost}
                            onChange={(value) => setAttributes({ estimatedCost: value })}
                            placeholder="e.g., $15, £10"
                        />
                        
                        <ArrayInput
                            label={__('Tools/Equipment', 'dm-recipes')}
                            items={tool}
                            onChange={(value) => setAttributes({ tool: value })}
                            placeholder="e.g., mixing bowl, whisk, oven"
                        />
                    </PanelBody>
                </InspectorControls>
                
                {/* Block Content in Editor */}
                <div className="recipe-schema-block-editor">
                    <h3>{__('Recipe Schema Block', 'dm-recipes')}</h3>
                    
                    {recipeName ? (
                        <h4>{recipeName}</h4>
                    ) : (
                        <p style={{ color: '#888' }}>
                            {__('Configure your recipe in the sidebar panel →', 'dm-recipes')}
                        </p>
                    )}
                    
                    {description && (
                        <p>{description}</p>
                    )}
                    
                    {recipeIngredient.length > 0 && (
                        <div>
                            <strong>{__('Ingredients:', 'dm-recipes')}</strong>
                            <ul>
                                {recipeIngredient.slice(0, 3).map((ingredient, index) => (
                                    <li key={index}>{ingredient}</li>
                                ))}
                                {recipeIngredient.length > 3 && (
                                    <li>
                                        <em>
                                            {__('+ ' + (recipeIngredient.length - 3) + ' more ingredients', 'dm-recipes')}
                                        </em>
                                    </li>
                                )}
                            </ul>
                        </div>
                    )}
                    
                    {recipeInstructions.length > 0 && (
                        <div>
                            <strong>{__('Instructions:', 'dm-recipes')}</strong>
                            <ol>
                                {recipeInstructions.slice(0, 2).map((instruction, index) => (
                                    <li key={index}>{instruction.substring(0, 100)}...</li>
                                ))}
                                {recipeInstructions.length > 2 && (
                                    <li>
                                        <em>
                                            {__('+ ' + (recipeInstructions.length - 2) + ' more steps', 'dm-recipes')}
                                        </em>
                                    </li>
                                )}
                            </ol>
                        </div>
                    )}
                    
                    <Notice status="info" isDismissible={false}>
                        {__('This block will generate complete Schema.org structured data for your recipe.', 'dm-recipes')}
                    </Notice>
                </div>
            </>
        );
    },
    
    save: () => {
        // Block rendering is handled by PHP
        return null;
    }
});