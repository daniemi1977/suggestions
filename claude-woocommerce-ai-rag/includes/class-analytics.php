<?php
/**
 * Analytics Class
 * Handles conversation analytics and reporting
 */

if (!defined('ABSPATH')) exit;

class CWAU_Analytics {

    /**
     * Get analytics data via AJAX
     */
    public static function get_analytics() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        global $wpdb;

        // Conversations today
        $conversations_today = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}cwau_conversations
            WHERE DATE(started_at) = CURDATE()
        ");

        // Total messages
        $messages_total = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}cwau_messages
        ");

        // Active escalations
        $escalations = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}cwau_escalations
            WHERE status IN ('pending', 'assigned')
        ");

        // Average sentiment
        $sentiment_query = $wpdb->get_results("
            SELECT sentiment, COUNT(*) as count
            FROM {$wpdb->prefix}cwau_conversations
            GROUP BY sentiment
        ");

        $sentiment_scores = array('positive' => 1, 'neutral' => 0, 'negative' => -1);
        $total_score = 0;
        $total_count = 0;

        foreach ($sentiment_query as $row) {
            $score = $sentiment_scores[$row->sentiment] ?? 0;
            $total_score += $score * $row->count;
            $total_count += $row->count;
        }

        $avg_sentiment = $total_count > 0 ? round($total_score / $total_count, 2) : 0;
        $sentiment_label = $avg_sentiment > 0.3 ? 'Positivo' : ($avg_sentiment < -0.3 ? 'Negativo' : 'Neutro');

        // Chart data - last 7 days
        $chart_data = array();
        $chart_labels = array();

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->prefix}cwau_conversations
                WHERE DATE(started_at) = %s
            ", $date));

            $chart_labels[] = date('d/m', strtotime($date));
            $chart_data[] = intval($count);
        }

        wp_send_json_success(array(
            'conversations_today' => intval($conversations_today),
            'messages_total' => intval($messages_total),
            'escalations' => intval($escalations),
            'avg_sentiment' => $sentiment_label,
            'chart_labels' => $chart_labels,
            'chart_data' => $chart_data
        ));
    }

    /**
     * Get conversation list
     */
    public static function get_conversations() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        global $wpdb;

        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $conversations = $wpdb->get_results($wpdb->prepare("
            SELECT c.*, COUNT(m.id) as message_count
            FROM {$wpdb->prefix}cwau_conversations c
            LEFT JOIN {$wpdb->prefix}cwau_messages m ON c.id = m.conversation_id
            GROUP BY c.id
            ORDER BY c.started_at DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cwau_conversations");

        wp_send_json_success(array(
            'conversations' => $conversations,
            'total' => intval($total),
            'pages' => ceil($total / $per_page)
        ));
    }

    /**
     * Get conversation messages
     */
    public static function get_conversation_messages() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;

        if (!$conversation_id) {
            wp_send_json_error(array('message' => 'ID conversazione non valido'));
        }

        $messages = CWAU_Database::get_conversation_messages($conversation_id);

        wp_send_json_success(array('messages' => $messages));
    }

    /**
     * Export conversations to CSV
     */
    public static function export_conversations() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Permessi insufficienti');
        }

        global $wpdb;

        $conversations = $wpdb->get_results("
            SELECT c.*, COUNT(m.id) as message_count
            FROM {$wpdb->prefix}cwau_conversations c
            LEFT JOIN {$wpdb->prefix}cwau_messages m ON c.id = m.conversation_id
            GROUP BY c.id
            ORDER BY c.started_at DESC
        ");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=conversazioni_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        // Header
        fputcsv($output, array('ID', 'Session ID', 'User Email', 'Messages', 'Status', 'Sentiment', 'Started At', 'Ended At'));

        // Data
        foreach ($conversations as $conv) {
            fputcsv($output, array(
                $conv->id,
                $conv->session_id,
                $conv->user_email ?: 'Anonimo',
                $conv->message_count,
                $conv->status,
                $conv->sentiment,
                $conv->started_at,
                $conv->ended_at
            ));
        }

        fclose($output);
        exit;
    }
}
