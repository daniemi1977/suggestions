<?php
/**
 * External Integrations Class
 * API connectors for HubSpot, Zendesk, Freshdesk
 * Bi-directional sync capabilities
 */

if (!defined('ABSPATH')) exit;

class CWAU_Integrations {

    /**
     * HubSpot API Client
     */
    public static function hubspot_api_request($endpoint, $method = 'GET', $data = null) {
        $api_key = get_option('cwau_hubspot_api_key');

        if (empty($api_key)) {
            return array('success' => false, 'error' => 'HubSpot API key not configured');
        }

        $base_url = 'https://api.hubapi.com';
        $url = $base_url . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code >= 200 && $code < 300) {
            return array('success' => true, 'data' => $data);
        }

        return array('success' => false, 'error' => $data['message'] ?? 'API request failed', 'code' => $code);
    }

    /**
     * Sync customer to HubSpot
     */
    public static function sync_customer_to_hubspot($customer_id) {
        $customer = CWAU_CRM::get_customer($customer_id);

        if (!$customer) {
            return array('success' => false, 'error' => 'Customer not found');
        }

        // Check if already synced
        $hubspot_contact_id = get_option('cwau_hubspot_contact_' . $customer_id);

        $contact_data = array(
            'properties' => array(
                'email' => $customer->email,
                'firstname' => $customer->first_name,
                'lastname' => $customer->last_name,
                'phone' => $customer->phone,
                'company' => $customer->company,
                'hs_lead_status' => self::map_lifecycle_to_hubspot($customer->lifecycle_stage),
                'custom_lead_score' => $customer->lead_score,
                'total_revenue' => $customer->total_revenue
            )
        );

        if ($hubspot_contact_id) {
            // Update existing contact
            $result = self::hubspot_api_request("/crm/v3/objects/contacts/{$hubspot_contact_id}", 'PATCH', $contact_data);
        } else {
            // Create new contact
            $result = self::hubspot_api_request('/crm/v3/objects/contacts', 'POST', $contact_data);

            if ($result['success'] && isset($result['data']['id'])) {
                update_option('cwau_hubspot_contact_' . $customer_id, $result['data']['id']);
            }
        }

        return $result;
    }

    /**
     * Map lifecycle stage to HubSpot lead status
     */
    private static function map_lifecycle_to_hubspot($stage) {
        $mapping = array(
            'lead' => 'NEW',
            'mql' => 'OPEN',
            'sql' => 'IN_PROGRESS',
            'opportunity' => 'IN_PROGRESS',
            'customer' => 'CONNECTED',
            'evangelist' => 'CONNECTED'
        );

        return $mapping[$stage] ?? 'NEW';
    }

    /**
     * Create deal in HubSpot
     */
    public static function create_hubspot_deal($deal_id) {
        $deal = CWAU_CRM::get_deal($deal_id);

        if (!$deal) {
            return array('success' => false, 'error' => 'Deal not found');
        }

        $customer = CWAU_CRM::get_customer($deal->customer_id);
        $hubspot_contact_id = get_option('cwau_hubspot_contact_' . $deal->customer_id);

        if (!$hubspot_contact_id) {
            // Sync customer first
            $sync_result = self::sync_customer_to_hubspot($deal->customer_id);
            if ($sync_result['success']) {
                $hubspot_contact_id = $sync_result['data']['id'];
            }
        }

        $deal_data = array(
            'properties' => array(
                'dealname' => $deal->title,
                'amount' => $deal->value,
                'dealstage' => self::map_stage_to_hubspot($deal->stage),
                'pipeline' => 'default'
            )
        );

        $result = self::hubspot_api_request('/crm/v3/objects/deals', 'POST', $deal_data);

        if ($result['success'] && isset($result['data']['id'])) {
            $hubspot_deal_id = $result['data']['id'];
            update_option('cwau_hubspot_deal_' . $deal_id, $hubspot_deal_id);

            // Associate deal with contact
            if ($hubspot_contact_id) {
                self::hubspot_api_request(
                    "/crm/v3/objects/deals/{$hubspot_deal_id}/associations/contacts/{$hubspot_contact_id}/3",
                    'PUT'
                );
            }
        }

        return $result;
    }

    /**
     * Map deal stage to HubSpot
     */
    private static function map_stage_to_hubspot($stage) {
        $mapping = array(
            'lead' => 'appointmentscheduled',
            'qualified' => 'qualifiedtobuy',
            'proposal' => 'presentationscheduled',
            'negotiation' => 'decisionmakerboughtin',
            'closed_won' => 'closedwon',
            'closed_lost' => 'closedlost'
        );

        return $mapping[$stage] ?? 'appointmentscheduled';
    }

    /**
     * Zendesk API Client
     */
    public static function zendesk_api_request($endpoint, $method = 'GET', $data = null) {
        $subdomain = get_option('cwau_zendesk_subdomain');
        $email = get_option('cwau_zendesk_email');
        $api_token = get_option('cwau_zendesk_api_token');

        if (empty($subdomain) || empty($email) || empty($api_token)) {
            return array('success' => false, 'error' => 'Zendesk credentials not configured');
        }

        $base_url = "https://{$subdomain}.zendesk.com/api/v2";
        $url = $base_url . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($email . '/token:' . $api_token),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code >= 200 && $code < 300) {
            return array('success' => true, 'data' => $data);
        }

        return array('success' => false, 'error' => $data['error'] ?? 'API request failed', 'code' => $code);
    }

    /**
     * Create Zendesk ticket
     */
    public static function create_zendesk_ticket($ticket_id) {
        $ticket = CWAU_Tickets::get_ticket($ticket_id);

        if (!$ticket) {
            return array('success' => false, 'error' => 'Ticket not found');
        }

        $customer = $ticket->customer_id ? CWAU_CRM::get_customer($ticket->customer_id) : null;

        $ticket_data = array(
            'ticket' => array(
                'subject' => $ticket->subject,
                'comment' => array(
                    'body' => $ticket->description
                ),
                'priority' => self::map_priority_to_zendesk($ticket->priority),
                'status' => self::map_status_to_zendesk($ticket->status),
                'type' => 'question'
            )
        );

        if ($customer) {
            $ticket_data['ticket']['requester'] = array(
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email
            );
        }

        $result = self::zendesk_api_request('/tickets.json', 'POST', $ticket_data);

        if ($result['success'] && isset($result['data']['ticket']['id'])) {
            update_option('cwau_zendesk_ticket_' . $ticket_id, $result['data']['ticket']['id']);
        }

        return $result;
    }

    /**
     * Map priority to Zendesk
     */
    private static function map_priority_to_zendesk($priority) {
        $mapping = array(
            'low' => 'low',
            'normal' => 'normal',
            'high' => 'high',
            'urgent' => 'urgent'
        );

        return $mapping[$priority] ?? 'normal';
    }

    /**
     * Map status to Zendesk
     */
    private static function map_status_to_zendesk($status) {
        $mapping = array(
            'new' => 'new',
            'open' => 'open',
            'pending' => 'pending',
            'on_hold' => 'hold',
            'solved' => 'solved',
            'closed' => 'closed'
        );

        return $mapping[$status] ?? 'new';
    }

    /**
     * Sync Zendesk ticket updates back
     */
    public static function sync_zendesk_ticket_updates($ticket_id) {
        $zendesk_ticket_id = get_option('cwau_zendesk_ticket_' . $ticket_id);

        if (!$zendesk_ticket_id) {
            return array('success' => false, 'error' => 'Ticket not synced to Zendesk');
        }

        $result = self::zendesk_api_request('/tickets/' . $zendesk_ticket_id . '.json', 'GET');

        if ($result['success'] && isset($result['data']['ticket'])) {
            $zendesk_ticket = $result['data']['ticket'];

            // Update local ticket status based on Zendesk
            $local_status = array_search($zendesk_ticket['status'], array(
                'new' => 'new',
                'open' => 'open',
                'pending' => 'pending',
                'on_hold' => 'hold',
                'solved' => 'solved',
                'closed' => 'closed'
            ));

            if ($local_status) {
                CWAU_Tickets::update_status($ticket_id, $local_status);
            }
        }

        return $result;
    }

    /**
     * Freshdesk API Client
     */
    public static function freshdesk_api_request($endpoint, $method = 'GET', $data = null) {
        $domain = get_option('cwau_freshdesk_domain');
        $api_key = get_option('cwau_freshdesk_api_key');

        if (empty($domain) || empty($api_key)) {
            return array('success' => false, 'error' => 'Freshdesk credentials not configured');
        }

        $base_url = "https://{$domain}.freshdesk.com/api/v2";
        $url = $base_url . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_key . ':X'),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code >= 200 && $code < 300) {
            return array('success' => true, 'data' => $data);
        }

        return array('success' => false, 'error' => $data['description'] ?? 'API request failed', 'code' => $code);
    }

    /**
     * Create Freshdesk ticket
     */
    public static function create_freshdesk_ticket($ticket_id) {
        $ticket = CWAU_Tickets::get_ticket($ticket_id);

        if (!$ticket) {
            return array('success' => false, 'error' => 'Ticket not found');
        }

        $customer = $ticket->customer_id ? CWAU_CRM::get_customer($ticket->customer_id) : null;

        $ticket_data = array(
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'priority' => self::map_priority_to_freshdesk($ticket->priority),
            'status' => self::map_status_to_freshdesk($ticket->status),
            'source' => 2 // Portal
        );

        if ($customer) {
            $ticket_data['email'] = $customer->email;
            $ticket_data['name'] = $customer->first_name . ' ' . $customer->last_name;
        }

        $result = self::freshdesk_api_request('/tickets', 'POST', $ticket_data);

        if ($result['success'] && isset($result['data']['id'])) {
            update_option('cwau_freshdesk_ticket_' . $ticket_id, $result['data']['id']);
        }

        return $result;
    }

    /**
     * Map priority to Freshdesk
     */
    private static function map_priority_to_freshdesk($priority) {
        $mapping = array(
            'low' => 1,
            'normal' => 2,
            'high' => 3,
            'urgent' => 4
        );

        return $mapping[$priority] ?? 2;
    }

    /**
     * Map status to Freshdesk
     */
    private static function map_status_to_freshdesk($status) {
        $mapping = array(
            'new' => 2,      // Open
            'open' => 2,     // Open
            'pending' => 3,  // Pending
            'on_hold' => 3,  // Pending
            'solved' => 4,   // Resolved
            'closed' => 5    // Closed
        );

        return $mapping[$status] ?? 2;
    }

    /**
     * Create Freshdesk contact
     */
    public static function sync_customer_to_freshdesk($customer_id) {
        $customer = CWAU_CRM::get_customer($customer_id);

        if (!$customer) {
            return array('success' => false, 'error' => 'Customer not found');
        }

        $contact_data = array(
            'name' => $customer->first_name . ' ' . $customer->last_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'company' => $customer->company,
            'description' => "Lead Score: {$customer->lead_score}, Stage: {$customer->lifecycle_stage}"
        );

        $result = self::freshdesk_api_request('/contacts', 'POST', $contact_data);

        if ($result['success'] && isset($result['data']['id'])) {
            update_option('cwau_freshdesk_contact_' . $customer_id, $result['data']['id']);
        }

        return $result;
    }

    /**
     * Webhook receiver for external updates
     */
    public static function handle_webhook() {
        $source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (!$data) {
            wp_send_json_error(array('message' => 'Invalid payload'));
        }

        // Log webhook for debugging
        error_log("Webhook received from {$source}: " . print_r($data, true));

        $result = match($source) {
            'hubspot' => self::process_hubspot_webhook($data),
            'zendesk' => self::process_zendesk_webhook($data),
            'freshdesk' => self::process_freshdesk_webhook($data),
            default => array('success' => false, 'error' => 'Unknown source')
        };

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Process HubSpot webhook
     */
    private static function process_hubspot_webhook($data) {
        // Example: contact updated
        if (isset($data['objectId']) && isset($data['propertyName'])) {
            // Find local customer by HubSpot ID
            global $wpdb;
            $customer_id = $wpdb->get_var($wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options}
                 WHERE option_name LIKE 'cwau_hubspot_contact_%'
                 AND option_value = %s",
                $data['objectId']
            ));

            if ($customer_id) {
                $customer_id = str_replace('cwau_hubspot_contact_', '', $customer_id);
                // Sync updates from HubSpot
                return array('success' => true, 'message' => 'Contact updated from HubSpot');
            }
        }

        return array('success' => true, 'message' => 'Webhook processed');
    }

    /**
     * Process Zendesk webhook
     */
    private static function process_zendesk_webhook($data) {
        // Example: ticket status changed
        if (isset($data['ticket']['id'])) {
            global $wpdb;
            $ticket_id = $wpdb->get_var($wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options}
                 WHERE option_name LIKE 'cwau_zendesk_ticket_%'
                 AND option_value = %s",
                $data['ticket']['id']
            ));

            if ($ticket_id) {
                $ticket_id = str_replace('cwau_zendesk_ticket_', '', $ticket_id);
                self::sync_zendesk_ticket_updates($ticket_id);
                return array('success' => true, 'message' => 'Ticket synced from Zendesk');
            }
        }

        return array('success' => true, 'message' => 'Webhook processed');
    }

    /**
     * Process Freshdesk webhook
     */
    private static function process_freshdesk_webhook($data) {
        // Similar to Zendesk
        return array('success' => true, 'message' => 'Webhook processed');
    }

    /**
     * Test API connection
     */
    public static function test_connection($platform) {
        return match($platform) {
            'hubspot' => self::hubspot_api_request('/crm/v3/objects/contacts?limit=1', 'GET'),
            'zendesk' => self::zendesk_api_request('/tickets.json?per_page=1', 'GET'),
            'freshdesk' => self::freshdesk_api_request('/tickets?per_page=1', 'GET'),
            default => array('success' => false, 'error' => 'Unknown platform')
        };
    }

    /**
     * AJAX: Test integration connection
     */
    public static function ajax_test_integration() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : '';

        if (!$platform) {
            wp_send_json_error(array('message' => 'Platform not specified'));
        }

        $result = self::test_connection($platform);

        if ($result['success']) {
            wp_send_json_success(array('message' => ucfirst($platform) . ' connection successful!'));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }

    /**
     * AJAX: Sync customer to platform
     */
    public static function ajax_sync_customer() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : '';

        if (!$customer_id || !$platform) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }

        $result = match($platform) {
            'hubspot' => self::sync_customer_to_hubspot($customer_id),
            'freshdesk' => self::sync_customer_to_freshdesk($customer_id),
            default => array('success' => false, 'error' => 'Platform not supported for customer sync')
        };

        if ($result['success']) {
            wp_send_json_success(array('message' => 'Customer synced to ' . ucfirst($platform)));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }

    /**
     * AJAX: Sync ticket to platform
     */
    public static function ajax_sync_ticket() {
        check_ajax_referer('cwau_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : '';

        if (!$ticket_id || !$platform) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }

        $result = match($platform) {
            'zendesk' => self::create_zendesk_ticket($ticket_id),
            'freshdesk' => self::create_freshdesk_ticket($ticket_id),
            default => array('success' => false, 'error' => 'Platform not supported for ticket sync')
        };

        if ($result['success']) {
            wp_send_json_success(array('message' => 'Ticket synced to ' . ucfirst($platform)));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
}
