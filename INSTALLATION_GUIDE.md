# Claude Post Processor - Installation & Quick Start Guide

## Installation

### Option 1: Direct Upload
1. Download or clone this repository
2. Copy the `claude-post-processor` folder to your WordPress installation's `wp-content/plugins/` directory
3. Log in to your WordPress admin panel
4. Navigate to **Plugins > Installed Plugins**
5. Find "Claude Post Processor" and click **Activate**

### Option 2: ZIP Upload
1. Zip the `claude-post-processor` folder
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the ZIP file and click **Install Now**
6. Click **Activate Plugin**

## Initial Configuration

### 1. Get Your API Key
1. Visit [Anthropic Console](https://console.anthropic.com/)
2. Sign up or log in to your account
3. Navigate to **API Keys**
4. Create a new API key
5. Copy the key (you'll need it in the next step)

### 2. Configure the Plugin
1. In WordPress admin, go to **Settings > Claude Post Processor**
2. You'll see the **API Configuration** tab
3. Paste your API key in the "Anthropic API Key" field
4. Select your preferred model (Claude Sonnet 4 is recommended and selected by default)
5. Click **Save Changes**

### 3. Configure Processing Options
1. Click on the **Processing Options** tab
2. Choose your preferences:
   - **Auto-process New Posts**: Enable if you want new posts to be processed automatically
   - **Email Notifications**: Enable if you want to receive emails when posts are processed
3. Click **Save Changes**

## Usage

### Manual Processing - Single Post

#### Method 1: From Post List
1. Go to **Posts > All Posts**
2. Hover over a post you want to process
3. Click **Process with Claude** in the row actions
4. The post will be processed and set to draft status

#### Method 2: From Settings Page
1. Go to **Settings > Claude Post Processor**
2. Click on the **Manual Processing** tab
3. You'll see a list of unprocessed posts
4. Check the posts you want to process
5. Click **Process Selected**

### Bulk Processing
1. Go to **Posts > All Posts**
2. Select multiple posts using the checkboxes
3. Select **Process with Claude** from the Bulk Actions dropdown
4. Click **Apply**
5. Wait for processing to complete

### Process All Unprocessed Posts
1. Go to **Settings > Claude Post Processor**
2. Click on the **Manual Processing** tab
3. Click **Process All Unprocessed**
4. Confirm the action
5. All unprocessed posts will be processed sequentially

### Auto-Processing
If you enabled auto-processing:
1. Create a new post as usual
2. Save or publish it
3. The plugin will automatically schedule background processing
4. The post will be processed within a few minutes
5. You'll receive an email notification (if enabled)
6. The processed post will be in draft status for your review

## What Happens During Processing?

The plugin performs these operations in order:

### 1. Grammar & Spelling Correction
- Fixes grammar errors
- Corrects spelling mistakes
- Improves punctuation
- Enhances sentence structure
- Preserves your original voice and style

### 2. Title Generation
- Creates an SEO-friendly title
- 40-60 characters long
- Engaging and descriptive
- Not clickbait

### 3. Tag Generation
- Generates 5-10 relevant tags
- Based on topics, themes, people, and places
- Mix of specific and general terms
- Automatically created and assigned

### 4. Category Generation
- Suggests 1-3 appropriate categories
- Creates hierarchical categories if needed (e.g., Travel > Europe > France)
- Automatically created and assigned

### 5. Historical Enrichment
- Identifies places and landmarks in your content
- Adds historical context (2-3 sentences)
- Includes interesting facts
- Formatted with beautiful blockquote styling
- Inserted between main content and media

### 6. Media Processing
- **Photos**: Creates galleries for multiple images, featured image for single image
- **PDFs**: Embeds inline with fallback download link
- **YouTube Videos**: Converts links to responsive embeds
- **Direct Videos**: Creates HTML5 video players for .mp4, .webm, .mov files

### 7. Post Status
- Sets post to draft for your review
- Backs up original content and title
- Stores processing log

## Reviewing Processed Posts

1. Go to **Posts > All Posts**
2. Look for the **Claude Status** column
3. Processed posts show a green checkmark (✓) and processing date
4. Click on a processed post to review it
5. Original content is backed up in post meta (accessible via custom fields)
6. Review and publish when ready

## Viewing Logs

1. Go to **Settings > Claude Post Processor**
2. Click on the **Logs** tab
3. You'll see a list of log files:
   - API call logs
   - Error logs
4. Click "View Log" to see details

## Dashboard Widget

The plugin adds a dashboard widget showing:
- Number of unprocessed posts
- Quick "Process Now" button
- Link to settings

## Troubleshooting

### "API key is not configured" error
- Make sure you've entered your API key in Settings > Claude Post Processor
- Verify the key is correct (no extra spaces)
- Try re-entering and saving the key

### "API rate limit exceeded" error
- The plugin will automatically retry with exponential backoff
- If persistent, wait a few minutes and try again
- Consider spreading out bulk processing

### Posts not processing automatically
- Check that auto-processing is enabled in Processing Options
- Verify your API key is valid
- Check error logs for details

### Media not displaying correctly
- Ensure media files are properly attached to the post
- Check that media URLs are accessible
- Review the CSS in your theme for conflicts

### Can't find processed content
- Processed posts are set to draft status
- Check the Drafts filter in Posts > All Posts
- Original content is backed up in post meta

## Best Practices

1. **Review Before Publishing**: Always review processed posts before publishing
2. **Start Small**: Test with one post first before bulk processing
3. **Backup First**: Make a backup of your site before processing many posts
4. **Monitor Costs**: Keep track of your API usage in the Anthropic Console
5. **Check Logs**: Review logs regularly for any issues
6. **Original Content**: Keep the original content backups for reference

## Uninstalling

If you need to uninstall the plugin:

1. Deactivate the plugin from **Plugins > Installed Plugins**
2. Click **Delete** to remove it
3. The plugin will automatically:
   - Delete all settings
   - Remove all post metadata
   - Delete all log files
   - Clear scheduled background tasks

**Note**: Processed post content will remain (the AI-generated improvements), but the processing metadata will be removed.

## Support

For issues, questions, or feature requests:
- GitHub Issues: https://github.com/gerry421/WordPress-Post-Processor/issues
- Check the TESTING_CHECKLIST.md for detailed feature verification

## API Costs

Remember that using the Claude API incurs costs based on:
- Number of tokens processed
- Model used (Claude Sonnet 4, Claude 3.5 Sonnet, etc.)
- Number of API calls

Check current pricing at: https://www.anthropic.com/pricing

The plugin makes 5 API calls per post:
1. Grammar correction
2. Title generation
3. Tag generation
4. Category generation
5. Historical enrichment

Plan your usage accordingly!
