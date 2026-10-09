<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Subject_Requests {

	/**
	 * Retrieve single request by ID.
	 *
	 * @param int $id Request ID.
	 * @return object|null Request object or null.
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		$id = (int) $id;
		if ( $id <= 0 ) {
			return null;
		}

		$table_name = $wpdb->prefix . 'notes_adda_subject_requests';
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id )
		);
	}

	/**
	 * Create a new subject request from a student or expert.
	 *
	 * @param string $name         Requested subject name.
	 * @param int    $requester_id User ID of requester.
	 * @param string $reason       Optional reason or note.
	 * @return object|WP_Error Created request object or WP_Error.
	 */
	public static function request( $name, $requester_id, $reason = '' ) {
		global $wpdb;

		$name         = Notes_Adda_Subjects::normalize_name( $name );
		$requester_id = (int) $requester_id;
		$reason       = sanitize_textarea_field( $reason );

		if ( empty( $name ) ) {
			return new WP_Error(
				'notes_adda_invalid_subject_name',
				'Subject name is required.'
			);
		}

		if ( $requester_id <= 0 ) {
			return new WP_Error(
				'notes_adda_invalid_user_id',
				'You must be logged in to request a subject.'
			);
		}

		$slug = Notes_Adda_Subjects::normalize_slug( $name );
		if ( empty( $slug ) ) {
			return new WP_Error(
				'notes_adda_invalid_subject_slug',
				'Could not generate a valid slug for this subject name.'
			);
		}

		// 1. Check if this subject is already approved in subjects table
		$existing_approved = Notes_Adda_Subjects::get_by_name( $name );
		if ( ! $existing_approved ) {
			$existing_approved = Notes_Adda_Subjects::get_by_slug( $slug );
		}

		if ( $existing_approved ) {
			return new WP_Error(
				'notes_adda_subject_already_exists',
				'This subject is already approved and available in the subjects list.'
			);
		}

		// 2. Check if a pending request already exists with the same normalized name or slug
		$table_name = $wpdb->prefix . 'notes_adda_subject_requests';
		$pending = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE status = 'pending' AND (LOWER(requested_name) = LOWER(%s) OR requested_slug = %s) LIMIT 1",
				$name,
				$slug
			)
		);

		if ( $pending ) {
			return new WP_Error(
				'notes_adda_request_pending',
				'A request for this subject is already pending review.'
			);
		}

		$now = current_time( 'mysql' );
		$inserted = $wpdb->insert(
			$table_name,
			array(
				'requested_name' => $name,
				'requested_slug' => $slug,
				'requester_id'   => $requester_id,
				'reason'         => $reason,
				'status'         => 'pending',
				'reviewed_by'    => null,
				'reviewed_at'    => null,
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'notes_adda_request_create_failed',
				'Failed to submit subject request. Please try again.'
			);
		}

		$new_id = (int) $wpdb->insert_id;
		return self::get_by_id( $new_id );
	}

	/**
	 * Retrieve all requests submitted by a specific user.
	 *
	 * @param int $user_id User ID.
	 * @return array List of request objects.
	 */
	public static function get_user_requests( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return array();
		}

		$table_name = $wpdb->prefix . 'notes_adda_subject_requests';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE requester_id = %d ORDER BY created_at DESC",
				$user_id
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Retrieve all subject requests (for Admin), optionally filtered by status, sorted pending first, then newest first.
	 *
	 * @param string $status Optional status filter ('pending', 'approved', 'rejected', 'all').
	 * @return array List of enriched request objects.
	 */
	public static function get_all_requests( $status = '' ) {
		global $wpdb;
		$table_name  = $wpdb->prefix . 'notes_adda_subject_requests';
		$users_table = $wpdb->users;

		$where_clauses = array( '1=1' );
		$params        = array();

		$status = sanitize_key( trim( (string) $status ) );
		if ( ! empty( $status ) && 'all' !== $status ) {
			if ( 'verified' === $status ) {
				$status = 'approved';
			}
			$where_clauses[] = 'r.status = %s';
			$params[]        = $status;
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$sql = "SELECT r.*,
					u.display_name AS requester_name,
					u.user_login AS requester_login,
					u.user_email AS requester_email,
					rev.display_name AS reviewer_name,
					rev.user_login AS reviewer_login
				FROM $table_name r
				LEFT JOIN $users_table u ON r.requester_id = u.ID
				LEFT JOIN $users_table rev ON r.reviewed_by = rev.ID
				WHERE $where_sql
				ORDER BY CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END, r.created_at DESC";

		if ( ! empty( $params ) ) {
			$prepared_sql = $wpdb->prepare( $sql, $params );
		} else {
			$prepared_sql = $sql;
		}

		$results = $wpdb->get_results( $prepared_sql );
		return $results ? $results : array();
	}

	/**
	 * Approve a subject request.
	 * Creates subject in subjects table if not exists, updates request to 'approved'.
	 *
	 * @param int $id          Request ID.
	 * @param int $reviewer_id Admin user ID.
	 * @return object|WP_Error Updated request object or WP_Error.
	 */
	public static function approve( $id, $reviewer_id ) {
		global $wpdb;

		$request = self::get_by_id( $id );
		if ( ! $request ) {
			return new WP_Error( 'notes_adda_request_not_found', 'Subject request not found.' );
		}

		// Create subject in approved table if it doesn't already exist
		$existing = Notes_Adda_Subjects::get_by_name( $request->requested_name );
		if ( ! $existing ) {
			$created = Notes_Adda_Subjects::create( $request->requested_name, $request->requester_id );
			if ( is_wp_error( $created ) && 'notes_adda_subject_exists' !== $created->get_error_code() ) {
				return $created;
			}
		}

		$now = current_time( 'mysql' );
		$table_name = $wpdb->prefix . 'notes_adda_subject_requests';

		$updated = $wpdb->update(
			$table_name,
			array(
				'status'      => 'approved',
				'reviewed_by' => (int) $reviewer_id,
				'reviewed_at' => $now,
				'updated_at'  => $now,
			),
			array( 'id' => $request->id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'notes_adda_request_update_failed', 'Could not update subject request.' );
		}

		return self::get_by_id( $request->id );
	}

	/**
	 * Reject a subject request.
	 *
	 * @param int $id          Request ID.
	 * @param int $reviewer_id Admin user ID.
	 * @return object|WP_Error Updated request object or WP_Error.
	 */
	public static function reject( $id, $reviewer_id ) {
		global $wpdb;

		$request = self::get_by_id( $id );
		if ( ! $request ) {
			return new WP_Error( 'notes_adda_request_not_found', 'Subject request not found.' );
		}

		$now = current_time( 'mysql' );
		$table_name = $wpdb->prefix . 'notes_adda_subject_requests';

		$updated = $wpdb->update(
			$table_name,
			array(
				'status'      => 'rejected',
				'reviewed_by' => (int) $reviewer_id,
				'reviewed_at' => $now,
				'updated_at'  => $now,
			),
			array( 'id' => $request->id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'notes_adda_request_update_failed', 'Could not update subject request.' );
		}

		return self::get_by_id( $request->id );
	}

	/**
	 * Delete a subject request.
	 *
	 * @param int $id Request ID.
	 * @return bool|WP_Error True on success or WP_Error.
	 */
	public static function delete( $id ) {
		global $wpdb;

		$request = self::get_by_id( $id );
		if ( ! $request ) {
			return new WP_Error( 'notes_adda_request_not_found', 'Subject request not found.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_subject_requests';
		$deleted = $wpdb->delete(
			$table_name,
			array( 'id' => $request->id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error( 'notes_adda_request_delete_failed', 'Failed to delete subject request.' );
		}

		return true;
	}
}
