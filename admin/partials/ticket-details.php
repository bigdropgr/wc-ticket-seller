<?php
/**
 * Provide a admin area view for viewing a single ticket
 *
 * @link       https://bigdrop.gr
 * @since      1.0.0
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get ticket ID
$ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;

if (!$ticket_id) {
    wp_die(__('Invalid ticket ID.', 'wc-ticket-seller'));
}

// Get Ticket_Manager instance
$ticket_manager = new WC_Ticket_Seller\Modules\Tickets\Ticket_Manager();

// Get ticket data
$ticket = $ticket_manager->get_ticket($ticket_id);

if (!$ticket) {
    wp_die(__('Ticket not found.', 'wc-ticket-seller'));
}

// Get event data
$event = new WC_Ticket_Seller\Modules\Events\Event($ticket['event_id']);

// Get check-in status
$check_in_status = $ticket_manager->get_check_in_status($ticket_id);

// Get custom field values
$custom_fields = $ticket_manager->get_custom_field_values($ticket_id);

// Get related order if exists
$order = !empty($ticket['order_id']) ? wc_get_order($ticket['order_id']) : null;

// Generate QR code data
$qr_data = array(
    'ticket_id'   => $ticket_id,
    'ticket_code' => $ticket['ticket_code'],
    'event_id'    => $ticket['event_id'],
);

$qr_data = json_encode($qr_data);
$qr_data = base64_encode($qr_data);
$qr_id = 'qrcode-' . $ticket_id;

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Ticket Details', 'wc-ticket-seller'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-tickets')); ?>" class="page-title-action">
        <?php esc_html_e('Back to Tickets', 'wc-ticket-seller'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php
    // Show messages
    if (isset($_GET['message']) && $_GET['message'] === 'check-in-success') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Ticket checked in successfully.', 'wc-ticket-seller'); ?></p>
        </div>
        <?php
    } elseif (isset($_GET['message']) && $_GET['message'] === 'cancel-success') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Ticket cancelled successfully.', 'wc-ticket-seller'); ?></p>
        </div>
        <?php
    }
    ?>
    
    <div class="wc-ticket-seller-ticket-details">
        <div class="wc-ticket-seller-ticket-details-left">
            <!-- Ticket Information -->
            <div class="wc-ticket-seller-ticket-detail-section">
                <h3 class="wc-ticket-seller-ticket-detail-heading"><?php esc_html_e('Ticket Information', 'wc-ticket-seller'); ?></h3>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Ticket ID:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($ticket['ticket_id']); ?></div>
                </div>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Ticket Code:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($ticket['ticket_code']); ?></div>
                </div>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Status:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value">
                        <span class="wc-ticket-seller-status <?php echo esc_attr($ticket['ticket_status']); ?>">
                            <?php 
                            if ($ticket['ticket_status'] === 'pending') {
                                esc_html_e('Pending', 'wc-ticket-seller');
                            } elseif ($ticket['ticket_status'] === 'checked-in') {
                                esc_html_e('Checked In', 'wc-ticket-seller');
                            } elseif ($ticket['ticket_status'] === 'cancelled') {
                                esc_html_e('Cancelled', 'wc-ticket-seller');
                            } else {
                                echo esc_html($ticket['ticket_status']);
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Type:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($ticket['ticket_type']); ?></div>
                </div>
                
                <?php if (!empty($ticket['seat_id'])) : 
                    $seat = $ticket_manager->get_seat($ticket['seat_id']);
                    if ($seat) : ?>
                    <div class="wc-ticket-seller-ticket-detail-row">
                        <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Seat:', 'wc-ticket-seller'); ?></div>
                        <div class="wc-ticket-seller-ticket-detail-value">
                            <?php 
                            echo esc_html(
                                $seat['section_name'] . ', ' . 
                                __('Row', 'wc-ticket-seller') . ' ' . $seat['row_name'] . ', ' . 
                                __('Seat', 'wc-ticket-seller') . ' ' . $seat['seat_number']
                            ); 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Created At:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value">
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['created_at']))); ?>
                    </div>
                </div>
                
                <?php if ($ticket['checked_in_at']) : ?>
                    <div class="wc-ticket-seller-ticket-detail-row">
                        <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Checked In At:', 'wc-ticket-seller'); ?></div>
                        <div class="wc-ticket-seller-ticket-detail-value">
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ticket['checked_in_at']))); ?>
                        </div>
                    </div>
                    
                    <?php if ($ticket['checked_in_by']) : 
                        $user = get_userdata($ticket['checked_in_by']);
                        if ($user) :
                        ?>
                            <div class="wc-ticket-seller-ticket-detail-row">
                                <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Checked In By:', 'wc-ticket-seller'); ?></div>
                                <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($user->display_name); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!empty($check_in_status)) : ?>
                    <?php if (!empty($check_in_status['station_id'])) : ?>
                        <div class="wc-ticket-seller-ticket-detail-row">
                            <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Station ID:', 'wc-ticket-seller'); ?></div>
                            <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($check_in_status['station_id']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($check_in_status['location'])) : ?>
                        <div class="wc-ticket-seller-ticket-detail-row">
                            <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Location:', 'wc-ticket-seller'); ?></div>
                            <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($check_in_status['location']); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($check_in_status['notes'])) : ?>
                        <div class="wc-ticket-seller-ticket-detail-row">
                            <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Check-in Notes:', 'wc-ticket-seller'); ?></div>
                            <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($check_in_status['notes']); ?></div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Event Information -->
            <div class="wc-ticket-seller-ticket-detail-section">
                <h3 class="wc-ticket-seller-ticket-detail-heading"><?php esc_html_e('Event Information', 'wc-ticket-seller'); ?></h3>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Event:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-events&action=edit&event_id=' . $event->get_id())); ?>">
                            <?php echo esc_html($event->get_name()); ?>
                        </a>
                    </div>
                </div>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Date:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($event->get_start('F j, Y')); ?></div>
                </div>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Time:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value">
                        <?php echo esc_html($event->get_start('g:i a') . ' - ' . $event->get_end('g:i a')); ?>
                    </div>
                </div>
                
                <?php if ($event->get_venue_name()) : ?>
                    <div class="wc-ticket-seller-ticket-detail-row">
                        <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Venue:', 'wc-ticket-seller'); ?></div>
                        <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($event->get_venue_name()); ?></div>
                    </div>
                    
                    <?php if ($event->get_venue_full_address()) : ?>
                        <div class="wc-ticket-seller-ticket-detail-row">
                            <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Address:', 'wc-ticket-seller'); ?></div>
                            <div class="wc-ticket-seller-ticket-detail-value"><?php echo esc_html($event->get_venue_full_address()); ?></div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Customer Information -->
            <div class="wc-ticket-seller-ticket-detail-section">
                <h3 class="wc-ticket-seller-ticket-detail-heading"><?php esc_html_e('Customer Information', 'wc-ticket-seller'); ?></h3>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Attendee:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value">
                        <?php echo esc_html($ticket['first_name'] . ' ' . $ticket['last_name']); ?>
                    </div>
                </div>
                
                <div class="wc-ticket-seller-ticket-detail-row">
                    <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Email:', 'wc-ticket-seller'); ?></div>
                    <div class="wc-ticket-seller-ticket-detail-value">
                        <a href="mailto:<?php echo esc_attr($ticket['customer_email']); ?>">
                            <?php echo esc_html($ticket['customer_email']); ?>
                        </a>
                    </div>
                </div>
                
                <?php if ($ticket['customer_id']) : 
                    $customer = get_userdata($ticket['customer_id']);
                    if ($customer) :
                    ?>
                        <div class="wc-ticket-seller-ticket-detail-row">
                            <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Customer Account:', 'wc-ticket-seller'); ?></div>
                            <div class="wc-ticket-seller-ticket-detail-value">
                                <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $ticket['customer_id'])); ?>">
                                    <?php echo esc_html($customer->display_name); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($order) : ?>
                    <div class="wc-ticket-seller-ticket-detail-row">
                        <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Order:', 'wc-ticket-seller'); ?></div>
                        <div class="wc-ticket-seller-ticket-detail-value">
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">
                                #<?php echo esc_html($order->get_order_number()); ?>
                            </a>
                            (<?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>)
                        </div>
                    </div>
                    
                    <div class="wc-ticket-seller-ticket-detail-row">
                        <div class="wc-ticket-seller-ticket-detail-label"><?php esc_html_e('Order Date:', 'wc-ticket-seller'); ?></div>
                        <div class="wc-ticket-seller-ticket-detail-value">
                            <?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format') . ' ' . get_option('time_format'))); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Custom Fields -->
            <?php if (!empty($custom_fields)) : ?>
                <div class="wc-ticket-seller-ticket-detail-section">
                    <h3 class="wc-ticket-seller-ticket-detail-heading"><?php esc_html_e('Additional Information', 'wc-ticket-seller'); ?></h3>
                    
                    <?php foreach ($custom_fields as $field) : ?>
                        <div class="wc-ticket-seller-ticket-detail-row">
                            <div class="wc-ticket-seller-ticket-detail-label"><?php echo esc_html($field['field_label']); ?>:</div>
                            <div class="wc-ticket-seller-ticket-detail-value">
                                <?php 
                                if (is_array($field['field_value'])) {
                                    echo esc_html(implode(', ', $field['field_value']));
                                } else {
                                    echo esc_html($field['field_value']); 
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="wc-ticket-seller-ticket-details-right">
            <!-- QR Code -->
            <div class="wc-ticket-seller-ticket-detail-section">
                <h3 class="wc-ticket-seller-ticket-detail-heading"><?php esc_html_e('Ticket QR Code', 'wc-ticket-seller'); ?></h3>
                
                <div class="wc-ticket-seller-ticket-qr">
                    <div id="<?php echo esc_attr($qr_id); ?>"></div>
                    <script>
                        (function() {
                            // Generate QR code
                            var qrcode = new QRCode(document.getElementById("<?php echo esc_js($qr_id); ?>"), {
                                text: "<?php echo esc_js($qr_data); ?>",
                                width: 180,
                                height: 180,
                                colorDark: "#000000",
                                colorLight: "#ffffff",
                                correctLevel: QRCode.CorrectLevel.H
                            });
                        })();
                    </script>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="wc-ticket-seller-ticket-detail-section">
                <h3 class="wc-ticket-seller-ticket-detail-heading"><?php esc_html_e('Actions', 'wc-ticket-seller'); ?></h3>
                
                <div class="wc-ticket-seller-ticket-actions-panel">
                    <?php if ($ticket['ticket_status'] === 'pending') : ?>
                        <a href="#" class="wc-ticket-seller-ticket-action-button success check-in-ticket" data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>">
                            <?php esc_html_e('Check In Ticket', 'wc-ticket-seller'); ?>
                        </a>
                        
                        <a href="#" class="wc-ticket-seller-ticket-action-button danger cancel-ticket" data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>">
                            <?php esc_html_e('Cancel Ticket', 'wc-ticket-seller'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(add_query_arg(array(
                        'action' => 'wc_ticket_seller_download_ticket',
                        'ticket_id' => $ticket['ticket_id'],
                        'format' => 'pdf',
                        'nonce' => wp_create_nonce('wc_ticket_seller_admin_nonce')
                    ), admin_url('admin-ajax.php'))); ?>" class="wc-ticket-seller-ticket-action-button primary" target="_blank">
                        <?php esc_html_e('Download PDF', 'wc-ticket-seller'); ?>
                    </a>
                    
                    <?php if (get_option('wc_ticket_seller_enable_passbook', 'no') === 'yes') : ?>
                        <a href="<?php echo esc_url(add_query_arg(array(
                            'action' => 'wc_ticket_seller_download_ticket',
                            'ticket_id' => $ticket['ticket_id'],
                            'format' => 'passbook',
                            'nonce' => wp_create_nonce('wc_ticket_seller_admin_nonce')
                        ), admin_url('admin-ajax.php'))); ?>" class="wc-ticket-seller-ticket-action-button" target="_blank">
                            <?php esc_html_e('Download Apple Wallet Pass', 'wc-ticket-seller'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($ticket['order_id'])) : ?>
                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $ticket['order_id'] . '&action=edit')); ?>" class="wc-ticket-seller-ticket-action-button">
                            <?php esc_html_e('View Order', 'wc-ticket-seller'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

