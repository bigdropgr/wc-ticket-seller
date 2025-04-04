/**
 * JavaScript for the tickets admin page
 *
 * Handles all interactive functionality for the tickets management screen
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initTicketsPage();
    });

    /**
     * Initialize tickets page functionality
     */
    function initTicketsPage() {
        initFilterControls();
        initTicketActions();
        initBulkActions();
        initPagination();
    }

    /**
     * Initialize filter controls
     */
    function initFilterControls() {
        // Handle filter form submission
        $('#ticket-filters-form').on('submit', function(e) {
            // Let the form submit normally
        });

        // Handle filter reset
        $('#reset-filters').on('click', function(e) {
            e.preventDefault();
            
            // Reset all form fields
            $('#ticket-filters-form').find('select, input[type="text"]').each(function() {
                $(this).val('');
            });
            
            // Submit the form
            $('#ticket-filters-form').submit();
        });
    }

    /**
     * Initialize ticket actions
     */
    function initTicketActions() {
        // Handle check-in action
        $('.wc-ticket-seller-ticket-action.check-in').on('click', function(e) {
            e.preventDefault();
            
            const ticketId = $(this).data('ticket-id');
            
            // Confirm check-in
            if (confirm(wc_ticket_seller_admin.i18n.confirm_checkin)) {
                checkInTicket(ticketId);
            }
        });
        
        // Handle cancel action
        $('.wc-ticket-seller-ticket-action.cancel').on('click', function(e) {
            e.preventDefault();
            
            const ticketId = $(this).data('ticket-id');
            
            // Confirm cancellation
            if (confirm(wc_ticket_seller_tickets.confirm_cancel)) {
                cancelTicket(ticketId);
            }
        });
    }

    /**
     * Initialize bulk actions
     */
    function initBulkActions() {
        // Handle select all checkbox
        $('#select-all-tickets').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.ticket-checkbox').prop('checked', isChecked);
        });
        
        // Handle bulk action application
        $('#apply-bulk-action').on('click', function(e) {
            e.preventDefault();
            
            const action = $('#bulk-action').val();
            
            if (!action) {
                return;
            }
            
            // Get selected ticket IDs
            const ticketIds = [];
            $('.ticket-checkbox:checked').each(function() {
                ticketIds.push($(this).val());
            });
            
            if (ticketIds.length === 0) {
                alert(wc_ticket_seller_admin.i18n.no_tickets_selected);
                return;
            }
            
            // Process bulk action
            if (action === 'check-in') {
                if (confirm(wc_ticket_seller_admin.i18n.confirm_bulk_checkin)) {
                    bulkCheckInTickets(ticketIds);
                }
            } else if (action === 'cancel') {
                if (confirm(wc_ticket_seller_admin.i18n.confirm_bulk_cancel)) {
                    bulkCancelTickets(ticketIds);
                }
            } else if (action === 'export-csv') {
                exportTickets(ticketIds, 'csv');
            } else if (action === 'export-excel') {
                exportTickets(ticketIds, 'excel');
            }
        });
    }

    /**
     * Initialize pagination
     */
    function initPagination() {
        $('.wc-ticket-seller-pagination-link').on('click', function(e) {
            // This is just a regular link, no need for special handling
        });
    }

    /**
     * Check in a ticket
     *
     * @param {number} ticketId The ticket ID
     */
    function checkInTicket(ticketId) {
        $.ajax({
            url: wc_ticket_seller_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_ticket_seller_check_in_ticket',
                ticket_id: ticketId,
                nonce: wc_ticket_seller_admin.nonce
            },
            beforeSend: function() {
                // Show loading state
            },
            success: function(response) {
                if (response.success) {
                    alert(wc_ticket_seller_tickets.check_in_success);
                    
                    // Reload page to show updated status
                    window.location.reload();
                } else {
                    alert(wc_ticket_seller_tickets.check_in_error + ' ' + response.data.message);
                }
            },
            error: function() {
                alert(wc_ticket_seller_admin.i18n.ajax_error);
            },
            complete: function() {
                // Hide loading state
            }
        });
    }

    /**
     * Cancel a ticket
     *
     * @param {number} ticketId The ticket ID
     */
    function cancelTicket(ticketId) {
        $.ajax({
            url: wc_ticket_seller_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_ticket_seller_cancel_ticket',
                ticket_id: ticketId,
                nonce: wc_ticket_seller_admin.nonce
            },
            beforeSend: function() {
                // Show loading state
            },
            success: function(response) {
                if (response.success) {
                    alert(wc_ticket_seller_tickets.cancel_success);
                    
                    // Reload page to show updated status
                    window.location.reload();
                } else {
                    alert(wc_ticket_seller_tickets.cancel_error + ' ' + response.data.message);
                }
            },
            error: function() {
                alert(wc_ticket_seller_admin.i18n.ajax_error);
            },
            complete: function() {
                // Hide loading state
            }
        });
    }

    /**
     * Bulk check in tickets
     *
     * @param {Array} ticketIds Array of ticket IDs
     */
    function bulkCheckInTickets(ticketIds) {
        $.ajax({
            url: wc_ticket_seller_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_ticket_seller_bulk_check_in_tickets',
                ticket_ids: ticketIds,
                nonce: wc_ticket_seller_admin.nonce
            },
            beforeSend: function() {
                // Show loading state
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    
                    // Reload page to show updated status
                    window.location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(wc_ticket_seller_admin.i18n.ajax_error);
            },
            complete: function() {
                // Hide loading state
            }
        });
    }

    /**
     * Bulk cancel tickets
     *
     * @param {Array} ticketIds Array of ticket IDs
     */
    function bulkCancelTickets(ticketIds) {
        $.ajax({
            url: wc_ticket_seller_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_ticket_seller_bulk_cancel_tickets',
                ticket_ids: ticketIds,
                nonce: wc_ticket_seller_admin.nonce
            },
            beforeSend: function() {
                // Show loading state
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    
                    // Reload page to show updated status
                    window.location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(wc_ticket_seller_admin.i18n.ajax_error);
            },
            complete: function() {
                // Hide loading state
            }
        });
    }

    /**
     * Export tickets
     *
     * @param {Array} ticketIds Array of ticket IDs
     * @param {string} format Export format (csv or excel)
     */
    function exportTickets(ticketIds, format) {
        // Create a form to submit the export request
        const form = $('<form></form>').attr({
            method: 'post',
            action: wc_ticket_seller_admin.ajax_url,
            target: '_blank'
        }).appendTo('body');
        
        // Add action
        $('<input>').attr({
            type: 'hidden',
            name: 'action',
            value: 'wc_ticket_seller_export_tickets'
        }).appendTo(form);
        
        // Add ticket IDs
        $.each(ticketIds, function(i, ticketId) {
            $('<input>').attr({
                type: 'hidden',
                name: 'ticket_ids[]',
                value: ticketId
            }).appendTo(form);
        });
        
        // Add format
        $('<input>').attr({
            type: 'hidden',
            name: 'format',
            value: format
        }).appendTo(form);
        
        // Add nonce
        $('<input>').attr({
            type: 'hidden',
            name: 'nonce',
            value: wc_ticket_seller_admin.nonce
        }).appendTo(form);
        
        // Submit form
        form.submit();
        
        // Remove form
        form.remove();
    }

})(jQuery);

jQuery(document).ready(function($) {
    // Check in ticket action
    $('.check-in-ticket').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php esc_html_e('Are you sure you want to check in this ticket?', 'wc-ticket-seller'); ?>')) {
            return;
        }
        
        var ticketId = $(this).data('ticket-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wc_ticket_seller_check_in_ticket',
                ticket_id: ticketId,
                nonce: '<?php echo wp_create_nonce('wc_ticket_seller_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-tickets&ticket_id=' . $ticket_id . '&message=check-in-success')); ?>';
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred. Please try again.', 'wc-ticket-seller'); ?>');
            }
        });
    });
    
    // Cancel ticket action
    $('.cancel-ticket').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php esc_html_e('Are you sure you want to cancel this ticket? This action cannot be undone.', 'wc-ticket-seller'); ?>')) {
            return;
        }
        
        var ticketId = $(this).data('ticket-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wc_ticket_seller_cancel_ticket',
                ticket_id: ticketId,
                nonce: '<?php echo wp_create_nonce('wc_ticket_seller_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-tickets&ticket_id=' . $ticket_id . '&message=cancel-success')); ?>';
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php esc_html_e('An error occurred. Please try again.', 'wc-ticket-seller'); ?>');
            }
        });
    });
});