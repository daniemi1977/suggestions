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

        // CRM: Customers table
        $sql_customers = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_customers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            email VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NULL,
            last_name VARCHAR(100) NULL,
            phone VARCHAR(50) NULL,
            company VARCHAR(255) NULL,
            avatar_url TEXT NULL,
            lead_score INT DEFAULT 0,
            lifecycle_stage ENUM('lead', 'mql', 'sql', 'opportunity', 'customer', 'evangelist') DEFAULT 'lead',
            lead_source VARCHAR(100) NULL,
            tags TEXT NULL,
            custom_fields JSON NULL,
            total_revenue DECIMAL(10,2) DEFAULT 0.00,
            total_orders INT DEFAULT 0,
            last_contact_date DATETIME NULL,
            last_purchase_date DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY user_id (user_id),
            KEY lead_score (lead_score),
            KEY lifecycle_stage (lifecycle_stage),
            KEY created_at (created_at)
        ) $charset;";

        // CRM: Deals/Opportunities table
        $sql_deals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_deals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) DEFAULT 0.00,
            stage ENUM('lead', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost') DEFAULT 'lead',
            probability INT DEFAULT 0,
            expected_close_date DATE NULL,
            actual_close_date DATE NULL,
            lost_reason TEXT NULL,
            assigned_to BIGINT UNSIGNED NULL,
            pipeline VARCHAR(100) DEFAULT 'default',
            tags TEXT NULL,
            custom_fields JSON NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY stage (stage),
            KEY assigned_to (assigned_to),
            KEY created_at (created_at),
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}cwau_customers(id) ON DELETE CASCADE
        ) $charset;";

        // CRM: Activities table (timeline)
        $sql_activities = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_activities (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            deal_id BIGINT UNSIGNED NULL,
            type ENUM('note', 'email', 'call', 'meeting', 'task', 'chat', 'purchase', 'page_view') NOT NULL,
            subject VARCHAR(255) NULL,
            description TEXT NULL,
            metadata JSON NULL,
            completed BOOLEAN DEFAULT FALSE,
            due_date DATETIME NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY deal_id (deal_id),
            KEY type (type),
            KEY created_at (created_at),
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}cwau_customers(id) ON DELETE CASCADE
        ) $charset;";

        // Ticketing: Tickets table
        $sql_tickets = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_tickets (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_number VARCHAR(50) NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            conversation_id BIGINT UNSIGNED NULL,
            subject VARCHAR(255) NOT NULL,
            description TEXT NULL,
            status ENUM('new', 'open', 'pending', 'on_hold', 'solved', 'closed') DEFAULT 'new',
            priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
            category VARCHAR(100) NULL,
            assigned_to BIGINT UNSIGNED NULL,
            assigned_team VARCHAR(100) NULL,
            tags TEXT NULL,
            sla_due_at DATETIME NULL,
            sla_breached BOOLEAN DEFAULT FALSE,
            first_response_at DATETIME NULL,
            resolved_at DATETIME NULL,
            closed_at DATETIME NULL,
            satisfaction_rating TINYINT NULL,
            satisfaction_comment TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY ticket_number (ticket_number),
            KEY customer_id (customer_id),
            KEY conversation_id (conversation_id),
            KEY status (status),
            KEY priority (priority),
            KEY assigned_to (assigned_to),
            KEY sla_due_at (sla_due_at),
            KEY created_at (created_at),
            FOREIGN KEY (customer_id) REFERENCES {$wpdb->prefix}cwau_customers(id) ON DELETE SET NULL,
            FOREIGN KEY (conversation_id) REFERENCES {$wpdb->prefix}cwau_conversations(id) ON DELETE SET NULL
        ) $charset;";

        // Ticketing: Ticket replies table
        $sql_ticket_replies = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_ticket_replies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id BIGINT UNSIGNED NOT NULL,
            author_id BIGINT UNSIGNED NULL,
            author_type ENUM('customer', 'agent', 'system') DEFAULT 'customer',
            content TEXT NOT NULL,
            is_internal BOOLEAN DEFAULT FALSE,
            attachments JSON NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY ticket_id (ticket_id),
            KEY created_at (created_at),
            FOREIGN KEY (ticket_id) REFERENCES {$wpdb->prefix}cwau_tickets(id) ON DELETE CASCADE
        ) $charset;";

        // Live Chat: Operator sessions table
        $sql_operator_sessions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_operator_sessions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            operator_id BIGINT UNSIGNED NOT NULL,
            conversation_id BIGINT UNSIGNED NOT NULL,
            status ENUM('active', 'transferred', 'ended') DEFAULT 'active',
            transferred_to BIGINT UNSIGNED NULL,
            transfer_reason TEXT NULL,
            joined_at DATETIME NOT NULL,
            left_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY operator_id (operator_id),
            KEY conversation_id (conversation_id),
            KEY status (status),
            FOREIGN KEY (conversation_id) REFERENCES {$wpdb->prefix}cwau_conversations(id) ON DELETE CASCADE
        ) $charset;";

        // Automation: Workflows table
        $sql_workflows = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_workflows (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            trigger_type VARCHAR(100) NOT NULL,
            trigger_conditions JSON NULL,
            actions JSON NOT NULL,
            enabled BOOLEAN DEFAULT TRUE,
            execution_count INT DEFAULT 0,
            last_execution_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY enabled (enabled),
            KEY trigger_type (trigger_type)
        ) $charset;";

        // Notifications table
        $sql_notifications = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cwau_notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NULL,
            link TEXT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            metadata JSON NULL,
            created_at DATETIME NOT NULL,
            read_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset;";

        // Execute all table creations
        dbDelta($sql_conversations);
        dbDelta($sql_messages);
        dbDelta($sql_escalations);
        dbDelta($sql_embeddings);
        dbDelta($sql_customers);
        dbDelta($sql_deals);
        dbDelta($sql_activities);
        dbDelta($sql_tickets);
        dbDelta($sql_ticket_replies);
        dbDelta($sql_operator_sessions);
        dbDelta($sql_workflows);
        dbDelta($sql_notifications);

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
