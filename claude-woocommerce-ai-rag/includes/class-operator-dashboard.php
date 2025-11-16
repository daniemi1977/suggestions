<?php
/**
 * Operator Dashboard Class
 * Admin interface for operators to manage live chats
 */

if (!defined('ABSPATH')) exit;

class CWAU_Operator_Dashboard {

    /**
     * Initialize dashboard
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'), 20);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    /**
     * Register admin menu
     */
    public static function register_menu() {
        add_menu_page(
            'Live Chat Dashboard',
            'Live Chat',
            'manage_woocommerce',
            'cwau-live-chat',
            array(__CLASS__, 'render_dashboard'),
            'dashicons-format-chat',
            56
        );

        add_submenu_page(
            'cwau-live-chat',
            'Chat Queue',
            'Queue',
            'manage_woocommerce',
            'cwau-chat-queue',
            array(__CLASS__, 'render_queue')
        );

        add_submenu_page(
            'cwau-live-chat',
            'My Conversations',
            'My Chats',
            'manage_woocommerce',
            'cwau-my-chats',
            array(__CLASS__, 'render_my_chats')
        );

        add_submenu_page(
            'cwau-live-chat',
            'Tickets',
            'Tickets',
            'manage_woocommerce',
            'cwau-tickets',
            array(__CLASS__, 'render_tickets')
        );

        add_submenu_page(
            'cwau-live-chat',
            'CRM',
            'CRM',
            'manage_woocommerce',
            'cwau-crm',
            array(__CLASS__, 'render_crm')
        );

        add_submenu_page(
            'cwau-live-chat',
            'Analytics',
            'Analytics',
            'manage_woocommerce',
            'cwau-analytics-dashboard',
            array(__CLASS__, 'render_analytics')
        );
    }

