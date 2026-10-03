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

	/**
	 * Update an existing note.
	 *
	 * @param int   $note_id  Note ID.
	 * @param int   $actor_id WordPress user ID of the actor.
	 * @param array $data     Note data to update (title, subject, chapter, description, file_url, file_id, is_whole_notes).
	 * @return object|WP_Error Updated note object on success, WP_Error on failure.
	 */
	public static function update( $note_id, $actor_id, $data = array() ) {
		global $wpdb;

		$note_id  = (int) $note_id;
		$actor_id = (int) $actor_id;

		if ( $note_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' );
		}
		if ( $actor_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_actor_id', 'Please provide a valid actor ID.' );
		}

		$actor = get_userdata( $actor_id );
		if ( ! $actor ) {
			return new WP_Error( 'notes_adda_actor_not_found', 'WordPress actor user does not exist.' );
		}

		$note = self::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error( 'notes_adda_note_not_found', 'Note does not exist.' );
		}

		// Authorization
		if ( $note->owner_id != $actor_id && ! user_can( $actor_id, 'manage_options' ) ) {
			return new WP_Error( 'notes_adda_forbidden', 'You are not allowed to update this note.' );
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'notes_adda_empty_update', 'No valid data provided for update.' );
		}

		$update_data   = array();
		$update_format = array();

		if ( isset( $data['title'] ) ) {
			$title = sanitize_text_field( trim( $data['title'] ) );
			if ( empty( $title ) ) {
				return new WP_Error( 'notes_adda_missing_title', 'Title cannot be empty.' );
			}
			$update_data['title'] = $title;
			$update_format[]      = '%s';
		}

		if ( isset( $data['subject'] ) ) {
			$subject = sanitize_text_field( trim( $data['subject'] ) );
			if ( empty( $subject ) ) {
				return new WP_Error( 'notes_adda_missing_subject', 'Subject cannot be empty.' );
			}
			$update_data['subject'] = $subject;
			$update_format[]        = '%s';
		}

		if ( isset( $data['file_url'] ) ) {
			$file_url = esc_url_raw( trim( $data['file_url'] ) );
			if ( empty( $file_url ) ) {
				return new WP_Error( 'notes_adda_missing_file_url', 'File URL cannot be empty.' );
			}
			$update_data['file_url'] = $file_url;
			$update_format[]         = '%s';
		}

		if ( isset( $data['chapter'] ) ) {
			$update_data['chapter'] = sanitize_text_field( $data['chapter'] );
			$update_format[]        = '%s';
		}

		if ( isset( $data['description'] ) ) {
			$update_data['description'] = sanitize_textarea_field( $data['description'] );
			$update_format[]            = '%s';
		}

		if ( isset( $data['file_id'] ) ) {
			$update_data['file_id'] = absint( $data['file_id'] );
			$update_format[]        = '%d';
		}

		if ( isset( $data['is_whole_notes'] ) ) {
			$update_data['is_whole_notes'] = ! empty( $data['is_whole_notes'] ) ? 1 : 0;
			$update_format[]               = '%d';
		}

		if ( empty( $update_data ) ) {
			return new WP_Error( 'notes_adda_empty_update', 'No valid data provided for update.' );
		}

		$update_data['updated_at'] = current_time( 'mysql' );
		$update_format[]           = '%s';

		$table_name = $wpdb->prefix . 'notes_adda_notes';

		$updated = $wpdb->update(
			$table_name,
			$update_data,
			array( 'id' => $note_id ),
			$update_format,
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'notes_adda_note_update_failed', 'Failed to update note in database.' );
		}

		return self::get_by_id( $note_id );
	}

	/**
	 * Delete a note.
	 *
	 * @param int $note_id  Note ID.
	 * @param int $actor_id WordPress user ID of the actor.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function delete( $note_id, $actor_id ) {
		global $wpdb;

		$note_id  = (int) $note_id;
		$actor_id = (int) $actor_id;

		if ( $note_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' );
		}
		if ( $actor_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_actor_id', 'Please provide a valid actor ID.' );
		}

		$actor = get_userdata( $actor_id );
		if ( ! $actor ) {
			return new WP_Error( 'notes_adda_actor_not_found', 'WordPress actor user does not exist.' );
		}

		$note = self::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error( 'notes_adda_note_not_found', 'Note does not exist.' );
		}

		// Authorization
		if ( $note->owner_id != $actor_id && ! user_can( $actor_id, 'manage_options' ) ) {
			return new WP_Error( 'notes_adda_forbidden', 'You are not allowed to delete this note.' );
		}

		$table_notes = $wpdb->prefix . 'notes_adda_notes';
		$table_tags  = $wpdb->prefix . 'notes_adda_note_tags';
		$table_likes = $wpdb->prefix . 'notes_adda_likes';

		$wpdb->query( 'START TRANSACTION' );

		// Delete from note_tags
		$deleted_tags = $wpdb->delete(
			$table_tags,
			array( 'note_id' => $note_id ),
			array( '%d' )
		);

		if ( false === $deleted_tags ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'notes_adda_note_delete_failed', 'Failed to delete note tags from database.' );
		}

		// Delete from likes
		$deleted_likes = $wpdb->delete(
			$table_likes,
			array( 'note_id' => $note_id ),
			array( '%d' )
		);

		if ( false === $deleted_likes ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'notes_adda_note_delete_failed', 'Failed to delete note likes from database.' );
		}

		$table_reports = $wpdb->prefix . 'notes_adda_reports';
		// Delete from reports
		$deleted_reports = $wpdb->delete(
			$table_reports,
			array( 'note_id' => $note_id ),
			array( '%d' )
		);

		if ( false === $deleted_reports ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'notes_adda_note_delete_failed', 'Failed to delete note reports from database.' );
		}

		// Delete from notes
		$deleted_note = $wpdb->delete(
			$table_notes,
			array( 'id' => $note_id ),
			array( '%d' )
		);

		if ( false === $deleted_note ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'notes_adda_note_delete_failed', 'Failed to delete note from database.' );
		}

		$wpdb->query( 'COMMIT' );

		return true;
	}
}
