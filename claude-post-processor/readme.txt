=== Claude Post Processor ===
Contributors: gerry421
Tags: ai, claude, anthropic, post-processing, content-enhancement
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Automatically or manually processes WordPress posts using the Anthropic Claude API to enhance posts with AI-generated improvements.

== Description ==

Claude Post Processor is a powerful WordPress plugin that leverages the Anthropic Claude AI to automatically enhance your blog posts with:

* **Grammar and Spelling Correction** - Automatically fixes grammar errors, spelling mistakes, and punctuation issues
* **SEO-Friendly Titles** - Generates compelling, optimized titles based on your content
* **Smart Tags** - Creates relevant tags automatically based on topics, themes, and locations
* **Category Management** - Suggests and creates appropriate categories, including hierarchical ones
* **Historical Enrichment** - Adds fascinating historical context for places and landmarks mentioned in your posts
* **Media Optimization** - Properly handles photo galleries, PDFs, and video embeds

= Features =

* **Automatic Processing** - Optionally process new posts automatically when created
* **Manual Processing** - Process existing posts individually or in bulk
* **Secure API Key Storage** - API keys are encrypted before storage
* **Rate Limiting** - Built-in exponential backoff to respect API limits
* **Comprehensive Logging** - Track all API calls and processing activities
* **Email Notifications** - Get notified when posts are processed
* **Dashboard Widget** - See processing status at a glance
* **Draft Status** - Processed posts are set to draft for review before publishing

= Media Handling =

* **Photo Galleries** - Automatically creates WordPress galleries from multiple images
* **PDF Embedding** - Displays PDFs inline with fallback download links
* **YouTube Videos** - Converts YouTube links to responsive embeds
* **Direct Videos** - Handles MP4, WebM, and MOV files with HTML5 video player

= Historical Enrichment =

When your posts mention places, landmarks, or historical sites, Claude Post Processor can:

* Add historical context (2-3 sentences)
* Include interesting facts visitors might not know
* Highlight relevant historical significance
* Format beautifully with blockquote styling

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/claude-post-processor` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Claude Post Processor to configure the plugin
4. Enter your Anthropic API key (get one at https://console.anthropic.com/)
5. Configure your processing options
6. Start processing posts!

== Frequently Asked Questions ==

= Where do I get an API key? =

You can get an Anthropic API key by signing up at https://console.anthropic.com/

= How much does the API cost? =

Pricing varies by model. Check https://www.anthropic.com/pricing for current rates. The plugin uses Claude Sonnet 4 by default.

= Will this work with my theme? =

Yes! The plugin uses standard WordPress functions and outputs standard HTML. The media embeds use responsive CSS that works with most themes.

= Can I review posts before they're published? =

Yes! All processed posts are set to "Draft" status so you can review them before publishing.

= Can I restore the original content? =

Yes! The plugin backs up your original content and title in post meta fields before processing.

= Does this work with Gutenberg? =

Yes, the plugin works with both the Classic Editor and Gutenberg (Block Editor).

== Screenshots ==

1. Settings page - API Configuration
2. Settings page - Processing Options
3. Manual Processing interface
4. Dashboard Widget
5. Posts list with processing status
6. Historical enrichment example

== Changelog ==

= 1.0.0 =
* Initial release
* Grammar and spelling correction
* Title generation
* Tag and category generation
* Historical enrichment
* Media handling (photos, PDFs, videos)
* Auto and manual processing
* Comprehensive logging
* Email notifications
* Dashboard widget

== Upgrade Notice ==

= 1.0.0 =
Initial release of Claude Post Processor.

== Privacy Policy ==

This plugin sends your post content to the Anthropic API for processing. Please review Anthropic's privacy policy at https://www.anthropic.com/privacy

The plugin stores:
* Encrypted API keys in your WordPress database
* Processing logs in wp-content/uploads/claude-processor-logs/
* Post metadata about processing status

No data is sent to any third party except Anthropic's API.

== Support ==

For support, please visit https://github.com/gerry421/WordPress-Post-Processor/issues
