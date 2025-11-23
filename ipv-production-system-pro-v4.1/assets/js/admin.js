/**
 * IPV Production System Pro v4.0.0
 * Admin JavaScript - Modern Interactions
 */

(function($) {
    'use strict';

    const IPVProd = {
        /**
         * Inizializzazione
         */
        init: function() {
            this.bindEvents();
            this.initBootstrap();
            this.startAutoRefresh();
        },

        /**
         * Bind degli eventi
         */
        bindEvents: function() {
            // Processo manuale della coda
            $('#ipv-manual-process').on('click', this.processQueue.bind(this));

            // Auto-submit form dopo selezione
            $('.ipv-auto-submit').on('change', function() {
                $(this).closest('form').submit();
            });

            // Conferma eliminazione
            $('.ipv-confirm-delete').on('click', function(e) {
                if (!confirm('Sei sicuro di voler eliminare questo elemento?')) {
                    e.preventDefault();
                    return false;
                }
            });

            // Copy to clipboard
            $('.ipv-copy-btn').on('click', this.copyToClipboard.bind(this));

            // Smooth scroll
            $('a[href^="#"]').on('click', this.smoothScroll);
        },

        /**
         * Inizializza componenti Bootstrap
         */
        initBootstrap: function() {
            // Inizializza tutti i toast
            const toastElList = [].slice.call(document.querySelectorAll('.toast'));
            const toastList = toastElList.map(function(toastEl) {
                return new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 5000
                });
            });

            // Inizializza tooltip
            const tooltipTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="tooltip"]')
            );
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Inizializza popover
            const popoverTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="popover"]')
            );
            popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        },

        /**
         * Processa la coda manualmente
         */
        processQueue: function(e) {
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            const originalHTML = $btn.html();
            
            // Disabilita il pulsante e mostra loading
            $btn.prop('disabled', true);
            $btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Processando...');

            $.ajax({
                url: ipvProdAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ipv_prod_process_queue',
                    nonce: ipvProdAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        IPVProd.showToast('Successo', response.data.message, 'success');
                        IPVProd.updateStats(response.data.stats);
                    } else {
                        IPVProd.showToast('Errore', response.data.message, 'danger');
                    }
                },
                error: function() {
                    IPVProd.showToast('Errore', 'Errore di comunicazione con il server.', 'danger');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btn.html(originalHTML);
                }
            });
        },

        /**
         * Aggiorna le statistiche nella dashboard
         */
        updateStats: function(stats) {
            if (!stats) return;

            // Anima il cambio dei valori
            this.animateValue('#stat-pending', stats.pending);
            this.animateValue('#stat-processing', stats.processing);
            this.animateValue('#stat-done', stats.done);
            this.animateValue('#stat-error', stats.error);

            // Aggiorna il grafico se esiste
            if (window.ipvStatsChart) {
                window.ipvStatsChart.data.datasets[0].data = [
                    stats.pending,
                    stats.processing,
                    stats.done,
                    stats.error
                ];
                window.ipvStatsChart.update('active');
            }
        },

        /**
         * Anima il cambio di un valore numerico
         */
        animateValue: function(selector, newValue) {
            const $el = $(selector);
            const currentValue = parseInt($el.text()) || 0;
            
            if (currentValue === newValue) return;

            $({ value: currentValue }).animate(
                { value: newValue },
                {
                    duration: 600,
                    easing: 'swing',
                    step: function() {
                        $el.text(Math.floor(this.value));
                    },
                    complete: function() {
                        $el.text(newValue);
                        $el.closest('.ipv-stat-card').addClass('ipv-pulse');
                        setTimeout(function() {
                            $el.closest('.ipv-stat-card').removeClass('ipv-pulse');
                        }, 2000);
                    }
                }
            );
        },

        /**
         * Mostra un toast notification
         */
        showToast: function(title, message, type = 'info') {
            const $toast = $('#ipv-toast');
            const iconMap = {
                success: 'check-circle-fill text-success',
                danger: 'exclamation-triangle-fill text-danger',
                warning: 'exclamation-circle-fill text-warning',
                info: 'info-circle-fill text-primary'
            };

            $toast.find('.toast-header i').attr('class', 'bi ' + iconMap[type] + ' me-2');
            $toast.find('.toast-header strong').text(title);
            $toast.find('.toast-body').text(message);

            const toast = new bootstrap.Toast($toast[0]);
            toast.show();
        },

        /**
         * Auto-refresh delle statistiche
         */
        startAutoRefresh: function() {
            // Refresh ogni 30 secondi solo se nella dashboard
            if ($('#ipv-stats-cards').length === 0) return;

            setInterval(function() {
                $.ajax({
                    url: ipvProdAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_prod_get_stats',
                        nonce: ipvProdAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            IPVProd.updateStats(response.data);
                        }
                    }
                });
            }, 30000); // 30 secondi
        },

        /**
         * Copy to clipboard
         */
        copyToClipboard: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const text = $btn.data('clipboard-text') || $btn.text();

            // Crea un elemento temporaneo
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();

            // Feedback visivo
            const originalHTML = $btn.html();
            $btn.html('<i class="bi bi-check2 me-1"></i>Copiato!');
            setTimeout(function() {
                $btn.html(originalHTML);
            }, 2000);

            this.showToast('Copiato', 'Testo copiato negli appunti!', 'success');
        },

        /**
         * Smooth scroll
         */
        smoothScroll: function(e) {
            const hash = this.hash;
            if (hash && $(hash).length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $(hash).offset().top - 20
                }, 600);
            }
        },

        /**
         * Utility: Formatta numero
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        },

        /**
         * Utility: Formatta durata
         */
        formatDuration: function(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            } else if (minutes > 0) {
                return `${minutes}m ${secs}s`;
            } else {
                return `${secs}s`;
            }
        }
    };

    /**
     * Chart.js Configuration
     */
    const IPVCharts = {
        /**
         * Configura Chart.js globale
         */
        setupDefaults: function() {
            if (typeof Chart === 'undefined') return;

            Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            Chart.defaults.color = '#718096';
            Chart.defaults.plugins.legend.display = true;
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(45, 55, 72, 0.95)';
            Chart.defaults.plugins.tooltip.cornerRadius = 8;
            Chart.defaults.plugins.tooltip.padding = 12;
        },

        /**
         * Crea grafico a torta
         */
        createDoughnutChart: function(canvasId, data, labels, colors) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 13,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    label += ` (${percentage}%)`;
                                    return label;
                                }
                            }
                        }
                    },
                    cutout: '60%',
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        },

        /**
         * Crea grafico a linee
         */
        createLineChart: function(canvasId, data, labels) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Video processati',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    };

    /**
     * Form Validation Enhancement
     */
    const IPVValidation = {
        init: function() {
            // Aggiungi validazione real-time
            $('.ipv-validate').on('blur', this.validateField);
            
            // Aggiungi validazione al submit
            $('form.ipv-form').on('submit', this.validateForm);
        },

        validateField: function(e) {
            const $field = $(e.currentTarget);
            const value = $field.val();
            const type = $field.data('validate-type');

            let isValid = true;
            let message = '';

            switch (type) {
                case 'url':
                    isValid = /^https?:\/\/.+/.test(value);
                    message = 'Inserisci un URL valido';
                    break;
                case 'email':
                    isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    message = 'Inserisci un email valida';
                    break;
                case 'required':
                    isValid = value.trim() !== '';
                    message = 'Questo campo è obbligatorio';
                    break;
            }

            IPVValidation.showFieldFeedback($field, isValid, message);
            return isValid;
        },

        showFieldFeedback: function($field, isValid, message) {
            $field.removeClass('is-valid is-invalid');
            $field.siblings('.invalid-feedback').remove();

            if (!isValid) {
                $field.addClass('is-invalid');
                $field.after(`<div class="invalid-feedback d-block">${message}</div>`);
            } else if ($field.val()) {
                $field.addClass('is-valid');
            }
        },

        validateForm: function(e) {
            let isValid = true;
            $(this).find('.ipv-validate').each(function() {
                if (!IPVValidation.validateField({ currentTarget: this })) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                IPVProd.showToast('Errore', 'Correggi gli errori nel form', 'danger');
            }

            return isValid;
        }
    };

    /**
     * Inizializzazione al caricamento del documento
     */
    $(document).ready(function() {
        IPVProd.init();
        IPVCharts.setupDefaults();
        IPVValidation.init();

        // Log per debug
        if (window.console && window.console.log) {
            console.log('%cIPV Production System Pro v4.0.0', 'color: #667eea; font-weight: bold; font-size: 16px;');
            console.log('%cUI caricata con successo ✓', 'color: #48bb78; font-weight: bold;');
        }
    });

    // Esporta funzioni globali se necessario
    window.IPVProd = IPVProd;
    window.IPVCharts = IPVCharts;

})(jQuery);
