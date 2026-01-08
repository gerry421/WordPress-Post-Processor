# WordPress Post Processor - Claude AI Plugin

A powerful WordPress plugin that automatically or manually processes posts using the Anthropic Claude API to enhance content with AI-generated improvements, proper media handling, and historical enrichment.

## Features

- **Grammar and Spelling Correction** - Automatically fixes errors while preserving your voice
- **SEO-Friendly Title Generation** - Creates compelling, optimized titles
- **Smart Tag Generation** - Automatically generates 5-10 relevant tags
- **Category Management** - Creates and assigns hierarchical categories
- **Historical Enrichment** - Adds fascinating historical context for mentioned locations
- **Media Optimization** - Handles photo galleries, PDFs, and video embeds
- **Secure API Key Storage** - Encrypted storage using WordPress encryption
- **Comprehensive Logging** - Track all API calls and processing activities
- **Bulk Processing** - Process multiple posts at once
- **Auto-Processing** - Optionally process new posts automatically

## Installation

1. Clone this repository or download the ZIP file
2. Copy the `claude-post-processor` directory to your WordPress `wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin panel
4. Go to Settings > Claude Post Processor
5. Enter your Anthropic API key (get one at https://console.anthropic.com/)
6. Configure your processing options

## Usage

### Manual Processing

1. Go to Settings > Claude Post Processor > Manual Processing
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

1. Go to Settings > Claude Post Processor > Processing Options
2. Enable "Auto-process New Posts"
3. New posts will be processed automatically when created

## File Structure

```
claude-post-processor/
├── claude-post-processor.php          # Main plugin file
├── includes/
│   ├── class-claude-api.php           # Claude API handler
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
- Anthropic API key

## Security

- API keys are encrypted before storage
- All inputs are sanitized and validated
- All outputs are properly escaped
- Nonces are used for all form submissions
- Capability checks on all admin actions

## Privacy

This plugin sends your post content to the Anthropic API for processing. Please review [Anthropic's privacy policy](https://www.anthropic.com/privacy).

The plugin stores:
- Encrypted API keys in your WordPress database
- Processing logs in `wp-content/uploads/claude-processor-logs/`
- Post metadata about processing status

No data is sent to any third party except Anthropic's API.

## Support

For issues, feature requests, or contributions, please visit:
https://github.com/gerry421/WordPress-Post-Processor/issues

## License

GPL-2.0+