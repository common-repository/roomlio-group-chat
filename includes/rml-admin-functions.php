<?php
/**
 * Functions for admin pages.
 *
 * @package RoomlioGroupChat
 */

if ( is_admin() ) {
	include_once 'rml-admin-settings.php';
	include_once 'rml-admin-room-form.php';

	add_action( 'admin_menu', 'roomlio_add_to_left_menu' );
	add_action( 'init', 'roomlio_add_room_post_type' );
	add_action( 'enqueue_block_editor_assets', 'roomlio_add_roomlio_block_type' );
	add_action( 'after_setup_theme', 'roomlio_add_settings_plugin_link' );

	/**
	 * Have to manually add this filter since the item_updated and item_published labels don't work for some reason.
	 * Sample message array below.
	 * ["post"]=> array(11) {
	 *   [0]=> string(0) ""
	 *   [1]=> string(13) "Post updated."
	 *   [2]=> string(21) "Custom field updated."
	 *   [3]=> string(21) "Custom field deleted."
	 *   [4]=> string(13) "Post updated."
	 *   [5]=> bool(false)
	 *   [6]=> string(15) "Post published."
	 *   [7]=> string(11) "Post saved."
	 *   [8]=> string(15) "Post submitted."
	 *   [9]=> string(59) "Post scheduled for: Sep 10, 2021 at 14:17."
	 *   [10]=> string(19) "Post draft updated."
	 * }
	 */

	add_filter(
		'post_updated_messages',
		function ( $messages ) {
			$room_added_msg              = 'Room added.<br>' .
			'- now grab the shortcode and add to your pages/posts.<br>' .
			'- or go to page(s) or post(s) you want to add room to and add a Roomlio Room block. Search for Roomlio Room block in the block dialog.<br>' .
			'- or check out the <a href="https://roomlio.com/docs/wordpress/" target="_blank">docs</a> for more details';
			$messages['roomlio_room'][1] = 'Room updated';
			$messages['roomlio_room'][6] = $room_added_msg;
			return $messages;
		}
	);
}

/**
 * See if the user has a public key defined in the settings.
 */
function roomlio_has_pk() {
	return ( strlen( get_option( 'roomlio-pk' ) ) > 0 ) ? 'true' : 'false';
}

/**
 * Add Roomlio to left admin menu.
 */
function roomlio_add_to_left_menu() {
	add_submenu_page(
		'edit.php?post_type=roomlio_room',
		'Roomlio Settings',
		'Settings',
		'manage_options',
		'roomlio-plugin-page',
		'roomlio_admin_settings_html'
	);

	wp_enqueue_style( 'rml-styles', plugins_url( 'roomlio-group-chat/assets/css/rml-styles.css' ), array(), PLUGIN_VERSION );
	wp_enqueue_script( 'rml-script', plugins_url( 'roomlio-group-chat/assets/js/rml-script.js' ), array(), PLUGIN_VERSION, true );
	// 'wp_localize_script' allows us to pass data into the JS
	wp_localize_script(
		'rml-script',
		'rmlSettingsData',
		array(
			'hasPK' => esc_js( roomlio_has_pk() ),
		)
	);

}

/**
 * Registering our own custom post type allows us to use WP's core functionality when adding/editing rooms.
 * https://developer.wordpress.org/plugins/post-types/registering-custom-post-types/.
 */
function roomlio_add_room_post_type() {
	$labels = array(
		'name'           => 'Roomlio',
		'singular_name'  => 'Room',
		'add_new'        => 'Add Room',
		'add_new_item'   => 'Add New Room',
		'edit_item'      => 'Edit Room',
		'all_items'      => 'Roomlio Rooms',
		'search_items'   => 'Search Rooms',
		'item_published' => 'Room published',
		'item_updated'   => 'Room updated',
		'not_found'      => 'No rooms found',
	);
	register_post_type(
		'roomlio_room',
		array(
			'labels'               => $labels,
			'public'               => false,
			'show_ui'              => true,
			'has_archive'          => true,
			'query_var'            => false,
			'publicly_queryable'   => false,
			'supports'             => array( 'title' ),
			'register_meta_box_cb' => 'roomlio_add_room_meta_boxes',
			'menu_icon'            => plugins_url( 'roomlio-group-chat/assets/svgs/roomlio-logo.svg' ),
		)
	);

	/** Adds shortcode column to admin rooms table. */
	add_filter( 'manage_roomlio_room_posts_columns', 'roomlio_set_custom_shortcode_column' );

	/**
	 * Create a custom 'Shortcode' room list table column.
	 *
	 * @param array $columns columns for room list table.
	 */
	function roomlio_set_custom_shortcode_column( $columns ) {
		$columns['shortcode'] = 'Shortcode';
		$columns              = roomlio_reorder_columns( $columns );
		return $columns;
	}

	/**
	 * Adds the shortcode column in the middle of the room list table.
	 *
	 * @param array $default_cols original array of room list columns.
	 */
	function roomlio_reorder_columns( $default_cols ) {
		$reordered_cols = array();
		$shortcode      = $default_cols['shortcode'];
		unset( $default_cols['shortcode'] );

		foreach ( $default_cols as $key => $value ) {
			if ( 'date' === $key ) {
				$reordered_cols['shortcode'] = $shortcode;
			}
			$reordered_cols[ $key ] = $value;
		}
		return $reordered_cols;
	}

	add_action( 'manage_roomlio_room_posts_custom_column', 'roomlio_custom_room_column', 10, 2 );

	/**
	 * Add data to the new shortcode column.
	 *
	 * @param string $column column name.
	 */
	function roomlio_custom_room_column( $column ) {
		global $post;
		switch ( $column ) {
			case 'shortcode':
				echo esc_html( roomlio_get_shortcode_string( $post ) );
				break;
		}
	}
}

/**
 * Creates the Roomlio custom block type that users of the new Guttenburg editor can use.
 */
function roomlio_add_roomlio_block_type() {
	wp_enqueue_script( 'rml-block-type-js', plugins_url( 'roomlio-group-chat/assets/js/rml-block-type.js' ), array(), PLUGIN_VERSION, true );
	$query = new WP_Query( array( 'post_type' => 'roomlio_room' ) );
	$rooms = $query->posts;
	foreach ( $rooms as $room ) {
		$room->shortcode = roomlio_get_shortcode_string( $room ); // Add shortcode to room object.
		$room->height    = get_post_meta( $room->ID, 'roomlio-height', true ); // Add height to room object.
		$room->width     = get_post_meta( $room->ID, 'roomlio-width', true ); // Add width to room object.
	}
	// Send rooms array into block type JS.
	wp_localize_script(
		'rml-block-type-js',
		'rmlBlockTypeData',
		array(
			'rooms' => $rooms,
		)
	);
}

/**
 * Adds a 'Settings' link in the plugin actions on the plugin list screen.
 */
function roomlio_add_settings_plugin_link() {
	add_filter( 'plugin_action_links_roomlio-group-chat/roomlio.php', 'roomlio_settings_link' );
}

/**
 * Adds a link to the Roomlio settings page to the plugin entry
 *
 * @param array $links links for the plugin entry on the plugin list page.
 */
function roomlio_settings_link( $links ) {
	$url           = get_admin_url() . 'edit.php?post_type=roomlio_room&page=roomlio-plugin-page';
	$settings_link = '<a href="' . esc_url( $url ) . '">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
