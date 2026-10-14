<?php
/**
 * Notes Adda Partial: Report Note Modal (Student / Community)
 * Allows any authenticated user to report copyright violations, spam, or inaccurate content.
 *
 * @package Notes_Adda
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Report Note Modal -->
<div id="na-report-modal" class="na-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="na-report-modal-title">
	<div class="na-modal-dialog">
		<div class="na-modal-header">
			<div class="na-modal-header-icon-wrap" style="display:flex; align-items:center; gap:8px;">
				<span class="dashicons dashicons-flag" style="color:var(--na-danger); font-size:20px; width:20px; height:20px;"></span>
				<h3 id="na-report-modal-title" class="na-modal-title">Report Note</h3>
			</div>
			<button type="button" class="na-modal-close-btn" data-modal="report" aria-label="Close modal">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="na-modal-body">
			<form id="na-report-form" class="na-form">
				<input type="hidden" id="na-report-note-id" name="note_id" value="">

				<div class="na-review-meta-preview">
					<div class="na-review-meta-item">
						<span class="na-review-meta-label">Note Title:</span>
						<span id="na-report-note-title" class="na-review-meta-val"></span>
					</div>
					<div class="na-review-meta-item" id="na-report-note-subject-row" style="display:none;">
						<span class="na-review-meta-label">Subject:</span>
						<span id="na-report-note-subject" class="na-review-meta-val"></span>
					</div>
				</div>

				<div class="na-form-group" style="margin-top:16px;">
					<label for="na-report-reason">
						Reason for Reporting <span class="na-required">*</span>
					</label>
					<textarea id="na-report-reason" name="reason" class="na-input na-textarea" rows="4" placeholder="Please describe why this note violates guidelines (e.g. copyright infringement, incorrect formulas/information, spam, inappropriate content)..." required></textarea>
					<span class="na-form-hint">Our moderation team (Admins & Subject Experts) will review your report and take appropriate action.</span>
				</div>

				<div id="na-report-modal-msg" class="na-form-message"></div>

				<div class="na-modal-footer" style="padding:0; margin-top:20px;">
					<button type="button" class="na-btn na-btn-ghost na-modal-close-btn na-modal-cancel-btn" data-modal="report">Cancel</button>
					<button type="submit" class="na-btn na-btn-danger" id="na-submit-report-btn">
						<span class="na-btn-text" id="na-submit-report-text">Submit Report</span>
						<span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
