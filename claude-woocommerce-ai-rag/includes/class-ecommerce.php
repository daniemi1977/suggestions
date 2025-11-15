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
}
