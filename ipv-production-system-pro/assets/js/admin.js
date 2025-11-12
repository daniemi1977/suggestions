/**
 * IPV Production System Pro - Admin JavaScript
 * Handles all interactive features and AJAX calls
 */

(function($) {
    'use strict';

    // Global vars
    const IPV = {
        nonce: ipvPro.nonce,
        ajaxUrl: ipvPro.ajaxUrl,
        strings: ipvPro.strings
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initAPITests();
        initImportForms();
        initVideoActions();
        initAutoRefresh();
    });

    /**
     * API Connection Tests
     */
    function initAPITests() {
        $('.ipv-test-api').on('click', function() {
            const $btn = $(this);
            const api = $btn.data('api');
            const $result = $(`.ipv-api-test-result[data-api="${api}"]`);

            // Disable button
            $btn.prop('disabled', true).text('Testing...');
            $result.removeClass('success error').hide();

            // Make AJAX call
            $.ajax({
                url: IPV.ajaxUrl,
                type: 'POST',
                data: {
                    action: `ipv_test_${api}_api`,
                    nonce: IPV.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').text('✅ ' + response.data.message).fadeIn();
                    } else {
                        $result.addClass('error').text('❌ ' + response.data.message).fadeIn();
                    }
                },
                error: function(xhr) {
                    $result.addClass('error').text('❌ Errore di connessione').fadeIn();
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Test Connessione');
                }
            });
        });
    }

    /**
     * Import Forms
     */
    function initImportForms() {
        // Single video import
        $('#ipv-btn-import-single').on('click', function() {
            const $btn = $(this);
            const $input = $('#ipv-single-url');
            const $result = $('#ipv-single-result');
            const videoUrl = $input.val().trim();

            if (!videoUrl) {
                showMessage($result, 'error', '⚠️ Inserisci un URL YouTube valido');
                return;
            }

            // Disable button
            $btn.prop('disabled', true).html('<span class="ipv-loading"></span> Aggiungendo...');
            $result.removeClass('success error').hide();

            // Make AJAX call
            $.ajax({
                url: IPV.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_import_single',
                    nonce: IPV.nonce,
                    video_url: videoUrl
                },
                success: function(response) {
                    if (response.success) {
                        showMessage($result, 'success', '✅ ' + response.data.message);
                        $input.val('');

                        // Refresh stats after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage($result, 'error', '❌ ' + response.data.message);
                    }
                },
                error: function(xhr) {
                    showMessage($result, 'error', '❌ Errore di connessione');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('➕ Aggiungi alla Coda');
                }
            });
        });

        // Enter key on single input
        $('#ipv-single-url').on('keypress', function(e) {
            if (e.which === 13) {
                $('#ipv-btn-import-single').click();
            }
        });

        // Bulk video import
        $('#ipv-btn-import-bulk').on('click', function() {
            const $btn = $(this);
            const $textarea = $('#ipv-bulk-urls');
            const $result = $('#ipv-bulk-result');
            const $progress = $('#ipv-bulk-progress');
            const urls = $textarea.val().trim();

            if (!urls) {
                showMessage($result, 'error', '⚠️ Inserisci almeno un URL');
                return;
            }

            const urlArray = urls.split('\n').filter(u => u.trim());

            if (urlArray.length === 0) {
                showMessage($result, 'error', '⚠️ Nessun URL valido trovato');
                return;
            }

            if (urlArray.length > 50) {
                showMessage($result, 'error', '⚠️ Massimo 50 video per importazione');
                return;
            }

            // Show progress
            $progress.show();
            $('.ipv-progress-total').text(urlArray.length);
            $('.ipv-progress-current').text('0');
            $('.ipv-progress-fill').css('width', '0%');

            // Disable button
            $btn.prop('disabled', true).html('<span class="ipv-loading"></span> Importando...');
            $result.removeClass('success error').hide();

            // Make AJAX call
            $.ajax({
                url: IPV.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_import_bulk',
                    nonce: IPV.nonce,
                    video_urls: urls
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;

                        // Update progress
                        $('.ipv-progress-fill').css('width', '100%');
                        $('.ipv-progress-current').text(urlArray.length);

                        // Show result
                        showMessage($result, 'success', '✅ ' + data.message);
                        $textarea.val('');

                        // Show details if there are errors
                        if (data.error_count > 0) {
                            let errorDetails = '<div style="margin-top: 10px; font-size: 12px;"><strong>Dettagli errori:</strong><ul>';
                            data.details.forEach(function(item) {
                                if (!item.success) {
                                    errorDetails += `<li>${item.url}: ${item.message}</li>`;
                                }
                            });
                            errorDetails += '</ul></div>';
                            $result.append(errorDetails);
                        }

                        // Refresh page after 3 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        showMessage($result, 'error', '❌ ' + response.data.message);
                    }
                },
                error: function(xhr) {
                    showMessage($result, 'error', '❌ Errore di connessione');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('📦 Importa Bulk');
                    setTimeout(function() {
                        $progress.fadeOut();
                    }, 2000);
                }
            });
        });
    }

    /**
     * Video Actions (Retry, Delete)
     */
    function initVideoActions() {
        // Retry action
        $(document).on('click', '.ipv-action-retry', function() {
            const $btn = $(this);
            const queueId = $btn.data('queue-id');

            if (!confirm(IPV.strings.confirm_retry)) {
                return;
            }

            $btn.prop('disabled', true).html('<span class="ipv-loading"></span>');

            $.ajax({
                url: IPV.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_retry_video',
                    nonce: IPV.nonce,
                    queue_id: queueId
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotice('error', response.data.message);
                        $btn.prop('disabled', false).html('🔄 Riprova');
                    }
                },
                error: function(xhr) {
                    showNotice('error', 'Errore di connessione');
                    $btn.prop('disabled', false).html('🔄 Riprova');
                }
            });
        });

        // Delete action
        $(document).on('click', '.ipv-action-delete', function() {
            const $btn = $(this);
            const queueId = $btn.data('queue-id');
            const postId = $btn.data('post-id');

            if (!confirm(IPV.strings.confirm_delete)) {
                return;
            }

            const deletePost = confirm('Vuoi anche eliminare il post WordPress?');

            $btn.prop('disabled', true);

            $.ajax({
                url: IPV.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ipv_delete_video',
                    nonce: IPV.nonce,
                    queue_id: queueId,
                    post_id: postId,
                    delete_post: deletePost
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                        $btn.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        showNotice('error', response.data.message);
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    showNotice('error', 'Errore di connessione');
                    $btn.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Auto-refresh for processing items
     */
    function initAutoRefresh() {
        // Only on dashboard and video manager pages
        if ($('.ipv-pro-dashboard, .ipv-pro-video-manager').length === 0) {
            return;
        }

        // Check for processing items every 10 seconds
        setInterval(function() {
            checkProcessingStatus();
        }, 10000);
    }

    /**
     * Check processing status via AJAX
     */
    function checkProcessingStatus() {
        $.ajax({
            url: IPV.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ipv_get_queue_status',
                nonce: IPV.nonce
            },
            success: function(response) {
                if (response.success && response.data.count > 0) {
                    // There are items being processed - show indicator
                    showProcessingIndicator(response.data.processing_items);
                }
            }
        });
    }

    /**
     * Show processing indicator
     */
    function showProcessingIndicator(items) {
        let $indicator = $('.ipv-processing-indicator');

        if ($indicator.length === 0) {
            $indicator = $('<div class="ipv-processing-indicator"></div>');
            $indicator.css({
                position: 'fixed',
                bottom: '20px',
                right: '20px',
                background: '#2271b1',
                color: '#fff',
                padding: '12px 20px',
                borderRadius: '6px',
                boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
                zIndex: 9999,
                fontSize: '13px'
            });
            $('body').append($indicator);
        }

        $indicator.html(`
            <strong>⏳ Elaborazione in corso</strong><br>
            <small>${items.length} video in processing</small>
        `);
    }

    /**
     * Helper: Show message in result div
     */
    function showMessage($element, type, message) {
        $element
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .fadeIn();
    }

    /**
     * Helper: Show WordPress notice
     */
    function showNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $(`
            <div class="notice ${noticeClass} is-dismissible">
                <p>${message}</p>
            </div>
        `);

        $('.wrap h1').after($notice);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Tooltips for errors
     */
    $(document).on('mouseenter', '.ipv-error-tooltip', function() {
        const $tooltip = $(this);
        const title = $tooltip.attr('title');

        if (!title) return;

        const $popup = $('<div class="ipv-tooltip-popup"></div>').text(title);
        $popup.css({
            position: 'absolute',
            background: '#1e1e1e',
            color: '#fff',
            padding: '8px 12px',
            borderRadius: '4px',
            fontSize: '12px',
            maxWidth: '300px',
            zIndex: 10000,
            boxShadow: '0 2px 8px rgba(0,0,0,0.3)'
        });

        const offset = $tooltip.offset();
        $popup.css({
            top: offset.top - 40,
            left: offset.left - 100
        });

        $('body').append($popup);

        $tooltip.data('tooltip', $popup);
    });

    $(document).on('mouseleave', '.ipv-error-tooltip', function() {
        const $tooltip = $(this);
        const $popup = $tooltip.data('tooltip');

        if ($popup) {
            $popup.remove();
            $tooltip.removeData('tooltip');
        }
    });

})(jQuery);
