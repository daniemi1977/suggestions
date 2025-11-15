/**
 * Admin Panel JavaScript for Claude WooCommerce AI RAG
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Test OpenAI API
        $('#test-openai').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.text();

            $btn.prop('disabled', true).text('Testing...');

            $.post(cwau_admin.ajax_url, {
                action: 'cwau_test_openai',
                nonce: cwau_admin.nonce
            }, function(response) {
                if (response.success) {
                    alert('✓ ' + response.data.message + '\nModel: ' + response.data.model);
                } else {
                    alert('✗ ' + response.data.message);
                }
            }).fail(function() {
                alert('✗ Errore di rete durante il test');
            }).always(function() {
                $btn.prop('disabled', false).text(originalText);
            });
        });

        // Test Anthropic API
        $('#test-anthropic').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.text();

            $btn.prop('disabled', true).text('Testing...');

            $.post(cwau_admin.ajax_url, {
                action: 'cwau_test_anthropic',
                nonce: cwau_admin.nonce
            }, function(response) {
                if (response.success) {
                    alert('✓ ' + response.data.message + '\nModel: ' + response.data.model);
                } else {
                    alert('✗ ' + response.data.message);
                }
            }).fail(function() {
                alert('✗ Errore di rete durante il test');
            }).always(function() {
                $btn.prop('disabled', false).text(originalText);
            });
        });

        // RAG Indexing
        let indexingOffset = 0;
        let isIndexing = false;

        $('#cwau-index-products').on('click', function() {
            if (isIndexing) return;

            indexProducts();
        });

        function indexProducts() {
            isIndexing = true;
            const $btn = $('#cwau-index-products');
            const $status = $('#cwau-rag-status');
            const $progress = $('#cwau-rag-progress');

            $btn.prop('disabled', true);
            $progress.show();
            $status.html('<div class="cwau-status-area info">Indicizzazione in corso...</div>');

            $.post(cwau_admin.ajax_url, {
                action: 'cwau_rag_index_batch',
                nonce: cwau_admin.nonce,
                offset: indexingOffset
            }, function(response) {
                if (response.success) {
                    const data = response.data;
                    indexingOffset = data.next_offset;

                    // Update progress
                    const percentage = (data.indexed / data.total) * 100;
                    $progress.find('.progress-fill').css('width', percentage + '%');
                    $progress.find('.progress-text').text(Math.round(percentage) + '%');

                    $status.html('<div class="cwau-status-area success">' + data.message + '</div>');

                    // Continue if there are more products
                    if (data.has_more) {
                        setTimeout(indexProducts, 1000); // Delay to avoid API rate limits
                    } else {
                        isIndexing = false;
                        indexingOffset = 0;
                        $btn.prop('disabled', false);
                        $status.html('<div class="cwau-status-area success">✓ Indicizzazione completata!</div>');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    isIndexing = false;
                    $btn.prop('disabled', false);
                    $progress.hide();
                    $status.html('<div class="cwau-status-area error">✗ ' + response.data.message + '</div>');
                }
            }).fail(function() {
                isIndexing = false;
                $btn.prop('disabled', false);
                $progress.hide();
                $status.html('<div class="cwau-status-area error">✗ Errore di rete durante l\'indicizzazione</div>');
            });
        }

        // Reindex all
        $('#cwau-reindex-all').on('click', function() {
            if (!confirm('Sei sicuro di voler reindicizzare tutti i prodotti? Questo potrebbe richiedere del tempo.')) {
                return;
            }

            if (isIndexing) return;

            indexingOffset = 0;
            indexProducts();
        });

        // Clear RAG index
        $('#cwau-clear-index').on('click', function() {
            if (!confirm('Sei sicuro di voler svuotare l\'indice RAG? Dovrai reindicizzare tutti i prodotti.')) {
                return;
            }

            const $btn = $(this);
            const $status = $('#cwau-rag-status');

            $btn.prop('disabled', true);
            $status.html('<div class="cwau-status-area info">Svuotamento in corso...</div>');

            $.post(cwau_admin.ajax_url, {
                action: 'cwau_rag_clear',
                nonce: cwau_admin.nonce
            }, function(response) {
                if (response.success) {
                    $status.html('<div class="cwau-status-area success">✓ ' + response.data.message + '</div>');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $status.html('<div class="cwau-status-area error">✗ ' + response.data.message + '</div>');
                    $btn.prop('disabled', false);
                }
            }).fail(function() {
                $status.html('<div class="cwau-status-area error">✗ Errore durante lo svuotamento</div>');
                $btn.prop('disabled', false);
            });
        });

        // Load analytics if on analytics page
        if ($('#cwau-analytics-dashboard').length) {
            loadAnalytics();
        }

        function loadAnalytics() {
            $.post(cwau_admin.ajax_url, {
                action: 'cwau_get_analytics',
                nonce: cwau_admin.nonce
            }, function(response) {
                if (response.success) {
                    const data = response.data;

                    $('#conversations-today').text(data.conversations_today);
                    $('#messages-total').text(data.messages_total);
                    $('#escalations-count').text(data.escalations);
                    $('#avg-sentiment').text(data.avg_sentiment);

                    // Draw chart
                    if (typeof Chart !== 'undefined') {
                        const ctx = document.getElementById('conversations-chart');
                        if (ctx) {
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: data.chart_labels,
                                    datasets: [{
                                        label: 'Conversazioni',
                                        data: data.chart_data,
                                        borderColor: '#2563eb',
                                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                        tension: 0.3,
                                        fill: true
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                precision: 0
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }
                }
            });
        }

        // View conversation modal
        $('.view-conversation').on('click', function() {
            const conversationId = $(this).data('id');

            $.post(cwau_admin.ajax_url, {
                action: 'cwau_get_conversation_messages',
                nonce: cwau_admin.nonce,
                conversation_id: conversationId
            }, function(response) {
                if (response.success) {
                    const messages = response.data.messages;
                    let html = '';

                    messages.forEach(function(msg) {
                        html += '<div class="message-item ' + msg.sender + '">';
                        html += '<div class="message-sender">' + msg.sender + '</div>';
                        html += '<div class="message-text">' + escapeHtml(msg.message) + '</div>';
                        html += '<div class="message-time">' + msg.created_at + '</div>';
                        html += '</div>';
                    });

                    $('#conversation-messages').html(html);
                    $('#conversation-modal').show();
                }
            });
        });

        // Close modal
        $('.cwau-modal-close').on('click', function() {
            $('.cwau-modal').hide();
        });

        $(window).on('click', function(e) {
            if ($(e.target).hasClass('cwau-modal')) {
                $('.cwau-modal').hide();
            }
        });

        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    });

})(jQuery);
