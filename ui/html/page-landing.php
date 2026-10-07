<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
	<?php wp_head(); ?>
	<style>
		body.notes-adda-landing-body {
			margin: 0;
			padding: 0;
			overflow-x: hidden;
			background: #fcfaf8;
		}
		#wpadminbar { display: none !important; }
		html { margin-top: 0 !important; }
	</style>
</head>
<body <?php body_class( 'notes-adda-landing-body' ); ?>>
	<?php echo do_shortcode( '[notes_adda_landing]' ); ?>
	<?php wp_footer(); ?>
</body>
</html>
