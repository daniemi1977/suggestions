/**
 * Enhanced Frontend Chat Widget JavaScript
 * Handles rich messages, product carousel, add to cart, and animations
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
            this.carousels = [];

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

            // Delegate events for dynamic content
            this.$messages.on('click', '.btn-add-to-cart', (e) => this.handleAddToCart(e));
            this.$messages.on('click', '.btn-notify-stock', (e) => this.handleNotifyStock(e));
            this.$messages.on('click', '.carousel-prev', (e) => this.handleCarouselNav(e, 'prev'));
            this.$messages.on('click', '.carousel-next', (e) => this.handleCarouselNav(e, 'next'));
            this.$messages.on('click', '.indicator', (e) => this.handleCarouselIndicator(e));

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
                this.scrollToBottom();
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
            this.$quickReplies.fadeOut(300);

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

                        // Add AI text response
                        this.addMessage(aiMessage, 'ai');

                        // Add rich content if available
                        if (response.data.rich_content && response.data.rich_content.length > 0) {
                            response.data.rich_content.forEach(content => {
                                this.addRichContent(content);
                            });
                        }
                    } else {
                        this.showError(response.data.message || 'Si è verificato un errore');
                    }
                },
                error: (xhr, status, error) => {
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

        addRichContent(content) {
            const $richMessage = $(`
                <div class="cwau-message ai-message">
                    <div class="message-avatar">AI</div>
                    <div class="message-content" style="max-width: 90%; background: transparent; border: none; box-shadow: none; padding: 0;">
                        ${content.html}
                    </div>
                </div>
            `);

            this.$messages.append($richMessage);

            // Initialize carousels in this rich content
            $richMessage.find('.cwau-product-carousel').each((index, carousel) => {
                this.initCarousel($(carousel));
            });

            this.scrollToBottom();
        }

        initCarousel($carousel) {
            const $track = $carousel.find('.carousel-track');
            const $items = $carousel.find('.carousel-item');
            const $prev = $carousel.find('.carousel-prev');
            const $next = $carousel.find('.carousel-next');
            const $indicators = $carousel.find('.indicator');

            if ($items.length <= 1) {
                $prev.hide();
                $next.hide();
                $indicators.hide();
                return;
            }

            const carousel = {
                $el: $carousel,
                $track: $track,
                $items: $items,
                $prev: $prev,
                $next: $next,
                $indicators: $indicators,
                currentIndex: 0,
                totalItems: $items.length
            };

            this.carousels.push(carousel);
            this.updateCarousel(carousel);
        }

        handleCarouselNav(e, direction) {
            e.preventDefault();
            const $carousel = $(e.target).closest('.cwau-product-carousel');
            const carousel = this.carousels.find(c => c.$el.is($carousel));

            if (!carousel) return;

            if (direction === 'prev' && carousel.currentIndex > 0) {
                carousel.currentIndex--;
            } else if (direction === 'next' && carousel.currentIndex < carousel.totalItems - 1) {
                carousel.currentIndex++;
            }

            this.updateCarousel(carousel);
        }

        handleCarouselIndicator(e) {
            e.preventDefault();
            const $indicator = $(e.target);
            const index = $indicator.data('index');
            const $carousel = $indicator.closest('.cwau-product-carousel');
            const carousel = this.carousels.find(c => c.$el.is($carousel));

            if (!carousel) return;

            carousel.currentIndex = index;
            this.updateCarousel(carousel);
        }

        updateCarousel(carousel) {
            // Update track position
            const offset = -carousel.currentIndex * 100;
            carousel.$track.css('transform', `translateX(${offset}%)`);

            // Update indicators
            carousel.$indicators.removeClass('active');
            carousel.$indicators.eq(carousel.currentIndex).addClass('active');

            // Update navigation buttons
            carousel.$prev.prop('disabled', carousel.currentIndex === 0);
            carousel.$next.prop('disabled', carousel.currentIndex === carousel.totalItems - 1);
        }

        handleAddToCart(e) {
            e.preventDefault();
            const $btn = $(e.target);
            const productId = $btn.data('product-id');

            if (!productId) return;

            // Disable button and show loading
            $btn.prop('disabled', true);
            const originalHtml = $btn.html();
            $btn.html('<svg class="cwau-loading-spinner" width="16" height="16" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" /></svg>');

            $.ajax({
                url: cwau_chat.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_add_to_cart',
                    nonce: cwau_chat.nonce,
                    product_id: productId,
                    quantity: 1
                },
                success: (response) => {
                    if (response.success) {
                        // Show success message
                        this.showSuccessNotification(response.data.message);

                        // Update button
                        $btn.html('✓ Aggiunto!');
                        $btn.css('background', 'linear-gradient(135deg, #10b981 0%, #059669 100%)');

                        // Update cart count if widget exists
                        this.updateCartBadge(response.data.cart_count);

                        // Restore button after 2 seconds
                        setTimeout(() => {
                            $btn.html(originalHtml);
                            $btn.css('background', '');
                            $btn.prop('disabled', false);
                        }, 2000);
                    } else {
                        this.showError(response.data.message || 'Impossibile aggiungere al carrello');
                        $btn.html(originalHtml);
                        $btn.prop('disabled', false);
                    }
                },
                error: () => {
                    this.showError('Errore durante l\'aggiunta al carrello');
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        }

        handleNotifyStock(e) {
            e.preventDefault();
            const $btn = $(e.target);
            const productId = $btn.data('product-id');

            if (!productId) return;

            // Prompt for email if user is not logged in
            let email = '';
            if (typeof cwau_chat.user_email !== 'undefined' && cwau_chat.user_email) {
                email = cwau_chat.user_email;
            } else {
                email = prompt('Inserisci la tua email per ricevere la notifica:');
                if (!email) return;
            }

            $btn.prop('disabled', true);
            const originalHtml = $btn.html();
            $btn.html('Invio...');

            $.ajax({
                url: cwau_chat.ajax_url,
                type: 'POST',
                data: {
                    action: 'cwau_notify_stock',
                    nonce: cwau_chat.nonce,
                    product_id: productId,
                    email: email
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccessNotification(response.data.message);
                        $btn.html('✓ Registrato!');

                        setTimeout(() => {
                            $btn.html(originalHtml);
                            $btn.prop('disabled', false);
                        }, 3000);
                    } else {
                        this.showError(response.data.message);
                        $btn.html(originalHtml);
                        $btn.prop('disabled', false);
                    }
                },
                error: () => {
                    this.showError('Errore durante la registrazione');
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        }

        showSuccessNotification(message) {
            const $notification = $(`
                <div class="cwau-success-notification">
                    ✓ ${this.escapeHtml(message)}
                </div>
            `);

            $('body').append($notification);

            // Animate in
            setTimeout(() => {
                $notification.addClass('show');
            }, 10);

            // Remove after 3 seconds
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 3000);
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

        updateCartBadge(count) {
            // Update WooCommerce cart widget if exists
            if ($('.cart-contents-count').length) {
                $('.cart-contents-count').text(count);
            }

            // Trigger WooCommerce cart fragment refresh
            $(document.body).trigger('wc_fragment_refresh');
        }

        scrollToBottom() {
            setTimeout(() => {
                this.$messages.scrollTop(this.$messages[0].scrollHeight);
            }, 100);
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, (m) => map[m]);
        }
    }

    // Initialize chat when DOM is ready
    $(document).ready(function() {
        if ($('.cwau-chat-widget').length) {
            window.cwauChatInstance = new CWAUChat();
        }
    });

})(jQuery);

// Add success notification CSS dynamically
jQuery(document).ready(function($) {
    if (!$('#cwau-notification-styles').length) {
        $('head').append(`
            <style id="cwau-notification-styles">
                .cwau-success-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: white;
                    padding: 16px 24px;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
                    font-weight: 600;
                    font-size: 15px;
                    z-index: 999999;
                    transform: translateY(-100px);
                    opacity: 0;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }

                .cwau-success-notification.show {
                    transform: translateY(0);
                    opacity: 1;
                }

                @media (max-width: 480px) {
                    .cwau-success-notification {
                        left: 20px;
                        right: 20px;
                        top: 10px;
                    }
                }
            </style>
        `);
    }
});
