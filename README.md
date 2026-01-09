# WordPress Post Processor - AI-Powered Content Enhancement

A powerful WordPress plugin that automatically or manually processes posts using multiple AI providers (Anthropic Claude, OpenAI, Google AI) to enhance content with AI-generated improvements, proper media handling using WordPress block format, and historical enrichment.

## Features

- **Multiple AI Providers** - Choose from Anthropic Claude, OpenAI, or Google AI (Gemini)
- **Grammar and Spelling Correction** - Automatically fixes errors while preserving your voice
- **SEO-Friendly Title Generation** - Creates compelling, optimized titles
- **Smart Tag Generation** - Automatically generates 5-10 relevant tags
- **Category Management** - Creates and assigns hierarchical categories
- **Historical Enrichment** - Adds fascinating historical context for mentioned locations
- **WordPress Block Format** - Properly formatted galleries and images using WordPress block editor format
- **Media Optimization** - Handles photo galleries, PDFs, and video embeds
- **Secure API Key Storage** - Encrypted storage using WordPress encryption
- **Comprehensive Logging** - Track all API calls and processing activities
- **Bulk Processing** - Process multiple posts at once
- **Auto-Processing** - Optionally process new posts automatically

## Installation

1. Clone this repository or download the ZIP file
2. Copy the `claude-post-processor` directory to your WordPress `wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin panel
4. Go to Settings > AI Post Processor
5. Select your preferred AI provider (Claude, OpenAI, or Google AI)
6. Enter your API key for the selected provider:
   - **Anthropic Claude**: Get key at https://console.anthropic.com/
   - **OpenAI**: Get key at https://platform.openai.com/api-keys
   - **Google AI**: Get key at https://aistudio.google.com/app/apikey
7. Configure your processing options

## Usage

### Manual Processing

1. Go to Settings > AI Post Processor > Manual Processing
2. Select posts to process
3. Click "Process Selected" or "Process All Unprocessed"

### Bulk Actions

1. Go to Posts > All Posts
2. Select posts using checkboxes
3. Choose "Process with Claude" from the Bulk Actions dropdown
4. Click Apply

### Individual Posts

1. Go to Posts > All Posts
2. Hover over a post
3. Click "Process with Claude"

### Auto-Processing

1. Go to Settings > AI Post Processor > Processing Options
2. Enable "Auto-process New Posts"
3. New posts will be processed automatically when created

## File Structure

```
claude-post-processor/
├── claude-post-processor.php          # Main plugin file
├── includes/
│   ├── interface-ai-provider.php      # AI provider interface
│   ├── class-claude-api.php           # Claude API handler
│   ├── class-openai-provider.php      # OpenAI provider
│   ├── class-google-ai-provider.php   # Google AI provider
│   ├── class-ai-provider-factory.php  # Provider factory
│   ├── class-post-processor.php       # Main processing logic
│   ├── class-media-handler.php        # Photo/PDF/Video handling
│   ├── class-taxonomy-manager.php     # Tags and categories
│   └── class-admin-settings.php       # Settings page
├── admin/
│   ├── css/admin-styles.css           # Admin and frontend styles
│   └── js/admin-scripts.js            # Admin JavaScript
├── readme.txt                          # WordPress.org readme
├── uninstall.php                       # Cleanup on uninstall
└── composer.json                       # Composer configuration
```

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- API key from one of the supported AI providers:
  - Anthropic Claude
  - OpenAI
  - Google AI (Gemini)

## Supported AI Providers

### Anthropic Claude
- Claude Sonnet 4 (Recommended)
- Claude 3.5 Sonnet
- Claude 3 Opus

### OpenAI
- GPT-4o (Recommended)
- GPT-4o Mini
- GPT-4 Turbo
- GPT-4
- GPT-3.5 Turbo

### Google AI (Gemini)
- Gemini 2.0 Flash (Experimental)
- Gemini 1.5 Pro (Recommended)
- Gemini 1.5 Flash
- Gemini 1.0 Pro

## WordPress Block Editor Support

The plugin now fully supports WordPress block editor formatting:

- **Gallery Block Format**: Creates proper `<!-- wp:gallery -->` blocks instead of shortcodes
- **Image Block Format**: Single images are formatted as `<!-- wp:image -->` blocks
- **Preserves Existing Blocks**: If content already contains WordPress blocks, they are preserved
- **Smart Image Detection**: Only adds images that aren't already in the content

## Security

- API keys are encrypted before storage using AES-256-CBC
- All inputs are sanitized and validated
- All outputs are properly escaped
- Nonces are used for all form submissions
- Capability checks on all admin actions

## Privacy

This plugin sends your post content to the selected AI provider's API for processing. Please review the privacy policies:
- [Anthropic's privacy policy](https://www.anthropic.com/privacy)
- [OpenAI's privacy policy](https://openai.com/policies/privacy-policy)
- [Google's privacy policy](https://policies.google.com/privacy)

The plugin stores:
- Encrypted API keys in your WordPress database
- Processing logs in `wp-content/uploads/claude-processor-logs/`
- Post metadata about processing status

No data is sent to any third party except your selected AI provider's API.

## Support

For issues, feature requests, or contributions, please visit:
https://github.com/gerry421/WordPress-Post-Processor/issues

## License

GPL-2.0+