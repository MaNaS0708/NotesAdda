<?php
/**
 * Notes Adda Partial: Central Library View
 * Displays community knowledge base, filters, search toolbar, cards grid, and pagination.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="na-view-library" class="na-view active" aria-labelledby="na-lib-heading">
	<div class="na-view-header">
		<div>
			<span class="na-eyebrow">Community knowledge base</span>
			<h2 id="na-lib-heading" class="na-view-title">Your study library, in one place.</h2>
			<p class="na-view-subtitle">Search reliable notes, study guides, and complete course material shared by your community.</p>
		</div>
		<button type="button" class="na-btn na-btn-primary na-open-create-btn" aria-label="Share notes">
			<span class="dashicons dashicons-upload"></span> <span>Share notes</span>
		</button>
	</div>

	<!-- Toolbar / Filters -->
	<div class="na-toolbar" role="search" aria-label="Filter library notes">
		<div class="na-search-box">
			<span class="dashicons dashicons-search na-search-icon" aria-hidden="true"></span>
			<input type="search" id="na-search-input" placeholder="Search by subject, topic, or title" class="na-input" aria-label="Search notes">
		</div>
		<div class="na-filters-row">
			<select id="na-subject-filter" class="na-select" aria-label="Filter by subject">
				<option value="">All Subjects</option>
			</select>
			<select id="na-sort-filter" class="na-select" aria-label="Sort notes">
				<option value="recent">Sort: Most Recent</option>
				<option value="popular">Sort: Most Liked</option>
			</select>
			<button type="button" id="na-apply-filters" class="na-btn na-btn-secondary" aria-label="Apply search filters">
				<span class="dashicons dashicons-search" aria-hidden="true"></span>
				<span>Search</span>
			</button>
			<button type="button" id="na-reset-filters" class="na-btn na-btn-ghost" style="display:none;" aria-label="Reset search filters">
				<span>Reset</span>
			</button>
		</div>
	</div>

	<div class="na-results-meta">
		<span id="na-library-result-count">Explore recently shared notes</span>
		<span class="na-results-meta-hint">Upload yours to help another student.</span>
	</div>

	<!-- Loading State -->
	<div id="na-library-loading" class="na-state-box na-loading-box" style="display:none;" aria-live="polite">
		<span class="dashicons dashicons-update na-spin na-state-icon" aria-hidden="true"></span>
		<p class="na-state-title">Loading notes...</p>
	</div>

	<!-- Empty State -->
	<div id="na-library-empty" class="na-state-box na-empty-box" style="display:none;">
		<div class="na-state-icon-wrap"><span class="dashicons dashicons-search na-state-icon" aria-hidden="true"></span></div>
		<h3 class="na-state-title">No notes found</h3>
		<p class="na-state-desc">No notes match your filter criteria or the library is empty.</p>
		<button type="button" class="na-btn na-btn-primary na-open-create-btn">
			<span class="dashicons dashicons-plus" aria-hidden="true"></span> <span>Upload First Note</span>
		</button>
	</div>

	<!-- Note Grid -->
	<div id="na-library-results" class="na-cards-grid" aria-live="polite"></div>

	<!-- Pagination -->
	<div id="na-library-pagination" class="na-pagination-container"></div>
</section>
