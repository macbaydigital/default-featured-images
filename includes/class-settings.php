<?php
/**
 * Settings Management Class
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DFI_Settings {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Option name
     */
    private $option_name = 'dfi_settings';

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
        // Settings are loaded on demand
    }

    /**
     * Get all settings
     */
    public function get_settings() {
        $defaults = array(
            'term_fallbacks' => array(),
            'global_fallback_enabled' => false,
            'global_fallback_id' => 0,
            'taxonomy_priority' => array(),
        );

        $settings = get_option( $this->option_name, $defaults );
        return wp_parse_args( $settings, $defaults );
    }

    /**
     * Get specific setting
     */
    public function get_setting( $key, $default = null ) {
        $settings = $this->get_settings();
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    /**
     * Update settings
     */
    public function update_settings( $new_settings ) {
        $current_settings = $this->get_settings();
        $updated_settings = wp_parse_args( $new_settings, $current_settings );
        return update_option( $this->option_name, $updated_settings );
    }

    /**
     * Update specific setting
     */
    public function update_setting( $key, $value ) {
        $settings = $this->get_settings();
        $settings[ $key ] = $value;
        return update_option( $this->option_name, $settings );
    }

    /**
     * Delete settings
     */
    public function delete_settings() {
        return delete_option( $this->option_name );
    }

    /**
     * Get term fallbacks
     */
    public function get_term_fallbacks() {
        return $this->get_setting( 'term_fallbacks', array() );
    }

    /**
     * Add or update term fallback
     */
    public function set_term_fallback( $taxonomy, $term_id, $image_id ) {
        $fallbacks = $this->get_term_fallbacks();
        
        if ( ! isset( $fallbacks[ $taxonomy ] ) ) {
            $fallbacks[ $taxonomy ] = array();
        }
        
        $fallbacks[ $taxonomy ][ $term_id ] = intval( $image_id );
        
        return $this->update_setting( 'term_fallbacks', $fallbacks );
    }

    /**
     * Remove term fallback
     */
    public function remove_term_fallback( $taxonomy, $term_id ) {
        $fallbacks = $this->get_term_fallbacks();
        
        if ( isset( $fallbacks[ $taxonomy ][ $term_id ] ) ) {
            unset( $fallbacks[ $taxonomy ][ $term_id ] );
            
            // Remove taxonomy key if empty
            if ( empty( $fallbacks[ $taxonomy ] ) ) {
                unset( $fallbacks[ $taxonomy ] );
            }
            
            return $this->update_setting( 'term_fallbacks', $fallbacks );
        }
        
        return false;
    }

    /**
     * Get taxonomy priority order
     */
    public function get_taxonomy_priority() {
        return $this->get_setting( 'taxonomy_priority', array() );
    }

    /**
     * Set taxonomy priority order
     */
    public function set_taxonomy_priority( $priority_array ) {
        return $this->update_setting( 'taxonomy_priority', $priority_array );
    }
}
