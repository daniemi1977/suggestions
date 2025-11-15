<?php
/**
 * E-commerce Integration Class
 * Handles WooCommerce-specific features
 */

if (!defined('ABSPATH')) exit;

class CWAU_Ecommerce {

    /**
     * Search products via AJAX
     */
    public static function search_products() {
        check_ajax_referer('cwau_chat_nonce', 'nonce');

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

        if (empty($query)) {
            wp_send_json_error(array('message' => 'Query non valida'));
        }

        // Use RAG for semantic search
        $rag_results = CWAU_RAG::search_similar_products($query, 10);

        if (!empty($rag_results)) {
            $products = array();
            foreach ($rag_results as $result) {
                $products[] = array(
                    'id' => $result['product_id'],
                    'name' => $result['name'],
                    'price' => $result['price'],
                    'url' => $result['url'],
                    'image' => $result['image'],
                    'similarity' => round($result['similarity'] * 100, 2)
                );
            }

            wp_send_json_success(array('products' => $products));
        } else {
            // Fallback to standard search
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => 10,
                's' => $query,
                'post_status' => 'publish'
            );

            $products_query = new WP_Query($args);
            $products = array();

            if ($products_query->have_posts()) {
                while ($products_query->have_posts()) {
                    $products_query->the_post();
                    $product = wc_get_product(get_the_ID());

                    $products[] = array(
                        'id' => get_the_ID(),
                        'name' => get_the_title(),
                        'price' => $product->get_price(),
                        'url' => get_permalink(),
                        'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')
                    );
                }
                wp_reset_postdata();
            }

            wp_send_json_success(array('products' => $products));
        }
    }

    /**
     * Get product details
     */
    public static function get_product_details($product_id) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return null;
        }

        return array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'sku' => $product->get_sku(),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'categories' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names')),
            'tags' => wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names')),
            'image' => wp_get_attachment_url($product->get_image_id()),
            'url' => $product->get_permalink()
        );
    }

    /**
     * Get order details for logged-in user
     */
    public static function get_user_orders($user_id) {
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $result = array();

        foreach ($orders as $order) {
            $result[] = array(
                'id' => $order->get_id(),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'items_count' => $order->get_item_count()
            );
        }

        return $result;
    }

    /**
     * Add product to cart via AJAX
     */
    public static function ajax_add_to_cart() {
        check_ajax_referer('cwau_chat_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;

        if (!$product_id) {
            wp_send_json_error(array('message' => 'ID prodotto non valido'));
        }

        // Check if WooCommerce cart is available
        if (!function_exists('WC')) {
            wp_send_json_error(array('message' => 'WooCommerce non disponibile'));
        }

        try {
            // Add to cart
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id);

            if ($cart_item_key) {
                $product = wc_get_product($product_id);
                $cart_count = WC()->cart->get_cart_contents_count();
                $cart_total = WC()->cart->get_cart_total();
                $cart_url = wc_get_cart_url();

                wp_send_json_success(array(
                    'message' => '✓ ' . $product->get_name() . ' aggiunto al carrello!',
                    'cart_count' => $cart_count,
                    'cart_total' => $cart_total,
                    'cart_url' => $cart_url,
                    'product_name' => $product->get_name()
                ));
            } else {
                wp_send_json_error(array('message' => 'Impossibile aggiungere il prodotto al carrello'));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Get order status via AJAX
     */
    public static function ajax_get_order_status() {
        check_ajax_referer('cwau_chat_nonce', 'nonce');

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

        if (!$order_id) {
            // Try to get latest order for current user
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $orders = wc_get_orders(array(
                    'customer_id' => $user_id,
                    'limit' => 1,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ));

                if (!empty($orders)) {
                    $order_id = $orders[0]->get_id();
                }
            }
        }

        if (!$order_id) {
            wp_send_json_error(array('message' => 'Ordine non trovato. Per favore fornisci il numero dell\'ordine.'));
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array('message' => 'Ordine #' . $order_id . ' non trovato.'));
        }

        // Check if user has permission to view this order
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            if ($order->get_customer_id() != $user_id && !current_user_can('manage_woocommerce')) {
                wp_send_json_error(array('message' => 'Non hai i permessi per visualizzare questo ordine.'));
            }
        }

        // Generate order status HTML using Rich Messages
        $html = CWAU_Rich_Messages::order_status_card($order_id);

        wp_send_json_success(array(
            'html' => $html,
            'order_id' => $order_id,
            'status' => $order->get_status()
        ));
    }

    /**
     * Notify when product back in stock
     */
    public static function ajax_notify_stock() {
        check_ajax_referer('cwau_chat_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (!$product_id) {
            wp_send_json_error(array('message' => 'ID prodotto non valido'));
        }

        // If user is logged in, use their email
        if (is_user_logged_in() && empty($email)) {
            $user = wp_get_current_user();
            $email = $user->user_email;
        }

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => 'Email non valida. Per favore fornisci una email valida.'));
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(array('message' => 'Prodotto non trovato'));
        }

        // Save notification request
        $existing = get_post_meta($product_id, '_stock_notifications', true);
        if (!is_array($existing)) {
            $existing = array();
        }

        // Check if email already registered
        if (in_array($email, $existing)) {
            wp_send_json_success(array(
                'message' => '✓ Sei già registrato per ricevere notifiche per ' . $product->get_name()
            ));
            return;
        }

        $existing[] = $email;
        update_post_meta($product_id, '_stock_notifications', $existing);

        wp_send_json_success(array(
            'message' => '✓ Perfetto! Ti invieremo una email quando ' . $product->get_name() . ' tornerà disponibile.'
        ));
    }

    /**
     * Get cart summary
     */
    public static function get_cart_summary() {
        if (!function_exists('WC') || !WC()->cart) {
            return array(
                'count' => 0,
                'total' => '€0.00',
                'items' => array()
            );
        }

        $cart_items = array();
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $cart_items[] = array(
                'key' => $cart_item_key,
                'product_id' => $cart_item['product_id'],
                'name' => $product->get_name(),
                'quantity' => $cart_item['quantity'],
                'price' => wc_price($product->get_price()),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')
            );
        }

        return array(
            'count' => WC()->cart->get_cart_contents_count(),
            'total' => WC()->cart->get_cart_total(),
            'subtotal' => WC()->cart->get_cart_subtotal(),
            'items' => $cart_items,
            'cart_url' => wc_get_cart_url(),
            'checkout_url' => wc_get_checkout_url()
        );
    }
}
