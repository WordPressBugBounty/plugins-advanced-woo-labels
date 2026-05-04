<?php
/**
 * Versions capability
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AWL_Versions' ) ) :

    /**
     * Class for plugin search
     */
    class AWL_Versions {

        /**
         * Return a singleton instance of the current class
         *
         * @return object
         */
        public static function factory() {
            static $instance = false;

            if ( ! $instance ) {
                $instance = new self();
                $instance->setup();
            }

            return $instance;
        }

        /**
         * Placeholder
         */
        public function __construct() {}

        /**
         * Setup actions and filters for all things settings
         */
        public function setup() {

            add_filter( 'awl_settings_field_font_style', array( $this, 'change_old_style_field' ), 10, 2 );
            add_filter( 'awl_settings_field_font_weight', array( $this, 'change_old_style_field' ), 10, 2 );

            $current_version = get_option( 'awl_plugin_ver' );

            if ( $current_version ) {

                if ( version_compare( $current_version, '1.45', '<' ) ) {
                    $settings = get_option( 'awl_settings' );
                    if ( $settings ) {
                        if ( ! isset( $settings['display_hooks'] ) ) {
                            $settings['display_hooks'] = 'true';
                        }
                        update_option( 'awl_settings', $settings );
                    }
                }

                if ( version_compare( $current_version, '1.75', '<' ) ) {
                    $settings = get_option( 'awl_settings' );
                    if ( $settings ) {
                        if ( ! isset( $settings['hooks_relation'] ) ) {
                            $settings['hooks_relation'] = 'additional';
                        }
                        if ( isset( $settings['hooks'] ) && is_array( $settings['hooks'] ) ) {
                            $hooks = $settings['hooks'];
                            foreach ( $hooks as $hooks_id => $hooks_args ) {
                                if ( isset( $hooks_args['custom'] ) && $hooks_args['custom'] && in_array( $hooks_args['hook'], array( 'custom action', 'custom filter' ) ) ) {
                                    $type = $hooks_args['hook'] === 'custom filter' ? 'filter' : 'action';
                                    $settings['hooks'][$hooks_id]['hook'] = 'custom';
                                    $settings['hooks'][$hooks_id]['type'] = $type;
                                }
                            }
                        }
                        update_option( 'awl_settings', $settings );
                    }
                }

                if ( version_compare( $current_version, '1.88', '<' ) ) {
                    $settings = get_option( 'awl_settings' );
                    if ( $settings ) {
                        if ( ! isset( $settings['show_loops'] ) ) {
                            $settings['show_loops'] = 'true';
                        }
                        update_option( 'awl_settings', $settings );
                    }
                }

                if ( version_compare( $current_version, '2.42', '<' ) ) {
                    $this->update_multiselect_condition_rules();
                }
                
            }

            if ( $current_version && $current_version !== AWL_VERSION ) {
                do_action( 'awl_new_plugin_version_released', AWL_VERSION );
            }
            
            update_option( 'awl_plugin_ver', AWL_VERSION );

        }

        /*
         * Since version 1.56 - update font_style and font_weight options
         */
        public function change_old_style_field( $field, $label_values ) {

            if ( isset( $label_values['settings'] ) && isset( $label_values['settings']['font_style'] ) ) {

                switch ($label_values['settings']['font_style']) {

                    case 'bold_italic':

                        if ($field['id'] === 'font_style') {

                            $field['value'] = 'italic';
                            $choices = array();

                            if ( isset( $field['choices'] ) ) {
                                $choices['italic'] = $field['choices']['italic'];
                                foreach ($field['choices'] as $field_choice_id => $field_choice_val) {
                                    if (!isset($choices[$field_choice_id])) {
                                        $choices[$field_choice_id] = $field_choice_val;
                                    }
                                }
                            }

                            $field['choices'] = $choices;

                        }
                        if ($field['id'] === 'font_weight') {
                            $field['value'] = '700';
                        }

                        break;

                    case 'bold':

                        if ($field['id'] === 'font_style') {
                            $field['value'] = 'normal';
                        }
                        if ($field['id'] === 'font_weight') {
                            $field['value'] = '700';
                        }

                        break;

                }

            }

            return $field;

        }

        /*
         * Since version 2.42 - update multiselect condition values
         */
        private function update_multiselect_condition_rules() {

            $labels = get_posts( array(
                'post_type'              => 'awl-labels',
                'post_status'            => 'publish',
                'posts_per_page'         => -1,
                'update_post_term_cache' => false,
                'fields'                 => 'ids',
                'no_found_rows'          => 1,
                'ignore_sticky_posts'    => true,
                'suppress_filters'       => true,
                'lang'                   => '',
                '_is_awl_query'          => true,
            ) );

            if ( ! $labels ) {
                return;
            }

            foreach ( $labels as $label_id ) {
                $label_options = get_post_meta( $label_id, '_awl_label', true );

                if ( ! $label_options || ! isset( $label_options['conditions'] ) || ! is_array( $label_options['conditions'] ) ) {
                    continue;
                }

                $updated = false;

                foreach ( $label_options['conditions'] as $group_id => $group_rules ) {
                    if ( ! is_array( $group_rules ) ) {
                        continue;
                    }

                    foreach ( $group_rules as $rule_id => $rule_values ) {
                        if ( ! isset( $rule_values['param'] ) ) {
                            continue;
                        }

                        $rule = AWL_Admin_Options::include_rule_by_id( $rule_values['param'] );
                        $is_multiple = isset( $rule['multiple'] ) && $rule['multiple'];

                        if ( ! $is_multiple ) {
                            continue;
                        }

                        if ( isset( $rule_values['operator'] ) ) {
                            if ( 'equal' === $rule_values['operator'] ) {
                                $label_options['conditions'][ $group_id ][ $rule_id ]['operator'] = 'in_list';
                                $updated = true;
                            } elseif ( 'not_equal' === $rule_values['operator'] ) {
                                $label_options['conditions'][ $group_id ][ $rule_id ]['operator'] = 'not_in_list';
                                $updated = true;
                            }
                        }

                        if ( isset( $rule_values['value'] ) && ! is_array( $rule_values['value'] ) ) {
                            $value = sanitize_text_field( $rule_values['value'] );
                            $label_options['conditions'][ $group_id ][ $rule_id ]['value'] = '' !== $value ? array( $value ) : array();
                            $updated = true;
                        }
                    }
                }

                if ( $updated ) {
                    update_post_meta( $label_id, '_awl_label', $label_options );
                }
            }

        }

    }

endif;

add_action( 'admin_init', 'AWL_Versions::factory' );