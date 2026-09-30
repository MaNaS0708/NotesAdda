<?php
require_once '/var/www/html/wordpress/wp-load.php';

$action_result = null;
$error_result  = null;

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['action'] ) ) {
	$action = $_POST['action'];

	if ( $action === 'create_note' ) {
		$owner_id       = isset( $_POST['owner_id'] ) ? $_POST['owner_id'] : '';
		$title          = isset( $_POST['title'] ) ? $_POST['title'] : '';
		$subject        = isset( $_POST['subject'] ) ? $_POST['subject'] : '';
		$chapter        = isset( $_POST['chapter'] ) ? $_POST['chapter'] : '';
		$description    = isset( $_POST['description'] ) ? $_POST['description'] : '';
		$file_url       = isset( $_POST['file_url'] ) ? $_POST['file_url'] : '';
		$file_id        = isset( $_POST['file_id'] ) ? $_POST['file_id'] : 0;
		$is_whole_notes = isset( $_POST['is_whole_notes'] ) ? 1 : 0;

		$data = array(
			'title'          => $title,
			'subject'        => $subject,
			'chapter'        => $chapter,
			'description'    => $description,
			'file_url'       => $file_url,
			'file_id'        => $file_id,
			'is_whole_notes' => $is_whole_notes,
		);

		$res = Notes_Adda_Notes::create( $owner_id, $data );

		if ( is_wp_error( $res ) ) {
			$error_result = array(
				'code'    => $res->get_error_code(),
				'message' => $res->get_error_message(),
			);
		} else {
			$action_result = $res;
		}
	} elseif ( $action === 'get_note_by_id' ) {
		$note_id = isset( $_POST['note_id'] ) ? $_POST['note_id'] : '';
		$res     = Notes_Adda_Notes::get_by_id( $note_id );

		if ( is_wp_error( $res ) ) {
			$error_result = array(
				'code'    => $res->get_error_code(),
				'message' => $res->get_error_message(),
			);
		} else {
			$action_result = $res;
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Test - Note Manager</title>
</head>
<body>
	<p><a href="index.php">&laquo; Back to Index</a></p>
	<h1>Note Manager Test Page (Notes_Adda_Notes)</h1>
	<hr>

	<h2>1. Create Note</h2>
	<form method="POST" action="notes.php">
		<input type="hidden" name="action" value="create_note">
		
		<label>Owner WP User ID (*):</label><br>
		<input type="number" name="owner_id" value="1" required><br><br>

		<label>Title (*):</label><br>
		<input type="text" name="title" value="Database Management Systems" required><br><br>

		<label>Subject (*):</label><br>
		<input type="text" name="subject" value="Computer Science" required><br><br>

		<label>Chapter:</label><br>
		<input type="text" name="chapter" value="Normalization & Relational Algebra"><br><br>

		<label>Description:</label><br>
		<textarea name="description" rows="3" cols="40">Complete lecture notes covering 1NF to 3NF.</textarea><br><br>

		<label>File URL (*):</label><br>
		<input type="text" name="file_url" value="http://localhost/wordpress/wp-content/uploads/dbms.pdf" required><br><br>

		<label>File ID:</label><br>
		<input type="number" name="file_id" value="0"><br><br>

		<label>Is Whole Notes?</label>
		<input type="checkbox" name="is_whole_notes" value="1" checked><br><br>

		<button type="submit">Create Note</button>
	</form>

	<hr>

	<h2>2. Get Note by ID</h2>
	<form method="POST" action="notes.php">
		<input type="hidden" name="action" value="get_note_by_id">
		<label>Note ID:</label><br>
		<input type="number" name="note_id" value="1000001" required><br><br>
		<button type="submit">Fetch Note</button>
	</form>

	<hr>
	<h2>Raw Execution Result</h2>

	<?php if ( $error_result ) : ?>
		<div style="background-color: #ffcccc; padding: 10px; border: 1px solid red;">
			<h3>Status: ERROR (WP_Error)</h3>
			<p><strong>Code:</strong> <?php echo htmlspecialchars( $error_result['code'] ); ?></p>
			<p><strong>Message:</strong> <?php echo htmlspecialchars( $error_result['message'] ); ?></p>
		</div>
	<?php elseif ( null !== $action_result ) : ?>
		<div style="background-color: #ccffcc; padding: 10px; border: 1px solid green;">
			<h3>Status: SUCCESS</h3>
			<pre><?php print_r( $action_result ); ?></pre>
		</div>
	<?php else : ?>
		<p>No action submitted yet.</p>
	<?php endif; ?>

</body>
</html>
