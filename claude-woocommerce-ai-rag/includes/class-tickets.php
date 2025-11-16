<?php
/**
 * Ticketing System Class
 * Complete trouble ticket management inspired by Zendesk and Freshdesk
 */

if (!defined('ABSPATH')) exit;

class CWAU_Tickets {

    /**
     * Create new ticket
     */
    public static function create_ticket($data) {
        global $wpdb;

        // Generate unique ticket number
        $ticket_number = self::generate_ticket_number();

        // Calculate SLA due date based on priority
        $sla_due_at = self::calculate_sla_due_date($data['priority'] ?? 'normal');

        // Auto-assign if auto-assignment is enabled
        $assigned_to = self::auto_assign_ticket($data);

        $insert_data = array(
            'ticket_number' => $ticket_number,
            'customer_id' => $data['customer_id'] ?? null,
            'conversation_id' => $data['conversation_id'] ?? null,
            'subject' => sanitize_text_field($data['subject']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'status' => 'new',
            'priority' => self::calculate_priority($data),
            'category' => sanitize_text_field($data['category'] ?? 'general'),
            'assigned_to' => $assigned_to,
            'assigned_team' => $data['assigned_team'] ?? null,
            'tags' => is_array($data['tags'] ?? null) ? implode(',', $data['tags']) : null,
            'sla_due_at' => $sla_due_at,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'cwau_tickets',
            $insert_data,
            array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            $ticket_id = $wpdb->insert_id;

            // Log activity in CRM if customer exists
            if (!empty($data['customer_id'])) {
                CWAU_CRM::log_activity(
                    $data['customer_id'],
                    null,
                    'chat',
                    'Ticket Created: ' . $ticket_number,
                    'New support ticket created: ' . $data['subject'],
                    array('ticket_id' => $ticket_id)
                );
            }

            // Send notification to assigned agent
            if ($assigned_to) {
                self::notify_agent_assignment($ticket_id, $assigned_to);
            }

            // Create initial reply if description provided
            if (!empty($data['description'])) {
                self::add_reply($ticket_id, array(
                    'author_id' => $data['customer_id'] ?? null,
                    'author_type' => 'customer',
                    'content' => $data['description'],
                    'is_internal' => false
                ));
            }

            return array(
                'success' => true,
                'ticket_id' => $ticket_id,
                'ticket_number' => $ticket_number
            );
        }

        return array('success' => false, 'error' => 'Failed to create ticket');
    }

    /**
     * Generate unique ticket number
     */
    private static function generate_ticket_number() {
        global $wpdb;

        do {
            $number = 'TKT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_tickets WHERE ticket_number = %s",
                $number
            ));
        } while ($exists > 0);

