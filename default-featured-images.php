<?php
/**
 * Plugin Name: Default Featured Images & Fallbacks
 * Plugin URI: https://macbay.digital
 * Description: Assign fallback featured images based on taxonomy terms with flexible priority rules and global defaults.
 * Version: 1.0.0
 * Author: Macbay Digital
 * Author URI: https://macbay.digital
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: default-featured-images
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'DFI_VERSION', '1.0.0' );
define( 'DFI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DFI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DFI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 */
class Default_Featured_Images {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once DFI_PLUGIN_DIR . 'includes/class-settings.php';
        require_once DFI_PLUGIN_DIR . 'includes/class-admin-page.php';
        require_once DFI_PLUGIN_DIR . 'includes/class-fallback-handler.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize components
        add_action( 'plugins_loaded', array( $this, 'init_components' ) );
        
        // Activation/Deactivation
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Initialize plugin components
     */
    public function init_components() {
        // Initialize settings
        DFI_Settings::get_instance();
        
        // Initialize admin page (only in admin)
        if ( is_admin() ) {
            DFI_Admin_Page::get_instance();
        }
        
        // Initialize fallback handler (frontend and admin)
        DFI_Fallback_Handler::get_instance();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'term_fallbacks' => array(),
            'global_fallback_enabled' => false,
            'global_fallback_id' => 0,
            'taxonomy_priority' => array(),
        );
        
        add_option( 'dfi_settings', $default_options );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function dfi_init() {
    return Default_Featured_Images::get_instance();
}

// Start the plugin
dfi_init();
