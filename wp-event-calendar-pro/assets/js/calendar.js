/**
 * WP Event Calendar Pro - Frontend JavaScript
 * Handles calendar interactions, lightbox, and booking
 */

(function($) {
    'use strict';

    const WECP = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initLightbox();
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Calendar navigation
            $(document).on('click', '.wecp-nav-prev', this.navigatePrev.bind(this));
            $(document).on('click', '.wecp-nav-next', this.navigateNext.bind(this));

            // Event interactions
            $(document).on('click', '.wecp-event-dot', this.handleEventDotClick.bind(this));
            $(document).on('click', '.wecp-btn-details', this.showEventDetails.bind(this));
            $(document).on('click', '.wecp-btn-book, .wecp-btn-book-large', this.handleBooking.bind(this));

            // Calendar cell clicks
            $(document).on('click', '.wecp-calendar-cell.wecp-has-events', this.handleCellClick.bind(this));
        },

        /**
         * Navigate to previous month
         */
        navigatePrev: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            let month = parseInt($btn.data('month'));
            let year = parseInt($btn.data('year'));

            month--;
            if (month < 1) {
                month = 12;
                year--;
            }

            this.loadMonth(month, year);
        },

        /**
         * Navigate to next month
         */
        navigateNext: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            let month = parseInt($btn.data('month'));
            let year = parseInt($btn.data('year'));

            month++;
            if (month > 12) {
                month = 1;
                year++;
            }

            this.loadMonth(month, year);
        },

        /**
         * Load calendar for specific month
         */
        loadMonth: function(month, year) {
            const $wrapper = $('.wecp-calendar-wrapper');

            $.ajax({
                url: wecpData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wecp_get_events',
                    nonce: wecpData.nonce,
                    month: month,
                    year: year
                },
                beforeSend: function() {
                    $wrapper.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        // Update calendar
                        // In a real implementation, you would rebuild the calendar grid here
                        $('.wecp-nav-prev, .wecp-nav-next').data({month: month, year: year});

                        const monthName = wecpData.monthNames[month - 1];
                        $('.wecp-calendar-title .month-name').text(monthName + ' ' + year);
                    }
                },
                complete: function() {
                    $wrapper.removeClass('loading');
                }
            });
        },

        /**
         * Handle event dot click
         */
        handleEventDotClick: function(e) {
            e.stopPropagation();
            const eventId = $(e.currentTarget).data('event-id');
            this.showEventDetails({currentTarget: {dataset: {eventId: eventId}}});
        },

        /**
         * Handle cell click
         */
        handleCellClick: function(e) {
            const $cell = $(e.currentTarget);
            const $firstDot = $cell.find('.wecp-event-dot').first();

            if ($firstDot.length) {
                const eventId = $firstDot.data('event-id');
                this.showEventDetails({currentTarget: {dataset: {eventId: eventId}}});
            }
        },

        /**
         * Show event details in lightbox
         */
        showEventDetails: function(e) {
            e.preventDefault();
            const eventId = $(e.currentTarget).data('event-id');

            $.ajax({
                url: wecpData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wecp_get_event_details',
                    nonce: wecpData.nonce,
                    event_id: eventId
                },
                beforeSend: function() {
                    $('#wecp-lightbox .wecp-lightbox-body').html('<p>' + wecpData.strings.loading + '</p>');
                    WECP.openLightbox();
                },
                success: function(response) {
                    if (response.success) {
                        $('#wecp-lightbox .wecp-lightbox-body').html(response.data.html);
                    } else {
                        $('#wecp-lightbox .wecp-lightbox-body').html('<p>' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    $('#wecp-lightbox .wecp-lightbox-body').html('<p>Error loading event details.</p>');
                }
            });
        },

        /**
         * Handle booking (add to cart)
         */
        handleBooking: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const eventId = $btn.data('event-id');
            const productId = $btn.data('product-id');

            // Check availability first
            $.ajax({
                url: wecpData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wecp_check_availability',
                    nonce: wecpData.nonce,
                    event_id: eventId,
                    quantity: 1
                },
                success: function(response) {
                    if (response.success && response.data.available) {
                        WECP.addToCart(eventId, 1, $btn);
                    } else {
                        alert(wecpData.strings.soldOut);
                    }
                }
            });
        },

        /**
         * Add event to cart
         */
        addToCart: function(eventId, quantity, $btn) {
            const originalText = $btn.text();

            $.ajax({
                url: wecpData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wecp_add_to_cart',
                    nonce: wecpData.nonce,
                    event_id: eventId,
                    quantity: quantity
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).text(wecpData.strings.loading);
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        WECP.showNotification(response.data.message, 'success');

                        // Update cart count if exists
                        if ($('.cart-count').length) {
                            $('.cart-count').text(response.data.cart_count);
                        }

                        // Option to redirect to cart
                        if (confirm(response.data.message + '\n\n' + 'View cart?')) {
                            window.location.href = response.data.cart_url;
                        }
                    } else {
                        WECP.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    WECP.showNotification('Error adding to cart', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Initialize lightbox
         */
        initLightbox: function() {
            // Close lightbox on overlay click
            $(document).on('click', '.wecp-lightbox-overlay', this.closeLightbox);

            // Close lightbox on close button click
            $(document).on('click', '.wecp-lightbox-close', this.closeLightbox);

            // Close lightbox on ESC key
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape' && $('#wecp-lightbox').is(':visible')) {
                    WECP.closeLightbox();
                }
            });
        },

        /**
         * Open lightbox
         */
        openLightbox: function() {
            $('#wecp-lightbox').fadeIn(300);
            $('body').css('overflow', 'hidden');
        },

        /**
         * Close lightbox
         */
        closeLightbox: function() {
            $('#wecp-lightbox').fadeOut(300);
            $('body').css('overflow', '');
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';

            const $notification = $('<div class="wecp-notification wecp-notification-' + type + '">' + message + '</div>');

            $('body').append($notification);

            setTimeout(function() {
                $notification.addClass('show');
            }, 100);

            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        WECP.init();
    });

    // Expose WECP globally
    window.WECP = WECP;

})(jQuery);

/**
 * Additional styles for notifications (injected via JS)
 */
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .wecp-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 999999;
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.3s ease;
            max-width: 400px;
        }

        .wecp-notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .wecp-notification-success {
            background: #10b981;
            color: #ffffff;
        }

        .wecp-notification-error {
            background: #ef4444;
            color: #ffffff;
        }

        .wecp-notification-info {
            background: #3b82f6;
            color: #ffffff;
        }
    `;
    document.head.appendChild(style);
})();
