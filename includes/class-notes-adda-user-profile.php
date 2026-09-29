<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_User_Profile {

	/**
	 * Retrieve a Notes Adda profile by WordPress user ID.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return object|null|WP_Error Profile object if found, null if not found, WP_Error on invalid input.
	 */
	public static function get_by_user_id( $user_id ) {
		global $wpdb;

		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return new WP_Error(
				'notes_adda_invalid_user_id',
				'Please provide a valid WordPress user ID.'
			);
		}

		$table_name = $wpdb->prefix . 'notes_adda_profiles';

		$profile = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE user_id = %d",
				$user_id
			)
		);

		return $profile ? $profile : null;
	}

	/**
	 * Retrieve existing profile or create a new profile for a WordPress user ID.
	 *
	 * @param int   $user_id WordPress user ID.
	 * @param array $data    Optional profile data (college, bio).
	 * @return object|WP_Error Profile object on success, WP_Error on failure.
	 */
	public static function get_or_create( $user_id, $data = array() ) {
		global $wpdb;

		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return new WP_Error(
				'notes_adda_invalid_user_id',
				'Please provide a valid WordPress user ID.'
			);
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'notes_adda_user_not_found',
				'WordPress user does not exist.'
			);
		}

		$existing = self::get_by_user_id( $user_id );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		if ( null !== $existing ) {
			return $existing;
		}

		$table_name = $wpdb->prefix . 'notes_adda_profiles';

		$college    = isset( $data['college'] ) ? sanitize_text_field( $data['college'] ) : '';
		$bio        = isset( $data['bio'] ) ? sanitize_textarea_field( $data['bio'] ) : '';
		$now        = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'user_id'    => $user_id,
				'college'    => $college,
				'bio'        => $bio,
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'notes_adda_profile_create_failed',
				'The profile could not be created in the database.'
			);
		}

		return self::get_by_user_id( $user_id );
	}
}
