<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Likes {

	/**
	 * Check if a user has liked a note.
	 *
	 * @param int $note_id Note ID.
	 * @param int $user_id WordPress user ID.
	 * @return bool True if liked, false otherwise.
	 */
	public static function has_liked( $note_id, $user_id ) {
		global $wpdb;

		$note_id = (int) $note_id;
		$user_id = (int) $user_id;

		if ( $note_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		$table_name = $wpdb->prefix . 'notes_adda_likes';
		
		$liked = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE note_id = %d AND user_id = %d",
				$note_id,
				$user_id
			)
		);

		return (int) $liked > 0;
	}

	/**
	 * Add a like to a note.
	 *
	 * @param int $note_id Note ID.
	 * @param int $user_id WordPress user ID.
	 * @return int|WP_Error Updated like_count on success, WP_Error on failure.
	 */
	public static function add( $note_id, $user_id ) {
		global $wpdb;

		$note_id = (int) $note_id;
		$user_id = (int) $user_id;

		if ( $note_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' );
		}
		if ( $user_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_user_id', 'Please provide a valid user ID.' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error( 'notes_adda_user_not_found', 'WordPress user does not exist.' );
		}

		$note = Notes_Adda_Notes::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error( 'notes_adda_note_not_found', 'Note does not exist.' );
		}

		if ( 'verified' !== $note->review_status ) {
			return new WP_Error( 'notes_adda_forbidden', 'Only verified notes can be liked.' );
		}

		if ( self::has_liked( $note_id, $user_id ) ) {
			return new WP_Error( 'notes_adda_already_liked', 'You have already liked this note.' );
		}

		$table_likes = $wpdb->prefix . 'notes_adda_likes';
		$table_notes = $wpdb->prefix . 'notes_adda_notes';

		$wpdb->query( 'START TRANSACTION' );

		$inserted = $wpdb->insert(
			$table_likes,
			array(
				'note_id'    => $note_id,
				'user_id'    => $user_id,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s' )
		);

		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'notes_adda_like_add_failed', 'Failed to add like to database.' );
		}

		$new_like_count = (int) $note->like_count + 1;

		$updated = $wpdb->update(
			$table_notes,
			array( 'like_count' => $new_like_count ),
			array( 'id' => $note_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'notes_adda_like_count_update_failed', 'Failed to update note like count.' );
		}

		$wpdb->query( 'COMMIT' );

		return $new_like_count;
	}

	/**
	 * Remove a like from a note.
	 *
	 * @param int $note_id Note ID.
	 * @param int $user_id WordPress user ID.
	 * @return int|WP_Error Updated like_count on success, WP_Error on failure.
	 */
	public static function remove( $note_id, $user_id ) {
		global $wpdb;

		$note_id = (int) $note_id;
		$user_id = (int) $user_id;

		if ( $note_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' );
		}
		if ( $user_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_user_id', 'Please provide a valid user ID.' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error( 'notes_adda_user_not_found', 'WordPress user does not exist.' );
		}

		$note = Notes_Adda_Notes::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error( 'notes_adda_note_not_found', 'Note does not exist.' );
		}

		if ( ! self::has_liked( $note_id, $user_id ) ) {
			return new WP_Error( 'notes_adda_like_not_found', 'You have not liked this note.' );
		}

		$table_likes = $wpdb->prefix . 'notes_adda_likes';
		$table_notes = $wpdb->prefix . 'notes_adda_notes';

		$wpdb->query( 'START TRANSACTION' );

		$deleted = $wpdb->delete(
			$table_likes,
			array(
				'note_id' => $note_id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);

		if ( false === $deleted ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'notes_adda_like_remove_failed', 'Failed to remove like from database.' );
		}

		$new_like_count = max( 0, (int) $note->like_count - 1 );

		$updated = $wpdb->update(
			$table_notes,
			array( 'like_count' => $new_like_count ),
			array( 'id' => $note_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'notes_adda_like_count_update_failed', 'Failed to update note like count.' );
		}

		$wpdb->query( 'COMMIT' );

		return $new_like_count;
	}

}
