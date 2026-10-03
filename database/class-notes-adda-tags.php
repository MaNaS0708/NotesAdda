<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Tags {

	public static function normalize_name( $name ) {
		$name = sanitize_text_field( $name );
		$name = preg_replace( '/\s+/', ' ', trim( $name ) );

		return $name;
	}

	public static function normalize_slug( $name ) {
		$slug = sanitize_title( $name );

		$slug = str_replace( '-', '', $slug );

		return $slug;
	}

	public static function get_or_create( $name, $type = 'other' ) {
		global $wpdb;

		$name = self::normalize_name( $name );
		$slug = self::normalize_slug( $name );
		$type = sanitize_key( $type );

		if ( empty( $name ) || empty( $slug ) ) {
			return new WP_Error(
				'notes_adda_invalid_tag',
				'Please enter a valid tag.'
			);
		}

		$allowed_types = array( 'sem', 'batch', 'subject', 'other' );

		if ( ! in_array( $type, $allowed_types, true ) ) {
			$type = 'other';
		}

		$table_name = $wpdb->prefix . 'notes_adda_tags';

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE slug = %s",
				$slug
			)
		);

		if ( $existing_id ) {
			return (int) $existing_id;
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'name' => $name,
				'slug' => $slug,
				'type' => $type,
			),
			array( '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'notes_adda_tag_create_failed',
				'The tag could not be saved.'
			);
		}

		return (int) $wpdb->insert_id;
	}
}