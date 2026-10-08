<?php
/**
 * Notes Adda - Main Application View
 * Orchestrates modular partials for student library, review workflows, taxonomy, and administration.
 *
 * @package Notes_Adda
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	$auth_mode = isset( $_GET['auth'] ) ? sanitize_text_field( wp_unslash( $_GET['auth'] ) ) : '';
	$args      = array();
	if ( in_array( $auth_mode, array( 'login', 'register' ), true ) ) {
		$args['auth'] = $auth_mode;
	}
	$target_url = class_exists( 'Notes_Adda_Frontend' ) ? Notes_Adda_Frontend::get_landing_url( $args ) : home_url( '/' );
	wp_safe_redirect( $target_url );
	exit;
}

$current_user = wp_get_current_user();
$owner_id     = (int) get_option( 'notes_adda_owner_id' );
$is_owner     = ( $owner_id > 0 && (int) $current_user->ID === $owner_id );

$can_upload           = $is_owner || current_user_can( 'notes_adda_upload_notes' ) || current_user_can( 'manage_options' );
$can_review           = $is_owner || current_user_can( 'notes_adda_review_notes' ) || current_user_can( 'manage_options' );
$can_request_subjects = $is_owner || current_user_can( 'notes_adda_request_subjects' ) || current_user_can( 'manage_options' );
$can_subjects         = $is_owner || current_user_can( 'notes_adda_manage_subjects' ) || current_user_can( 'manage_options' );
$can_users            = $is_owner || current_user_can( 'notes_adda_manage_users' ) || current_user_can( 'manage_options' );
?>
<div id="notes-adda-app" class="notes-adda-app-container">
	<?php if ( ! is_user_logged_in() ) : ?>
		<?php include NOTES_ADDA_PATH . 'ui/html/partials/auth-view.php'; ?>
	<?php else : ?>
		<div class="na-app-layout">
			<!-- Application Sidebar -->
			<?php include NOTES_ADDA_PATH . 'ui/html/partials/sidebar.php'; ?>

			<!-- Main Content Canvas -->
			<main class="na-main-container">
				<!-- Mobile Top Bar -->
				<header class="na-mobile-header">
					<div class="na-mobile-header-left">
						<button type="button" id="na-mobile-drawer-toggle" class="na-icon-btn na-drawer-toggle-btn" aria-label="Open navigation menu">
							<span class="dashicons dashicons-menu-alt3"></span>
						</button>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="na-mobile-brand-link" title="Notes Adda">
							<img src="<?php echo esc_url( NOTES_ADDA_URL . 'ui/assets/images/logoname.png' ); ?>" alt="Notes Adda" class="na-mobile-brand-logo" width="140" height="30">
						</a>
					</div>
					<div class="na-mobile-actions">
						<?php include NOTES_ADDA_PATH . 'ui/html/partials/notification-panel.php'; ?>
						<button type="button" class="na-btn na-btn-primary na-btn-sm" id="na-mobile-new-note-btn" aria-label="Upload note">
							<span class="dashicons dashicons-plus"></span> <span>Upload</span>
						</button>
					</div>
				</header>

				<!-- Desktop Top Bar -->
				<header class="na-desktop-topbar">
					<div class="na-topbar-breadcrumb">
						<span class="na-topbar-pulse"></span>
						<span class="na-topbar-breadcrumb-text">Notes Adda Network Active</span>
					</div>
					<div class="na-topbar-actions">
						<?php include NOTES_ADDA_PATH . 'ui/html/partials/notification-panel.php'; ?>
					</div>
				</header>

				<!-- Views -->
				<?php include NOTES_ADDA_PATH . 'ui/html/partials/library-view.php'; ?>
				<?php include NOTES_ADDA_PATH . 'ui/html/partials/my-notes-view.php'; ?>
				<?php include NOTES_ADDA_PATH . 'ui/html/partials/bookmarks-view.php'; ?>
				<?php include NOTES_ADDA_PATH . 'ui/html/partials/note-details-view.php'; ?>

				<?php if ( $can_review ) : ?>
					<?php include NOTES_ADDA_PATH . 'ui/html/partials/review-queue-view.php'; ?>
				<?php endif; ?>

				<?php if ( $can_subjects || $can_request_subjects ) : ?>
					<?php include NOTES_ADDA_PATH . 'ui/html/partials/subject-requests-view.php'; ?>
				<?php endif; ?>

				<?php if ( $can_subjects ) : ?>
					<?php include NOTES_ADDA_PATH . 'ui/html/partials/subject-management-view.php'; ?>
				<?php endif; ?>

				<?php if ( $can_users ) : ?>
					<?php include NOTES_ADDA_PATH . 'ui/html/partials/user-management-view.php'; ?>
				<?php endif; ?>
			</main>
		</div>

		<!-- Upload / Edit Note Modal -->
		<?php include NOTES_ADDA_PATH . 'ui/html/partials/upload-modal.php'; ?>
	<?php endif; ?>

	<!-- Star Rating Gradients (Hidden Defs) -->
	<svg width="0" height="0" class="na-star-svg-defs" style="position:absolute; width:0; height:0; pointer-events:none;" aria-hidden="true">
		<defs>
			<linearGradient id="na-star-grad-100" x1="0%" y1="0%" x2="100%" y2="0%">
				<stop offset="100%" stop-color="#ffb703" />
			</linearGradient>
			<linearGradient id="na-star-grad-75" x1="0%" y1="0%" x2="100%" y2="0%">
				<stop offset="75%" stop-color="#ffb703" />
				<stop offset="75%" stop-color="rgba(255,255,255,0.18)" />
			</linearGradient>
			<linearGradient id="na-star-grad-50" x1="0%" y1="0%" x2="100%" y2="0%">
				<stop offset="50%" stop-color="#ffb703" />
				<stop offset="50%" stop-color="rgba(255,255,255,0.18)" />
			</linearGradient>
			<linearGradient id="na-star-grad-25" x1="0%" y1="0%" x2="100%" y2="0%">
				<stop offset="25%" stop-color="#ffb703" />
				<stop offset="25%" stop-color="rgba(255,255,255,0.18)" />
			</linearGradient>
			<linearGradient id="na-star-grad-0" x1="0%" y1="0%" x2="100%" y2="0%">
				<stop offset="100%" stop-color="rgba(255,255,255,0.18)" />
			</linearGradient>
		</defs>
	</svg>
</div>
