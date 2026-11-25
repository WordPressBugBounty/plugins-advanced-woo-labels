<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWL_Admin_Page_Premium' ) ) :

    /**
     * Class for plugin admin ajax hooks
     */
    class AWL_Admin_Page_Premium {

        /*
         * Constructor
         */
        public function __construct() {
            
            $this->generate_content();

        }

        /*
         * Generate options fields
         */
        private function generate_content() {

            add_thickbox();

            echo '<div class="links">';
                echo  '<span class="links-title">' . __( 'Website Links:', 'advanced-woo-labels' ) . '</span>';
                echo '<ul>';
                    echo '<li><a target="_blank" href="https://kramakit.com/plugins/advanced-woo-labels/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=awl-pro-plugin">' . __( 'Plugin page', 'advanced-woo-labels' ) . '</a></li>';
                    echo '<li><a target="_blank" href="https://kramakit.com/guide-category/awl/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=awl-pro-plugin">' . __( 'Documentation', 'advanced-woo-labels' ) . '</a></li>';
                    echo '<li><a target="_blank" href="https://kramakit.com/plugins/advanced-woo-labels/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=awl-pro-plugin#pricing">' . __( 'Pricing', 'advanced-woo-labels' ) . '</a></li>';
                echo '</ul>';
            echo '</div>';


            $promo_deal = class_exists( 'AWL_Admin_Black_Friday' ) ? AWL_Admin_Black_Friday::get_card_badge() : '';

            echo '<div class="buy-premium-row">';

                echo '<div class="buy-premium">';
                    echo '<a href="https://kramakit.com/plugins/advanced-woo-labels/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=awl-pro-plugin" target="_blank">';
                        echo $promo_deal;
                        echo '<span class="desc">' . __( 'Upgrade to the', 'advanced-woo-labels' ) . '<b> ' . __( 'Premium plugin version', 'advanced-woo-labels' ) . '</b><br>' . __( 'to have all available features!', 'advanced-woo-labels' ) . '</span>';
                        echo '<ul>';
                            echo '<li>' . __( '30-day money back guarantee', 'advanced-woo-labels' ) . '</li>';
                            echo '<li>' . __( 'Priority support', 'advanced-woo-labels' ) . '</li>';
                        echo '</ul>';
                    echo '</a>';
                echo '</div>';

                echo '<div class="buy-premium buy-bundle">';
                    echo '<a href="https://kramakit.com/plugins/bundle/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=awl-pro-plugin" target="_blank">';
                        echo $promo_deal;
                        echo '<span class="desc">' . esc_html__( 'OR get the', 'advanced-woo-labels' ) . '<b> ' . esc_html__( 'KramaKit Bundle', 'advanced-woo-labels' ) . '</b><br>' . esc_html__( 'and save 30% on all our plugins!', 'advanced-woo-labels' ) . '</span>';
                        echo '<ul>';
                            echo '<li>' . esc_html__( 'All 3 plugins', 'advanced-woo-labels' ) . '</li>';
                            echo '<li>' . esc_html__( 'All future plugins', 'advanced-woo-labels' ) . '</li>';
                        echo '</ul>';
                    echo '</a>';
                echo '</div>';

            echo '</div>';

            echo '<div class="compare">';

                echo '<h3>' . esc_html__( 'Free vs. Pro', 'advanced-woo-labels' ) . '</h3>';
                echo '<p class="compare-desc">' . esc_html__( 'Everything in the free version, plus powerful Pro features for growing stores.', 'advanced-woo-labels' ) . '</p>';

                $features = array(
                    array(
                        'name' => __( 'Themes, page builders support', 'advanced-woo-labels' ),
                        'tip'  => __( 'We work hard to make labels compatible with almost all available themes. Also labels will be visible for all blocks created via such plugins like Elementor, WP Block Editor, Beaver Builder, Divi Builder, etc.', 'advanced-woo-labels' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Unlimited number of labels', 'advanced-woo-labels' ),
                        'tip'  => __( 'Show unlimited number of labels for each WooCommerce product. Optionally you can limit the maximal number of labels per product by special plugin settings.', 'advanced-woo-labels' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Labels groups', 'advanced-woo-labels' ),
                        'tip'  => __( 'Several labels on the same position for the same product combined in groups. Set group alignment, distance, max number of labels.', 'advanced-woo-labels' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Labels positions and align', 'advanced-woo-labels' ),
                        'tip'  => __( 'Display labels in two available positions: on product image or before product title. Align labels to the left, center or right relative to product content ( top, center, bottom additionally for product image ).', 'advanced-woo-labels' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Labels display conditions', 'advanced-woo-labels' ),
                        'tip'  => __( 'Use powerful display conditions to choose for what product/page/user labels must be visible. For example - show labels only for products with certain categories and attributes.', 'advanced-woo-labels' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Text and text variables', 'advanced-woo-labels' ),
                        'tip'  => __( 'Set any custom text for your labels. Use special text variables to show important product information like Price, Sale Price, Discount Percentage, etc.', 'advanced-woo-labels' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Styling options', 'advanced-woo-labels' ),
                        'tip'  => __( 'Create your unique label view by using styling options to change label background color, text color, font, margins, paddings, borders, shadows.', 'advanced-woo-labels' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Different labels shapes', 'advanced-woo-labels' ),
                        'tip'  => __( 'Choose from several predefined label templates.', 'advanced-woo-labels' ),
                        'free' => true,
                    ),
                    array(
                        'name' => __( 'Additional labels display conditions', 'advanced-woo-labels' ),
                        'tip'  => __( 'Use even more label display conditions based on current page, user, product or date.', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Additional text variables', 'advanced-woo-labels' ),
                        'tip'  => __( 'Use some additional text variables to make labels text even more attractive and product-specific.', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Image labels', 'advanced-woo-labels' ),
                        'tip'  => __( 'Use one of predefined images as a product label or upload your custom one. Use png/jpg/gif or SVG images.', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Animation options', 'advanced-woo-labels' ),
                        'tip'  => __( 'Set unique animation for each label with different customizable parameters.', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Emojis support', 'advanced-woo-labels' ),
                        'tip'  => __( 'Use any emoji inside the text label. Mix them with text variables, plain text and other emojis.', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Labels styles: borders and shadows', 'advanced-woo-labels' ),
                        'tip'  => __( 'More label styling options: set label shadow and border ( size and color ).', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Additional label shapes', 'advanced-woo-labels' ),
                        'tip'  => __( 'New cool templates for your text labels to attract users\' attention.', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'Label custom links', 'advanced-woo-labels' ),
                        'tip'  => __( 'Add any custom link inside your label. So now your product labels not just tell users some important information but can contain some useful links.', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'ACF plugin support', 'advanced-woo-labels' ),
                        'tip'  => __( 'Advanced integration with Advanced Custom Fields plugin. Show value of any ACF field inside the label. Also set label display conditions based on ACF fields values.', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                    array(
                        'name' => __( 'WCFM, Dokan, MultiVendorX, WC Vendors plugins support', 'advanced-woo-labels' ),
                        'tip'  => __( 'Built-in integration with WCFM Multivendor Marketplace, Dokan WooCommerce Multivendor Marketplace, WC Vendors and MultiVendorX plugins. Show vendor related data inside product labels and create special labels display conditions based on these data.', 'advanced-woo-labels' ),
                        'free' => false,
                    ),
                );

                $check = '<span class="check"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></span>';
                $dash = '<span class="dash">-</span>';

                echo '<table class="compare-table">';
                    echo '<thead>';
                        echo '<tr>';
                            echo '<th>' . esc_html__( 'Feature', 'advanced-woo-labels' ) . '</th>';
                            echo '<th>' . esc_html__( 'Free', 'advanced-woo-labels' ) . '</th>';
                            echo '<th class="pro-col">' . esc_html__( 'Pro', 'advanced-woo-labels' ) . '</th>';
                        echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                        foreach ( $features as $feature ) {
                            echo '<tr>';
                                echo '<td><span class="feat">' . esc_html( $feature['name'] ) . '<span class="feat-i" aria-hidden="true"></span><span class="feat-tip">' . esc_html( $feature['tip'] ) . '</span></span></td>';
                                echo '<td>' . ( $feature['free'] ? $check : $dash ) . '</td>';
                                echo '<td>' . $check . '</td>';
                            echo '</tr>';
                        }
                    echo '</tbody>';
                echo '</table>';

                echo '<div class="compare-ctas">';
                    echo '<a class="compare-learn" href="https://kramakit.com/plugins/advanced-woo-labels/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=awl-pro-plugin" target="_blank">' . esc_html__( 'Learn more', 'advanced-woo-labels' ) . '</a>';
                    echo '<a class="compare-buy" href="https://kramakit.com/plugins/advanced-woo-labels/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=awl-pro-plugin#pricing" target="_blank">' . esc_html__( 'Get Pro Version', 'advanced-woo-labels' ) . ' &rarr;</a>';
                echo '</div>';

                echo '<p class="compare-guarantee">';
                    echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
                    echo esc_html__( '30-day money-back guarantee, no questions asked', 'advanced-woo-labels' );
                echo '</p>';

            echo '</div>';


            echo '<div class="screenshots">';

                echo '<h3>' . __( 'Screenshots', 'advanced-woo-labels' ) . '</h3>';

                echo '<div class="screenshots-section">';

                    echo '<div class="screenshots-list">';

                        echo '<div class="screen">';
                            echo '<a href="#TB_inline?&width=1200&height=720&inlineId=my-content-id-1" class="thickbox">';
                                echo '<span id="my-content-id-1" >';
                                    echo '<img class="awl-screen" src="' . AWL_URL . '/assets/img/pro/product-cond-pro.png' . '">';
                                echo '<span>';
                            echo '</a>';
                        echo '</div>';

                        echo '<div class="screen">';
                             echo '<a href="#TB_inline?&width=1200&height=540&inlineId=my-content-id-2" class="thickbox">';
                                echo '<span id="my-content-id-2" >';
                                    echo '<img class="awl-screen" src="' . AWL_URL . '/assets/img/pro/user-cond-pro.png' . '">';
                                echo '<span>';
                            echo '</a>';
                        echo '</div>';

                        echo '<div class="screen">';
                             echo '<a href="#TB_inline?&width=1200&height=560&inlineId=my-content-id-3" class="thickbox">';
                                echo '<span id="my-content-id-3" >';
                                    echo '<img class="awl-screen" src="' . AWL_URL . '/assets/img/pro/page-cond-pro.png' . '">';
                                echo '<span>';
                            echo '</a>';
                        echo '</div>';

                        echo '<div class="screen">';
                             echo '<a href="#TB_inline?&width=1020&height=800&inlineId=my-content-id-8" class="thickbox">';
                                echo '<span id="my-content-id-8" >';
                                    echo '<img class="awl-screen" src="' . AWL_URL . '/assets/img/pro/image-label-pro.png' . '">';
                                echo '<span>';
                            echo '</a>';
                        echo '</div>';

                        echo '<div class="screen">';
                             echo '<a href="#TB_inline?&width=1200&height=450&inlineId=my-content-id-4" class="thickbox">';
                                echo '<span id="my-content-id-4" >';
                                    echo '<img class="awl-screen" src="' . AWL_URL . '/assets/img/pro/date-cond-pro.png' . '">';
                                echo '<span>';
                            echo '</a>';
                        echo '</div>';

                        echo '<div class="screen">';
                             echo '<a href="#TB_inline?&width=1200&height=900&inlineId=my-content-id-5" class="thickbox">';
                                echo '<span id="my-content-id-5" >';
                                    echo '<img class="awl-screen" src="' . AWL_URL . '/assets/img/pro/text-vars-pro.png' . '">';
                                echo '<span>';
                            echo '</a>';
                        echo '</div>';

                        echo '<div class="screen">';
                             echo '<a href="#TB_inline?&width=1200&height=820&inlineId=my-content-id-6" class="thickbox">';
                                echo '<span id="my-content-id-6" >';
                                    echo '<img class="awl-screen" src="' . AWL_URL . '/assets/img/pro/styling-pro.png' . '">';
                                echo '<span>';
                            echo '</a>';
                        echo '</div>';

                        echo '<div class="screen">';
                             echo '<a href="#TB_inline?&width=1200&height=550&inlineId=my-content-id-7" class="thickbox">';
                                echo '<span id="my-content-id-7" >';
                                    echo '<img class="awl-screen" src="' . AWL_URL . '/assets/img/pro/templates-pro.png' . '">';
                                echo '<span>';
                            echo '</a>';
                        echo '</div>';

                    echo '</div>';

                echo '</div>';

            echo '</div>';

            echo '<div class="faq">';

                echo '<h3>' . __( 'Frequently Asked Questions', 'advanced-woo-labels' ) . '</h3>';

                echo '<div class="faq-item">';
                    echo '<h4 class="question">';
                        echo __( 'Do you offer refunds?', 'advanced-woo-labels' );
                    echo '</h4>';
                    echo '<div class="answer">';
                        echo __( 'If you\'re not completely happy with your purchase and we\'re unable to resolve the issue, let us know and we\'ll refund the full purchase price. Refunds can be processed within 30 days of the original purchase.', 'advanced-woo-labels' );
                    echo '</div>';
                echo '</div>';

                echo '<div class="faq-item">';
                    echo '<h4 class="question">';
                        echo __( 'What payment methods do you accept?', 'advanced-woo-labels' );
                    echo '</h4>';
                    echo '<div class="answer">';
                        echo __( 'Checkout is powered by FastSpring. It supports major credit and debit cards, PayPal, and a variety of other mainstream payment methods, so there’s plenty to pick from.', 'advanced-woo-labels' );
                    echo '</div>';
                echo '</div>';

                echo '<div class="faq-item">';
                    echo '<h4 class="question">';
                        echo __( 'Do you offer support if I need help?', 'advanced-woo-labels' );
                    echo '</h4>';
                    echo '<div class="answer">';
                        echo __( 'Yes! You will benefit of our full support for any issues you have with this plugin.', 'advanced-woo-labels' );
                    echo '</div>';
                echo '</div>';

                echo '<div class="faq-item">';
                    echo '<h4 class="question">';
                        echo __( 'I have other pre-sale questions, can you help?', 'advanced-woo-labels' );
                    echo '</h4>';
                    echo '<div class="answer">';
                        echo __( 'Yes! You can ask us any question through our', 'advanced-woo-labels' ) . ' <a href="https://kramakit.com/contact/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=awl-pro-plugin" target="_blank">' . __( 'contact form.', 'advanced-woo-labels' ) . '</a>';
                    echo '</div>';
                echo '</div>';

            echo '</div>';

            echo '<div class="buy-premium">';
                echo '<a href="https://kramakit.com/plugins/advanced-woo-labels/?utm_source=plugin&utm_medium=premium-tab&utm_campaign=awl-pro-plugin" target="_blank">';
                    echo '<span class="desc">' . __( 'Upgrade to the', 'advanced-woo-labels' ) . '<b> ' . __( 'Premium plugin version', 'advanced-woo-labels' ) . '</b><br>' . __( 'to have all available features!', 'advanced-woo-labels' ) . '</span>';
                echo '</a>';
            echo '</div>';

        }
        
    }

endif;
