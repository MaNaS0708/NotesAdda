<?php
/**
 * Notes Adda Partial: Review Queue View & Decision Modal
 * Dedicated interface for expert reviewers and administrators to verify or reject submissions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Review Queue View -->
<section id="na-view-review-queue" class="na-view" style="display:none;" aria-labelledby="na-review-heading">
	<div class="na-view-header">
		<div>
			<span class="na-eyebrow">Quality & Verification</span>
			<h2 id="na-review-heading" class="na-view-title">Review Queue</h2>
			<p class="na-view-subtitle">Review student submissions, verify reliable notes, and moderate community content.</p>
		</div>
	</div>

	<!-- Review Tabs -->
	<div class="na-review-filter-tabs" role="tablist" aria-label="Review status filters">
		<button type="button" class="na-tab-btn active" data-review-status="pending" role="tab" aria-selected="true">
			<span class="dashicons dashicons-clock" aria-hidden="true"></span>
			<span>Pending Notes</span>
		</button>
		<button type="button" class="na-tab-btn" data-review-status="verified" role="tab" aria-selected="false">
			<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
			<span>Verified Notes</span>
		</button>
		<button type="button" class="na-tab-btn" data-review-status="rejected" role="tab" aria-selected="false">
			<span class="dashicons dashicons-dismiss" aria-hidden="true"></span>
			<span>Rejected Notes</span>
		</button>
		<button type="button" class="na-tab-btn" data-review-status="all" role="tab" aria-selected="false">
			<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
			<span>All Notes</span>
		</button>
	</div>

	<div class="na-results-meta">
		<span id="na-review-result-count">Notes awaiting review</span>
		<span class="na-results-meta-hint">Review content before marking verified or removing inappropriate materials.</span>
	</div>

	<!-- Loading State -->
	<div id="na-review-loading" class="na-state-box na-loading-box" style="display:none;" aria-live="polite">
		<span class="dashicons dashicons-update na-spin na-state-icon" aria-hidden="true"></span>
		<p class="na-state-title">Loading review queue...</p>
	</div>

	<!-- Empty State -->
	<div id="na-review-empty" class="na-state-box na-empty-box" style="display:none;">
		<div class="na-state-icon-wrap"><span class="dashicons dashicons-yes-alt na-state-icon" aria-hidden="true"></span></div>
		<h3 class="na-state-title">Queue is clear!</h3>
		<p class="na-state-desc">There are no unverified notes awaiting review right now.</p>
	</div>

	<!-- Review Queue Items -->
	<div id="na-review-results" class="na-review-list" aria-live="polite"></div>

	<!-- Pagination -->
	<div id="na-review-pagination" class="na-pagination-container"></div>
</section>

<!-- Review Decision Modal (Approve / Reject with required reason) -->
<div id="na-review-modal" class="na-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="na-review-modal-title">
	<div class="na-modal-dialog">
		<div class="na-modal-header">
			<h3 id="na-review-modal-title" class="na-modal-title">Review Submission</h3>
			<button type="button" class="na-modal-close-btn" data-modal="review" aria-label="Close modal">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="na-modal-body">
			<form id="na-review-form" class="na-form">
				<input type="hidden" id="na-review-note-id" name="note_id" value="">
				<input type="hidden" id="na-review-action-type" name="action_type" value="verify">

				<div class="na-review-meta-preview">
					<div class="na-review-meta-item">
						<span class="na-review-meta-label">Note Title:</span>
						<span id="na-review-target-title" class="na-review-meta-val"></span>
					</div>
					<div class="na-review-meta-item">
						<span class="na-review-meta-label">Action:</span>
						<span id="na-review-action-badge" class="na-badge"></span>
					</div>
				</div>

				<div class="na-form-group" style="margin-top:16px;">
					<label for="na-review-reason">
						Review Reason / Moderation Note <span class="na-required">*</span>
					</label>
					<textarea id="na-review-reason" name="review_note" class="na-input na-textarea" rows="4" placeholder="Explain why this note is being approved or what must be corrected before resubmission..." required></textarea>
					<span class="na-form-hint">This decision explanation is sent directly to the note author in persistent notifications.</span>
				</div>

				<div id="na-review-modal-msg" class="na-form-message"></div>

				<div class="na-modal-footer" style="padding:0; margin-top:20px;">
					<button type="button" class="na-btn na-btn-ghost na-modal-close-btn na-modal-cancel-btn" data-modal="review">Cancel</button>
					<button type="submit" class="na-btn" id="na-submit-review-btn">
						<span class="na-btn-text" id="na-submit-review-text">Submit Review</span>
						<span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
