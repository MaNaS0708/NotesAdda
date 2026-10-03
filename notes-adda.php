<?php
/**
 * Plugin Name: Notes Adda
 * Description: Student notes sharing application.
 * Version: 0.1.2
 * Author: Manas
 * Text Domain: notes-adda
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOTES_ADDA_VERSION', '0.1.2' );
define( 'NOTES_ADDA_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOTES_ADDA_URL', plugin_dir_url( __FILE__ ) );

require_once NOTES_ADDA_PATH . 'database/class-notes-adda-activator.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-tags.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-user-profile.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-notes.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-note-tags.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-note-query.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-likes.php';
require_once NOTES_ADDA_PATH . 'database/class-notes-adda-reports.php';
require_once NOTES_ADDA_PATH . 'ajax/class-notes-adda-ajax.php';
require_once NOTES_ADDA_PATH . 'ui/class-notes-adda-frontend.php';
require_once NOTES_ADDA_PATH . 'ajax/class-notes-adda-uploads.php';
require_once NOTES_ADDA_PATH . 'ajax/class-notes-adda-auth.php';
register_activation_hook( __FILE__, array( 'Notes_Adda_Activator', 'activate' ) );
