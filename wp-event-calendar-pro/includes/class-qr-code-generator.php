<?php
/**
 * QR Code Generator
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_QR_Code_Generator {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Verificazione QR code
        add_action('wp_ajax_wecp_verify_ticket', array($this, 'verify_ticket'));
        add_action('wp_ajax_nopriv_wecp_verify_ticket', array($this, 'verify_ticket'));

        // Shortcode per scanner
        add_shortcode('wecp_ticket_scanner', array($this, 'ticket_scanner_shortcode'));
    }

    /**
     * Generate QR code for ticket
     *
     * Uses Google Charts API as a simple solution
     * For production, consider using a library like chillerlan/php-qrcode
     */
    public function generate($ticket_code, $order_id, $item_id, $index) {
        $upload_dir = wp_upload_dir();
        $qr_dir = $upload_dir['basedir'] . '/wecp-qr-codes/';

        // Create directory if it doesn't exist
        if (!file_exists($qr_dir)) {
            wp_mkdir_p($qr_dir);
        }

        $filename = 'ticket-' . $order_id . '-' . $item_id . '-' . $index . '.png';
        $filepath = $qr_dir . $filename;

        // Generate QR code URL using Google Charts API
        $qr_url = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($ticket_code) . '&choe=UTF-8';

        // Download and save QR code image
        $image_data = file_get_contents($qr_url);

        if ($image_data) {
            file_put_contents($filepath, $image_data);
            return $upload_dir['baseurl'] . '/wecp-qr-codes/' . $filename;
        }

        return false;
    }

    /**
     * Verify ticket code
     */
    public function verify_ticket() {
        check_ajax_referer('wecp_nonce', 'nonce');

        $ticket_code = isset($_POST['ticket_code']) ? sanitize_text_field($_POST['ticket_code']) : '';

        if (!$ticket_code) {
            wp_send_json_error(array('message' => __('Invalid ticket code', 'wp-event-calendar-pro')));
        }

        // Search for ticket in order meta
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare("
            SELECT oim.order_item_id, oim.meta_value as ticket_code,
                   oim_event.meta_value as event_id,
                   oi.order_id
            FROM {$wpdb->prefix}woocommerce_order_itemmeta AS oim
            LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON oim.order_item_id = oi.order_item_id
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim_event ON oi.order_item_id = oim_event.order_item_id
            WHERE oim.meta_key LIKE '_wecp_ticket_code_%'
            AND oim.meta_value = %s
            AND oim_event.meta_key = '_wecp_event_id'
            LIMIT 1
        ", $ticket_code));

        if (!$result) {
            wp_send_json_error(array('message' => __('Ticket not found', 'wp-event-calendar-pro')));
        }

        // Get order details
        $order = wc_get_order($result->order_id);
        $event = get_post($result->event_id);

        // Check if ticket was already checked in
        $checked_in = get_post_meta($result->order_id, '_wecp_checked_in_' . $ticket_code, true);

        if ($checked_in) {
            wp_send_json_success(array(
                'valid' => true,
                'already_checked_in' => true,
                'checked_in_time' => $checked_in,
                'message' => __('This ticket was already checked in', 'wp-event-calendar-pro'),
                'event_title' => $event->post_title,
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            ));
        }

        // Mark as checked in
        update_post_meta($result->order_id, '_wecp_checked_in_' . $ticket_code, current_time('mysql'));

        wp_send_json_success(array(
            'valid' => true,
            'already_checked_in' => false,
            'message' => __('Ticket verified successfully!', 'wp-event-calendar-pro'),
            'event_title' => $event->post_title,
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'order_id' => $result->order_id,
        ));
    }

    /**
     * Ticket scanner shortcode
     */
    public function ticket_scanner_shortcode($atts) {
        if (!current_user_can('manage_options')) {
            return '<p>' . __('You do not have permission to access this feature.', 'wp-event-calendar-pro') . '</p>';
        }

        ob_start();
        ?>
        <div class="wecp-ticket-scanner">
            <h3><?php _e('Ticket Scanner', 'wp-event-calendar-pro'); ?></h3>
            <div class="scanner-input">
                <input type="text" id="wecp-ticket-input" placeholder="<?php _e('Scan or enter ticket code', 'wp-event-calendar-pro'); ?>" autofocus>
                <button id="wecp-verify-btn" class="button button-primary"><?php _e('Verify', 'wp-event-calendar-pro'); ?></button>
            </div>
            <div id="wecp-scanner-result"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#wecp-ticket-input, #wecp-verify-btn').on('click keypress', function(e) {
                if (e.type === 'click' || e.which === 13) {
                    var ticketCode = $('#wecp-ticket-input').val();

                    if (!ticketCode) {
                        return;
                    }

                    $.ajax({
                        url: wecpData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'wecp_verify_ticket',
                            nonce: wecpData.nonce,
                            ticket_code: ticketCode
                        },
                        success: function(response) {
                            var resultDiv = $('#wecp-scanner-result');

                            if (response.success) {
                                var className = response.data.already_checked_in ? 'warning' : 'success';
                                resultDiv.html(
                                    '<div class="notice notice-' + className + '">' +
                                    '<p><strong>' + response.data.event_title + '</strong></p>' +
                                    '<p>' + response.data.customer_name + '</p>' +
                                    '<p>' + response.data.message + '</p>' +
                                    '</div>'
                                );
                            } else {
                                resultDiv.html(
                                    '<div class="notice notice-error">' +
                                    '<p>' + response.data.message + '</p>' +
                                    '</div>'
                                );
                            }

                            $('#wecp-ticket-input').val('').focus();
                        }
                    });
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
