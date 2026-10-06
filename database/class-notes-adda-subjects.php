<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Subjects {

	/**
	 * Normalize subject name.
	 *
	 * @param string $name Subject name.
	 * @return string Normalized name.
	 */
	public static function normalize_name( $name ) {
		$name = sanitize_text_field( $name );
		$name = preg_replace( '/\s+/', ' ', trim( $name ) );
		return $name;
	}

	/**
	 * Normalize subject slug.
	 *
	 * @param string $name Subject name.
	 * @return string Normalized slug.
	 */
	public static function normalize_slug( $name ) {
		$slug = sanitize_title( $name );
		return $slug;
	}

	/**
	 * Retrieve all subjects alphabetically by name.
	 *
	 * @return array Array of subject objects.
	 */
	public static function get_all() {
		global $wpdb;
		$table_name  = $wpdb->prefix . 'notes_adda_subjects';
		$notes_table = $wpdb->prefix . 'notes_adda_notes';

		// Get subjects with note count
		$sql = "SELECT s.*, 
				(SELECT COUNT(*) FROM $notes_table n WHERE n.subject = s.name) AS note_count
				FROM $table_name s
				ORDER BY s.name ASC";

		$subjects = $wpdb->get_results( $sql );
		return $subjects ? $subjects : array();
	}

	/**
	 * Retrieve subject by ID.
	 *
	 * @param int $id Subject ID.
	 * @return object|null Subject object or null.
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		$id = (int) $id;
		if ( $id <= 0 ) {
			return null;
		}

		$table_name = $wpdb->prefix . 'notes_adda_subjects';
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id )
		);
	}

	/**
	 * Retrieve subject by slug.
	 *
	 * @param string $slug Subject slug.
	 * @return object|null Subject object or null.
	 */
	public static function get_by_slug( $slug ) {
		global $wpdb;
		$slug = sanitize_title( trim( $slug ) );
		if ( empty( $slug ) ) {
			return null;
		}

		$table_name = $wpdb->prefix . 'notes_adda_subjects';
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE slug = %s", $slug )
		);
	}

	/**
	 * Retrieve subject by name (case-insensitive).
	 *
	 * @param string $name Subject name.
	 * @return object|null Subject object or null.
	 */
	public static function get_by_name( $name ) {
		global $wpdb;
		$name = self::normalize_name( $name );
		if ( empty( $name ) ) {
			return null;
		}

		$table_name = $wpdb->prefix . 'notes_adda_subjects';
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE LOWER(name) = LOWER(%s)", $name )
		);
	}

	/**
	 * Create a new subject.
	 *
	 * @param string $name    Subject name.
	 * @param int    $user_id WordPress user ID of creator.
	 * @return object|WP_Error Created subject or WP_Error.
	 */
	public static function create( $name, $user_id ) {
		global $wpdb;

		$name    = self::normalize_name( $name );
		$user_id = (int) $user_id;

		if ( empty( $name ) ) {
			return new WP_Error(
				'notes_adda_invalid_subject_name',
				'Subject name cannot be empty.'
			);
		}

		$slug = self::normalize_slug( $name );
		if ( empty( $slug ) ) {
			return new WP_Error(
				'notes_adda_invalid_subject_slug',
				'Could not generate a valid slug for this subject name.'
			);
		}

		$existing = self::get_by_name( $name );
		if ( $existing ) {
			return new WP_Error(
				'notes_adda_subject_exists',
				'A subject with this name already exists.'
			);
		}

		$existing_slug = self::get_by_slug( $slug );
		if ( $existing_slug ) {
			return new WP_Error(
				'notes_adda_subject_slug_exists',
				'A subject with a similar slug already exists.'
			);
		}

		$table_name = $wpdb->prefix . 'notes_adda_subjects';
		$now        = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'name'       => $name,
				'slug'       => $slug,
				'created_by' => $user_id,
				'created_at' => $now,
			),
			array( '%s', '%s', '%d', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'notes_adda_subject_create_failed',
				'Failed to create subject in database.'
			);
		}

		return self::get_by_id( $wpdb->insert_id );
	}

	/**
	 * Delete a subject.
	 *
	 * @param int $id Subject ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		if ( $id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_subject_id', 'Invalid subject ID.' );
		}

		$subject = self::get_by_id( $id );
		if ( ! $subject ) {
			return new WP_Error( 'notes_adda_subject_not_found', 'Subject does not exist.' );
		}

		// Check if existing notes use this subject
		$notes_table = $wpdb->prefix . 'notes_adda_notes';
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $notes_table WHERE subject = %s",
				$subject->name
			)
		);

		if ( $count > 0 ) {
			return new WP_Error(
				'notes_adda_subject_in_use',
				sprintf(
					'Cannot delete subject "%s" because %d note%s currently categorized under it. Please reassign or delete those notes first.',
					$subject->name,
					$count,
					$count === 1 ? ' is' : 's are'
				)
			);
		}

		$table_name = $wpdb->prefix . 'notes_adda_subjects';
		$deleted    = $wpdb->delete(
			$table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error( 'notes_adda_subject_delete_failed', 'Failed to delete subject.' );
		}

		return true;
	}
}
