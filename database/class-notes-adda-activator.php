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

		update_option( 'notes_adda_db_version', NOTES_ADDA_VERSION );
	}

	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$notes_table     = $wpdb->prefix . 'notes_adda_notes';
		$tags_table      = $wpdb->prefix . 'notes_adda_tags';
		$note_tags_table = $wpdb->prefix . 'notes_adda_note_tags';
		$user_tags_table = $wpdb->prefix . 'notes_adda_user_tags';
		$likes_table     = $wpdb->prefix . 'notes_adda_likes';
		$reports_table   = $wpdb->prefix . 'notes_adda_reports';
		$profiles_table  = $wpdb->prefix . 'notes_adda_profiles';
		$subjects_table  = $wpdb->prefix . 'notes_adda_subjects';
		$bookmarks_table = $wpdb->prefix . 'notes_adda_bookmarks';
		$subject_requests_table = $wpdb->prefix . 'notes_adda_subject_requests';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

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
				review_status varchar(30) NOT NULL DEFAULT 'unverified',
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
			'notes_adda_manage_all_notes' => true,
		);
		$expert_role = get_role( 'notes_adda_expert' );
		if ( ! $expert_role ) {
			add_role( 'notes_adda_expert', 'Expert', $expert_caps );
		} else {
			$expert_role->remove_cap( 'notes_adda_manage_subjects' );
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
			// Give that user BOTH administrator and notes_adda_admin using add_role()
			$dev_user->add_role( 'administrator' );
			$dev_user->add_role( 'notes_adda_admin' );
		} else {
			// Record safe notice if notes_adda_dev does not exist
			if ( is_admin() ) {
				add_action( 'admin_notices', array( __CLASS__, 'render_missing_dev_notice' ) );
			}
		}
	}

	public static function render_missing_dev_notice() {
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Notes Adda: Account "notes_adda_dev" was not found. Please create the user account first to assign Notes Adda Admin privileges.', 'notes-adda' ) . '</p></div>';
	}

	public static function migrate_existing_users() {
		// Guard with an idempotent option flag so migration runs once
		if ( get_option( 'notes_adda_users_migrated_v2' ) ) {
			return;
		}

		$owner_id = (int) get_option( 'notes_adda_owner_id' );
		$users    = get_users( array( 'fields' => 'all' ) );

		foreach ( $users as $user ) {
			// Never touch owner
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

			// If user is administrator or has manage_options, grant notes_adda_admin as an ADDITIONAL role.
			// NEVER remove administrator or use set_role()!
			if ( in_array( 'administrator', $user_roles, true ) || user_can( $user->ID, 'manage_options' ) ) {
				$user->add_role( 'administrator' );
				$user->add_role( 'notes_adda_admin' );
				continue;
			}

			// If user already has a Notes Adda role (e.g. expert or admin), keep it intact
			if ( in_array( 'notes_adda_expert', $user_roles, true ) || in_array( 'notes_adda_admin', $user_roles, true ) ) {
				continue;
			}

			// If user is subscriber or has no roles, migrate to notes_adda_student
			if ( empty( $user_roles ) || in_array( 'subscriber', $user_roles, true ) ) {
				$user->remove_role( 'subscriber' );
				$user->add_role( 'notes_adda_student' );
			} else {
				// Other existing roles (e.g., author, editor): add notes_adda_student without replacing existing role
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
}