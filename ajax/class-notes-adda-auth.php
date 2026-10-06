<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Auth {

	public static function init() {
		add_action( 'wp_ajax_nopriv_notes_adda_register', array( __CLASS__, 'register_user' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_login', array( __CLASS__, 'login_user' ) );
		add_action( 'wp_ajax_notes_adda_logout', array( __CLASS__, 'logout_user' ) );
	}

	public static function register_user() {
		check_ajax_referer( 'notes_adda_ajax_nonce', '_ajax_nonce' );

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : '';
		$confirm  = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';
		$bio      = isset( $_POST['bio'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bio'] ) ) : '';

		if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => 'Please fill in all required fields.' ) );
		}

		// Reserve owner username
		if ( 'notes_adda_dev' === strtolower( trim( $username ) ) ) {
			wp_send_json_error( array( 'message' => 'This username is reserved and cannot be registered.' ) );
		}

		if ( $password !== $confirm ) {
			wp_send_json_error( array( 'message' => 'Passwords do not match.' ) );
		}

		if ( username_exists( $username ) ) {
			wp_send_json_error( array( 'message' => 'Username already exists.' ) );
		}

		if ( email_exists( $email ) ) {
			wp_send_json_error( array( 'message' => 'Email already exists.' ) );
		}

		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => 'Registration failed. Please try again.' ) );
		}

		$user = new WP_User( $user_id );
		// Assign Student role as an application role
		$user->add_role( 'notes_adda_student' );

		// Create Notes Adda Profile
		if ( class_exists( 'Notes_Adda_User_Profile' ) ) {
			Notes_Adda_User_Profile::get_or_create( $user_id, array( 'bio' => $bio ) );
		}

		// Log in
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		wp_send_json_success( array( 'message' => 'Registration successful.' ) );
	}

	public static function login_user() {
		check_ajax_referer( 'notes_adda_ajax_nonce', '_ajax_nonce' );

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : '';

		if ( empty( $username ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => 'Please provide both username and password.' ) );
		}

		$creds = array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => true,
		);

		$user = wp_signon( $creds, false );

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'message' => 'Invalid username or password.' ) );
		}

		wp_send_json_success( array( 'message' => 'Login successful.' ) );
	}

	public static function logout_user() {
		check_ajax_referer( 'notes_adda_ajax_nonce', '_ajax_nonce' );
		wp_logout();
		wp_send_json_success( array( 'message' => 'Logged out successfully.' ) );
	}
}
Notes_Adda_Auth::init();
