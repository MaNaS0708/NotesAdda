<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Ratings {

	/**
	 * Check if a user is an eligible Expert for receiving ratings.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if user is an Expert, false otherwise.
	 */
	public static function is_expert( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$roles = (array) $user->roles;
		if ( in_array( 'notes_adda_expert', $roles, true ) ) {
			return true;
		}

		if ( user_can( $user_id, 'notes_adda_review_notes' ) ) {
			return true;
		}

		$super_owner_id = (int) get_option( 'notes_adda_owner_id' );
		if ( $super_owner_id > 0 && $user_id === $super_owner_id ) {
			return true;
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if an Expert has reviewed and verified at least one note for a specific subject.
	 *
	 * @param int    $expert_id Target Expert ID.
	 * @param string $subject   Subject name.
	 * @return bool True if expert has verified at least one note for this subject.
	 */
	public static function has_reviewed_subject( $expert_id, $subject ) {
		$count = self::get_reviewed_subject_notes_count( $expert_id, $subject );
		return $count > 0;
	}

	/**
	 * Get the number of verified notes an Expert has reviewed for a specific subject.
	 *
	 * @param int    $expert_id Target Expert ID.
	 * @param string $subject   Subject name.
	 * @return int Count of verified notes reviewed.
	 */
	public static function get_reviewed_subject_notes_count( $expert_id, $subject ) {
		global $wpdb;

		$expert_id = (int) $expert_id;
		$subject   = sanitize_text_field( trim( $subject ) );

		if ( $expert_id <= 0 || empty( $subject ) ) {
			return 0;
		}

		$notes_table = $wpdb->prefix . 'notes_adda_notes';
		$count       = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $notes_table 
				 WHERE reviewed_by = %d 
				   AND subject = %s 
				   AND review_status = 'verified'",
				$expert_id,
				$subject
			)
		);

		return (int) $count;
	}

	/**
	 * Validate a quarter-star rating value (0.25 to 5.00 in 0.25 increments).
	 *
	 * @param mixed $rating Rating input.
	 * @return float|WP_Error Validated float rating or WP_Error.
	 */
	public static function validate_rating_value( $rating ) {
		if ( ! is_numeric( $rating ) ) {
			return new WP_Error( 'notes_adda_invalid_rating', 'Rating must be a numeric value.' );
		}

		$val = (float) $rating;
		if ( $val < 0.25 || $val > 5.00 ) {
			return new WP_Error( 'notes_adda_rating_out_of_range', 'Rating must be between 0.25 and 5.00 stars.' );
		}

		$quarters = $val * 4;
		if ( abs( round( $quarters ) - $quarters ) > 0.0001 ) {
			return new WP_Error( 'notes_adda_invalid_quarter_step', 'Rating must be in exact quarter-star increments (e.g. 4.00, 4.25, 4.50, 4.75, 5.00).' );
		}

		return round( $quarters ) / 4;
	}

	/**
	 * Rate an Expert for a specific reviewed subject with quarter-star precision.
	 *
	 * @param int    $expert_id WordPress user ID of the Expert being rated.
	 * @param int    $rater_id  WordPress user ID of the user submitting the rating.
	 * @param string $subject   Subject context for the rating.
	 * @param float  $rating    Rating value (0.25 to 5.00 in increments of 0.25).
	 * @return array|WP_Error Updated summary array or WP_Error on failure.
	 */
	public static function rate( $expert_id, $rater_id, $subject, $rating ) {
		global $wpdb;

		$expert_id = (int) $expert_id;
		$rater_id  = (int) $rater_id;
		$subject   = sanitize_text_field( trim( $subject ) );

		if ( $expert_id <= 0 || $rater_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_user_id', 'Invalid user ID provided.' );
		}

		// Self-rating is strictly forbidden
		if ( $expert_id === $rater_id ) {
			return new WP_Error( 'notes_adda_cannot_rate_self', 'Experts cannot rate themselves.' );
		}

		// Target must be an Expert
		if ( ! self::is_expert( $expert_id ) ) {
			return new WP_Error( 'notes_adda_not_an_expert', 'Only users with the Expert role or review capability can receive ratings.' );
		}

		// Subject cannot be empty
		if ( empty( $subject ) ) {
			return new WP_Error( 'notes_adda_empty_subject', 'A valid subject context is required to rate an expert.' );
		}

		// Contextual rule: Expert must have verified at least one note for this subject
		if ( ! self::has_reviewed_subject( $expert_id, $subject ) ) {
			return new WP_Error(
				'notes_adda_subject_not_reviewed',
				sprintf( 'This expert has not verified any notes for subject "%s". Ratings are only permitted for subjects the expert has reviewed.', esc_html( $subject ) )
			);
		}

		// Validate rating
		$valid_rating = self::validate_rating_value( $rating );
		if ( is_wp_error( $valid_rating ) ) {
			return $valid_rating;
		}

		// Rater must exist
		$rater = get_userdata( $rater_id );
		if ( ! $rater ) {
			return new WP_Error( 'notes_adda_rater_not_found', 'Rater user does not exist.' );
		}

		$table_name = $wpdb->prefix . 'notes_adda_expert_ratings';
		$now        = current_time( 'mysql' );

		// Upsert rating using ON DUPLICATE KEY UPDATE on (expert_id, rater_id, subject)
		$sql = $wpdb->prepare(
			"INSERT INTO $table_name (expert_id, rater_id, subject, rating, created_at, updated_at) 
			VALUES (%d, %d, %s, %f, %s, %s) 
			ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = VALUES(updated_at)",
			$expert_id,
			$rater_id,
			$subject,
			$valid_rating,
			$now,
			$now
		);

		$result = $wpdb->query( $sql );
		if ( false === $result ) {
			return new WP_Error( 'notes_adda_rating_save_failed', 'Failed to save rating to database.' );
		}

		return self::get_expert_subject_summary( $expert_id, $subject, $rater_id );
	}

	/**
	 * Get aggregated rating summary for an Expert in a specific subject.
	 *
	 * @param int    $expert_id Target Expert ID.
	 * @param string $subject   Target subject context.
	 * @param int    $rater_id  Optional current user ID to retrieve their personal rating.
	 * @return array Rating summary (subject rating, overall rating, counts, user rating).
	 */
	public static function get_expert_subject_summary( $expert_id, $subject = '', $rater_id = 0 ) {
		global $wpdb;

		$expert_id = (int) $expert_id;
		$subject   = sanitize_text_field( trim( $subject ) );
		$rater_id  = (int) $rater_id;

		if ( $expert_id <= 0 ) {
			return array(
				'expert_id'                    => 0,
				'subject'                      => $subject,
				'has_ratings'                  => false,
				'average'                      => null,
				'formatted_average'            => null,
				'count'                        => 0,
				'total'                        => 0,
				'user_rating'                  => null,
				'formatted_user_rating'        => null,
				'reviewed_subject_notes_count' => 0,
				'overall_average'              => null,
				'formatted_overall_average'    => null,
				'overall_total'                => 0,
				'has_overall_ratings'          => false,
				'can_rate'                     => false,
			);
		}

		$table_name = $wpdb->prefix . 'notes_adda_expert_ratings';

		// 1. Subject-specific stats
		$count       = 0;
		$avg         = null;
		$formatted   = null;
		$has_ratings = false;
		$user_rating = null;
		$formatted_user = null;

		if ( ! empty( $subject ) ) {
			$sub_stats = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS total_count, AVG(rating) AS avg_rating 
					FROM $table_name 
					WHERE expert_id = %d AND subject = %s",
					$expert_id,
					$subject
				)
			);

			if ( $sub_stats && (int) $sub_stats->total_count > 0 ) {
				$count       = (int) $sub_stats->total_count;
				$has_ratings = true;
				// Round raw average to nearest 0.25
				$raw_avg     = (float) $sub_stats->avg_rating;
				$avg         = round( $raw_avg * 4 ) / 4;
				$formatted   = number_format( $avg, 2, '.', '' );
			}

			if ( $rater_id > 0 ) {
				$existing = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT rating FROM $table_name WHERE expert_id = %d AND rater_id = %d AND subject = %s",
						$expert_id,
						$rater_id,
						$subject
					)
				);
				if ( null !== $existing ) {
					$user_rating    = (float) $existing;
					$formatted_user = number_format( $user_rating, 2, '.', '' );
				}
			}
		}

		// 2. Overall stats across all subjects
		$overall_stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total_count, AVG(rating) AS avg_rating 
				FROM $table_name 
				WHERE expert_id = %d",
				$expert_id
			)
		);

		$overall_total     = $overall_stats ? (int) $overall_stats->total_count : 0;
		$overall_avg       = null;
		$formatted_overall = null;
		if ( $overall_total > 0 && null !== $overall_stats->avg_rating ) {
			$raw_ov            = (float) $overall_stats->avg_rating;
			$overall_avg       = round( $raw_ov * 4 ) / 4;
			$formatted_overall = number_format( $overall_avg, 2, '.', '' );
		}

		$reviewed_notes_count = ! empty( $subject ) ? self::get_reviewed_subject_notes_count( $expert_id, $subject ) : 0;
		$can_rate = ( $rater_id > 0 && $rater_id !== $expert_id && self::is_expert( $expert_id ) && ( empty( $subject ) || $reviewed_notes_count > 0 ) );

		return array(
			'expert_id'                    => $expert_id,
			'subject'                      => $subject,
			'has_ratings'                  => $has_ratings,
			'average'                      => $avg,
			'formatted_average'            => $formatted,
			'count'                        => $count,
			'total'                        => $count,
			'user_rating'                  => $user_rating,
			'formatted_user_rating'        => $formatted_user,
			'reviewed_subject_notes_count' => $reviewed_notes_count,
			'overall_average'              => $overall_avg,
			'formatted_overall_average'    => $formatted_overall,
			'overall_total'                => $overall_total,
			'has_overall_ratings'          => ( $overall_total > 0 ),
			'can_rate'                     => $can_rate,
		);
	}

	/**
	 * Get overall aggregated rating summary for an Expert.
	 *
	 * @param int $expert_id Target Expert ID.
	 * @param int $rater_id  Optional current user ID.
	 * @return array Rating summary.
	 */
	public static function get_expert_summary( $expert_id, $rater_id = 0 ) {
		return self::get_expert_subject_summary( $expert_id, '', $rater_id );
	}
}
