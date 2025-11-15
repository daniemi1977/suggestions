<?php
/**
 * Rich Messages Class
 * Handles creation of rich message components (product cards, carousels, buttons)
 */

if (!defined('ABSPATH')) exit;

class CWAU_Rich_Messages {

    /**
     * Create product card HTML
     */
    public static function product_card($product_data) {
        if (!$product_data || !isset($product_data['id'])) {
            return '';
        }

        $product = wc_get_product($product_data['id']);
        if (!$product) {
            return '';
        }

        $image_url = wp_get_attachment_image_url($product->get_image_id(), 'medium') ?: wc_placeholder_img_src('medium');
        $name = $product->get_name();
        $price = $product->get_price_html();
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        $permalink = $product->get_permalink();
        $stock_status = $product->get_stock_status();
        $in_stock = $stock_status === 'instock';
        $rating = $product->get_average_rating();
        $review_count = $product->get_review_count();

        // Calculate discount percentage
        $discount_percent = 0;
        if ($sale_price && $regular_price && $regular_price > $sale_price) {
            $discount_percent = round((($regular_price - $sale_price) / $regular_price) * 100);
        }

        // Stock badge
        $stock_badge = '';
        if ($in_stock) {
            $stock_quantity = $product->get_stock_quantity();
            if ($stock_quantity && $stock_quantity < 5) {
                $stock_badge = '<span class="stock-badge low-stock">Solo ' . $stock_quantity . ' disponibili</span>';
            } else {
                $stock_badge = '<span class="stock-badge in-stock">✓ Disponibile</span>';
            }
        } else {
            $stock_badge = '<span class="stock-badge out-of-stock">✗ Esaurito</span>';
        }

        // Discount badge
        $discount_badge = '';
        if ($discount_percent > 0) {
            $discount_badge = '<span class="discount-badge">-' . $discount_percent . '%</span>';
        }

        // Rating stars
        $stars_html = '';
        if ($rating > 0) {
            $full_stars = floor($rating);
            $half_star = ($rating - $full_stars) >= 0.5;

            for ($i = 0; $i < $full_stars; $i++) {
                $stars_html .= '<span class="star full">★</span>';
            }
            if ($half_star) {
                $stars_html .= '<span class="star half">★</span>';
            }
            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
            for ($i = 0; $i < $empty_stars; $i++) {
                $stars_html .= '<span class="star empty">☆</span>';
            }
            $stars_html .= '<span class="review-count">(' . $review_count . ')</span>';
        }

        $html = '<div class="cwau-product-card" data-product-id="' . esc_attr($product->get_id()) . '">';

        // Image container with badges
        $html .= '<div class="product-image-container">';
        $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($name) . '" class="product-image" loading="lazy">';
        if ($discount_badge) {
            $html .= $discount_badge;
        }
        $html .= '</div>';

        // Product info
        $html .= '<div class="product-info">';
        $html .= '<h4 class="product-name">' . esc_html($name) . '</h4>';

        // Rating
        if ($stars_html) {
            $html .= '<div class="product-rating">' . $stars_html . '</div>';
        }

        // Price
        $html .= '<div class="product-price">' . $price . '</div>';

        // Stock status
        $html .= '<div class="product-stock">' . $stock_badge . '</div>';

        // Actions
        $html .= '<div class="product-actions">';

        if ($in_stock) {
            $html .= '<button class="btn-add-to-cart" data-product-id="' . esc_attr($product->get_id()) . '">';
            $html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>';
            $html .= ' Aggiungi';
            $html .= '</button>';
        } else {
            $html .= '<button class="btn-notify-stock" data-product-id="' . esc_attr($product->get_id()) . '">';
            $html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>';
            $html .= ' Avvisami';
            $html .= '</button>';
        }

        $html .= '<a href="' . esc_url($permalink) . '" class="btn-view-product" target="_blank">';
        $html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
        $html .= ' Dettagli';
        $html .= '</a>';

        $html .= '</div>'; // .product-actions
        $html .= '</div>'; // .product-info
        $html .= '</div>'; // .cwau-product-card

        return $html;
    }

