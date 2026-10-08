<?php
/**
 * Notes Adda Partial: My Notes View
 * Displays notes contributed by the logged in student/author with review status indicators.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="na-view-my-notes" class="na-view" style="display:none;" aria-labelledby="na-my-notes-heading">
	<div class="na-view-header">
		<div>
			<span class="na-eyebrow">Your contribution</span>
			<h2 id="na-my-notes-heading" class="na-view-title">My Notes</h2>
			<p class="na-view-subtitle">Manage the material you have shared with the student community.</p>
		</div>
		<button type="button" class="na-btn na-btn-primary na-open-create-btn" aria-label="Upload note">
			<span class="dashicons dashicons-plus" aria-hidden="true"></span> <span>Upload Note</span>
		</button>
	</div>

	<div class="na-results-meta na-results-meta-my">
		<span id="na-my-notes-result-count">Your published material</span>
		<span class="na-results-meta-hint">Keep titles and descriptions clear so students can find them.</span>
	</div>

	<!-- Loading State -->
	<div id="na-my-notes-loading" class="na-state-box na-loading-box" style="display:none;" aria-live="polite">
		<span class="dashicons dashicons-update na-spin na-state-icon" aria-hidden="true"></span>
		<p class="na-state-title">Loading your notes...</p>
	</div>

	<!-- Empty State -->
	<div id="na-my-notes-empty" class="na-state-box na-empty-box" style="display:none;">
		<div class="na-state-icon-wrap"><span class="dashicons dashicons-portfolio na-state-icon" aria-hidden="true"></span></div>
		<h3 class="na-state-title">You haven't uploaded any notes yet</h3>
		<p class="na-state-desc">Share your lecture notes, summaries, or study guides with other students.</p>
		<button type="button" class="na-btn na-btn-primary na-open-create-btn">
			<span class="dashicons dashicons-plus" aria-hidden="true"></span> <span>Upload Your First Note</span>
		</button>
	</div>

	<!-- Note Grid -->
	<div id="na-my-notes-results" class="na-cards-grid" aria-live="polite"></div>

	<!-- Pagination -->
	<div id="na-my-notes-pagination" class="na-pagination-container"></div>
</section>