        return $number;
    }

    /**
     * Calculate ticket priority based on customer, keywords, and metadata
     */
    private static function calculate_priority($data) {
        $priority = $data['priority'] ?? 'normal';

        // VIP customer detection
        if (!empty($data['customer_id'])) {
            $customer = CWAU_CRM::get_customer($data['customer_id']);
            if ($customer) {
                // High-value customers get higher priority
                if ($customer->total_revenue > 1000 || $customer->lead_score > 80) {
                    if ($priority === 'normal') $priority = 'high';
                }
            }
        }

        // Keyword-based priority escalation
        $urgent_keywords = array('urgent', 'emergency', 'critical', 'broken', 'down', 'not working', 'crashed');
        $text = strtolower(($data['subject'] ?? '') . ' ' . ($data['description'] ?? ''));

        foreach ($urgent_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $priority = 'urgent';
                break;
            }
        }

        return $priority;
    }

    /**
     * Calculate SLA due date based on priority
     */
    private static function calculate_sla_due_date($priority) {
        $hours = array(
            'urgent' => 2,    // 2 hours
            'high' => 8,      // 8 hours
            'normal' => 24,   // 24 hours
            'low' => 48       // 48 hours
        );

        $sla_hours = $hours[$priority] ?? 24;
        return date('Y-m-d H:i:s', strtotime("+{$sla_hours} hours"));
    }

    /**
     * Auto-assign ticket using round-robin or load balancing
     */
    private static function auto_assign_ticket($data) {
        global $wpdb;

        // Get auto-assignment settings
        $auto_assign = get_option('cwau_ticket_auto_assign', 'round_robin');

        if ($auto_assign === 'none') {
            return null;
        }

        // Get available agents (users with 'manage_woocommerce' capability)
        $agents = get_users(array(
            'role__in' => array('administrator', 'shop_manager'),
            'number' => -1
        ));

        if (empty($agents)) {
            return null;
        }

        if ($auto_assign === 'round_robin') {
            // Round-robin: assign to agent with oldest last assignment
            $agent_loads = array();
            foreach ($agents as $agent) {
                $last_assigned = $wpdb->get_var($wpdb->prepare(
                    "SELECT MAX(created_at) FROM {$wpdb->prefix}cwau_tickets WHERE assigned_to = %d",
                    $agent->ID
                ));
                $agent_loads[$agent->ID] = $last_assigned ? strtotime($last_assigned) : 0;
            }
            asort($agent_loads);
            return key($agent_loads);
        }

        if ($auto_assign === 'load_balancing') {
            // Load balancing: assign to agent with fewest open tickets
            $agent_loads = array();
            foreach ($agents as $agent) {
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_tickets
                     WHERE assigned_to = %d AND status IN ('new', 'open', 'pending')",
                    $agent->ID
                ));
                $agent_loads[$agent->ID] = (int) $count;
            }
            asort($agent_loads);
            return key($agent_loads);
        }

        // Default: random assignment
        return $agents[array_rand($agents)]->ID;
    }

    /**
     * Add reply to ticket
     */
    public static function add_reply($ticket_id, $data) {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'cwau_ticket_replies',
            array(
                'ticket_id' => $ticket_id,
                'author_id' => $data['author_id'] ?? null,
                'author_type' => $data['author_type'] ?? 'customer',
                'content' => sanitize_textarea_field($data['content']),
                'is_internal' => $data['is_internal'] ?? false,
                'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s')
        );

        if ($result) {
            $reply_id = $wpdb->insert_id;

            // Update ticket updated_at timestamp
            $wpdb->update(
                $wpdb->prefix . 'cwau_tickets',
                array('updated_at' => current_time('mysql')),
                array('id' => $ticket_id),
                array('%s'),
                array('%d')
            );

            // Update first_response_at if this is first agent reply
            if ($data['author_type'] === 'agent') {
                $ticket = self::get_ticket($ticket_id);
                if (empty($ticket->first_response_at)) {
                    $wpdb->update(
                        $wpdb->prefix . 'cwau_tickets',
                        array('first_response_at' => current_time('mysql')),
                        array('id' => $ticket_id),
                        array('%s'),
                        array('%d')
                    );
                }

                // Auto-update status from 'new' to 'open'
                if ($ticket->status === 'new') {
                    self::update_status($ticket_id, 'open');
                }
            }

            // Send notifications
            if (!$data['is_internal']) {
                self::notify_ticket_update($ticket_id, $reply_id);
            }

            return $reply_id;
        }

        return false;
    }

    /**
     * Get ticket by ID or ticket number
     */
    public static function get_ticket($identifier) {
        global $wpdb;

        if (is_numeric($identifier)) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cwau_tickets WHERE id = %d",
                $identifier
            ));
        } else {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cwau_tickets WHERE ticket_number = %s",
                $identifier
            ));
        }
    }

    /**
     * Get ticket replies
     */
    public static function get_replies($ticket_id, $include_internal = false) {
        global $wpdb;

        $where = $include_internal ? '' : ' AND is_internal = 0';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_ticket_replies
             WHERE ticket_id = %d {$where}
             ORDER BY created_at ASC",
            $ticket_id
        ));
    }

    /**
     * Update ticket status
     */
    public static function update_status($ticket_id, $new_status, $metadata = array()) {
        global $wpdb;

        $update_data = array(
            'status' => $new_status,
            'updated_at' => current_time('mysql')
        );

        // Handle status-specific updates
        if ($new_status === 'solved' && empty(self::get_ticket($ticket_id)->resolved_at)) {
            $update_data['resolved_at'] = current_time('mysql');
        }

        if ($new_status === 'closed') {
            $update_data['closed_at'] = current_time('mysql');

            // Send CSAT survey if enabled
            if (get_option('cwau_ticket_csat_enabled', true)) {
                self::send_csat_survey($ticket_id);
            }
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'cwau_tickets',
            $update_data,
            array('id' => $ticket_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );

        // Check SLA breach
        self::check_sla_breach($ticket_id);

        return $result !== false;
    }

    /**
     * Check and mark SLA breach
     */
    private static function check_sla_breach($ticket_id) {
        global $wpdb;

        $ticket = self::get_ticket($ticket_id);

        if ($ticket && $ticket->sla_due_at && !$ticket->sla_breached) {
            if (strtotime($ticket->sla_due_at) < time() && !in_array($ticket->status, array('solved', 'closed'))) {
                $wpdb->update(
                    $wpdb->prefix . 'cwau_tickets',
                    array('sla_breached' => true),
                    array('id' => $ticket_id),
                    array('%d'),
                    array('%d')
                );

                // Notify administrators
                self::notify_sla_breach($ticket_id);
            }
        }
    }

    /**
     * Assign ticket to agent
     */
    public static function assign_ticket($ticket_id, $agent_id, $team = null) {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'cwau_tickets',
            array(
                'assigned_to' => $agent_id,
                'assigned_team' => $team,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $ticket_id),
            array('%d', '%s', '%s'),
            array('%d')
        );

        if ($result) {
            self::notify_agent_assignment($ticket_id, $agent_id);

            // Add internal note
            self::add_reply($ticket_id, array(
                'author_type' => 'system',
                'content' => 'Ticket assigned to ' . get_userdata($agent_id)->display_name,
                'is_internal' => true
            ));
        }

        return $result !== false;
    }

    /**
     * Get tickets with filters
     */
    public static function get_tickets($filters = array()) {
        global $wpdb;

        $where = array('1=1');
        $params = array();

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = implode(',', array_fill(0, count($filters['status']), '%s'));
                $where[] = "status IN ($placeholders)";
                $params = array_merge($params, $filters['status']);
            } else {
                $where[] = 'status = %s';
                $params[] = $filters['status'];
            }
        }

        if (!empty($filters['priority'])) {
            $where[] = 'priority = %s';
            $params[] = $filters['priority'];
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 'assigned_to = %d';
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['customer_id'])) {
            $where[] = 'customer_id = %d';
            $params[] = $filters['customer_id'];
        }

        if (!empty($filters['category'])) {
            $where[] = 'category = %s';
            $params[] = $filters['category'];
        }

        if (isset($filters['sla_breached'])) {
            $where[] = 'sla_breached = %d';
            $params[] = $filters['sla_breached'] ? 1 : 0;
        }

        $where_sql = implode(' AND ', $where);
        $order = $filters['orderby'] ?? 'created_at';
        $order_dir = $filters['order'] ?? 'DESC';
        $limit = isset($filters['limit']) ? 'LIMIT ' . (int)$filters['limit'] : '';
        $offset = isset($filters['offset']) ? 'OFFSET ' . (int)$filters['offset'] : '';

        $sql = "SELECT * FROM {$wpdb->prefix}cwau_tickets WHERE {$where_sql} ORDER BY {$order} {$order_dir} {$limit} {$offset}";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Get ticket count with filters
     */
    public static function count_tickets($filters = array()) {
        global $wpdb;

        $where = array('1=1');
        $params = array();

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = implode(',', array_fill(0, count($filters['status']), '%s'));
                $where[] = "status IN ($placeholders)";
                $params = array_merge($params, $filters['status']);
            } else {
                $where[] = 'status = %s';
                $params[] = $filters['status'];
            }
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 'assigned_to = %d';
            $params[] = $filters['assigned_to'];
        }

        if (isset($filters['sla_breached'])) {
            $where[] = 'sla_breached = %d';
            $params[] = $filters['sla_breached'] ? 1 : 0;
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_tickets WHERE {$where_sql}";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Merge tickets
     */
    public static function merge_tickets($source_ticket_id, $target_ticket_id) {
        global $wpdb;

        // Move all replies from source to target
        $wpdb->update(
            $wpdb->prefix . 'cwau_ticket_replies',
            array('ticket_id' => $target_ticket_id),
            array('ticket_id' => $source_ticket_id),
            array('%d'),
            array('%d')
        );

        // Add internal note to target
        $source = self::get_ticket($source_ticket_id);
        self::add_reply($target_ticket_id, array(
            'author_type' => 'system',
            'content' => "Merged from ticket {$source->ticket_number}",
            'is_internal' => true
        ));

        // Close source ticket
        self::update_status($source_ticket_id, 'closed', array('merged_into' => $target_ticket_id));

        return true;
    }

    /**
     * Get macros (canned responses)
     */
    public static function get_macros() {
        $default_macros = array(
            'welcome' => array(
                'name' => 'Welcome Message',
                'content' => 'Thank you for contacting us! We\'re here to help. Let me look into that for you.'
            ),
            'investigating' => array(
                'name' => 'Investigating',
                'content' => 'I\'m currently investigating this issue. I\'ll get back to you shortly with an update.'
            ),
            'resolved' => array(
                'name' => 'Issue Resolved',
                'content' => 'Great news! This issue has been resolved. Please let me know if you need anything else.'
            ),
            'escalation' => array(
                'name' => 'Escalating to Manager',
                'content' => 'I\'m escalating this to our senior team for further assistance. Someone will be in touch soon.'
            ),
            'waiting_customer' => array(
                'name' => 'Waiting for Customer',
                'content' => 'I need some additional information from you to proceed. Could you please provide the details requested above?'
            )
        );

        $custom_macros = get_option('cwau_ticket_macros', array());
        return array_merge($default_macros, $custom_macros);
    }

    /**
     * Save CSAT rating
     */
    public static function save_csat($ticket_id, $rating, $comment = '') {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'cwau_tickets',
            array(
                'satisfaction_rating' => $rating,
                'satisfaction_comment' => $comment
            ),
            array('id' => $ticket_id),
            array('%d', '%s'),
            array('%d')
        );
    }

    /**
     * Get ticket statistics
     */
    public static function get_stats($period = 'week') {
        global $wpdb;

        $date_filter = match($period) {
            'today' => "DATE(created_at) = CURDATE()",
            'week' => "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            default => "1=1"
        };

        // Total tickets
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cwau_tickets WHERE {$date_filter}");

        // By status
        $by_status = $wpdb->get_results(
            "SELECT status, COUNT(*) as count
             FROM {$wpdb->prefix}cwau_tickets
             WHERE {$date_filter}
             GROUP BY status",
            OBJECT_K
        );

        // By priority
        $by_priority = $wpdb->get_results(
            "SELECT priority, COUNT(*) as count
             FROM {$wpdb->prefix}cwau_tickets
             WHERE {$date_filter}
             GROUP BY priority",
            OBJECT_K
        );

        // SLA performance
        $sla_breached = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cwau_tickets
             WHERE {$date_filter} AND sla_breached = 1"
        );

        // Average resolution time (in hours)
        $avg_resolution = $wpdb->get_var(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at))
             FROM {$wpdb->prefix}cwau_tickets
             WHERE {$date_filter} AND resolved_at IS NOT NULL"
        );

        // Average CSAT
        $avg_csat = $wpdb->get_var(
            "SELECT AVG(satisfaction_rating)
             FROM {$wpdb->prefix}cwau_tickets
             WHERE {$date_filter} AND satisfaction_rating IS NOT NULL"
        );

        return array(
            'total' => (int) $total,
            'by_status' => $by_status,
            'by_priority' => $by_priority,
            'sla_breached' => (int) $sla_breached,
            'sla_compliance' => $total > 0 ? round((($total - $sla_breached) / $total) * 100, 2) : 100,
            'avg_resolution_hours' => round($avg_resolution, 2),
            'avg_csat' => round($avg_csat, 2)
        );
    }

    /**
     * Send CSAT survey
     */
    private static function send_csat_survey($ticket_id) {
        $ticket = self::get_ticket($ticket_id);

        if (!$ticket || !$ticket->customer_id) {
            return;
        }

        $customer = CWAU_CRM::get_customer($ticket->customer_id);

        if (!$customer || !$customer->email) {
            return;
        }

        $survey_url = add_query_arg(array(
            'ticket_id' => $ticket_id,
            'token' => wp_hash($ticket_id . $ticket->ticket_number)
        ), home_url('/ticket-survey/'));

        $subject = sprintf('How was your support experience? (Ticket %s)', $ticket->ticket_number);

        $message = sprintf(
            "Hi %s,\n\nYour ticket %s has been resolved.\n\nWe'd love to hear about your experience. Please take a moment to rate our support:\n\n%s\n\nThank you!",
            $customer->first_name ?: 'there',
            $ticket->ticket_number,
            $survey_url
        );

        wp_mail($customer->email, $subject, $message);
    }

    /**
     * Notify agent of ticket assignment
     */
    private static function notify_agent_assignment($ticket_id, $agent_id) {
        $ticket = self::get_ticket($ticket_id);
        $agent = get_userdata($agent_id);

        if (!$agent) return;

        $subject = sprintf('New ticket assigned: %s', $ticket->ticket_number);
        $message = sprintf(
            "A new ticket has been assigned to you.\n\nTicket: %s\nSubject: %s\nPriority: %s\nDue: %s\n\nView ticket: %s",
            $ticket->ticket_number,
            $ticket->subject,
            ucfirst($ticket->priority),
            date('Y-m-d H:i', strtotime($ticket->sla_due_at)),
            admin_url('admin.php?page=cwau-tickets&ticket_id=' . $ticket_id)
        );

        wp_mail($agent->user_email, $subject, $message);
    }

    /**
     * Notify on ticket update
     */
    private static function notify_ticket_update($ticket_id, $reply_id) {
        $ticket = self::get_ticket($ticket_id);
        $reply = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_ticket_replies WHERE id = %d",
            $reply_id
        ));

        // Notify customer if agent replied
        if ($reply->author_type === 'agent' && $ticket->customer_id) {
            $customer = CWAU_CRM::get_customer($ticket->customer_id);
            if ($customer && $customer->email) {
                $subject = sprintf('Update on ticket %s', $ticket->ticket_number);
                $message = sprintf(
                    "Hi %s,\n\nThere's a new update on your ticket %s:\n\n%s\n\nView ticket: %s",
                    $customer->first_name ?: 'there',
                    $ticket->ticket_number,
                    $reply->content,
                    home_url('/my-tickets/?ticket_id=' . $ticket_id)
                );
                wp_mail($customer->email, $subject, $message);
            }
        }

        // Notify assigned agent if customer replied
        if ($reply->author_type === 'customer' && $ticket->assigned_to) {
            $agent = get_userdata($ticket->assigned_to);
            if ($agent) {
                $subject = sprintf('Customer replied to ticket %s', $ticket->ticket_number);
                $message = sprintf(
                    "Customer replied to ticket %s:\n\n%s\n\nView ticket: %s",
                    $ticket->ticket_number,
                    $reply->content,
                    admin_url('admin.php?page=cwau-tickets&ticket_id=' . $ticket_id)
                );
                wp_mail($agent->user_email, $subject, $message);
            }
        }
    }

    /**
     * Notify on SLA breach
     */
    private static function notify_sla_breach($ticket_id) {
        $ticket = self::get_ticket($ticket_id);
        $admin_email = get_option('admin_email');

        $subject = sprintf('SLA BREACH: Ticket %s', $ticket->ticket_number);
        $message = sprintf(
            "ALERT: Ticket %s has breached its SLA.\n\nTicket: %s\nSubject: %s\nPriority: %s\nAssigned to: %s\nDue: %s\n\nView ticket: %s",
            $ticket->ticket_number,
            $ticket->ticket_number,
            $ticket->subject,
            ucfirst($ticket->priority),
            $ticket->assigned_to ? get_userdata($ticket->assigned_to)->display_name : 'Unassigned',
            date('Y-m-d H:i', strtotime($ticket->sla_due_at)),
            admin_url('admin.php?page=cwau-tickets&ticket_id=' . $ticket_id)
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * AJAX: Create ticket from chat escalation
     */
    public static function ajax_create_from_escalation() {
        check_ajax_referer('cwau_chat_nonce', 'nonce');

        $conversation_id = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

        if (!$conversation_id) {
            wp_send_json_error(array('message' => 'Invalid conversation ID'));
        }

        // Get conversation details
        $conversation = CWAU_Database::get_conversation_by_session($conversation_id);

        if (!$conversation) {
            wp_send_json_error(array('message' => 'Conversation not found'));
        }

        // Get or create customer
        $customer_id = null;
        if ($conversation->user_id) {
            $user = get_userdata($conversation->user_id);
            $customer = CWAU_CRM::get_customer($user->user_email);
            if (!$customer) {
                $customer_result = CWAU_CRM::upsert_customer(array(
                    'email' => $user->user_email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'user_id' => $user->ID
                ));
                $customer_id = $customer_result['customer_id'];
            } else {
                $customer_id = $customer->id;
            }
        }

        // Create ticket
        $result = self::create_ticket(array(
            'customer_id' => $customer_id,
            'conversation_id' => $conversation_id,
            'subject' => $subject ?: 'Support Request from Chat',
            'description' => $description,
            'priority' => 'normal',
            'category' => 'chat_escalation'
        ));

        if ($result['success']) {
            // Update conversation status
            CWAU_Database::update_conversation_status($conversation_id, 'escalated');

            wp_send_json_success(array(
                'message' => 'Ticket created successfully',
                'ticket_number' => $result['ticket_number'],
                'ticket_id' => $result['ticket_id']
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to create ticket'));
        }
    }

    /**
     * AJAX: Get tickets
     */
    public static function ajax_get_tickets() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $filters = array(
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null,
            'priority' => isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : null,
            'assigned_to' => isset($_POST['assigned_to']) ? absint($_POST['assigned_to']) : null,
            'limit' => isset($_POST['limit']) ? absint($_POST['limit']) : 50,
            'offset' => isset($_POST['offset']) ? absint($_POST['offset']) : 0
        );

        $tickets = self::get_tickets($filters);
        $total = self::count_tickets($filters);

        wp_send_json_success(array(
            'tickets' => $tickets,
            'total' => $total
        ));
    }
}
