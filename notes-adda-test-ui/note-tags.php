<?php
require_once '/var/www/html/wordpress/wp-load.php';

$action_result = null;
$error_result  = null;

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['action'] ) ) {
	$action = $_POST['action'];

	if ( $action === 'get_note_tags' ) {
		$note_id = isset( $_POST['note_id'] ) ? $_POST['note_id'] : '';
		$res     = Notes_Adda_Note_Tags::get_tags( $note_id );

		if ( is_wp_error( $res ) ) {
			$error_result = array(
				'code'    => $res->get_error_code(),
				'message' => $res->get_error_message(),
			);
		} else {
			$action_result = $res;
		}
	} elseif ( $action === 'set_note_tags' ) {
		$note_id = isset( $_POST['note_id'] ) ? $_POST['note_id'] : '';

		$tag1_name = isset( $_POST['tag1_name'] ) ? trim( $_POST['tag1_name'] ) : '';
		$tag1_type = isset( $_POST['tag1_type'] ) ? trim( $_POST['tag1_type'] ) : 'other';

		$tag2_name = isset( $_POST['tag2_name'] ) ? trim( $_POST['tag2_name'] ) : '';
		$tag2_type = isset( $_POST['tag2_type'] ) ? trim( $_POST['tag2_type'] ) : 'other';

		$tags = array();
		if ( '' !== $tag1_name ) {
			$tags[] = array(
				'name' => $tag1_name,
				'type' => $tag1_type,
			);
		}
		if ( '' !== $tag2_name ) {
			$tags[] = array(
				'name' => $tag2_name,
				'type' => $tag2_type,
			);
		}

		$res = Notes_Adda_Note_Tags::set_tags( $note_id, $tags );

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
	<title>Test - Note Tag Manager</title>
</head>
<body>
	<p><a href="index.php">&laquo; Back to Index</a></p>
	<h1>Note Tag Link Manager Test Page (Notes_Adda_Note_Tags)</h1>
	<hr>

	<h2>1. Get Note Tags</h2>
	<form method="POST" action="note-tags.php">
		<input type="hidden" name="action" value="get_note_tags">
		<label>Note ID:</label><br>
		<input type="number" name="note_id" value="1000001" required><br><br>
		<button type="submit">Get Attached Tags</button>
	</form>

	<hr>

	<h2>2. Set Note Tags</h2>
	<form method="POST" action="note-tags.php">
		<input type="hidden" name="action" value="set_note_tags">
		
		<label>Note ID:</label><br>
		<input type="number" name="note_id" value="1000001" required><br><br>

		<fieldset>
			<legend>Tag 1</legend>
			<label>Name:</label>
			<input type="text" name="tag1_name" value="Sem 7">
			<label>Type:</label>
			<select name="tag1_type">
				<option value="sem" selected>sem</option>
				<option value="batch">batch</option>
				<option value="subject">subject</option>
				<option value="other">other</option>
			</select>
		</fieldset>
		<br>

		<fieldset>
			<legend>Tag 2</legend>
			<label>Name:</label>
			<input type="text" name="tag2_name" value="Computer Science">
			<label>Type:</label>
			<select name="tag2_type">
				<option value="sem">sem</option>
				<option value="batch">batch</option>
				<option value="subject" selected>subject</option>
				<option value="other">other</option>
			</select>
		</fieldset>
		<br>

		<button type="submit">Set Note Tags</button>
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
