<?php
/**
 * Elementor Events Tile Widget
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Elementor_Events_Tile_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'wecp_events_tile';
    }

    public function get_title() {
        return __('Events Tile Grid', 'wp-event-calendar-pro');
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return array('wecp-events');
    }

    public function get_keywords() {
        return array('event', 'tile', 'grid', 'masonry');
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Grid Settings', 'wp-event-calendar-pro'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'skin',
            array(
                'label' => __('Theme/Skin', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'default',
                'options' => WECP_Elementor_Integration::get_available_skins(),
            )
        );

        $this->add_responsive_control(
            'columns',
            array(
                'label' => __('Columns', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .wecp-events-tiles' => 'grid-template-columns: repeat({{VALUE}}, 1fr)',
                ),
            )
        );

        $this->add_control(
            'category',
            array(
                'label' => __('Category', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => WECP_Elementor_Integration::get_event_categories(),
            )
        );

        $this->add_control(
            'limit',
            array(
                'label' => __('Number of Events', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'default' => 12,
            )
        );

        $this->add_control(
            'show_past',
            array(
                'label' => __('Show Past Events', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
            )
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __('Style', 'wp-event-calendar-pro'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'column_gap',
            array(
                'label' => __('Column Gap', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 20,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .wecp-events-tiles' => 'gap: {{SIZE}}{{UNIT}}',
                ),
            )
        );

        $this->add_control(
            'card_background',
            array(
                'label' => __('Card Background', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .wecp-event-card' => 'background-color: {{VALUE}}',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'card_border',
                'label' => __('Card Border', 'wp-event-calendar-pro'),
                'selector' => '{{WRAPPER}} .wecp-event-card',
            )
        );

        $this->add_control(
            'card_border_radius',
            array(
                'label' => __('Border Radius', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .wecp-event-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'card_shadow',
                'label' => __('Card Shadow', 'wp-event-calendar-pro'),
                'selector' => '{{WRAPPER}} .wecp-event-card',
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $atts = array(
            'skin' => $settings['skin'],
            'columns' => $settings['columns'],
            'category' => $settings['category'],
            'limit' => $settings['limit'],
            'show_past' => $settings['show_past'],
        );

        echo '<div class="wecp-elementor-widget wecp-skin-' . esc_attr($settings['skin']) . '">';

        wp_enqueue_style(
            'wecp-skin-' . $settings['skin'],
            WECP_PLUGIN_URL . 'assets/css/skins/skin-' . $settings['skin'] . '.css',
            array('wecp-calendar'),
            WECP_VERSION
        );

        $calendar = WECP_Event_Calendar::get_instance();
        echo $calendar->events_tile_shortcode($atts);

        echo '</div>';
    }
}
