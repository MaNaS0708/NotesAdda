<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Activator {

	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$notes_table      = $wpdb->prefix . 'notes_adda_notes';
		$tags_table       = $wpdb->prefix . 'notes_adda_tags';
		$note_tags_table  = $wpdb->prefix . 'notes_adda_note_tags';
		$user_tags_table  = $wpdb->prefix . 'notes_adda_user_tags';
		$likes_table      = $wpdb->prefix . 'notes_adda_likes';
		$reports_table    = $wpdb->prefix . 'notes_adda_reports';
		$profiles_table   = $wpdb->prefix . 'notes_adda_profiles';

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
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY (id),
				KEY owner_id (owner_id),
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
				bio text NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY user_id (user_id)
			) $charset_collate;"
		);

		foreach ( $sql as $table_sql ) {
			dbDelta( $table_sql );
		}

        $wpdb->query(
        	"ALTER TABLE $notes_table AUTO_INCREMENT = 1000001"
        );
	}
}