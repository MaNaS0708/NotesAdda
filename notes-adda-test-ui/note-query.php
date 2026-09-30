<?php
require_once '/var/www/html/wordpress/wp-load.php';

$action_result = null;
$error_result  = null;
$search_query  = isset( $_GET['search'] ) ? trim( $_GET['search'] ) : '';
$sort_option   = isset( $_GET['sort'] ) ? trim( $_GET['sort'] ) : 'recent';
$page          = isset( $_GET['page'] ) ? max( 1, (int) $_GET['page'] ) : 1;

// Map sort option to backend orderby and order parameters
$orderby = 'created_at';
$order   = 'DESC';

switch ( $sort_option ) {
	case 'likes':
		$orderby = 'like_count';
		$order   = 'DESC';
		break;
	case 'oldest':
		$orderby = 'created_at';
		$order   = 'ASC';
		break;
	case 'title_asc':
		$orderby = 'title';
		$order   = 'ASC';
		break;
	case 'title_desc':
		$orderby = 'title';
		$order   = 'DESC';
		break;
	case 'recent':
	default:
		$orderby = 'created_at';
		$order   = 'DESC';
		break;
}

if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
	$args = array(
		'page'     => $page,
		'per_page' => 10,
		'orderby'  => $orderby,
		'order'    => $order,
	);

	if ( '' !== $search_query ) {
		// 1. Check if search query matches an existing tag slug
		$normalized_slug = str_replace( '-', '', sanitize_title( $search_query ) );
		
		global $wpdb;
		$tag_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}notes_adda_tags WHERE slug = %s",
				$normalized_slug
			)
		);

		if ( $tag_exists ) {
			// Query notes assigned to this tag
			$args['tag_slug'] = $normalized_slug;
			$res = Notes_Adda_Note_Query::get_notes( $args );

			// Fallback to keyword search if tag query returned 0 items
			if ( ! is_wp_error( $res ) && 0 === $res['total'] ) {
				unset( $args['tag_slug'] );
				$args['search'] = $search_query;
				$res = Notes_Adda_Note_Query::get_notes( $args );
			}
		} else {
			// Query notes by keyword (title, subject, chapter, description)
			$args['search'] = $search_query;
			$res = Notes_Adda_Note_Query::get_notes( $args );
		}
	} else {
		// Unfiltered search (all notes)
		$res = Notes_Adda_Note_Query::get_notes( $args );
	}

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
	<title>Note Search &amp; Filter - Notes Adda</title>
</head>
<body>
	<p><a href="index.php">&laquo; Back to Index</a></p>
	<h1>Note Search &amp; Filter Manager</h1>
	<p>Type a tag name (e.g. <code>Sem 7</code>, <code>Physics</code>) or keyword in the single search bar below to filter notes, and select a sorting order.</p>
	<hr>

	<!-- Search & Sort Form -->
	<form method="GET" action="note-query.php">
		<p>
			<label><strong>Search Bar (Tags or Keywords):</strong></label><br>
			<input type="text" name="search" value="<?php echo htmlspecialchars( $search_query ); ?>" placeholder="e.g. Sem 7, Physics, Algorithms..." style="width: 350px; padding: 5px;">
		</p>

		<p>
			<label><strong>Sort Notes By:</strong></label><br>
			<select name="sort" style="padding: 5px;">
				<option value="recent" <?php selected( $sort_option, 'recent' ); ?>>Recent / Latest</option>
				<option value="likes" <?php selected( $sort_option, 'likes' ); ?>>Most Liked</option>
				<option value="oldest" <?php selected( $sort_option, 'oldest' ); ?>>Oldest First</option>
				<option value="title_asc" <?php selected( $sort_option, 'title_asc' ); ?>>Alphabetical (A-Z)</option>
				<option value="title_desc" <?php selected( $sort_option, 'title_desc' ); ?>>Alphabetical (Z-A)</option>
			</select>
		</p>

		<p>
			<button type="submit" style="padding: 6px 14px;">Search Notes</button>
			<?php if ( '' !== $search_query || 'recent' !== $sort_option ) : ?>
				<a href="note-query.php" style="margin-left: 10px;">Clear Search</a>
			<?php endif; ?>
		</p>
	</form>

	<hr>

	<!-- Execution Output & Results -->
	<h2>Search Results</h2>

	<?php if ( $error_result ) : ?>
		<div style="background-color: #ffcccc; padding: 10px; border: 1px solid red;">
			<h3>Status: ERROR (WP_Error)</h3>
			<p><strong>Code:</strong> <?php echo htmlspecialchars( $error_result['code'] ); ?></p>
			<p><strong>Message:</strong> <?php echo htmlspecialchars( $error_result['message'] ); ?></p>
		</div>
	<?php elseif ( null !== $action_result ) : ?>
		<p>
			<strong>Total Matching Notes Found:</strong> <?php echo (int) $action_result['total']; ?> | 
			<strong>Current Page:</strong> <?php echo (int) $action_result['page']; ?> of <?php echo (int) $action_result['total_pages']; ?> | 
			<strong>Sorted By:</strong> <?php echo htmlspecialchars( $sort_option ); ?>
		</p>

		<?php if ( empty( $action_result['items'] ) ) : ?>
			<p><em>No notes found matching your search. Try searching for another tag or keyword.</em></p>
		<?php else : ?>
			<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="background-color: #f0f0f0;">
						<th>ID</th>
						<th>Title</th>
						<th>Subject</th>
						<th>Chapter</th>
						<th>Description</th>
						<th>Attached Tags</th>
						<th>Likes</th>
						<th>Created At</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $action_result['items'] as $note ) : ?>
						<?php 
						// Fetch attached tags for display
						$attached_tags = Notes_Adda_Note_Tags::get_tags( $note->id );
						$tag_labels    = array();
						if ( is_array( $attached_tags ) ) {
							foreach ( $attached_tags as $t ) {
								$tag_labels[] = htmlspecialchars( $t->name . ' (' . $t->type . ')' );
							}
						}
						?>
						<tr>
							<td><?php echo htmlspecialchars( $note->id ); ?></td>
							<td><strong><?php echo htmlspecialchars( $note->title ); ?></strong></td>
							<td><?php echo htmlspecialchars( $note->subject ); ?></td>
							<td><?php echo htmlspecialchars( $note->chapter ); ?></td>
							<td><?php echo htmlspecialchars( $note->description ); ?></td>
							<td><?php echo ! empty( $tag_labels ) ? implode( ', ', $tag_labels ) : '<em>None</em>'; ?></td>
							<td><?php echo htmlspecialchars( $note->like_count ); ?></td>
							<td><?php echo htmlspecialchars( $note->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<!-- Raw Execution Data -->
		<details style="margin-top: 20px;">
			<summary>View Raw Output Object</summary>
			<pre><?php print_r( $action_result ); ?></pre>
		</details>
	<?php endif; ?>

</body>
</html>
