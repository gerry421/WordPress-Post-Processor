# WordPress Post Processor - Testing Checklist

## Installation & Configuration
- [ ] Plugin activates without errors
- [ ] Settings page appears under Settings > Claude Post Processor
- [ ] All tabs are accessible (API Configuration, Processing Options, Manual Processing, Logs)

## API Configuration
- [ ] API key field accepts input
- [ ] API key is encrypted when saved (check database)
- [ ] API key decrypts correctly for API calls
- [ ] API key placeholder (********) appears after saving
- [ ] Model selection dropdown shows all models
- [ ] Default model is claude-sonnet-4-20250514

## Processing Options
- [ ] Auto-process toggle works
- [ ] Email notifications toggle works
- [ ] Settings save correctly

## Grammar and Spelling Correction
- [ ] Content is sent to Claude API
- [ ] Corrected text is returned
- [ ] Original voice and style preserved
- [ ] Formatting is maintained

## Title Generation
- [ ] Title is generated from content
- [ ] Title length is appropriate (40-60 characters)
- [ ] Title is engaging and descriptive
- [ ] Title is not clickbait

## Tag Generation
- [ ] 5-10 tags are generated
- [ ] Tags are relevant to content
- [ ] Tags are assigned to post
- [ ] Generated tags stored in post meta (_claude_generated_tags)

## Category Generation
- [ ] 1-3 categories are suggested
- [ ] Categories are created if they don't exist
- [ ] Hierarchical categories work (e.g., "Travel > Europe > France")
- [ ] Parent categories created before child categories
- [ ] Categories are assigned to post
- [ ] Generated categories stored in post meta (_claude_generated_categories)

## Historical Enrichment
- [ ] Places/landmarks are identified in content
- [ ] Historical context is provided (2-3 sentences)
- [ ] Interesting facts are included
- [ ] HTML formatting is correct with blockquote
- [ ] Horizontal rules appear before and after enrichment
- [ ] Enrichment appears AFTER narrative and BEFORE media

## Media Handling - Images
- [ ] Single images display as featured image
- [ ] Multiple images create gallery shortcode
- [ ] Gallery format: [gallery ids="1,2,3" columns="3" link="file" size="large"]
- [ ] Image captions are preserved
- [ ] Alt text is preserved
- [ ] Individual image tags removed from content

## Media Handling - PDFs
- [ ] PDF links are detected
- [ ] PDFs are embedded inline using <object> tag
- [ ] Fallback download link is provided
- [ ] PDF container has correct CSS class (pdf-embed-container)

## Media Handling - YouTube Videos
- [ ] youtube.com/watch?v= links detected
- [ ] youtu.be/ links detected
- [ ] youtube.com/embed/ links detected
- [ ] Video ID extracted correctly
- [ ] Responsive iframe embed created
- [ ] Loading="lazy" attribute present
- [ ] Video container has correct CSS class (video-embed-container)

## Media Handling - Direct Videos
- [ ] .mp4 files detected
- [ ] .webm files detected
- [ ] .mov files detected
- [ ] HTML5 video player created
- [ ] Correct MIME type set
- [ ] Video controls enabled
- [ ] Video container has correct CSS class (video-embed-container)

## Content Assembly
- [ ] Content assembled in correct order:
  1. Main narrative (corrected)
  2. Historical enrichment (if any)
  3. Media (photos/galleries)
  4. PDFs and videos

## Post Status & Metadata
- [ ] Processed posts set to 'draft' status
- [ ] _claude_processed meta set to true
- [ ] _claude_processed_date meta set to current timestamp
- [ ] _claude_original_content meta contains backup
- [ ] _claude_original_title meta contains backup
- [ ] _claude_processing_log meta contains operation log

## Auto-Processing
- [ ] Hook into 'save_post' action works
- [ ] Only processes new posts (not updates)
- [ ] Only processes 'post' post type
- [ ] Skips already processed posts
- [ ] Skips autosaves and revisions
- [ ] Background processing scheduled with wp_schedule_single_event
- [ ] Background processing executes correctly

