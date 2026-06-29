<?php
/**
 * Astra functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define Constants
 */
define( 'ASTRA_THEME_VERSION', '4.13.4' );
define( 'ASTRA_THEME_SETTINGS', 'astra-settings' );
define( 'ASTRA_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'ASTRA_THEME_URI', trailingslashit( esc_url( get_template_directory_uri() ) ) );
define( 'ASTRA_THEME_ORG_VERSION', file_exists( ASTRA_THEME_DIR . 'inc/w-org-version.php' ) );

/**
 * Minimum Version requirement of the Astra Pro addon.
 * This constant will be used to display the notice asking user to update the Astra addon to the version defined below.
 */
define( 'ASTRA_EXT_MIN_VER', '4.12.0' );

/**
 * Load in-house compatibility.
 */
if ( ASTRA_THEME_ORG_VERSION ) {
	require_once ASTRA_THEME_DIR . 'inc/w-org-version.php';
}

/**
 * Setup helper functions of Astra.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-theme-options.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-theme-strings.php';
require_once ASTRA_THEME_DIR . 'inc/core/common-functions.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-icons.php';

define( 'ASTRA_WEBSITE_BASE_URL', 'https://wpastra.com' );

/**
 * Update theme
 */
require_once ASTRA_THEME_DIR . 'inc/theme-update/astra-update-functions.php';
require_once ASTRA_THEME_DIR . 'inc/theme-update/class-astra-theme-background-updater.php';

/**
 * Fonts Files
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-font-families.php';
if ( is_admin() ) {
	require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts-data.php';
}

require_once ASTRA_THEME_DIR . 'inc/lib/webfont/class-astra-webfont-loader.php';
require_once ASTRA_THEME_DIR . 'inc/lib/docs/class-astra-docs-loader.php';
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts.php';

require_once ASTRA_THEME_DIR . 'inc/dynamic-css/custom-menu-old-header.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/container-layouts.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/astra-icons.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-walker-page.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-enqueue-scripts.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-gutenberg-editor-css.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-wp-editor-css.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-command-palette.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/block-editor-compatibility.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/inline-on-mobile.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/content-background.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/dark-mode.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-dynamic-css.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-global-palette.php';

// Enable NPS Survey only if the starter templates version is < 4.3.7 or > 4.4.4 to prevent fatal error.
if ( ! defined( 'ASTRA_SITES_VER' ) || version_compare( ASTRA_SITES_VER, '4.3.7', '<' ) || version_compare( ASTRA_SITES_VER, '4.4.4', '>' ) ) {
	// NPS Survey Integration
	require_once ASTRA_THEME_DIR . 'inc/lib/class-astra-nps-notice.php';
	require_once ASTRA_THEME_DIR . 'inc/lib/class-astra-nps-survey.php';
}

/**
 * Custom template tags for this theme.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-attr.php';
require_once ASTRA_THEME_DIR . 'inc/template-tags.php';

require_once ASTRA_THEME_DIR . 'inc/widgets.php';
require_once ASTRA_THEME_DIR . 'inc/core/theme-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/admin-functions.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-memory-limit-notice.php';
require_once ASTRA_THEME_DIR . 'inc/core/sidebar-manager.php';

/**
 * Markup Functions
 */
require_once ASTRA_THEME_DIR . 'inc/markup-extras.php';
require_once ASTRA_THEME_DIR . 'inc/extras.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog-config.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog.php';
require_once ASTRA_THEME_DIR . 'inc/blog/single-blog.php';

/**
 * Markup Files
 */
require_once ASTRA_THEME_DIR . 'inc/template-parts.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-loop.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-mobile-header.php';

/**
 * Functions and definitions.
 */
require_once ASTRA_THEME_DIR . 'inc/class-astra-after-setup-theme.php';

// Required files.
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-helper.php';

require_once ASTRA_THEME_DIR . 'inc/schema/class-astra-schema.php';

/* Setup API */
require_once ASTRA_THEME_DIR . 'admin/includes/class-astra-learn.php';
require_once ASTRA_THEME_DIR . 'admin/includes/class-astra-api-init.php';

if ( is_admin() ) {
	/**
	 * Admin Menu Settings
	 */
	require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-settings.php';
	require_once ASTRA_THEME_DIR . 'admin/class-astra-admin-loader.php';
	require_once ASTRA_THEME_DIR . 'inc/lib/astra-notices/class-bsf-admin-notices.php';
}

/**
 * BSF Analytics.
 */
require_once ASTRA_THEME_DIR . 'admin/class-astra-bsf-analytics.php';

/**
 * Metabox additions.
 */
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-boxes.php';
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-box-operations.php';
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-elementor-editor-settings.php';

/**
 * Customizer additions.
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-customizer.php';

/**
 * Astra Modules.
 */
require_once ASTRA_THEME_DIR . 'inc/modules/posts-structures/class-astra-post-structures.php';
require_once ASTRA_THEME_DIR . 'inc/modules/related-posts/class-astra-related-posts.php';

/**
 * Compatibility
 */
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gutenberg.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-jetpack.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/woocommerce/class-astra-woocommerce.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/edd/class-astra-edd.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/lifterlms/class-astra-lifterlms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/learndash/class-astra-learndash.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bb-ultimate-addon.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-contact-form-7.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-visual-composer.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-site-origin.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gravity-forms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bne-flyout.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-ubermeu.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-divi-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-amp.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-yoast-seo.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/surecart/class-astra-surecart.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-starter-content.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-buddypress.php';
require_once ASTRA_THEME_DIR . 'inc/addons/transparent-header/class-astra-ext-transparent-header.php';
require_once ASTRA_THEME_DIR . 'inc/addons/breadcrumbs/class-astra-breadcrumbs.php';
require_once ASTRA_THEME_DIR . 'inc/addons/scroll-to-top/class-astra-scroll-to-top.php';
require_once ASTRA_THEME_DIR . 'inc/addons/heading-colors/class-astra-heading-colors.php';
require_once ASTRA_THEME_DIR . 'inc/builder/class-astra-builder-loader.php';

// Elementor Compatibility requires PHP 5.4 for namespaces.
if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor-pro.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-web-stories.php';
}

