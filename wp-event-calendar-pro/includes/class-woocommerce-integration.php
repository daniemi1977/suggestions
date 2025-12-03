<?php
/**
 * WooCommerce Integration
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_WooCommerce_Integration {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Sync event with WooCommerce product
        add_action('wecp_sync_event_product', array($this, 'sync_event_product'));
        add_action('save_post_wecp_event', array($this, 'auto_sync_product'), 20);

        // Add custom data to cart
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);

        // Save order meta
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_meta'), 10, 4);

        // Generate QR code after order completion
        add_action('woocommerce_order_status_completed', array($this, 'generate_qr_codes'));

        // Add event info to order emails
        add_action('woocommerce_order_item_meta_start', array($this, 'display_order_item_event_info'), 10, 3);

        // Stock management hooks
        add_filter('woocommerce_product_get_stock_quantity', array($this, 'adjust_stock_quantity'), 10, 2);
        add_filter('woocommerce_product_variation_get_stock_quantity', array($this, 'adjust_stock_quantity'), 10, 2);
    }

    /**
     * Sync event with WooCommerce product
     */
    public function sync_event_product($event_id) {
        $enable_booking = get_post_meta($event_id, '_wecp_enable_booking', true);

        if ($enable_booking !== '1') {
            return;
        }

        $wc_product_id = get_post_meta($event_id, '_wecp_wc_product_id', true);
        $event = get_post($event_id);

        if (!$event) {
            return;
        }

        // Get event data
        $ticket_price = get_post_meta($event_id, '_wecp_ticket_price', true) ?: 0;
        $max_attendees = get_post_meta($event_id, '_wecp_max_attendees', true);
        $start_date = get_post_meta($event_id, '_wecp_start_date', true);

        // Create or update product
        if ($wc_product_id && get_post($wc_product_id)) {
            // Update existing product
            $product = wc_get_product($wc_product_id);
        } else {
            // Create new product
            $product = new WC_Product_Simple();
            $product->set_catalog_visibility('hidden'); // Hide from shop
        }

        // Set product data
        $product->set_name($event->post_title . ' - ' . __('Event Ticket', 'wp-event-calendar-pro'));
        $product->set_description($event->post_content);
        $product->set_short_description($event->post_excerpt);
        $product->set_regular_price($ticket_price);
        $product->set_sold_individually(false);
        $product->set_virtual(true);
        $product->set_downloadable(false);

        // Set stock management
        if ($max_attendees) {
            $product->set_manage_stock(true);
            $product->set_stock_quantity(intval($max_attendees));
            $product->set_stock_status('instock');
            $product->set_backorders('no');
        } else {
            $product->set_manage_stock(false);
            $product->set_stock_status('instock');
        }

        // Set featured image
        if (has_post_thumbnail($event_id)) {
            $product->set_image_id(get_post_thumbnail_id($event_id));
        }

        // Save product
        $product_id = $product->save();

        // Update meta linking product to event
        update_post_meta($product_id, '_wecp_event_id', $event_id);
        update_post_meta($product_id, '_wecp_event_date', $start_date);
        update_post_meta($event_id, '_wecp_wc_product_id', $product_id);

        return $product_id;
    }

    /**
     * Auto sync product when event is saved
     */
    public function auto_sync_product($event_id) {
        $enable_booking = get_post_meta($event_id, '_wecp_enable_booking', true);

        if ($enable_booking === '1') {
            $this->sync_event_product($event_id);
        }
    }

    /**
     * Add custom data when adding to cart
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $event_id = get_post_meta($product_id, '_wecp_event_id', true);

        if ($event_id) {
            $cart_item_data['wecp_event_id'] = $event_id;
            $cart_item_data['wecp_event_date'] = get_post_meta($event_id, '_wecp_start_date', true);
            $cart_item_data['wecp_event_time'] = get_post_meta($event_id, '_wecp_start_time', true);
            $cart_item_data['wecp_venue_name'] = get_post_meta($event_id, '_wecp_venue_name', true);
        }

        return $cart_item_data;
    }

    /**
     * Display custom data in cart
     */
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['wecp_event_id'])) {
            $event_id = $cart_item['wecp_event_id'];

            if (isset($cart_item['wecp_event_date'])) {
                $item_data[] = array(
                    'name' => __('Event Date', 'wp-event-calendar-pro'),
                    'value' => date_i18n(get_option('date_format'), strtotime($cart_item['wecp_event_date'])),
                );
            }

            if (isset($cart_item['wecp_event_time'])) {
                $item_data[] = array(
                    'name' => __('Event Time', 'wp-event-calendar-pro'),
                    'value' => $cart_item['wecp_event_time'],
                );
            }

            if (isset($cart_item['wecp_venue_name'])) {
                $item_data[] = array(
                    'name' => __('Venue', 'wp-event-calendar-pro'),
                    'value' => $cart_item['wecp_venue_name'],
                );
            }
        }

        return $item_data;
    }

    /**
     * Save order item meta
     */
    public function save_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['wecp_event_id'])) {
            $item->add_meta_data('_wecp_event_id', $values['wecp_event_id'], true);
            $item->add_meta_data('_wecp_event_date', $values['wecp_event_date'], true);
            $item->add_meta_data('_wecp_event_time', $values['wecp_event_time'], true);
            $item->add_meta_data('_wecp_venue_name', $values['wecp_venue_name'], true);
        }
    }

    /**
     * Generate QR codes for tickets
     */
    public function generate_qr_codes($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item_id => $item) {
            $event_id = $item->get_meta('_wecp_event_id');

            if ($event_id) {
                $qr_generator = WECP_QR_Code_Generator::get_instance();
                $quantity = $item->get_quantity();

                // Generate QR code for each ticket
                for ($i = 0; $i < $quantity; $i++) {
                    $ticket_code = $this->generate_ticket_code($order_id, $item_id, $i);
                    $qr_code_path = $qr_generator->generate($ticket_code, $order_id, $item_id, $i);

                    // Save ticket code
                    wc_add_order_item_meta($item_id, '_wecp_ticket_code_' . $i, $ticket_code);
                    wc_add_order_item_meta($item_id, '_wecp_qr_code_' . $i, $qr_code_path);
                }
            }
        }

        // Add order note
        $order->add_order_note(__('Event tickets and QR codes generated.', 'wp-event-calendar-pro'));
    }

    /**
     * Generate unique ticket code
     */
    private function generate_ticket_code($order_id, $item_id, $index) {
        return strtoupper(md5($order_id . '-' . $item_id . '-' . $index . '-' . time()));
    }

    /**
     * Display event info in order item meta
     */
    public function display_order_item_event_info($item_id, $item, $order) {
        $event_id = $item->get_meta('_wecp_event_id');

        if ($event_id) {
            $event_date = $item->get_meta('_wecp_event_date');
            $event_time = $item->get_meta('_wecp_event_time');
            $venue_name = $item->get_meta('_wecp_venue_name');

            echo '<div class="wecp-order-event-info">';

            if ($event_date) {
                echo '<strong>' . __('Event Date:', 'wp-event-calendar-pro') . '</strong> ';
                echo date_i18n(get_option('date_format'), strtotime($event_date));

                if ($event_time) {
                    echo ' @ ' . esc_html($event_time);
                }
                echo '<br>';
            }

            if ($venue_name) {
                echo '<strong>' . __('Venue:', 'wp-event-calendar-pro') . '</strong> ';
                echo esc_html($venue_name) . '<br>';
            }

            echo '</div>';
        }
    }

    /**
     * Adjust stock quantity based on event capacity
     */
    public function adjust_stock_quantity($stock, $product) {
        $event_id = get_post_meta($product->get_id(), '_wecp_event_id', true);

        if ($event_id) {
            $max_attendees = get_post_meta($event_id, '_wecp_max_attendees', true);

            if ($max_attendees) {
                $current_bookings = $this->get_event_bookings_count($event_id);
                return max(0, intval($max_attendees) - $current_bookings);
            }
        }

        return $stock;
    }

    /**
     * Get event bookings count
     */
    private function get_event_bookings_count($event_id) {
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
}
