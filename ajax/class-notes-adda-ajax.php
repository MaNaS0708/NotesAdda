<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Ajax {

	/**
	 * Init AJAX hooks.
	 */
	public static function init() {
		// Notes Query
		add_action( 'wp_ajax_notes_adda_query_notes', array( __CLASS__, 'query_notes' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_query_notes', array( __CLASS__, 'query_notes' ) );
		add_action( 'wp_ajax_notes_adda_get_note_details', array( __CLASS__, 'get_note_details' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_get_note_details', array( __CLASS__, 'get_note_details' ) );

		// Notes Mutation (Logged-in only)
		add_action( 'wp_ajax_notes_adda_create_note', array( __CLASS__, 'create_note' ) );
		add_action( 'wp_ajax_notes_adda_update_note', array( __CLASS__, 'update_note' ) );
		add_action( 'wp_ajax_notes_adda_delete_note', array( __CLASS__, 'delete_note' ) );

		// Note Review Workflow (Expert/Admin only)
		add_action( 'wp_ajax_notes_adda_review_note', array( __CLASS__, 'review_note' ) );

		// Subjects
		add_action( 'wp_ajax_notes_adda_get_subjects', array( __CLASS__, 'get_subjects' ) );
		add_action( 'wp_ajax_nopriv_notes_adda_get_subjects', array( __CLASS__, 'get_subjects' ) );
		add_action( 'wp_ajax_notes_adda_create_subject', array( __CLASS__, 'create_subject' ) );
		add_action( 'wp_ajax_notes_adda_delete_subject', array( __CLASS__, 'delete_subject' ) );

		// Subject Requests
		add_action( 'wp_ajax_notes_adda_request_subject', array( __CLASS__, 'request_subject' ) );
		add_action( 'wp_ajax_notes_adda_get_my_subject_requests', array( __CLASS__, 'get_my_subject_requests' ) );
		add_action( 'wp_ajax_notes_adda_get_subject_requests', array( __CLASS__, 'get_subject_requests' ) );
		add_action( 'wp_ajax_notes_adda_approve_subject_request', array( __CLASS__, 'approve_subject_request' ) );
		add_action( 'wp_ajax_notes_adda_reject_subject_request', array( __CLASS__, 'reject_subject_request' ) );
		add_action( 'wp_ajax_notes_adda_delete_subject_request', array( __CLASS__, 'delete_subject_request' ) );

		// Bookmarks
		add_action( 'wp_ajax_notes_adda_get_bookmark_status', array( __CLASS__, 'get_bookmark_status' ) );
		add_action( 'wp_ajax_notes_adda_toggle_bookmark', array( __CLASS__, 'toggle_bookmark' ) );
		add_action( 'wp_ajax_notes_adda_get_bookmarked_notes', array( __CLASS__, 'get_bookmarked_notes' ) );
		add_action( 'wp_ajax_notes_adda_query_bookmarks', array( __CLASS__, 'get_bookmarked_notes' ) );

		// User Management (Admin only)
		add_action( 'wp_ajax_notes_adda_get_users', array( __CLASS__, 'get_users' ) );
		add_action( 'wp_ajax_notes_adda_update_user_role', array( __CLASS__, 'update_user_role' ) );

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

		// Uploads
		add_action( 'wp_ajax_notes_adda_upload_note_file', array( __CLASS__, 'upload_note_file' ) );
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
	 * Check authentication and nonce.
	 *
	 * @return int Current WordPress user ID.
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
	 * Query notes handler.
	 */
	public static function query_notes() {
		$args = array();

		$allowed_filters = array(
			'search', 'owner_id', 'tag_id', 'tag_slug', 'subject', 'is_whole_notes',
			'review_status', 'is_review_queue', 'page', 'per_page', 'orderby', 'order'
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
	 * Get full note details with contributor profile and permissions.
	 */
	public static function get_note_details() {
		$note_id = isset( $_REQUEST['note_id'] ) ? (int) $_REQUEST['note_id'] : 0;

		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$current_user_id = is_user_logged_in() ? get_current_user_id() : 0;
		$details = Notes_Adda_Notes::get_details( $note_id, $current_user_id );

		if ( is_wp_error( $details ) ) {
			self::send_error( $details );
		}

		if ( ! $details ) {
			self::send_error( new WP_Error( 'notes_adda_note_not_found', 'Note not found or has been removed.' ) );
		}

		wp_send_json_success( $details );
	}

	/**
	 * Create note handler.
	 */
	public static function create_note() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_upload_notes' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'You do not have permission to upload notes.' ) );
		}

		$data = array();
		$allowed_fields = array( 'title', 'subject', 'chapter', 'description', 'file_url', 'file_id', 'is_whole_notes' );
		foreach ( $allowed_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
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
	 * Review note handler (Verify or Mark Unverified).
	 */
	public static function review_note() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_review_notes' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'You do not have permission to review notes.' ) );
		}

		$note_id = isset( $_POST['note_id'] ) ? (int) $_POST['note_id'] : 0;
		$status  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$review_note = isset( $_POST['review_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review_note'] ) ) : '';

		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		if ( ! in_array( $status, array( 'verified', 'unverified' ), true ) ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_status', 'Status must be verified or unverified.' ) );
		}

		$result = Notes_Adda_Notes::review( $note_id, $user_id, $status, $review_note );

		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Get subjects list handler.
	 */
	public static function get_subjects() {
		$subjects = Notes_Adda_Subjects::get_all();
		wp_send_json_success( $subjects );
	}

	/**
	 * Create subject handler.
	 */
	public static function create_subject() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_manage_subjects' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'You do not have permission to create subjects.' ) );
		}

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( empty( trim( $name ) ) ) {
			self::send_error( new WP_Error( 'notes_adda_missing_name', 'Subject name is required.' ) );
		}

		$subject = Notes_Adda_Subjects::create( $name, $user_id );
		if ( is_wp_error( $subject ) ) {
			self::send_error( $subject );
		}

		wp_send_json_success( $subject );
	}

	/**
	 * Delete subject handler (Admin only).
	 */
	public static function delete_subject() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_manage_users' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'Only Notes Adda Admins can delete subjects.' ) );
		}

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( $id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_id', 'Please provide a valid subject ID.' ) );
		}

		$result = Notes_Adda_Subjects::delete( $id );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( array( 'deleted' => true ) );
	}

	/**
	 * Submit a subject request (Student, Expert, Admin).
	 */
	public static function request_subject() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_request_subjects' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'You do not have permission to request subjects.' ) );
		}

		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( empty( trim( $name ) ) ) {
			self::send_error( new WP_Error( 'notes_adda_missing_name', 'Subject name is required.' ) );
		}

		$result = Notes_Adda_Subject_Requests::request( $name, $user_id, $reason );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Get current user's submitted subject requests.
	 */
	public static function get_my_subject_requests() {
		$user_id = self::check_auth();

		$requests = Notes_Adda_Subject_Requests::get_user_requests( $user_id );
		wp_send_json_success( $requests );
	}

	/**
	 * Get all subject requests (Admin only).
	 */
	public static function get_subject_requests() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_manage_subjects' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'Only Notes Adda Admins can view subject requests.' ) );
		}

		$requests = Notes_Adda_Subject_Requests::get_all_requests();
		wp_send_json_success( $requests );
	}

	/**
	 * Approve a subject request (Admin only).
	 */
	public static function approve_subject_request() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_manage_subjects' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'Only Notes Adda Admins can approve subject requests.' ) );
		}

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( $id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_id', 'Please provide a valid request ID.' ) );
		}

		$result = Notes_Adda_Subject_Requests::approve( $id, $user_id );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Reject a subject request (Admin only).
	 */
	public static function reject_subject_request() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_manage_subjects' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'Only Notes Adda Admins can reject subject requests.' ) );
		}

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( $id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_id', 'Please provide a valid request ID.' ) );
		}

		$result = Notes_Adda_Subject_Requests::reject( $id, $user_id );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Delete a subject request (Admin only).
	 */
	public static function delete_subject_request() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_manage_subjects' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'Only Notes Adda Admins can delete subject requests.' ) );
		}

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( $id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_id', 'Please provide a valid request ID.' ) );
		}

		$result = Notes_Adda_Subject_Requests::delete( $id );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( array( 'deleted' => true ) );
	}

	/**
	 * Get bookmark status handler.
	 */
	public static function get_bookmark_status() {
		$user_id = self::check_auth();
		$note_id = isset( $_REQUEST['note_id'] ) ? (int) $_REQUEST['note_id'] : 0;

		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$is_bookmarked = Notes_Adda_Bookmarks::has_bookmarked( $note_id, $user_id );
		wp_send_json_success( array( 'is_bookmarked' => $is_bookmarked ) );
	}

	/**
	 * Toggle bookmark handler.
	 */
	public static function toggle_bookmark() {
		$user_id = self::check_auth();
		$note_id = isset( $_POST['note_id'] ) ? (int) $_POST['note_id'] : 0;

		if ( $note_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' ) );
		}

		$result = Notes_Adda_Bookmarks::toggle( $note_id, $user_id );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( array( 'is_bookmarked' => $result ) );
	}

	/**
	 * Get bookmarked notes handler for current logged-in user.
	 */
	public static function get_bookmarked_notes() {
		$user_id = self::check_auth();

		$page     = isset( $_REQUEST['page'] ) ? max( 1, (int) $_REQUEST['page'] ) : 1;
		$per_page = isset( $_REQUEST['per_page'] ) ? max( 1, min( 100, (int) $_REQUEST['per_page'] ) ) : 9;

		$result = Notes_Adda_Bookmarks::get_bookmarked_notes( $user_id, $page, $per_page );
		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Backward compatibility alias for query_bookmarks.
	 */
	public static function query_bookmarks() {
		self::get_bookmarked_notes();
	}

	/**
	 * Get users list handler (Admin only).
	 */
	public static function get_users() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_manage_users' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'You do not have permission to view user management.' ) );
		}

		$owner_id = (int) get_option( 'notes_adda_owner_id' );
		$search   = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) : '';
		$page     = isset( $_REQUEST['page'] ) ? max( 1, (int) $_REQUEST['page'] ) : 1;
		$per_page = isset( $_REQUEST['per_page'] ) ? max( 1, min( 100, (int) $_REQUEST['per_page'] ) ) : 20;
		$offset   = ( $page - 1 ) * $per_page;

		$user_args = array(
			'number'  => $per_page,
			'offset'  => $offset,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		);

		if ( ! empty( $search ) ) {
			$user_args['search']         = '*' . $search . '*';
			$user_args['search_columns'] = array( 'user_login', 'user_nicename', 'user_email', 'display_name' );
		}

		$user_query = new WP_User_Query( $user_args );
		$users      = $user_query->get_results();
		$total      = $user_query->get_total_users();

		$user_list = array();
		if ( ! empty( $users ) ) {
			foreach ( $users as $u ) {
				$roles = (array) $u->roles;
				$is_owner = ( $owner_id > 0 && (int) $u->ID === $owner_id );

				$app_role = 'notes_adda_student';
				$app_role_label = 'Student';

				if ( $is_owner ) {
					$app_role = 'notes_adda_admin';
					$app_role_label = 'Owner';
				} elseif ( in_array( 'notes_adda_admin', $roles, true ) || in_array( 'administrator', $roles, true ) ) {
					$app_role = 'notes_adda_admin';
					$app_role_label = 'Notes Adda Admin';
				} elseif ( in_array( 'notes_adda_expert', $roles, true ) ) {
					$app_role = 'notes_adda_expert';
					$app_role_label = 'Expert';
				}

				$profile = class_exists( 'Notes_Adda_User_Profile' ) ? Notes_Adda_User_Profile::get_by_user_id( $u->ID ) : null;

				$user_list[] = array(
					'id'           => $u->ID,
					'username'     => $u->user_login,
					'display_name' => $u->display_name,
					'email'        => $u->user_email,
					'role'         => $app_role,
					'role_label'   => $app_role_label,
					'avatar_url'   => get_avatar_url( $u->ID, array( 'size' => 64 ) ),
					'college'      => $profile ? $profile->college : '',
					'bio'          => $profile ? $profile->bio : '',
					'registered'   => $u->user_registered,
					'is_self'      => ( (int) $u->ID === (int) $user_id ),
					'is_owner'     => $is_owner,
				);
			}
		}

		wp_send_json_success(
			array(
				'users'       => $user_list,
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 0,
			)
		);
	}

	/**
	 * Update user role handler (Admin only).
	 */
	public static function update_user_role() {
		$current_user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_manage_users' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'You do not have permission to manage user roles.' ) );
		}

		$target_user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
		$new_role       = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';

		if ( $target_user_id <= 0 ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_user_id', 'Please provide a valid user ID.' ) );
		}

		// Prevent modifying the Owner
		$owner_id = (int) get_option( 'notes_adda_owner_id' );
		if ( $owner_id > 0 && $target_user_id === $owner_id ) {
			self::send_error( new WP_Error( 'notes_adda_cannot_modify_owner', 'The application owner role cannot be modified.' ) );
		}

		// Prevent self-downgrade / self-modification
		if ( $target_user_id === $current_user_id ) {
			self::send_error( new WP_Error( 'notes_adda_cannot_modify_self', 'You cannot change your own role to prevent accidental lockout.' ) );
		}

		// Normalize role name
		$role_map = array(
			'student'            => 'notes_adda_student',
			'notes_adda_student' => 'notes_adda_student',
			'expert'             => 'notes_adda_expert',
			'notes_adda_expert'  => 'notes_adda_expert',
			'admin'              => 'notes_adda_admin',
			'notes_adda_admin'   => 'notes_adda_admin',
		);

		if ( ! isset( $role_map[ $new_role ] ) ) {
			self::send_error( new WP_Error( 'notes_adda_invalid_role', 'Invalid role specified. Allowed: Student, Expert, Notes Adda Admin.' ) );
		}

		$assigned_role = $role_map[ $new_role ];
		$target_user   = get_userdata( $target_user_id );
		if ( ! $target_user ) {
			self::send_error( new WP_Error( 'notes_adda_user_not_found', 'User does not exist.' ) );
		}

		// Remove only Notes Adda specific roles, preserving WordPress core roles (e.g. administrator)
		$target_user->remove_role( 'notes_adda_student' );
		$target_user->remove_role( 'notes_adda_expert' );
		$target_user->remove_role( 'notes_adda_admin' );

		// Add new role
		$target_user->add_role( $assigned_role );

		wp_send_json_success(
			array(
				'user_id'    => $target_user_id,
				'role'       => $assigned_role,
				'role_label' => ( 'notes_adda_admin' === $assigned_role ? 'Notes Adda Admin' : ( 'notes_adda_expert' === $assigned_role ? 'Expert' : 'Student' ) ),
				'message'    => sprintf( 'Role for %s updated successfully.', $target_user->display_name ),
			)
		);
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
		if ( (int) $note->owner_id !== $user_id && ! current_user_can( 'notes_adda_manage_all_notes' ) && ! current_user_can( 'manage_options' ) ) {
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

		if ( ! current_user_can( 'notes_adda_review_notes' ) && ! current_user_can( 'notes_adda_manage_users' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'You do not have permission to update report status.' ) );
		}

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

	/**
	 * Upload file handler.
	 */
	public static function upload_note_file() {
		$user_id = self::check_auth();

		if ( ! current_user_can( 'notes_adda_upload_notes' ) && ! current_user_can( 'manage_options' ) ) {
			self::send_error( new WP_Error( 'notes_adda_forbidden', 'You do not have permission to upload files.' ) );
		}

		if ( ! isset( $_FILES['file'] ) ) {
			self::send_error( new WP_Error( 'notes_adda_no_file', 'No file was provided.' ) );
		}

		$result = Notes_Adda_Uploads::upload_note_file( $user_id, $_FILES['file'] );

		if ( is_wp_error( $result ) ) {
			self::send_error( $result );
		}

		wp_send_json_success( $result );
	}

}

Notes_Adda_Ajax::init();
