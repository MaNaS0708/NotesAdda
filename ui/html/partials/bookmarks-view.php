<?php
/**
 * Notes Adda Partial: Bookmarks View
 * Displays saved notes for quick retrieval and study preparation.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="na-view-bookmarks" class="na-view" style="display:none;" aria-labelledby="na-bookmarks-heading">
	<div class="na-view-header">
		<div>
			<span class="na-eyebrow">Saved for quick study</span>
			<h2 id="na-bookmarks-heading" class="na-view-title">My Bookmarks</h2>
			<p class="na-view-subtitle">Access your collection of saved study guides, notes, and full-course materials.</p>
		</div>
		<button type="button" class="na-btn na-btn-secondary" id="na-bookmarks-browse-btn" aria-label="Browse study library">
			<span class="dashicons dashicons-books" aria-hidden="true"></span> <span>Browse Library</span>
		</button>
	</div>

	<div class="na-results-meta">
		<span id="na-bookmarks-result-count">Your saved notes</span>
		<span class="na-results-meta-hint">Click the bookmark icon on any note card to save or remove.</span>
	</div>

	<!-- Loading State -->
	<div id="na-bookmarks-loading" class="na-state-box na-loading-box" style="display:none;" aria-live="polite">
		<span class="dashicons dashicons-update na-spin na-state-icon" aria-hidden="true"></span>
		<p class="na-state-title">Loading bookmarks...</p>
	</div>

	<!-- Empty State -->
	<div id="na-bookmarks-empty" class="na-state-box na-empty-box" style="display:none;">
		<div class="na-state-icon-wrap">
			<svg viewBox="0 0 24 24" class="na-svg-bookmark na-state-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
		</div>
		<h3 class="na-state-title">No saved notes yet</h3>
		<p class="na-state-desc">Save useful notes to find them quickly later.</p>
		<button type="button" class="na-btn na-btn-primary" id="na-empty-browse-btn">
			<span class="dashicons dashicons-books" aria-hidden="true"></span> <span>Browse Study Library</span>
		</button>
	</div>

	<!-- Bookmarks Grid -->
	<div id="na-bookmarks-results" class="na-cards-grid" aria-live="polite"></div>

	<!-- Pagination -->
	<div id="na-bookmarks-pagination" class="na-pagination-container"></div>
</section>
