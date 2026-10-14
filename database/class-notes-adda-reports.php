<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Reports {

	/**
	 * Retrieve a report by its ID.
	 *
	 * @param int $report_id Report ID.
	 * @return object|null|WP_Error Report object if found, null if not found, WP_Error on invalid input.
	 */
	public static function get_by_id( $report_id ) {
		global $wpdb;

		$report_id = (int) $report_id;
		if ( $report_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_report_id', 'Please provide a valid report ID.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_reports';

		$report = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d",
				$report_id
			)
		);

		return $report ? $report : null;
	}

	/**
	 * Get user IDs of all admins and experts who should be notified on reports.
	 *
	 * @return int[] Unique array of user IDs.
	 */
	public static function get_moderator_user_ids() {
		$moderator_ids = array();

		// Site owner
		$owner_id = (int) get_option( 'notes_adda_owner_id' );
		if ( $owner_id > 0 ) {
			$moderator_ids[] = $owner_id;
		}

		// WordPress Administrators
		$admins = get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) );
		if ( ! empty( $admins ) ) {
			foreach ( $admins as $uid ) {
				$moderator_ids[] = (int) $uid;
			}
		}

		// Notes Adda Admins
		$na_admins = get_users( array( 'role' => 'notes_adda_admin', 'fields' => 'ID' ) );
		if ( ! empty( $na_admins ) ) {
			foreach ( $na_admins as $uid ) {
				$moderator_ids[] = (int) $uid;
			}
		}

		// Notes Adda Experts
		$na_experts = get_users( array( 'role' => 'notes_adda_expert', 'fields' => 'ID' ) );
		if ( ! empty( $na_experts ) ) {
			foreach ( $na_experts as $uid ) {
				$moderator_ids[] = (int) $uid;
			}
		}

		// Users with explicit review capability
		$capable_users = get_users( array( 'capability' => 'notes_adda_review_notes', 'fields' => 'ID' ) );
		if ( ! empty( $capable_users ) ) {
			foreach ( $capable_users as $uid ) {
				$moderator_ids[] = (int) $uid;
			}
		}

		return array_values( array_unique( array_filter( $moderator_ids ) ) );
	}

	/**
	 * Create a new report and notify all relevant parties (reporter, owner, admins & experts).
	 *
	 * @param int    $note_id     Note ID.
	 * @param int    $reporter_id WordPress user ID of the reporter.
	 * @param string $reason      Reason for reporting.
	 * @return object|WP_Error Created report object on success, WP_Error on failure.
	 */
	public static function create( $note_id, $reporter_id, $reason ) {
		global $wpdb;

		$note_id     = (int) $note_id;
		$reporter_id = (int) $reporter_id;

		if ( $note_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' );
		}
		if ( $reporter_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_reporter_id', 'Please provide a valid reporter ID.' );
		}

		$reporter = get_userdata( $reporter_id );
		if ( ! $reporter ) {
			return new WP_Error( 'notes_adda_reporter_not_found', 'WordPress reporter does not exist.' );
		}

		$note = Notes_Adda_Notes::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error( 'notes_adda_note_not_found', 'Note does not exist.' );
		}

		if ( 'verified' !== $note->review_status ) {
			return new WP_Error( 'notes_adda_forbidden', 'Only verified notes can be reported.' );
		}

		// Prevent reporting own notes
		if ( (int) $note->owner_id === $reporter_id ) {
			return new WP_Error( 'notes_adda_cannot_report_own_note', 'You cannot report your own note.' );
		}

		$reason = sanitize_textarea_field( trim( $reason ) );
		if ( empty( $reason ) ) {
			return new WP_Error( 'notes_adda_missing_reason', 'Report reason is required.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_reports';

		// Check if user already has an open report for this note
		$existing_open = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE note_id = %d AND reporter_id = %d AND status = 'open'",
				$note_id,
				$reporter_id
			)
		);

		if ( $existing_open ) {
			return new WP_Error( 'notes_adda_already_reported', 'You have already reported this note. It is awaiting moderation.' );
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'note_id'     => $note_id,
				'reporter_id' => $reporter_id,
				'reason'      => $reason,
				'status'      => 'open',
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'notes_adda_report_create_failed', 'Failed to create report in database.' );
		}

		$report_id = (int) $wpdb->insert_id;
		$report    = self::get_by_id( $report_id );

		// 1. In-app notification to the Reporter
		if ( class_exists( 'Notes_Adda_Notifications' ) ) {
			Notes_Adda_Notifications::create(
				$reporter_id,
				'report_submitted',
				'Report Submitted: ' . $note->title,
				'Your report for "' . $note->title . '" has been received and flagged for expert & admin review.',
				$note_id
			);

			// 2. In-app notification to the Note Owner
			if ( (int) $note->owner_id > 0 && (int) $note->owner_id !== $reporter_id ) {
				Notes_Adda_Notifications::create(
					(int) $note->owner_id,
					'note_flagged',
					'Note Flagged for Review: ' . $note->title,
					'Your note "' . $note->title . '" has been reported by a community member and is under moderation review. Reason: ' . $reason,
					$note_id
				);
			}

			// 3. In-app notification to all Admins & Experts
			$moderator_ids  = self::get_moderator_user_ids();
			$reporter_label = $reporter->display_name ? $reporter->display_name : $reporter->user_login;
			foreach ( $moderator_ids as $mod_id ) {
				if ( $mod_id === $reporter_id || $mod_id === (int) $note->owner_id ) {
					continue;
				}
				Notes_Adda_Notifications::create(
					$mod_id,
					'report_flagged',
					'Note Reported: ' . $note->title,
					'Student ' . $reporter_label . ' reported note "' . $note->title . '". Reason: ' . $reason,
					$note_id
				);
			}
		}

		return $report;
	}

	/**
	 * Retrieve reports with filters, joins, and pagination.
	 *
	 * @param array $args Query arguments (status, page, per_page, search).
	 * @return array Array with items, total, page, per_page, total_pages.
	 */
	public static function get_reports( $args = array() ) {
		global $wpdb;

		$reports_table = $wpdb->prefix . 'notes_adda_reports';
		$notes_table   = $wpdb->prefix . 'notes_adda_notes';

		$page     = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$per_page = isset( $args['per_page'] ) ? max( 1, min( 100, (int) $args['per_page'] ) ) : 10;
		$offset   = ( $page - 1 ) * $per_page;

		$status = isset( $args['status'] ) ? sanitize_key( trim( $args['status'] ) ) : 'open';
		if ( empty( $status ) ) {
			$status = 'open';
		}

		$where_clauses = array( '1=1' );
		$params        = array();

		if ( 'all' !== $status ) {
			$where_clauses[] = 'r.status = %s';
			$params[]        = $status;
		}

		if ( isset( $args['search'] ) && '' !== trim( $args['search'] ) ) {
			$search          = sanitize_text_field( trim( $args['search'] ) );
			$like            = '%' . $wpdb->esc_like( $search ) . '%';
			$where_clauses[] = '(n.title LIKE %s OR n.subject LIKE %s OR r.reason LIKE %s)';
			$params[]        = $like;
			$params[]        = $like;
			$params[]        = $like;
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$count_sql = "SELECT COUNT(*) FROM $reports_table r INNER JOIN $notes_table n ON r.note_id = n.id WHERE $where_sql";
		$total     = (int) ( ! empty( $params ) ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) );

		$items_sql = "SELECT r.*,
		                     n.title AS note_title,
		                     n.subject AS note_subject,
		                     n.chapter AS note_chapter,
		                     n.file_url AS note_file_url,
		                     n.owner_id AS note_owner_id,
		                     n.review_status AS note_review_status
		              FROM $reports_table r
		              INNER JOIN $notes_table n ON r.note_id = n.id
		              WHERE $where_sql
		              ORDER BY (CASE WHEN r.status = 'open' THEN 0 ELSE 1 END), r.created_at DESC
		              LIMIT %d OFFSET %d";

		$items_params = array_merge( $params, array( $per_page, $offset ) );
		$rows         = $wpdb->get_results( $wpdb->prepare( $items_sql, $items_params ) );

		$items = array();
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$reporter = get_userdata( (int) $row->reporter_id );
				$owner    = get_userdata( (int) $row->note_owner_id );
				$resolver = ! empty( $row->resolved_by ) ? get_userdata( (int) $row->resolved_by ) : null;

				$items[] = array(
					'id'                 => (int) $row->id,
					'note_id'            => (int) $row->note_id,
					'reporter_id'        => (int) $row->reporter_id,
					'reporter_name'      => $reporter ? $reporter->display_name : 'Student',
					'reporter_login'     => $reporter ? $reporter->user_login : '',
					'reason'             => $row->reason,
					'status'             => $row->status,
					'resolved_by'        => (int) $row->resolved_by,
					'resolver_name'      => $resolver ? $resolver->display_name : '',
					'resolved_at'        => $row->resolved_at,
					'resolution_note'    => $row->resolution_note,
					'created_at'         => $row->created_at,
					'note_title'         => $row->note_title,
					'note_subject'       => $row->note_subject,
					'note_chapter'       => $row->note_chapter,
					'note_file_url'      => $row->note_file_url,
					'note_owner_id'      => (int) $row->note_owner_id,
					'note_owner_name'    => $owner ? $owner->display_name : 'Author',
					'note_review_status' => $row->note_review_status,
				);
			}
		}

		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

		return array(
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		);
	}

	/**
	 * Get total count of open (unresolved) reports.
	 *
	 * @return int Total open reports.
	 */
	public static function get_open_reports_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'notes_adda_reports';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'open'" );
	}

	/**
	 * Check if a note currently has any open reports.
	 *
	 * @param int $note_id Note ID.
	 * @return bool True if has open reports.
	 */
	public static function has_open_report( $note_id ) {
		global $wpdb;
		$note_id = (int) $note_id;
		if ( $note_id <= 0 ) {
			return false;
		}
		$table_name = $wpdb->prefix . 'notes_adda_reports';
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM $table_name WHERE note_id = %d AND status = 'open' LIMIT 1", $note_id ) );
	}

	/**
	 * Resolve a report: either accept (reject note) or dismiss (keep note verified).
	 * Dispatches notifications to Reporter, Note Owner, and Moderators.
	 *
	 * @param int    $report_id       Report ID.
	 * @param int    $actor_id        Moderator user ID.
	 * @param string $decision        'accept' (take down note) or 'dismiss' (keep note).
	 * @param string $resolution_note Explanation for the decision.
	 * @return object|WP_Error Updated report on success, WP_Error on failure.
	 */
	public static function resolve_report( $report_id, $actor_id, $decision, $resolution_note ) {
		global $wpdb;

		$report_id = (int) $report_id;
		$actor_id  = (int) $actor_id;
		$decision  = sanitize_key( trim( $decision ) );

		if ( $report_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_report_id', 'Please provide a valid report ID.' );
		}
		if ( $actor_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_actor_id', 'Please provide a valid actor ID.' );
		}

		$actor = get_userdata( $actor_id );
		if ( ! $actor ) {
			return new WP_Error( 'notes_adda_actor_not_found', 'WordPress actor user does not exist.' );
		}

		$owner_id = (int) get_option( 'notes_adda_owner_id' );
		$is_owner = ( $owner_id > 0 && $actor_id === $owner_id );
		if ( ! $is_owner && ! user_can( $actor_id, 'notes_adda_review_notes' ) && ! user_can( $actor_id, 'manage_options' ) ) {
			return new WP_Error( 'notes_adda_forbidden', 'You are not allowed to resolve reports.' );
		}

		if ( ! in_array( $decision, array( 'accept', 'dismiss' ), true ) ) {
			return new WP_Error( 'notes_adda_invalid_decision', 'Invalid decision. Allowed values: accept, dismiss.' );
		}

		$resolution_note = sanitize_textarea_field( trim( $resolution_note ) );
		if ( empty( $resolution_note ) ) {
			return new WP_Error( 'notes_adda_missing_resolution_note', 'A moderation explanation/reason is required.' );
		}

		$report = self::get_by_id( $report_id );
		if ( is_wp_error( $report ) ) {
			return $report;
		}
		if ( ! $report ) {
			return new WP_Error( 'notes_adda_report_not_found', 'Report does not exist.' );
		}

		$note = Notes_Adda_Notes::get_by_id( (int) $report->note_id );
		if ( is_wp_error( $note ) || ! $note ) {
			return new WP_Error( 'notes_adda_note_not_found', 'Associated note does not exist.' );
		}

		$reports_table = $wpdb->prefix . 'notes_adda_reports';
		$notes_table   = $wpdb->prefix . 'notes_adda_notes';
		$now           = current_time( 'mysql' );
		$new_status    = ( 'accept' === $decision ) ? 'resolved' : 'dismissed';

		// Update Report status
		$updated_report = $wpdb->update(
			$reports_table,
			array(
				'status'          => $new_status,
				'resolved_by'     => $actor_id,
				'resolved_at'     => $now,
				'resolution_note' => $resolution_note,
			),
			array( 'id' => $report_id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated_report ) {
			return new WP_Error( 'notes_adda_report_update_failed', 'Failed to update report status.' );
		}

		$actor_name = $actor->display_name ? $actor->display_name : $actor->user_login;

		if ( 'accept' === $decision ) {
			// Accept Report -> Reject the Note (takes note down from public library)
			$wpdb->update(
				$notes_table,
				array(
					'review_status' => 'rejected',
					'reviewed_by'   => $actor_id,
					'reviewed_at'   => $now,
					'review_note'   => 'Rejected following community report: ' . $resolution_note,
					'updated_at'    => $now,
				),
				array( 'id' => $note->id ),
				array( '%s', '%d', '%s', '%s', '%s' ),
				array( '%d' )
			);

			// Also mark any other open reports for this note as resolved
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $reports_table SET status = 'resolved', resolved_by = %d, resolved_at = %s, resolution_note = %s WHERE note_id = %d AND status = 'open'",
					$actor_id,
					$now,
					$resolution_note,
					$note->id
				)
			);

			// Notifications
			if ( class_exists( 'Notes_Adda_Notifications' ) ) {
				// 1. Reporter: Report Accepted
				Notes_Adda_Notifications::create(
					(int) $report->reporter_id,
					'report_accepted',
					'Report Accepted: ' . $note->title,
					'Your report for "' . $note->title . '" was accepted by ' . $actor_name . '. The note has been taken down. Reason: ' . $resolution_note,
					$note->id
				);

				// 2. Note Owner: Note Rejected
				if ( (int) $note->owner_id > 0 && (int) $note->owner_id !== (int) $report->reporter_id ) {
					Notes_Adda_Notifications::create(
						(int) $note->owner_id,
						'note_rejected',
						'Note Rejected following Report: ' . $note->title,
						'Your note "' . $note->title . '" was reviewed following a user report and rejected by ' . $actor_name . '. Reason: ' . $resolution_note,
						$note->id
					);
				}

				// 3. Other Admins & Experts
				$moderators = self::get_moderator_user_ids();
				foreach ( $moderators as $mid ) {
					if ( $mid === $actor_id || $mid === (int) $report->reporter_id || $mid === (int) $note->owner_id ) {
						continue;
					}
					Notes_Adda_Notifications::create(
						$mid,
						'report_resolved',
						'Report Accepted: ' . $note->title,
						'Moderator ' . $actor_name . ' accepted the report on note "' . $note->title . '" and rejected the note.',
						$note->id
					);
				}
			}
		} else {
			// Dismiss Report -> Keep Note Verified
			// Notifications
			if ( class_exists( 'Notes_Adda_Notifications' ) ) {
				// 1. Reporter: Report Dismissed
				Notes_Adda_Notifications::create(
					(int) $report->reporter_id,
					'report_dismissed',
					'Report Dismissed: ' . $note->title,
					'Your report for "' . $note->title . '" was reviewed by ' . $actor_name . ' and dismissed as invalid. The note remains verified. Note: ' . $resolution_note,
					$note->id
				);

				// 2. Note Owner: Notification that report was dismissed
				if ( (int) $note->owner_id > 0 && (int) $note->owner_id !== (int) $report->reporter_id ) {
					Notes_Adda_Notifications::create(
						(int) $note->owner_id,
						'report_dismissed_owner',
						'Report Dismissed: ' . $note->title,
						'The report filed against your note "' . $note->title . '" was reviewed by ' . $actor_name . ' and dismissed. Your note remains verified.',
						$note->id
					);
				}

				// 3. Other Admins & Experts
				$moderators = self::get_moderator_user_ids();
				foreach ( $moderators as $mid ) {
					if ( $mid === $actor_id || $mid === (int) $report->reporter_id || $mid === (int) $note->owner_id ) {
						continue;
					}
					Notes_Adda_Notifications::create(
						$mid,
						'report_dismissed_admin',
						'Report Dismissed: ' . $note->title,
						'Moderator ' . $actor_name . ' reviewed and dismissed the report on note "' . $note->title . '".',
						$note->id
					);
				}
			}
		}

		return self::get_by_id( $report_id );
	}

	/**
	 * Legacy update status method for backward compatibility.
	 *
	 * @param int    $report_id Report ID.
	 * @param int    $actor_id  WordPress user ID of the actor.
	 * @param string $status    New status (open, resolved, dismissed).
	 * @return object|WP_Error Updated report object on success, WP_Error on failure.
	 */
	public static function update_status( $report_id, $actor_id, $status ) {
		$decision = ( 'resolved' === $status ) ? 'accept' : 'dismiss';
		return self::resolve_report( $report_id, $actor_id, $decision, 'Status updated via legacy control' );
	}

}
