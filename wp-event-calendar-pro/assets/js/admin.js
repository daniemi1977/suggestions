/**
 * WP Event Calendar Pro - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize color picker
        if ($.fn.wpColorPicker) {
            $('.wecp-color-picker').wpColorPicker();
        }

        // Toggle time fields based on all-day checkbox
        $('#wecp_all_day').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('#wecp_start_time, #wecp_end_time').prop('disabled', isChecked);
        });

        // Set end date to start date if not set
        $('#wecp_start_date').on('change', function() {
            const startDate = $(this).val();
            const endDate = $('#wecp_end_date').val();

            if (!endDate && startDate) {
                $('#wecp_end_date').val(startDate);
            }
        });

        // Booking toggle
        $('#wecp_enable_booking').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('#wecp_max_attendees, #wecp_ticket_price').prop('disabled', !isChecked);
        });

        // Map provider toggle - Show/hide API key fields
        $('#wecp_map_provider').on('change', function() {
            const selectedProvider = $(this).val();

            // Hide all API key rows
            $('.wecp-api-key-row').hide();

            // Show the selected provider's API key row if it needs one
            if (selectedProvider !== 'openstreetmap') {
                $('.wecp-api-key-row[data-provider="' + selectedProvider + '"]').show();
            }
        }).trigger('change'); // Trigger on page load
    });

})(jQuery);
