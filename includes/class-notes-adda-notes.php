<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Notes {

	/**
	 * Retrieve a note by its ID.
	 *
	 * @param int $note_id Note ID.
	 * @return object|null|WP_Error Note object if found, null if not found, WP_Error on invalid input.
	 */
	public static function get_by_id( $note_id ) {
		global $wpdb;

		$note_id = (int) $note_id;
		if ( $note_id <= 0 ) {
			return new WP_Error(
				'notes_adda_invalid_note_id',
				'Please provide a valid note ID.'
			);
		}

		$table_name = $wpdb->prefix . 'notes_adda_notes';

		$note = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d",
				$note_id
			)
		);

		return $note ? $note : null;
	}

	/**
	 * Create a new note.
	 *
	 * @param int   $owner_id WordPress user ID of the owner.
	 * @param array $data     Note data (title, subject, file_url, chapter, description, file_id, is_whole_notes).
	 * @return object|WP_Error Created note object on success, WP_Error on failure.
	 */
	public static function create( $owner_id, $data = array() ) {
		global $wpdb;

		$owner_id = (int) $owner_id;
		if ( $owner_id <= 0 ) {
			return new WP_Error(
				'notes_adda_invalid_owner_id',
				'Please provide a valid owner ID.'
			);
		}

		$user = get_userdata( $owner_id );
		if ( ! $user ) {
			return new WP_Error(
				'notes_adda_owner_not_found',
				'WordPress owner user does not exist.'
			);
		}

		$title    = isset( $data['title'] ) ? sanitize_text_field( trim( $data['title'] ) ) : '';
		$subject  = isset( $data['subject'] ) ? sanitize_text_field( trim( $data['subject'] ) ) : '';
		$file_url = isset( $data['file_url'] ) ? esc_url_raw( trim( $data['file_url'] ) ) : '';

		if ( empty( $title ) ) {
			return new WP_Error(
				'notes_adda_missing_title',
				'Title is required.'
			);
		}

		if ( empty( $subject ) ) {
			return new WP_Error(
				'notes_adda_missing_subject',
				'Subject is required.'
			);
		}

		if ( empty( $file_url ) ) {
			return new WP_Error(
				'notes_adda_missing_file_url',
				'File URL is required.'
			);
		}

		$chapter        = isset( $data['chapter'] ) ? sanitize_text_field( $data['chapter'] ) : '';
		$description    = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$file_id        = isset( $data['file_id'] ) ? absint( $data['file_id'] ) : 0;
		$is_whole_notes = ! empty( $data['is_whole_notes'] ) ? 1 : 0;
		$now            = current_time( 'mysql' );

		$table_name = $wpdb->prefix . 'notes_adda_notes';

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'owner_id'       => $owner_id,
				'title'          => $title,
				'subject'        => $subject,
				'chapter'        => $chapter,
				'is_whole_notes' => $is_whole_notes,
				'description'    => $description,
				'file_url'       => $file_url,
				'file_id'        => $file_id,
				'like_count'     => 0,
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'notes_adda_note_create_failed',
				'Failed to create note in database.'
			);
		}

		return self::get_by_id( $wpdb->insert_id );
	}
}