## Manual Processing - Single Post
- [ ] Row action "Process with Claude" appears on post list
- [ ] Row action only appears for posts
- [ ] Row action redirects correctly after processing
- [ ] Success message displayed

## Manual Processing - Bulk
- [ ] Bulk action "Process with Claude" appears
- [ ] Bulk action processes selected posts
- [ ] Success message shows count of processed posts
- [ ] Delay between posts (2 seconds)

## Manual Processing - Settings Page
- [ ] Unprocessed posts list displays correctly
- [ ] Post title, date, and status shown
- [ ] Select all checkbox works
- [ ] Individual checkboxes work
- [ ] "Process Selected" button works
- [ ] "Process All Unprocessed" button works
- [ ] Confirmation prompt appears
- [ ] Processing status displays

## Admin UI - Custom Column
- [ ] "Claude Status" column appears in post list
- [ ] Checkmark (✓) shows for processed posts
- [ ] Processing date shown for processed posts
- [ ] Dash (—) shows for unprocessed posts

## Admin UI - Dashboard Widget
- [ ] Widget appears on dashboard
- [ ] Shows count of unprocessed posts
- [ ] "Process Now" button links to manual processing tab
- [ ] "Settings" link works

## Error Handling - API
- [ ] Connection failures logged
- [ ] Rate limiting handled with exponential backoff
- [ ] Max 5 retries attempted
- [ ] Invalid API key displays admin notice
- [ ] Auto-processing disabled on invalid key
- [ ] Processing timeout saves partial progress

## Error Handling - Content
- [ ] Content too long handled (if applicable)
- [ ] Empty content handled gracefully
- [ ] Invalid content handled gracefully

## Logging
- [ ] API calls logged to wp-content/uploads/claude-processor-logs/
- [ ] Error logs created
- [ ] Log files protected with .htaccess
- [ ] Log files displayed in Logs tab
- [ ] Logs show date and timestamp

## Security
- [ ] API key encrypted before storage
- [ ] Nonces used for all form submissions
- [ ] Capability checks (manage_options for settings)
- [ ] Capability checks (edit_posts for processing)
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] API responses validated

## Email Notifications
- [ ] Email sent when processing complete (if enabled)
- [ ] Email contains post title
- [ ] Email contains edit post link
- [ ] Email sent to admin email

## CSS Styling
- [ ] Video embeds display correctly (16:9 aspect ratio)
- [ ] PDF embeds display correctly
- [ ] Historical enrichment styled with blockquote
- [ ] Location icon appears in enrichment section
- [ ] Horizontal rules styled correctly
- [ ] Responsive design works on mobile
- [ ] Admin styles load on settings page
- [ ] Admin styles load on post edit screens

## JavaScript
- [ ] Select all checkbox works
- [ ] Individual checkboxes sync with select all
- [ ] API key placeholder clears on focus
- [ ] Confirmation prompts appear
- [ ] Processing status displays

## Uninstall
- [ ] All options deleted
- [ ] All post meta deleted
- [ ] Log directory and files deleted
- [ ] Scheduled cron events cleared

## WordPress Standards
- [ ] PHP 8.0+ compatible
- [ ] WordPress 6.0+ compatible
- [ ] All strings translatable
- [ ] PHPDoc comments on all functions
- [ ] WordPress coding standards followed
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors

## Performance
- [ ] 2-second delay between API calls
- [ ] Background processing doesn't timeout
- [ ] Large posts handled without timeout
- [ ] Multiple posts can be processed in bulk

## Edge Cases
- [ ] Posts with no images handled
- [ ] Posts with no categories/tags handled
- [ ] Posts with existing categories/tags preserved
- [ ] Posts with special characters handled
- [ ] Posts with HTML entities handled
- [ ] Posts with shortcodes preserved
