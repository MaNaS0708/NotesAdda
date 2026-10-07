<?php
/**
 * Plugin Name: Notes Adda
 * Description: Student notes sharing application with review workflows, subjects, personal bookmarks, role governance, and public landing page.
 * Version: 0.3.1
 * Author: Manas
 * Text Domain: notes-adda
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOTES_ADDA_VERSION', '0.3.1' );
define( 'NOTES_ADDA_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOTES_ADDA_URL', plugin_dir_url( __FILE__ ) );

require_once NOTES_ADDA_PATH . 'database/class-notes-adda-activator.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-tags.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-user-profile.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-subjects.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-subject-requests.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-bookmarks.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-notes.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-note-tags.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-note-query.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-likes.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-reports.php';
require_once NOTES_ADDA_PATH . 'ajax/class-notes-adda-ajax.php';
require_once NOTES_ADDA_PATH . 'ui/class-notes-adda-frontend.php';
require_once NOTES_ADDA_PATH . 'ajax/class-notes-adda-uploads.php';
require_once NOTES_ADDA_PATH . 'ajax/class-notes-adda-auth.php';

register_activation_hook( __FILE__, array( 'Notes_Adda_Activator', 'activate' ) );

// Auto-upgrade schema, roles, and provision pages if version changed
add_action( 'init', function() {
	$installed_ver = get_option( 'notes_adda_version' );
	if ( $installed_ver !== NOTES_ADDA_VERSION ) {
		Notes_Adda_Activator::activate();
	}
} );

// Admin notice when an unrelated custom homepage was detected and preserved
add_action( 'admin_notices', function() {
	if ( get_option( 'notes_adda_unrelated_homepage_notice' ) ) {
		$landing_page_id = (int) get_option( 'notes_adda_landing_page_id' );
		$landing_url     = $landing_page_id > 0 ? get_permalink( $landing_page_id ) : home_url( '/notes-adda-home/' );
		$reading_url     = admin_url( 'options-reading.php' );
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong>Notes Adda:</strong> The Notes Adda landing page was created (<a href="<?php echo esc_url( $landing_url ); ?>" target="_blank">view page</a>). Because your site currently has a custom homepage configured, your existing homepage was preserved. To set Notes Adda as your site homepage, navigate to <a href="<?php echo esc_url( $reading_url ); ?>">Settings &rarr; Reading</a> and select <em>Notes Adda</em> as your Homepage.
			</p>
		</div>
		<?php
	}
} );

// Protect Owner against modification/deletion via map_meta_cap
add_filter( 'map_meta_cap', function( $caps, $cap, $user_id, $args ) {
	$owner_id = (int) get_option( 'notes_adda_owner_id' );
	if ( $owner_id > 0 && in_array( $cap, array( 'delete_user', 'edit_user', 'remove_user', 'promote_user' ), true ) ) {
		$target_user_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( $target_user_id === $owner_id && (int) $user_id !== $owner_id ) {
			$caps[] = 'do_not_allow';
		}
	}
	return $caps;
}, 10, 4 );

// Guarantee Owner has all notes_adda capabilities always
add_filter( 'user_has_cap', function( $allcaps, $caps, $args, $user ) {
	$owner_id = (int) get_option( 'notes_adda_owner_id' );
	if ( $owner_id > 0 && $user && (int) $user->ID === $owner_id ) {
		$allcaps['notes_adda_upload_notes']     = true;
		$allcaps['notes_adda_request_subjects'] = true;
		$allcaps['notes_adda_review_notes']     = true;
		$allcaps['notes_adda_manage_subjects']  = true;
		$allcaps['notes_adda_manage_all_notes'] = true;
		$allcaps['notes_adda_manage_users']     = true;
	}
	return $allcaps;
}, 10, 4 );

// Reserve the "notes_adda_dev" username on standard WordPress registration
add_filter( 'registration_errors', function( $errors, $sanitized_user_login, $user_email ) {
	if ( 'notes_adda_dev' === strtolower( trim( $sanitized_user_login ) ) ) {
		$errors->add( 'notes_adda_reserved_username', __( 'This username is reserved and cannot be registered.', 'notes-adda' ) );
	}
	return $errors;
}, 10, 3 );
