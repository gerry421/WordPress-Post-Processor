# Multi-AI Provider Support & WordPress Block Formatting

This document describes the new features added to the WordPress Post Processor plugin.

## Overview of Changes

This update addresses the following requirements:
1. WordPress block formatting compatibility (preserving `<!-- wp:gallery -->` and other block tags)
2. Proper handling of photos already in galleries
3. Replacing non-working gallery shortcode with WordPress block format
4. Support for multiple AI providers and models

## Multi-AI Provider Support

### Supported Providers

The plugin now supports three major AI providers:

#### 1. Anthropic Claude
- **API Key**: Required from https://console.anthropic.com/
- **Models**:
  - Claude Sonnet 4 (claude-sonnet-4-20250514) - Recommended
  - Claude 3.5 Sonnet (claude-3-5-sonnet-20241022)
  - Claude 3 Opus (claude-3-opus-20240229)

#### 2. OpenAI
- **API Key**: Required from https://platform.openai.com/api-keys
- **Models**:
  - GPT-4o (gpt-4o) - Recommended
  - GPT-4o Mini (gpt-4o-mini)
  - GPT-4 Turbo (gpt-4-turbo)
  - GPT-4 (gpt-4)
  - GPT-3.5 Turbo (gpt-3.5-turbo)

#### 3. Google AI (Gemini)
- **API Key**: Required from https://aistudio.google.com/app/apikey
- **Models**:
  - Gemini 2.0 Flash (gemini-2.0-flash-exp) - Experimental
  - Gemini 1.5 Pro (gemini-1.5-pro-latest) - Recommended
  - Gemini 1.5 Flash (gemini-1.5-flash-latest)
  - Gemini 1.0 Pro (gemini-1.0-pro)

### How to Configure

1. Go to **Settings > Claude Post Processor > API Configuration**
2. Select your preferred AI provider from the dropdown
3. Enter the API key for your selected provider
4. Choose the model you want to use
5. Save changes

The plugin will dynamically show/hide the relevant API key and model fields based on your provider selection.

## WordPress Block Formatting

### Gallery Block Format

Previously, the plugin used WordPress shortcodes for galleries:
```
[gallery ids="1,2,3" columns="3" link="file" size="large"]
```

Now it uses proper WordPress block editor format:
```html
<!-- wp:gallery {"linkTo":"none","sizeSlug":"large","className":"wp-block-gallery-1"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped wp-block-gallery-1">
  <figure class="wp-block-image size-large">
    <img src="..." alt="..." data-id="1" class="wp-image-1"/>
    <figcaption class="wp-element-caption">Caption</figcaption>
  </figure>
  <!-- More images... -->
</figure>
<!-- /wp:gallery -->
```

### Single Image Block Format

Single images are now formatted as WordPress image blocks:
```html
<!-- wp:image {"id":1,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large">
  <img src="..." alt="..." class="wp-image-1"/>
  <figcaption class="wp-element-caption">Caption</figcaption>
</figure>
<!-- /wp:image -->
```

### Preserving Existing Blocks

The media handler now intelligently detects if content already contains WordPress block formatting:

- If content has `<!-- wp:gallery -->`, `<!-- wp:image -->`, or `<!-- wp:media-text -->` blocks, they are preserved
- Only images not already in the content are added
- Images already in galleries are left alone

This ensures that manually created galleries and image layouts are not disrupted.

### Smart Image Detection

The plugin now:
1. Checks if WordPress block formatting already exists in the content
2. Extracts image IDs from existing blocks and shortcodes
3. Only processes images that are not already present in the content
4. Preserves all captions and alt text

## Technical Implementation

### Provider Interface

All AI providers implement the `AI_Provider` interface with the following methods:
- `get_provider_name()` - Returns the provider name
- `get_api_key()` / `set_api_key()` - API key management with encryption
- `send_message()` - Send prompts to the AI service
- `extract_text()` - Extract text from API responses
- `test_connection()` - Test API connectivity
- `get_available_models()` - List available models
- `get_model()` / `set_model()` - Model selection

### Provider Factory

The `AI_Provider_Factory` class manages provider instantiation:
```php
// Get the current provider
$provider = AI_Provider_Factory::get_current_provider();

// Create a specific provider
$provider = AI_Provider_Factory::create_provider('openai');
```

### Media Handler Updates

The `Media_Handler` class now includes:
- `has_wordpress_blocks()` - Detects WordPress block format
- `get_images_in_content()` - Extracts image IDs from content
- `create_image_block()` - Creates WordPress image blocks
- `create_gallery_block()` - Creates WordPress gallery blocks

## Benefits

### For Users
- **Choice**: Select the AI provider that works best for you
- **Cost Control**: Use different providers based on pricing
- **Reliability**: Switch providers if one has downtime
- **Block Editor**: Proper WordPress block format for better editor experience
- **Preservation**: Existing gallery layouts are not disrupted

### For Developers
- **Extensible**: Easy to add new AI providers
- **Interface-based**: Consistent API across providers
- **Maintainable**: Separated concerns with factory pattern
- **Standard Format**: Uses WordPress native block format

## Migration Notes

### From Previous Version

If you're upgrading from a previous version:

1. **API Keys**: Existing Claude API keys will continue to work
2. **Default Provider**: Claude remains the default provider
3. **Existing Posts**: Posts processed with shortcodes will continue to work, but new posts will use block format
4. **Settings**: No settings migration required

### Block Format Conversion

To convert existing shortcode galleries to block format:
1. Edit the post in the block editor
2. Click on the gallery shortcode
3. WordPress will offer to convert it to a gallery block
4. Save the post

Alternatively, let the plugin reprocess the post to use the new block format.

## Troubleshooting

### Provider Connection Issues

If you encounter API connection issues:
1. Verify your API key is correct
2. Check that your account has sufficient credits/quota
3. Try the test connection feature in settings
4. Review logs in `wp-content/uploads/claude-processor-logs/`

### Block Format Issues

If images or galleries don't display correctly:
1. Ensure your theme supports WordPress blocks
2. Check that the post was saved in the block editor
3. Clear any caching plugins
4. Try switching to a default WordPress theme temporarily

### Model Selection

If a specific model isn't working:
1. Verify the model is available for your API tier
2. Check provider documentation for model deprecations
3. Try the recommended model for your provider
4. Review error logs for specific error messages

## Future Enhancements

Potential future improvements:
- Support for additional AI providers (Cohere, Hugging Face, etc.)
- Custom prompt templates per provider
- Rate limiting and quota management
- Provider-specific optimization settings
- Batch processing with mixed providers
- A/B testing between providers
