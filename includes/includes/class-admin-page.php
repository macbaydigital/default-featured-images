<?php
/**
 * Admin Page Class
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DFI_Admin_Page {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Settings instance
     */
    private $settings;

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
        $this->settings = DFI_Settings::get_instance();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_dfi_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_dfi_remove_fallback', array( $this, 'ajax_remove_fallback' ) );
        add_action( 'wp_ajax_dfi_get_taxonomies', array( $this, 'ajax_get_taxonomies' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_media_page(
            __( 'Featured Images', 'default-featured-images' ),
            __( 'Featured Images', 'default-featured-images' ),
            'edit_posts', // Capability for editors and admins
            'default-featured-images',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on our plugin page
        if ( 'media_page_default-featured-images' !== $hook ) {
            return;
        }

        // Enqueue WordPress media library
        wp_enqueue_media();

        // Enqueue custom CSS
        wp_enqueue_style(
            'dfi-admin-style',
            DFI_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            DFI_VERSION
        );

        // Enqueue custom JS
        wp_enqueue_script(
            'dfi-admin-script',
            DFI_PLUGIN_URL . 'assets/js/admin-script.js',
            array( 'jquery', 'jquery-ui-sortable' ),
            DFI_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'dfi-admin-script', 'dfiAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'dfi_admin_nonce' ),
            'strings' => array(
                'selectImage' => __( 'Select Image', 'default-featured-images' ),
                'useImage' => __( 'Use this image', 'default-featured-images' ),
                'confirmRemove' => __( 'Are you sure you want to remove this fallback?', 'default-featured-images' ),
                'saved' => __( 'Settings saved successfully!', 'default-featured-images' ),
                'error' => __( 'An error occurred. Please try again.', 'default-featured-images' ),
            ),
        ) );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Check user capabilities
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'default-featured-images' ) );
        }

        $settings = $this->settings->get_settings();
        $taxonomies = $this->get_all_taxonomies();
        $term_fallbacks = $settings['term_fallbacks'];
        $taxonomy_priority = $settings['taxonomy_priority'];

        ?>
        <div class="wrap dfi-admin-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="dfi-notice" style="display: none;"></div>

            <form id="dfi-settings-form" method="post">
                <?php wp_nonce_field( 'dfi_save_settings', 'dfi_nonce' ); ?>

                <!-- Global Fallback Section -->
                <div class="dfi-section dfi-global-section">
                    <h2><?php _e( 'Global Default Featured Image', 'default-featured-images' ); ?></h2>
                    <p class="description">
                        <?php _e( 'This image will be used when no featured image is set and no taxonomy-specific fallback matches.', 'default-featured-images' ); ?>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="dfi-global-enabled">
                                    <?php _e( 'Enable Global Fallback', 'default-featured-images' ); ?>
                                </label>
                            </th>
                            <td>
                                <label class="dfi-toggle">
                                    <input type="checkbox" 
                                           id="dfi-global-enabled" 
                                           name="global_fallback_enabled" 
                                           value="1" 
                                           <?php checked( $settings['global_fallback_enabled'], true ); ?>>
                                    <span class="dfi-toggle-slider"></span>
                                </label>
                            </td>
                        </tr>
                        <tr class="dfi-global-image-row" <?php echo ! $settings['global_fallback_enabled'] ? 'style="display:none;"' : ''; ?>>
                            <th scope="row">
                                <label><?php _e( 'Global Fallback Image', 'default-featured-images' ); ?></label>
                            </th>
                            <td>
                                <div class="dfi-image-selector">
                                    <input type="hidden" 
                                           id="dfi-global-fallback-id" 
                                           name="global_fallback_id" 
                                           value="<?php echo esc_attr( $settings['global_fallback_id'] ); ?>">
                                    
                                    <div class="dfi-image-preview">
                                        <?php if ( $settings['global_fallback_id'] ) : 
                                            $image_url = wp_get_attachment_image_url( $settings['global_fallback_id'], 'thumbnail' );
                                            if ( $image_url ) : ?>
                                                <img src="<?php echo esc_url( $image_url ); ?>" alt="">
                                                <button type="button" class="dfi-remove-image" title="<?php esc_attr_e( 'Remove image', 'default-featured-images' ); ?>">&times;</button>
                                            <?php endif;
                                        endif; ?>
                                    </div>
                                    
                                    <button type="button" class="button dfi-select-image">
                                        <?php _e( 'Select Image', 'default-featured-images' ); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Taxonomy Priority Section -->
                <div class="dfi-section dfi-priority-section">
                    <h2><?php _e( 'Taxonomy Priority', 'default-featured-images' ); ?></h2>
                    <p class="description">
                        <?php _e( 'Drag to reorder. Higher taxonomies have priority when multiple terms match. Hierarchical terms (deeper levels) always take precedence within their taxonomy.', 'default-featured-images' ); ?>
                    </p>

                    <button type="button" class="button dfi-refresh-taxonomies">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e( 'Update Taxonomies', 'default-featured-images' ); ?>
                    </button>

                    <ul id="dfi-taxonomy-priority" class="dfi-sortable-list">
                        <?php 
                        // Show taxonomies in priority order, then remaining ones
                        $ordered_taxonomies = array();
                        $remaining_taxonomies = array_keys( $taxonomies );

                        if ( ! empty( $taxonomy_priority ) ) {
                            foreach ( $taxonomy_priority as $tax_name ) {
                                if ( isset( $taxonomies[ $tax_name ] ) ) {
                                    $ordered_taxonomies[] = $tax_name;
                                    $remaining_taxonomies = array_diff( $remaining_taxonomies, array( $tax_name ) );
                                }
                            }
                        }

                        $all_ordered = array_merge( $ordered_taxonomies, $remaining_taxonomies );

                        foreach ( $all_ordered as $tax_name ) :
                            $tax_obj = $taxonomies[ $tax_name ];
                        ?>
                            <li data-taxonomy="<?php echo esc_attr( $tax_name ); ?>">
                                <span class="dashicons dashicons-menu dfi-drag-handle"></span>
                                <span class="dfi-tax-label">
                                    <?php echo esc_html( $tax_obj->label ); ?>
                                    <small>(<?php echo esc_html( $tax_name ); ?>)</small>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Taxonomy Fallbacks Section -->
                <div class="dfi-section dfi-fallbacks-section">
                    <h2><?php _e( 'Taxonomy Term Fallbacks', 'default-featured-images' ); ?></h2>
                    <p class="description">
                        <?php _e( 'Assign fallback featured images to specific taxonomy terms.', 'default-featured-images' ); ?>
                    </p>

                    <div class="dfi-accordion-controls">
                        <button type="button" class="button dfi-expand-all">
                            <?php _e( 'Expand All', 'default-featured-images' ); ?>
                        </button>
                        <button type="button" class="button dfi-collapse-all">
                            <?php _e( 'Collapse All', 'default-featured-images' ); ?>
                        </button>
                    </div>

                    <div class="dfi-accordion">
                        <?php foreach ( $taxonomies as $tax_name => $tax_obj ) : 
                            $terms = get_terms( array(
                                'taxonomy' => $tax_name,
                                'hide_empty' => false,
                            ) );

                            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                                continue;
                            }
                        ?>
                            <div class="dfi-accordion-item" data-taxonomy="<?php echo esc_attr( $tax_name ); ?>">
                                <button type="button" class="dfi-accordion-header">
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    <strong><?php echo esc_html( $tax_obj->label ); ?></strong>
                                    <span class="dfi-term-count">(<?php echo count( $terms ); ?> <?php _e( 'terms', 'default-featured-images' ); ?>)</span>
                                </button>
                                
                                <div class="dfi-accordion-content">
                                    <table class="dfi-terms-table">
                                        <thead>
                                            <tr>
                                                <th><?php _e( 'Term', 'default-featured-images' ); ?></th>
                                                <th><?php _e( 'Fallback Image', 'default-featured-images' ); ?></th>
                                                <th><?php _e( 'Actions', 'default-featured-images' ); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ( $terms as $term ) : 
                                                $current_image_id = isset( $term_fallbacks[ $tax_name ][ $term->term_id ] ) 
                                                    ? $term_fallbacks[ $tax_name ][ $term->term_id ] 
                                                    : 0;
                                                $indent = '';
                                                if ( $term->parent ) {
                                                    $indent = str_repeat( '— ', $this->get_term_depth( $term->term_id, $tax_name ) );
                                                }
                                            ?>
                                                <tr data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
                                                    <td class="dfi-term-name">
                                                        <?php echo $indent . esc_html( $term->name ); ?>
                                                    </td>
                                                    <td class="dfi-term-image">
                                                        <div class="dfi-image-selector">
                                                            <input type="hidden" 
                                                                   name="term_fallbacks[<?php echo esc_attr( $tax_name ); ?>][<?php echo esc_attr( $term->term_id ); ?>]" 
                                                                   value="<?php echo esc_attr( $current_image_id ); ?>"
                                                                   class="dfi-term-image-id">
                                                            
                                                            <div class="dfi-image-preview">
                                                                <?php if ( $current_image_id ) : 
                                                                    $image_url = wp_get_attachment_image_url( $current_image_id, 'thumbnail' );
                                                                    if ( $image_url ) : ?>
                                                                        <img src="<?php echo esc_url( $image_url ); ?>" alt="">
                                                                        <button type="button" class="dfi-remove-image" title="<?php esc_attr_e( 'Remove image', 'default-featured-images' ); ?>">&times;</button>
                                                                    <?php endif;
                                                                endif; ?>
                                                            </div>
                                                            
                                                            <button type="button" class="button button-small dfi-select-image">
                                                                <?php _e( 'Select', 'default-featured-images' ); ?>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="dfi-term-actions">
                                                        <?php if ( $current_image_id ) : ?>
                                                            <button type="button" 
                                                                    class="button button-small dfi-clear-fallback"
                                                                    data-taxonomy="<?php echo esc_attr( $tax_name ); ?>"
                                                                    data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
                                                                <?php _e( 'Clear', 'default-featured-images' ); ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <?php _e( 'Save Settings', 'default-featured-images' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Get all public taxonomies
     */
    private function get_all_taxonomies() {
        $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
        
        // Remove nav_menu and other unwanted taxonomies
        unset( $taxonomies['nav_menu'] );
        unset( $taxonomies['link_category'] );
        unset( $taxonomies['post_format'] );
        
        return $taxonomies;
    }

    /**
     * Get term depth in hierarchy
     */
    private function get_term_depth( $term_id, $taxonomy, $depth = 0 ) {
        $term = get_term( $term_id, $taxonomy );
        
        if ( is_wp_error( $term ) || ! $term->parent ) {
            return $depth;
        }
        
        return $this->get_term_depth( $term->parent, $taxonomy, $depth + 1 );
    }

    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer( 'dfi_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'default-featured-images' ) ) );
        }

        $settings = array();

        // Global fallback
        $settings['global_fallback_enabled'] = isset( $_POST['global_fallback_enabled'] ) ? true : false;
        $settings['global_fallback_id'] = isset( $_POST['global_fallback_id'] ) ? intval( $_POST['global_fallback_id'] ) : 0;

        // Taxonomy priority
        $settings['taxonomy_priority'] = isset( $_POST['taxonomy_priority'] ) ? array_map( 'sanitize_text_field', $_POST['taxonomy_priority'] ) : array();

        // Term fallbacks
        $term_fallbacks = array();
        if ( isset( $_POST['term_fallbacks'] ) && is_array( $_POST['term_fallbacks'] ) ) {
            foreach ( $_POST['term_fallbacks'] as $taxonomy => $terms ) {
                $taxonomy = sanitize_text_field( $taxonomy );
                $term_fallbacks[ $taxonomy ] = array();
                
                foreach ( $terms as $term_id => $image_id ) {
                    $term_id = intval( $term_id );
                    $image_id = intval( $image_id );
                    
                    if ( $image_id > 0 ) {
                        $term_fallbacks[ $taxonomy ][ $term_id ] = $image_id;
                    }
                }
                
                // Remove empty taxonomies
                if ( empty( $term_fallbacks[ $taxonomy ] ) ) {
                    unset( $term_fallbacks[ $taxonomy ] );
                }
            }
        }
        $settings['term_fallbacks'] = $term_fallbacks;

        // Save settings
        $result = $this->settings->update_settings( $settings );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Settings saved successfully!', 'default-featured-images' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to save settings.', 'default-featured-images' ) ) );
        }
    }

    /**
     * AJAX: Remove fallback
     */
    public function ajax_remove_fallback() {
        check_ajax_referer( 'dfi_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'default-featured-images' ) ) );
        }

        $taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( $_POST['taxonomy'] ) : '';
        $term_id = isset( $_POST['term_id'] ) ? intval( $_POST['term_id'] ) : 0;

        if ( empty( $taxonomy ) || empty( $term_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'default-featured-images' ) ) );
        }

        $result = $this->settings->remove_term_fallback( $taxonomy, $term_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Fallback removed successfully!', 'default-featured-images' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to remove fallback.', 'default-featured-images' ) ) );
        }
    }

    /**
     * AJAX: Get taxonomies (for refresh)
     */
    public function ajax_get_taxonomies() {
        check_ajax_referer( 'dfi_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'default-featured-images' ) ) );
        }

        $taxonomies = $this->get_all_taxonomies();
        $taxonomy_data = array();

        foreach ( $taxonomies as $tax_name => $tax_obj ) {
            $taxonomy_data[] = array(
                'name' => $tax_name,
                'label' => $tax_obj->label,
            );
        }

        wp_send_json_success( array( 'taxonomies' => $taxonomy_data ) );
    }
}
