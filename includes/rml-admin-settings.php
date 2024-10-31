<?php
/**
 * Roomlio admin settings page.
 *
 * @package RoomlioGroupChat
 */

add_action( 'admin_init', 'roomlio_register_settings' );

$roomlio_signup_url = 'https://app.roomlio.com/#/signup?wpsetup=yes';

/** Roomlio settings page html. */
function roomlio_admin_settings_html() {
	global $roomlio_signup_url;

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>

<div class="wrap">
	<?php settings_errors(); ?>
	<h1>Roomlio</h1>
	<div id="rml-acct-confirm">
	<h3>Do you have a Roomlio account?</h3>
	<button id="rml-acct-yes" class="button">Yes</button>
	<button id="rml-acct-no" class="button">No</button>
	</div>
	<div id="rml-setup-steps" class="rml-setup-steps">
	<h3>Steps to get going</h3>
	<ol>
		<li>Sign up at <a href="<?php echo esc_url( $roomlio_signup_url ); ?>" target="_blank">Roomlio</a>.</li>
		<li>Go to the embed code section of the Roomlio settings page.  Select "WordPress" for the Client Framework if it already isn't.</li>
		<li>Grab your PK and HMAC Key.</li>
		<li>Come back to this Roomlio plugin settings page and paste your PK and HMAC Key below.</li>
		<li>Add a new room in the Roomlio plugin.</li>
		<li>Either grab the auto-generated shortcode when you create the room and add it to your page or use the Roomlio custom block type widget to add it to your page.</li>
	</ol>
	</div>
	<form id="rml-settings-form" class="rml-settings-form" method="post" action="options.php">
	<?php
		settings_fields( 'general_section' );
		do_settings_sections( 'roomlio-plugin-page' );
		submit_button();
	?>
	</form>
</div>
	<?php
}

/**
 * Use of WordPress' Settings API to create Roomlio settings.
 * https://developer.wordpress.org/plugins/settings/settings-api/
 */
function roomlio_register_settings() {
	add_settings_section(
		'general_section',
		'General Settings',
		'roomlio_general_settings_html',
		'roomlio-plugin-page'
	);

	add_settings_field(
		'roomlio-pk',
		'PK (Public Key)',
		'roomlio_pk_html',
		'roomlio-plugin-page',
		'general_section'
	);

	add_settings_field(
		'roomlio-hmac-key',
		'HMAC Key',
		'roomlio_hmac_key_html',
		'roomlio-plugin-page',
		'general_section'
	);

	register_setting(
		'general_section',
		'roomlio-pk',
		array(
			'type'              => 'string',
			'default'           => null,
			'sanitize_callback' => 'roomlio_validate_pk',
		)
	);

	register_setting(
		'general_section',
		'roomlio-hmac-key',
		array(
			'type'              => 'string',
			'default'           => null,
			'sanitize_callback' => 'roomlio_validate_hmac',
		)
	);
}

/**
 * Validate public key before saving.
 *
 * Public Key cannot be blank and must be 44 characters.
 *
 * @param string $input roomlio-pk string.
 */
function roomlio_validate_pk( $input ) {
	$output = get_option( 'roomlio-pk' );
	if ( empty( $input ) ) {
		add_settings_error( 'roomlio-pk', 'roomlio-pk', 'Public Key (pk) cannot be blank.', 'error' );
	} elseif ( 44 !== strlen( $input ) ) {
		add_settings_error( 'roomlio-pk', 'roomlio-pk', 'Public Key (pk) is invalid.', 'error' );
	} else {
		$output = $input;
	}
	return sanitize_text_field( $output );
}

/**
 * Validate HMAC key before saving.
 *
 * HMAC Key cannot be blank and must be 60 characters.
 *
 * @param string $input roomlio-hmac-key string.
 */
function roomlio_validate_hmac( $input ) {
	$output = get_option( 'roomlio-hmac-key' );
	if ( ! empty( $input ) && 60 !== strlen( $input ) ) {
		add_settings_error( 'roomlio-hmac-key', 'roomlio-hmac-key', 'HMAC key is invalid.', 'error' );
	} else {
		$output = $input;
	}
	return sanitize_text_field( $output );
}

/** Roomlio general settings html. */
function roomlio_general_settings_html() {
	global $roomlio_signup_url;
	?>
<p>Your PK (Public Key) and HMAC Key can be found in the embed code section of the <a href="https://app.roomlio.com/#/settings" target="_blank">Roomlio settings</a>. Don't have a Roomlio account yet?  <a href="<?php echo esc_url( $roomlio_signup_url ); ?>" target="_blank">Sign up</a>!</p>
	<?php
	if ( strlen( get_option( 'roomlio-pk' ) ) > 0 ) {
		?>
	<p>Now that you have a PK set, <a href="/wp-admin/post-new.php?post_type=roomlio_room">create a new room</a>.</p>
		<?php
	}
}

/** Roomlio public key input html. */
function roomlio_pk_html() {
	?>
<div class="rml-input-container large">
	<input id="roomlio-pk" class="rml-input" name="roomlio-pk" type="text" value="<?php echo esc_attr( get_option( 'roomlio-pk' ) ); ?>" autocomplete="off" spellcheck="false" />
	<p>Required. The PK is your "publishable key" for your Roomlio account. It identifies your embed code with your Roomlio account. It is not a secret value, but it is unique per Roomlio account. </p>
</div>
	<?php
}

/** Roomlio HMAC key input html. */
function roomlio_hmac_key_html() {
	?>
<div class="rml-input-container large">
	<input id="roomlio-hmac-key" class="rml-input" name="roomlio-hmac-key" type="password" autocomplete="new-password" value="<?php echo esc_attr( get_option( 'roomlio-hmac-key' ) ); ?>" autocomplete="off" spellcheck="false" />
	<img id="roomlio-hmac-key-icon" class="rml-input-icon" src="<?php echo esc_url( plugins_url( 'roomlio-group-chat/assets/svgs/eye.svg' ) ); ?>" alt="password-toggle-icon" />
	<p>Optional. Enter your HMAC key if you plan to authenticate your chat room users securely based on their WP site login. If you leave it blank, chat users will be assigned a name based on their geo location.</p>
	<p>HINT: Protect your HMAC Key like an API key or password.</p>
</div>
	<?php
}

?>
