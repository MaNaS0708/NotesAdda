<?php
require_once '/var/www/html/wordpress/wp-load.php';

$action_result = null;
$error_result  = null;

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['action'] ) ) {
	if ( $_POST['action'] === 'get_or_create_tag' ) {
		$name = isset( $_POST['name'] ) ? $_POST['name'] : '';
		$type = isset( $_POST['type'] ) ? $_POST['type'] : 'other';

		$res = Notes_Adda_Tags::get_or_create( $name, $type );
		if ( is_wp_error( $res ) ) {
			$error_result = array(
				'code'    => $res->get_error_code(),
				'message' => $res->get_error_message(),
			);
		} else {
			$action_result = array(
				'tag_id' => $res,
				'name'   => $name,
				'type'   => $type,
			);
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Test - Tag Manager</title>
</head>
<body>
	<p><a href="index.php">&laquo; Back to Index</a></p>
	<h1>Tag Manager Test Page (Notes_Adda_Tags)</h1>
	<hr>

	<h2>1. Get or Create Tag</h2>
	<form method="POST" action="tags.php">
		<input type="hidden" name="action" value="get_or_create_tag">
		
		<label>Tag Name:</label><br>
		<input type="text" name="name" value="Sem 7" required><br><br>

		<label>Tag Type:</label><br>
		<select name="type">
			<option value="sem">sem</option>
			<option value="batch">batch</option>
			<option value="subject">subject</option>
			<option value="other" selected>other</option>
		</select><br><br>

		<button type="submit">Submit Tag</button>
	</form>

	<hr>
	<h2>Raw Execution Result</h2>

	<?php if ( $error_result ) : ?>
		<div style="background-color: #ffcccc; padding: 10px; border: 1px solid red;">
			<h3>Status: ERROR (WP_Error)</h3>
			<p><strong>Code:</strong> <?php echo htmlspecialchars( $error_result['code'] ); ?></p>
			<p><strong>Message:</strong> <?php echo htmlspecialchars( $error_result['message'] ); ?></p>
		</div>
	<?php elseif ( $action_result ) : ?>
		<div style="background-color: #ccffcc; padding: 10px; border: 1px solid green;">
			<h3>Status: SUCCESS</h3>
			<pre><?php print_r( $action_result ); ?></pre>
		</div>
	<?php else : ?>
		<p>No action submitted yet.</p>
	<?php endif; ?>

</body>
</html>
