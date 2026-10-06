<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Frontend {

	public static function init() {
		add_shortcode( 'notes_adda_app', array( __CLASS__, 'render_app' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_filter( 'template_include', array( __CLASS__, 'load_custom_template' ) );
	}

	public static function load_custom_template( $template ) {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'notes_adda_app' ) ) {
			return NOTES_ADDA_PATH . 'ui/html/page-app.php';
		}
		return $template;
	}

	public static function enqueue_scripts() {
		global $post;

		// Only enqueue if the shortcode is present
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'notes_adda_app' ) ) {
			show_admin_bar( false );
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

			$can_upload          = $is_logged_in && ( current_user_can( 'notes_adda_upload_notes' ) || current_user_can( 'manage_options' ) );
			$can_review          = $is_logged_in && ( current_user_can( 'notes_adda_review_notes' ) || current_user_can( 'manage_options' ) );
			$can_manage_subjects = $is_logged_in && ( current_user_can( 'notes_adda_manage_subjects' ) || current_user_can( 'manage_options' ) );
			$can_manage_users    = $is_logged_in && ( current_user_can( 'notes_adda_manage_users' ) || current_user_can( 'manage_options' ) );
			$can_manage_all      = $is_logged_in && ( current_user_can( 'notes_adda_manage_all_notes' ) || current_user_can( 'manage_options' ) );

			$app_role = 'student';
			$role_label = 'Student';
			if ( $can_manage_users ) {
				$app_role = 'admin';
				$role_label = 'Notes Adda Admin';
			} elseif ( $can_review ) {
				$app_role = 'expert';
				$role_label = 'Expert';
			}

			wp_localize_script(
				'notes-adda-app-script',
				'NotesAdda',
				array(
					'ajax_url'             => admin_url( 'admin-ajax.php' ),
					'nonce'                => wp_create_nonce( 'notes_adda_ajax_nonce' ),
					'is_logged_in'         => $is_logged_in,
					'user_id'              => $is_logged_in ? $current_user->ID : 0,
					'user_name'            => $is_logged_in ? $current_user->display_name : '',
					'user_login'           => $is_logged_in ? $current_user->user_login : '',
					'user_role'            => $app_role,
					'role_label'           => $role_label,
					'can_upload'           => $can_upload,
					'can_review'           => $can_review,
					'can_manage_subjects'  => $can_manage_subjects,
					'can_manage_users'     => $can_manage_users,
					'can_manage_all_notes' => $can_manage_all,
					'login_url'            => wp_login_url( get_permalink() ),
				)
			);

			// Enqueue Dashicons
			wp_enqueue_style( 'dashicons' );
		}
	}

	public static function render_app( $atts ) {
		ob_start();
		include NOTES_ADDA_PATH . 'ui/html/app.php';
		return ob_get_clean();
	}
}

Notes_Adda_Frontend::init();