// Beaver Themer compatibility requires PHP 5.3 for anonymous functions.
if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-themer.php';
}

require_once ASTRA_THEME_DIR . 'inc/core/markup/class-astra-markup.php';

/**
 * Abilities API integration.
 */
require_once ASTRA_THEME_DIR . 'inc/abilities/bootstrap.php';

/**
 * Load deprecated functions
 */
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-filters.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-functions.php';

// Custom query to handle news filtering
add_action('pre_get_posts', 'custom_news_filter');
function custom_news_filter($query) {
    if (!is_admin() && $query->is_main_query() && (is_archive() || is_home())) {
        if (isset($_GET['cat']) && !empty($_GET['cat'])) {
            $query->set('cat', intval($_GET['cat']));
        }
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $query->set('s', sanitize_text_field($_GET['s']));
            $query->set('post_type', 'post');
        }
    }
}

// Enqueue Slick Slider for Homepage Sections
function enqueue_slick_slider() {
    // Only load on homepage
    if (is_front_page()) {
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
        wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
        wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);
        
        wp_add_inline_script('slick-js', '
            jQuery(document).ready(function($) {
                $(".hero-slider").slick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows: true,
                    dots: false,
                    infinite: true,
                    speed: 500,
                    fade: true,
                    cssEase: "linear",
                    autoplay: true,
                    autoplaySpeed: 5000,
                    pauseOnHover: true
                });

                $(".cars-grid").slick({
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    arrows: true,
                    dots: false,
                    infinite: true,
                    speed: 300,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 2,
                            }
                        },
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 1,
                                arrows: false,
                                dots: true,
                            }
                        }
                    ]
                });
            });
        ');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_slick_slider');

// ==================================================
// REGISTER CUSTOM POST TYPE: CAR
// ==================================================
function register_car_post_type() {
    $args = array(
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'cars'), 
        'supports' => array(
            'title',
            'editor',      
            'excerpt',     
            'thumbnail',   
        ),
        'labels' => array(
            'name' => 'Cars',
            'singular_name' => 'Car',
            'add_new' => 'Add New Car',
            'add_new_item' => 'Add New Car',
            'edit_item' => 'Edit Car',
            'view_item' => 'View Car',
            'search_items' => 'Search Cars',
            'not_found' => 'No cars found',
            'not_found_in_trash' => 'No cars found in Trash',
        ),
        'menu_icon' => 'dashicons-car', // Bonus: adds a car icon in the admin
    );
    register_post_type('car', $args);
}
add_action('init', 'register_car_post_type');

// ================================================== 
// REGISTER CUSTOM POST TYPE: TEAM
// ==================================================
function register_team_post_type() {
    register_post_type('team', array(
        'labels' => array(
            'name'               => 'Team Members',
            'singular_name'      => 'Team Member',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Team Member',
            'edit_item'          => 'Edit Team Member',
            'new_item'           => 'New Team Member',
            'view_item'          => 'View Team Member',
            'search_items'       => 'Search Team Members',
            'not_found'          => 'No team members found',
            'not_found_in_trash' => 'No team members found in Trash'
        ),
        'public'             => true,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-groups',
        'supports'           => array('title', 'thumbnail')
    ));
}
add_action('init', 'register_team_post_type');

// ==================================================
// TEAM MEMBER CUSTOM FIELD
// ==================================================
function team_position_meta_box() {
    add_meta_box(
        'team_position',
        'Position / Role',
        'team_position_meta_box_callback',
        'team',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'team_position_meta_box');

function team_position_meta_box_callback($post) {
    $value = get_post_meta($post->ID, '_team_position', true);
    echo '<input type="text" id="team_position_field" name="team_position_field" value="' . esc_attr($value) . '" style="width:100%;" />';
}

function save_team_position_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['team_position_field'])) {
        update_post_meta($post_id, '_team_position', sanitize_text_field($_POST['team_position_field']));
    }
}
add_action('save_post', 'save_team_position_meta');

