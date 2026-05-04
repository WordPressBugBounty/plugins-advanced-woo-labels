<?php

/**
 * AWL WooCommerce Product Bundles plugin integration
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('AWL_Product_Bundles')) :

    /**
     * Class for main plugin functions
     */
    class AWL_Product_Bundles {

        /**
         * @var AWL_Product_Bundles The single instance of the class
         */
        protected static $_instance = null;

        /**
         * Main AWL_Product_Bundles Instance
         *
         * Ensures only one instance of AWL_Product_Bundles is loaded or can be loaded.
         *
         * @static
         * @return AWL_Product_Bundles - Main instance
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct() {

            add_filter( 'awl_label_condition_match_rule', array( $this, 'awl_label_condition_match_rule' ), 10, 3 );

        }

        /*
         * Rewrite stock_status display conditions. Fix bundle products stock status.
         * is_in_stock() is authoritative for bundles as it dynamically checks all bundled items,
         * whereas get_stock_status() may return a stale stored meta value.
         */
        public function awl_label_condition_match_rule( $match_rule, $condition_name, $condition_rule ) {

            if ( 'stock_status' === $condition_name ) {

                global $product;

                if ( $product && $product->is_type( 'bundle' ) ) {

                    if ( $product->is_in_stock() ) {
                        $stock_status = 'instock';
                    } else {
                        $stored = $product->get_stock_status();
                        $stock_status = ( 'onbackorder' === $stored ) ? 'onbackorder' : 'outofstock';
                    }

                    $compare_value = isset( $condition_rule['value'] ) ? $condition_rule['value'] : '';
                    $operator      = isset( $condition_rule['operator'] ) ? $condition_rule['operator'] : '';

                    if ( 'equal' === $operator ) {
                        $match_rule = ( $compare_value == $stock_status );
                    } elseif ( 'not_equal' === $operator ) {
                        $match_rule = ( $compare_value != $stock_status );
                    } elseif ( 'in_list' === $operator ) {
                        $rule_values = is_array( $compare_value ) ? array_map( 'strval', $compare_value ) : array( (string) $compare_value );
                        if ( in_array( 'awl_any', $rule_values, true ) ) {
                            $match_rule = true;
                        } else {
                            $match_rule = in_array( $stock_status, $rule_values, true );
                        }
                    } elseif ( 'not_in_list' === $operator ) {
                        $rule_values = is_array( $compare_value ) ? array_map( 'strval', $compare_value ) : array( (string) $compare_value );
                        if ( in_array( 'awl_any', $rule_values, true ) ) {
                            $match_rule = false;
                        } else {
                            $match_rule = ! in_array( $stock_status, $rule_values, true );
                        }
                    }

                }

            }

            return $match_rule;

        }

    }

endif;

AWL_Product_Bundles::instance();