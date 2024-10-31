<?php
/**
 * For admin add Roomlio room form.
 *
 * @package RoomlioGroupChat
 */

add_action( 'pre_post_update', 'roomlio_room_validate_and_save_meta', 10, 2 ); /** Make sure form is valid before we save to db. */
add_action( 'admin_notices', 'roomlio_admin_custom_notice' ); /** Our custom admin notice function we can use on forms. */
add_action( 'admin_notices', 'roomlio_no_pk_found_notification' ); /** A custom notice for invalid settings that will show on all admin pages. */
add_filter( 'enter_title_here', 'roomlio_change_title_input_placeholder' );
add_filter( 'wp_insert_post_data', 'roomlio_filter_wp_insert_post_data', 10, 2 );

/**
 * Replace post_title with roomlio-room-key if post_title is empty.
 *
 * @param array $data data.
 * @param array $postarr post data.
 */
function roomlio_filter_wp_insert_post_data( $data, $postarr ) {
	if ( ! empty( $postarr['roomlio-room-key'] ) && empty( $data['post_title'] ) ) {
		$data['post_title'] = sanitize_text_field( $postarr['roomlio-room-key'] );
	}
	return $data;
};

/**
 * Change post title placeholder.
 *
 * @param array $input placeholder text.
 */
function roomlio_change_title_input_placeholder( $input ) {
	if ( 'roomlio_room' === get_post_type() ) {
		return 'Room Name';
	} else {
		return $input;
	}
}

/**
 * Meta boxes are WP's way of adding custom content (i.e. forms) to the add/edit screens of your custom post type.
 * https://developer.wordpress.org/plugins/metadata/custom-meta-boxes/
 */
function roomlio_add_room_meta_boxes() {
	global $post;
	/** Don't show the shortcode on the add room form */
	if ( 'edit' === $post->filter ) {
		add_meta_box( 'roomlio_room_shortcode', 'Room Shortcode', 'roomlio_room_shortcode_html', 'roomlio_room' );
	}
	add_meta_box( 'roomlio_room_form', 'Room Info', 'roomlio_room_form_html', 'roomlio_room' );
}

/** Add Roomlio room form html */
function roomlio_room_form_html() {
	global $post;
	$height = get_post_meta( $post->ID, 'roomlio-height', true );
	if ( 0 === strlen( $height ) ) {
		$height = '500px';
	}
	?>
<table class="form-table">
	<tbody>
	<tr>
		<th scope="row">Room Key</th>
			<?php wp_nonce_field( 'roomlio_room_form_action', 'roomlio_room_form_nonce' ); ?>
		<td>
		<div class="rml-input-container large">
			<input id="roomlio-room-key" class="rml-input" name="roomlio-room-key" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, 'roomlio-room-key', true ) ); ?>" autocomplete="off" spellcheck="false" />
			<p>Required. A unique key to identify your room.</p>
		</div>
		</td>
	</tr>
	<tr>
		<th>Width</th>
		<td>
		<div class="rml-input-container large">
			<input id="roomlio-width" class="rml-input" name="roomlio-width" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, 'roomlio-width', true ) ); ?>" autocomplete="off" spellcheck="false" />
			<p>Optional. Only use CSS values (e.g. 500px, 50%, or 75vw). If no width is set, the room width will be 100%</p>
		</div>
		</td>
	</tr>  
	<tr>
		<th>Height</th>
		<td>
		<div class="rml-input-container large">
			<input id="roomlio-height" class="rml-input" name="roomlio-height" type="text" value="<?php echo esc_attr( $height ); ?>" autocomplete="off" spellcheck="false" />
			<p>Optional. Only use CSS values (e.g. 500px, 50%, or 75vh). If no height is set, the room height will be 100%</p>
		</div>
		</td>
	</tr>  
	</tbody>
</table>
	<?php
}

/**
 * Add Roomlio room form save validation.
 *
 * Room Key cannot be blank.
 *
 * @param string $post_id post id.
 * @param array  $post_data post data.
 */
