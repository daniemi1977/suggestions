<?php
/**
 * Booking Manager
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Booking_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_wecp_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_wecp_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_wecp_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_wecp_check_availability', array($this, 'ajax_check_availability'));
    }

    /**
     * AJAX: Add event to cart
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('wecp_nonce', 'nonce');

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'wp-event-calendar-pro')));
        }

        // Get WooCommerce product ID
        $product_id = get_post_meta($event_id, '_wecp_wc_product_id', true);

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Event booking not available', 'wp-event-calendar-pro')));
        }

        // Check availability
        $available = $this->check_event_availability($event_id, $quantity);

        if (!$available) {
            wp_send_json_error(array('message' => __('Sorry, not enough tickets available', 'wp-event-calendar-pro')));
        }

        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);

        if ($cart_item_key) {
            wp_send_json_success(array(
                'message' => __('Event added to cart', 'wp-event-calendar-pro'),
                'cart_url' => wc_get_cart_url(),
                'cart_count' => WC()->cart->get_cart_contents_count(),
            ));
        } else {
            wp_send_json_error(array('message' => __('Could not add to cart', 'wp-event-calendar-pro')));
        }
    }

    /**
     * AJAX: Check event availability
     */
    public function ajax_check_availability() {
        check_ajax_referer('wecp_nonce', 'nonce');

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'wp-event-calendar-pro')));
        }

        $max_attendees = get_post_meta($event_id, '_wecp_max_attendees', true);
        $current_bookings = $this->get_event_bookings($event_id);
        $available_spots = $max_attendees ? intval($max_attendees) - $current_bookings : -1;

        wp_send_json_success(array(
            'available' => ($available_spots === -1 || $available_spots >= $quantity),
            'available_spots' => $available_spots,
            'current_bookings' => $current_bookings,
            'max_attendees' => $max_attendees ?: __('Unlimited', 'wp-event-calendar-pro'),
        ));
    }

    /**
     * Check if event has available spots
     */
    public function check_event_availability($event_id, $quantity = 1) {
        $max_attendees = get_post_meta($event_id, '_wecp_max_attendees', true);

        // Unlimited capacity
        if (!$max_attendees) {
            return true;
        }

        $current_bookings = $this->get_event_bookings($event_id);
        $available_spots = intval($max_attendees) - $current_bookings;

        return $available_spots >= $quantity;
    }

    /**
     * Get current bookings count for an event
     */
    public function get_event_bookings($event_id) {
        $wc_product_id = get_post_meta($event_id, '_wecp_wc_product_id', true);

        if (!$wc_product_id) {
            return 0;
        }

        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(oim.meta_value)
            FROM {$wpdb->prefix}woocommerce_order_items AS oi
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim ON oi.order_item_id = oim.order_item_id
            LEFT JOIN {$wpdb->prefix}posts AS p ON oi.order_id = p.ID
            WHERE oim.meta_key = '_qty'
            AND oim.order_item_id IN (
                SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta
                WHERE meta_key = '_product_id' AND meta_value = %d
            )
            AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        ", $wc_product_id));

        return intval($count);
    }

    /**
     * Get event attendees list
     */
    public function get_event_attendees($event_id) {
        $wc_product_id = get_post_meta($event_id, '_wecp_wc_product_id', true);

        if (!$wc_product_id) {
            return array();
        }

        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT p.ID as order_id, p.post_date, oim_qty.meta_value as quantity,
                   pm_first.meta_value as first_name, pm_last.meta_value as last_name,
                   pm_email.meta_value as email
            FROM {$wpdb->prefix}woocommerce_order_items AS oi
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim_prod ON oi.order_item_id = oim_prod.order_item_id
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim_qty ON oi.order_item_id = oim_qty.order_item_id
            LEFT JOIN {$wpdb->prefix}posts AS p ON oi.order_id = p.ID
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_first ON p.ID = pm_first.post_id
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_last ON p.ID = pm_last.post_id
            LEFT JOIN {$wpdb->prefix}postmeta AS pm_email ON p.ID = pm_email.post_id
            WHERE oim_prod.meta_key = '_product_id'
            AND oim_prod.meta_value = %d
            AND oim_qty.meta_key = '_qty'
            AND pm_first.meta_key = '_billing_first_name'
            AND pm_last.meta_key = '_billing_last_name'
            AND pm_email.meta_key = '_billing_email'
            AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
            ORDER BY p.post_date ASC
        ", $wc_product_id));

        return $results;
    }
}
