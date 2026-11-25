<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWL_Admin_Black_Friday' ) ) :

    /**
     * Class for Black Friday sale banner on plugin admin screens
     *
     * The twin of the one in Advanced Woo Search, and deliberately independent of it: the two
     * plugins keep their own stored offer and their own "hidden by this user" flag, so closing
     * the banner in one of them says nothing about the other.
     *
     * It appears on one screen of the plugin's own: above the table on the list of labels. The
     * box beside a label being edited keeps its own promotion of the premium version, and the
     * dot on the menu is only cleared by the screen that actually carries the banner.
     *
     * None of the text goes through a translation function on purpose. The banner lives for ten
     * days and its wording changes while the sale runs, so handing throwaway strings to
     * translate.wordpress.org would have translators working on text that is gone before their
     * work ships. Everything printed here is English and escaped at the point of output.
     */
    class AWL_Admin_Black_Friday {

        /*
         * Sale end time, December 2 2026 23:59:59 UTC
         */
        const ENDS = 1796255999;

        /*
         * User meta key, set when user hides the banner
         */
        const META_KEY = 'awl_hide_bf2026_banner';

        /*
         * User meta key, set the first time this user opens a screen that carries the banner
         */
        const SEEN_META = 'awl_bf2026_page_seen';

        /*
         * Option holding the offer this site shows
         *
         * Worked out once, on the first screen that shows the banner, and then left alone:
         * every visit after that, and every user of the site, sees the same thing.
         */
        const OFFER_OPTION = 'awl_bf2026_offer';

        /*
         * Shape of what is stored, raised when the keys of the stored array change
         */
        const OFFER_VERSION = 1;

        /*
         * Brands a store must carry before labels by brand are worth offering
         */
        const BRANDS_MIN_TERMS = 100;

        /*
         * Terms a custom taxonomy must hold before it says anything about the catalogue
         */
        const TAXONOMY_MIN_TERMS = 20;

        /*
         * Products on sale before the discount label is worth offering
         */
        const SALE_MIN_PRODUCTS = 10;

        /*
         * Labels a store must have made before it counts as invested in them
         */
        const LABELS_MIN = 3;

        /*
         * How many taxonomies are looked at, so a site with dozens of them does not turn one
         * banner into dozens of queries
         */
        const TAXONOMY_MAX_CHECKED = 8;

        /*
         * Product search plugins of other vendors
         *
         * Folder names, matched by their first part, so premium builds are covered by the same
         * entry. A store running one of these has a search need already, and ours comes with
         * the bundle.
         */
        static private $search_plugins = array(
            'ajax-search-for-woocommerce',   // FiboSearch, 100k installs
            'relevanssi',                    // 100k
            'add-search-to-menu',            // Ivory Search, 90k
            'ajax-search-lite',              // 70k
            'yith-woocommerce-ajax-search',  // 30k
            'searchwp',
            'woocommerce-product-search',
            'asl-storage',
        );

        /*
         * Product filter plugins of other vendors
         */
        static private $filter_plugins = array(
            'woocommerce-products-filter',          // HUSKY, 80k
            'yith-woocommerce-ajax-navigation',     // YITH, 70k
            'woo-product-filter',                   // WBW, 50k
            'filter-everything',                    // 50k
            'woocommerce-ajax-filters',             // BeRocket, 50k
            'themify-wc-product-filter',            // 20k
            'wc-ajax-product-filter',               // 8k
            'facetwp',
            'premmerce-woocommerce-product-filter',
            'annasta-woocommerce-product-filters',
            'pofily-woo-product-filters',
            'jet-smart-filters',
            'fibofilters',
            'search-filter',
        );

        /*
         * Constructor
         */
        public function __construct() {

            add_action( 'wp_ajax_awl-hidePromoBanner', array( $this, 'hide_banner' ) );

            // the dot is cleared before it is drawn, so it is already gone on the very load
            // that brings the user to the labels
            add_action( 'admin_init', array( $this, 'mark_page_seen' ), 9 );
            add_action( 'admin_init', array( $this, 'add_menu_dot' ), 10 );

            // ?awl_promo=reset puts this user back to never having seen any of it
            add_action( 'admin_init', array( $this, 'reset_user_flags' ), 11 );

            // above the table on the list of labels
            add_action( 'admin_notices', array( $this, 'show_list_banner' ) );

        }

        /*
         * Capability that may see the banner
         *
         * The same one the plugin's menu is registered with, in one place so a site that
         * changes who manages labels does not have it half applied.
         *
         * @return string
         */
        static private function capability() {

            return 'edit_others_posts';

        }

        /*
         * Is the sale on at all
         * @return bool
         */
        static public function is_running() {

            return time() < self::ENDS;

        }

        /*
         * Check if banner must be shown for current user
         * @return bool
         */
        static public function is_active() {

            // a variant asked for in the url is shown whatever the date and the hidden flag say
            if ( ! empty( $_GET['awl_promo'] ) && current_user_can( self::capability() ) ) {
                return true;
            }

            if ( ! self::is_running() ) {
                return false;
            }

            $user_id = get_current_user_id();

            return $user_id && ! get_user_meta( $user_id, self::META_KEY, true );

        }

        /*
         * Offer of the banner, picked from what the store runs
         *
         * Our own plugins first ( the bundle is the only thing left to sell them ), then a need
         * proven by another vendor's search or filtering plugin, then the premium features this
         * catalogue asks for. A store that says nothing gets the plain discount.
         *
         * Everything is read locally and nothing is sent anywhere.
         *
         * @return array
         */
        static public function get_offer() {

            $offer = self::preview();

            if ( ! $offer ) {

                $offer = get_option( self::OFFER_OPTION );

                if ( ! is_array( $offer ) || ! isset( $offer['reason'] ) || ! isset( $offer['v'] ) || self::OFFER_VERSION !== (int) $offer['v'] ) {

                    $offer = self::choose_offer();
                    $offer['v'] = self::OFFER_VERSION;

                    // not autoloaded: it is read on the plugin's own screens and nowhere else
                    update_option( self::OFFER_OPTION, $offer, false );

                }

            }

            $bundle = $offer['type'] === 'bundle';

            // text is built here and never stored
            if ( $bundle ) {

                // the plugin they are standing in comes first, the rest of the set follows
                $title = 'Product labels, search and filters';

                $other = self::other_vendor_text( $offer['reason'] );

                $lines = array(
                    $other ? $other : self::missing_text( isset( $offer['missing'] ) ? $offer['missing'] : 'none' ),
                    '50% cheaper than buying separately',
                );

            } else {

                $title = 'Get Advanced Woo Labels Pro';
                $lines = array();

                $first  = $offer['reason'] === 'generic' ? '' : self::reason_text( $offer['reason'], isset( $offer['arg'] ) ? $offer['arg'] : '' );
                $second = empty( $offer['second'] ) ? '' : self::reason_text( $offer['second'], isset( $offer['second_arg'] ) ? $offer['second_arg'] : '' );

                if ( $first ) {
                    $lines[] = $first;
                }

                if ( $second ) {
                    $lines[] = $second;
                }

                foreach ( self::fallback_lines() as $fallback ) {

                    if ( count( $lines ) >= 2 ) {
                        break;
                    }

                    $lines[] = $fallback;

                }

            }

            return array(
                'type'   => $offer['type'],
                'id'     => $offer['type'] . '_' . $offer['reason'],
                'title'  => $title,
                // the discount in front of the headline, for the single plugin only
                'prefix' => $bundle ? '' : '30% off.',
                'lines'  => array_slice( array_filter( $lines ), 0, 2 ),
                'cta'    => $bundle ? 'See the bundle deal' : 'Check the deals',
                'url'    => $bundle
                    ? 'https://kramakit.com/plugins/bundle/'
                    : 'https://kramakit.com/plugins/advanced-woo-labels/',
            );

        }

        /*
         * Variant asked for in the url, for looking at them all without changing the store
         *
         *     ?awl_promo=bundle:own_aws
         *     ?awl_promo=pro:acf
         *     ?awl_promo=pro:acf,vendors          two reasons, the second one goes on line two
         *     ?awl_promo=pro:taxonomies&awl_promo_arg=Collection
         *     ?awl_promo=off                      the banner a store with nothing to say sees
         *     ?awl_promo=reset                    forget the hidden banner and the seen dot
         *
         * Bundle reasons: own_both, own_aws, own_filters, search, filters, search_filters.
         * Pro reasons: acf, vendors, brands, multilingual, memberships, ean, tables, wishlist,
         * sale, taxonomies, attributes, labels.
         *
         * Nothing is written and nothing is stored while previewing, and the date and the
         * "hidden by this user" check are skipped.
         *
         * @return array|false
         */
        static private function preview() {

            if ( empty( $_GET['awl_promo'] ) ) {
                return false;
            }

            if ( ! current_user_can( self::capability() ) ) {
                return false;
            }

            $asked = sanitize_text_field( wp_unslash( $_GET['awl_promo'] ) );

            if ( $asked === 'off' ) {
                return array( 'type' => 'pro', 'reason' => 'generic' );
            }

            // handled by reset_user_flags(), the banner itself stays the stored one
            if ( $asked === 'reset' ) {
                return false;
            }

            $parts = explode( ':', $asked, 2 );
            $type  = $parts[0] === 'bundle' ? 'bundle' : 'pro';
            $ids   = isset( $parts[1] ) ? explode( ',', $parts[1] ) : array();
            $first = isset( $ids[0] ) ? sanitize_key( $ids[0] ) : '';

            if ( ! $first ) {
                return false;
            }

            if ( 'bundle' === $type ) {

                $missing = array(
                    'own_both'       => 'none',
                    'own_aws'        => 'filters',
                    'own_filters'    => 'search',
                    'search'         => 'both',
                    'filters'        => 'both',
                    'search_filters' => 'both',
                );

                return array(
                    'type'    => 'bundle',
                    'reason'  => $first,
                    'missing' => isset( $missing[ $first ] ) ? $missing[ $first ] : 'both',
                );

            }

            $offer = array(
                'type'   => 'pro',
                'reason' => $first,
                'arg'    => isset( $_GET['awl_promo_arg'] ) ? sanitize_text_field( wp_unslash( $_GET['awl_promo_arg'] ) ) : '',
            );

            if ( isset( $ids[1] ) ) {
                $offer['second']     = sanitize_key( $ids[1] );
                $offer['second_arg'] = '';
            }

            return $offer;

        }

        /*
         * Lines that fill the banner when the store gave us fewer than two reasons
         * @return array
         */
        static private function fallback_lines() {

            return array(
                'And a lot more in the Pro version',
                '30-day money back guarantee',
            );

        }

        /*
         * Headline of a reason
         * @param string $id  Reason id
         * @param string $arg Something of this store the line can name
         * @return string
         */
        static private function reason_text( $id, $arg = '' ) {

            if ( $id === 'taxonomies' && $arg ) {
                return sprintf( 'Use %s in label rules and in label text', $arg );
            }

            $reasons = self::reasons();

            return isset( $reasons[ $id ] ) ? $reasons[ $id ] : '';

        }

        /*
         * Reason lines
         *
         * Each one names something the free version cannot do: the display conditions and the
         * text variables marked Pro in class-awl-admin-options-premium.php.
         *
         * @return array
         */
        static private function reasons() {

            // Pro gives each of these twice over: as a rule that decides whether a label is
            // shown at all, and as a variable that prints the value inside it. One shape,
            // "use X in label rules and in label text", carries both halves everywhere.
            return array(
                'acf'          => 'Use ACF fields in label rules and in label text',
                'vendors'      => 'Use vendor data in label rules and in label text',
                'brands'       => 'Use brands in label rules and in label text',
                'multilingual' => 'Use the visitor language in label rules',
                'memberships'  => 'Use memberships in label rules and in label text',
                'ean'          => 'Use GTIN and EAN in label rules and in label text',
                'tables'       => 'Show your labels inside product tables',
                'wishlist'     => 'Use wishlist counts in label rules and in label text',
                'sale'         => 'Use sale dates in label rules, discounts in label text',
                'taxonomies'   => 'Use your taxonomies in label rules and in label text',
                'attributes'   => 'Use attributes in label rules and in label text',
                'labels'       => 'Image labels, animations and more shapes',
                'generic'      => '',
            );

        }

        /*
         * What a store running another vendor's search or filtering plugin hears
         *
         * Those plugins and the labels share the product card, and when one of them redraws it
         * the labels have to survive the redraw. That is what the same team's plugins take care
         * of, and the line also names the other half of the bundle, which such a store is
         * missing completely.
         *
         * No competitor is named: this is their own admin, and we are guests in it.
         *
         * @param string $reason Why the bundle was picked
         * @return string Empty when the store runs nothing of the sort
         */
        static private function other_vendor_text( $reason ) {

            $lines = array(
                'search'         => 'Labels that survive an AJAX search, plus filtering',
                'filters'        => 'Labels that survive AJAX filtering, plus search',
                'search_filters' => 'One vendor for all three, no plugin conflicts',
            );

            return isset( $lines[ $reason ] ) ? $lines[ $reason ] : '';

        }

        /*
         * Line naming the plugins of the bundle this store does not have yet
         * @param string $missing What is missing: both, search, filters or none
         * @return string
         */
        static private function missing_text( $missing ) {

            $lines = array(
                'both'    => 'Additional search and filtering plugins that work seamlessly',
                'search'  => 'Additional product search plugin that works seamlessly',
                'filters' => 'Additional product filtering plugin that works seamlessly',
                'none'    => 'Pro versions of all three plugins, working seamlessly together',
            );

            return isset( $lines[ $missing ] ) ? $lines[ $missing ] : $lines['none'];

        }

        /*
         * Which plugins of the bundle this store is missing
         * @return string both, search, filters or none
         */
        static private function missing_plugins() {

            $search  = defined( 'AWS_VERSION' ) || defined( 'AWS_PRO_VERSION' );
            $filters = defined( 'KRAMA_FILTERS_VERSION' ) || defined( 'KRAMA_FILTERS_PRO_VERSION' );

            if ( ! $search && ! $filters ) {
                return 'both';
            }

            if ( ! $search ) {
                return 'search';
            }

            if ( ! $filters ) {
                return 'filters';
            }

            return 'none';

        }

        /*
         * Pick the offer
         * @return array
         */
        static private function choose_offer() {

            // 1. other KramaKit plugins are already installed: only the bundle is left to offer
            $own = self::own_plugins_reason();

            if ( $own ) {
                return array( 'type' => 'bundle', 'reason' => $own, 'missing' => self::missing_plugins() );
            }

            // 2. search or filtering is handled by another vendor: both come with the bundle
            $has_search  = self::has_plugin_from( self::$search_plugins );
            $has_filters = self::has_plugin_from( self::$filter_plugins );

            if ( $has_search || $has_filters ) {

                if ( $has_search && $has_filters ) {
                    $reason = 'search_filters';
                } elseif ( $has_search ) {
                    $reason = 'search';
                } else {
                    $reason = 'filters';
                }

                return array( 'type' => 'bundle', 'reason' => $reason, 'missing' => self::missing_plugins() );

            }

            // 3. the premium features this store asks for, strongest first
            $found = self::pro_reasons();

            if ( $found ) {

                $offer = array( 'type' => 'pro', 'reason' => $found[0][0], 'arg' => $found[0][1] );

                if ( isset( $found[1] ) ) {
                    $offer['second']     = $found[1][0];
                    $offer['second_arg'] = $found[1][1];
                }

                return $offer;

            }

            // 4. nothing to go on, the discount alone
            return array( 'type' => 'pro', 'reason' => 'generic' );

        }

        /*
         * Our own plugins running on this store
         * @return string|false Reason id
         */
        static private function own_plugins_reason() {

            $search  = defined( 'AWS_VERSION' ) || defined( 'AWS_PRO_VERSION' );
            $filters = defined( 'KRAMA_FILTERS_VERSION' ) || defined( 'KRAMA_FILTERS_PRO_VERSION' );

            if ( $search && $filters ) {
                return 'own_both';
            }

            if ( $search ) {
                return 'own_aws';
            }

            if ( $filters ) {
                return 'own_filters';
            }

            return false;

        }

        /*
         * Is one of the listed plugins active
         *
         * Folders are compared by their first part, so a premium build in its own folder is
         * caught by the same entry.
         *
         * @param array $list Folder names to look for
         * @return bool
         */
        static private function has_plugin_from( $list ) {

            $active = (array) get_option( 'active_plugins', array() );

            if ( is_multisite() ) {
                $active = array_merge( $active, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
            }

            foreach ( $active as $plugin ) {

                $folder = current( explode( '/', $plugin ) );

                foreach ( $list as $known ) {
                    if ( strpos( $folder, $known ) === 0 ) {
                        return true;
                    }
                }

            }

            return false;

        }

        /*
         * Premium features this store asks for, in order of how clearly it asks
         *
         * Two are enough for a banner, so the scan stops there: the integrations are constant
         * checks and cost nothing, while the catalogue ones query the database and are only
         * reached when the integrations did not fill both places.
         *
         * @return array Up to two pairs of reason id and the name the line may print
         */
        static private function pro_reasons() {

            $found = array();

            // integrations, the same plugins AWL_Admin_Helpers::get_advanced_integrations() looks for
            if ( class_exists( 'ACF' ) ) {
                $found[] = array( 'acf', '' );
            }

            if ( class_exists( 'WCFMmp' ) || class_exists( 'WeDevs_Dokan' ) || defined( 'WCMp_PLUGIN_VERSION' ) || defined( 'MVX_PLUGIN_VERSION' ) || class_exists( 'WC_Vendors' ) ) {
                $found[] = array( 'vendors', '' );
            }

            if ( defined( 'PWB_PLUGIN_VERSION' ) ) {
                $found[] = array( 'brands', '' );
            }

            if ( defined( 'ICL_SITEPRESS_VERSION' ) || defined( 'POLYLANG_VERSION' ) ) {
                $found[] = array( 'multilingual', '' );
            }

            if ( class_exists( 'WC_Memberships' ) ) {
                $found[] = array( 'memberships', '' );
            }

            if ( class_exists( 'Alg_WC_EAN' ) ) {
                $found[] = array( 'ean', '' );
            }

            if ( class_exists( 'WC_Product_Table_Plugin' ) ) {
                $found[] = array( 'tables', '' );
            }

            if ( class_exists( 'YITH_WCWL' ) ) {
                $found[] = array( 'wishlist', '' );
            }

            if ( count( $found ) >= 2 ) {
                return array_slice( $found, 0, 2 );
            }

            // the catalogue, from here on every check is a query
            if ( self::has_sale_products() ) {
                $found[] = array( 'sale', '' );
            }

            if ( count( $found ) < 2 ) {

                $taxonomy = self::custom_taxonomy_reason();

                if ( $taxonomy ) {
                    $found[] = $taxonomy;
                }

            }

            $found = self::unique_reasons( $found );

            if ( count( $found ) < 2 ) {

                $attributes = function_exists( 'wc_get_attribute_taxonomies' ) ? count( wc_get_attribute_taxonomies() ) : 0;

                if ( $attributes > 3 ) {
                    $found[] = array( 'attributes', '' );
                }

            }

            // offset is how many rows to skip, so the Nth label sits at offset N - 1
            if ( count( $found ) < 2 && self::has_posts_beyond( 'awl-labels', self::LABELS_MIN - 1 ) ) {
                $found[] = array( 'labels', '' );
            }

            return array_slice( self::unique_reasons( $found ), 0, 2 );

        }

        /*
         * Drop reasons found twice, e.g. brands by plugin and by the size of the taxonomy
         * @param array $found Pairs of reason id and name
         * @return array
         */
        static private function unique_reasons( $found ) {

            $seen   = array();
            $unique = array();

            foreach ( $found as $reason ) {

                if ( in_array( $reason[0], $seen, true ) ) {
                    continue;
                }

                $seen[]   = $reason[0];
                $unique[] = $reason;

            }

            return $unique;

        }

        /*
         * Is there a published post of this type past the given position
         *
         * Counting is the expensive way to answer "are there more than N of them": COUNT walks
         * every row of the post type. Asking for one row at an offset stops as soon as it is
         * found, so the work is bounded by the threshold and not by the size of the store.
         *
         * @param string $post_type Post type
         * @param int    $offset    How many rows to skip, 0 to ask whether any exists at all
         * @return bool
         */
        static private function has_posts_beyond( $post_type, $offset ) {

            global $wpdb;

            $found = $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' LIMIT 1 OFFSET %d",
                $post_type,
                absint( $offset )
            ) );

            return ! empty( $found );

        }

        /*
         * Does the store have enough products on sale for a discount label to matter
         *
         * WooCommerce keeps an 'onsale' flag in its own lookup table, indexed, one row per
         * product. Asking postmeta instead would mean walking every _sale_price row and
         * reading each one to see whether it is empty - WooCommerce writes that key for
         * products that are not on sale too, so on a large catalogue most of the rows read
         * would be the wrong ones. The lookup table has existed since WooCommerce 3.6; the
         * postmeta query stays as a fallback for a store whose table is missing.
         *
         * @return bool
         */
        static private function has_sale_products() {

            global $wpdb;

            $offset = absint( self::SALE_MIN_PRODUCTS ) > 0 ? absint( self::SALE_MIN_PRODUCTS ) - 1 : 0;
            $lookup = $wpdb->prefix . 'wc_product_meta_lookup';

            // asking the table and catching the error costs one query, SHOW TABLES costs one
            // more and is slow on hosts with a lot of them
            $quiet = $wpdb->suppress_errors( true );

            $found = $wpdb->get_var( $wpdb->prepare(
                "SELECT product_id FROM {$lookup} WHERE onsale = 1 LIMIT 1 OFFSET %d",
                $offset
            ) );

            $missing = (bool) $wpdb->last_error;

            $wpdb->suppress_errors( $quiet );

            if ( ! $missing ) {
                return ! empty( $found );
            }

            $found = $wpdb->get_var( $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sale_price' AND meta_value != '' LIMIT 1 OFFSET %d",
                $offset
            ) );

            return ! empty( $found );

        }

        /*
         * Does this taxonomy hold at least the given number of terms used on products
         * @param string $taxonomy Taxonomy name
         * @param int    $minimum  Terms the taxonomy must hold
         * @return bool
         */
        static private function has_terms( $taxonomy, $minimum ) {

            global $wpdb;

            $found = $wpdb->get_var( $wpdb->prepare(
                "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s AND count > 0 LIMIT 1 OFFSET %d",
                $taxonomy,
                absint( $minimum ) > 0 ? absint( $minimum ) - 1 : 0
            ) );

            return ! empty( $found );

        }

        /*
         * Taxonomies of WooCommerce products that are not WooCommerce's own
         *
         * A taxonomy the store registered itself is a condition the free version cannot label
         * by, and it lets the banner name it. Attribute taxonomies are left out, they have
         * their own reason, and brands get the brand line, which says more than "taxonomy".
         *
         * @return array|false Reason id and the name to print
         */
        static private function custom_taxonomy_reason() {

            if ( ! function_exists( 'get_object_taxonomies' ) ) {
                return false;
            }

            $core = array(
                'product_cat',
                'product_tag',
                'product_type',
                'product_visibility',
                'product_shipping_class',
            );

            $brands = array(
                'product_brand',
                'product_brands',
                'pwb-brand',
                'yith_product_brand',
                'berocket_brand',
            );

            $candidates = array();

            foreach ( get_object_taxonomies( 'product', 'objects' ) as $slug => $taxonomy ) {

                if ( in_array( $slug, $core, true ) || strpos( $slug, 'pa_' ) === 0 ) {
                    continue;
                }

                if ( empty( $taxonomy->public ) || empty( $taxonomy->show_ui ) ) {
                    continue;
                }

                $candidates[ $slug ] = isset( $taxonomy->labels->singular_name ) ? $taxonomy->labels->singular_name : $taxonomy->label;

            }

            // brands first and on their own, so they keep their priority however the store
            // registered them
            foreach ( array_keys( $candidates ) as $slug ) {

                if ( ! in_array( $slug, $brands, true ) ) {
                    continue;
                }

                unset( $candidates[ $slug ] );

                if ( self::has_terms( $slug, self::BRANDS_MIN_TERMS ) ) {
                    return array( 'brands', '' );
                }

            }

            $custom  = array();
            $checked = 0;

            foreach ( $candidates as $slug => $name ) {

                if ( $checked >= self::TAXONOMY_MAX_CHECKED ) {
                    break;
                }

                $checked++;

                if ( self::has_terms( $slug, self::TAXONOMY_MIN_TERMS ) ) {
                    $custom[] = $name;
                }

            }

            if ( ! $custom ) {
                return false;
            }

            // one taxonomy gets named, several are spoken of together
            return count( $custom ) === 1 ? array( 'taxonomies', $custom[0] ) : array( 'taxonomies', '' );

        }

        /*
         * Badge above a purchase box on the premium tab
         *
         * That tab already lays the offer out in full, so the sale only has to add the fact
         * that these prices are down right now, said once over each box, where the eye already
         * is. A banner of its own would push the boxes further down the page.
         *
         * @return string
         */
        static public function get_card_badge() {

            if ( ! self::is_running() ) {
                return '';
            }

            return '<span class="awl-promo-deal"><b>Black Friday deal</b> 30% off</span>';

        }

        /*
         * Banner markup
         *
         * One place only: above the table on the list of labels. The box beside a label being
         * edited keeps its own promotion of the premium version.
         *
         * @return string
         */
        static public function get_banner() {

            if ( ! self::is_active() ) {
                return '';
            }

            $offer = self::get_offer();

            $url = add_query_arg( array(
                'utm_source'   => 'plugin',
                'utm_medium'   => 'promo-banner',
                'utm_campaign' => 'awl-black-friday',
                // which rule picked this offer, so the landing page can lead with the same thing
                'utm_content'  => $offer['id'],
            ), $offer['url'] );

            $left = self::ENDS - time();

            // countdown is updated every second by the admin script
            $counters = array(
                'days'    => array( floor( $left / DAY_IN_SECONDS ), 'days' ),
                'hours'   => array( floor( $left % DAY_IN_SECONDS / HOUR_IN_SECONDS ), 'hrs' ),
                'minutes' => array( floor( $left % HOUR_IN_SECONDS / MINUTE_IN_SECONDS ), 'min' ),
                'seconds' => array( $left % MINUTE_IN_SECONDS, 'sec' ),
            );

            $html = '';

            $html .= '<div class="awl-promo-banner awl-promo-wide" data-ends="' . esc_attr( self::ENDS ) . '">';

                $html .= '<button type="button" class="awl-promo-close" title="Hide banner"><span class="screen-reader-text">Hide banner</span></button>';

                $html .= '<div class="awl-promo-text">';
                    $html .= '<span class="awl-promo-badge">Black Friday</span>';
                    $prefix = $offer['prefix'] ? '<b>' . esc_html( $offer['prefix'] ) . '</b> ' : '';
                    $html .= '<p class="awl-promo-title">' . $prefix . esc_html( $offer['title'] ) . '</p>';
                    $html .= '<ul class="awl-promo-list">';
                        foreach ( $offer['lines'] as $line ) {
                            // a line that opens with a number carries the offer, so the number
                            // is picked out: escaped first, marked up after, never the reverse
                            $html .= '<li>' . preg_replace( '/^(\d+%)/', '<b>$1</b>', esc_html( $line ) ) . '</li>';
                        }
                    $html .= '</ul>';
                $html .= '</div>';

                $html .= '<div class="awl-promo-actions">';
                    $html .= '<div class="awl-promo-timer" aria-hidden="true">';
                        foreach ( $counters as $name => $counter ) {
                            $html .= '<span class="awl-promo-counter"><b data-countdown="' . esc_attr( $name ) . '">' . ( $name === 'days' ? $counter[0] : sprintf( '%02d', $counter[0] ) ) . '</b><span>' . esc_html( $counter[1] ) . '</span></span>';
                        }
                    $html .= '</div>';
                    $html .= '<a class="awl-promo-cta" href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $offer['cta'] ) . ' &rarr;</a>';
                    $html .= '<p class="awl-promo-note">Sale ends December 2.</p>';
                $html .= '</div>';

            $html .= '</div>';

            return $html;

        }

        /*
         * Banner above the table on the list of labels
         */
        public function show_list_banner() {

            $screen = get_current_screen();

            if ( ! $screen || 'edit-awl-labels' !== $screen->id ) {
                return;
            }

            echo self::get_banner();

        }

        /*
         * Remember that this user has been on a screen with the banner
         */
        public function mark_page_seen() {

            if ( ! self::is_running() ) {
                return;
            }

            global $pagenow;

            $type = empty( $_GET['post_type'] ) ? '' : sanitize_key( wp_unslash( $_GET['post_type'] ) );

            // only the screen that carries the banner clears the dot: somebody who went
            // straight to editing a label has not been shown the offer yet
            if ( 'edit.php' !== $pagenow || 'awl-labels' !== $type ) {
                return;
            }

            $user_id = get_current_user_id();

            if ( ! $user_id || get_user_meta( $user_id, self::SEEN_META, true ) ) {
                return;
            }

            update_user_meta( $user_id, self::SEEN_META, 'true' );

        }

        /*
         * Count dot on the plugin menu, until the user has opened the labels
         */
        public function add_menu_dot() {

            global $menu;

            if ( ! self::is_running() || ! $menu ) {
                return;
            }

            $user_id = get_current_user_id();

            if ( ! $user_id || get_user_meta( $user_id, self::SEEN_META, true ) ) {
                return;
            }

            if ( ! current_user_can( self::capability() ) ) {
                return;
            }

            $dot = '<span class="awl-promo-menu-dot update-plugins" style="margin-left:5px;"><span class="awl-promo-menu-dot-count">1</span></span>';

            foreach ( $menu as $key => $item ) {

                if ( ! isset( $item[2] ) || 'edit.php?post_type=awl-labels' !== $item[2] ) {
                    continue;
                }

                $menu[ $key ][0] = '<div style="position:relative;white-space:nowrap;">' . $menu[ $key ][0] . '<span style="position:absolute;right:4px;">' . $dot . '</span></div>';

            }

        }

        /*
         * Forget, for this user, both the hidden banner and the seen menu dot
         */
        public function reset_user_flags() {

            if ( empty( $_GET['awl_promo'] ) || 'reset' !== sanitize_text_field( wp_unslash( $_GET['awl_promo'] ) ) ) {
                return;
            }

            if ( ! current_user_can( self::capability() ) ) {
                return;
            }

            $user_id = get_current_user_id();

            if ( ! $user_id ) {
                return;
            }

            delete_user_meta( $user_id, self::META_KEY );
            delete_user_meta( $user_id, self::SEEN_META );

        }

        /*
         * Hide banner for current user
         */
        public function hide_banner() {

            check_ajax_referer( 'awl_admin_ajax_nonce' );

            update_user_meta( get_current_user_id(), self::META_KEY, 'true' );

            wp_send_json_success( '1' );

        }

    }

endif;


// nothing here has anything to say on the front end: no object, no hooks
if ( is_admin() ) {
    new AWL_Admin_Black_Friday();
}
