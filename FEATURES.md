# Claude Post Processor - Features Overview

## Overview

Claude Post Processor is a comprehensive WordPress plugin that leverages Anthropic's Claude AI to automatically enhance blog posts with intelligent improvements, proper media formatting, and enriched historical context.

## Core Features

### 1. AI-Powered Content Enhancement

#### Grammar & Spelling Correction
- **Automatic Error Detection**: Identifies and fixes grammar errors, spelling mistakes, and punctuation issues
- **Style Preservation**: Maintains your unique voice and writing style while making improvements
- **Sentence Structure**: Enhances readability without changing meaning
- **No Manual Work**: All corrections happen automatically during processing

#### SEO-Friendly Title Generation
- **AI-Generated Titles**: Creates compelling, optimized titles based on your content
- **Optimal Length**: Generates titles between 40-60 characters for best SEO results
- **Engaging & Descriptive**: Captures the essence of your content
- **No Clickbait**: Professional, honest titles that accurately represent your content

#### Smart Tag Generation
- **Automatic Creation**: Generates 5-10 relevant tags per post
- **Topic Analysis**: Identifies main topics, themes, people, and places
- **SEO Optimization**: Mix of specific and general terms for better discoverability
- **Instant Assignment**: Tags are created and assigned automatically

#### Intelligent Category Management
- **Contextual Suggestions**: Recommends 1-3 appropriate categories
- **Hierarchical Support**: Creates parent-child category relationships (e.g., Travel > Europe > France)
- **Automatic Creation**: Categories are created if they don't exist
- **Smart Assignment**: Assigns categories that make sense for your content

### 2. Historical Enrichment

#### Location-Based Enhancement
- **Place Detection**: Automatically identifies locations, landmarks, and historical sites in your content
- **Historical Context**: Adds 2-3 sentences of historical background
- **Interesting Facts**: Includes lesser-known details visitors might not know
- **Cultural Significance**: Highlights relevant historical importance

#### Beautiful Formatting
- **Blockquote Styling**: Enrichment displayed in elegant blockquote format
- **Visual Separation**: Horizontal rules separate enrichment from main content
- **Location Icons**: WordPress dashicons enhance visual appeal
- **Gradient Background**: Subtle gradient for professional look

#### Smart Placement
- **Correct Order**: Inserted AFTER main narrative
- **Before Media**: Appears BEFORE photo galleries and other media
- **Non-Intrusive**: Only added when locations are actually mentioned

### 3. Advanced Media Handling

#### Photo Gallery Management
- **Single Image**: Displays as featured image with caption
- **Multiple Images**: Automatically creates WordPress native gallery
- **Gallery Shortcode**: `[gallery ids="1,2,3" columns="3" link="file" size="large"]`
- **Caption Preservation**: Original captions and alt text maintained
- **Clean Content**: Individual image tags removed and consolidated

#### PDF Embedding
- **Inline Display**: PDFs embedded directly in posts using `<object>` tag
- **Responsive Container**: Adapts to screen size
- **Fallback Link**: Download option for unsupported browsers
- **Mobile Friendly**: Works on all devices

#### YouTube Video Embedding
- **URL Detection**: Recognizes all YouTube URL formats
  - `youtube.com/watch?v=VIDEO_ID`
  - `youtu.be/VIDEO_ID`
  - `youtube.com/embed/VIDEO_ID`
- **Responsive Embed**: 16:9 aspect ratio container
- **Lazy Loading**: Performance optimization
- **Allowfullscreen**: Full-screen viewing enabled

#### Direct Video Support
- **Multiple Formats**: .mp4, .webm, .mov files
- **HTML5 Player**: Native video player with controls
- **Responsive Design**: 16:9 aspect ratio maintained
- **Browser Compatibility**: Fallback messages for unsupported formats

### 4. Processing Options

#### Auto-Processing
- **Background Processing**: Uses WordPress cron for async processing
- **New Post Detection**: Only processes new posts, not updates
- **Smart Skipping**: Avoids autosaves and revisions
- **Scheduled Execution**: 10-second delay before processing starts

#### Manual Processing
- **Single Post**: Process individual posts from row actions
- **Bulk Actions**: Select and process multiple posts at once
- **Settings Page**: Dedicated interface for manual processing
- **Progress Tracking**: Visual feedback during processing

#### Flexible Control
- **Draft Status**: All processed posts set to draft for review
- **Original Backup**: Content and title backed up before processing
- **Processing Log**: Detailed log of all operations performed
- **Email Notifications**: Optional email when processing completes

### 5. Security Features

#### API Key Protection
- **Encryption**: AES-256-CBC encryption for API keys
- **Secure Storage**: Uses WordPress salts for encryption key
- **Password Field**: API key hidden in admin interface
- **OpenSSL Support**: Uses modern encryption when available

#### WordPress Security
- **Nonces**: All forms protected with WordPress nonces
- **Capability Checks**: 
  - `manage_options` for settings
  - `edit_posts` for processing
- **Input Sanitization**: All inputs sanitized with appropriate functions
- **Output Escaping**: All outputs properly escaped to prevent XSS

#### Data Protection
- **Log Protection**: `.htaccess` file protects log directory
- **Private Logs**: Logs stored outside web root when possible
- **API Validation**: All API responses validated before use

### 6. Error Handling & Logging

#### Comprehensive Error Management
- **Connection Failures**: Gracefully handles API unavailability
- **Rate Limiting**: Exponential backoff with up to 5 retries
- **Invalid Credentials**: Helpful admin notices
- **Timeout Handling**: Saves partial progress
- **Content Length**: Handles oversized content appropriately

