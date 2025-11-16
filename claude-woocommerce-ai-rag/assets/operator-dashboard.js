/**
 * Operator Dashboard JavaScript
 * Real-time chat management and updates
 */

(function($) {
    'use strict';

    class OperatorDashboard {
        constructor() {
            this.currentConversationId = null;
            this.refreshInterval = null;
            this.typingTimeout = null;
            this.lastMessageId = 0;

            this.init();
        }

        init() {
            this.bindEvents();
            this.loadDashboard();
            this.startRefreshLoop();
        }

        bindEvents() {
            const self = this;

            // Operator status change
            $(document).on('change', '#cwau-operator-status-select', function() {
                self.updateOperatorStatus($(this).val());
            });

            // Open conversation
            $(document).on('click', '.cwau-open-chat, .cwau-queue-item', function() {
                const conversationId = $(this).data('conversation-id');
                self.openConversation(conversationId);
            });

            // Close modal
            $(document).on('click', '.cwau-modal-close, .cwau-modal-overlay', function() {
                self.closeConversation();
            });

            // Join conversation
            $(document).on('click', '.cwau-btn-join', function() {
                self.joinConversation(self.currentConversationId);
            });

            // Leave conversation
            $(document).on('click', '.cwau-btn-leave', function() {
                if (confirm(cwauOperator.strings.leave + '?')) {
                    self.leaveConversation(self.currentConversationId);
                }
            });

            // Transfer conversation
            $(document).on('click', '.cwau-btn-transfer', function() {
                self.showTransferDialog();
            });

            // Send message
            $(document).on('click', '#cwau-send-message', function() {
                self.sendMessage();
            });

            // Send on Enter (Shift+Enter for new line)
            $(document).on('keydown', '#cwau-message-input', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Typing indicator
            $(document).on('input', '#cwau-message-input', function() {
                self.sendTypingIndicator(true);

                clearTimeout(self.typingTimeout);
                self.typingTimeout = setTimeout(function() {
                    self.sendTypingIndicator(false);
                }, 3000);
            });

            // Macros
            $(document).on('click', '.cwau-btn-macros', function() {
                self.showMacrosDialog();
            });

            // Create ticket
            $(document).on('click', '.cwau-btn-create-ticket', function() {
                self.createTicketFromChat(self.currentConversationId);
            });

            // Tabs
            $(document).on('click', '.cwau-tab', function() {
                $('.cwau-tab').removeClass('active');
                $(this).addClass('active');
                const tab = $(this).data('tab');
                self.switchTab(tab);
            });

            // Period selector
            $(document).on('click', '.cwau-period', function() {
                $('.cwau-period').removeClass('active');
                $(this).addClass('active');
                const period = $(this).data('period');
                self.loadAnalytics(period);
            });

            // Filters
            $(document).on('change', '#cwau-filter-escalated, #cwau-filter-sentiment', function() {
                self.loadQueue();
            });
        }

        loadDashboard() {
            this.loadMyConversations();
            this.loadQueue();
            this.loadOperatorStatus();
        }

        loadMyConversations() {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_operator_get_my_conversations',
                    nonce: cwauOperator.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderConversations(response.data.conversations);
                    }
                }
            });
        }

        renderConversations(conversations) {
            const $container = $('#cwau-my-conversations');

            if (!conversations || conversations.length === 0) {
                $container.html(`
                    <div class="cwau-empty-state">
                        <p>${cwauOperator.strings.no_conversations}</p>
                    </div>
                `);
                return;
            }

            let html = '';
            conversations.forEach(conv => {
                const timeAgo = this.timeAgo(conv.last_message_at);
                html += `
                    <div class="cwau-conversation-card cwau-open-chat" data-conversation-id="${conv.id}">
                        <div class="cwau-conversation-header">
                            <h3>${this.escapeHtml(conv.user_email || 'Guest')}</h3>
                            <span class="cwau-conversation-time">${timeAgo}</span>
                        </div>
                        <div class="cwau-conversation-meta">
                            <span class="cwau-badge cwau-badge-${conv.sentiment}">${conv.sentiment}</span>
                            <span class="cwau-message-count">${conv.message_count} messages</span>
                        </div>
                    </div>
                `;
            });

            $container.html(html);
        }

        loadQueue() {
            const escalated = $('#cwau-filter-escalated').is(':checked');
            const sentiment = $('#cwau-filter-sentiment').val();

            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_operator_get_queue',
                    nonce: cwauOperator.nonce,
                    escalated: escalated,
                    sentiment: sentiment
                },
                success: (response) => {
                    if (response.success) {
                        this.renderQueue(response.data.queue);
                    }
                }
            });
        }

        renderQueue(queue) {
            const $container = $('#cwau-queue, #cwau-queue-container');

            if (!queue || queue.length === 0) {
                $container.html(`
                    <div class="cwau-empty-state">
                        <p>No conversations waiting in queue</p>
                    </div>
                `);
                return;
            }

            let html = '';
            queue.forEach(item => {
                const urgentClass = item.wait_time_minutes > 10 ? 'urgent' : '';
                html += `
                    <div class="cwau-queue-item" data-conversation-id="${item.id}">
                        <div class="cwau-queue-info">
                            <strong>${this.escapeHtml(item.user_email || 'Guest')}</strong>
                            <div class="cwau-queue-meta">
                                <span class="cwau-badge cwau-badge-${item.sentiment}">${item.sentiment}</span>
                                <span class="cwau-message-count">${item.message_count} messages</span>
                                <span class="cwau-wait-time ${urgentClass}">⏱️ ${item.wait_time_minutes} min wait</span>
                            </div>
                        </div>
                        <button class="cwau-btn cwau-btn-primary cwau-btn-sm" data-conversation-id="${item.id}">
                            ${cwauOperator.strings.join}
                        </button>
                    </div>
                `;
            });

            $container.html(html);
        }

        openConversation(conversationId) {
            this.currentConversationId = conversationId;
            $('#cwau-chat-modal').fadeIn(200);
            this.loadConversationDetails(conversationId);
            this.loadMessages(conversationId);

            // Check if already joined
            this.checkOperatorSession(conversationId);
        }

        loadConversationDetails(conversationId) {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_get_conversation_details',
                    nonce: cwauOperator.nonce,
                    conversation_id: conversationId
                },
                success: (response) => {
                    if (response.success) {
                        this.renderCustomerInfo(response.data);
                        $('#cwau-modal-title').text(`Conversation #${conversationId}`);
                    }
                }
            });
        }

        renderCustomerInfo(data) {
            const customer = data.customer || {};
            const conversation = data.conversation || {};

            let html = '<div class="cwau-customer-info">';
            html += '<h4>Customer Information</h4>';

            if (customer.avatar_url) {
                html += `<img src="${customer.avatar_url}" alt="" style="width:80px;height:80px;border-radius:50%;margin-bottom:16px;">`;
            }

            html += `
                <div class="cwau-customer-field">
                    <label>Name</label>
                    <value>${this.escapeHtml(customer.first_name + ' ' + customer.last_name || 'N/A')}</value>
                </div>
                <div class="cwau-customer-field">
                    <label>Email</label>
                    <value>${this.escapeHtml(customer.email || conversation.user_email || 'N/A')}</value>
                </div>
                <div class="cwau-customer-field">
                    <label>Phone</label>
                    <value>${this.escapeHtml(customer.phone || 'N/A')}</value>
                </div>
                <div class="cwau-customer-field">
                    <label>Lead Score</label>
                    <value>${customer.lead_score || 0}/100</value>
                </div>
                <div class="cwau-customer-field">
                    <label>Lifecycle Stage</label>
                    <value>${this.escapeHtml(customer.lifecycle_stage || 'lead')}</value>
                </div>
                <div class="cwau-customer-field">
                    <label>Total Orders</label>
                    <value>${customer.total_orders || 0}</value>
                </div>
                <div class="cwau-customer-field">
                    <label>Total Revenue</label>
                    <value>$${customer.total_revenue || '0.00'}</value>
                </div>
            `;

            html += '</div>';
            $('#cwau-customer-info').html(html);
        }

        loadMessages(conversationId) {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_get_conversation_messages',
                    nonce: cwauOperator.nonce,
                    conversation_id: conversationId
                },
                success: (response) => {
                    if (response.success) {
                        this.renderMessages(response.data.messages);
                        this.scrollToBottom();
                    }
                }
            });
        }

        renderMessages(messages) {
            const $container = $('#cwau-messages-container');

            if (!messages || messages.length === 0) {
                $container.html('<div class="cwau-empty-state"><p>No messages yet</p></div>');
                return;
            }

            let html = '';
            messages.forEach(msg => {
                const metadata = msg.metadata ? JSON.parse(msg.metadata) : {};
                const avatar = metadata.operator_avatar || cwauOperator.operator_avatar;
                const name = metadata.operator_name || cwauOperator.operator_name;

                html += `
                    <div class="cwau-message ${msg.sender}">
                        ${msg.sender === 'user' ? `<img src="${avatar}" class="cwau-message-avatar">` : ''}
                        <div class="cwau-message-content">
                            ${this.escapeHtml(msg.message)}
                            <div class="cwau-message-meta">
                                ${msg.sender === 'operator' ? name + ' · ' : ''}${this.formatTime(msg.created_at)}
                            </div>
                        </div>
                        ${msg.sender !== 'user' ? `<img src="${avatar}" class="cwau-message-avatar">` : ''}
                    </div>
                `;

                this.lastMessageId = Math.max(this.lastMessageId, msg.id);
            });

            $container.html(html);
        }

        checkOperatorSession(conversationId) {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_check_operator_session',
                    nonce: cwauOperator.nonce,
                    conversation_id: conversationId,
                    operator_id: cwauOperator.operator_id
                },
                success: (response) => {
                    if (response.success && response.data.has_session) {
                        // Already joined - show message input
                        this.enableMessageInput();
                    } else {
                        // Not joined - show join button
                        this.showJoinPrompt();
                    }
                }
            });
        }

        showJoinPrompt() {
            const html = `
                <div class="cwau-join-prompt" style="padding:20px;text-align:center;background:#f3f4f6;">
                    <p>You haven't joined this conversation yet.</p>
                    <button class="cwau-btn cwau-btn-primary cwau-btn-join">
                        ${cwauOperator.strings.join}
                    </button>
                </div>
            `;
            $('.cwau-message-input-area').html(html);
        }

        enableMessageInput() {
            // Message input is already in HTML, just make sure it's visible
            $('.cwau-message-input-area').show();
        }

        joinConversation(conversationId) {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_operator_join',
                    nonce: cwauOperator.nonce,
                    conversation_id: conversationId
                },
                success: (response) => {
                    if (response.success) {
                        this.enableMessageInput();
                        this.loadMessages(conversationId);
                        this.showNotification('Joined conversation successfully', 'success');
                    } else {
                        this.showNotification(response.data.message || 'Failed to join', 'error');
                    }
                }
            });
        }

        leaveConversation(conversationId) {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_operator_leave',
                    nonce: cwauOperator.nonce,
                    conversation_id: conversationId,
                    reason: 'Operator left'
                },
                success: (response) => {
                    if (response.success) {
                        this.closeConversation();
                        this.loadDashboard();
                        this.showNotification('Left conversation successfully', 'success');
                    }
                }
            });
        }

        sendMessage() {
            const $input = $('#cwau-message-input');
            const message = $input.val().trim();

            if (!message) return;

            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_operator_send_message',
                    nonce: cwauOperator.nonce,
                    conversation_id: this.currentConversationId,
                    message: message
                },
                success: (response) => {
                    if (response.success) {
                        $input.val('');
                        this.loadMessages(this.currentConversationId);
                        this.sendTypingIndicator(false);
                    } else {
                        this.showNotification(response.data.error || 'Failed to send', 'error');
                    }
                }
            });
        }

        sendTypingIndicator(isTyping) {
            if (!this.currentConversationId) return;

            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_operator_typing',
                    nonce: cwauOperator.nonce,
                    conversation_id: this.currentConversationId,
                    is_typing: isTyping
                }
            });
        }

        closeConversation() {
            $('#cwau-chat-modal').fadeOut(200);
            this.currentConversationId = null;
            this.lastMessageId = 0;
            this.sendTypingIndicator(false);
        }

        updateOperatorStatus(status) {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_operator_update_status',
                    nonce: cwauOperator.nonce,
                    status: status
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(`Status updated to ${status}`, 'success');
                    }
                }
            });
        }

        loadOperatorStatus() {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_get_operator_status',
                    nonce: cwauOperator.nonce
                },
                success: (response) => {
                    if (response.success && response.data.status) {
                        $('#cwau-operator-status-select').val(response.data.status);
                    }
                }
            });
        }

        showTransferDialog() {
            // TODO: Show modal with operator list to transfer to
            const targetOperatorId = prompt('Enter operator ID to transfer to:');
            if (targetOperatorId) {
                this.transferConversation(this.currentConversationId, targetOperatorId);
            }
        }

        transferConversation(conversationId, toOperatorId) {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_operator_transfer',
                    nonce: cwauOperator.nonce,
                    conversation_id: conversationId,
                    to_operator_id: toOperatorId,
                    reason: 'Manual transfer'
                },
                success: (response) => {
                    if (response.success) {
                        this.closeConversation();
                        this.loadDashboard();
                        this.showNotification('Conversation transferred successfully', 'success');
                    } else {
                        this.showNotification(response.data.error || 'Transfer failed', 'error');
                    }
                }
            });
        }

        showMacrosDialog() {
            // TODO: Show modal with macro templates
            alert('Macros feature coming soon!');
        }

        createTicketFromChat(conversationId) {
            const subject = prompt('Enter ticket subject:');
            if (!subject) return;

            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_create_ticket',
                    nonce: cwauOperator.nonce,
                    conversation_id: conversationId,
                    subject: subject
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(`Ticket ${response.data.ticket_number} created!`, 'success');
                    } else {
                        this.showNotification(response.data.message || 'Failed to create ticket', 'error');
                    }
                }
            });
        }

        startRefreshLoop() {
            this.refreshInterval = setInterval(() => {
                if ($('#cwau-my-conversations').is(':visible')) {
                    this.loadMyConversations();
                }
                if ($('#cwau-queue').is(':visible')) {
                    this.loadQueue();
                }
                if (this.currentConversationId) {
                    this.checkNewMessages();
                    this.checkTypingIndicators();
                }
            }, cwauOperator.refresh_interval);
        }

        checkNewMessages() {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_get_new_messages',
                    nonce: cwauOperator.nonce,
                    conversation_id: this.currentConversationId,
                    after_id: this.lastMessageId
                },
                success: (response) => {
                    if (response.success && response.data.messages.length > 0) {
                        this.loadMessages(this.currentConversationId);
                    }
                }
            });
        }

        checkTypingIndicators() {
            $.ajax({
                url: cwauOperator.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_get_typing_indicators',
                    nonce: cwauOperator.nonce,
                    conversation_id: this.currentConversationId
                },
                success: (response) => {
                    if (response.success) {
                        this.renderTypingIndicators(response.data.typing);
                    }
                }
            });
        }

        renderTypingIndicators(typing) {
            const $indicator = $('#cwau-typing-indicator');

            if (typing.length > 0) {
                const names = typing.map(t => t.name).join(', ');
                $('#cwau-typing-indicator .cwau-typing-text').text(`${names} ${cwauOperator.strings.typing}`);
                $indicator.show();
            } else {
                $indicator.hide();
            }
        }

        switchTab(tab) {
            // Handle tab switching logic
            console.log('Switching to tab:', tab);
        }

        loadAnalytics(period) {
            // Load analytics for specified period
            console.log('Loading analytics for:', period);
        }

        scrollToBottom() {
            const $container = $('#cwau-messages-container');
            $container.scrollTop($container[0].scrollHeight);
        }

        showNotification(message, type = 'info') {
            // Simple notification - could be enhanced with toast library
            if (type === 'error') {
                alert('Error: ' + message);
            } else {
                console.log(message);
            }
        }

        timeAgo(dateString) {
            const seconds = Math.floor((new Date() - new Date(dateString)) / 1000);

            const intervals = {
                year: 31536000,
                month: 2592000,
                week: 604800,
                day: 86400,
                hour: 3600,
                minute: 60
            };

            for (const [name, value] of Object.entries(intervals)) {
                const interval = Math.floor(seconds / value);
                if (interval >= 1) {
                    return interval === 1 ? `1 ${name} ago` : `${interval} ${name}s ago`;
                }
            }

            return 'Just now';
        }

        formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('.cwau-operator-dashboard').length || $('.cwau-queue-page').length || $('.cwau-my-chats-page').length) {
            new OperatorDashboard();
        }
    });

})(jQuery);
