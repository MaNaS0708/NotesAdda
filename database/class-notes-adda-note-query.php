<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Note_Query {

	/**
	 * Query notes with pagination and filters.
	 *
	 * @param array $args Query filters.
	 * @return array|WP_Error Array with items, total, page, per_page, total_pages on success, WP_Error on failure.
	 */
	public static function get_notes( $args = array() ) {
		global $wpdb;

		if ( ! is_array( $args ) ) {
			return new WP_Error(
				'notes_adda_invalid_args',
				'Arguments must be an array.'
			);
		}

		// Alias 'status' to 'review_status' if not explicitly provided
		if ( ! isset( $args['review_status'] ) && isset( $args['status'] ) ) {
			$args['review_status'] = $args['status'];
		}

		$is_review_queue = ! empty( $args['is_review_queue'] );

		$current_user_id = is_user_logged_in() ? get_current_user_id() : 0;
		$super_owner_id  = (int) get_option( 'notes_adda_owner_id' );
		$is_super_owner  = ( $super_owner_id > 0 && $current_user_id === $super_owner_id );
		$is_admin        = $is_super_owner || user_can( $current_user_id, 'notes_adda_manage_users' ) || user_can( $current_user_id, 'manage_options' );
		$is_reviewer     = $is_admin || user_can( $current_user_id, 'notes_adda_review_notes' );

		// If caller is a reviewer and querying pending/rejected notes without specifying owner_id, treat as review queue
		if ( ! $is_review_queue && $is_reviewer && isset( $args['review_status'] ) && in_array( $args['review_status'], array( 'pending', 'rejected' ), true ) && empty( $args['owner_id'] ) ) {
			$is_review_queue = true;
		}

		if ( $is_review_queue && ! $is_reviewer ) {
			return new WP_Error(
				'notes_adda_forbidden',
				'You do not have permission to access the Review Queue.'
			);
		}

		$orderby_raw     = isset( $args['orderby'] ) ? strtolower( trim( $args['orderby'] ) ) : 'created_at';
		$allowed_orderby = array( 'created_at', 'title', 'like_count' );
		if ( ! in_array( $orderby_raw, $allowed_orderby, true ) ) {
			$orderby_raw = 'created_at';
		}

		$order_raw     = isset( $args['order'] ) ? strtoupper( trim( $args['order'] ) ) : 'DESC';
		$allowed_order = array( 'ASC', 'DESC' );
		if ( ! in_array( $order_raw, $allowed_order, true ) ) {
			$order_raw = 'DESC';
		}

		$page     = isset( $args['page'] ) ? (int) $args['page'] : 1;
		$per_page = isset( $args['per_page'] ) ? (int) $args['per_page'] : 10;

		if ( $page < 1 ) {
			return new WP_Error(
				'notes_adda_invalid_page',
				'Page parameter must be an integer greater than or equal to 1.'
			);
		}

		if ( $per_page < 1 || $per_page > 100 ) {
			return new WP_Error(
				'notes_adda_invalid_per_page',
				'Per page parameter must be an integer between 1 and 100.'
			);
		}

		$offset = ( $page - 1 ) * $per_page;

		$notes_table     = $wpdb->prefix . 'notes_adda_notes';
		$note_tags_table = $wpdb->prefix . 'notes_adda_note_tags';
		$tags_table      = $wpdb->prefix . 'notes_adda_tags';
		$bookmarks_table = $wpdb->prefix . 'notes_adda_bookmarks';

		$where_clauses = array( '1=1' );
		$query_params  = array();

		if ( isset( $args['owner_id'] ) ) {
			$owner_id = (int) $args['owner_id'];
			if ( $owner_id > 0 ) {
				$where_clauses[] = 'n.owner_id = %d';
				$query_params[]  = $owner_id;
			}
		}

		if ( isset( $args['bookmarked_by'] ) ) {
			$bookmarked_by = (int) $args['bookmarked_by'];
			if ( $bookmarked_by > 0 ) {
				$where_clauses[] = "EXISTS (SELECT 1 FROM $bookmarks_table b WHERE b.note_id = n.id AND b.user_id = %d)";
				$query_params[]  = $bookmarked_by;
			}
		}

		// Visibility & Review Status enforcement:
		if ( $is_review_queue ) {
			// Review Queue default tab is 'pending'
			$review_status = isset( $args['review_status'] ) ? sanitize_key( trim( $args['review_status'] ) ) : 'pending';
			if ( empty( $review_status ) ) {
				$review_status = 'pending';
			}
			if ( 'all' !== $review_status ) {
				$where_clauses[] = 'n.review_status = %s';
				$query_params[]  = $review_status;
			}
		} else {
			// Non-review queue: If user is querying their own notes, they can see pending, verified, or rejected
			$querying_own_notes = ( isset( $args['owner_id'] ) && (int) $args['owner_id'] > 0 && (int) $args['owner_id'] === $current_user_id );
			if ( $querying_own_notes ) {
				if ( isset( $args['review_status'] ) && '' !== trim( $args['review_status'] ) && 'all' !== $args['review_status'] ) {
					$review_status   = sanitize_key( trim( $args['review_status'] ) );
					$where_clauses[] = 'n.review_status = %s';
					$query_params[]  = $review_status;
				}
			} else {
				// Public Browse Library, search, subject filters, and bookmarks strictly return ONLY verified notes
				$where_clauses[] = "n.review_status = 'verified'";
			}
		}

		if ( isset( $args['subject'] ) && '' !== trim( $args['subject'] ) ) {
			$subject         = sanitize_text_field( trim( $args['subject'] ) );
			$where_clauses[] = 'n.subject = %s';
			$query_params[]  = $subject;
		}

		if ( isset( $args['is_whole_notes'] ) && '' !== $args['is_whole_notes'] ) {
			$is_whole        = ! empty( $args['is_whole_notes'] ) ? 1 : 0;
			$where_clauses[] = 'n.is_whole_notes = %d';
			$query_params[]  = $is_whole;
		}

		if ( isset( $args['tag_id'] ) ) {
			$tag_id = (int) $args['tag_id'];
			if ( $tag_id > 0 ) {
				$where_clauses[] = "EXISTS (SELECT 1 FROM $note_tags_table nt WHERE nt.note_id = n.id AND nt.tag_id = %d)";
				$query_params[]  = $tag_id;
			}
		}

		if ( isset( $args['tag_slug'] ) && '' !== trim( $args['tag_slug'] ) ) {
			$tag_slug        = sanitize_title( trim( $args['tag_slug'] ) );
			$tag_slug        = str_replace( '-', '', $tag_slug );
			$where_clauses[] = "EXISTS (SELECT 1 FROM $note_tags_table nt INNER JOIN $tags_table t ON nt.tag_id = t.id WHERE nt.note_id = n.id AND t.slug = %s)";
			$query_params[]  = $tag_slug;
		}

		if ( isset( $args['search'] ) && '' !== trim( $args['search'] ) ) {
			$search          = sanitize_text_field( trim( $args['search'] ) );
			$like            = '%' . $wpdb->esc_like( $search ) . '%';
			$where_clauses[] = '(n.title LIKE %s OR n.subject LIKE %s OR n.chapter LIKE %s OR n.description LIKE %s)';
			$query_params[]  = $like;
			$query_params[]  = $like;
			$query_params[]  = $like;
			$query_params[]  = $like;
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$count_sql = "SELECT COUNT(*) FROM $notes_table n WHERE $where_sql";
		if ( ! empty( $query_params ) ) {
			$prepared_count_sql = $wpdb->prepare( $count_sql, $query_params );
		} else {
			$prepared_count_sql = $count_sql;
		}

		$total = (int) $wpdb->get_var( $prepared_count_sql );

		if ( $is_review_queue ) {
			$review_status_filter = isset( $args['review_status'] ) ? sanitize_key( trim( $args['review_status'] ) ) : 'pending';
			if ( 'pending' === $review_status_filter || empty( $review_status_filter ) ) {
				// Fairness rule: Pending queue must sort oldest first so submissions are handled fairly
				$order_clause = 'ORDER BY n.created_at ASC';
			} else {
				$order_clause = 'ORDER BY n.created_at DESC';
			}
		} else {
			$order_clause = "ORDER BY n.$orderby_raw $order_raw";
		}

		$items_sql          = "SELECT n.* FROM $notes_table n WHERE $where_sql $order_clause LIMIT %d OFFSET %d";
		$items_params       = array_merge( $query_params, array( $per_page, $offset ) );
		$prepared_items_sql = $wpdb->prepare( $items_sql, $items_params );

		$items = $wpdb->get_results( $prepared_items_sql );

		if ( null === $items ) {
			return new WP_Error(
				'notes_adda_query_failed',
				'Database query failed.'
			);
		}

		// Decorate items with uploader name, reviewer name, bookmark status, and like status
		if ( ! empty( $items ) ) {
			foreach ( $items as &$item ) {
				// Default review_status if not set
				if ( empty( $item->review_status ) ) {
					$item->review_status = 'pending';
				}

				// Owner details
				$owner = get_userdata( (int) $item->owner_id );
				$item->uploader_name  = $owner ? $owner->display_name : 'Student';
				$item->uploader_login = $owner ? $owner->user_login : '';

				// Reviewer details
				if ( ! empty( $item->reviewed_by ) ) {
					$reviewer = get_userdata( (int) $item->reviewed_by );
					$item->reviewer_name = $reviewer ? $reviewer->display_name : 'Expert Reviewer';
					if ( 'verified' === $item->review_status && class_exists( 'Notes_Adda_Ratings' ) ) {
						$item->reviewer_rating = Notes_Adda_Ratings::get_expert_subject_summary( (int) $item->reviewed_by, $item->subject, $current_user_id );
					} else {
						$item->reviewer_rating = null;
					}
				} else {
					$item->reviewer_name   = '';
					$item->reviewer_rating = null;
				}

				// Bookmark and like status
				if ( $current_user_id > 0 ) {
					$item->is_bookmarked = Notes_Adda_Bookmarks::has_bookmarked( (int) $item->id, $current_user_id );
					$item->is_liked      = class_exists( 'Notes_Adda_Likes' ) ? Notes_Adda_Likes::has_liked( (int) $item->id, $current_user_id ) : false;
				} else {
					$item->is_bookmarked = false;
					$item->is_liked      = false;
				}
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
}
