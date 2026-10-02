<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notes_Adda_Uploads {

	/**
	 * Upload a note file (PDF only) to the WordPress Media Library.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $file    $_FILES array for the single uploaded file.
	 * @return array|WP_Error Array with attachment details, or WP_Error on failure.
	 */
	public static function upload_note_file( $user_id, $file ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return new WP_Error( 'notes_adda_invalid_user', 'Invalid user ID.' );
		}

		if ( empty( $file ) || ! isset( $file['tmp_name'] ) || empty( $file['tmp_name'] ) ) {
			return new WP_Error( 'notes_adda_no_file', 'No file was provided.' );
		}

		// Check for PHP upload errors
		if ( isset( $file['error'] ) && $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'notes_adda_upload_error', 'PHP Upload Error: ' . $file['error'] );
		}

		// Maximum allowed size: 25 MB
		$max_size = 25 * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'notes_adda_file_too_large', 'File exceeds the maximum allowed size of 25 MB.' );
		}

		// Validate extension and MIME type
		$file_type = wp_check_filetype( $file['name'] );
		$ext       = strtolower( $file_type['ext'] );
		$type      = $file_type['type'];

		if ( 'pdf' !== $ext || 'application/pdf' !== $type ) {
			return new WP_Error( 'notes_adda_invalid_type', 'Only PDF files are allowed.' );
		}

		// Use WordPress functions to handle the upload
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$upload_overrides = array( 'test_form' => false );
		$movefile         = wp_handle_upload( $file, $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
			$filename = $movefile['file'];
			$url      = $movefile['url'];
			$type     = $movefile['type'];

			$attachment = array(
				'guid'           => $url,
				'post_mime_type' => $type,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_author'    => $user_id,
			);

			$attach_id = wp_insert_attachment( $attachment, $filename );

			if ( is_wp_error( $attach_id ) ) {
				return new WP_Error( 'notes_adda_attachment_failed', 'Failed to create attachment in Media Library.' );
			}

			// Generate metadata
			$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			return array(
				'file_id'   => $attach_id,
				'file_url'  => $url,
				'file_name' => basename( $filename ),
				'mime_type' => $type,
				'file_size' => $file['size'],
			);
		} else {
			return new WP_Error( 'notes_adda_upload_failed', 'Upload failed: ' . ( isset( $movefile['error'] ) ? $movefile['error'] : 'Unknown error' ) );
		}
	}
}
