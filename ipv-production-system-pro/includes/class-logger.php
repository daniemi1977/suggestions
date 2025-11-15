<?php
/**
 * Logger
 *
 * Sistema di logging strutturato per debug e monitoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Pro_Logger {

    /**
     * Log message with context
     *
     * @param string $message
     * @param array $context
     * @param string $level error|warning|info|debug
     */
    public static function log($message, $context = [], $level = 'info') {
        // Only log if WP_DEBUG is enabled
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $prefix = '[IPV Pro';

        // Add level
        if ($level !== 'info') {
            $prefix .= ' ' . strtoupper($level);
        }

        $prefix .= '] ';

        // Add context if provided
        if (!empty($context)) {
            $message .= ' ' . wp_json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        error_log($prefix . $message);

        // Save to custom log table if critical
        if ($level === 'error' && get_option('ipv_pro_save_error_logs', false)) {
            self::save_to_db($message, $context, $level);
        }
    }

    /**
     * Log error
     */
    public static function error($message, $context = []) {
        self::log($message, $context, 'error');
    }

    /**
     * Log warning
     */
    public static function warning($message, $context = []) {
        self::log($message, $context, 'warning');
    }

    /**
     * Log info
     */
    public static function info($message, $context = []) {
        self::log($message, $context, 'info');
    }

    /**
     * Log debug
     */
    public static function debug($message, $context = []) {
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }
        self::log($message, $context, 'debug');
    }

    /**
     * Save error to database
     */
    private static function save_to_db($message, $context, $level) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_error_logs';

        // Check if table exists, if not skip
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return;
        }

        $wpdb->insert(
            $table_name,
            [
                'level' => $level,
                'message' => $message,
                'context' => wp_json_encode($context),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    /**
     * Create error logs table
     */
    public static function create_error_logs_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_error_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get recent error logs
     */
    public static function get_recent_logs($limit = 50, $level = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_error_logs';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return [];
        }

        $where = $level ? $wpdb->prepare("WHERE level = %s", $level) : "";

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} {$where} ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }

    /**
     * Clear old logs (older than 30 days)
     */
    public static function clear_old_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ipv_error_logs';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return;
        }

        $wpdb->query("DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    }
}
