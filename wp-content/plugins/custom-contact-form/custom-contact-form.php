<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CCF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCF_VERSION', '2.5' );

require_once CCF_PLUGIN_DIR . 'includes/class-ccf-validator.php';
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-db.php';
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-mailer.php';
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-form.php';
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-admin.php';
require_once CCF_PLUGIN_DIR . 'includes/class-ccf-list-table.php';

add_action( 'plugins_loaded', 'ccf_init' );
function ccf_init() {
    CCF_DB::init();
    CCF_Form::init();
    if ( is_admin() ) {
        CCF_Admin::init();
    }
}

register_activation_hook( __FILE__, [ 'CCF_DB', 'create_table' ] );