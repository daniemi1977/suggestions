<?php
/**
 * Elementor Events List Widget
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Elementor_Events_List_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'wecp_events_list';
    }

    public function get_title() {
        return __('Events List', 'wp-event-calendar-pro');
    }

    public function get_icon() {
        return 'eicon-post-list';
    }

    public function get_categories() {
        return array('wecp-events');
    }

    public function get_keywords() {
        return array('event', 'list', 'schedule');
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('List Settings', 'wp-event-calendar-pro'),
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
                'default' => 10,
            )
        );

        $this->add_control(
            'show_past',
            array(
                'label' => __('Show Past Events', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wp-event-calendar-pro'),
                'label_off' => __('No', 'wp-event-calendar-pro'),
                'return_value' => 'yes',
                'default' => 'no',
            )
        );

        $this->add_control(
            'show_image',
            array(
                'label' => __('Show Featured Image', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wp-event-calendar-pro'),
                'label_off' => __('No', 'wp-event-calendar-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );

        $this->add_control(
            'show_excerpt',
            array(
                'label' => __('Show Excerpt', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wp-event-calendar-pro'),
                'label_off' => __('No', 'wp-event-calendar-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
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

        $this->add_control(
            'primary_color',
            array(
                'label' => __('Primary Color', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#3498db',
                'selectors' => array(
                    '{{WRAPPER}} .wecp-events-list' => '--wecp-primary-color: {{VALUE}}',
                ),
            )
        );

        $this->add_responsive_control(
            'spacing',
            array(
                'label' => __('Item Spacing', 'wp-event-calendar-pro'),
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
                    '{{WRAPPER}} .wecp-event-card' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $atts = array(
            'skin' => $settings['skin'],
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
        echo $calendar->events_list_shortcode($atts);

        echo '</div>';
    }
}
