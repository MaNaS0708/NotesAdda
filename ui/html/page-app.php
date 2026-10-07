<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	$auth_mode = isset( $_GET['auth'] ) ? sanitize_text_field( wp_unslash( $_GET['auth'] ) ) : '';
	$args      = array();
	if ( in_array( $auth_mode, array( 'login', 'register' ), true ) ) {
		$args['auth'] = $auth_mode;
	}
	$target_url = class_exists( 'Notes_Adda_Frontend' ) ? Notes_Adda_Frontend::get_landing_url( $args ) : home_url( '/' );
	wp_safe_redirect( $target_url );
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
            background: #070a0e;
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