    /**
     * Enqueue dashboard assets
     */
    public static function enqueue_assets($hook) {
        if (strpos($hook, 'cwau-') === false) {
            return;
        }

        wp_enqueue_style(
            'cwau-operator-dashboard',
            CWAU_URL . 'assets/operator-dashboard.css',
            array(),
            CWAU_VERSION
        );

        wp_enqueue_script(
            'cwau-operator-dashboard',
            CWAU_URL . 'assets/operator-dashboard.js',
            array('jquery'),
            CWAU_VERSION,
            true
        );

        wp_localize_script('cwau-operator-dashboard', 'cwauOperator', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cwau_admin_nonce'),
            'operator_id' => get_current_user_id(),
            'operator_name' => wp_get_current_user()->display_name,
            'operator_avatar' => get_avatar_url(get_current_user_id()),
            'refresh_interval' => 3000, // 3 seconds
            'strings' => array(
                'join' => __('Join Chat', 'claude-wc-ultimate'),
                'leave' => __('Leave Chat', 'claude-wc-ultimate'),
                'transfer' => __('Transfer', 'claude-wc-ultimate'),
                'loading' => __('Loading...', 'claude-wc-ultimate'),
                'no_conversations' => __('No active conversations', 'claude-wc-ultimate'),
                'typing' => __('is typing...', 'claude-wc-ultimate')
            )
        ));
    }

    /**
     * Render main dashboard
     */
    public static function render_dashboard() {
        $operator_id = get_current_user_id();
        $stats = CWAU_Live_Chat::get_operator_stats($operator_id, 'today');
        $online_operators = CWAU_Live_Chat::get_online_operators();

        ?>
        <div class="wrap cwau-operator-dashboard">
            <h1>
                <?php _e('Live Chat Dashboard', 'claude-wc-ultimate'); ?>
                <span class="cwau-operator-status-toggle">
                    <select id="cwau-operator-status-select">
                        <option value="online"><?php _e('🟢 Online', 'claude-wc-ultimate'); ?></option>
                        <option value="away"><?php _e('🟡 Away', 'claude-wc-ultimate'); ?></option>
                        <option value="offline"><?php _e('⚫ Offline', 'claude-wc-ultimate'); ?></option>
                    </select>
                </span>
            </h1>

            <!-- Quick Stats -->
            <div class="cwau-stats-grid">
                <div class="cwau-stat-card">
                    <div class="cwau-stat-icon">💬</div>
                    <div class="cwau-stat-content">
                        <div class="cwau-stat-value"><?php echo $stats['total_conversations']; ?></div>
                        <div class="cwau-stat-label"><?php _e('Chats Today', 'claude-wc-ultimate'); ?></div>
                    </div>
                </div>

                <div class="cwau-stat-card">
                    <div class="cwau-stat-icon">⏱️</div>
                    <div class="cwau-stat-content">
                        <div class="cwau-stat-value"><?php echo $stats['avg_handle_time_minutes']; ?>m</div>
                        <div class="cwau-stat-label"><?php _e('Avg Handle Time', 'claude-wc-ultimate'); ?></div>
                    </div>
                </div>

                <div class="cwau-stat-card">
                    <div class="cwau-stat-icon">📊</div>
                    <div class="cwau-stat-content">
                        <div class="cwau-stat-value"><?php echo $stats['currently_active']; ?></div>
                        <div class="cwau-stat-label"><?php _e('Active Now', 'claude-wc-ultimate'); ?></div>
                    </div>
                </div>

                <div class="cwau-stat-card">
                    <div class="cwau-stat-icon">⏰</div>
                    <div class="cwau-stat-content">
                        <div class="cwau-stat-value"><?php echo $stats['total_chat_time_hours']; ?>h</div>
                        <div class="cwau-stat-label"><?php _e('Total Chat Time', 'claude-wc-ultimate'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Online Operators -->
            <div class="cwau-section">
                <h2><?php _e('Online Operators', 'claude-wc-ultimate'); ?> (<?php echo count($online_operators); ?>)</h2>
                <div class="cwau-operators-list">
                    <?php if (!empty($online_operators)): ?>
                        <?php foreach ($online_operators as $op): ?>
                            <div class="cwau-operator-item">
                                <img src="<?php echo esc_url($op['avatar']); ?>" alt="" class="cwau-operator-avatar">
                                <div class="cwau-operator-info">
                                    <strong><?php echo esc_html($op['name']); ?></strong>
                                    <span class="cwau-operator-chats"><?php echo $op['active_chats']; ?> active chats</span>
                                </div>
                                <span class="cwau-status-indicator online"></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php _e('No operators online', 'claude-wc-ultimate'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Active Conversations -->
            <div class="cwau-section">
                <h2><?php _e('My Active Conversations', 'claude-wc-ultimate'); ?></h2>
                <div id="cwau-my-conversations" class="cwau-conversations-grid">
                    <div class="cwau-loading"><?php _e('Loading conversations...', 'claude-wc-ultimate'); ?></div>
                </div>
            </div>

            <!-- Waiting Queue -->
            <div class="cwau-section">
                <h2><?php _e('Waiting Queue', 'claude-wc-ultimate'); ?></h2>
                <div id="cwau-queue" class="cwau-queue-list">
                    <div class="cwau-loading"><?php _e('Loading queue...', 'claude-wc-ultimate'); ?></div>
                </div>
            </div>
        </div>

        <!-- Chat Modal -->
        <div id="cwau-chat-modal" class="cwau-modal" style="display:none;">
            <div class="cwau-modal-overlay"></div>
            <div class="cwau-modal-content">
                <div class="cwau-modal-header">
                    <h3 id="cwau-modal-title"><?php _e('Conversation', 'claude-wc-ultimate'); ?></h3>
                    <button class="cwau-modal-close">&times;</button>
                </div>
                <div class="cwau-modal-body">
                    <!-- Customer Info Sidebar -->
                    <div class="cwau-customer-sidebar">
                        <div id="cwau-customer-info">
                            <div class="cwau-loading"><?php _e('Loading...', 'claude-wc-ultimate'); ?></div>
                        </div>
                    </div>

                    <!-- Chat Area -->
                    <div class="cwau-chat-area">
                        <div id="cwau-messages-container" class="cwau-messages">
                            <div class="cwau-loading"><?php _e('Loading messages...', 'claude-wc-ultimate'); ?></div>
                        </div>

                        <div id="cwau-typing-indicator" class="cwau-typing-indicator" style="display:none;">
                            <span class="cwau-typing-dots">
                                <span></span><span></span><span></span>
                            </span>
                            <span class="cwau-typing-text"></span>
                        </div>

                        <div class="cwau-message-input-area">
                            <!-- Quick Actions -->
                            <div class="cwau-quick-actions">
                                <button class="cwau-btn cwau-btn-sm cwau-btn-transfer">
                                    🔄 <?php _e('Transfer', 'claude-wc-ultimate'); ?>
                                </button>
                                <button class="cwau-btn cwau-btn-sm cwau-btn-create-ticket">
                                    🎫 <?php _e('Create Ticket', 'claude-wc-ultimate'); ?>
                                </button>
                                <button class="cwau-btn cwau-btn-sm cwau-btn-macros">
                                    📝 <?php _e('Macros', 'claude-wc-ultimate'); ?>
                                </button>
                                <button class="cwau-btn cwau-btn-sm cwau-btn-danger cwau-btn-leave">
                                    👋 <?php _e('Leave Chat', 'claude-wc-ultimate'); ?>
                                </button>
                            </div>

                            <!-- Message Input -->
                            <div class="cwau-input-container">
                                <textarea
                                    id="cwau-message-input"
                                    placeholder="<?php _e('Type your message...', 'claude-wc-ultimate'); ?>"
                                    rows="3"
                                ></textarea>
                                <button id="cwau-send-message" class="cwau-btn cwau-btn-primary">
                                    <?php _e('Send', 'claude-wc-ultimate'); ?> ↗
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render queue page
     */
    public static function render_queue() {
        ?>
        <div class="wrap cwau-queue-page">
            <h1><?php _e('Chat Queue', 'claude-wc-ultimate'); ?></h1>

            <div class="cwau-filters">
                <label>
                    <input type="checkbox" id="cwau-filter-escalated">
                    <?php _e('Show only escalated', 'claude-wc-ultimate'); ?>
                </label>
                <select id="cwau-filter-sentiment">
                    <option value=""><?php _e('All sentiments', 'claude-wc-ultimate'); ?></option>
                    <option value="positive"><?php _e('Positive', 'claude-wc-ultimate'); ?></option>
                    <option value="neutral"><?php _e('Neutral', 'claude-wc-ultimate'); ?></option>
                    <option value="negative"><?php _e('Negative', 'claude-wc-ultimate'); ?></option>
                </select>
            </div>

            <div id="cwau-queue-container">
                <div class="cwau-loading"><?php _e('Loading queue...', 'claude-wc-ultimate'); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render my chats page
     */
    public static function render_my_chats() {
        $operator_id = get_current_user_id();
        $conversations = CWAU_Live_Chat::get_operator_conversations($operator_id);
        ?>
        <div class="wrap cwau-my-chats-page">
            <h1><?php _e('My Active Conversations', 'claude-wc-ultimate'); ?> (<?php echo count($conversations); ?>)</h1>

            <div class="cwau-conversations-list">
                <?php if (!empty($conversations)): ?>
                    <?php foreach ($conversations as $conv): ?>
                        <div class="cwau-conversation-card" data-conversation-id="<?php echo $conv->id; ?>">
                            <div class="cwau-conversation-header">
                                <h3><?php echo esc_html($conv->user_email ?: 'Guest'); ?></h3>
                                <span class="cwau-conversation-time">
                                    <?php echo human_time_diff(strtotime($conv->last_message_at), current_time('timestamp')); ?> ago
                                </span>
                            </div>
                            <div class="cwau-conversation-meta">
                                <span class="cwau-badge cwau-badge-<?php echo $conv->sentiment; ?>">
                                    <?php echo ucfirst($conv->sentiment); ?>
                                </span>
                                <span class="cwau-message-count">
                                    <?php echo $conv->message_count; ?> messages
                                </span>
                            </div>
                            <button class="cwau-btn cwau-btn-primary cwau-open-chat" data-conversation-id="<?php echo $conv->id; ?>">
                                <?php _e('Open Chat', 'claude-wc-ultimate'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="cwau-empty-state">
                        <p><?php _e('You have no active conversations', 'claude-wc-ultimate'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=cwau-chat-queue'); ?>" class="cwau-btn cwau-btn-primary">
                            <?php _e('View Queue', 'claude-wc-ultimate'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render tickets page
     */
    public static function render_tickets() {
        $operator_id = get_current_user_id();
        $my_tickets = CWAU_Tickets::get_tickets(array(
            'assigned_to' => $operator_id,
            'status' => array('new', 'open', 'pending'),
            'limit' => 50
        ));
        ?>
        <div class="wrap cwau-tickets-page">
            <h1><?php _e('Support Tickets', 'claude-wc-ultimate'); ?></h1>

            <div class="cwau-tabs">
                <button class="cwau-tab active" data-tab="my-tickets"><?php _e('My Tickets', 'claude-wc-ultimate'); ?></button>
                <button class="cwau-tab" data-tab="all-tickets"><?php _e('All Tickets', 'claude-wc-ultimate'); ?></button>
                <button class="cwau-tab" data-tab="sla-breached"><?php _e('SLA Breached', 'claude-wc-ultimate'); ?></button>
            </div>

            <div id="cwau-tickets-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Ticket', 'claude-wc-ultimate'); ?></th>
                            <th><?php _e('Subject', 'claude-wc-ultimate'); ?></th>
                            <th><?php _e('Status', 'claude-wc-ultimate'); ?></th>
                            <th><?php _e('Priority', 'claude-wc-ultimate'); ?></th>
                            <th><?php _e('SLA', 'claude-wc-ultimate'); ?></th>
                            <th><?php _e('Created', 'claude-wc-ultimate'); ?></th>
                            <th><?php _e('Actions', 'claude-wc-ultimate'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($my_tickets)): ?>
                            <?php foreach ($my_tickets as $ticket): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
                                    <td><?php echo esc_html($ticket->subject); ?></td>
                                    <td>
                                        <span class="cwau-badge cwau-badge-<?php echo $ticket->status; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cwau-priority cwau-priority-<?php echo $ticket->priority; ?>">
                                            <?php echo ucfirst($ticket->priority); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ticket->sla_breached): ?>
                                            <span class="cwau-sla-breached">❌ Breached</span>
                                        <?php else: ?>
                                            <span class="cwau-sla-ok">✅ <?php echo human_time_diff(current_time('timestamp'), strtotime($ticket->sla_due_at)); ?> left</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo human_time_diff(strtotime($ticket->created_at), current_time('timestamp')); ?> ago</td>
                                    <td>
                                        <button class="button cwau-view-ticket" data-ticket-id="<?php echo $ticket->id; ?>">
                                            <?php _e('View', 'claude-wc-ultimate'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="cwau-empty-state">
                                    <?php _e('No tickets assigned to you', 'claude-wc-ultimate'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render CRM page
     */
    public static function render_crm() {
        $recent_customers = CWAU_CRM::search_customers(array('limit' => 20));
        $hot_leads = CWAU_CRM::get_hot_leads(10);
        ?>
        <div class="wrap cwau-crm-page">
            <h1><?php _e('CRM - Customer Relationship Management', 'claude-wc-ultimate'); ?></h1>

            <div class="cwau-tabs">
                <button class="cwau-tab active" data-tab="customers"><?php _e('Customers', 'claude-wc-ultimate'); ?></button>
                <button class="cwau-tab" data-tab="hot-leads"><?php _e('Hot Leads', 'claude-wc-ultimate'); ?></button>
                <button class="cwau-tab" data-tab="pipeline"><?php _e('Sales Pipeline', 'claude-wc-ultimate'); ?></button>
            </div>

            <div id="cwau-crm-container">
                <!-- Customer list will be loaded here -->
            </div>
        </div>
        <?php
    }

    /**
     * Render analytics page
     */
    public static function render_analytics() {
        $ticket_stats = CWAU_Tickets::get_stats('month');
        ?>
        <div class="wrap cwau-analytics-page">
            <h1><?php _e('Analytics & Reports', 'claude-wc-ultimate'); ?></h1>

            <div class="cwau-period-selector">
                <button class="cwau-period active" data-period="today"><?php _e('Today', 'claude-wc-ultimate'); ?></button>
                <button class="cwau-period" data-period="week"><?php _e('This Week', 'claude-wc-ultimate'); ?></button>
                <button class="cwau-period" data-period="month"><?php _e('This Month', 'claude-wc-ultimate'); ?></button>
            </div>

            <div class="cwau-stats-grid">
                <div class="cwau-stat-card">
                    <h3><?php _e('Total Tickets', 'claude-wc-ultimate'); ?></h3>
                    <div class="cwau-stat-value"><?php echo $ticket_stats['total']; ?></div>
                </div>

                <div class="cwau-stat-card">
                    <h3><?php _e('SLA Compliance', 'claude-wc-ultimate'); ?></h3>
                    <div class="cwau-stat-value"><?php echo $ticket_stats['sla_compliance']; ?>%</div>
                </div>

                <div class="cwau-stat-card">
                    <h3><?php _e('Avg Resolution Time', 'claude-wc-ultimate'); ?></h3>
                    <div class="cwau-stat-value"><?php echo $ticket_stats['avg_resolution_hours']; ?>h</div>
                </div>

                <div class="cwau-stat-card">
                    <h3><?php _e('Avg CSAT', 'claude-wc-ultimate'); ?></h3>
                    <div class="cwau-stat-value"><?php echo $ticket_stats['avg_csat']; ?>/5</div>
                </div>
            </div>

            <div id="cwau-charts-container">
                <!-- Charts will be loaded here via JS -->
            </div>
        </div>
        <?php
    }
}

// Initialize
CWAU_Operator_Dashboard::init();
