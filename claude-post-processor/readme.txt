=== AI Post Processor ===
Contributors: gerry421
Tags: ai, artificial-intelligence, post-processing, content-enhancement, openai, claude, gemini
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Automatically or manually processes WordPress posts using AI (Anthropic Claude, OpenAI, or Google Gemini) to enhance posts with AI-generated improvements.

== Description ==

AI Post Processor is a powerful WordPress plugin that leverages multiple AI providers (Anthropic Claude, OpenAI, or Google Gemini) to automatically enhance your blog posts with:

* **Grammar and Spelling Correction** - Automatically fixes grammar errors, spelling mistakes, and punctuation issues
* **Natural Titles** - Generates conversational, engaging titles based on your content
* **Smart Tags** - Creates relevant tags automatically based on topics, themes, and locations
* **Category Management** - Suggests and creates appropriate categories, including hierarchical ones
* **Historical Enrichment with RAG** - Adds historical context enriched with links to related posts, tags, and categories
* **Media Optimization** - Properly handles photo galleries, PDFs, and video embeds

= Features =

* **Multiple AI Providers** - Choose from Anthropic Claude, OpenAI, or Google Gemini
* **Automatic Processing** - Optionally process new posts automatically when created
* **Manual Processing** - Process existing posts individually or in bulk
* **Secure API Key Storage** - API keys are encrypted before storage
* **Rate Limiting** - Built-in exponential backoff to respect API limits
* **Comprehensive Logging** - Track all API calls and processing activities
* **Email Notifications** - Get notified when posts are processed
* **Dashboard Widget** - See processing status at a glance
* **Draft Status** - Processed posts are set to draft for review before publishing

= Media Handling =

* **Photo Galleries** - Automatically creates WordPress block galleries from multiple images
* **Single Images** - Creates proper WordPress image blocks
* **PDF Embedding** - Displays PDFs inline with fallback download links
* **YouTube Videos** - Converts YouTube links to responsive embeds
* **Direct Videos** - Handles MP4, WebM, and MOV files with HTML5 video player

= Historical Enrichment with RAG =

When your posts mention places, landmarks, or historical sites, AI Post Processor can:

* Add historical context (2-3 sentences) with specific dates and events
* Include interesting, lesser-known facts
* Link to related posts on your site
* Reference relevant tags and categories
* Use existing content to enrich and inform the historical context
* Format beautifully with WordPress quote blocks

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/claude-post-processor` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > AI Post Processor to configure the plugin
4. Select your preferred AI provider (Claude, OpenAI, or Google AI)
5. Enter your API key for the selected provider
6. Configure your processing options
7. Start processing posts!

== Frequently Asked Questions ==

= Where do I get an API key? =

Depending on your chosen AI provider:
* **Anthropic Claude**: https://console.anthropic.com/
* **OpenAI**: https://platform.openai.com/api-keys
* **Google AI**: https://aistudio.google.com/app/apikey

= How much does the API cost? =

Pricing varies by provider and model:
* **Anthropic**: https://www.anthropic.com/pricing
* **OpenAI**: https://openai.com/pricing
* **Google AI**: https://ai.google.dev/pricing

= Will this work with my theme? =

Yes! The plugin uses standard WordPress functions and outputs standard HTML. The media embeds use responsive CSS that works with most themes.

= Can I review posts before they're published? =

Yes! All processed posts are set to "Draft" status so you can review them before publishing.

= Can I restore the original content? =

Yes! The plugin backs up your original content and title in post meta fields before processing.

= Does this work with Gutenberg? =

Yes, the plugin works with both the Classic Editor and Gutenberg (Block Editor). Images and galleries are created using WordPress block format.

== Screenshots ==

1. Settings page - API Configuration
2. Settings page - Processing Options
3. Manual Processing interface
4. Dashboard Widget
5. Posts list with processing status
6. Historical enrichment example with related content

== Changelog ==

= 1.0.0 =
* Initial release
* Multi-provider support (Claude, OpenAI, Google AI)
* Grammar and spelling correction
* Natural, conversational title generation
* Tag and category generation
* Historical enrichment with RAG-type analysis
* Related content links and references
* Media handling (photos, PDFs, videos)
* WordPress block format support
* Auto and manual processing
* Comprehensive logging
* Email notifications
* Dashboard widget

== Upgrade Notice ==

= 1.0.0 =
Initial release of AI Post Processor with multi-provider support.

== Privacy Policy ==

This plugin sends your post content to your selected AI provider's API for processing. Please review the privacy policies:
* Anthropic: https://www.anthropic.com/privacy
* OpenAI: https://openai.com/policies/privacy-policy
* Google: https://policies.google.com/privacy

The plugin stores:
* Encrypted API keys in your WordPress database
* Processing logs in wp-content/uploads/claude-processor-logs/
* Post metadata about processing status

No data is sent to any third party except your selected AI provider's API.

== Support ==

For support, please visit https://github.com/gerry421/WordPress-Post-Processor/issues
