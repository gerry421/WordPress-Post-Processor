<?php
/**
 * Uninstall script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Claude_Post_Processor
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all plugin options.
 */
delete_option( 'claude_post_processor_api_key' );
delete_option( 'claude_post_processor_model' );
delete_option( 'claude_post_processor_auto_process' );
delete_option( 'claude_post_processor_email_notifications' );

/**
 * Delete all post meta created by the plugin.
 */
global $wpdb;

$wpdb->query(
	"DELETE FROM {$wpdb->postmeta} 
	WHERE meta_key IN (
		'_claude_processed',
		'_claude_processed_date',
		'_claude_original_content',
		'_claude_original_title',
		'_claude_processing_log',
		'_claude_generated_tags',
		'_claude_generated_categories'
	)"
);

/**
 * Delete log directory and files.
 */
$log_dir = wp_upload_dir()['basedir'] . '/claude-processor-logs';

if ( file_exists( $log_dir ) ) {
	// Delete all log files
	$log_files = glob( $log_dir . '/*' );
	foreach ( $log_files as $file ) {
		if ( is_file( $file ) ) {
			unlink( $file );
		}
	}
	// Remove directory
	rmdir( $log_dir );
}

/**
 * Clear any scheduled cron events.
 */
$cron = _get_cron_array();
if ( is_array( $cron ) ) {
	foreach ( $cron as $timestamp => $hooks ) {
		if ( isset( $hooks['claude_process_post_background'] ) ) {
			unset( $cron[ $timestamp ]['claude_process_post_background'] );
			if ( empty( $cron[ $timestamp ] ) ) {
				unset( $cron[ $timestamp ] );
			}
		}
	}
	_set_cron_array( $cron );
}
