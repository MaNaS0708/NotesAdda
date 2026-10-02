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
	 * Create a new report.
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

		$user = get_userdata( $reporter_id );
		if ( ! $user ) {
			return new WP_Error( 'notes_adda_reporter_not_found', 'WordPress reporter does not exist.' );
		}

		$note = Notes_Adda_Notes::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error( 'notes_adda_note_not_found', 'Note does not exist.' );
		}

		$reason = sanitize_textarea_field( trim( $reason ) );
		if ( empty( $reason ) ) {
			return new WP_Error( 'notes_adda_missing_reason', 'Report reason is required.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_reports';

		// Check if already reported
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE note_id = %d AND reporter_id = %d",
				$note_id,
				$reporter_id
			)
		);

		if ( $existing ) {
			return new WP_Error( 'notes_adda_already_reported', 'You have already reported this note.' );
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

		return self::get_by_id( $wpdb->insert_id );
	}

	/**
	 * Update the status of a report.
	 *
	 * @param int    $report_id Report ID.
	 * @param int    $actor_id  WordPress user ID of the actor.
	 * @param string $status    New status (open, resolved, dismissed).
	 * @return object|WP_Error Updated report object on success, WP_Error on failure.
	 */
	public static function update_status( $report_id, $actor_id, $status ) {
		global $wpdb;

		$report_id = (int) $report_id;
		$actor_id  = (int) $actor_id;

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

		if ( ! user_can( $actor_id, 'manage_options' ) ) {
			return new WP_Error( 'notes_adda_forbidden', 'You are not allowed to update report status.' );
		}

		$allowed_statuses = array( 'open', 'resolved', 'dismissed' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			return new WP_Error( 'notes_adda_invalid_status', 'Invalid status provided.' );
		}

		$report = self::get_by_id( $report_id );
		if ( is_wp_error( $report ) ) {
			return $report;
		}
		if ( ! $report ) {
			return new WP_Error( 'notes_adda_report_not_found', 'Report does not exist.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_reports';

		$updated = $wpdb->update(
			$table_name,
			array( 'status' => $status ),
			array( 'id' => $report_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'notes_adda_report_update_failed', 'Failed to update report status.' );
		}

		return self::get_by_id( $report_id );
	}

}
