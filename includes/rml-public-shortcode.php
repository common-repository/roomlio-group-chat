<?php
/**
 * Functions for shortcode and Roomlio embed code.
 *
 * @package RoomlioGroupChat
 */

add_action( 'init', 'roomlio_init_shortcode' );

/**
 * Add the Roomlio shortcode to the site.
 */
function roomlio_init_shortcode() {
	global $pagenow;
	// We don't to call rml if they are editing the page.
	if ( 'post.php' !== $pagenow ) {
		add_shortcode( 'roomlio-room', 'roomlio_room_shortcode' );
	}
}

/**
 * See if the user has a HMAC key defined in the settings.
 */
function roomlio_has_hmac() {
	return ( strlen( get_option( 'roomlio-hmac-key' ) ) > 0 ) ? 'true' : 'false';
}

/**
 * Gathers all of the room data from the shortcode and calls the rml JS embed code.
 *
 * @param array  $atts attributes passed in.
 * @param string $content content passed in.
 * @param string $tag tag passed in.
 */
function roomlio_room_shortcode( $atts = array(), $content = null, $tag = '' ) {
	$atts     = array_change_key_case( (array) $atts, CASE_LOWER );
	$rml_atts = shortcode_atts(
		array(
			'roomKey'       => $atts['roomkey'],
			'roomName'      => $atts['roomname'],
			'roomElementID' => 'rml-' . sanitize_key( $atts['roomkey'] ),
			'height'        => array_key_exists( 'height', $atts ) ? $atts['height'] : '100%', // have to check if the optional height key exists starting in php 8.0.0 (can't be missing).
			'width'         => array_key_exists( 'width', $atts ) ? $atts['width'] : '100%', // have to check if the optional width key exists starting in php 8.0.0 (can't be missing).
		),
		$atts,
		$tag
	);

	// Call the embed code JS.
	wp_enqueue_script( 'rml-embed-code', plugins_url( 'roomlio-group-chat/assets/js/rml-embed-code.js' ), array(), PLUGIN_VERSION, true );

	$public_roomlio_embed_data = array(
		'pk'            => esc_js( get_option( 'roomlio-pk' ) ),
		'roomKey'       => esc_js( $rml_atts['roomKey'] ),
		'roomName'      => esc_js( $rml_atts['roomName'] ),
		'roomElementID' => esc_js( $rml_atts['roomElementID'] ),
		'hasHMAC'       => esc_js( roomlio_has_hmac() ),
	);

	/** If user is logged into WordPress create a nonce that the JS code (rml-emded-code.js)
	 * will check the existance of to see if it will call rml('register') or rml('registerSecure').
	 * https://codex.wordpress.org/WordPress_Nonces.
	*/
	if ( is_user_logged_in() ) {
		$public_roomlio_embed_data['nonce'] = wp_create_nonce( 'wp_rest' );
	};
	// 'wp_localize_script' allows us to pass data into the JS
	wp_localize_script(
		'rml-embed-code',
		'rmlEmbedCodeData',
		$public_roomlio_embed_data
	);

	$rml_height = $rml_atts['height'] ? $rml_atts['height'] : '100%';
	$rml_width  = $rml_atts['width'] ? $rml_atts['width'] : '100%';
	return '<div style="height:' . esc_attr( $rml_height ) . '; width:' . esc_attr( $rml_width ) . ';"><div id="' . esc_attr( $rml_atts['roomElementID'] ) . '"></div></div>';
}
