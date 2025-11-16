<?php
/**
 * Automation & Workflows Class
 * Visual workflow builder with triggers and actions
 * Inspired by HubSpot Workflows and Zapier
 */

if (!defined('ABSPATH')) exit;

class CWAU_Automation {

    /**
     * Get available triggers
     */
    public static function get_available_triggers() {
        return array(
            'chat' => array(
                'chat_started' => array(
                    'name' => 'Chat Started',
                    'description' => 'When a new chat conversation is initiated',
                    'fields' => array()
                ),
                'chat_escalated' => array(
                    'name' => 'Chat Escalated',
                    'description' => 'When a chat is escalated to an operator',
                    'fields' => array()
                ),
                'chat_idle' => array(
                    'name' => 'Chat Idle',
                    'description' => 'When chat has no activity for X minutes',
                    'fields' => array(
                        'minutes' => array('type' => 'number', 'label' => 'Minutes', 'default' => 5)
                    )
                ),
                'sentiment_negative' => array(
                    'name' => 'Negative Sentiment Detected',
                    'description' => 'When AI detects negative sentiment',
                    'fields' => array()
                )
            ),
            'crm' => array(
                'lead_score_threshold' => array(
                    'name' => 'Lead Score Threshold',
                    'description' => 'When lead score reaches a threshold',
                    'fields' => array(
                        'threshold' => array('type' => 'number', 'label' => 'Score Threshold', 'default' => 80)
                    )
                ),
                'lifecycle_stage_changed' => array(
                    'name' => 'Lifecycle Stage Changed',
                    'description' => 'When customer lifecycle stage changes',
                    'fields' => array(
                        'stage' => array(
                            'type' => 'select',
                            'label' => 'Stage',
                            'options' => array('lead', 'mql', 'sql', 'opportunity', 'customer', 'evangelist')
                        )
                    )
                ),
                'customer_created' => array(
                    'name' => 'New Customer Created',
                    'description' => 'When a new customer is added to CRM',
                    'fields' => array()
                )
            ),
            'tickets' => array(
                'ticket_created' => array(
                    'name' => 'Ticket Created',
                    'description' => 'When a new support ticket is created',
                    'fields' => array()
                ),
                'sla_breached' => array(
                    'name' => 'SLA Breached',
                    'description' => 'When a ticket breaches its SLA',
                    'fields' => array()
                ),
                'ticket_priority_urgent' => array(
                    'name' => 'Urgent Ticket Created',
                    'description' => 'When a ticket with urgent priority is created',
                    'fields' => array()
                )
            ),
            'ecommerce' => array(
                'order_placed' => array(
                    'name' => 'Order Placed',
                    'description' => 'When a customer places an order',
                    'fields' => array()
                ),
                'cart_abandoned' => array(
                    'name' => 'Cart Abandoned',
                    'description' => 'When a cart is abandoned for X hours',
                    'fields' => array(
                        'hours' => array('type' => 'number', 'label' => 'Hours', 'default' => 24)
                    )
                ),
                'high_value_order' => array(
                    'name' => 'High Value Order',
                    'description' => 'When order exceeds a value threshold',
                    'fields' => array(
                        'amount' => array('type' => 'number', 'label' => 'Minimum Amount', 'default' => 500)
                    )
                )
            )
        );
    }

