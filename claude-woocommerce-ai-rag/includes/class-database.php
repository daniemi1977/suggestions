<?php
/**
 * Database Management Class
 * Handles all database operations including table creation and maintenance
 */

if (!defined('ABSPATH')) exit;

class CWAU_Database {

    /**
     * Create all necessary database tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Conversations table
        $sql_conversations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_conversations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(255) NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            user_email VARCHAR(255) NULL,
            status ENUM('active', 'completed', 'escalated') DEFAULT 'active',
            sentiment VARCHAR(20) DEFAULT 'neutral',
            tags TEXT NULL,
            metadata JSON NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY started_at (started_at)
        ) $charset;";

        // Messages table
        $sql_messages = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id BIGINT UNSIGNED NOT NULL,
            sender ENUM('user', 'ai', 'operator') NOT NULL,
            message TEXT NOT NULL,
            metadata JSON NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY sender (sender),
            KEY created_at (created_at),
            FOREIGN KEY (conversation_id) REFERENCES {$wpdb->prefix}cwau_conversations(id) ON DELETE CASCADE
        ) $charset;";

        // Escalations table
        $sql_escalations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_escalations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id BIGINT UNSIGNED NOT NULL,
            reason TEXT NULL,
            status ENUM('pending', 'assigned', 'resolved') DEFAULT 'pending',
            assigned_to VARCHAR(255) NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            resolved_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY status (status),
            KEY created_at (created_at),
            FOREIGN KEY (conversation_id) REFERENCES {$wpdb->prefix}cwau_conversations(id) ON DELETE CASCADE
        ) $charset;";

        // Embeddings table for RAG
        $sql_embeddings = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_embeddings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            embedding LONGTEXT NOT NULL,
            model VARCHAR(100) DEFAULT 'text-embedding-3-small',
            dimension INT DEFAULT 1536,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY product_id (product_id),
            KEY created_at (created_at)
        ) $charset;";

        dbDelta($sql_conversations);
        dbDelta($sql_messages);
        dbDelta($sql_escalations);
        dbDelta($sql_embeddings);

        return true;
    }

    /**
     * Get conversation by session ID
     */
    public static function get_conversation_by_session($session_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_conversations WHERE session_id = %s ORDER BY id DESC LIMIT 1",
            $session_id
        ));
    }

    /**
     * Create new conversation
     */
    public static function create_conversation($session_id, $user_id = null, $user_email = null) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'cwau_conversations',
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'user_email' => $user_email,
                'status' => 'active',
                'sentiment' => 'neutral',
                'started_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
        return $wpdb->insert_id;
    }

    /**
     * Add message to conversation
     */
    public static function add_message($conversation_id, $sender, $message, $metadata = null) {
        global $wpdb;
        return $wpdb->insert(
            $wpdb->prefix . 'cwau_messages',
            array(
                'conversation_id' => $conversation_id,
                'sender' => $sender,
                'message' => $message,
                'metadata' => $metadata ? json_encode($metadata) : null,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get conversation messages
     */
    public static function get_conversation_messages($conversation_id, $limit = 50) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_messages WHERE conversation_id = %d ORDER BY created_at ASC LIMIT %d",
            $conversation_id,
            $limit
        ));
    }

    /**
     * Update conversation status
     */
    public static function update_conversation_status($conversation_id, $status) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'cwau_conversations',
            array('status' => $status),
            array('id' => $conversation_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Update conversation sentiment
     */
    public static function update_conversation_sentiment($conversation_id, $sentiment) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'cwau_conversations',
            array('sentiment' => $sentiment),
            array('id' => $conversation_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Save embedding to database
     */
    public static function save_embedding($product_id, $content, $embedding, $model = 'text-embedding-3-small', $dimension = 1536) {
        global $wpdb;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}cwau_embeddings WHERE product_id = %d",
            $product_id
        ));

        $data = array(
            'product_id' => $product_id,
            'content' => $content,
            'embedding' => json_encode($embedding),
            'model' => $model,
            'dimension' => $dimension,
            'updated_at' => current_time('mysql')
        );

        if ($existing) {
            return $wpdb->update(
                $wpdb->prefix . 'cwau_embeddings',
                $data,
                array('product_id' => $product_id),
                array('%d', '%s', '%s', '%s', '%d', '%s'),
                array('%d')
            );
        } else {
            $data['created_at'] = current_time('mysql');
            return $wpdb->insert(
                $wpdb->prefix . 'cwau_embeddings',
                $data,
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
            );
        }
    }

    /**
     * Get all embeddings
     */
    public static function get_all_embeddings() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}cwau_embeddings ORDER BY id ASC"
        );
    }

    /**
     * Get embedding by product ID
     */
    public static function get_embedding($product_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_embeddings WHERE product_id = %d",
            $product_id
        ));
    }

    /**
     * Delete all embeddings
     */
    public static function clear_embeddings() {
        global $wpdb;
        return $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}cwau_embeddings");
    }

    /**
     * Count indexed products
     */
    public static function count_embeddings() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cwau_embeddings");
    }

    /**
     * Delete embedding by product ID
     */
    public static function delete_embedding($product_id) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->prefix . 'cwau_embeddings',
            array('product_id' => $product_id),
            array('%d')
        );
    }
}
