<?php
/**
 * Notes Adda Partial: Subject Requests View & Request Modal
 * Admin taxonomy queue and student/expert subject request dialog.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
$owner_id     = (int) get_option( 'notes_adda_owner_id' );
$is_owner     = ( $owner_id > 0 && (int) $current_user->ID === $owner_id );
$can_subjects = $is_owner || current_user_can( 'notes_adda_manage_subjects' ) || current_user_can( 'manage_options' );
?>
<?php if ( $can_subjects ) : ?>
<!-- Subject Requests View (Admin Only) -->
<section id="na-view-subject-requests" class="na-view" style="display:none;" aria-labelledby="na-subject-requests-heading">
	<div class="na-view-header">
		<div>
			<span class="na-eyebrow">Taxonomy Governance</span>
			<h2 id="na-subject-requests-heading" class="na-view-title">Subject Requests</h2>
			<p class="na-view-subtitle">Review student and expert requests for new study subjects. Approved subjects are instantly added to the active catalog.</p>
		</div>
	</div>

	<!-- Subject Request Filter Tabs -->
	<div class="na-review-filter-tabs" role="tablist" aria-label="Subject request filters">
		<button type="button" class="na-tab-btn active" data-subject-req-status="pending" role="tab" aria-selected="true">
			<span class="dashicons dashicons-clock" aria-hidden="true"></span>
			<span>Pending Requests</span>
		</button>
		<button type="button" class="na-tab-btn" data-subject-req-status="all" role="tab" aria-selected="false">
			<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
			<span>All Requests</span>
		</button>
		<button type="button" class="na-tab-btn" data-subject-req-status="approved" role="tab" aria-selected="false">
			<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
			<span>Approved</span>
		</button>
		<button type="button" class="na-tab-btn" data-subject-req-status="rejected" role="tab" aria-selected="false">
			<span class="dashicons dashicons-dismiss" aria-hidden="true"></span>
			<span>Rejected</span>
		</button>
	</div>

	<div class="na-results-meta">
		<span id="na-subject-requests-count">Subject requests awaiting review</span>
		<span class="na-results-meta-hint">Approving a request creates the subject in the active catalog immediately.</span>
	</div>

	<!-- Loading State -->
	<div id="na-subject-requests-loading" class="na-state-box na-loading-box" style="display:none;" aria-live="polite">
		<span class="dashicons dashicons-update na-spin na-state-icon" aria-hidden="true"></span>
		<p class="na-state-title">Loading subject requests...</p>
	</div>

	<!-- Empty State -->
	<div id="na-subject-requests-empty" class="na-state-box na-empty-box" style="display:none;">
		<div class="na-state-icon-wrap"><span class="dashicons dashicons-yes-alt na-state-icon" aria-hidden="true"></span></div>
		<h3 class="na-state-title">No requests found</h3>
		<p class="na-state-desc">There are no subject requests matching the selected filter.</p>
	</div>

	<!-- Subject Requests Table / Cards -->
	<div id="na-subject-requests-results" class="na-subject-requests-list" aria-live="polite"></div>
</section>
<?php endif; ?>

