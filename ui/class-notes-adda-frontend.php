<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Frontend {

	public static function init() {
		add_shortcode( 'notes_adda_app', array( __CLASS__, 'render_app' ) );
		add_shortcode( 'notes_adda_landing', array( __CLASS__, 'render_landing' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_filter( 'template_include', array( __CLASS__, 'load_custom_template' ) );
	}

	public static function get_app_page_id() {
		$cached_id = (int) get_option( 'notes_adda_app_page_id' );
		if ( $cached_id > 0 && 'publish' === get_post_status( $cached_id ) ) {
			$post = get_post( $cached_id );
			if ( $post && has_shortcode( $post->post_content, 'notes_adda_app' ) ) {
				return $cached_id;
			}
		}

		// Search dynamically for page containing [notes_adda_app]
		global $wpdb;
		$found_id = (int) $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} 
			 WHERE post_type = 'page' 
			   AND post_status = 'publish' 
			   AND post_content LIKE '%[notes_adda_app]%' 
			 ORDER BY ID ASC 
			 LIMIT 1"
		);

		if ( $found_id > 0 ) {
			update_option( 'notes_adda_app_page_id', $found_id );
			return $found_id;
		}

		$fallback_page = get_page_by_path( 'notes-adda' );
		if ( $fallback_page && 'publish' === $fallback_page->post_status ) {
			return $fallback_page->ID;
		}

		return 0;
	}

	public static function get_app_url( $args = array() ) {
		$page_id = self::get_app_page_id();
		$url     = $page_id > 0 ? get_permalink( $page_id ) : home_url( '/notes-adda/' );

		if ( ! empty( $args ) && is_array( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	public static function get_landing_page_id() {
		$cached_id = (int) get_option( 'notes_adda_landing_page_id' );
		if ( $cached_id > 0 && 'publish' === get_post_status( $cached_id ) ) {
			$post = get_post( $cached_id );
			if ( $post && has_shortcode( $post->post_content, 'notes_adda_landing' ) ) {
				return $cached_id;
			}
		}

		global $wpdb;
		$found_id = (int) $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} 
			 WHERE post_type = 'page' 
			   AND post_status = 'publish' 
			   AND post_content LIKE '%[notes_adda_landing]%' 
			 ORDER BY ID ASC 
			 LIMIT 1"
		);

		if ( $found_id > 0 ) {
			update_option( 'notes_adda_landing_page_id', $found_id );
			return $found_id;
		}

		$fallback_page = get_page_by_path( 'notes-adda-home' );
		if ( $fallback_page && 'publish' === $fallback_page->post_status ) {
			return $fallback_page->ID;
		}

		return 0;
	}

	public static function get_landing_url() {
		$page_id = self::get_landing_page_id();
		return $page_id > 0 ? get_permalink( $page_id ) : home_url( '/' );
	}

	public static function load_custom_template( $template ) {
		global $post;
		if ( is_a( $post, 'WP_Post' ) ) {
			if ( has_shortcode( $post->post_content, 'notes_adda_app' ) ) {
				return NOTES_ADDA_PATH . 'ui/html/page-app.php';
			}
			if ( has_shortcode( $post->post_content, 'notes_adda_landing' ) ) {
				return NOTES_ADDA_PATH . 'ui/html/page-landing.php';
			}
		}
		return $template;
	}

	public static function enqueue_scripts() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		// Check for Landing shortcode
		if ( has_shortcode( $post->post_content, 'notes_adda_landing' ) ) {
			show_admin_bar( false );
			wp_enqueue_style(
				'notes-adda-cyber-fonts',
				'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap',
				array(),
				null
			);
			wp_enqueue_style(
				'notes-adda-landing-style',
				NOTES_ADDA_URL . 'ui/css/notes-adda-landing.css',
				array(),
				NOTES_ADDA_VERSION
			);
			wp_enqueue_style( 'dashicons' );
		}

		// Check for App shortcode
		if ( has_shortcode( $post->post_content, 'notes_adda_app' ) ) {
			show_admin_bar( false );
			wp_enqueue_style(
				'notes-adda-cyber-fonts',
				'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap',
				array(),
				null
			);
			wp_enqueue_style(
				'notes-adda-app-style',
				NOTES_ADDA_URL . 'ui/css/notes-adda-app.css',
				array(),
				NOTES_ADDA_VERSION
			);

			wp_enqueue_script(
				'notes-adda-app-script',
				NOTES_ADDA_URL . 'ui/js/notes-adda-app.js',
				array( 'jquery' ),
				NOTES_ADDA_VERSION,
				true
			);

			// Fetch current user details and capabilities
			$current_user = wp_get_current_user();
			$is_logged_in = is_user_logged_in();
			$owner_id     = (int) get_option( 'notes_adda_owner_id' );
			$is_owner     = ( $is_logged_in && $owner_id > 0 && (int) $current_user->ID === $owner_id );

			$can_upload           = $is_logged_in && ( $is_owner || current_user_can( 'notes_adda_upload_notes' ) || current_user_can( 'manage_options' ) );
			$can_review           = $is_logged_in && ( $is_owner || current_user_can( 'notes_adda_review_notes' ) || current_user_can( 'manage_options' ) );
			$can_request_subjects = $is_logged_in && ( $is_owner || current_user_can( 'notes_adda_request_subjects' ) || current_user_can( 'manage_options' ) );
			$can_manage_subjects  = $is_logged_in && ( $is_owner || current_user_can( 'notes_adda_manage_subjects' ) || current_user_can( 'manage_options' ) );
			$can_manage_users     = $is_logged_in && ( $is_owner || current_user_can( 'notes_adda_manage_users' ) || current_user_can( 'manage_options' ) );
			$can_manage_all       = $is_logged_in && ( $is_owner || $can_manage_users || current_user_can( 'manage_options' ) );

			$app_role   = 'student';
			$role_label = 'Student';

			if ( $is_owner ) {
				$app_role   = 'admin';
				$role_label = 'Owner';
			} elseif ( $can_manage_users ) {
				$app_role   = 'admin';
				$role_label = 'Notes Adda Admin';
			} elseif ( $can_review ) {
				$app_role   = 'expert';
				$role_label = 'Expert';
			}

			wp_localize_script(
				'notes-adda-app-script',
				'NotesAdda',
				array(
					'ajax_url'             => admin_url( 'admin-ajax.php' ),
					'nonce'                => wp_create_nonce( 'notes_adda_ajax_nonce' ),
					'is_logged_in'         => $is_logged_in,
					'is_owner'             => $is_owner,
					'user_id'              => $is_logged_in ? $current_user->ID : 0,
					'user_name'            => $is_logged_in ? $current_user->display_name : '',
					'user_login'           => $is_logged_in ? $current_user->user_login : '',
					'user_role'            => $app_role,
					'role_label'           => $role_label,
					'can_upload'           => $can_upload,
					'can_review'           => $can_review,
					'can_request_subjects' => $can_request_subjects,
					'can_manage_subjects'  => $can_manage_subjects,
					'can_manage_users'     => $can_manage_users,
					'can_manage_all_notes' => $can_manage_all,
					'login_url'            => wp_login_url( get_permalink() ),
					'logo_icon'            => NOTES_ADDA_URL . 'ui/assets/images/logo_wui.png',
					'logo_name'            => NOTES_ADDA_URL . 'ui/assets/images/logoname.png',
				)
			);

			// Enqueue Dashicons
			wp_enqueue_style( 'dashicons' );
		}
	}

	public static function render_landing( $atts ) {
		ob_start();
		include NOTES_ADDA_PATH . 'ui/html/landing.php';
		return ob_get_clean();
	}

	public static function render_app( $atts ) {
		ob_start();
		include NOTES_ADDA_PATH . 'ui/html/app.php';
		return ob_get_clean();
	}
}

Notes_Adda_Frontend::init();
