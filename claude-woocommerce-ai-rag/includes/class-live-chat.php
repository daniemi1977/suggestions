<?php
/**
 * Live Chat Operatore Class
 * Real-time chat between operators and customers
 * Inspired by Intercom, Zendesk Chat, and LiveAgent
 */

if (!defined('ABSPATH')) exit;

class CWAU_Live_Chat {

    /**
     * Operator joins a conversation (takes over from AI)
     */
    public static function operator_join($conversation_id, $operator_id) {
        global $wpdb;

        // Check if conversation exists
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_conversations WHERE id = %d",
            $conversation_id
        ));

        if (!$conversation) {
            return array('success' => false, 'error' => 'Conversation not found');
        }

        // Check if operator already in session
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_operator_sessions
             WHERE conversation_id = %d AND operator_id = %d AND status = 'active'",
            $conversation_id,
            $operator_id
        ));

        if ($existing > 0) {
            return array('success' => false, 'error' => 'Operator already in conversation');
        }

        // Create operator session
        $result = $wpdb->insert(
            $wpdb->prefix . 'cwau_operator_sessions',
            array(
                'operator_id' => $operator_id,
                'conversation_id' => $conversation_id,
                'status' => 'active',
                'joined_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result) {
            $session_id = $wpdb->insert_id;

            // Add system message to conversation
            CWAU_Database::add_message(
                $conversation_id,
                'operator',
                sprintf('👤 %s has joined the conversation', get_userdata($operator_id)->display_name),
                array('operator_id' => $operator_id, 'type' => 'system_join')
            );

            // Update conversation status to show operator is handling
            $wpdb->update(
                $wpdb->prefix . 'cwau_conversations',
                array('metadata' => json_encode(array('operator_active' => true, 'operator_id' => $operator_id))),
                array('id' => $conversation_id),
                array('%s'),
                array('%d')
            );

            // Log activity in CRM if customer exists
            if ($conversation->user_id) {
                $customer = CWAU_CRM::get_customer_by_user_id($conversation->user_id);
                if ($customer) {
                    CWAU_CRM::log_activity(
                        $customer->id,
                        null,
                        'chat',
                        'Live Chat Joined',
                        'Operator ' . get_userdata($operator_id)->display_name . ' joined the conversation',
                        array('conversation_id' => $conversation_id, 'session_id' => $session_id)
                    );
                }
            }

            return array(
                'success' => true,
                'session_id' => $session_id,
                'message' => 'Joined conversation successfully'
            );
        }

        return array('success' => false, 'error' => 'Failed to create operator session');
    }

    /**
     * Operator leaves a conversation
     */
    public static function operator_leave($conversation_id, $operator_id, $reason = null) {
        global $wpdb;

        // Find active session
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_operator_sessions
             WHERE conversation_id = %d AND operator_id = %d AND status = 'active'
             ORDER BY id DESC LIMIT 1",
            $conversation_id,
            $operator_id
        ));

        if (!$session) {
            return array('success' => false, 'error' => 'No active session found');
        }

        // Update session
        $wpdb->update(
            $wpdb->prefix . 'cwau_operator_sessions',
            array(
                'status' => 'ended',
                'left_at' => current_time('mysql')
            ),
            array('id' => $session->id),
            array('%s', '%s'),
            array('%d')
        );

        // Add system message
        CWAU_Database::add_message(
            $conversation_id,
            'operator',
            sprintf('👤 %s has left the conversation', get_userdata($operator_id)->display_name),
            array('operator_id' => $operator_id, 'type' => 'system_leave', 'reason' => $reason)
        );

        // Update conversation metadata
        $wpdb->update(
            $wpdb->prefix . 'cwau_conversations',
            array('metadata' => json_encode(array('operator_active' => false))),
            array('id' => $conversation_id),
            array('%s'),
            array('%d')
        );

        return array('success' => true, 'message' => 'Left conversation successfully');
    }

    /**
     * Transfer conversation to another operator
     */
    public static function transfer_conversation($conversation_id, $from_operator_id, $to_operator_id, $reason = null) {
        global $wpdb;

        // End current operator session
        $wpdb->update(
            $wpdb->prefix . 'cwau_operator_sessions',
            array(
                'status' => 'transferred',
                'transferred_to' => $to_operator_id,
                'transfer_reason' => $reason,
                'left_at' => current_time('mysql')
            ),
            array(
                'conversation_id' => $conversation_id,
                'operator_id' => $from_operator_id,
                'status' => 'active'
            ),
            array('%s', '%d', '%s', '%s'),
            array('%d', '%d', '%s')
        );

        // Create new operator session
        $wpdb->insert(
            $wpdb->prefix . 'cwau_operator_sessions',
            array(
                'operator_id' => $to_operator_id,
                'conversation_id' => $conversation_id,
                'status' => 'active',
                'joined_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s')
        );

        // Add system message
        $from_name = get_userdata($from_operator_id)->display_name;
        $to_name = get_userdata($to_operator_id)->display_name;

        CWAU_Database::add_message(
            $conversation_id,
            'operator',
            sprintf('🔄 Conversation transferred from %s to %s', $from_name, $to_name),
            array(
                'type' => 'system_transfer',
                'from_operator' => $from_operator_id,
                'to_operator' => $to_operator_id,
                'reason' => $reason
            )
        );

        // Notify new operator
        self::notify_operator($to_operator_id, $conversation_id, 'transfer', array(
            'from_operator' => $from_name,
            'reason' => $reason
        ));

        return array('success' => true, 'message' => 'Conversation transferred successfully');
    }

    /**
     * Send message as operator
     */
    public static function send_operator_message($conversation_id, $operator_id, $message, $metadata = array()) {
        global $wpdb;

        // Check if operator has active session
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_operator_sessions
             WHERE conversation_id = %d AND operator_id = %d AND status = 'active'
             ORDER BY id DESC LIMIT 1",
            $conversation_id,
            $operator_id
        ));

        if (!$session) {
            return array('success' => false, 'error' => 'No active session. Please join the conversation first.');
        }

        // Add operator metadata
        $metadata['operator_id'] = $operator_id;
        $metadata['operator_name'] = get_userdata($operator_id)->display_name;
        $metadata['operator_avatar'] = get_avatar_url($operator_id);

        // Save message
        $result = CWAU_Database::add_message($conversation_id, 'operator', $message, $metadata);

        if ($result) {
            // Update conversation timestamp
            $wpdb->update(
                $wpdb->prefix . 'cwau_conversations',
                array('updated_at' => current_time('mysql')),
                array('id' => $conversation_id),
                array('%s'),
                array('%d')
            );

            return array('success' => true, 'message_id' => $result);
        }

        return array('success' => false, 'error' => 'Failed to send message');
    }

    /**
     * Get operator's active conversations
     */
    public static function get_operator_conversations($operator_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, os.joined_at, os.status as session_status,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}cwau_messages WHERE conversation_id = c.id) as message_count,
                    (SELECT created_at FROM {$wpdb->prefix}cwau_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_at
             FROM {$wpdb->prefix}cwau_conversations c
             INNER JOIN {$wpdb->prefix}cwau_operator_sessions os ON c.id = os.conversation_id
             WHERE os.operator_id = %d AND os.status = 'active'
             ORDER BY last_message_at DESC",
            $operator_id
        ));
    }

    /**
     * Get available conversations (queue) for operators to pick up
     */
    public static function get_available_queue($filters = array()) {
        global $wpdb;

        $where = array("c.status = 'active'");
        $params = array();

        // Only conversations without active operator
        $where[] = "NOT EXISTS (
            SELECT 1 FROM {$wpdb->prefix}cwau_operator_sessions os
            WHERE os.conversation_id = c.id AND os.status = 'active'
        )";

        // Filter by escalated
        if (isset($filters['escalated']) && $filters['escalated']) {
            $where[] = "c.status = 'escalated'";
        }

        // Filter by sentiment
        if (!empty($filters['sentiment'])) {
            $where[] = 'c.sentiment = %s';
            $params[] = $filters['sentiment'];
        }

        $where_sql = implode(' AND ', $where);
        $limit = isset($filters['limit']) ? 'LIMIT ' . (int)$filters['limit'] : 'LIMIT 50';

        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM {$wpdb->prefix}cwau_messages WHERE conversation_id = c.id) as message_count,
                       (SELECT created_at FROM {$wpdb->prefix}cwau_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_at,
                       TIMESTAMPDIFF(MINUTE, c.started_at, NOW()) as wait_time_minutes
                FROM {$wpdb->prefix}cwau_conversations c
                WHERE {$where_sql}
                ORDER BY c.started_at ASC
                {$limit}";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Update operator status (online, away, offline)
     */
    public static function update_operator_status($operator_id, $status) {
        update_user_meta($operator_id, 'cwau_operator_status', $status);
        update_user_meta($operator_id, 'cwau_operator_last_seen', current_time('mysql'));

        return array('success' => true, 'status' => $status);
    }

    /**
     * Get operator status
     */
    public static function get_operator_status($operator_id) {
        $status = get_user_meta($operator_id, 'cwau_operator_status', true);
        $last_seen = get_user_meta($operator_id, 'cwau_operator_last_seen', true);

        return array(
            'status' => $status ?: 'offline',
            'last_seen' => $last_seen,
            'is_online' => $status === 'online'
        );
    }

    /**
     * Get all online operators
     */
    public static function get_online_operators() {
        $args = array(
            'role__in' => array('administrator', 'shop_manager'),
            'meta_query' => array(
                array(
                    'key' => 'cwau_operator_status',
                    'value' => 'online',
                    'compare' => '='
                )
            )
        );

        $users = get_users($args);
        $operators = array();

        foreach ($users as $user) {
            $active_chats = count(self::get_operator_conversations($user->ID));

            $operators[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'avatar' => get_avatar_url($user->ID),
                'status' => 'online',
                'active_chats' => $active_chats,
                'last_seen' => get_user_meta($user->ID, 'cwau_operator_last_seen', true)
            );
        }

        return $operators;
    }

    /**
     * Save typing indicator status
     */
    public static function set_typing_indicator($conversation_id, $sender_id, $sender_type, $is_typing) {
        $key = "cwau_typing_{$conversation_id}_{$sender_type}_{$sender_id}";

        if ($is_typing) {
            set_transient($key, true, 10); // 10 seconds TTL
        } else {
            delete_transient($key);
        }

        return array('success' => true);
    }

    /**
     * Get typing indicators for conversation
     */
    public static function get_typing_indicators($conversation_id) {
        global $wpdb;

        $typing = array();

        // Check operators typing
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT operator_id FROM {$wpdb->prefix}cwau_operator_sessions
             WHERE conversation_id = %d AND status = 'active'",
            $conversation_id
        ));

        foreach ($sessions as $session) {
            $key = "cwau_typing_{$conversation_id}_operator_{$session->operator_id}";
            if (get_transient($key)) {
                $user = get_userdata($session->operator_id);
                $typing[] = array(
                    'type' => 'operator',
                    'id' => $session->operator_id,
                    'name' => $user->display_name
                );
            }
        }

        // Check customer typing
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}cwau_conversations WHERE id = %d",
            $conversation_id
        ));

        if ($conversation && $conversation->user_id) {
            $key = "cwau_typing_{$conversation_id}_customer_{$conversation->user_id}";
            if (get_transient($key)) {
                $typing[] = array(
                    'type' => 'customer',
                    'id' => $conversation->user_id,
                    'name' => 'Customer'
                );
            }
        }

        return $typing;
    }

    /**
     * Get conversation statistics for operator
     */
    public static function get_operator_stats($operator_id, $period = 'today') {
        global $wpdb;

        $date_filter = match($period) {
            'today' => "DATE(joined_at) = CURDATE()",
            'week' => "joined_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "joined_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            default => "1=1"
        };

        // Total conversations handled
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_operator_sessions
             WHERE operator_id = %d AND {$date_filter}",
            $operator_id
        ));

        // Average handle time (in minutes)
        $avg_handle_time = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, joined_at, left_at))
             FROM {$wpdb->prefix}cwau_operator_sessions
             WHERE operator_id = %d AND left_at IS NOT NULL AND {$date_filter}",
            $operator_id
        ));

        // Total chat time (in hours)
        $total_chat_time = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(TIMESTAMPDIFF(MINUTE, joined_at, IFNULL(left_at, NOW())))
             FROM {$wpdb->prefix}cwau_operator_sessions
             WHERE operator_id = %d AND {$date_filter}",
            $operator_id
        ));

        // Messages sent
        $messages_sent = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}cwau_messages
             WHERE sender = 'operator'
             AND JSON_EXTRACT(metadata, '$.operator_id') = %d
             AND {$date_filter}",
            $operator_id
        ));

        return array(
            'total_conversations' => (int) $total,
            'avg_handle_time_minutes' => round($avg_handle_time, 2),
            'total_chat_time_hours' => round($total_chat_time / 60, 2),
            'messages_sent' => (int) $messages_sent,
            'currently_active' => count(self::get_operator_conversations($operator_id))
        );
    }

    /**
     * Notify operator
     */
    private static function notify_operator($operator_id, $conversation_id, $type, $data = array()) {
        $operator = get_userdata($operator_id);

        if (!$operator) return;

        $titles = array(
            'transfer' => 'Chat Transferred to You',
            'escalation' => 'New Escalated Chat',
            'new' => 'New Chat Assigned'
        );

        $title = $titles[$type] ?? 'New Notification';

        // Create in-app notification
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'cwau_notifications',
            array(
                'user_id' => $operator_id,
                'type' => 'chat_' . $type,
                'title' => $title,
                'message' => json_encode($data),
                'link' => admin_url('admin.php?page=cwau-live-chat&conversation_id=' . $conversation_id),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        // Send email notification if enabled
        if (get_user_meta($operator_id, 'cwau_email_notifications', true)) {
            $subject = $title;
            $message = sprintf(
                "A chat has been assigned to you.\n\nConversation ID: %d\n\nView conversation: %s",
                $conversation_id,
                admin_url('admin.php?page=cwau-live-chat&conversation_id=' . $conversation_id)
            );
            wp_mail($operator->user_email, $subject, $message);
        }
    }

    /**
     * AJAX: Operator join conversation
     */
    public static function ajax_operator_join() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
        $operator_id = get_current_user_id();

        if (!$conversation_id) {
            wp_send_json_error(array('message' => 'Invalid conversation ID'));
        }

        $result = self::operator_join($conversation_id, $operator_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Operator leave conversation
     */
    public static function ajax_operator_leave() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
        $operator_id = get_current_user_id();
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : null;

        $result = self::operator_leave($conversation_id, $operator_id, $reason);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Transfer conversation
     */
    public static function ajax_transfer() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
        $to_operator_id = isset($_POST['to_operator_id']) ? absint($_POST['to_operator_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : null;
        $from_operator_id = get_current_user_id();

        if (!$conversation_id || !$to_operator_id) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }

        $result = self::transfer_conversation($conversation_id, $from_operator_id, $to_operator_id, $reason);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Send operator message
     */
    public static function ajax_send_message() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $operator_id = get_current_user_id();

        if (!$conversation_id || empty($message)) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }

        $result = self::send_operator_message($conversation_id, $operator_id, $message);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Get queue
     */
    public static function ajax_get_queue() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $filters = array(
            'escalated' => isset($_POST['escalated']) ? (bool)$_POST['escalated'] : false,
            'limit' => isset($_POST['limit']) ? absint($_POST['limit']) : 50
        );

        $queue = self::get_available_queue($filters);

        wp_send_json_success(array('queue' => $queue));
    }

    /**
     * AJAX: Update operator status
     */
    public static function ajax_update_status() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'offline';
        $operator_id = get_current_user_id();

        $result = self::update_operator_status($operator_id, $status);
        wp_send_json_success($result);
    }

    /**
     * AJAX: Set typing indicator
     */
    public static function ajax_typing() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
        $is_typing = isset($_POST['is_typing']) ? (bool)$_POST['is_typing'] : false;
        $operator_id = get_current_user_id();

        $result = self::set_typing_indicator($conversation_id, $operator_id, 'operator', $is_typing);
        wp_send_json_success($result);
    }
}
