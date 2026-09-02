<?php
/**
 * Uninstall Handler for User Role Expiration Manager.
 *
 * Runs when the plugin is deleted via WP Admin.
 *
 * @package UserRoleExpirationManager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete global settings option and transients
delete_option( 'urem_settings' );
delete_site_transient( 'urem_github_release_info' );

// Unschedule cron job
$timestamp = wp_next_scheduled( 'urem_daily_expiration_event' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'urem_daily_expiration_event' );
}

// Drop custom DB table
global $wpdb;
$table_name = $wpdb->prefix . 'urem_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
