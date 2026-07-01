<?php
/**
 * Plugin Name: Custom Contact Form
 * Description: A custom contact form with file upload, email notification, admin view, edit, and status management.
 * Version: 2.5
 * Author: Long Nguyen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'CCF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCF_VERSION', '2.5' );

// Load required classes
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-validator.php';
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-db.php';
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-mailer.php';
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-form.php';
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-admin.php';

// Initialize the plugin
add_action( 'plugins_loaded', 'ccf_init' );
function ccf_init() {
    CCF_DB::init();               
    CCF_Form::init();            
    if ( is_admin() ) {
        CCF_Admin::init();        
    }
}

// Activation hook for table creation
register_activation_hook( __FILE__, [ 'CCF_DB', 'create_table' ] );