    /**
     * Get available actions
     */
    public static function get_available_actions() {
        return array(
            'communication' => array(
                'send_email' => array(
                    'name' => 'Send Email',
                    'description' => 'Send an email notification',
                    'fields' => array(
                        'to' => array('type' => 'text', 'label' => 'To (use {customer_email} for dynamic)', 'required' => true),
                        'subject' => array('type' => 'text', 'label' => 'Subject', 'required' => true),
                        'body' => array('type' => 'textarea', 'label' => 'Body', 'required' => true)
                    )
                ),
                'send_chat_message' => array(
                    'name' => 'Send Chat Message',
                    'description' => 'Send an automated chat message',
                    'fields' => array(
                        'message' => array('type' => 'textarea', 'label' => 'Message', 'required' => true)
                    )
                ),
                'notify_operator' => array(
                    'name' => 'Notify Operator',
                    'description' => 'Send notification to specific operator',
                    'fields' => array(
                        'operator_id' => array('type' => 'user', 'label' => 'Operator', 'required' => true),
                        'message' => array('type' => 'textarea', 'label' => 'Message', 'required' => true)
                    )
                )
            ),
            'crm' => array(
                'update_lead_score' => array(
                    'name' => 'Update Lead Score',
                    'description' => 'Add or subtract points from lead score',
                    'fields' => array(
                        'points' => array('type' => 'number', 'label' => 'Points (use negative to subtract)', 'required' => true)
                    )
                ),
                'change_lifecycle_stage' => array(
                    'name' => 'Change Lifecycle Stage',
                    'description' => 'Update customer lifecycle stage',
                    'fields' => array(
                        'stage' => array(
                            'type' => 'select',
                            'label' => 'New Stage',
                            'options' => array('lead', 'mql', 'sql', 'opportunity', 'customer', 'evangelist'),
                            'required' => true
                        )
                    )
                ),
                'create_deal' => array(
                    'name' => 'Create Deal',
                    'description' => 'Create a new sales opportunity',
                    'fields' => array(
                        'title' => array('type' => 'text', 'label' => 'Deal Title', 'required' => true),
                        'value' => array('type' => 'number', 'label' => 'Deal Value', 'required' => true),
                        'stage' => array('type' => 'text', 'label' => 'Initial Stage', 'default' => 'lead')
                    )
                ),
                'add_tag' => array(
                    'name' => 'Add Tag',
                    'description' => 'Add a tag to customer',
                    'fields' => array(
                        'tag' => array('type' => 'text', 'label' => 'Tag', 'required' => true)
                    )
                )
            ),
            'tickets' => array(
                'create_ticket' => array(
                    'name' => 'Create Support Ticket',
                    'description' => 'Automatically create a support ticket',
                    'fields' => array(
                        'subject' => array('type' => 'text', 'label' => 'Subject', 'required' => true),
                        'priority' => array(
                            'type' => 'select',
                            'label' => 'Priority',
                            'options' => array('low', 'normal', 'high', 'urgent'),
                            'default' => 'normal'
                        )
                    )
                ),
                'assign_ticket' => array(
                    'name' => 'Assign Ticket',
                    'description' => 'Assign ticket to specific operator',
                    'fields' => array(
                        'operator_id' => array('type' => 'user', 'label' => 'Operator', 'required' => true)
                    )
                ),
                'escalate_priority' => array(
                    'name' => 'Escalate Priority',
                    'description' => 'Increase ticket priority',
                    'fields' => array()
                )
            ),
            'integrations' => array(
                'sync_to_hubspot' => array(
                    'name' => 'Sync to HubSpot',
                    'description' => 'Sync customer data to HubSpot CRM',
                    'fields' => array()
                ),
                'create_zendesk_ticket' => array(
                    'name' => 'Create Zendesk Ticket',
                    'description' => 'Create ticket in Zendesk',
                    'fields' => array(
                        'subject' => array('type' => 'text', 'label' => 'Subject', 'required' => true)
                    )
                ),
                'webhook' => array(
                    'name' => 'Send Webhook',
                    'description' => 'Send data to external webhook URL',
                    'fields' => array(
                        'url' => array('type' => 'url', 'label' => 'Webhook URL', 'required' => true),
                        'method' => array(
                            'type' => 'select',
                            'label' => 'Method',
                            'options' => array('POST', 'GET', 'PUT'),
                            'default' => 'POST'
                        )
                    )
                )
            ),
            'delays' => array(
                'wait' => array(
                    'name' => 'Wait/Delay',
                    'description' => 'Wait for a specified time before next action',
                    'fields' => array(
                        'duration' => array('type' => 'number', 'label' => 'Minutes', 'default' => 5)
                    )
                )
            )
        );
    }

    /**
     * Create workflow
     */
    public static function create_workflow($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'cwau_workflows',
            array(
                'name' => sanitize_text_field($data['name']),
                'description' => sanitize_textarea_field($data['description'] ?? ''),
                'trigger_type' => sanitize_text_field($data['trigger_type']),
                'trigger_config' => json_encode($data['trigger_config'] ?? array()),
                'actions' => json_encode($data['actions'] ?? array()),
                'conditions' => json_encode($data['conditions'] ?? array()),
                'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        if ($result) {
            return array('success' => true, 'workflow_id' => $wpdb->insert_id);
        }

        return array('success' => false, 'error' => 'Failed to create workflow');
    }

