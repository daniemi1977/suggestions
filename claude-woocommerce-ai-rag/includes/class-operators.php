<?php
/**
 * Operators Class
 * Handles operator escalations and notifications
 */

if (!defined('ABSPATH')) exit;

class CWAU_Operators {

    /**
     * Escalate conversation to operator
     */
    public static function escalate() {
        check_ajax_referer('cwau_chat_nonce', 'nonce');

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : 'Richiesta utente';

        if (!$conversation_id) {
            wp_send_json_error(array('message' => 'ID conversazione non valido'));
        }

        // Create escalation
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'cwau_escalations',
            array(
                'conversation_id' => $conversation_id,
                'reason' => $reason,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );

        $escalation_id = $wpdb->insert_id;

        // Update conversation status
        CWAU_Database::update_conversation_status($conversation_id, 'escalated');

        // Send email notification
        self::send_escalation_email($escalation_id, $conversation_id, $reason);

        wp_send_json_success(array(
            'message' => 'Escalation creata con successo. Un operatore ti contatterà presto.',
            'escalation_id' => $escalation_id
        ));
    }

    /**
     * Auto-escalate based on keywords
     */
    public static function auto_escalate($conversation_id, $trigger_message) {
        global $wpdb;

        // Check if already escalated
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}cwau_escalations
            WHERE conversation_id = %d AND status != 'resolved'
        ", $conversation_id));

        if ($existing) {
            return; // Already escalated
        }

        // Create escalation
        $wpdb->insert(
            $wpdb->prefix . 'cwau_escalations',
            array(
                'conversation_id' => $conversation_id,
                'reason' => 'Auto-escalation: ' . substr($trigger_message, 0, 200),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );

        $escalation_id = $wpdb->insert_id;

        // Update conversation status
        CWAU_Database::update_conversation_status($conversation_id, 'escalated');

        // Send email
        self::send_escalation_email($escalation_id, $conversation_id, $trigger_message);
    }

    /**
     * Send escalation email to operator
     */
    private static function send_escalation_email($escalation_id, $conversation_id, $reason) {
        $operator_email = get_option('cwau_operator_email', get_option('admin_email'));

        if (!$operator_email) {
            return;
        }

        $subject = sprintf('[%s] Nuova Escalation Chat #%d', get_bloginfo('name'), $escalation_id);

        $message = "Una nuova escalation richiede la tua attenzione.\n\n";
        $message .= "ID Escalation: $escalation_id\n";
        $message .= "ID Conversazione: $conversation_id\n";
        $message .= "Motivo: $reason\n\n";
        $message .= "Visualizza i dettagli: " . admin_url('admin.php?page=cwau-escalations') . "\n";

        wp_mail($operator_email, $subject, $message);
    }

    /**
     * Get escalations list
     */
    public static function get_escalations() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti'));
        }

        global $wpdb;

        $escalations = $wpdb->get_results("
            SELECT e.*, c.user_email, c.session_id
            FROM {$wpdb->prefix}cwau_escalations e
            LEFT JOIN {$wpdb->prefix}cwau_conversations c ON e.conversation_id = c.id
            ORDER BY e.created_at DESC
            LIMIT 50
        ");

        wp_send_json_success(array('escalations' => $escalations));
    }

    /**
     * Resolve escalation
     */
    public static function resolve_escalation($escalation_id, $notes = '') {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'cwau_escalations',
            array(
                'status' => 'resolved',
                'notes' => $notes,
                'resolved_at' => current_time('mysql')
            ),
            array('id' => $escalation_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        // Update conversation status
        $conversation_id = $wpdb->get_var($wpdb->prepare("
            SELECT conversation_id FROM {$wpdb->prefix}cwau_escalations WHERE id = %d
        ", $escalation_id));

        if ($conversation_id) {
            CWAU_Database::update_conversation_status($conversation_id, 'completed');
        }
    }
}
