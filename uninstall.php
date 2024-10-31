<?php
/**
 * Uninstall tasks.
 *
 * @package RoomlioGroupChat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		die;
}

global $wpdb;

/** Delete all Roomlio Room posts and meta data on uninstall. */
/** We don't want to use a cache here, so ignore phpcs. */
// phpcs:ignore 
$wpdb->query( "DELETE FROM {$wpdb->prefix}posts WHERE post_type = 'roomlio_room'" );
// phpcs:ignore 
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE 'roomlio-%'" );
// phpcs:ignore 
$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE 'roomlio-%'" );