    /**
     * Create product carousel
     */
    public static function product_carousel($products) {
        if (empty($products)) {
            return '';
        }

        $html = '<div class="cwau-product-carousel">';
        $html .= '<div class="carousel-header">';
        $html .= '<h4>Prodotti Consigliati</h4>';
        $html .= '<div class="carousel-nav">';
        $html .= '<button class="carousel-prev">‹</button>';
        $html .= '<button class="carousel-next">›</button>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="carousel-container">';
        $html .= '<div class="carousel-track">';

        foreach ($products as $product_data) {
            $html .= '<div class="carousel-item">';
            $html .= self::product_card($product_data);
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        // Indicators
        if (count($products) > 1) {
            $html .= '<div class="carousel-indicators">';
            for ($i = 0; $i < count($products); $i++) {
                $active = $i === 0 ? ' active' : '';
                $html .= '<span class="indicator' . $active . '" data-index="' . $i . '"></span>';
            }
            $html .= '</div>';
        }

        $html .= '</div>'; // .cwau-product-carousel

        return $html;
    }

    /**
     * Create button group
     */
    public static function button_group($buttons) {
        if (empty($buttons)) {
            return '';
        }

        $html = '<div class="cwau-button-group">';

        foreach ($buttons as $button) {
            $type = $button['type'] ?? 'primary';
            $text = $button['text'] ?? 'Button';
            $action = $button['action'] ?? '';
            $data = $button['data'] ?? '';

            $html .= '<button class="cwau-btn cwau-btn-' . esc_attr($type) . '" ';
            $html .= 'data-action="' . esc_attr($action) . '" ';
            $html .= 'data-value="' . esc_attr($data) . '">';
            $html .= esc_html($text);
            $html .= '</button>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Create order status card
     */
    public static function order_status_card($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return '<p>Ordine non trovato.</p>';
        }

        $status = $order->get_status();
        $status_name = wc_get_order_status_name($status);
        $total = $order->get_total();
        $date = $order->get_date_created()->format('d/m/Y H:i');
        $items_count = $order->get_item_count();

        // Status icon
        $status_icons = array(
            'pending' => '⏳',
            'processing' => '🔄',
            'on-hold' => '⏸️',
            'completed' => '✅',
            'cancelled' => '❌',
            'refunded' => '💰',
            'failed' => '⚠️'
        );
        $icon = $status_icons[$status] ?? '📦';

        $html = '<div class="cwau-order-card">';
        $html .= '<div class="order-header">';
        $html .= '<h4>' . $icon . ' Ordine #' . $order_id . '</h4>';
        $html .= '<span class="order-status status-' . esc_attr($status) . '">' . esc_html($status_name) . '</span>';
        $html .= '</div>';

        $html .= '<div class="order-details">';
        $html .= '<div class="detail-row">';
        $html .= '<span class="label">Data:</span>';
        $html .= '<span class="value">' . esc_html($date) . '</span>';
        $html .= '</div>';

        $html .= '<div class="detail-row">';
        $html .= '<span class="label">Articoli:</span>';
        $html .= '<span class="value">' . $items_count . '</span>';
        $html .= '</div>';

        $html .= '<div class="detail-row">';
        $html .= '<span class="label">Totale:</span>';
        $html .= '<span class="value total">' . wc_price($total) . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        // Order items
        $items = $order->get_items();
        if (!empty($items)) {
            $html .= '<div class="order-items">';
            $html .= '<h5>Prodotti:</h5>';
            $html .= '<ul>';
            foreach ($items as $item) {
                $product_name = $item->get_name();
                $quantity = $item->get_quantity();
                $html .= '<li>' . $quantity . 'x ' . esc_html($product_name) . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        // Tracking info
        $tracking_number = $order->get_meta('_tracking_number');
        if ($tracking_number) {
            $html .= '<div class="tracking-info">';
            $html .= '<strong>Tracking:</strong> ' . esc_html($tracking_number);
            $html .= '</div>';
        }

        $html .= '<div class="order-actions">';
        $html .= '<a href="' . esc_url($order->get_view_order_url()) . '" class="btn-view-order" target="_blank">Visualizza Ordine</a>';
        $html .= '</div>';

        $html .= '</div>'; // .cwau-order-card

        return $html;
    }

    /**
     * Create discount code card
     */
    public static function discount_card($code, $amount, $type = 'percent') {
        $html = '<div class="cwau-discount-card">';
        $html .= '<div class="discount-icon">🎁</div>';
        $html .= '<div class="discount-content">';
        $html .= '<h4>Codice Sconto Applicato!</h4>';
        $html .= '<div class="discount-code">' . esc_html($code) . '</div>';

        if ($type === 'percent') {
            $html .= '<div class="discount-value">-' . esc_html($amount) . '%</div>';
        } else {
            $html .= '<div class="discount-value">-' . wc_price($amount) . '</div>';
        }

        $html .= '<p class="discount-note">Il sconto verrà applicato automaticamente al checkout</p>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Parse AI response to detect and convert product mentions to cards
     */
    public static function parse_and_enrich_response($text, $rag_products = array()) {
        if (empty($rag_products)) {
            return array(
                'text' => $text,
                'rich_content' => array()
            );
        }

        $rich_content = array();

        // Check if AI is recommending products
        $is_recommending = (
            stripos($text, 'consiglio') !== false ||
            stripos($text, 'suggerisco') !== false ||
            stripos($text, 'raccomando') !== false ||
            stripos($text, 'perfetto per') !== false ||
            stripos($text, 'ideale') !== false
        );

        if ($is_recommending && count($rag_products) > 0) {
            // Create carousel with top 3 products
            $top_products = array_slice($rag_products, 0, 3);
            $rich_content[] = array(
                'type' => 'carousel',
                'products' => $top_products
            );
        }

        return array(
            'text' => $text,
            'rich_content' => $rich_content
        );
    }

    /**
     * Render rich content
     */
    public static function render_rich_content($content) {
        if (empty($content) || !is_array($content)) {
            return '';
        }

        $html = '';

        foreach ($content as $item) {
            $type = $item['type'] ?? '';

            switch ($type) {
                case 'product_card':
                    $html .= self::product_card($item);
                    break;

                case 'carousel':
                    $html .= self::product_carousel($item['products'] ?? array());
                    break;

                case 'buttons':
                    $html .= self::button_group($item['buttons'] ?? array());
                    break;

                case 'order_status':
                    $html .= self::order_status_card($item['order_id'] ?? 0);
                    break;

                case 'discount':
                    $html .= self::discount_card(
                        $item['code'] ?? '',
                        $item['amount'] ?? 0,
                        $item['discount_type'] ?? 'percent'
                    );
                    break;
            }
        }

        return $html;
    }
}
