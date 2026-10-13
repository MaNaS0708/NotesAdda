<?php
/**
 * Notes Adda Partial: PDF Preview Modal
 * Responsive dialog with dark app chrome and isolated white document canvas.
 *
 * @package Notes_Adda
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- PDF Document Preview Modal -->
<div id="na-pdf-preview-modal" class="na-modal-overlay na-pdf-overlay" role="dialog" aria-modal="true" aria-labelledby="na-pdf-modal-title">
	<div class="na-modal-dialog na-modal-pdf">
		<div class="na-modal-header na-pdf-modal-header">
			<div class="na-pdf-modal-header-left">
				<span class="dashicons dashicons-pdf na-pdf-header-icon" aria-hidden="true"></span>
				<h3 id="na-pdf-modal-title" class="na-modal-title na-pdf-modal-title">Document Preview</h3>
			</div>
			<div class="na-pdf-modal-header-actions">
				<a href="#" id="na-pdf-open-newtab-btn" target="_blank" rel="noopener noreferrer" class="na-btn na-btn-sm na-btn-secondary" title="Open document in a new browser tab">
					<span class="dashicons dashicons-external" aria-hidden="true"></span>
					<span>Open in new tab</span>
				</a>
				<button type="button" class="na-modal-close-btn" data-modal="pdf-preview" aria-label="Close PDF preview">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
		</div>
		<div class="na-pdf-viewport-container">
			<iframe id="na-pdf-iframe" src="" class="na-pdf-iframe" title="PDF Document Viewport"></iframe>
		</div>
	</div>
</div>