#### Detailed Logging
- **API Call Logs**: Every API interaction recorded
- **Error Logs**: Separate error log files
- **Date-Based**: Logs organized by date
- **Admin Access**: View logs from settings page
- **Timestamp**: All entries timestamped

### 7. Admin Interface

#### Settings Page
- **Tabbed Interface**: Clean organization
  - API Configuration
  - Processing Options
  - Manual Processing
  - Logs
- **Intuitive Forms**: Easy to use settings
- **Help Text**: Contextual help for each setting
- **Model Selection**: Choose from available Claude models

#### Dashboard Integration
- **Dashboard Widget**: Shows unprocessed post count
- **Quick Actions**: Process button from dashboard
- **At-a-Glance**: See status without navigating

#### Post List Enhancements
- **Status Column**: Shows processing status for each post
- **Processing Date**: When post was processed
- **Bulk Actions**: Process multiple posts easily
- **Row Actions**: Quick process link per post

### 8. Performance Optimization

#### API Rate Limiting
- **2-Second Delay**: Between API calls within same post
- **Exponential Backoff**: On rate limit errors
- **Sequential Processing**: One post at a time in bulk operations
- **Background Jobs**: Non-blocking for user experience

#### Resource Management
- **Lazy Loading**: Videos load only when needed
- **Transient Caching**: Could be added for API responses
- **Efficient Queries**: Optimized database queries
- **Minimal Dependencies**: Lightweight codebase

### 9. Data Management

#### Post Metadata
- `_claude_processed`: Processing status (boolean)
- `_claude_processed_date`: When processed (timestamp)
- `_claude_original_content`: Backup of original content
- `_claude_original_title`: Backup of original title
- `_claude_processing_log`: Array of operations performed
- `_claude_generated_tags`: Which tags were auto-generated
- `_claude_generated_categories`: Which categories were auto-generated

#### Plugin Options
- `claude_post_processor_api_key`: Encrypted API key
- `claude_post_processor_model`: Selected Claude model
- `claude_post_processor_auto_process`: Auto-processing enabled/disabled
- `claude_post_processor_email_notifications`: Email notifications enabled/disabled

#### Clean Uninstall
- **Complete Cleanup**: Removes all options on uninstall
- **Metadata Removal**: Deletes all post meta
- **Log Deletion**: Removes all log files and directory
- **Cron Cleanup**: Clears scheduled events

### 10. Developer-Friendly

#### WordPress Standards
- **Coding Standards**: Follows WordPress coding standards
- **PHPDoc Comments**: Comprehensive documentation
- **Translation Ready**: All strings use translation functions
- **Action Hooks**: Extensible via WordPress hooks
- **Filter Hooks**: Customizable outputs

#### Modern PHP
- **PHP 8.0+**: Uses modern PHP features
- **Type Safety**: Proper type declarations where appropriate
- **Error Handling**: Exception handling and WP_Error usage
- **Singleton Pattern**: Main class uses singleton for global access

#### Composer Support
- **Autoloading**: Composer autoload configuration
- **Dependencies**: Properly declared in composer.json
- **PSR Standards**: Follows PHP-FIG standards

## Technical Specifications

### Requirements
- **WordPress**: 6.0 or higher
- **PHP**: 8.0 or higher
- **API Key**: Anthropic Claude API key required
- **Internet**: Active internet connection for API calls

### Supported Media
- **Images**: All WordPress-supported image formats
- **PDFs**: PDF documents (embedded inline)
- **Videos**: 
  - YouTube (all URL formats)
  - MP4, WebM, MOV (direct files)

### Supported Models
- Claude Sonnet 4 (claude-sonnet-4-20250514) - Default
- Claude 3.5 Sonnet (claude-3-5-sonnet-20241022)
- Claude 3 Opus (claude-3-opus-20240229)

### API Calls Per Post
Each processed post makes 5 API calls:
1. Grammar and spelling correction
2. Title generation
3. Tag generation
4. Category generation
5. Historical enrichment

## Future Enhancement Possibilities

- **Custom Prompts**: Allow users to customize AI prompts
- **Multi-Language**: Support for non-English content
- **Image Analysis**: Use Claude's vision capabilities for image descriptions
- **Content Summarization**: Auto-generate excerpts
- **Related Posts**: AI-suggested related content
- **SEO Metadata**: Auto-generate meta descriptions
- **Social Media**: Generate social media snippets
- **Content Scheduling**: Schedule processing for off-peak hours
- **Batch Reporting**: Detailed reports for bulk processing
- **Custom Fields**: Support for custom post types and fields

## Use Cases

### Bloggers
- Enhance personal blog posts with professional quality
- Save time on editing and formatting
- Improve SEO with better titles and tags

### Content Teams
- Standardize content quality across multiple authors
- Automate repetitive editing tasks
- Ensure consistent categorization

### Travel Blogs
- Add rich historical context to location posts
- Create beautiful photo galleries automatically
- Enhance storytelling with facts

### Educational Sites
- Provide historical context for educational content
- Maintain high writing standards
- Organize content with smart categorization

### News Sites
- Quick turnaround on breaking news
- Consistent formatting
- Automated tagging and categorization

## Support & Resources

- **Documentation**: Comprehensive README and installation guide
- **Testing Checklist**: Detailed feature verification checklist
- **GitHub Issues**: Bug reports and feature requests
- **Code Quality**: Passes CodeQL security analysis
- **Code Reviews**: AI-reviewed for best practices