    /**
     * Get workflow by ID
     */
    public static function get_workflow($workflow_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_workflows WHERE id = %d",
            $workflow_id
        ));
    }

    /**
     * Get all workflows
     */
    public static function get_workflows($filters = array()) {
        global $wpdb;

        $where = array('1=1');
        $params = array();

        if (isset($filters['is_active'])) {
            $where[] = 'is_active = %d';
            $params[] = $filters['is_active'] ? 1 : 0;
        }

        if (!empty($filters['trigger_type'])) {
            $where[] = 'trigger_type = %s';
            $params[] = $filters['trigger_type'];
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT * FROM {$wpdb->prefix}cwau_workflows WHERE {$where_sql} ORDER BY created_at DESC";

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Update workflow
     */
    public static function update_workflow($workflow_id, $data) {
        global $wpdb;

        $update_data = array();
        $format = array();

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }

        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }

        if (isset($data['is_active'])) {
            $update_data['is_active'] = (bool)$data['is_active'];
            $format[] = '%d';
        }

        if (isset($data['actions'])) {
            $update_data['actions'] = json_encode($data['actions']);
            $format[] = '%s';
        }

        if (isset($data['conditions'])) {
            $update_data['conditions'] = json_encode($data['conditions']);
            $format[] = '%s';
        }

        if (!empty($update_data)) {
            return $wpdb->update(
                $wpdb->prefix . 'cwau_workflows',
                $update_data,
                array('id' => $workflow_id),
                $format,
                array('%d')
            ) !== false;
        }

        return false;
    }

    /**
     * Delete workflow
     */
    public static function delete_workflow($workflow_id) {
        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . 'cwau_workflows',
            array('id' => $workflow_id),
            array('%d')
        ) !== false;
    }

    /**
     * Execute workflow
     */
    public static function execute_workflow($workflow_id, $context = array()) {
        $workflow = self::get_workflow($workflow_id);

        if (!$workflow || !$workflow->is_active) {
            return array('success' => false, 'error' => 'Workflow not found or inactive');
        }

        // Check conditions
        $conditions = json_decode($workflow->conditions, true);
        if (!self::check_conditions($conditions, $context)) {
            return array('success' => false, 'error' => 'Conditions not met');
        }

        // Execute actions
        $actions = json_decode($workflow->actions, true);
        $results = array();

        foreach ($actions as $action) {
            $result = self::execute_action($action, $context);
            $results[] = $result;

            if (!$result['success']) {
                // Log error but continue with other actions
                error_log("Workflow action failed: " . ($result['error'] ?? 'Unknown error'));
            }
        }

        // Log execution
        self::log_execution($workflow_id, $context, $results);

        return array('success' => true, 'results' => $results);
    }

    /**
     * Check if conditions are met
     */
    private static function check_conditions($conditions, $context) {
        if (empty($conditions)) {
            return true; // No conditions = always execute
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? '';

            $actual_value = $context[$field] ?? null;

            $result = match($operator) {
                '=' => $actual_value == $value,
                '!=' => $actual_value != $value,
                '>' => $actual_value > $value,
                '<' => $actual_value < $value,
                '>=' => $actual_value >= $value,
                '<=' => $actual_value <= $value,
                'contains' => strpos($actual_value, $value) !== false,
                'not_contains' => strpos($actual_value, $value) === false,
                default => false
            };

            if (!$result) {
                return false; // AND logic - all conditions must pass
            }
        }

        return true;
    }

    /**
     * Execute individual action
     */
    private static function execute_action($action, $context) {
        $type = $action['type'] ?? '';
        $config = $action['config'] ?? array();

        // Replace variables in config with context values
        $config = self::replace_variables($config, $context);

        return match($type) {
            'send_email' => self::action_send_email($config, $context),
            'send_chat_message' => self::action_send_chat_message($config, $context),
            'notify_operator' => self::action_notify_operator($config, $context),
            'update_lead_score' => self::action_update_lead_score($config, $context),
            'change_lifecycle_stage' => self::action_change_lifecycle_stage($config, $context),
            'create_deal' => self::action_create_deal($config, $context),
            'add_tag' => self::action_add_tag($config, $context),
            'create_ticket' => self::action_create_ticket($config, $context),
            'assign_ticket' => self::action_assign_ticket($config, $context),
            'webhook' => self::action_webhook($config, $context),
            'wait' => self::action_wait($config, $context),
            default => array('success' => false, 'error' => 'Unknown action type: ' . $type)
        };
    }

    /**
     * Replace variables like {customer_email} with actual values
     */
    private static function replace_variables($config, $context) {
        $json = json_encode($config);

        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $json = str_replace('{' . $key . '}', $value, $json);
            }
        }

        return json_decode($json, true);
    }

    // Action implementations

    private static function action_send_email($config, $context) {
        $to = $config['to'] ?? '';
        $subject = $config['subject'] ?? '';
        $body = $config['body'] ?? '';

        if (empty($to) || empty($subject)) {
            return array('success' => false, 'error' => 'Missing required fields');
        }

        $result = wp_mail($to, $subject, $body);

        return array('success' => $result, 'message' => 'Email sent to ' . $to);
    }

    private static function action_send_chat_message($config, $context) {
        $conversation_id = $context['conversation_id'] ?? null;
        $message = $config['message'] ?? '';

        if (!$conversation_id || !$message) {
            return array('success' => false, 'error' => 'Missing conversation_id or message');
        }

        CWAU_Database::add_message($conversation_id, 'ai', $message, array('automated' => true));

        return array('success' => true, 'message' => 'Chat message sent');
    }

    private static function action_notify_operator($config, $context) {
        global $wpdb;

        $operator_id = $config['operator_id'] ?? null;
        $message = $config['message'] ?? '';

        if (!$operator_id) {
            return array('success' => false, 'error' => 'Missing operator_id');
        }

        $wpdb->insert(
            $wpdb->prefix . 'cwau_notifications',
            array(
                'user_id' => $operator_id,
                'type' => 'automation',
                'title' => 'Workflow Notification',
                'message' => $message,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );

        return array('success' => true, 'message' => 'Operator notified');
    }

    private static function action_update_lead_score($config, $context) {
        $customer_id = $context['customer_id'] ?? null;
        $points = $config['points'] ?? 0;

        if (!$customer_id) {
            return array('success' => false, 'error' => 'Missing customer_id');
        }

        CWAU_CRM::update_lead_score($customer_id, 'automation', array('points' => $points));

        return array('success' => true, 'message' => 'Lead score updated by ' . $points);
    }

    private static function action_change_lifecycle_stage($config, $context) {
        global $wpdb;

        $customer_id = $context['customer_id'] ?? null;
        $stage = $config['stage'] ?? '';

        if (!$customer_id || !$stage) {
            return array('success' => false, 'error' => 'Missing required fields');
        }

        $wpdb->update(
            $wpdb->prefix . 'cwau_customers',
            array('lifecycle_stage' => $stage),
            array('id' => $customer_id),
            array('%s'),
            array('%d')
        );

        return array('success' => true, 'message' => 'Lifecycle stage changed to ' . $stage);
    }

    private static function action_create_deal($config, $context) {
        $customer_id = $context['customer_id'] ?? null;
        $title = $config['title'] ?? '';
        $value = $config['value'] ?? 0;
        $stage = $config['stage'] ?? 'lead';

        if (!$customer_id || !$title) {
            return array('success' => false, 'error' => 'Missing required fields');
        }

        $result = CWAU_CRM::create_deal(array(
            'customer_id' => $customer_id,
            'title' => $title,
            'value' => $value,
            'stage' => $stage
        ));

        return array('success' => true, 'message' => 'Deal created', 'deal_id' => $result);
    }

    private static function action_add_tag($config, $context) {
        global $wpdb;

        $customer_id = $context['customer_id'] ?? null;
        $tag = $config['tag'] ?? '';

        if (!$customer_id || !$tag) {
            return array('success' => false, 'error' => 'Missing required fields');
        }

        $customer = CWAU_CRM::get_customer($customer_id);
        $tags = $customer->tags ? explode(',', $customer->tags) : array();

        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $wpdb->update(
                $wpdb->prefix . 'cwau_customers',
                array('tags' => implode(',', $tags)),
                array('id' => $customer_id),
                array('%s'),
                array('%d')
            );
        }

        return array('success' => true, 'message' => 'Tag added');
    }

    private static function action_create_ticket($config, $context) {
        $customer_id = $context['customer_id'] ?? null;
        $conversation_id = $context['conversation_id'] ?? null;
        $subject = $config['subject'] ?? 'Automated Ticket';
        $priority = $config['priority'] ?? 'normal';

        $result = CWAU_Tickets::create_ticket(array(
            'customer_id' => $customer_id,
            'conversation_id' => $conversation_id,
            'subject' => $subject,
            'priority' => $priority,
            'description' => 'Automatically created by workflow'
        ));

        return $result;
    }

    private static function action_assign_ticket($config, $context) {
        $ticket_id = $context['ticket_id'] ?? null;
        $operator_id = $config['operator_id'] ?? null;

        if (!$ticket_id || !$operator_id) {
            return array('success' => false, 'error' => 'Missing required fields');
        }

        CWAU_Tickets::assign_ticket($ticket_id, $operator_id);

        return array('success' => true, 'message' => 'Ticket assigned');
    }

    private static function action_webhook($config, $context) {
        $url = $config['url'] ?? '';
        $method = $config['method'] ?? 'POST';

        if (!$url) {
            return array('success' => false, 'error' => 'Missing webhook URL');
        }

        $response = wp_remote_request($url, array(
            'method' => $method,
            'body' => json_encode($context),
            'headers' => array('Content-Type' => 'application/json')
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        return array('success' => true, 'message' => 'Webhook sent', 'response_code' => wp_remote_retrieve_response_code($response));
    }

    private static function action_wait($config, $context) {
        // For async execution, this would schedule next action
        // For now, just return success
        return array('success' => true, 'message' => 'Wait action scheduled');
    }

    /**
     * Log workflow execution
     */
    private static function log_execution($workflow_id, $context, $results) {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'cwau_workflows',
            array(
                'last_run_at' => current_time('mysql'),
                'run_count' => $wpdb->get_var($wpdb->prepare(
                    "SELECT run_count FROM {$wpdb->prefix}cwau_workflows WHERE id = %d",
                    $workflow_id
                )) + 1
            ),
            array('id' => $workflow_id),
            array('%s', '%d'),
            array('%d')
        );
    }

    /**
     * Trigger workflow by event
     */
    public static function trigger($trigger_type, $context = array()) {
        $workflows = self::get_workflows(array(
            'is_active' => true,
            'trigger_type' => $trigger_type
        ));

        foreach ($workflows as $workflow) {
            self::execute_workflow($workflow->id, $context);
        }
    }

    /**
     * AJAX: Get workflows
     */
    public static function ajax_get_workflows() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $workflows = self::get_workflows();

        wp_send_json_success(array('workflows' => $workflows));
    }

    /**
     * AJAX: Create workflow
     */
    public static function ajax_create_workflow() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $data = array(
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'trigger_type' => $_POST['trigger_type'] ?? '',
            'trigger_config' => isset($_POST['trigger_config']) ? json_decode(stripslashes($_POST['trigger_config']), true) : array(),
            'actions' => isset($_POST['actions']) ? json_decode(stripslashes($_POST['actions']), true) : array(),
            'conditions' => isset($_POST['conditions']) ? json_decode(stripslashes($_POST['conditions']), true) : array(),
            'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true
        );

        $result = self::create_workflow($data);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Update workflow
     */
    public static function ajax_update_workflow() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $workflow_id = isset($_POST['workflow_id']) ? absint($_POST['workflow_id']) : 0;

        if (!$workflow_id) {
            wp_send_json_error(array('message' => 'Invalid workflow ID'));
        }

        $data = array();

        if (isset($_POST['name'])) $data['name'] = $_POST['name'];
        if (isset($_POST['description'])) $data['description'] = $_POST['description'];
        if (isset($_POST['is_active'])) $data['is_active'] = (bool)$_POST['is_active'];
        if (isset($_POST['actions'])) $data['actions'] = json_decode(stripslashes($_POST['actions']), true);
        if (isset($_POST['conditions'])) $data['conditions'] = json_decode(stripslashes($_POST['conditions']), true);

        $result = self::update_workflow($workflow_id, $data);

        if ($result) {
            wp_send_json_success(array('message' => 'Workflow updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update workflow'));
        }
    }

    /**
     * AJAX: Delete workflow
     */
    public static function ajax_delete_workflow() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $workflow_id = isset($_POST['workflow_id']) ? absint($_POST['workflow_id']) : 0;

        if (!$workflow_id) {
            wp_send_json_error(array('message' => 'Invalid workflow ID'));
        }

        $result = self::delete_workflow($workflow_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Workflow deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete workflow'));
        }
    }
}
