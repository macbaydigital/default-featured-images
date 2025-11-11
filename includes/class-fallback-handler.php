<?php
/**
 * Fallback Handler Class
 * Handles the logic for applying fallback featured images
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DFI_Fallback_Handler {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Settings instance
     */
    private $settings;

    /**
     * Flag to prevent recursion
     */
    private static $checking = false;

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
        add_filter( 'get_post_metadata', array( $this, 'apply_fallback_thumbnail' ), 10, 4 );
    }

    /**
     * Apply fallback thumbnail
     */
    public function apply_fallback_thumbnail( $value, $object_id, $meta_key, $single ) {
        // Only intercept for _thumbnail_id
        if ( '_thumbnail_id' !== $meta_key ) {
            return $value;
        }

        // Prevent recursion
        if ( self::$checking ) {
            return $value;
        }

        // Set flag
        self::$checking = true;

        // Check if a real featured image exists
        $existing_thumbnail = get_metadata_raw( 'post', $object_id, '_thumbnail_id', true );

        if ( ! empty( $existing_thumbnail ) ) {
            self::$checking = false;
            return $value; // Real featured image exists, don't override
        }

        // Get fallback image ID
        $fallback_id = $this->get_fallback_for_post( $object_id );

        self::$checking = false;

        if ( $fallback_id ) {
            return (int) $fallback_id;
        }

        return $value;
    }

    /**
     * Get fallback image for a post
     */
    private function get_fallback_for_post( $post_id ) {
        $settings = $this->settings->get_settings();
        $term_fallbacks = $settings['term_fallbacks'];
        
        if ( empty( $term_fallbacks ) ) {
            return $this->get_global_fallback();
        }

        // Get all taxonomies for this post
        $post_taxonomies = get_object_taxonomies( get_post_type( $post_id ) );
        
        if ( empty( $post_taxonomies ) ) {
            return $this->get_global_fallback();
        }

        // Get taxonomy priority
        $taxonomy_priority = $settings['taxonomy_priority'];
        
        // Order taxonomies by priority
        $ordered_taxonomies = array();
        
        // First, add taxonomies in priority order
        if ( ! empty( $taxonomy_priority ) ) {
            foreach ( $taxonomy_priority as $tax_name ) {
                if ( in_array( $tax_name, $post_taxonomies ) ) {
                    $ordered_taxonomies[] = $tax_name;
                }
            }
        }
        
        // Then add remaining taxonomies
        $remaining = array_diff( $post_taxonomies, $ordered_taxonomies );
        $ordered_taxonomies = array_merge( $ordered_taxonomies, $remaining );

        // Check each taxonomy in priority order
        foreach ( $ordered_taxonomies as $taxonomy ) {
            if ( ! isset( $term_fallbacks[ $taxonomy ] ) ) {
                continue;
            }

            // Get all terms for this post in this taxonomy
            $terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
            
            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            // Find the most specific (deepest) term with a fallback
            $fallback_id = $this->get_most_specific_term_fallback( $terms, $taxonomy, $term_fallbacks[ $taxonomy ] );
            
            if ( $fallback_id ) {
                return $fallback_id;
            }
        }

        // No term-specific fallback found, use global
        return $this->get_global_fallback();
    }

    /**
     * Get the most specific (deepest in hierarchy) term fallback
     */
    private function get_most_specific_term_fallback( $term_ids, $taxonomy, $taxonomy_fallbacks ) {
        $max_depth = -1;
        $fallback_id = 0;

        foreach ( $term_ids as $term_id ) {
            // Check if this term has a fallback
            if ( ! isset( $taxonomy_fallbacks[ $term_id ] ) ) {
                continue;
            }

            // Get term depth
            $depth = $this->get_term_depth( $term_id, $taxonomy );

            // If this term is deeper (more specific), use it
            if ( $depth > $max_depth ) {
                $max_depth = $depth;
                $fallback_id = $taxonomy_fallbacks[ $term_id ];
            }
        }

        return $fallback_id;
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
     * Get global fallback
     */
    private function get_global_fallback() {
        $settings = $this->settings->get_settings();
        
        if ( ! $settings['global_fallback_enabled'] ) {
            return 0;
        }

        return $settings['global_fallback_id'];
    }
}
