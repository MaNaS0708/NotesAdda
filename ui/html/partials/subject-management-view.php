<?php
/**
 * Notes Adda Partial: Subject Management View
 * Admin taxonomy catalog editor for creating and organizing standard course subjects.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="na-view-subjects" class="na-view" style="display:none;" aria-labelledby="na-subjects-heading">
	<div class="na-view-header">
		<div>
			<span class="na-eyebrow">Taxonomy Management</span>
			<h2 id="na-subjects-heading" class="na-view-title">Subject Management</h2>
			<p class="na-view-subtitle">Create and organize standard subjects used by students when uploading notes.</p>
		</div>
	</div>

	<!-- Add Subject Form Card -->
	<div class="na-admin-card na-subject-create-card">
		<h3 class="na-admin-card-title">Add New Subject</h3>
		<p class="na-admin-card-subtitle">Subject names must be unique and will be available to all students in upload and filter dropdowns.</p>
		
		<form id="na-add-subject-form" class="na-inline-form">
			<div class="na-input-wrapper na-col">
				<span class="na-input-icon dashicons dashicons-tag" aria-hidden="true"></span>
				<input type="text" id="na-new-subject-name" name="name" class="na-input" placeholder="e.g. Computer Science, Neuroscience, Thermodynamics" required>
			</div>
			<button type="submit" class="na-btn na-btn-primary" id="na-add-subject-btn">
				<span class="na-btn-text">Add Subject</span>
				<span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
			</button>
		</form>
		<div id="na-subject-form-msg" class="na-form-message" style="margin-top:12px;"></div>
	</div>

	<div class="na-results-meta">
		<span id="na-subjects-count">Current Subjects</span>
		<span class="na-results-meta-hint">Alphabetical database list. Subjects with existing notes cannot be deleted.</span>
	</div>

	<!-- Loading State -->
	<div id="na-subjects-loading" class="na-state-box na-loading-box" style="display:none;" aria-live="polite">
		<span class="dashicons dashicons-update na-spin na-state-icon" aria-hidden="true"></span>
		<p class="na-state-title">Loading subjects...</p>
	</div>

	<!-- Empty State -->
	<div id="na-subjects-empty" class="na-state-box na-empty-box" style="display:none;">
		<div class="na-state-icon-wrap"><span class="dashicons dashicons-tag na-state-icon" aria-hidden="true"></span></div>
		<h3 class="na-state-title">No subjects defined yet</h3>
		<p class="na-state-desc">Use the form above to add the first study subject.</p>
	</div>

	<!-- Subjects Grid / Table -->
	<div id="na-subjects-results" class="na-subjects-grid" aria-live="polite"></div>
</section>