// ==================================================
// REGISTER CUSTOM POST TYPE: BANNER
// ==================================================
function register_banner_post_type() {
    $args = array(
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'banners'),
        'publicly_queryable' => true,
        'supports' => array('title', 'excerpt', 'thumbnail', 'editor', 'custom-fields', 'page-attributes'),
        'labels' => array(
            'name' => 'Banners',
            'singular_name' => 'Banner',
            'add_new' => 'Add New Banner',
            'add_new_item' => 'Add New Banner',
            'edit_item' => 'Edit Banner',
            'view_item' => 'View Banner',
            'search_items' => 'Search Banners',
            'not_found' => 'No banners found',
            'not_found_in_trash' => 'No banners found in Trash',
        ),
        'menu_icon' => 'dashicons-images-alt2',
        'show_in_menu' => true,
        'exclude_from_search' => true,
    );
    register_post_type('banner', $args);
}
add_action('init', 'register_banner_post_type');

// ==================================================
// ADD PLACEHOLDER TO CUSTOM FIELDS PANEL
// ==================================================
function custom_fields_placeholder_script() {
    $screen = get_current_screen();
    if ($screen->post_type === 'banner') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#newmeta #metakeyselect option[value="newmeta"]').text('Enter meta key');
            $('#postcustom').before('<p><strong>Tip:</strong> Use <code>banner_button_url</code> for the URL, and <code>banner_button_text</code> for the button text.</p>');
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'custom_fields_placeholder_script');

// ==================================================
// ADD INSTRUCTION NOTE ON BANNER EDIT SCREEN
// ==================================================
function banner_admin_notice() {
    global $post;
    if ($post && $post->post_type === 'banner') {
        ?>
        <div class="notice notice-info" style="margin: 20px 0;">
            <p><strong>Important:</strong> Your Banner post <strong>must have a Featured Image</strong> set. The Featured Image will be used as the banner background on the homepage.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'banner_admin_notice');

// ==================================================
// PREVENT BANNER PUBLISH WITHOUT FEATURED IMAGE
// ==================================================
add_action('wp_insert_post', 'validate_banner_featured_image_on_save', 10, 3);
function validate_banner_featured_image_on_save($post_id, $post, $update) {
    if ($post->post_type !== 'banner') {
        return;
    }
    if ($post->post_status !== 'publish') {
        return;
    }
    if ( ! has_post_thumbnail( $post_id ) ) {
        remove_action('wp_insert_post', 'validate_banner_featured_image_on_save', 10, 3);
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft'
        ));
        set_transient('banner_publish_featured_error_' . $post_id, true, 60);
    }
}

// ==================================================
// SHOW ERROR MESSAGE IF PUBLISH FAILED
// ==================================================
add_action('admin_notices', function() {
    global $post;
    if ($post && $post->post_type === 'banner') {
        if (get_transient('banner_publish_featured_error_' . $post->ID)) {
            delete_transient('banner_publish_featured_error_' . $post->ID);
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Cannot Publish:</strong> A Banner post must have a <strong>Featured Image</strong> set. Please set a Featured Image in the sidebar and try again.</p>
            </div>
            <?php
        }
    }
});
// ==================================================
// BANNER BUTTON META BOX (Custom Fields for Button Text and URL)
// ==================================================
function banner_button_meta_box() {
    add_meta_box(
        'banner_button_meta_box',
        'Banner Button',
        'banner_button_meta_box_html',
        'banner',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'banner_button_meta_box' );

function banner_button_meta_box_html( $post ) {
    wp_nonce_field( 'banner_button_save', 'banner_button_nonce' );
    $button_text = get_post_meta( $post->ID, 'banner_button_text', true );
    $button_url = get_post_meta( $post->ID, 'banner_button_url', true );
    ?>
    <p>
        <label for="banner_button_text"><strong>Button Text</strong></label><br>
        <input type="text" id="banner_button_text" name="banner_button_text" value="<?php echo esc_attr( $button_text ); ?>" style="width:100%; padding:8px;" placeholder="e.g. Learn More" />
    </p>
    <p>
        <label for="banner_button_url"><strong>Button URL</strong></label><br>
        <input type="url" id="banner_button_url" name="banner_button_url" value="<?php echo esc_url( $button_url ); ?>" style="width:100%; padding:8px;" placeholder="https://example.com" />
    </p>
    <?php
}

function save_banner_button_meta_box( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST['banner_button_nonce'] ) || ! wp_verify_nonce( $_POST['banner_button_nonce'], 'banner_button_save' ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['banner_button_text'] ) ) {
        update_post_meta( $post_id, 'banner_button_text', sanitize_text_field( $_POST['banner_button_text'] ) );
    }
    if ( isset( $_POST['banner_button_url'] ) ) {
        update_post_meta( $post_id, 'banner_button_url', esc_url_raw( $_POST['banner_button_url'] ) );
    }
}
add_action( 'save_post_banner', 'save_banner_button_meta_box' );