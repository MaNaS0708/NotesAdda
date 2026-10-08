<?php
/**
 * Notes Adda Partial: User Management View
 * Member directory for searching community accounts and assigning Notes Adda roles.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="na-view-users" class="na-view" style="display:none;" aria-labelledby="na-users-heading">
	<div class="na-view-header">
		<div>
			<span class="na-eyebrow">Access Control & Staff</span>
			<h2 id="na-users-heading" class="na-view-title">User Management</h2>
			<p class="na-view-subtitle">Search registered community members and assign Notes Adda roles (Student, Expert, Notes Adda Admin).</p>
		</div>
	</div>

	<!-- Search Box -->
	<div class="na-toolbar" role="search" aria-label="Search community members">
		<div class="na-search-box">
			<span class="dashicons dashicons-search na-search-icon" aria-hidden="true"></span>
			<input type="search" id="na-user-search-input" placeholder="Search users by username, display name, or email" class="na-input" aria-label="Search users">
		</div>
		<button type="button" id="na-search-users-btn" class="na-btn na-btn-secondary" aria-label="Perform user search">
			<span class="dashicons dashicons-search" aria-hidden="true"></span>
			<span>Search</span>
		</button>
	</div>

	<div class="na-results-meta">
		<span id="na-users-count">Community Members</span>
		<span class="na-results-meta-hint">Roles control review powers, subject creation, and user management.</span>
	</div>

	<!-- Loading State -->
	<div id="na-users-loading" class="na-state-box na-loading-box" style="display:none;" aria-live="polite">
		<span class="dashicons dashicons-update na-spin na-state-icon" aria-hidden="true"></span>
		<p class="na-state-title">Loading users...</p>
	</div>

	<!-- Empty State -->
	<div id="na-users-empty" class="na-state-box na-empty-box" style="display:none;">
		<div class="na-state-icon-wrap"><span class="dashicons dashicons-admin-users na-state-icon" aria-hidden="true"></span></div>
		<h3 class="na-state-title">No users found</h3>
		<p class="na-state-desc">No accounts matched your search criteria.</p>
	</div>

	<!-- Users List / Stacked Cards on Mobile -->
	<div id="na-users-results" class="na-users-list" aria-live="polite"></div>

	<!-- Pagination -->
	<div id="na-users-pagination" class="na-pagination-container"></div>
</section>
