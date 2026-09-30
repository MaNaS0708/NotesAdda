<?php
require_once '/var/www/html/wordpress/wp-load.php';

$action_result = null;
$error_result  = null;

if ( $_SERVER['REQUEST_METHOD'] === 'GET' && isset( $_GET['action'] ) && $_GET['action'] === 'query_notes' ) {
	$args = array();

	if ( ! empty( $_GET['search'] ) ) {
		$args['search'] = $_GET['search'];
	}
	if ( ! empty( $_GET['owner_id'] ) ) {
		$args['owner_id'] = $_GET['owner_id'];
	}
	if ( ! empty( $_GET['tag_id'] ) ) {
		$args['tag_id'] = $_GET['tag_id'];
	}
	if ( ! empty( $_GET['tag_slug'] ) ) {
		$args['tag_slug'] = $_GET['tag_slug'];
	}
	if ( ! empty( $_GET['subject'] ) ) {
		$args['subject'] = $_GET['subject'];
	}
	if ( isset( $_GET['is_whole_notes'] ) && $_GET['is_whole_notes'] !== '' ) {
		$args['is_whole_notes'] = $_GET['is_whole_notes'];
	}
	if ( ! empty( $_GET['page'] ) ) {
		$args['page'] = $_GET['page'];
	}
	if ( ! empty( $_GET['per_page'] ) ) {
		$args['per_page'] = $_GET['per_page'];
	}
	if ( ! empty( $_GET['orderby'] ) ) {
		$args['orderby'] = $_GET['orderby'];
	}
	if ( ! empty( $_GET['order'] ) ) {
		$args['order'] = $_GET['order'];
	}

	$res = Notes_Adda_Note_Query::get_notes( $args );

	if ( is_wp_error( $res ) ) {
		$error_result = array(
			'code'    => $res->get_error_code(),
			'message' => $res->get_error_message(),
		);
	} else {
		$action_result = $res;
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Test - Note Search &amp; Query</title>
</head>
<body>
	<p><a href="index.php">&laquo; Back to Index</a></p>
	<h1>Note Search &amp; Query Test Page (Notes_Adda_Note_Query)</h1>
	<hr>

	<h2>Filter &amp; Search Notes</h2>
	<form method="GET" action="note-query.php">
		<input type="hidden" name="action" value="query_notes">

		<label>Keyword Search (title/subject/chapter/description):</label><br>
		<input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"><br><br>

		<label>Owner WP User ID:</label><br>
		<input type="number" name="owner_id" value="<?php echo isset($_GET['owner_id']) ? htmlspecialchars($_GET['owner_id']) : ''; ?>"><br><br>

		<label>Tag ID:</label><br>
		<input type="number" name="tag_id" value="<?php echo isset($_GET['tag_id']) ? htmlspecialchars($_GET['tag_id']) : ''; ?>"><br><br>

		<label>Tag Slug:</label><br>
		<input type="text" name="tag_slug" value="<?php echo isset($_GET['tag_slug']) ? htmlspecialchars($_GET['tag_slug']) : ''; ?>"><br><br>

		<label>Subject (Exact):</label><br>
		<input type="text" name="subject" value="<?php echo isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : ''; ?>"><br><br>

		<label>Is Whole Notes?</label><br>
		<select name="is_whole_notes">
			<option value="">-- All --</option>
			<option value="1" <?php echo (isset($_GET['is_whole_notes']) && $_GET['is_whole_notes'] === '1') ? 'selected' : ''; ?>>Yes (1)</option>
			<option value="0" <?php echo (isset($_GET['is_whole_notes']) && $_GET['is_whole_notes'] === '0') ? 'selected' : ''; ?>>No (0)</option>
		</select><br><br>

		<label>Page:</label><br>
		<input type="number" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : '1'; ?>"><br><br>

		<label>Per Page (1-50):</label><br>
		<input type="number" name="per_page" value="<?php echo isset($_GET['per_page']) ? htmlspecialchars($_GET['per_page']) : '10'; ?>"><br><br>

		<label>Order By:</label><br>
		<select name="orderby">
			<option value="created_at" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] === 'created_at') ? 'selected' : ''; ?>>created_at</option>
			<option value="title" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] === 'title') ? 'selected' : ''; ?>>title</option>
			<option value="like_count" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] === 'like_count') ? 'selected' : ''; ?>>like_count</option>
			<option value="invalid_column" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] === 'invalid_column') ? 'selected' : ''; ?>>[TEST INVALID ORDERBY]</option>
		</select><br><br>

		<label>Order Direction:</label><br>
		<select name="order">
			<option value="DESC" <?php echo (isset($_GET['order']) && $_GET['order'] === 'DESC') ? 'selected' : ''; ?>>DESC</option>
			<option value="ASC" <?php echo (isset($_GET['order']) && $_GET['order'] === 'ASC') ? 'selected' : ''; ?>>ASC</option>
		</select><br><br>

		<button type="submit">Run Query</button>
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
			<p><strong>Total Matches:</strong> <?php echo $action_result['total']; ?> | 
			<strong>Current Page:</strong> <?php echo $action_result['page']; ?> / <?php echo $action_result['total_pages']; ?> | 
			<strong>Items Per Page:</strong> <?php echo $action_result['per_page']; ?></p>
			<pre><?php print_r( $action_result ); ?></pre>
		</div>
	<?php else : ?>
		<p>No query executed yet. Click "Run Query" above.</p>
	<?php endif; ?>

</body>
</html>
