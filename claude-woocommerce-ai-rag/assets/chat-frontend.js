/**
 * Frontend Chat Widget JavaScript
 */

(function($) {
    'use strict';

    class CWAUChat {
        constructor() {
            this.$widget = $('.cwau-chat-widget');
            this.$messages = $('.cwau-chat-messages');
            this.$input = $('.cwau-chat-input');
            this.$sendBtn = $('.cwau-send-button');
            this.$typing = $('.cwau-typing-indicator');
            this.$quickReplies = $('.cwau-quick-replies');

            this.isMinimized = false;
            this.conversationId = null;

            this.init();
        }

        init() {
            // Event listeners
            this.$sendBtn.on('click', () => this.sendMessage());

            this.$input.on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            $('.cwau-chat-minimize').on('click', () => this.toggleMinimize());

            $('.quick-reply').on('click', (e) => {
                const text = $(e.target).data('text') || $(e.target).text();
                this.$input.val(text);
                this.sendMessage();
            });

            // Auto-scroll to bottom
            this.scrollToBottom();

            // Focus input on load
            this.$input.focus();
        }

        toggleMinimize() {
            this.isMinimized = !this.isMinimized;
            this.$widget.toggleClass('minimized');

            if (!this.isMinimized) {
                this.$input.focus();
            }
        }

        sendMessage() {
            const message = this.$input.val().trim();

            if (!message) {
                return;
            }

            // Disable input
            this.$input.prop('disabled', true);
            this.$sendBtn.prop('disabled', true);

            // Add user message to UI
            this.addMessage(message, 'user');

            // Clear input
            this.$input.val('');

            // Hide quick replies after first message
            this.$quickReplies.hide();

            // Show typing indicator
            this.$typing.show();

            // Send to server
            $.ajax({
                url: cwau_chat.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_chat',
                    nonce: cwau_chat.nonce,
                    message: message,
                    session_id: cwau_chat.session_id,
                    conversation_id: this.conversationId
                },
                success: (response) => {
                    this.$typing.hide();

                    if (response.success) {
                        const aiMessage = response.data.message;
                        this.conversationId = response.data.conversation_id;

                        // Add AI response
                        this.addMessage(aiMessage, 'ai');
                    } else {
                        this.showError(response.data.message || 'Si è verificato un errore');
                    }
                },
                error: (xhr, status, error) {
                    this.$typing.hide();
                    console.error('Chat error:', error);
                    this.showError('Impossibile connettersi al server. Riprova tra poco.');
                },
                complete: () => {
                    this.$input.prop('disabled', false);
                    this.$sendBtn.prop('disabled', false);
                    this.$input.focus();
                }
            });
        }

        addMessage(text, sender) {
            const avatarText = sender === 'ai' ? 'AI' : 'Tu';
            const messageClass = sender === 'ai' ? 'ai-message' : 'user-message';

            const $message = $(`
                <div class="cwau-message ${messageClass}">
                    <div class="message-avatar">${avatarText}</div>
                    <div class="message-content">
                        <p>${this.escapeHtml(text)}</p>
                    </div>
                </div>
            `);

            this.$messages.append($message);
            this.scrollToBottom();
        }

        showError(message) {
            const $error = $(`
                <div class="cwau-error">
                    ${this.escapeHtml(message)}
                </div>
            `);

            this.$messages.append($error);
            this.scrollToBottom();

            // Remove error after 5 seconds
            setTimeout(() => {
                $error.fadeOut(() => $error.remove());
            }, 5000);
        }

        scrollToBottom() {
            this.$messages.scrollTop(this.$messages[0].scrollHeight);
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, (m) => map[m]);
        }
    }

    // Initialize chat when DOM is ready
    $(document).ready(function() {
        if ($('.cwau-chat-widget').length) {
            new CWAUChat();
        }
    });

})(jQuery);
