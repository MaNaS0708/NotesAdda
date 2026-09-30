<?php
require_once '/var/www/html/wordpress/wp-load.php';

$action_result = null;
$error_result  = null;

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['action'] ) ) {
	$action = $_POST['action'];

	if ( $action === 'get_by_user_id' ) {
		$user_id = isset( $_POST['user_id'] ) ? $_POST['user_id'] : '';
		$res     = Notes_Adda_User_Profile::get_by_user_id( $user_id );

		if ( is_wp_error( $res ) ) {
			$error_result = array(
				'code'    => $res->get_error_code(),
				'message' => $res->get_error_message(),
			);
		} else {
			$action_result = $res;
		}
	} elseif ( $action === 'get_or_create_profile' ) {
		$user_id = isset( $_POST['user_id'] ) ? $_POST['user_id'] : '';
		$college = isset( $_POST['college'] ) ? $_POST['college'] : '';
		$bio     = isset( $_POST['bio'] ) ? $_POST['bio'] : '';

		$data = array(
			'college' => $college,
			'bio'     => $bio,
		);

		$res = Notes_Adda_User_Profile::get_or_create( $user_id, $data );

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
	<title>Test - User Profile Manager</title>
</head>
<body>
	<p><a href="index.php">&laquo; Back to Index</a></p>
	<h1>User Profile Manager Test Page (Notes_Adda_User_Profile)</h1>
	<hr>

	<h2>1. Get Profile by WP User ID</h2>
	<form method="POST" action="user-profile.php">
		<input type="hidden" name="action" value="get_by_user_id">
		<label>WordPress User ID:</label><br>
		<input type="number" name="user_id" value="1" required><br><br>
		<button type="submit">Fetch Profile</button>
	</form>

	<hr>

	<h2>2. Get or Create Profile</h2>
	<form method="POST" action="user-profile.php">
		<input type="hidden" name="action" value="get_or_create_profile">
		
		<label>WordPress User ID:</label><br>
		<input type="number" name="user_id" value="1" required><br><br>

		<label>College:</label><br>
		<input type="text" name="college" value="IIT Delhi"><br><br>

		<label>Bio:</label><br>
		<textarea name="bio" rows="3" cols="40">Computer Science Student</textarea><br><br>

		<button type="submit">Get or Create Profile</button>
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
