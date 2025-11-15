<?php
/**
 * RAG (Retrieval-Augmented Generation) Class
 * Handles product indexing, embeddings creation, and semantic search
 */

if (!defined('ABSPATH')) exit;

class CWAU_RAG {

    const EMBEDDING_MODEL = 'text-embedding-3-small';
    const EMBEDDING_DIMENSION = 1536;
    const BATCH_SIZE = 10;

    /**
     * Index a batch of products
     */
    public static function ajax_index_batch() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $result = self::index_products_batch($offset, self::BATCH_SIZE);

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => sprintf('Indicizzati %d prodotti (totale: %d)', $result['indexed'], $result['total']),
                'indexed' => $result['indexed'],
                'total' => $result['total'],
                'has_more' => $result['has_more'],
                'next_offset' => $result['next_offset']
            ));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }

    /**
     * Clear all embeddings
     */
    public static function ajax_clear() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        CWAU_Database::clear_embeddings();

        // Remove indexed meta
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_cwau_indexed'");

        wp_send_json_success(array('message' => 'Indice RAG svuotato con successo'));
    }

    /**
     * Reindex all products
     */
    public static function ajax_reindex() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        // Clear existing index
        CWAU_Database::clear_embeddings();

        // Reindex
        $result = self::index_products_batch(0, self::BATCH_SIZE);

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => sprintf('Reindicizzazione avviata: %d prodotti', $result['indexed']),
                'has_more' => $result['has_more']
            ));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }

    /**
     * Index a batch of products
     */
    public static function index_products_batch($offset = 0, $limit = 10) {
        $api_key = get_option('cwau_openai_api_key');
        if (empty($api_key)) {
            return array('success' => false, 'error' => 'API Key OpenAI non configurata');
        }

        // Get WooCommerce products
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'post_status' => 'publish',
            'orderby' => 'ID',
            'order' => 'ASC'
        );

        $products = get_posts($args);
        $total_products = wp_count_posts('product')->publish;

        if (empty($products)) {
            return array(
                'success' => true,
                'indexed' => 0,
                'total' => $total_products,
                'has_more' => false,
                'next_offset' => $offset
            );
        }

        $indexed = 0;
        $errors = array();

        foreach ($products as $product) {
            try {
                $wc_product = wc_get_product($product->ID);
                if (!$wc_product) continue;

                // Create product content for embedding
                $content = self::create_product_content($wc_product);

                // Generate embedding
                $embedding = self::generate_embedding($content, $api_key);

                if ($embedding) {
                    // Save to database
                    CWAU_Database::save_embedding(
                        $product->ID,
                        $content,
                        $embedding,
                        self::EMBEDDING_MODEL,
                        self::EMBEDDING_DIMENSION
                    );

                    // Mark as indexed
                    update_post_meta($product->ID, '_cwau_indexed', current_time('mysql'));
                    $indexed++;
                }
            } catch (Exception $e) {
                $errors[] = sprintf('Product ID %d: %s', $product->ID, $e->getMessage());
                error_log('CWAU RAG Error: ' . $e->getMessage());
            }
        }

        $has_more = ($offset + $limit) < $total_products;

        return array(
            'success' => true,
            'indexed' => $indexed,
            'total' => $total_products,
            'has_more' => $has_more,
            'next_offset' => $offset + $limit,
            'errors' => $errors
        );
    }

    /**
     * Create product content for embedding
     */
    private static function create_product_content($product) {
        $content = array();

        // Product name
        $content[] = 'Nome: ' . $product->get_name();

        // Description
        $description = $product->get_description();
        if (!empty($description)) {
            $content[] = 'Descrizione: ' . wp_strip_all_tags($description);
        }

        // Short description
        $short_desc = $product->get_short_description();
        if (!empty($short_desc)) {
            $content[] = 'Descrizione breve: ' . wp_strip_all_tags($short_desc);
        }

        // Price
        if ($product->get_price()) {
            $content[] = 'Prezzo: ' . $product->get_price() . ' ' . get_woocommerce_currency();
        }

        // SKU
        if ($product->get_sku()) {
            $content[] = 'SKU: ' . $product->get_sku();
        }

        // Categories
        $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
        if (!empty($categories)) {
            $content[] = 'Categorie: ' . implode(', ', $categories);
        }

        // Tags
        $tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names'));
        if (!empty($tags)) {
            $content[] = 'Tag: ' . implode(', ', $tags);
        }

        // Attributes
        $attributes = $product->get_attributes();
        if (!empty($attributes)) {
            $attr_strings = array();
            foreach ($attributes as $attribute) {
                if (is_a($attribute, 'WC_Product_Attribute')) {
                    $attr_strings[] = wc_attribute_label($attribute->get_name()) . ': ' .
                                      implode(', ', $attribute->get_options());
                }
            }
            if (!empty($attr_strings)) {
                $content[] = 'Attributi: ' . implode('; ', $attr_strings);
            }
        }

        // Stock status
        if ($product->is_in_stock()) {
            $content[] = 'Disponibilità: In stock';
        } else {
            $content[] = 'Disponibilità: Esaurito';
        }

        return implode("\n", $content);
    }

    /**
     * Generate embedding using OpenAI API
     */
    private static function generate_embedding($text, $api_key) {
        $url = 'https://api.openai.com/v1/embeddings';

        $body = json_encode(array(
            'model' => self::EMBEDDING_MODEL,
            'input' => $text,
            'encoding_format' => 'float'
        ));

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => $body,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            throw new Exception('OpenAI API Error: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new Exception('OpenAI API Error: ' . $body['error']['message']);
        }

        if (!isset($body['data'][0]['embedding'])) {
            throw new Exception('Invalid response from OpenAI API');
        }

        return $body['data'][0]['embedding'];
    }

    /**
     * Search for similar products using query embedding
     */
    public static function search_similar_products($query, $top_k = 5) {
        $api_key = get_option('cwau_openai_api_key');
        if (empty($api_key)) {
            return array();
        }

        try {
            // Generate embedding for query
            $query_embedding = self::generate_embedding($query, $api_key);
            if (!$query_embedding) {
                return array();
            }

            // Get all product embeddings
            $embeddings = CWAU_Database::get_all_embeddings();
            if (empty($embeddings)) {
                return array();
            }

            // Calculate similarities
            $similarities = array();
            foreach ($embeddings as $embedding) {
                $product_embedding = json_decode($embedding->embedding, true);
                $similarity = self::cosine_similarity($query_embedding, $product_embedding);

                $similarities[] = array(
                    'product_id' => $embedding->product_id,
                    'similarity' => $similarity,
                    'content' => $embedding->content
                );
            }

            // Sort by similarity (descending)
            usort($similarities, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            // Return top K results
            $results = array_slice($similarities, 0, $top_k);

            // Enrich with product data
            $enriched_results = array();
            foreach ($results as $result) {
                $product = wc_get_product($result['product_id']);
                if ($product) {
                    $enriched_results[] = array(
                        'product_id' => $result['product_id'],
                        'similarity' => $result['similarity'],
                        'content' => $result['content'],
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'url' => $product->get_permalink(),
                        'image' => wp_get_attachment_url($product->get_image_id())
                    );
                }
            }

            return $enriched_results;

        } catch (Exception $e) {
            error_log('CWAU RAG Search Error: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    private static function cosine_similarity($vec1, $vec2) {
        if (count($vec1) !== count($vec2)) {
            return 0;
        }

        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dot_product += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dot_product / ($magnitude1 * $magnitude2);
    }

    /**
     * Get RAG context for AI prompt
     */
    public static function get_context_for_query($query, $top_k = null) {
        if (!get_option('cwau_rag_enabled', 1)) {
            return '';
        }

        if ($top_k === null) {
            $top_k = get_option('cwau_rag_top_k', 5);
        }

        $similar_products = self::search_similar_products($query, $top_k);

        if (empty($similar_products)) {
            return '';
        }

        $context = "\n\n=== PRODOTTI RILEVANTI DAL CATALOGO ===\n";

        foreach ($similar_products as $i => $product) {
            $context .= sprintf(
                "\nProdotto %d (Rilevanza: %.2f%%):\n%s\nPrezzo: %s €\nURL: %s\n",
                $i + 1,
                $product['similarity'] * 100,
                $product['content'],
                $product['price'],
                $product['url']
            );
        }

        $context .= "\n=== FINE PRODOTTI RILEVANTI ===\n";
        $context .= "\nUtilizza queste informazioni per rispondere in modo preciso alla domanda dell'utente. ";
        $context .= "Se suggerisci un prodotto, includi il link diretto.\n";

        return $context;
    }

    /**
     * Get indexing statistics
     */
    public static function get_stats() {
        $total_products = wp_count_posts('product')->publish;
        $indexed_products = CWAU_Database::count_embeddings();

        return array(
            'total_products' => $total_products,
            'indexed_products' => $indexed_products,
            'percentage' => $total_products > 0 ? round(($indexed_products / $total_products) * 100, 2) : 0
        );
    }
}
