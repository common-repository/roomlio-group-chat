<?php
/**
 * Plugin Name: Roomlio - Group Chat
 * Description: Embed a Roomlio group chat room in your WordPress website in no time at all. Great for live video chat, community chat, and member only chat.
 * Version: 1.0.5
 * Author: Roomlio
 * Author URI: https://roomlio.com/?ref=wordpress
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * Author URI: https://roomlio.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package RoomlioGroupChat
 */

$plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), false );
define( 'PLUGIN_VERSION', $plugin_data['Version'] );

require_once plugin_dir_path( __FILE__ ) . 'includes/rml-admin-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/rml-public-functions.php';

register_deactivation_hook( __FILE__, 'roomlio_deactivate' );

/**
 * Deactivation hook.
 */
function roomlio_deactivate() {
	// Unregister the post type, so the rules are no longer in memory.
	unregister_post_type( 'roomlio_room' );
	// Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules();
}

/**
 * Console Log function just helps us debug plugin
 *
 * @param string $thing is the "thing" you want to log.
 */
function roomlio_log( $thing ) {
	// phpcs:ignore
	error_log( print_r( $thing, true ) );
}
