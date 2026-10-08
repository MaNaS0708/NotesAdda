<?php
/**
 * Notes Adda Partial: Notifications Panel
 * Reusable notification toggle button and dropdown menu component.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="na-notifications-menu-wrap">
	<button type="button" class="na-icon-btn na-bell-btn na-notifications-toggle" aria-label="Notifications" aria-expanded="false" aria-haspopup="true">
		<span class="dashicons dashicons-bell"></span>
		<span class="na-bell-badge na-notifications-count-badge" style="display:none;" aria-label="0 unread notifications">0</span>
	</button>
	<div class="na-notifications-dropdown" style="display:none;" role="region" aria-label="Notifications">
		<div class="na-dropdown-header">
			<div class="na-dropdown-title-wrap">
				<span class="dashicons dashicons-bell"></span>
				<h4 class="na-dropdown-title">Notifications</h4>
			</div>
			<button type="button" class="na-btn-link na-mark-all-read-btn" title="Mark all as read">Mark all read</button>
		</div>
		<div class="na-notifications-list-wrap">
			<div class="na-notifications-empty" style="display:none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<p>No notifications yet</p>
			</div>
			<div class="na-notifications-items"></div>
		</div>
	</div>
</div>
