/**
 * IPV Production System Pro - Frontend JavaScript
 * Theme Integration Scripts
 * Version: 2.0.0
 */

(function($) {
    'use strict';

    /**
     * IPV Frontend Handler
     */
    const IPVFrontend = {

        /**
         * Initialize
         */
        init: function() {
            this.videoPlayer();
            this.lazyLoadVideos();
            this.videoTracking();
            this.enhanceAccessibility();
            console.log('IPV Frontend initialized');
        },

        /**
         * Enhanced Video Player
         */
        videoPlayer: function() {
            const $videoContainers = $('.ipv-video-container');

            if ($videoContainers.length === 0) {
                return;
            }

            $videoContainers.each(function() {
                const $container = $(this);
                const $iframe = $container.find('iframe');

                if ($iframe.length === 0) {
                    return;
                }

                // Add loading state
                $container.addClass('ipv-loading');

                // Remove loading state when iframe loads
                $iframe.on('load', function() {
                    $container.removeClass('ipv-loading');
                    $container.addClass('ipv-loaded');
                });

                // Error handling
                $iframe.on('error', function() {
                    $container.removeClass('ipv-loading');
                    $container.addClass('ipv-error');
                    $container.append('<div class="ipv-error-message">Errore nel caricamento del video</div>');
                });
            });
        },

        /**
         * Lazy Load Videos
         */
        lazyLoadVideos: function() {
            if (!('IntersectionObserver' in window)) {
                return; // No support for IntersectionObserver
            }

            const videoObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const $video = $(entry.target);
                        const $iframe = $video.find('iframe');

                        if ($iframe.length > 0 && $iframe.data('lazy-src')) {
                            $iframe.attr('src', $iframe.data('lazy-src'));
                            $iframe.removeAttr('data-lazy-src');
                        }

                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            $('.ipv-video-container[data-lazy="true"]').each(function() {
                videoObserver.observe(this);
            });
        },

        /**
         * Video Tracking & Analytics
         */
        videoTracking: function() {
            const $videoCards = $('.ipv-video-card');

            $videoCards.on('click', function(e) {
                const $card = $(this);
                const videoTitle = $card.find('.ipv-video-title').text().trim();
                const videoUrl = $card.find('a').attr('href');

                // Send to analytics (if available)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'video_click', {
                        'event_category': 'IPV Video',
                        'event_label': videoTitle,
                        'value': videoUrl
                    });
                }

                // Custom event for developers
                $(document).trigger('ipv:video:click', {
                    title: videoTitle,
                    url: videoUrl
                });
            });

            // Track video player interactions
            $(window).on('message', function(e) {
                const event = e.originalEvent;

                try {
                    const data = JSON.parse(event.data);

                    // YouTube player events
                    if (data.event === 'onStateChange') {
                        let eventName = '';

                        switch (data.info) {
                            case 1: // Playing
                                eventName = 'video_play';
                                break;
                            case 2: // Paused
                                eventName = 'video_pause';
                                break;
                            case 0: // Ended
                                eventName = 'video_complete';
                                break;
                        }

                        if (eventName && typeof gtag !== 'undefined') {
                            gtag('event', eventName, {
                                'event_category': 'IPV Video Player'
                            });
                        }
                    }
                } catch (err) {
                    // Not a JSON message, ignore
                }
            });
        },

        /**
         * Enhance Accessibility
         */
        enhanceAccessibility: function() {
            // Add ARIA labels to video cards
            $('.ipv-video-card').each(function() {
                const $card = $(this);
                const title = $card.find('.ipv-video-title').text().trim();

                if (title) {
                    $card.attr('aria-label', 'Video: ' + title);
                }
            });

            // Add keyboard navigation
            $('.ipv-video-card').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).find('a').first()[0].click();
                }
            });

            // Make cards focusable
            $('.ipv-video-card').attr('tabindex', '0');

            // Skip to video content link
            const $videoContainer = $('.ipv-video-container').first();
            if ($videoContainer.length > 0) {
                $videoContainer.attr('id', 'ipv-main-video');
                $('body').prepend('<a href="#ipv-main-video" class="ipv-skip-link">Salta al video</a>');
            }
        },

        /**
         * Format Numbers
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        /**
         * Update Video Stats (Dynamic)
         */
        updateVideoStats: function() {
            const $stats = $('.ipv-video-stats-box .ipv-stat-value');

            $stats.each(function() {
                const $stat = $(this);
                const value = parseInt($stat.text().replace(/[^0-9]/g, ''), 10);

                if (!isNaN(value)) {
                    $stat.text(IPVFrontend.formatNumber(value));
                }
            });
        }
    };

    /**
     * Video Grid Filter (Optional Feature)
     */
    const IPVGridFilter = {
        init: function() {
            this.bindFilters();
        },

        bindFilters: function() {
            const $filters = $('.ipv-grid-filters button');

            if ($filters.length === 0) {
                return;
            }

            $filters.on('click', function(e) {
                e.preventDefault();

                const $btn = $(this);
                const filter = $btn.data('filter');

                // Update active state
                $filters.removeClass('active');
                $btn.addClass('active');

                // Filter videos
                IPVGridFilter.filterVideos(filter);
            });
        },

        filterVideos: function(category) {
            const $cards = $('.ipv-video-card');

            if (category === 'all') {
                $cards.fadeIn(300);
                return;
            }

            $cards.each(function() {
                const $card = $(this);
                const cardCategory = $card.data('category');

                if (cardCategory === category) {
                    $card.fadeIn(300);
                } else {
                    $card.fadeOut(300);
                }
            });
        }
    };

    /**
     * Infinite Scroll for Video Archive (Optional)
     */
    const IPVInfiniteScroll = {
        loading: false,
        page: 2,
        hasMore: true,

        init: function() {
            if ($('.ipv-video-grid').length === 0) {
                return;
            }

            this.bindScroll();
        },

        bindScroll: function() {
            $(window).on('scroll', function() {
                if (IPVInfiniteScroll.loading || !IPVInfiniteScroll.hasMore) {
                    return;
                }

                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                const documentHeight = $(document).height();

                // Load more when 300px from bottom
                if (scrollTop + windowHeight > documentHeight - 300) {
                    IPVInfiniteScroll.loadMore();
                }
            });
        },

        loadMore: function() {
            this.loading = true;

            const $grid = $('.ipv-video-grid');
            const $loader = $('<div class="ipv-loading-spinner">Caricamento...</div>');

            $grid.after($loader);

            // AJAX request (customize with your endpoint)
            $.ajax({
                url: '/wp-json/ipv/v1/videos',
                method: 'GET',
                data: {
                    page: this.page,
                    per_page: 12
                },
                success: function(response) {
                    if (response.videos && response.videos.length > 0) {
                        $grid.append(response.videos);
                        IPVInfiniteScroll.page++;
                    } else {
                        IPVInfiniteScroll.hasMore = false;
                    }

                    $loader.remove();
                    IPVInfiniteScroll.loading = false;
                },
                error: function() {
                    $loader.remove();
                    IPVInfiniteScroll.loading = false;
                    IPVInfiniteScroll.hasMore = false;
                }
            });
        }
    };

    /**
     * Video Thumbnail Hover Effect
     */
    const IPVThumbnailHover = {
        init: function() {
            const $thumbs = $('.ipv-video-thumb');

            $thumbs.each(function() {
                const $thumb = $(this);
                const $img = $thumb.find('img');

                // Add hover overlay
                $thumb.append('<div class="ipv-play-overlay"><span class="ipv-play-icon">▶</span></div>');

                // Hover animation
                $thumb.on('mouseenter', function() {
                    $(this).find('.ipv-play-overlay').fadeIn(200);
                });

                $thumb.on('mouseleave', function() {
                    $(this).find('.ipv-play-overlay').fadeOut(200);
                });
            });

            // Add CSS for overlay
            if (!$('#ipv-dynamic-css').length) {
                $('head').append(`
                    <style id="ipv-dynamic-css">
                        .ipv-play-overlay {
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: rgba(0, 0, 0, 0.6);
                            display: none;
                            align-items: center;
                            justify-content: center;
                            z-index: 10;
                        }
                        .ipv-play-icon {
                            font-size: 48px;
                            color: #fff;
                            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
                        }
                        .ipv-skip-link {
                            position: absolute;
                            left: -9999px;
                            z-index: 999;
                            padding: 10px 20px;
                            background: #000;
                            color: #fff;
                            text-decoration: none;
                        }
                        .ipv-skip-link:focus {
                            left: 10px;
                            top: 10px;
                        }
                    </style>
                `);
            }
        }
    };

    /**
     * Document Ready
     */
    $(document).ready(function() {
        IPVFrontend.init();
        IPVGridFilter.init();
        IPVThumbnailHover.init();
        // IPVInfiniteScroll.init(); // Uncomment to enable infinite scroll

        // Custom event hooks for developers
        $(document).trigger('ipv:frontend:ready');
    });

    /**
     * Window Load
     */
    $(window).on('load', function() {
        IPVFrontend.updateVideoStats();
        $(document).trigger('ipv:frontend:loaded');
    });

    /**
     * Export to global scope for external access
     */
    window.IPVFrontend = IPVFrontend;
    window.IPVGridFilter = IPVGridFilter;
    window.IPVInfiniteScroll = IPVInfiniteScroll;

})(jQuery);
