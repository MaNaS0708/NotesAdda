<?php
/**
 * Notes Adda Partial: Note Details View & Modal
 * Dedicated note overview page and quick preview modal dialog with rating & download tools.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Dedicated Note Details Full-Page View -->
<section id="na-view-note-details" class="na-view" style="display:none;" aria-labelledby="na-note-page-title">
	<div class="na-view-header na-note-page-header" style="margin-bottom:16px;">
		<div class="na-note-back-nav">
			<button type="button" class="na-btn na-btn-ghost na-back-btn" id="na-note-back-btn" aria-label="Back to previous view">
				<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
				<span>Back</span>
			</button>
		</div>
	</div>

	<!-- Loading State -->
	<div id="na-note-details-loading" class="na-state-box na-loading-box" style="display:none;" aria-live="polite">
		<span class="dashicons dashicons-update na-spin na-state-icon" aria-hidden="true"></span>
		<p class="na-state-title">Loading note details...</p>
	</div>

	<!-- Not Found / Error State -->
	<div id="na-note-details-error" class="na-state-box na-empty-box" style="display:none;">
		<div class="na-state-icon-wrap"><span class="dashicons dashicons-warning na-state-icon" aria-hidden="true"></span></div>
		<h3 class="na-state-title">Note not found</h3>
		<p class="na-state-desc">This study note does not exist or has been removed from the library.</p>
		<button type="button" class="na-btn na-btn-primary" id="na-notfound-browse-btn" style="margin-top:12px;">
			<span class="dashicons dashicons-books" aria-hidden="true"></span> <span>Back to Study Library</span>
		</button>
	</div>

	<!-- Note Details Page Container -->
	<div id="na-note-details-container" class="na-note-page"></div>
</section>

<!-- Note Details Quick Preview Modal -->
<div id="na-note-details-modal" class="na-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="na-note-modal-title">
	<div class="na-modal-dialog na-modal-lg">
		<div class="na-modal-header">
			<h3 id="na-note-modal-title" class="na-modal-title">Note Preview</h3>
			<button type="button" class="na-modal-close-btn" data-modal="details" aria-label="Close modal">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div id="na-note-details-body" class="na-modal-body">
			<!-- Loaded dynamically via AJAX -->
		</div>
	</div>
</div>
