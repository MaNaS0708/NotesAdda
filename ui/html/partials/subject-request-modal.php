<?php
/**
 * Notes Adda Partial: Subject Request Modal (Student / Expert / Admin)
 * Dialog for submitting subject addition requests and tracking approval status.
 *
 * @package Notes_Adda
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Subject Request Modal (Student / Expert / Admin) -->
<div id="na-subject-request-modal" class="na-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="na-subject-req-modal-title">
	<div class="na-modal-dialog">
		<div class="na-modal-header">
			<h3 id="na-subject-req-modal-title" class="na-modal-title">Subject Request</h3>
			<button type="button" class="na-modal-close-btn" data-modal="subject-request" aria-label="Close modal">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="na-modal-body">
			<!-- Modal Subtabs -->
			<div class="na-modal-tabs" role="tablist">
				<button type="button" class="na-modal-tab-btn active" data-subtab="new-request" role="tab" aria-selected="true">
					<span class="dashicons dashicons-plus-alt" aria-hidden="true"></span> Request New Subject
				</button>
				<button type="button" class="na-modal-tab-btn" data-subtab="my-requests" id="na-my-requests-tab-btn" role="tab" aria-selected="false">
					<span class="dashicons dashicons-list-view" aria-hidden="true"></span> My Requests <span id="na-my-requests-badge" class="na-subtab-badge" style="display:none;">0</span>
				</button>
			</div>

			<!-- Tab 1: New Request Form -->
			<div id="na-subtab-new-request" class="na-modal-subtab-pane active">
				<form id="na-subject-request-form" class="na-form" style="margin-top:16px;">
					<div class="na-form-group">
						<label for="na-req-subject-name">Requested Subject Name <span class="na-required">*</span></label>
						<input type="text" id="na-req-subject-name" name="name" class="na-input" placeholder="e.g. Computer Graphics, Biochemistry" required autocomplete="off">
					</div>

					<div class="na-form-group">
						<label for="na-req-subject-reason">Reason or Note <span class="na-label-hint">(Optional)</span></label>
						<textarea id="na-req-subject-reason" name="reason" class="na-input na-textarea" rows="2" placeholder="Course title, syllabus code, or why this subject is needed..."></textarea>
					</div>

					<div id="na-subject-request-msg" class="na-form-message"></div>

					<div class="na-modal-footer" style="padding:0; margin-top:20px;">
						<button type="button" class="na-btn na-btn-ghost na-modal-close-btn na-modal-cancel-btn" data-modal="subject-request">Cancel</button>
						<button type="submit" class="na-btn na-btn-primary" id="na-submit-subject-req-btn">
							<span class="na-btn-text">Submit Request</span>
							<span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
						</button>
					</div>
				</form>
			</div>

			<!-- Tab 2: My Requests List -->
			<div id="na-subtab-my-requests" class="na-modal-subtab-pane" style="display:none; margin-top:16px;">
				<div id="na-my-requests-loading" class="na-state-box na-loading-box" style="padding:20px; display:none;" aria-live="polite">
					<span class="dashicons dashicons-update na-spin na-state-icon" aria-hidden="true"></span>
					<p class="na-state-title">Loading your requests...</p>
				</div>
				<div id="na-my-requests-empty" class="na-state-box na-empty-box" style="padding:24px; display:none;">
					<div class="na-state-icon-wrap"><span class="dashicons dashicons-tag na-state-icon" aria-hidden="true"></span></div>
					<h3 class="na-state-title" style="font-size:15px;">No requests submitted yet</h3>
					<p class="na-state-desc" style="font-size:13px;">When you request new study subjects, track their approval status here.</p>
				</div>
				<div id="na-my-requests-list" class="na-my-requests-list" aria-live="polite"></div>
			</div>
		</div>
	</div>
</div>
