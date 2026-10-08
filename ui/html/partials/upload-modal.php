<?php
/**
 * Notes Adda Partial: Note Upload & Edit Modal
 * Form for submitting notes, selecting course subjects, attaching PDF files, and tagging.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Create / Edit Note Modal -->
<div id="na-note-form-modal" class="na-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="na-note-form-title">
	<div class="na-modal-dialog na-modal-lg">
		<div class="na-modal-header">
			<h3 id="na-note-form-title" class="na-modal-title">Upload New Note</h3>
			<button type="button" class="na-modal-close-btn" data-modal="form" aria-label="Close modal">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="na-modal-body">
			<form id="na-note-form" class="na-form">
				<input type="hidden" id="na-note-id" name="note_id" value="">

				<div class="na-form-group">
					<label for="na-note-title">Title <span class="na-required">*</span></label>
					<input type="text" id="na-note-title" name="title" class="na-input" placeholder="e.g. Operating Systems: Process Scheduling" required>
				</div>

				<div class="na-form-row">
					<div class="na-form-group na-col">
						<div class="na-label-with-action">
							<label for="na-note-subject">Subject <span class="na-required">*</span></label>
							<button type="button" class="na-btn-link na-request-subject-trigger" id="na-request-subject-link">
								<span class="dashicons dashicons-plus"></span> Request a new subject
							</button>
						</div>
						<select id="na-note-subject" name="subject" class="na-select" required>
							<option value="">Select a Subject *</option>
						</select>
						<div id="na-no-subjects-warning" class="na-form-warning" style="display:none; margin-top:5px;">
							No subjects are available yet. <button type="button" class="na-btn-link na-request-subject-trigger">Request a new subject</button> to get started.
						</div>
					</div>
					<div class="na-form-group na-col">
						<label for="na-note-chapter">Chapter / Unit (Optional)</label>
						<input type="text" id="na-note-chapter" name="chapter" class="na-input" placeholder="e.g. Chapter 4">
					</div>
				</div>

				<div class="na-form-group">
					<label for="na-note-description">Description / Overview</label>
					<textarea id="na-note-description" name="description" class="na-input na-textarea" rows="3" placeholder="Brief summary of what this note covers..."></textarea>
				</div>

				<!-- PDF Upload Area -->
				<div class="na-form-group">
					<label>PDF Document <span class="na-required">*</span> <span class="na-label-hint">(Max 50 MB)</span></label>
					<div class="na-file-dropzone" id="na-file-dropzone">
						<input type="file" id="na-note-file" accept="application/pdf" class="na-file-input" aria-label="Upload PDF file">
						<input type="hidden" id="na-note-file-url" name="file_url" value="">
						<input type="hidden" id="na-note-file-id" name="file_id" value="">
						
						<div class="na-dropzone-content">
							<div class="na-dropzone-icon"><span class="dashicons dashicons-pdf" aria-hidden="true"></span></div>
							<div class="na-dropzone-text">
								<span class="na-dropzone-primary">Click to select PDF or drag &amp; drop</span>
								<span class="na-dropzone-secondary" id="na-file-name-display">Only PDF files up to 50 MB supported</span>
							</div>
						</div>
					</div>
					<div id="na-file-upload-status" class="na-file-status"></div>
				</div>

				<div class="na-form-group na-checkbox-wrapper">
					<label class="na-checkbox-label">
						<input type="checkbox" id="na-note-is-whole" name="is_whole_notes">
						<span class="na-checkbox-custom"></span>
						<span class="na-checkbox-text">This note covers the complete course / whole syllabus</span>
					</label>
				</div>

				<div class="na-form-group">
					<label for="na-note-tags">Tags <span class="na-label-hint">(Comma separated)</span></label>
					<input type="text" id="na-note-tags" name="tags" class="na-input" placeholder="e.g. Midterms, Sem 4, Cheatsheet">
				</div>

				<div id="na-form-message" class="na-form-message"></div>

				<div class="na-modal-footer">
					<button type="button" class="na-btn na-btn-ghost na-modal-close-btn na-modal-cancel-btn" data-modal="form">Cancel</button>
					<button type="submit" class="na-btn na-btn-primary" id="na-save-note-btn">
						<span class="na-btn-text">Publish Note</span>
						<span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
