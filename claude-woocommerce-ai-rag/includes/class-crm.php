<?php
/**
 * CRM (Customer Relationship Management) Class
 * Manages customers, deals, lead scoring, and sales pipeline
 */

if (!defined('ABSPATH')) exit;

class CWAU_CRM {

    /**
     * Create or update customer profile
     */
    public static function upsert_customer($data) {
        global $wpdb;

        $email = sanitize_email($data['email']);
        if (!is_email($email)) {
            return false;
        }

        // Check if customer exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_customers WHERE email = %s",
            $email
        ));

        $customer_data = array(
            'email' => $email,
            'first_name' => isset($data['first_name']) ? sanitize_text_field($data['first_name']) : null,
            'last_name' => isset($data['last_name']) ? sanitize_text_field($data['last_name']) : null,
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : null,
            'company' => isset($data['company']) ? sanitize_text_field($data['company']) : null,
            'user_id' => isset($data['user_id']) ? absint($data['user_id']) : null,
            'avatar_url' => isset($data['avatar_url']) ? esc_url_raw($data['avatar_url']) : null,
            'lead_source' => isset($data['lead_source']) ? sanitize_text_field($data['lead_source']) : 'chat',
            'tags' => isset($data['tags']) ? sanitize_text_field($data['tags']) : null,
            'custom_fields' => isset($data['custom_fields']) ? json_encode($data['custom_fields']) : null,
            'updated_at' => current_time('mysql')
        );

        if ($existing) {
            // Update existing customer
            $wpdb->update(
                $wpdb->prefix . 'cwau_customers',
                $customer_data,
                array('id' => $existing->id),
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            return $existing->id;
        } else {
            // Create new customer
            $customer_data['created_at'] = current_time('mysql');
            $customer_data['lead_score'] = self::calculate_initial_score($data);

            $wpdb->insert(
                $wpdb->prefix . 'cwau_customers',
                $customer_data,
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
            );

            return $wpdb->insert_id;
        }
    }

    /**
     * Get customer by email or ID
     */
    public static function get_customer($identifier) {
        global $wpdb;

        if (is_numeric($identifier)) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cwau_customers WHERE id = %d",
                $identifier
            ));
        } else {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}cwau_customers WHERE email = %s",
                sanitize_email($identifier)
            ));
        }
    }

    /**
     * Get customer by WordPress user ID
     */
    public static function get_customer_by_user_id($user_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_customers WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Calculate initial lead score
     */
    private static function calculate_initial_score($data) {
        $score = 0;

        // Has company +10
        if (!empty($data['company'])) $score += 10;

        // Has phone +5
        if (!empty($data['phone'])) $score += 5;

        // Complete name +5
        if (!empty($data['first_name']) && !empty($data['last_name'])) $score += 5;

        // Lead source scoring
        $source_scores = array(
            'chat' => 15,
            'form' => 10,
            'organic' => 8,
            'referral' => 12,
            'paid' => 5
        );
        $source = isset($data['lead_source']) ? $data['lead_source'] : 'chat';
        $score += isset($source_scores[$source]) ? $source_scores[$source] : 0;

        return $score;
    }

    /**
     * Update lead score based on activity
     */
    public static function update_lead_score($customer_id, $activity_type, $metadata = array()) {
        global $wpdb;

        $customer = self::get_customer($customer_id);
        if (!$customer) return false;

        $current_score = intval($customer->lead_score);
        $score_change = 0;

        // Scoring rules
        switch ($activity_type) {
            case 'chat_initiated':
                $score_change = 5;
                break;
            case 'product_viewed':
                $score_change = 2;
                break;
            case 'cart_added':
                $score_change = 10;
                break;
            case 'purchase':
                $score_change = 50;
                break;
            case 'email_opened':
                $score_change = 1;
                break;
            case 'email_clicked':
                $score_change = 5;
                break;
            case 'form_submitted':
                $score_change = 15;
                break;
            case 'demo_requested':
                $score_change = 30;
                break;
            case 'pricing_page_viewed':
                $score_change = 15;
                break;
            case 'multiple_sessions':
                $sessions = isset($metadata['session_count']) ? intval($metadata['session_count']) : 1;
                $score_change = min($sessions * 3, 20);
                break;
        }

        // Apply score change
        $new_score = max(0, min(100, $current_score + $score_change));

        $wpdb->update(
            $wpdb->prefix . 'cwau_customers',
            array('lead_score' => $new_score, 'updated_at' => current_time('mysql')),
            array('id' => $customer_id),
            array('%d', '%s'),
            array('%d')
        );

        // Update lifecycle stage based on score
        self::update_lifecycle_stage($customer_id, $new_score);

        return $new_score;
    }

    /**
     * Update lifecycle stage
     */
    private static function update_lifecycle_stage($customer_id, $score) {
        global $wpdb;

        $customer = self::get_customer($customer_id);
        $current_stage = $customer->lifecycle_stage;
        $new_stage = $current_stage;

        // Stage progression logic
        if ($score >= 70 && $current_stage == 'lead') {
            $new_stage = 'mql'; // Marketing Qualified Lead
        } elseif ($score >= 80 && $current_stage == 'mql') {
            $new_stage = 'sql'; // Sales Qualified Lead
        } elseif ($customer->total_orders > 0 && $current_stage != 'customer') {
            $new_stage = 'customer';
        } elseif ($customer->total_orders >= 5 && $customer->total_revenue >= 1000) {
            $new_stage = 'evangelist';
        }

        if ($new_stage != $current_stage) {
            $wpdb->update(
                $wpdb->prefix . 'cwau_customers',
                array('lifecycle_stage' => $new_stage),
                array('id' => $customer_id),
                array('%s'),
                array('%d')
            );

            // Log stage change activity
            self::log_activity($customer_id, null, 'note',
                'Lifecycle stage changed',
                "Changed from {$current_stage} to {$new_stage}"
            );
        }
    }

    /**
     * Create deal/opportunity
     */
    public static function create_deal($data) {
        global $wpdb;

        $required = array('customer_id', 'title');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        $deal_data = array(
            'customer_id' => absint($data['customer_id']),
            'title' => sanitize_text_field($data['title']),
            'amount' => isset($data['amount']) ? floatval($data['amount']) : 0.00,
            'stage' => isset($data['stage']) ? sanitize_text_field($data['stage']) : 'lead',
            'probability' => isset($data['probability']) ? absint($data['probability']) : 0,
            'expected_close_date' => isset($data['expected_close_date']) ? sanitize_text_field($data['expected_close_date']) : null,
            'assigned_to' => isset($data['assigned_to']) ? absint($data['assigned_to']) : get_current_user_id(),
            'pipeline' => isset($data['pipeline']) ? sanitize_text_field($data['pipeline']) : 'default',
            'tags' => isset($data['tags']) ? sanitize_text_field($data['tags']) : null,
            'custom_fields' => isset($data['custom_fields']) ? json_encode($data['custom_fields']) : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $wpdb->insert(
            $wpdb->prefix . 'cwau_deals',
            $deal_data,
            array('%d', '%s', '%f', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        $deal_id = $wpdb->insert_id;

        // Log activity
        self::log_activity($data['customer_id'], $deal_id, 'note',
            'Deal created',
            "New deal: {$data['title']}"
        );

        return $deal_id;
    }

    /**
     * Get deal by ID
     */
    public static function get_deal($deal_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_deals WHERE id = %d",
            $deal_id
        ));
    }

    /**
     * Update deal stage
     */
    public static function update_deal_stage($deal_id, $new_stage, $metadata = array()) {
        global $wpdb;

        $deal = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_deals WHERE id = %d",
            $deal_id
        ));

        if (!$deal) return false;

        $update_data = array(
            'stage' => $new_stage,
            'updated_at' => current_time('mysql')
        );

        // Update probability based on stage
        $stage_probabilities = array(
            'lead' => 10,
            'qualified' => 25,
            'proposal' => 50,
            'negotiation' => 75,
            'closed_won' => 100,
            'closed_lost' => 0
        );
        $update_data['probability'] = isset($stage_probabilities[$new_stage]) ? $stage_probabilities[$new_stage] : 0;

        // If closed, set close date
        if (in_array($new_stage, array('closed_won', 'closed_lost'))) {
            $update_data['actual_close_date'] = current_time('mysql');

            if ($new_stage == 'closed_lost' && isset($metadata['lost_reason'])) {
                $update_data['lost_reason'] = sanitize_textarea_field($metadata['lost_reason']);
            }
        }

        $wpdb->update(
            $wpdb->prefix . 'cwau_deals',
            $update_data,
            array('id' => $deal_id),
            array('%s', '%s', '%d', '%s', '%s'),
            array('%d')
        );

        // Log activity
        self::log_activity($deal->customer_id, $deal_id, 'note',
            'Deal stage updated',
            "Deal moved to: {$new_stage}"
        );

        return true;
    }

    /**
     * Get deals by customer
     */
    public static function get_customer_deals($customer_id, $filters = array()) {
        global $wpdb;

        $where = array("customer_id = %d");
        $params = array($customer_id);

        if (isset($filters['stage'])) {
            $where[] = "stage = %s";
            $params[] = $filters['stage'];
        }

        if (isset($filters['pipeline'])) {
            $where[] = "pipeline = %s";
            $params[] = $filters['pipeline'];
        }

        $sql = "SELECT * FROM {$wpdb->prefix}cwau_deals WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Log customer activity
     */
    public static function log_activity($customer_id, $deal_id, $type, $subject, $description = '', $metadata = array()) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'cwau_activities',
            array(
                'customer_id' => $customer_id,
                'deal_id' => $deal_id,
                'type' => $type,
                'subject' => sanitize_text_field($subject),
                'description' => sanitize_textarea_field($description),
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        // Update last contact date
        $wpdb->update(
            $wpdb->prefix . 'cwau_customers',
            array('last_contact_date' => current_time('mysql')),
            array('id' => $customer_id),
            array('%s'),
            array('%d')
        );

        return $wpdb->insert_id;
    }

    /**
     * Get customer timeline/activities
     */
    public static function get_customer_timeline($customer_id, $limit = 50) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as created_by_name
            FROM {$wpdb->prefix}cwau_activities a
            LEFT JOIN {$wpdb->users} u ON a.created_by = u.ID
            WHERE a.customer_id = %d
            ORDER BY a.created_at DESC
            LIMIT %d",
            $customer_id,
            $limit
        ));
    }

    /**
     * Get pipeline overview
     */
    public static function get_pipeline_overview($pipeline = 'default') {
        global $wpdb;

        $stages = array('lead', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost');
        $overview = array();

        foreach ($stages as $stage) {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT COUNT(*) as count, SUM(amount) as total_value
                FROM {$wpdb->prefix}cwau_deals
                WHERE stage = %s AND pipeline = %s",
                $stage,
                $pipeline
            ));

            $overview[$stage] = array(
                'count' => intval($result->count),
                'total_value' => floatval($result->total_value)
            );
        }

        return $overview;
    }

    /**
     * Get top leads by score
     */
    public static function get_hot_leads($limit = 10) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cwau_customers
            WHERE lifecycle_stage IN ('lead', 'mql', 'sql')
            AND lead_score >= 50
            ORDER BY lead_score DESC, last_contact_date DESC
            LIMIT %d",
            $limit
        ));
    }

    /**
     * Search customers
     */
    public static function search_customers($query, $filters = array()) {
        global $wpdb;

        $where = array("1=1");
        $params = array();

        if (!empty($query)) {
            $where[] = "(email LIKE %s OR first_name LIKE %s OR last_name LIKE %s OR company LIKE %s)";
            $like = '%' . $wpdb->esc_like($query) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (isset($filters['lifecycle_stage'])) {
            $where[] = "lifecycle_stage = %s";
            $params[] = $filters['lifecycle_stage'];
        }

        if (isset($filters['min_score'])) {
            $where[] = "lead_score >= %d";
            $params[] = $filters['min_score'];
        }

        $sql = "SELECT * FROM {$wpdb->prefix}cwau_customers WHERE " . implode(' AND ', $where) . " ORDER BY lead_score DESC LIMIT 50";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            return $wpdb->get_results($sql);
        }
    }

    /**
     * Sync purchase to CRM (called on WooCommerce order)
     */
    public static function sync_purchase($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return false;

        $email = $order->get_billing_email();
        $customer = self::get_customer($email);

        if (!$customer) {
            // Create customer from order
            $customer_id = self::upsert_customer(array(
                'email' => $email,
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'phone' => $order->get_billing_phone(),
                'company' => $order->get_billing_company(),
                'user_id' => $order->get_customer_id(),
                'lead_source' => 'purchase'
            ));
        } else {
            $customer_id = $customer->id;
        }

        // Update customer stats
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}cwau_customers
            SET total_revenue = total_revenue + %f,
                total_orders = total_orders + 1,
                last_purchase_date = %s,
                lifecycle_stage = 'customer',
                updated_at = %s
            WHERE id = %d",
            $order->get_total(),
            current_time('mysql'),
            current_time('mysql'),
            $customer_id
        ));

        // Log purchase activity
        self::log_activity($customer_id, null, 'purchase',
            'Purchase completed',
            'Order #' . $order_id . ' - ' . wc_price($order->get_total()),
            array('order_id' => $order_id, 'amount' => $order->get_total())
        );

        // Update lead score
        self::update_lead_score($customer_id, 'purchase');

        return $customer_id;
    }

    /**
     * Get CRM statistics
     */
    public static function get_stats() {
        global $wpdb;

        return array(
            'total_customers' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cwau_customers"),
            'total_deals' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cwau_deals"),
            'active_deals' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cwau_deals WHERE stage NOT IN ('closed_won', 'closed_lost')"),
            'total_revenue' => $wpdb->get_var("SELECT SUM(total_revenue) FROM {$wpdb->prefix}cwau_customers"),
            'avg_lead_score' => $wpdb->get_var("SELECT AVG(lead_score) FROM {$wpdb->prefix}cwau_customers"),
            'hot_leads' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cwau_customers WHERE lead_score >= 70"),
            'lifecycle_breakdown' => $wpdb->get_results(
                "SELECT lifecycle_stage, COUNT(*) as count FROM {$wpdb->prefix}cwau_customers GROUP BY lifecycle_stage"
            )
        );
    }
}
