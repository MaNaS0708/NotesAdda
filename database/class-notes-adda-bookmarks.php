<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Bookmarks {

	/**
	 * Check if a user has bookmarked a note.
	 *
	 * @param int $note_id Note ID.
	 * @param int $user_id WordPress user ID.
	 * @return bool True if bookmarked, false otherwise.
	 */
	public static function has_bookmarked( $note_id, $user_id ) {
		global $wpdb;

		$note_id = (int) $note_id;
		$user_id = (int) $user_id;

		if ( $note_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		$table_name = $wpdb->prefix . 'notes_adda_bookmarks';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE note_id = %d AND user_id = %d",
				$note_id,
				$user_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Add a bookmark.
	 *
	 * @param int $note_id Note ID.
	 * @param int $user_id WordPress user ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
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

		$note = Notes_Adda_Notes::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error( 'notes_adda_note_not_found', 'Note does not exist.' );
		}

		if ( self::has_bookmarked( $note_id, $user_id ) ) {
			return true;
		}

		$table_name = $wpdb->prefix . 'notes_adda_bookmarks';
		$inserted   = $wpdb->insert(
			$table_name,
			array(
				'note_id'    => $note_id,
				'user_id'    => $user_id,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'notes_adda_bookmark_add_failed', 'Failed to bookmark note.' );
		}

		return true;
	}

	/**
	 * Remove a bookmark.
	 *
	 * @param int $note_id Note ID.
	 * @param int $user_id WordPress user ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function remove( $note_id, $user_id ) {
		global $wpdb;

		$note_id = (int) $note_id;
		$user_id = (int) $user_id;

		if ( $note_id <= 0 || $user_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_params', 'Invalid note or user ID.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_bookmarks';
		$deleted    = $wpdb->delete(
			$table_name,
			array(
				'note_id' => $note_id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error( 'notes_adda_bookmark_remove_failed', 'Failed to remove bookmark.' );
		}

		return true;
	}

	/**
	 * Toggle a bookmark.
	 *
	 * @param int $note_id Note ID.
	 * @param int $user_id WordPress user ID.
	 * @return bool|WP_Error New bookmarked status (true/false) on success, WP_Error on failure.
	 */
	public static function toggle( $note_id, $user_id ) {
		if ( self::has_bookmarked( $note_id, $user_id ) ) {
			$res = self::remove( $note_id, $user_id );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			return false;
		} else {
			$res = self::add( $note_id, $user_id );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			return true;
		}
	}

	/**
	 * Get bookmarked notes for a specific user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @param int $page Page number.
	 * @param int $per_page Items per page.
	 * @return array|WP_Error Query result array on success, WP_Error on failure.
	 */
	public static function get_by_user( $user_id, $page = 1, $per_page = 10 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_user_id', 'Please provide a valid user ID.' );
		}

		return Notes_Adda_Note_Query::get_notes( array(
			'bookmarked_by' => $user_id,
			'page'          => $page,
			'per_page'      => $per_page,
		) );
	}

	/**
	 * Clean up bookmarks when a note is deleted.
	 *
	 * @param int $note_id Note ID.
	 * @return bool True on success.
	 */
	public static function delete_by_note_id( $note_id ) {
		global $wpdb;
		$note_id = (int) $note_id;
		if ( $note_id <= 0 ) {
			return false;
		}

		$table_name = $wpdb->prefix . 'notes_adda_bookmarks';
		$wpdb->delete(
			$table_name,
			array( 'note_id' => $note_id ),
			array( '%d' )
		);
		return true;
	}
}
