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
	<?php wp_head(); ?>
    <style>
        /* Force reset of body margins for full screen */
        body.notes-adda-full-screen {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background: #1e1e24; /* Fallback dark background */
        }
        #wpadminbar { display: none !important; }
        html { margin-top: 0 !important; }
    </style>
</head>
<body <?php body_class( 'notes-adda-full-screen' ); ?>>
	<?php echo do_shortcode( '[notes_adda_app]' ); ?>
	<?php wp_footer(); ?>
</body>
</html>
