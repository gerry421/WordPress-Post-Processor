/**
 * AI Post Processor Admin Scripts
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Select all posts checkbox for unprocessed posts
        $('#select-all-posts').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('input[name="post_ids[]"]').prop('checked', isChecked);
        });

        // Select all posts checkbox for processed posts
        $('#select-all-processed-posts').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('input[name="post_ids[]"]').prop('checked', isChecked);
        });

        // Individual checkboxes
        $('input[name="post_ids[]"]').on('change', function() {
            var allChecked = $('input[name="post_ids[]"]').length === $('input[name="post_ids[]"]:checked').length;
            $('#select-all-posts').prop('checked', allChecked);
            $('#select-all-processed-posts').prop('checked', allChecked);
        });

        // Processing status display
        var processingStatus = $('#processing-status');
        if (processingStatus.length) {
            // Show processing status if there's activity
            if (window.location.search.indexOf('processing=1') !== -1) {
                processingStatus.show();
            }
        }

        // API key field - clear placeholder on focus
        $('#claude_post_processor_api_key').on('focus', function() {
            if ($(this).val() === '****************************************') {
                $(this).val('');
            }
        });

        // Confirm before processing all posts
        $('button[name="process_type"][value="all"]').on('click', function(e) {
            var count = $('input[name="post_ids[]"]').length;
            var message = 'Are you sure you want to process all ' + count + ' posts? This may take a while.';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Confirm before bulk processing
        $('button[name="process_type"][value="selected"]').on('click', function(e) {
            var count = $('input[name="post_ids[]"]:checked').length;
            
            if (count === 0) {
                alert('Please select at least one post to process.');
                e.preventDefault();
                return false;
            }
            
            var message = 'Are you sure you want to process ' + count + ' selected post(s)?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Confirm before reprocessing all posts
        $('button[name="reprocess_type"][value="all"]').on('click', function(e) {
            var count = $('input[name="post_ids[]"]').length;
            var message = 'Are you sure you want to reprocess all ' + count + ' posts? This will overwrite existing processed content and may take a while.';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Confirm before bulk reprocessing
        $('button[name="reprocess_type"][value="selected"]').on('click', function(e) {
            var count = $('input[name="post_ids[]"]:checked').length;
            
            if (count === 0) {
                alert('Please select at least one post to reprocess.');
                e.preventDefault();
                return false;
            }
            
            var message = 'Are you sure you want to reprocess ' + count + ' selected post(s)? This will overwrite existing processed content.';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

})(jQuery);