function roomlio_room_validate_and_save_meta( $post_id, $post_data ) {
	/** Don't execute on non roomlio post types. */
	if ( 'roomlio_room' !== $post_data['post_type'] ) {
		return;
	}

	/** Don't execute on trash, autosave, etc, only 'publish' */
	if ( 'publish' !== $post_data['post_status'] ) {
		return;
	}

		/** Return invalid session if nonce missing */
	if ( ! isset( $_POST['roomlio_room_form_nonce'] ) ) {
		update_option( 'roomlio_admin_notification', wp_json_encode( array( 'error', 'Invalid session, reload the page and try again.' ) ) );
		header(
			'Location: ' . get_admin_url() . 'post-new.php?post_type=roomlio_room'
		);
		exit;
	}

	/** Verify the nonce, if not valid display session invalid error */
	if ( ! wp_verify_nonce( sanitize_key( $_POST['roomlio_room_form_nonce'] ), 'roomlio_room_form_action' ) ) {
		update_option( 'roomlio_admin_notification', wp_json_encode( array( 'error', 'Invalid session, reload the page and try again.' ) ) );
		header(
			'Location: ' . get_admin_url() . 'post-new.php?post_type=roomlio_room'
		);
		exit;
	}

	/** If we don't have an auth error, lets verify roomlio-room-key isn't empty. */
	if ( empty( $_POST['roomlio-room-key'] ) ) {
		$redirect_to = get_admin_url() . 'post-new.php?post_type=roomlio_room'; // New room form!
		if ( isset( $_POST['save'] ) ) { // That is the button value on edit room form.
			$redirect_to = get_edit_post_link( $post_id, 'redirect' ); // Edit room form!
		}
		update_option( 'roomlio_admin_notification', wp_json_encode( array( 'error', 'Room Key cannot be blank.' ) ) );
		header(
			'Location: ' . $redirect_to
		);
		exit;
	}

	/**
	 * Append 'px' if the user doesn't provide a valid CSS value for the height and width.
	 * Sanitized below.
	 */
	$h = sanitize_text_field( empty( $_POST['roomlio-height'] ) ? '' : wp_unslash( $_POST['roomlio-height'] ) );
	if ( is_numeric( substr( $h, -1 ) ) ) {
		$h = $h . 'px';
	}

	$w = sanitize_text_field( empty( $_POST['roomlio-width'] ) ? '' : wp_unslash( $_POST['roomlio-width'] ) );
	if ( is_numeric( substr( $w, -1 ) ) ) {
		$w = $w . 'px';
	}

	update_post_meta( $post_id, 'roomlio-room-key', sanitize_text_field( wp_unslash( $_POST['roomlio-room-key'] ) ) );
	update_post_meta( $post_id, 'roomlio-width', $w );
	update_post_meta( $post_id, 'roomlio-height', $h );
}

/**
 * Adding 'roomlio_admin_custom_notice' function via the 'admin_notices' add_action hook above
 * makes the roomlio_admin_custom_notice function available for our form validation.
 * We can call 'update_option('roomlio_admin_notification', ...)' in our form
 * validation and show custom msgs when our form fails.
 */
function roomlio_admin_custom_notice() {
	$notifications = get_option( 'roomlio_admin_notification' );
	if ( ! empty( $notifications ) ) {
		$notifications = json_decode( $notifications );
		// notifications[0] = (string) Type of notification: error, updated or update-nag
		// notifications[1] = (string) Message
		// notifications[2] = (boolean) is_dismissible?
		switch ( $notifications[0] ) {
			case 'error':
			case 'updated':
				$class = $notifications[0];
				break;
			default:
				// Defaults to error just in case.
				$class = 'error';
				break;
		}

		$is_dismissable = '';
		if ( isset( $notifications[2] ) && true === $notifications[2] ) {
			$is_dismissable = 'is_dismissable';
		}

		echo '<div class="' . esc_attr( $class ) . ' notice ' . esc_attr( $is_dismissable ) . '">';
		echo '<p>' . esc_html( $notifications[1] ) . '</p>';
		echo '</div>';

		// reset the notification. otherwise it won't go away when user routes to another page.
		update_option( 'roomlio_admin_notification', false );
	}
}

/**
 * HTML that displays when no public key is defined in the Roomlio settings.
 * Can't use update_option('roomlio_admin_notification', ...) since this
 * admin notice needs to live on multiple screens.
 */
function roomlio_no_pk_found_notification() {
	if ( 0 === strlen( get_option( 'roomlio-pk' ) ) ) {
		$url = get_admin_url() . 'edit.php?post_type=roomlio_room&page=roomlio-plugin-page';
		?>
	<div class="notice notice-warning is-dismissible">
	<p>You need to have a PK (Public Key) defined in the Roomlio plugin <a href="<?php echo esc_url( $url ); ?>">settings</a> for the plugin to work.</p>
	</div>
		<?php
	}
}

/** HTML for Roomlio room shortcode. */
function roomlio_room_shortcode_html() {
	global $post;
	$shortcode = roomlio_get_shortcode_string( $post );
	?>
<div class="rml-textarea-container large one-row">
	<textarea class="rml-textarea" rows="1" readonly="readonly"><?php echo esc_textarea( $shortcode ); ?></textarea>
</div>
	<?php
}

/**
 * Build Roomlio shortcode string.
 *
 * @param array $post post.
 */
function roomlio_get_shortcode_string( $post ) {
	$sc        = '[roomlio-room';
	$sc_ending = ']';

	$room_key = get_post_meta( $post->ID, 'roomlio-room-key', true );
	if ( '' !== $room_key ) {
		$sc .= ' roomKey="' . $room_key . '"';
	}

	$room_name = $post->post_title;
	if ( '' !== $room_name ) {
		$sc .= ' roomName="' . $room_name . '"';
	}

	$width = get_post_meta( $post->ID, 'roomlio-width', true );
	if ( '' !== $width ) {
		$sc .= ' width="' . $width . '"';
	}

	$height = get_post_meta( $post->ID, 'roomlio-height', true );
	if ( '' !== $height ) {
		$sc .= ' height="' . $height . '"';
	}

	return $sc .= $sc_ending;
}

?>
