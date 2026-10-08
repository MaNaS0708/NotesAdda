<?php
/**
 * Notes Adda Partial: Sidebar Navigation
 * Contains brand logo, user card, primary upload action, view links, and sign out button.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
$owner_id     = (int) get_option( 'notes_adda_owner_id' );
$is_owner     = ( $owner_id > 0 && (int) $current_user->ID === $owner_id );

$can_upload   = $is_owner || current_user_can( 'notes_adda_upload_notes' ) || current_user_can( 'manage_options' );
$can_review   = $is_owner || current_user_can( 'notes_adda_review_notes' ) || current_user_can( 'manage_options' );
$can_subjects = $is_owner || current_user_can( 'notes_adda_manage_subjects' ) || current_user_can( 'manage_options' );
$can_users    = $is_owner || current_user_can( 'notes_adda_manage_users' ) || current_user_can( 'manage_options' );

$role_badge_class = 'na-role-student';
$role_badge_text  = 'Student';
if ( $is_owner ) {
	$role_badge_class = 'na-role-owner';
	$role_badge_text  = 'Owner';
} elseif ( $can_users ) {
	$role_badge_class = 'na-role-admin';
	$role_badge_text  = 'Notes Adda Admin';
} elseif ( $can_review ) {
	$role_badge_class = 'na-role-expert';
	$role_badge_text  = 'Expert';
}
?>
<!-- Mobile Drawer Backdrop Overlay -->
<div id="na-sidebar-backdrop" class="na-sidebar-backdrop" aria-hidden="true"></div>

<!-- Sidebar Component -->
<aside id="na-app-sidebar" class="na-sidebar" aria-label="Application Navigation">
	<div class="na-sidebar-header">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="na-sidebar-brand-link" title="Notes Adda">
			<img src="<?php echo esc_url( NOTES_ADDA_URL . 'ui/assets/images/logoname.png' ); ?>" alt="Notes Adda" class="na-sidebar-brand-logo" width="220" height="46">
		</a>
		<button type="button" id="na-sidebar-close-btn" class="na-icon-btn na-sidebar-close-btn" aria-label="Close navigation drawer">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>
	
	<div class="na-user-card">
		<div class="na-user-avatar">
			<?php echo get_avatar( $current_user->ID, 44 ); ?>
		</div>
		<div class="na-user-details">
			<span class="na-user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
			<div class="na-user-meta-row">
				<span class="na-user-handle">@<?php echo esc_html( $current_user->user_login ); ?></span>
				<span class="na-role-badge <?php echo esc_attr( $role_badge_class ); ?>"><?php echo esc_html( $role_badge_text ); ?></span>
			</div>
		</div>
	</div>

	<div class="na-sidebar-action">
		<button type="button" class="na-btn na-btn-primary na-btn-block" id="na-new-note-btn">
			<span class="dashicons dashicons-plus-alt2"></span>
			<span>Upload Note</span>
		</button>
	</div>

	<nav class="na-sidebar-nav" aria-label="Main Navigation">
		<div class="na-nav-label">Navigation</div>
		<ul class="na-nav-list">
			<li>
				<a href="#library" class="na-nav-item active" data-view="library">
					<span class="dashicons dashicons-books"></span>
					<span class="na-nav-text">Browse library</span>
				</a>
			</li>
			<li>
				<a href="#my-notes" class="na-nav-item" data-view="my-notes">
					<span class="dashicons dashicons-category"></span>
					<span class="na-nav-text">My Notes</span>
				</a>
			</li>
			<li>
				<a href="#bookmarks" class="na-nav-item" data-view="bookmarks">
					<span class="na-nav-icon"><svg viewBox="0 0 24 24" class="na-svg-nav" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></span>
					<span class="na-nav-text">Bookmarks</span>
				</a>
			</li>
		</ul>

		<?php if ( $can_review || $can_subjects || $can_users ) : ?>
			<div class="na-nav-label" style="margin-top:20px;">Administration</div>
			<ul class="na-nav-list">
				<?php if ( $can_review ) : ?>
					<li>
						<a href="#review-queue" class="na-nav-item" data-view="review-queue">
							<span class="dashicons dashicons-shield"></span>
							<span class="na-nav-text">Review Queue</span>
							<span id="na-pending-reviews-badge" class="na-nav-counter-badge na-badge-danger" style="display:none;" aria-label="0 notes awaiting verification">0</span>
						</a>
					</li>
				<?php endif; ?>
				<?php if ( $can_subjects ) : ?>
					<li>
						<a href="#subject-requests" class="na-nav-item" data-view="subject-requests">
							<span class="dashicons dashicons-clipboard"></span>
							<span class="na-nav-text">Subject Requests</span>
							<span id="na-pending-requests-badge" class="na-nav-counter-badge" style="display:none;">0</span>
						</a>
					</li>
					<li>
						<a href="#subjects" class="na-nav-item" data-view="subjects">
							<span class="dashicons dashicons-tag"></span>
							<span class="na-nav-text">Subject Management</span>
						</a>
					</li>
				<?php endif; ?>
				<?php if ( $can_users ) : ?>
					<li>
						<a href="#users" class="na-nav-item" data-view="users">
							<span class="dashicons dashicons-admin-users"></span>
							<span class="na-nav-text">User Management</span>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>
	</nav>

	<div class="na-sidebar-footer">
		<button type="button" id="na-logout-btn" class="na-logout-btn" aria-label="Sign Out">
			<span class="dashicons dashicons-migrate"></span>
			<span>Sign Out</span>
		</button>
	</div>
</aside>
