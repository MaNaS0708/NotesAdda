<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Notifications {

	/**
	 * Create a new notification.
	 *
	 * @param int    $user_id User ID receiving the notification.
	 * @param string $type    Notification type (e.g. 'note_verified', 'note_rejected').
	 * @param string $title   Notification title.
	 * @param string $message Notification message content.
	 * @param int    $note_id Associated note ID (optional).
	 * @return int|WP_Error Notification ID on success, WP_Error on failure.
	 */
	public static function create( $user_id, $type, $title, $message, $note_id = 0 ) {
		global $wpdb;

		$user_id = (int) $user_id;
		$note_id = (int) $note_id;

		if ( $user_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_user_id', 'Please provide a valid user ID.' );
		}

		$type    = sanitize_key( trim( $type ) );
		$title   = sanitize_text_field( trim( $title ) );
		$message = sanitize_textarea_field( trim( $message ) );

		if ( empty( $title ) || empty( $message ) ) {
			return new WP_Error( 'notes_adda_invalid_data', 'Title and message are required.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_notifications';
		$now        = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'user_id'    => $user_id,
				'type'       => $type,
				'title'      => $title,
				'message'    => $message,
				'note_id'    => $note_id,
				'is_read'    => 0,
				'created_at' => $now,
				'read_at'    => null,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'notes_adda_db_error', 'Failed to insert notification into database.' );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Retrieve notifications for a specific user.
	 *
	 * @param int  $user_id     User ID.
	 * @param int  $page        Page number (1-indexed).
	 * @param int  $per_page    Notifications per page.
	 * @param bool $unread_only Whether to retrieve only unread notifications.
	 * @return array Array with items, total, unread_count.
	 */
	public static function get_for_user( $user_id, $page = 1, $per_page = 20, $unread_only = false ) {
		global $wpdb;

		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return array(
				'items'        => array(),
				'total'        => 0,
				'unread_count' => 0,
			);
		}

		$page     = max( 1, (int) $page );
		$per_page = max( 1, min( 100, (int) $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		$table_name = $wpdb->prefix . 'notes_adda_notifications';

		$unread_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_read = 0",
				$user_id
			)
		);

		$where = 'user_id = %d';
		$params = array( $user_id );

		if ( $unread_only ) {
			$where   .= ' AND is_read = 0';
		}

		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE $where", $params )
		);

		$items_sql = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
			array_merge( $params, array( $per_page, $offset ) )
		);

		$rows = $wpdb->get_results( $items_sql );
		$items = array();

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$items[] = array(
					'id'         => (int) $row->id,
					'user_id'    => (int) $row->user_id,
					'type'       => $row->type,
					'title'      => $row->title,
					'message'    => $row->message,
					'note_id'    => (int) $row->note_id,
					'is_read'    => (bool) $row->is_read,
					'created_at' => $row->created_at,
					'read_at'    => $row->read_at,
				);
			}
		}

		return array(
			'items'        => $items,
			'total'        => $total,
			'unread_count' => $unread_count,
			'page'         => $page,
			'per_page'     => $per_page,
		);
	}

	/**
	 * Get unread notification count for a user.
	 *
	 * @param int $user_id User ID.
	 * @return int Unread count.
	 */
	public static function get_unread_count( $user_id ) {
		global $wpdb;

		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return 0;
		}

		$table_name = $wpdb->prefix . 'notes_adda_notifications';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_read = 0",
				$user_id
			)
		);
	}

	/**
	 * Mark a single notification as read.
	 *
	 * @param int $notification_id Notification ID.
	 * @param int $user_id         User ID (owner verification).
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function mark_as_read( $notification_id, $user_id ) {
		global $wpdb;

		$notification_id = (int) $notification_id;
		$user_id         = (int) $user_id;

		if ( $notification_id <= 0 || $user_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_args', 'Invalid notification or user ID.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_notifications';
		$now        = current_time( 'mysql' );

		$updated = $wpdb->update(
			$table_name,
			array(
				'is_read' => 1,
				'read_at' => $now,
			),
			array(
				'id'      => $notification_id,
				'user_id' => $user_id,
			),
			array( '%d', '%s' ),
			array( '%d', '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'notes_adda_db_error', 'Failed to update notification.' );
		}

		return true;
	}

	/**
	 * Mark all notifications as read for a user.
	 *
	 * @param int $user_id User ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function mark_all_as_read( $user_id ) {
		global $wpdb;

		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_user_id', 'Invalid user ID.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_notifications';
		$now        = current_time( 'mysql' );

		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_name SET is_read = 1, read_at = %s WHERE user_id = %d AND is_read = 0",
				$now,
				$user_id
			)
		);

		if ( false === $updated ) {
			return new WP_Error( 'notes_adda_db_error', 'Failed to mark notifications as read.' );
		}

		return true;
	}
}
