<?php
/**
 * Elementor Calendar Widget
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Elementor_Calendar_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'wecp_calendar';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Event Calendar', 'wp-event-calendar-pro');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-calendar';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return array('wecp-events');
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return array('event', 'calendar', 'schedule', 'booking');
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Calendar Settings', 'wp-event-calendar-pro'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'calendar_view',
            array(
                'label' => __('View Type', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'month',
                'options' => array(
                    'month' => __('Month View', 'wp-event-calendar-pro'),
                    'week' => __('Week View', 'wp-event-calendar-pro'),
                    'list' => __('List View', 'wp-event-calendar-pro'),
                    'tile' => __('Tile View', 'wp-event-calendar-pro'),
                ),
            )
        );

        $this->add_control(
            'interaction_mode',
            array(
                'label' => __('Interaction Mode', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'lightbox',
                'options' => array(
                    'lightbox' => __('Lightbox Popup', 'wp-event-calendar-pro'),
                    'slide' => __('Slide Down', 'wp-event-calendar-pro'),
                    'page' => __('Event Page', 'wp-event-calendar-pro'),
                    'none' => __('None', 'wp-event-calendar-pro'),
                ),
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

        $this->end_controls_section();

        // Filter Section
        $this->start_controls_section(
            'filter_section',
            array(
                'label' => __('Filters', 'wp-event-calendar-pro'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
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
            'tag',
            array(
                'label' => __('Tag', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => WECP_Elementor_Integration::get_event_tags(),
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
            'limit',
            array(
                'label' => __('Events Limit', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 500,
                'step' => 1,
                'default' => 100,
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
                    '{{WRAPPER}} .wecp-calendar-wrapper' => '--wecp-primary-color: {{VALUE}}',
                ),
            )
        );

        $this->add_control(
            'accent_color',
            array(
                'label' => __('Accent Color', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#667eea',
                'selectors' => array(
                    '{{WRAPPER}} .wecp-calendar-wrapper' => '--wecp-accent-color: {{VALUE}}',
                ),
            )
        );

        $this->add_control(
            'text_color',
            array(
                'label' => __('Text Color', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#2c3e50',
                'selectors' => array(
                    '{{WRAPPER}} .wecp-calendar-wrapper' => '--wecp-text-color: {{VALUE}}',
                ),
            )
        );

        $this->add_control(
            'background_color',
            array(
                'label' => __('Background Color', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .wecp-calendar-wrapper' => '--wecp-bg-color: {{VALUE}}',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'heading_typography',
                'label' => __('Heading Typography', 'wp-event-calendar-pro'),
                'selector' => '{{WRAPPER}} .wecp-calendar-title, {{WRAPPER}} .wecp-event-title',
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'body_typography',
                'label' => __('Body Typography', 'wp-event-calendar-pro'),
                'selector' => '{{WRAPPER}} .wecp-event-excerpt, {{WRAPPER}} .wecp-event-time',
            )
        );

        $this->add_control(
            'border_radius',
            array(
                'label' => __('Border Radius', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 8,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .wecp-calendar-wrapper' => '--wecp-border-radius: {{SIZE}}{{UNIT}}',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'box_shadow',
                'label' => __('Box Shadow', 'wp-event-calendar-pro'),
                'selector' => '{{WRAPPER}} .wecp-calendar-wrapper',
            )
        );

        $this->end_controls_section();

        // Button Style Section
        $this->start_controls_section(
            'button_style_section',
            array(
                'label' => __('Buttons', 'wp-event-calendar-pro'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'button_color',
            array(
                'label' => __('Button Color', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#667eea',
                'selectors' => array(
                    '{{WRAPPER}} .wecp-btn-book' => 'background: {{VALUE}}',
                ),
            )
        );

        $this->add_control(
            'button_hover_color',
            array(
                'label' => __('Button Hover Color', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#764ba2',
                'selectors' => array(
                    '{{WRAPPER}} .wecp-btn-book:hover' => 'background: {{VALUE}}',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'button_typography',
                'label' => __('Button Typography', 'wp-event-calendar-pro'),
                'selector' => '{{WRAPPER}} .wecp-btn',
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        // Build shortcode attributes
        $atts = array(
            'view' => $settings['calendar_view'],
            'interaction' => $settings['interaction_mode'],
            'skin' => $settings['skin'],
            'category' => $settings['category'],
            'tag' => $settings['tag'],
            'show_past' => $settings['show_past'],
            'limit' => $settings['limit'],
        );

        // Add skin class to wrapper
        echo '<div class="wecp-elementor-widget wecp-skin-' . esc_attr($settings['skin']) . '">';

        // Enqueue skin-specific CSS
        wp_enqueue_style(
            'wecp-skin-' . $settings['skin'],
            WECP_PLUGIN_URL . 'assets/css/skins/skin-' . $settings['skin'] . '.css',
            array('wecp-calendar'),
            WECP_VERSION
        );

        // Render calendar
        $calendar = WECP_Event_Calendar::get_instance();
        echo $calendar->calendar_shortcode($atts);

        echo '</div>';
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <#
        var viewType = settings.calendar_view;
        var skin = settings.skin;
        #>
        <div class="wecp-elementor-widget wecp-skin-{{ skin }}">
            <div class="wecp-elementor-preview">
                <div class="wecp-preview-info">
                    <i class="eicon-calendar"></i>
                    <h3><?php _e('Event Calendar', 'wp-event-calendar-pro'); ?></h3>
                    <p><?php _e('View:', 'wp-event-calendar-pro'); ?> <strong>{{ viewType }}</strong></p>
                    <p><?php _e('Skin:', 'wp-event-calendar-pro'); ?> <strong>{{ skin }}</strong></p>
                    <p class="description"><?php _e('Calendar will be displayed here on the frontend', 'wp-event-calendar-pro'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}
