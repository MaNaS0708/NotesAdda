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
	/**
	 * Get paginated bookmarked notes for a specific user with full metadata.
	 *
	 * @param int $user_id WordPress user ID.
	 * @param int $page Page number.
	 * @param int $per_page Items per page.
	 * @return array|WP_Error Query result array on success, WP_Error on failure.
	 */
	public static function get_bookmarked_notes( $user_id, $page = 1, $per_page = 9 ) {
		global $wpdb;

		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_user_id', 'Please provide a valid user ID.' );
		}

		$page     = max( 1, (int) $page );
		$per_page = max( 1, min( 100, (int) $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		$notes_table     = $wpdb->prefix . 'notes_adda_notes';
		$bookmarks_table = $wpdb->prefix . 'notes_adda_bookmarks';

		$count_sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM $bookmarks_table b 
			INNER JOIN $notes_table n ON b.note_id = n.id 
			WHERE b.user_id = %d",
			$user_id
		);
		$total = (int) $wpdb->get_var( $count_sql );

		if ( 0 === $total ) {
			return array(
				'items'       => array(),
				'total'       => 0,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => 0,
			);
		}

		$items_sql = $wpdb->prepare(
			"SELECT n.*, b.created_at AS bookmarked_at 
			FROM $bookmarks_table b 
			INNER JOIN $notes_table n ON b.note_id = n.id 
			WHERE b.user_id = %d 
			ORDER BY b.created_at DESC 
			LIMIT %d OFFSET %d",
			$user_id,
			$per_page,
			$offset
		);
		$items = $wpdb->get_results( $items_sql );

		if ( null === $items ) {
			return new WP_Error( 'notes_adda_query_failed', 'Database query failed.' );
		}

		if ( ! empty( $items ) ) {
			foreach ( $items as &$item ) {
				if ( empty( $item->review_status ) ) {
					$item->review_status = 'unverified';
				}

				$owner = get_userdata( (int) $item->owner_id );
				$item->uploader_name  = $owner ? $owner->display_name : 'Student';
				$item->uploader_login = $owner ? $owner->user_login : '';

				if ( ! empty( $item->reviewed_by ) ) {
					$reviewer = get_userdata( (int) $item->reviewed_by );
					$item->reviewer_name = $reviewer ? $reviewer->display_name : 'Expert Reviewer';
				} else {
					$item->reviewer_name = '';
				}

				$item->is_bookmarked = true;
			}
		}

		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

		return array(
			'items'       => $items ? $items : array(),
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		);
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
