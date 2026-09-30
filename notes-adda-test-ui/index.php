<?php
require_once '/var/www/html/wordpress/wp-load.php';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Notes Adda Backend Test UI</title>
</head>
<body>
	<h1>Notes Adda - Backend Test Interface</h1>
	<p>Temporary plain HTML test frontend to manually test backend PHP classes.</p>
	<hr>

	<h2>Available Feature Test Pages</h2>
	<ul>
		<li><a href="tags.php">1. Tag Manager Test Page (Notes_Adda_Tags)</a></li>
		<li><a href="user-profile.php">2. User Profile Manager Test Page (Notes_Adda_User_Profile)</a></li>
		<li><a href="notes.php">3. Note Manager Test Page (Notes_Adda_Notes)</a></li>
		<li><a href="note-tags.php">4. Note Tag Link Manager Test Page (Notes_Adda_Note_Tags)</a></li>
		<li><a href="note-query.php">5. Note Search &amp; Query Test Page (Notes_Adda_Note_Query)</a></li>
	</ul>
	<hr>
	<p><small>Environment: WordPress active at <code>/var/www/html/wordpress</code></small></p>
</body>
</html>
