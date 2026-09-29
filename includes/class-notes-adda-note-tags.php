<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Note_Tags {

	/**
	 * Retrieve all tags attached to a note.
	 *
	 * @param int $note_id Note ID.
	 * @return array|WP_Error Array of tag objects on success, WP_Error on failure.
	 */
	public static function get_tags( $note_id ) {
		global $wpdb;

		$note_id = (int) $note_id;
		if ( $note_id <= 0 ) {
			return new WP_Error(
				'notes_adda_invalid_note_id',
				'Please provide a valid note ID.'
			);
		}

		$note = Notes_Adda_Notes::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error(
				'notes_adda_note_not_found',
				'Note does not exist.'
			);
		}

		$tags_table      = $wpdb->prefix . 'notes_adda_tags';
		$note_tags_table = $wpdb->prefix . 'notes_adda_note_tags';

		$tags = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.* FROM $tags_table t 
				INNER JOIN $note_tags_table nt ON t.id = nt.tag_id 
				WHERE nt.note_id = %d 
				ORDER BY t.id ASC",
				$note_id
			)
		);

		return $tags ? $tags : array();
	}

	/**
	 * Set tags for a note, replacing any existing tag relationships.
	 *
	 * @param int   $note_id Note ID.
	 * @param array $tags    Array of tag data arrays (e.g. array( array( 'name' => 'Sem 7', 'type' => 'sem' ) )).
	 * @return array|WP_Error Array of final tag objects on success, WP_Error on failure.
	 */
	public static function set_tags( $note_id, $tags = array() ) {
		global $wpdb;

		$note_id = (int) $note_id;
		if ( $note_id <= 0 ) {
			return new WP_Error(
				'notes_adda_invalid_note_id',
				'Please provide a valid note ID.'
			);
		}

		$note = Notes_Adda_Notes::get_by_id( $note_id );
		if ( is_wp_error( $note ) ) {
			return $note;
		}
		if ( ! $note ) {
			return new WP_Error(
				'notes_adda_note_not_found',
				'Note does not exist.'
			);
		}

		if ( ! is_array( $tags ) ) {
			return new WP_Error(
				'notes_adda_invalid_tags_format',
				'Tags parameter must be an array.'
			);
		}

		$tag_ids = array();
		foreach ( $tags as $tag_item ) {
			if ( is_array( $tag_item ) ) {
				$name = isset( $tag_item['name'] ) ? $tag_item['name'] : '';
				$type = isset( $tag_item['type'] ) ? $tag_item['type'] : 'other';
			} elseif ( is_string( $tag_item ) ) {
				$name = $tag_item;
				$type = 'other';
			} else {
				continue;
			}

			if ( empty( trim( $name ) ) ) {
				continue;
			}

			$tag_id = Notes_Adda_Tags::get_or_create( $name, $type );
			if ( is_wp_error( $tag_id ) ) {
				return $tag_id;
			}

			if ( $tag_id > 0 ) {
				$tag_ids[] = (int) $tag_id;
			}
		}

		$tag_ids = array_values( array_unique( $tag_ids ) );

		$note_tags_table = $wpdb->prefix . 'notes_adda_note_tags';

		$wpdb->query( 'START TRANSACTION' );

		$deleted = $wpdb->delete(
			$note_tags_table,
			array( 'note_id' => $note_id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error(
				'notes_adda_tag_link_delete_failed',
				'Failed to remove existing tags from note.'
			);
		}

		foreach ( $tag_ids as $tag_id ) {
			$inserted = $wpdb->insert(
				$note_tags_table,
				array(
					'note_id' => $note_id,
					'tag_id'  => $tag_id,
				),
				array( '%d', '%d' )
			);

			if ( false === $inserted ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error(
					'notes_adda_tag_link_insert_failed',
					'Failed to attach tag to note.'
				);
			}
		}

		$wpdb->query( 'COMMIT' );

		return self::get_tags( $note_id );
	}
}
