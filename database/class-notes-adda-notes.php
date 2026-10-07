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

		if ( ! user_can( $owner_id, 'notes_adda_upload_notes' ) && ! user_can( $owner_id, 'read' ) ) {
			return new WP_Error(
				'notes_adda_forbidden',
				'You do not have permission to upload notes.'
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

		// Every new upload starts as pending
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
				'review_status'  => 'pending',
				'reviewed_by'    => null,
				'reviewed_at'    => null,
				'review_note'    => null,
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
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

		// Authorization: Owner or user with notes_adda_manage_all_notes / manage_options
		if ( (int) $note->owner_id !== $actor_id && ! user_can( $actor_id, 'notes_adda_manage_all_notes' ) && ! user_can( $actor_id, 'manage_options' ) ) {
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

		// Workflow rules:
		// 1. Rejected notes: When owner edits/resubmits a rejected note, reset review_status to 'pending'
		//    and clear previous reviewer metadata so it re-enters the active review queue.
		// 2. Verified notes: Meaningful content or file modifications (title, subject, chapter, description,
		//    file URL, file ID, or syllabus scope) return the note to 'pending' for re-verification.
		$current_status = ! empty( $note->review_status ) ? $note->review_status : 'pending';
		if ( 'rejected' === $current_status ) {
			$update_data['review_status'] = 'pending';
			$update_format[]              = '%s';
			$update_data['reviewed_by']   = null;
			$update_format[]              = '%d';
			$update_data['reviewed_at']   = null;
			$update_format[]              = '%s';
			$update_data['review_note']   = null;
			$update_format[]              = '%s';
		} elseif ( 'verified' === $current_status ) {
			$content_fields = array( 'title', 'subject', 'chapter', 'description', 'file_url', 'file_id', 'is_whole_notes' );
			$content_changed = false;
			foreach ( $content_fields as $cf ) {
				if ( isset( $update_data[ $cf ] ) ) {
					$old_val = (string) $note->$cf;
					$new_val = (string) $update_data[ $cf ];
					if ( $old_val !== $new_val ) {
						$content_changed = true;
						break;
					}
				}
			}
			if ( $content_changed ) {
				$update_data['review_status'] = 'pending';
				$update_format[]              = '%s';
				$update_data['reviewed_by']   = null;
				$update_format[]              = '%d';
				$update_data['reviewed_at']   = null;
				$update_format[]              = '%s';
				$update_data['review_note']   = null;
				$update_format[]              = '%s';
			}
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
	 * Review a note (Verify / Reject).
	 *
	 * @param int    $note_id     Note ID.
	 * @param int    $reviewer_id WordPress user ID of the reviewer.
	 * @param string $status      Review status ('verified' or 'rejected').
	 * @param string $review_note Required note from reviewer explaining approval or rejection reason.
	 * @return object|WP_Error Updated note object or WP_Error.
	 */
	public static function review( $note_id, $reviewer_id, $status, $review_note = '' ) {
		global $wpdb;

		$note_id     = (int) $note_id;
		$reviewer_id = (int) $reviewer_id;

		if ( $note_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_note_id', 'Please provide a valid note ID.' );
		}
		if ( $reviewer_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_reviewer_id', 'Please provide a valid reviewer ID.' );
		}

		if ( ! user_can( $reviewer_id, 'notes_adda_review_notes' ) && ! user_can( $reviewer_id, 'manage_options' ) ) {
			return new WP_Error( 'notes_adda_forbidden', 'You do not have permission to review notes.' );
		}

		$note = self::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error( 'notes_adda_note_not_found', 'Note does not exist.' );
		}

		// Self-review restriction: A reviewer cannot review their own note
		if ( (int) $note->owner_id === $reviewer_id ) {
			return new WP_Error( 'notes_adda_cannot_review_own_note', 'Reviewers cannot review their own notes.' );
		}

		$status = sanitize_key( $status );
		if ( ! in_array( $status, array( 'verified', 'rejected' ), true ) ) {
			return new WP_Error( 'notes_adda_invalid_status', 'Invalid review status. Allowed values: verified, rejected.' );
		}

		$review_note = sanitize_textarea_field( trim( $review_note ) );
		if ( empty( $review_note ) ) {
			return new WP_Error( 'notes_adda_missing_review_reason', 'A reviewer reason is required for both approval and rejection.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_notes';
		$now        = current_time( 'mysql' );

		$update_data = array(
			'review_status' => $status,
			'reviewed_by'   => $reviewer_id,
			'reviewed_at'   => $now,
			'review_note'   => $review_note,
			'updated_at'    => $now,
		);
		$format = array( '%s', '%d', '%s', '%s', '%s' );

		$updated = $wpdb->update(
			$table_name,
			$update_data,
			array( 'id' => $note_id ),
			$format,
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'notes_adda_review_failed', 'Failed to update review status in database.' );
		}

		// Create persistent in-app notification for the note owner
		if ( class_exists( 'Notes_Adda_Notifications' ) && (int) $note->owner_id !== $reviewer_id ) {
			if ( 'verified' === $status ) {
				Notes_Adda_Notifications::create(
					(int) $note->owner_id,
					'note_verified',
					'Note Verified: ' . $note->title,
					'Your note "' . $note->title . '" has been verified and published to the library. Reason: ' . $review_note,
					$note_id
				);
			} else {
				Notes_Adda_Notifications::create(
					(int) $note->owner_id,
					'note_rejected',
					'Note Rejected: ' . $note->title,
					'Your note "' . $note->title . '" was rejected by the reviewer. Reason: ' . $review_note,
					$note_id
				);
			}
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

		// Authorization: Only note owner and administrators can delete notes (non-owners and experts cannot)
		$super_owner_id = (int) get_option( 'notes_adda_owner_id' );
		$is_super_owner = ( $super_owner_id > 0 && $actor_id === $super_owner_id );
		$is_admin       = $is_super_owner || user_can( $actor_id, 'notes_adda_manage_users' ) || user_can( $actor_id, 'manage_options' );
		$is_note_owner  = ( (int) $note->owner_id === $actor_id );

		if ( ! $is_note_owner && ! $is_admin ) {
			return new WP_Error( 'notes_adda_forbidden', 'Only the note owner and administrators can delete this note.' );
		}

		$table_notes     = $wpdb->prefix . 'notes_adda_notes';
		$table_tags      = $wpdb->prefix . 'notes_adda_note_tags';
		$table_likes     = $wpdb->prefix . 'notes_adda_likes';
		$table_reports   = $wpdb->prefix . 'notes_adda_reports';
		$table_bookmarks = $wpdb->prefix . 'notes_adda_bookmarks';

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

		// Delete from bookmarks
		$deleted_bookmarks = $wpdb->delete(
			$table_bookmarks,
			array( 'note_id' => $note_id ),
			array( '%d' )
		);
		if ( false === $deleted_bookmarks ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'notes_adda_note_delete_failed', 'Failed to delete note bookmarks from database.' );
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

	/**
	 * Count total published notes for a specific owner.
	 *
	 * @param int $owner_id WordPress user ID of owner.
	 * @return int Total notes count.
	 */
	public static function count_by_owner( $owner_id ) {
		global $wpdb;
		$owner_id = (int) $owner_id;
		if ( $owner_id <= 0 ) {
			return 0;
		}

		$table_name = $wpdb->prefix . 'notes_adda_notes';
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE owner_id = %d",
				$owner_id
			)
		);

		return (int) $count;
	}

	/**
	 * Count total pending notes awaiting review.
	 *
	 * @return int Total pending notes count.
	 */
	public static function get_pending_review_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'notes_adda_notes';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE review_status = 'pending'" );
	}

	/**
	 * Retrieve rich details for a single note, including contributor profile, tags, and user permissions.
	 *
	 * @param int $note_id         Note ID.
	 * @param int $current_user_id Optional current user ID for bookmark and like status.
	 * @return array|null|WP_Error Note details array, null if not found, WP_Error on failure.
	 */
	public static function get_details( $note_id, $current_user_id = 0 ) {
		$note = self::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return null;
		}

		$current_user_id = (int) $current_user_id;
		$owner_id        = (int) $note->owner_id;
		$is_owner        = ( $current_user_id > 0 && $current_user_id === $owner_id );
		$super_owner_id  = (int) get_option( 'notes_adda_owner_id' );
		$is_super_owner  = ( $super_owner_id > 0 && $current_user_id === $super_owner_id );
		$is_admin        = $is_super_owner || user_can( $current_user_id, 'notes_adda_manage_users' ) || user_can( $current_user_id, 'manage_options' );
		$is_reviewer     = $is_admin || user_can( $current_user_id, 'notes_adda_review_notes' );

		// Visibility check: Non-verified (pending or rejected) notes are strictly private to owner and reviewers
		$review_status   = ! empty( $note->review_status ) ? $note->review_status : 'pending';
		if ( 'verified' !== $review_status && ! $is_owner && ! $is_reviewer ) {
			return new WP_Error( 'notes_adda_forbidden', 'This note is not publicly available.' );
		}

		// 1. Owner & Profile Information
		$owner = get_userdata( $owner_id );
		$profile = class_exists( 'Notes_Adda_User_Profile' ) ? Notes_Adda_User_Profile::get_by_user_id( $owner_id ) : null;
		$notes_count = self::count_by_owner( $owner_id );

		$contributor = array(
			'id'           => $owner ? $owner->ID : 0,
			'display_name' => $owner ? $owner->display_name : 'Student',
			'username'     => $owner ? $owner->user_login : '',
			'avatar_url'   => $owner ? get_avatar_url( $owner->ID, array( 'size' => 120 ) ) : '',
			'bio'          => ( $profile && ! empty( $profile->bio ) ) ? $profile->bio : '',
			'notes_count'  => $notes_count,
		);

		if ( class_exists( 'Notes_Adda_Ratings' ) && Notes_Adda_Ratings::is_expert( $owner_id ) ) {
			$contributor['expert_rating'] = Notes_Adda_Ratings::get_expert_summary( $owner_id, $current_user_id );
		}

		// 2. Reviewer Information
		$reviewer_name   = '';
		$reviewer_rating = null;
		if ( ! empty( $note->reviewed_by ) ) {
			$reviewer = get_userdata( (int) $note->reviewed_by );
			if ( $reviewer ) {
				$reviewer_name = $reviewer->display_name;
			}
			if ( 'verified' === $review_status && class_exists( 'Notes_Adda_Ratings' ) && Notes_Adda_Ratings::is_expert( (int) $note->reviewed_by ) ) {
				$reviewer_rating = Notes_Adda_Ratings::get_expert_subject_summary( (int) $note->reviewed_by, $note->subject, $current_user_id );
			}
		}

		// 3. User bookmark & like status
		$is_bookmarked = false;
		$is_liked      = false;
		if ( $current_user_id > 0 ) {
			if ( class_exists( 'Notes_Adda_Bookmarks' ) ) {
				$is_bookmarked = Notes_Adda_Bookmarks::has_bookmarked( $note_id, $current_user_id );
			}
			if ( class_exists( 'Notes_Adda_Likes' ) ) {
				$is_liked = Notes_Adda_Likes::has_liked( $note_id, $current_user_id );
			}
		}

		// 4. Tags
		$tags = array();
		if ( class_exists( 'Notes_Adda_Note_Tags' ) ) {
			$raw_tags = Notes_Adda_Note_Tags::get_tags( $note_id );
			if ( ! is_wp_error( $raw_tags ) && is_array( $raw_tags ) ) {
				$tags = $raw_tags;
			}
		}

		// 5. Capabilities & Permissions (Self-review is prohibited)
		$can_review = $is_reviewer && ! $is_owner;
		$can_edit   = $is_owner || $is_admin;
		$can_delete = $is_owner || $is_admin;

		return array(
			'id'              => (int) $note->id,
			'owner_id'        => $owner_id,
			'title'           => $note->title,
			'subject'         => $note->subject,
			'chapter'         => $note->chapter,
			'is_whole_notes'  => (int) $note->is_whole_notes,
			'description'     => $note->description,
			'file_url'        => $note->file_url,
			'file_id'         => (int) $note->file_id,
			'like_count'      => (int) $note->like_count,
			'review_status'   => $review_status,
			'reviewed_by'     => $note->reviewed_by ? (int) $note->reviewed_by : null,
			'reviewed_at'     => $note->reviewed_at,
			'review_note'     => $note->review_note,
			'created_at'      => $note->created_at,
			'updated_at'      => $note->updated_at,
			'uploader_name'   => $contributor['display_name'],
			'uploader_login'  => $contributor['username'],
			'reviewer_name'   => $reviewer_name,
			'reviewer_rating' => $reviewer_rating,
			'is_bookmarked'   => $is_bookmarked,
			'is_liked'        => $is_liked,
			'is_owner'        => $is_owner,
			'can_edit'        => $can_edit,
			'can_delete'      => $can_delete,
			'can_review'      => $can_review,
			'contributor'     => $contributor,
			'tags'            => $tags,
		);
	}
}
