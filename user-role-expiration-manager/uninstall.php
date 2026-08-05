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

// Delete global settings option
delete_option( 'urem_settings' );
delete_transient( 'urem_expiring_users_cache' );

// Unschedule cron job
$timestamp = wp_next_scheduled( 'urem_scheduled_expiration_check' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'urem_scheduled_expiration_check' );
}

// Drop custom DB table
global $wpdb;
$table_name = $wpdb->prefix . 'urem_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
