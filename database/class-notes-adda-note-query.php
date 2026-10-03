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

		$orderby_raw     = isset( $args['orderby'] ) ? strtolower( trim( $args['orderby'] ) ) : 'created_at';
		$allowed_orderby = array( 'created_at', 'title', 'like_count' );
		if ( ! in_array( $orderby_raw, $allowed_orderby, true ) ) {
			return new WP_Error(
				'notes_adda_invalid_orderby',
				'Invalid orderby parameter. Allowed fields: created_at, title, like_count.'
			);
		}

		$order_raw     = isset( $args['order'] ) ? strtoupper( trim( $args['order'] ) ) : 'DESC';
		$allowed_order = array( 'ASC', 'DESC' );
		if ( ! in_array( $order_raw, $allowed_order, true ) ) {
			return new WP_Error(
				'notes_adda_invalid_order',
				'Invalid order parameter. Allowed values: ASC, DESC.'
			);
		}

		$page     = isset( $args['page'] ) ? (int) $args['page'] : 1;
		$per_page = isset( $args['per_page'] ) ? (int) $args['per_page'] : 10;

		if ( $page < 1 ) {
			return new WP_Error(
				'notes_adda_invalid_page',
				'Page parameter must be an integer greater than or equal to 1.'
			);
		}

		if ( $per_page < 1 || $per_page > 50 ) {
			return new WP_Error(
				'notes_adda_invalid_per_page',
				'Per page parameter must be an integer between 1 and 50.'
			);
		}

		$offset = ( $page - 1 ) * $per_page;

		$notes_table     = $wpdb->prefix . 'notes_adda_notes';
		$note_tags_table = $wpdb->prefix . 'notes_adda_note_tags';
		$tags_table      = $wpdb->prefix . 'notes_adda_tags';

		$where_clauses = array( '1=1' );
		$query_params  = array();

		if ( isset( $args['owner_id'] ) ) {
			$owner_id = (int) $args['owner_id'];
			if ( $owner_id <= 0 ) {
				return new WP_Error(
					'notes_adda_invalid_owner_id',
					'Invalid owner_id parameter.'
				);
			}
			$where_clauses[] = 'n.owner_id = %d';
			$query_params[]  = $owner_id;
		}

		if ( isset( $args['subject'] ) && '' !== trim( $args['subject'] ) ) {
			$subject         = sanitize_text_field( trim( $args['subject'] ) );
			$where_clauses[] = 'n.subject = %s';
			$query_params[]  = $subject;
		}

		if ( isset( $args['is_whole_notes'] ) ) {
			$is_whole        = ! empty( $args['is_whole_notes'] ) ? 1 : 0;
			$where_clauses[] = 'n.is_whole_notes = %d';
			$query_params[]  = $is_whole;
		}

		if ( isset( $args['tag_id'] ) ) {
			$tag_id = (int) $args['tag_id'];
			if ( $tag_id <= 0 ) {
				return new WP_Error(
					'notes_adda_invalid_tag_id',
					'Invalid tag_id parameter.'
				);
			}
			$where_clauses[] = "EXISTS (SELECT 1 FROM $note_tags_table nt WHERE nt.note_id = n.id AND nt.tag_id = %d)";
			$query_params[]  = $tag_id;
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

		$items_sql          = "SELECT n.* FROM $notes_table n WHERE $where_sql ORDER BY n.$orderby_raw $order_raw LIMIT %d OFFSET %d";
		$items_params       = array_merge( $query_params, array( $per_page, $offset ) );
		$prepared_items_sql = $wpdb->prepare( $items_sql, $items_params );

		$items = $wpdb->get_results( $prepared_items_sql );

		if ( null === $items ) {
			return new WP_Error(
				'notes_adda_query_failed',
				'Database query failed.'
			);
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
