<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Ajax {

	/**
	 * Init AJAX hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_notes_adda_query_notes', array( __CLASS__, 'query_notes' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_query_notes', array( __CLASS__, 'query_notes' ) );

		add_action( 'wp_ajax_notes_adda_create_note', array( __CLASS__, 'create_note' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_create_note', array( __CLASS__, 'create_note' ) );
		add_action( 'wp_ajax_notes_adda_update_note', array( __CLASS__, 'update_note' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_update_note', array( __CLASS__, 'update_note' ) );
		add_action( 'wp_ajax_notes_adda_delete_note', array( __CLASS__, 'delete_note' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_delete_note', array( __CLASS__, 'delete_note' ) );

		// Tags
		add_action( 'wp_ajax_notes_adda_get_note_tags', array( __CLASS__, 'get_note_tags' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_get_note_tags', array( __CLASS__, 'get_note_tags' ) );
		add_action( 'wp_ajax_notes_adda_set_note_tags', array( __CLASS__, 'set_note_tags' ) );

		// Likes
		add_action( 'wp_ajax_notes_adda_get_like_status', array( __CLASS__, 'get_like_status' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_get_like_status', array( __CLASS__, 'get_like_status' ) );
		add_action( 'wp_ajax_notes_adda_add_like', array( __CLASS__, 'add_like' ) );
		add_action( 'wp_ajax_notes_adda_remove_like', array( __CLASS__, 'remove_like' ) );

		// Reports
		add_action( 'wp_ajax_notes_adda_create_report', array( __CLASS__, 'create_report' ) );
		add_action( 'wp_ajax_notes_adda_update_report_status', array( __CLASS__, 'update_report_status' ) );
	}

	/**
	 * Helper to send error response with correct HTTP status.
	 *
	 * @param WP_Error $error WordPress error object.
	 */
	private static function send_error( $error ) {
		$code    = $error->get_error_code();
		$message = $error->get_error_message();
		$status  = 400; // Default bad request

		if ( strpos( $code, 'forbidden' ) !== false || strpos( $code, 'unauthorized' ) !== false ) {
			$status = 403;
		} elseif ( strpos( $code, 'not_found' ) !== false ) {
			$status = 404;
		} elseif ( strpos( $code, 'failed' ) !== false ) {
			$status = 500;
		}

		wp_send_json_error(
			array(
				'code'    => $code,
				'message' => $message,
			),
			$status
		);
	}

	/**
	 * Query notes handler.
	 */
	public static function query_notes() {
		$args = array();

		// Allowed filters
		$allowed_filters = array(
			'search', 'owner_id', 'tag_id', 'tag_slug', 'subject', 'is_whole_notes',
			'page', 'per_page', 'orderby', 'order'
		);

		foreach ( $allowed_filters as $filter ) {
			if ( isset( $_REQUEST[ $filter ] ) ) {
				$args[ $filter ] = sanitize_text_field( wp_unslash( $_REQUEST[ $filter ] ) );
			}
		}

		$result = Notes_Adda_Note_Query::get_notes( $args );

		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Check authentication and nonce.
	 */
	private static function check_auth() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'code'    => 'notes_adda_not_logged_in',
					'message' => 'You must be logged in to perform this action.',
				),
				401
			);
		}

		if ( ! isset( $_REQUEST['_ajax_nonce'] ) || ! wp_verify_nonce( $_REQUEST['_ajax_nonce'], 'notes_adda_ajax_nonce' ) ) {
			wp_send_json_error(
				array(
					'code'    => 'notes_adda_forbidden',
					'message' => 'Invalid nonce or permission denied.',
				),
				403
			);
		}

		return get_current_user_id();
	}

	/**
	 * Create note handler.
	 */
	public static function create_note() {
		$user_id = self::check_auth();

		$data = array();
		$allowed_fields = array( 'title', 'subject', 'chapter', 'description', 'file_url', 'file_id', 'is_whole_notes' );
		foreach ( $allowed_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				// The Notes_Adda_Notes::create handles specific sanitization for each field.
				$data[ $field ] = wp_unslash( $_POST[ $field ] );
			}
		}

		$note = Notes_Adda_Notes::create( $user_id, $data );

		if ( is_wp_error( $note ) ) {
			self::send_error( $note );
		}

		wp_send_json_success( $note );
	}

	/**
	 * Update note handler.
	 */
	public static function update_note() {
		$user_id = self::check_auth();

		$note_id = isset( $_POST['note_id'] ) ? (int) $_POST['note_id'] : 0;
		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$data = array();
		$allowed_fields = array( 'title', 'subject', 'chapter', 'description', 'file_url', 'file_id', 'is_whole_notes' );
		foreach ( $allowed_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$data[ $field ] = wp_unslash( $_POST[ $field ] );
			}
		}

		$note = Notes_Adda_Notes::update( $note_id, $user_id, $data );

		if ( is_wp_error( $note ) ) {
			self::send_error( $note );
		}

		wp_send_json_success( $note );
	}

	/**
	 * Delete note handler.
	 */
	public static function delete_note() {
		$user_id = self::check_auth();

		$note_id = isset( $_POST['note_id'] ) ? (int) $_POST['note_id'] : 0;
		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$result = Notes_Adda_Notes::delete( $note_id, $user_id );

		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( array( 'deleted' => true ) );
	}

	/**
	 * Get note tags handler.
	 */
	public static function get_note_tags() {
		$note_id = isset( $_REQUEST['note_id'] ) ? (int) $_REQUEST['note_id'] : 0;
		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$tags = Notes_Adda_Note_Tags::get_tags( $note_id );
		wp_send_json_success( $tags );
	}

	/**
	 * Set note tags handler.
	 */
	public static function set_note_tags() {
		$user_id = self::check_auth();

		$note_id = isset( $_POST['note_id'] ) ? (int) $_POST['note_id'] : 0;
		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$note = Notes_Adda_Notes::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			self::send_error( $note );
		}
		if ( ! $note ) {
			self::send_error( new WP_Error( 'notes_adda_note_not_found', 'Note does not exist.' ) );
		}

		// Authorization
		if ( $note->owner_id != $user_id && ! user_can( $user_id, 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'You are not allowed to update this note.' ) );
		}

		$tags = isset( $_POST['tags'] ) ? $_POST['tags'] : array();
		if ( ! is_array( $tags ) ) {
			$decoded = json_decode( wp_unslash( $tags ), true );
			if ( is_array( $decoded ) ) {
				$tags = $decoded;
			} else {
				$tags = array();
			}
		}

		$sanitized_tags = array_map( 'sanitize_text_field', $tags );
		$result = Notes_Adda_Note_Tags::set_tags( $note_id, $sanitized_tags );

		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Get like status handler.
	 */
	public static function get_like_status() {
		$note_id = isset( $_REQUEST['note_id'] ) ? (int) $_REQUEST['note_id'] : 0;
		$user_id = is_user_logged_in() ? get_current_user_id() : 0;

		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$has_liked = false;
		if ( $user_id > 0 ) {
			$has_liked = Notes_Adda_Likes::has_liked( $note_id, $user_id );
		}

		wp_send_json_success( array( 'has_liked' => $has_liked ) );
	}

	/**
	 * Add like handler.
	 */
	public static function add_like() {
		$user_id = self::check_auth();

		$note_id = isset( $_POST['note_id'] ) ? (int) $_POST['note_id'] : 0;
		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$result = Notes_Adda_Likes::add( $note_id, $user_id );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( array( 'like_count' => $result ) );
	}

	/**
	 * Remove like handler.
	 */
	public static function remove_like() {
		$user_id = self::check_auth();

		$note_id = isset( $_POST['note_id'] ) ? (int) $_POST['note_id'] : 0;
		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$result = Notes_Adda_Likes::remove( $note_id, $user_id );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( array( 'like_count' => $result ) );
	}

	/**
	 * Create report handler.
	 */
	public static function create_report() {
		$user_id = self::check_auth();

		$note_id = isset( $_POST['note_id'] ) ? (int) $_POST['note_id'] : 0;
		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$reason = isset( $_POST['reason'] ) ? wp_unslash( $_POST['reason'] ) : '';

		$result = Notes_Adda_Reports::create( $note_id, $user_id, $reason );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Update report status handler.
	 */
	public static function update_report_status() {
		$user_id = self::check_auth();

		$report_id = isset( $_POST['report_id'] ) ? (int) $_POST['report_id'] : 0;
		if ( $report_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_report_id', 'Please provide a valid report ID.' ) );
		}

		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		$result = Notes_Adda_Reports::update_status( $report_id, $user_id, $status );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

}

Notes_Adda_Ajax::init();
