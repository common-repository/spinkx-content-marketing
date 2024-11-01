<?php
/**
 * This is spinkx uninstall file.
 *
 * In this send plugin status on update server
 *
 * @package WordPress.
 * @subpackage spinkx.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! WP_UNINSTALL_PLUGIN || dirname( WP_UNINSTALL_PLUGIN ) !== dirname( plugin_basename( __FILE__ ) ) ) {
	status_header( 404 );
	exit;
}