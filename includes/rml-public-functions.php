<?php // phpcs:ignore
/**
 * Functions for public pages.
 *
 * @package RoomlioGroupChat
 */

require_once 'rml-public-shortcode.php';

/** JS embed code calls this function to get 'registerSecure' payload. */
function roomlio_get_secure_payload() {
	$current_user = wp_get_current_user();
	$rml_payload  = array(
		'apiName'            => 'register',
		// phpcs:ignore
		'userID'             => rtrim( base64_encode( site_url() ), '=' ) . '-wpusr_' . strval( $current_user->ID ),
		'username'           => $current_user->display_name,
		'allowInsecureUsers' => true,
	);

	/** Verify we have a proper nonce for the API call, then we can fetch roomKey/roomName */
	if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'wp_rest' ) ) {
		if ( isset( $_POST['roomKey'] ) ) {
			$rml_payload['roomKey'] = sanitize_text_field(
				wp_unslash( $_POST['roomKey'] )
			);
		}

		if ( isset( $_POST['roomName'] ) ) {
			$rml_payload['roomName'] = sanitize_text_field(
				wp_unslash( $_POST['roomName'] )
			);
		}
	} else {
		status_header( 401 );
		echo wp_json_encode( 'ERROR: WP auth denied' );
		exit;
	}

	$payload_str = wp_json_encode( $rml_payload );

	/** Roomlio expects the payload base64 encoded */
  // phpcs:ignore 
	$payload_mac = base64_encode( hash_hmac( 'sha256', $payload_str, esc_js( get_option( 'roomlio-hmac-key' ) ), true ) );
	$response    = array(
		'payloadStr' => $payload_str,
		'payloadMAC' => $payload_mac,
	);

	header( 'Content-Type: application/json' );
	echo wp_json_encode( $response );
}

/** Custom WordPress endpoint that our JS can call to fetch the secure payload for the `registerSecure` call. */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'roomlio-group-chat/v1',
			'/secure_identify',
			array(
				'methods'             => 'POST',
				'callback'            => 'roomlio_get_secure_payload',
				/**
				 * WP errors out if you don't include a 'permission_callback'
				 * https://developer.wordpress.org/reference/functions/register_rest_route/#comment-4379 .
				 */
				'permission_callback' => function () {
					return true;}, /** "For REST API routes that are intended to be public, use __return_true as the permission callback." */
			)
		);
	}
);
