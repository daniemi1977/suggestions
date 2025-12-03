<?php
/**
 * Elementor Single Event Widget
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Elementor_Single_Event_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'wecp_single_event';
    }

    public function get_title() {
        return __('Single Event Display', 'wp-event-calendar-pro');
    }

    public function get_icon() {
        return 'eicon-single-post';
    }

    public function get_categories() {
        return array('wecp-events');
    }

    public function get_keywords() {
        return array('event', 'single', 'detail');
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Event Selection', 'wp-event-calendar-pro'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'event_id',
            array(
                'label' => __('Select Event', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => $this->get_events_list(),
                'description' => __('Select an event to display', 'wp-event-calendar-pro'),
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
            'show_booking',
            array(
                'label' => __('Show Booking Button', 'wp-event-calendar-pro'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );

        $this->end_controls_section();
    }

    private function get_events_list() {
        $events = get_posts(array(
            'post_type' => 'wecp_event',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));

        $options = array('' => __('Select Event', 'wp-event-calendar-pro'));

        foreach ($events as $event) {
            $options[$event->ID] = $event->post_title;
        }

        return $options;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $event_id = $settings['event_id'];

        if (!$event_id) {
            echo '<p>' . __('Please select an event', 'wp-event-calendar-pro') . '</p>';
            return;
        }

        echo '<div class="wecp-elementor-widget wecp-skin-' . esc_attr($settings['skin']) . '">';

        wp_enqueue_style(
            'wecp-skin-' . $settings['skin'],
            WECP_PLUGIN_URL . 'assets/css/skins/skin-' . $settings['skin'] . '.css',
            array('wecp-calendar'),
            WECP_VERSION
        );

        $event = get_post($event_id);
        $calendar = WECP_Event_Calendar::get_instance();

        // Use the event card rendering method
        echo '<div class="wecp-single-event-display">';
        // This would use a method from the calendar class to render single event
        // For now, we'll output basic info
        if ($event) {
            echo do_shortcode('[wecp_events_list limit="1" event_id="' . $event_id . '"]');
        }
        echo '</div>';

        echo '</div>';
    }
}
