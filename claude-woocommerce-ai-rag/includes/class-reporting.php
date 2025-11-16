<?php
/**
 * Advanced Reporting Class
 * Charts, KPIs, custom reports, and data export
 */

if (!defined('ABSPATH')) exit;

class CWAU_Reporting {

    /**
     * Get overview metrics
     */
    public static function get_overview_metrics($period = 'month') {
        global $wpdb;

        $date_filter = self::get_date_filter($period);

        // Chat metrics
        $total_chats = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_conversations WHERE {$date_filter}"
        );

        $total_messages = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_messages WHERE {$date_filter}"
        );

        $avg_messages_per_chat = $total_chats > 0 ? round($total_messages / $total_chats, 2) : 0;

        $escalation_rate = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_conversations
             WHERE status = 'escalated' AND {$date_filter}"
        );
        $escalation_rate = $total_chats > 0 ? round(($escalation_rate / $total_chats) * 100, 2) : 0;

        // Sentiment analysis
        $sentiment_data = $wpdb->get_results(
            "SELECT sentiment, COUNT(*) as count
             FROM {$wpdb->prefix}cwau_conversations
             WHERE {$date_filter}
             GROUP BY sentiment",
            OBJECT_K
        );

        // Ticket metrics
        $ticket_stats = CWAU_Tickets::get_stats($period);

        // CRM metrics
        $new_customers = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_customers WHERE {$date_filter}"
        );

        $hot_leads = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_customers
             WHERE lead_score >= 80 AND {$date_filter}"
        );

        $total_deals_value = $wpdb->get_var(
            "SELECT SUM(value) FROM {$wpdb->prefix}cwau_deals WHERE {$date_filter}"
        );

        $won_deals = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_deals
             WHERE stage = 'closed_won' AND {$date_filter}"
        );

        return array(
            'chat' => array(
                'total_conversations' => (int) $total_chats,
                'total_messages' => (int) $total_messages,
                'avg_messages_per_chat' => $avg_messages_per_chat,
                'escalation_rate' => $escalation_rate,
                'sentiment' => array(
                    'positive' => isset($sentiment_data['positive']) ? (int) $sentiment_data['positive']->count : 0,
                    'neutral' => isset($sentiment_data['neutral']) ? (int) $sentiment_data['neutral']->count : 0,
                    'negative' => isset($sentiment_data['negative']) ? (int) $sentiment_data['negative']->count : 0
                )
            ),
            'tickets' => $ticket_stats,
            'crm' => array(
                'new_customers' => (int) $new_customers,
                'hot_leads' => (int) $hot_leads,
                'total_deals_value' => (float) $total_deals_value,
                'won_deals' => (int) $won_deals
            )
        );
    }

    /**
     * Get chat volume by day/hour
     */
    public static function get_chat_volume_chart($period = 'week') {
        global $wpdb;

        $date_filter = self::get_date_filter($period);
        $group_by = $period === 'today' ? 'HOUR(started_at)' : 'DATE(started_at)';

        $results = $wpdb->get_results(
            "SELECT {$group_by} as period, COUNT(*) as count
             FROM {$wpdb->prefix}cwau_conversations
             WHERE {$date_filter}
             GROUP BY period
             ORDER BY period ASC"
        );

        $labels = array();
        $data = array();

        foreach ($results as $row) {
            $labels[] = $period === 'today' ? $row->period . ':00' : $row->period;
            $data[] = (int) $row->count;
        }

        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => 'Conversations',
                    'data' => $data,
                    'backgroundColor' => 'rgba(37, 99, 235, 0.5)',
                    'borderColor' => 'rgba(37, 99, 235, 1)',
                    'borderWidth' => 2
                )
            )
        );
    }

    /**
     * Get sentiment trend
     */
    public static function get_sentiment_trend($period = 'week') {
        global $wpdb;

        $date_filter = self::get_date_filter($period);
        $group_by = $period === 'today' ? 'HOUR(started_at)' : 'DATE(started_at)';

        $results = $wpdb->get_results(
            "SELECT {$group_by} as period, sentiment, COUNT(*) as count
             FROM {$wpdb->prefix}cwau_conversations
             WHERE {$date_filter}
             GROUP BY period, sentiment
             ORDER BY period ASC"
        );

        $labels = array();
        $positive_data = array();
        $neutral_data = array();
        $negative_data = array();

        $grouped = array();
        foreach ($results as $row) {
            $period_label = $period === 'today' ? $row->period . ':00' : $row->period;
            if (!isset($grouped[$period_label])) {
                $grouped[$period_label] = array('positive' => 0, 'neutral' => 0, 'negative' => 0);
            }
            $grouped[$period_label][$row->sentiment] = (int) $row->count;
        }

        foreach ($grouped as $period_label => $sentiments) {
            $labels[] = $period_label;
            $positive_data[] = $sentiments['positive'];
            $neutral_data[] = $sentiments['neutral'];
            $negative_data[] = $sentiments['negative'];
        }

        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => 'Positive',
                    'data' => $positive_data,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 2
                ),
                array(
                    'label' => 'Neutral',
                    'data' => $neutral_data,
                    'backgroundColor' => 'rgba(156, 163, 175, 0.5)',
                    'borderColor' => 'rgba(156, 163, 175, 1)',
                    'borderWidth' => 2
                ),
                array(
                    'label' => 'Negative',
                    'data' => $negative_data,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 2
                )
            )
        );
    }

    /**
     * Get ticket status distribution
     */
    public static function get_ticket_status_chart() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count
             FROM {$wpdb->prefix}cwau_tickets
             GROUP BY status"
        );

        $labels = array();
        $data = array();
        $colors = array(
            'new' => '#3b82f6',
            'open' => '#8b5cf6',
            'pending' => '#f59e0b',
            'on_hold' => '#ef4444',
            'solved' => '#10b981',
            'closed' => '#6b7280'
        );
        $backgroundColor = array();

        foreach ($results as $row) {
            $labels[] = ucfirst(str_replace('_', ' ', $row->status));
            $data[] = (int) $row->count;
            $backgroundColor[] = $colors[$row->status] ?? '#6b7280';
        }

        return array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $data,
                    'backgroundColor' => $backgroundColor
                )
            )
        );
    }

    /**
     * Get sales pipeline funnel
     */
    public static function get_pipeline_funnel() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT stage, COUNT(*) as count, SUM(value) as total_value
             FROM {$wpdb->prefix}cwau_deals
             GROUP BY stage
             ORDER BY FIELD(stage, 'lead', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost')"
        );

        $labels = array();
        $counts = array();
        $values = array();

        foreach ($results as $row) {
            $labels[] = ucfirst(str_replace('_', ' ', $row->stage));
            $counts[] = (int) $row->count;
            $values[] = (float) $row->total_value;
        }

        return array(
            'labels' => $labels,
            'counts' => $counts,
            'values' => $values
        );
    }

    /**
     * Get operator performance
     */
    public static function get_operator_performance($period = 'week') {
        global $wpdb;

        $date_filter = self::get_date_filter($period, 'joined_at');

        $results = $wpdb->get_results(
            "SELECT os.operator_id,
                    COUNT(DISTINCT os.conversation_id) as total_chats,
                    AVG(TIMESTAMPDIFF(MINUTE, os.joined_at, os.left_at)) as avg_handle_time,
                    SUM(TIMESTAMPDIFF(MINUTE, os.joined_at, IFNULL(os.left_at, NOW()))) as total_time
             FROM {$wpdb->prefix}cwau_operator_sessions os
             WHERE {$date_filter}
             GROUP BY os.operator_id
             ORDER BY total_chats DESC"
        );

        $data = array();

        foreach ($results as $row) {
            $user = get_userdata($row->operator_id);
            if ($user) {
                $data[] = array(
                    'operator_id' => (int) $row->operator_id,
                    'operator_name' => $user->display_name,
                    'total_chats' => (int) $row->total_chats,
                    'avg_handle_time' => round($row->avg_handle_time, 2),
                    'total_hours' => round($row->total_time / 60, 2)
                );
            }
        }

        return $data;
    }

    /**
     * Get top customers by revenue
     */
    public static function get_top_customers($limit = 10) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, CONCAT(first_name, ' ', last_name) as name, email,
                    total_revenue, total_orders, lead_score
             FROM {$wpdb->prefix}cwau_customers
             WHERE total_revenue > 0
             ORDER BY total_revenue DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Get conversion funnel
     */
    public static function get_conversion_funnel($period = 'month') {
        global $wpdb;

        $date_filter = self::get_date_filter($period, 'created_at');

        $total_chats = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_conversations WHERE {$date_filter}"
        );

        $chats_with_products = $wpdb->get_var(
            "SELECT COUNT(DISTINCT conversation_id)
             FROM {$wpdb->prefix}cwau_messages
             WHERE {$date_filter} AND metadata LIKE '%product%'"
        );

        $cart_additions = $wpdb->get_var(
            "SELECT COUNT(DISTINCT session_id)
             FROM {$wpdb->prefix}woocommerce_sessions
             WHERE {$date_filter}"
        );

        $orders = $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}posts
             WHERE post_type = 'shop_order'
             AND post_status IN ('wc-processing', 'wc-completed')
             AND {$date_filter}"
        );

        return array(
            'total_chats' => (int) $total_chats,
            'product_views' => (int) $chats_with_products,
            'cart_additions' => (int) $cart_additions,
            'orders' => (int) $orders,
            'conversion_rate' => $total_chats > 0 ? round(($orders / $total_chats) * 100, 2) : 0
        );
    }

    /**
     * Export data to CSV
     */
    public static function export_to_csv($report_type, $period = 'month') {
        $data = match($report_type) {
            'conversations' => self::export_conversations($period),
            'tickets' => self::export_tickets($period),
            'customers' => self::export_customers($period),
            'operators' => self::export_operator_performance($period),
            default => array()
        };

        if (empty($data)) {
            return false;
        }

        $filename = "cwau_{$report_type}_" . date('Y-m-d') . ".csv";
        $filepath = wp_upload_dir()['basedir'] . '/' . $filename;

        $file = fopen($filepath, 'w');

        // Write headers
        fputcsv($file, array_keys($data[0]));

        // Write data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return wp_upload_dir()['baseurl'] . '/' . $filename;
    }

    /**
     * Export conversations data
     */
    private static function export_conversations($period) {
        global $wpdb;

        $date_filter = self::get_date_filter($period, 'started_at');

        return $wpdb->get_results(
            "SELECT c.id, c.session_id, c.user_email, c.status, c.sentiment,
                    c.started_at, c.ended_at,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}cwau_messages WHERE conversation_id = c.id) as messages
             FROM {$wpdb->prefix}cwau_conversations c
             WHERE {$date_filter}
             ORDER BY c.started_at DESC",
            ARRAY_A
        );
    }

    /**
     * Export tickets data
     */
    private static function export_tickets($period) {
        global $wpdb;

        $date_filter = self::get_date_filter($period, 'created_at');

        return $wpdb->get_results(
            "SELECT ticket_number, subject, status, priority, category,
                    created_at, resolved_at, sla_breached, satisfaction_rating
             FROM {$wpdb->prefix}cwau_tickets
             WHERE {$date_filter}
             ORDER BY created_at DESC",
            ARRAY_A
        );
    }

    /**
     * Export customers data
     */
    private static function export_customers($period) {
        global $wpdb;

        $date_filter = self::get_date_filter($period, 'created_at');

        return $wpdb->get_results(
            "SELECT email, first_name, last_name, company, phone,
                    lead_score, lifecycle_stage, total_revenue, total_orders,
                    created_at
             FROM {$wpdb->prefix}cwau_customers
             WHERE {$date_filter}
             ORDER BY created_at DESC",
            ARRAY_A
        );
    }

    /**
     * Export operator performance
     */
    private static function export_operator_performance($period) {
        $performance = self::get_operator_performance($period);

        return array_map(function($item) {
            return array(
                'operator_name' => $item['operator_name'],
                'total_chats' => $item['total_chats'],
                'avg_handle_time_minutes' => $item['avg_handle_time'],
                'total_hours' => $item['total_hours']
            );
        }, $performance);
    }

    /**
     * Get date filter SQL
     */
    private static function get_date_filter($period, $field = 'created_at') {
        return match($period) {
            'today' => "DATE({$field}) = CURDATE()",
            'yesterday' => "DATE({$field}) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
            'week' => "{$field} >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "{$field} >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            'quarter' => "{$field} >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
            'year' => "{$field} >= DATE_SUB(NOW(), INTERVAL 1 YEAR)",
            default => "1=1"
        };
    }

    /**
     * Get AI usage statistics
     */
    public static function get_ai_usage_stats($period = 'month') {
        global $wpdb;

        $date_filter = self::get_date_filter($period);

        $total_ai_messages = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_messages
             WHERE sender = 'ai' AND {$date_filter}"
        );

        $openai_usage = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_messages
             WHERE sender = 'ai'
             AND metadata LIKE '%openai%'
             AND {$date_filter}"
        );

        $claude_usage = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_messages
             WHERE sender = 'ai'
             AND metadata LIKE '%claude%'
             AND {$date_filter}"
        );

        return array(
            'total_ai_messages' => (int) $total_ai_messages,
            'openai_messages' => (int) $openai_usage,
            'claude_messages' => (int) $claude_usage,
            'openai_percentage' => $total_ai_messages > 0 ? round(($openai_usage / $total_ai_messages) * 100, 2) : 0,
            'claude_percentage' => $total_ai_messages > 0 ? round(($claude_usage / $total_ai_messages) * 100, 2) : 0
        );
    }

    /**
     * AJAX: Get report data
     */
    public static function ajax_get_report() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : 'overview';
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';

        $data = match($report_type) {
            'overview' => self::get_overview_metrics($period),
            'chat_volume' => self::get_chat_volume_chart($period),
            'sentiment_trend' => self::get_sentiment_trend($period),
            'ticket_status' => self::get_ticket_status_chart(),
            'pipeline' => self::get_pipeline_funnel(),
            'operators' => self::get_operator_performance($period),
            'top_customers' => self::get_top_customers(),
            'conversion_funnel' => self::get_conversion_funnel($period),
            'ai_usage' => self::get_ai_usage_stats($period),
            default => array()
        };

        wp_send_json_success(array('data' => $data));
    }

    /**
     * AJAX: Export report
     */
    public static function ajax_export_report() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';

        if (!$report_type) {
            wp_send_json_error(array('message' => 'Report type not specified'));
        }

        $file_url = self::export_to_csv($report_type, $period);

        if ($file_url) {
            wp_send_json_success(array('file_url' => $file_url));
        } else {
            wp_send_json_error(array('message' => 'Failed to export report'));
        }
    }
}
