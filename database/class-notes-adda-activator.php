<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Activator {

	public static function activate() {
		self::create_tables();
		self::setup_roles();
		self::bootstrap_admin();
		self::migrate_existing_users();
		self::migrate_subjects();
		self::migrate_review_statuses();
		self::migrate_ratings();
		self::provision_pages();

		update_option( 'notes_adda_version', NOTES_ADDA_VERSION );
		update_option( 'notes_adda_db_version', NOTES_ADDA_VERSION );
	}

	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$notes_table            = $wpdb->prefix . 'notes_adda_notes';
		$tags_table             = $wpdb->prefix . 'notes_adda_tags';
		$note_tags_table        = $wpdb->prefix . 'notes_adda_note_tags';
		$user_tags_table        = $wpdb->prefix . 'notes_adda_user_tags';
		$likes_table            = $wpdb->prefix . 'notes_adda_likes';
		$reports_table          = $wpdb->prefix . 'notes_adda_reports';
		$profiles_table         = $wpdb->prefix . 'notes_adda_profiles';
		$subjects_table         = $wpdb->prefix . 'notes_adda_subjects';
		$bookmarks_table        = $wpdb->prefix . 'notes_adda_bookmarks';
		$subject_requests_table = $wpdb->prefix . 'notes_adda_subject_requests';
		$notifications_table    = $wpdb->prefix . 'notes_adda_notifications';
		$expert_ratings_table   = $wpdb->prefix . 'notes_adda_expert_ratings';

		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$sql = array(

			"CREATE TABLE $notes_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				owner_id bigint(20) unsigned NOT NULL,
				title varchar(255) NOT NULL,
				subject varchar(150) NOT NULL,
				chapter varchar(150) NOT NULL DEFAULT '',
				is_whole_notes tinyint(1) NOT NULL DEFAULT 0,
				description text NOT NULL,
				file_url text NOT NULL,
				file_id bigint(20) unsigned NOT NULL DEFAULT 0,
				like_count bigint(20) unsigned NOT NULL DEFAULT 0,
				review_status varchar(30) NOT NULL DEFAULT 'pending',
				reviewed_by bigint(20) unsigned DEFAULT NULL,
				reviewed_at datetime DEFAULT NULL,
				review_note text DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY (id),
				KEY owner_id (owner_id),
				KEY review_status (review_status),
				KEY created_at (created_at)
			) $charset_collate;",

			"CREATE TABLE $tags_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(100) NOT NULL,
				slug varchar(100) NOT NULL,
				type varchar(50) NOT NULL DEFAULT 'other',
				PRIMARY KEY (id),
				UNIQUE KEY slug (slug)
			) $charset_collate;",

			"CREATE TABLE $note_tags_table (
				note_id bigint(20) unsigned NOT NULL,
				tag_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY (note_id, tag_id),
				KEY tag_id (tag_id)
			) $charset_collate;",

			"CREATE TABLE $user_tags_table (
				user_id bigint(20) unsigned NOT NULL,
				tag_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY (user_id, tag_id),
				KEY tag_id (tag_id)
			) $charset_collate;",

			"CREATE TABLE $likes_table (
				user_id bigint(20) unsigned NOT NULL,
				note_id bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY (user_id, note_id),
				KEY note_id (note_id)
			) $charset_collate;",

			"CREATE TABLE $reports_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				note_id bigint(20) unsigned NOT NULL,
				reporter_id bigint(20) unsigned NOT NULL,
				reason text NOT NULL,
				status varchar(30) NOT NULL DEFAULT 'open',
				created_at datetime NOT NULL,
				PRIMARY KEY (id),
				KEY note_id (note_id),
				KEY status (status)
			) $charset_collate;",

			"CREATE TABLE $profiles_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				college varchar(255) NOT NULL DEFAULT '',
				bio text NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY user_id (user_id)
			) $charset_collate;",

			"CREATE TABLE $subjects_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(150) NOT NULL,
				slug varchar(150) NOT NULL,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY slug (slug)
			) $charset_collate;",

			"CREATE TABLE $bookmarks_table (
				user_id bigint(20) unsigned NOT NULL,
				note_id bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY (user_id, note_id),
				KEY note_id (note_id),
				KEY user_id (user_id)
			) $charset_collate;",

			"CREATE TABLE $subject_requests_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				requested_name varchar(100) NOT NULL,
				requested_slug varchar(120) NOT NULL,
				requester_id bigint(20) unsigned NOT NULL,
				reason text NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'pending',
				reviewed_by bigint(20) unsigned DEFAULT NULL,
				reviewed_at datetime DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY status (status),
				KEY requester_id (requester_id),
				KEY requested_slug (requested_slug)
			) $charset_collate;",

			"CREATE TABLE $notifications_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				type varchar(50) NOT NULL,
				title varchar(255) NOT NULL,
				message text NOT NULL,
				note_id bigint(20) unsigned NOT NULL DEFAULT 0,
				is_read tinyint(1) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				read_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY user_read (user_id, is_read),
				KEY user_id (user_id),
				KEY note_id (note_id),
				KEY created_at (created_at)
			) $charset_collate;",

			"CREATE TABLE $expert_ratings_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				expert_id bigint(20) unsigned NOT NULL,
				rater_id bigint(20) unsigned NOT NULL,
				subject varchar(100) NOT NULL DEFAULT '',
				rating decimal(3,2) NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY expert_rater_subject (expert_id, rater_id, subject),
				KEY expert_id (expert_id),
				KEY rater_id (rater_id),
				KEY subject (subject)
			) $charset_collate;"
		);

		foreach ( $sql as $table_sql ) {
			dbDelta( $table_sql );
		}

		$wpdb->query(
			"ALTER TABLE $notes_table AUTO_INCREMENT = 1000001"
		);
	}

	public static function setup_roles() {
		// 1. Student Role
		$student_caps = array(
			'read'                        => true,
			'notes_adda_upload_notes'     => true,
			'notes_adda_request_subjects' => true,
		);
		$student_role = get_role( 'notes_adda_student' );
		if ( ! $student_role ) {
			add_role( 'notes_adda_student', 'Student', $student_caps );
		} else {
			foreach ( $student_caps as $cap => $grant ) {
				$student_role->add_cap( $cap, $grant );
			}
		}

		// 2. Expert Role
		$expert_caps = array(
			'read'                        => true,
			'notes_adda_upload_notes'     => true,
			'notes_adda_review_notes'     => true,
			'notes_adda_request_subjects' => true,
		);
		$expert_role = get_role( 'notes_adda_expert' );
		if ( ! $expert_role ) {
			add_role( 'notes_adda_expert', 'Expert', $expert_caps );
		} else {
			$expert_role->remove_cap( 'notes_adda_manage_subjects' );
			$expert_role->remove_cap( 'notes_adda_manage_all_notes' );
			foreach ( $expert_caps as $cap => $grant ) {
				$expert_role->add_cap( $cap, $grant );
			}
		}

		// 3. Notes Adda Admin Role
		$admin_caps = array(
			'read'                        => true,
			'notes_adda_upload_notes'     => true,
			'notes_adda_review_notes'     => true,
			'notes_adda_request_subjects' => true,
			'notes_adda_manage_subjects'  => true,
			'notes_adda_manage_all_notes' => true,
			'notes_adda_manage_users'     => true,
		);
		$na_admin_role = get_role( 'notes_adda_admin' );
		if ( ! $na_admin_role ) {
			add_role( 'notes_adda_admin', 'Notes Adda Admin', $admin_caps );
		} else {
			foreach ( $admin_caps as $cap => $grant ) {
				$na_admin_role->add_cap( $cap, $grant );
			}
		}

		// Also grant caps to WordPress Administrator role so site admins have full access
		$wp_admin_role = get_role( 'administrator' );
		if ( $wp_admin_role ) {
			foreach ( $admin_caps as $cap => $grant ) {
				$wp_admin_role->add_cap( $cap, $grant );
			}
		}
	}

	public static function bootstrap_admin() {
		$owner_id = (int) get_option( 'notes_adda_owner_id' );

		if ( $owner_id > 0 ) {
			$owner_user = get_userdata( $owner_id );
			if ( $owner_user ) {
				$owner_user->add_role( 'administrator' );
				$owner_user->add_role( 'notes_adda_admin' );
				return;
			}
		}

		// Look up user by login "notes_adda_dev"
		$dev_user = get_user_by( 'login', 'notes_adda_dev' );
		if ( $dev_user ) {
			update_option( 'notes_adda_owner_id', (int) $dev_user->ID );
			$dev_user->add_role( 'administrator' );
			$dev_user->add_role( 'notes_adda_admin' );
		}
	}

	public static function migrate_existing_users() {
		if ( get_option( 'notes_adda_users_migrated_v2' ) ) {
			return;
		}

		$owner_id = (int) get_option( 'notes_adda_owner_id' );
		$users    = get_users( array( 'fields' => 'all' ) );

		foreach ( $users as $user ) {
			if ( $owner_id > 0 && (int) $user->ID === $owner_id ) {
				$user->add_role( 'administrator' );
				$user->add_role( 'notes_adda_admin' );
				continue;
			}

			if ( 'notes_adda_dev' === $user->user_login ) {
				$user->add_role( 'administrator' );
				$user->add_role( 'notes_adda_admin' );
				continue;
			}

			$user_roles = (array) $user->roles;

			if ( in_array( 'administrator', $user_roles, true ) || user_can( $user->ID, 'manage_options' ) ) {
				$user->add_role( 'administrator' );
				$user->add_role( 'notes_adda_admin' );
				continue;
			}

			if ( in_array( 'notes_adda_expert', $user_roles, true ) || in_array( 'notes_adda_admin', $user_roles, true ) ) {
				continue;
			}

			if ( empty( $user_roles ) || in_array( 'subscriber', $user_roles, true ) ) {
				$user->remove_role( 'subscriber' );
				$user->add_role( 'notes_adda_student' );
			} else {
				$user->add_role( 'notes_adda_student' );
			}
		}

		update_option( 'notes_adda_users_migrated_v2', 1 );
	}

	public static function migrate_subjects() {
		global $wpdb;
		$notes_table    = $wpdb->prefix . 'notes_adda_notes';
		$subjects_table = $wpdb->prefix . 'notes_adda_subjects';

		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $subjects_table ) );
		if ( ! $table_exists ) {
			return;
		}

		$distinct_subjects = $wpdb->get_col( "SELECT DISTINCT subject FROM $notes_table WHERE subject IS NOT NULL AND TRIM(subject) != ''" );
		if ( ! empty( $distinct_subjects ) ) {
			foreach ( $distinct_subjects as $subject_name ) {
				$name = sanitize_text_field( trim( $subject_name ) );
				if ( empty( $name ) ) {
					continue;
				}
				$slug = sanitize_title( $name );
				if ( empty( $slug ) ) {
					continue;
				}

				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM $subjects_table WHERE slug = %s OR LOWER(name) = LOWER(%s)",
						$slug,
						$name
					)
				);

				if ( ! $exists ) {
					$wpdb->insert(
						$subjects_table,
						array(
							'name'       => $name,
							'slug'       => $slug,
							'created_by' => 1,
							'created_at' => current_time( 'mysql' ),
						),
						array( '%s', '%s', '%d', '%s' )
					);
				}
			}
		}
	}

	/**
	 * Upgrade-safe migration: Migrate existing 'unverified' rows to 'pending'.
	 */
	public static function migrate_review_statuses() {
		global $wpdb;
		$notes_table = $wpdb->prefix . 'notes_adda_notes';

		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $notes_table ) );
		if ( ! $table_exists ) {
			return;
		}

		$wpdb->query(
			"UPDATE $notes_table SET review_status = 'pending' WHERE review_status = 'unverified'"
		);
	}

	/**
	 * Upgrade-safe migration: Migrate expert ratings to include subject context & decimal(3,2) precision.
	 */
	public static function migrate_ratings() {
		global $wpdb;
		$expert_ratings_table = $wpdb->prefix . 'notes_adda_expert_ratings';

		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $expert_ratings_table ) );
		if ( ! $table_exists ) {
			return;
		}

		// 1. Add subject column if missing
		$column_subject = $wpdb->get_results( "SHOW COLUMNS FROM $expert_ratings_table LIKE 'subject'" );
		if ( empty( $column_subject ) ) {
			$wpdb->query( "ALTER TABLE $expert_ratings_table ADD COLUMN subject varchar(100) NOT NULL DEFAULT '' AFTER rater_id" );
		}

		// 2. Ensure rating column is decimal(3,2)
		$column_rating = $wpdb->get_results( "SHOW COLUMNS FROM $expert_ratings_table LIKE 'rating'" );
		if ( ! empty( $column_rating ) && strpos( strtolower( $column_rating[0]->Type ), 'decimal' ) === false ) {
			$wpdb->query( "ALTER TABLE $expert_ratings_table MODIFY COLUMN rating decimal(3,2) NOT NULL" );
		}

		// 3. Drop legacy unique key expert_rater if present and create expert_rater_subject
		$indexes = $wpdb->get_results( "SHOW INDEX FROM $expert_ratings_table" );
		$has_old_index = false;
		$has_new_index = false;
		$has_subject_index = false;
		if ( ! empty( $indexes ) ) {
			foreach ( $indexes as $idx ) {
				if ( 'expert_rater' === $idx->Key_name ) {
					$has_old_index = true;
				}
				if ( 'expert_rater_subject' === $idx->Key_name ) {
					$has_new_index = true;
				}
				if ( 'subject' === $idx->Key_name ) {
					$has_subject_index = true;
				}
			}
		}

		if ( $has_old_index ) {
			$wpdb->query( "ALTER TABLE $expert_ratings_table DROP INDEX expert_rater" );
		}

		if ( ! $has_new_index ) {
			$wpdb->query( "ALTER TABLE $expert_ratings_table ADD UNIQUE KEY expert_rater_subject (expert_id, rater_id, subject)" );
		}

		if ( ! $has_subject_index ) {
			$wpdb->query( "ALTER TABLE $expert_ratings_table ADD KEY subject (subject)" );
		}
	}

	/**
	 * Safe, idempotent provisioning of Landing & App pages + homepage setup.
	 */
	public static function provision_pages() {
		global $wpdb;

		// 1. Provision / Verify Landing Page
		$landing_page_id = 0;
		$landing_post    = get_page_by_path( 'notes-adda-home' );

		if ( $landing_post ) {
			$landing_page_id = $landing_post->ID;
			// Ensure it has publish status
			if ( 'publish' !== $landing_post->post_status ) {
				wp_update_post( array(
					'ID'          => $landing_page_id,
					'post_status' => 'publish',
				) );
			}
			// Ensure it has the shortcode
			if ( ! has_shortcode( $landing_post->post_content, 'notes_adda_landing' ) ) {
				wp_update_post( array(
					'ID'           => $landing_page_id,
					'post_content' => '[notes_adda_landing]',
				) );
			}
		} else {
			// Check if any existing page has [notes_adda_landing]
			$existing_landing_id = (int) $wpdb->get_var(
				"SELECT ID FROM {$wpdb->posts} 
				 WHERE post_type = 'page' 
				   AND post_content LIKE '%[notes_adda_landing]%' 
				 ORDER BY ID ASC LIMIT 1"
			);

			if ( $existing_landing_id > 0 ) {
				$landing_page_id = $existing_landing_id;
				if ( 'publish' !== get_post_status( $landing_page_id ) ) {
					wp_update_post( array(
						'ID'          => $landing_page_id,
						'post_status' => 'publish',
					) );
				}
			} else {
				// Create the landing page
				$landing_page_id = wp_insert_post( array(
					'post_title'     => 'Notes Adda',
					'post_name'      => 'notes-adda-home',
					'post_content'   => '[notes_adda_landing]',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				) );
			}
		}

		if ( $landing_page_id > 0 && ! is_wp_error( $landing_page_id ) ) {
			update_option( 'notes_adda_landing_page_id', (int) $landing_page_id );
		}

		// 2. Provision / Verify App Page
		$app_page_id = 0;
		$existing_app_id = (int) $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} 
			 WHERE post_type = 'page' 
			   AND post_content LIKE '%[notes_adda_app]%' 
			 ORDER BY ID ASC LIMIT 1"
		);

		if ( $existing_app_id > 0 ) {
			$app_page_id = $existing_app_id;
			if ( 'publish' !== get_post_status( $app_page_id ) ) {
				wp_update_post( array(
					'ID'          => $app_page_id,
					'post_status' => 'publish',
				) );
			}
		} else {
			$app_post = get_page_by_path( 'notes-adda' );
			if ( $app_post ) {
				$app_page_id = $app_post->ID;
				if ( 'publish' !== $app_post->post_status ) {
					wp_update_post( array(
						'ID'          => $app_page_id,
						'post_status' => 'publish',
					) );
				}
				if ( ! has_shortcode( $app_post->post_content, 'notes_adda_app' ) ) {
					wp_update_post( array(
						'ID'           => $app_page_id,
						'post_content' => '[notes_adda_app]',
					) );
				}
			} else {
				$app_page_id = wp_insert_post( array(
					'post_title'     => 'Notes Adda App',
					'post_name'      => 'notes-adda',
					'post_content'   => '[notes_adda_app]',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				) );
			}
		}

		if ( $app_page_id > 0 && ! is_wp_error( $app_page_id ) ) {
			update_option( 'notes_adda_app_page_id', (int) $app_page_id );
		}

		// 3. Homepage Handling with Safety Safeguards
		if ( $landing_page_id > 0 && ! is_wp_error( $landing_page_id ) ) {
			$show_on_front = get_option( 'show_on_front', 'posts' );
			$page_on_front = (int) get_option( 'page_on_front', 0 );

			// Case A: Homepage is default/empty ('posts' or 0)
			if ( 'page' !== $show_on_front || $page_on_front <= 0 ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', (int) $landing_page_id );
				delete_option( 'notes_adda_unrelated_homepage_notice' );
			} elseif ( $page_on_front === (int) $landing_page_id ) {
				// Case B: Already set to this landing page
				delete_option( 'notes_adda_unrelated_homepage_notice' );
			} else {
				// Case C: A custom static homepage is set ($page_on_front > 0). Check if it belongs to Notes Adda.
				$current_front_post = get_post( $page_on_front );
				$is_na_page         = false;

				if ( $current_front_post ) {
					if ( has_shortcode( $current_front_post->post_content, 'notes_adda_landing' ) ||
					     has_shortcode( $current_front_post->post_content, 'notes_adda_app' ) ||
					     'notes-adda-home' === $current_front_post->post_name ||
					     'notes-adda' === $current_front_post->post_name ) {
						$is_na_page = true;
					}
				}

				if ( $is_na_page ) {
					// It's a Notes Adda page, update to current landing page
					update_option( 'show_on_front', 'page' );
					update_option( 'page_on_front', (int) $landing_page_id );
					delete_option( 'notes_adda_unrelated_homepage_notice' );
				} else {
					// SAFEGUARD: An unrelated custom homepage is active. Do NOT overwrite.
					update_option( 'notes_adda_unrelated_homepage_notice', 1 );
				}
			}
		}
	}
}