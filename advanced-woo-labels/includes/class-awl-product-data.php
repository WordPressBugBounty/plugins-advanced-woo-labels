<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWL_Product_Data' ) ) :

    /**
     * Class for plugin help methods
     */
    class AWL_Product_Data {
        
        /**
         * Get product sales based on date query
         * @since 1.0
         * @param  string $query Date query
         * @param  object $product Product
         * @return integer
         */
        static public function get_sales_count( $query, $product ) {

            $value = 0;

            if ( $query === 'all' ) {

                $value = method_exists( $product, 'get_total_sales' ) ? $product->get_total_sales() : get_post_meta( $product->get_id(), 'total_sales', true );

            } else {

                $value = self::get_period_sales_count( $query, $product );

            }

            return $value;

        }

        /**
         * Get product sales count for a given time period.
         *
         * When WooCommerce High-Performance Order Storage ( HPOS ) is the active
         * order storage the legacy reports class can no longer read the orders
         * ( it is hardcoded to the posts table ), so the count is taken directly
         * from the orders table. Otherwise the original reports query is used.
         *
         * @since 2.47
         * @param  string $query Relative date query ( e.g. '-1 month' )
         * @param  object $product Product
         * @return integer
         */
        static private function get_period_sales_count( $query, $product ) {

            if ( self::orders_table_enabled() ) {
                return self::get_period_sales_count_hpos( $query, $product );
            }

            return self::get_period_sales_count_legacy( $query, $product );

        }

        /**
         * Get product sales count for a given time period from the HPOS orders table.
         *
         * @since 2.47
         * @param  string $query Relative date query ( e.g. '-1 month' )
         * @param  object $product Product
         * @return integer
         */
        static private function get_period_sales_count_hpos( $query, $product ) {
            global $wpdb;

            $product_id = $product->get_id();

            // Boundary date in GMT, matching the legacy reports behaviour.
            $date = get_gmt_from_date( date_i18n( 'Y-m-d', strtotime( $query, current_time( 'timestamp' ) ) ) . ' 00:00:00' );

            // Order statuses counted as a sale ( same defaults as WC reports ).
            $statuses = "'wc-" . implode( "','wc-", array( 'completed', 'processing', 'on-hold' ) ) . "'";

            $value = $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM( qty.meta_value )
                FROM {$wpdb->prefix}woocommerce_order_items AS items
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS product
                    ON items.order_item_id = product.order_item_id AND product.meta_key = '_product_id'
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS qty
                    ON items.order_item_id = qty.order_item_id AND qty.meta_key = '_qty'
                INNER JOIN {$wpdb->prefix}wc_orders AS orders
                    ON items.order_id = orders.id
                WHERE items.order_item_type = 'line_item'
                    AND product.meta_value = %d
                    AND orders.type = 'shop_order'
                    AND orders.status IN ( $statuses )
                    AND orders.date_created_gmt > %s",
                $product_id,
                $date
            ) );

            return $value ? intval( $value ) : 0;

        }

        /**
         * Get product sales count for a given time period using the legacy reports query.
         *
         * @since 2.47
         * @param  string $query Relative date query ( e.g. '-1 month' )
         * @param  object $product Product
         * @return integer
         */
        static private function get_period_sales_count_legacy( $query, $product ) {
            global $woocommerce;

            $value = 0;

            include_once( $woocommerce->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' );
            $wc_report = new WC_Admin_Report();

            $data = $wc_report->get_order_report_data(
                array(
                    'data'         => array(
                        '_product_id' => array(
                            'type'            => 'order_item_meta',
                            'order_item_type' => 'line_item',
                            'function'        => '',
                            'name'            => 'product_id',
                        ),
                        '_qty'     => array(
                            'type'            => 'order_item_meta',
                            'order_item_type' => 'line_item',
                            'function'        => 'SUM',
                            'name'            => 'sales',
                        ),
                        'post_date'   => array(
                            'type'     => 'post_data',
                            'function' => '',
                            'name'     => 'post_date',
                        ),
                    ),
                    'where'        => array(
                        array(
                            'key'      => 'post_date',
                            'value'    => date_i18n( 'Y-m-d', strtotime( $query, current_time( 'timestamp' ) ) ),
                            'operator' => '>',
                        ),
                        array(
                            'key'      => 'order_item_meta__product_id.meta_value',
                            'value'    => $product->get_id(),
                            'operator' => '=',
                        ),
                    ),
                    'group_by'     => 'product_id',
                    'query_type'   => 'get_results',
                    'filter_range' => false,
                )
            );

            if ( $data && is_array( $data ) ) {
                $value = $data[0]->sales;
            }

            return $value;

        }

        /**
         * Check whether WooCommerce HPOS ( custom orders table ) storage is in use.
         *
         * @since 2.47
         * @return boolean
         */
        static private function orders_table_enabled() {
            if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
                return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
            }
            return false;
        }

        /**
         * Get product quantity
         * @since 1.0
         * @param  object $product Product
         * @return integer
         */
        static public function get_quantity( $product ) {

            $stock_levels = array();

            if ( $product->is_type( 'variable' ) ) {
                foreach ( $product->get_children() as $variation ) {
                    $var = wc_get_product( $variation );
                    if ( $var->is_in_stock() && ! $var->get_manage_stock() ) {
                        $stock_levels[] = 999999;
                    } else {
                        $stock_levels[] = $var->get_stock_quantity();
                    }
                }
            } else {
                if ( $product->is_in_stock() && ! $product->get_manage_stock() ) {
                    $stock_levels[] = 999999;
                } else {
                    $stock_levels[] = $product->get_stock_quantity();
                }
            }

            if ( empty( $stock_levels ) ) {
                $stock_levels[] = 0;
            }

            return intval( max( $stock_levels ) );

        }

        /**
         * Get product reviews count
         * @since 1.0
         * @param  string $query Date query
         * @param  object $product Product
         * @return integer
         */
        static public function get_reviews_count( $query, $product ) {

            if ( $query === 'all' ) {

                $value = $product->get_review_count();

            } else {

                $value = get_comments( array(
                    'post_id'    => $product->get_id(),
                    'count'      => true,
                    'date_query' => $query
                ));

            }

            return $value;

        }

        /**
         * Get product sale status
         * @since 1.30
         * @param  object $product Product
         * @return boolean
         */
        static public function is_on_sale( $product ) {

            /**
             * Filter product sale status
             * @since 1.30
             * @param boolean $is_on_sale Sale status
             * @param object $product Product
             */
            $is_on_sale = apply_filters( 'awl_is_on_sale', $product->is_on_sale(), $product );

            return $is_on_sale;

        }

        /**
         * Get product price
         * @since 1.23
         * @param  object $product Product
         * @return integer
         */
        static public function get_price( $product ) {

            /**
             * Filter product price
             * @since 1.23
             * @param integer $price Product price
             * @param object $product Product
             */
            $price = apply_filters( 'awl_product_price', wc_get_price_to_display( $product, array('price' => $product->get_price() ) ), $product );

            return $price;

        }

        /**
         * Get product price
         * @since 1.23
         * @param  object $product Product
         * @return integer
         */
        static public function get_sale_price( $product ) {

            /**
             * Filter product sale price
             * @since 1.23
             * @param integer $price Product price
             * @param object $product Product
             */
            $price = apply_filters( 'awl_product_sale_price', wc_get_price_to_display( $product, array('price' => $product->get_sale_price() ) ), $product );

            if ( ! $price || ! self::is_on_sale( $product ) ) {
                $price = AWL_Product_Data::get_price( $product );
            }

            return $price;

        }

        /**
         * Get product discount percentage
         * @since 1.06
         * @param  object $product Product
         * @return integer
         */
        static public function get_discount_percent( $product ) {

            $enable_cache_discounts = apply_filters( 'awl_enable_discounts_cache', true, $product );

            $save_percents = 0;

            if ( $product->is_type( 'variable' ) ) {

               if ( $enable_cache_discounts ) {
                   $save_percents_cache = get_post_meta( $product->get_id(), '_awl_save_percent_value', true );
                   if ( $save_percents_cache ) {
                       return $save_percents_cache;
                   }
               }

               $available_variations = $product->get_available_variations();

               for ( $i = 0; $i < count( $available_variations ); ++ $i ) {
                   $variation_id     = $available_variations[ $i ]['variation_id'];
                   $variable_product = new WC_Product_Variation( $variation_id );
                   $variable_product_regular_price = wc_get_price_to_display( $product, array('price' => $variable_product->get_regular_price() ) );
                   $variable_product_sale_price = AWL_Product_Data::get_sale_price( $variable_product );
                   if ( $variable_product_regular_price == $variable_product_sale_price ) {
                    continue;
                   }
                   $percentage = ( ( $variable_product_regular_price - $variable_product_sale_price ) / $variable_product_regular_price ) * 100;
                   if ( $percentage > $save_percents ) {
                       $save_percents = $percentage;
                   }
               }

               if ( $enable_cache_discounts ) {
                   update_post_meta( $product->get_id(), '_awl_save_percent_value', $save_percents );
               }

           } else {
               $product_regular_price = wc_get_price_to_display( $product, array('price' => $product->get_regular_price() ) );
               $product_sale_price = AWL_Product_Data::get_sale_price( $product );
               if ( $product_sale_price && $product_regular_price && $product_regular_price !== $product_sale_price ) {
                   $save_percents = ( ( $product_regular_price - $product_sale_price ) / $product_regular_price ) * 100;
               }
           }

           return $save_percents;

        }

        /**
         * Get product discount amount
         * @since 1.06
         * @param  object $product Product
         * @return integer
         */
        static public function get_discount_amount( $product ) {

            $enable_cache_discounts = apply_filters( 'awl_enable_discounts_cache', true, $product );

            $save_amount = 0;

            if ( $product->is_type( 'variable' ) ) {

                if ( $enable_cache_discounts ) {
                    $save_amount_cache = get_post_meta( $product->get_id(), '_awl_save_amount_value', true );
                    if ( $save_amount_cache ) {
                        return $save_amount_cache;
                    }
                }

                $available_variations = $product->get_available_variations();

                for ( $i = 0; $i < count( $available_variations ); ++ $i ) {
                    $variation_id     = $available_variations[ $i ]['variation_id'];
                    $variable_product = new WC_Product_Variation( $variation_id );
                    $variable_product_regular_price = wc_get_price_to_display( $product, array('price' => $variable_product->get_regular_price() ) );
                    $variable_product_sale_price = AWL_Product_Data::get_sale_price( $variable_product );
                    if ( $variable_product_regular_price == $variable_product_sale_price ) {
                        continue;
                    }
                    $amount = $variable_product_regular_price - $variable_product_sale_price;
                    if ( $amount > $save_amount ) {
                        $save_amount = $amount;
                    }
                }

                if ( $enable_cache_discounts ) {
                    update_post_meta( $product->get_id(), '_awl_save_amount_value', $save_amount );
                }

            } else {
                $product_regular_price = wc_get_price_to_display( $product, array('price' => $product->get_regular_price() ) );
                $product_sale_price = AWL_Product_Data::get_sale_price( $product );
                if ( $product_sale_price && $product_regular_price && $product_regular_price !== $product_sale_price ) {
                    $save_amount = $product_regular_price - $product_sale_price;
                }
            }

            return $save_amount;

        }

    }

endif;