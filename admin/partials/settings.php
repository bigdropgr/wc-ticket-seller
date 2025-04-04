<?php
/**
 * Provide a admin area view for plugin settings
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current settings
$ticket_expiry = get_option('wc_ticket_seller_ticket_expiry', '2');
$enable_qr_codes = get_option('wc_ticket_seller_enable_qr_codes', 'yes');
$barcode_type = get_option('wc_ticket_seller_barcode_type', 'qrcode');
$send_tickets = get_option('wc_ticket_seller_send_tickets', 'completed');
$email_ticket_subject = get_option('wc_ticket_seller_email_ticket_subject', __('Your Tickets for {event_name}', 'wc-ticket-seller'));
$email_ticket_heading = get_option('wc_ticket_seller_email_ticket_heading', __('Your Tickets', 'wc-ticket-seller'));
$email_reminder_subject = get_option('wc_ticket_seller_email_reminder_subject', __('Upcoming Event: {event_name}', 'wc-ticket-seller'));
$email_reminder_heading = get_option('wc_ticket_seller_email_reminder_heading', __('Event Reminder', 'wc-ticket-seller'));
$reminder_days = get_option('wc_ticket_seller_reminder_days', '2');
$pdf_size = get_option('wc_ticket_seller_pdf_size', 'A4');
$pdf_orientation = get_option('wc_ticket_seller_pdf_orientation', 'portrait');
$ticket_terms = get_option('wc_ticket_seller_ticket_terms', '');
$enable_passbook = get_option('wc_ticket_seller_enable_passbook', 'no');
$delete_data = get_option('wc_ticket_seller_delete_data_on_uninstall', 'no');

// Check if settings were saved
$settings_saved = false;
if (isset($_POST['wc_ticket_seller_settings_nonce']) && wp_verify_nonce($_POST['wc_ticket_seller_settings_nonce'], 'wc_ticket_seller_save_settings')) {
    
    // Update settings
    update_option('wc_ticket_seller_ticket_expiry', sanitize_text_field($_POST['ticket_expiry']));
    update_option('wc_ticket_seller_enable_qr_codes', isset($_POST['enable_qr_codes']) ? 'yes' : 'no');
    update_option('wc_ticket_seller_barcode_type', sanitize_text_field($_POST['barcode_type']));
    update_option('wc_ticket_seller_send_tickets', sanitize_text_field($_POST['send_tickets']));
    update_option('wc_ticket_seller_email_ticket_subject', sanitize_text_field($_POST['email_ticket_subject']));
    update_option('wc_ticket_seller_email_ticket_heading', sanitize_text_field($_POST['email_ticket_heading']));
    update_option('wc_ticket_seller_email_reminder_subject', sanitize_text_field($_POST['email_reminder_subject']));
    update_option('wc_ticket_seller_email_reminder_heading', sanitize_text_field($_POST['email_reminder_heading']));
    update_option('wc_ticket_seller_reminder_days', sanitize_text_field($_POST['reminder_days']));
    update_option('wc_ticket_seller_pdf_size', sanitize_text_field($_POST['pdf_size']));
    update_option('wc_ticket_seller_pdf_orientation', sanitize_text_field($_POST['pdf_orientation']));
    update_option('wc_ticket_seller_ticket_terms', wp_kses_post($_POST['ticket_terms']));
    update_option('wc_ticket_seller_enable_passbook', isset($_POST['enable_passbook']) ? 'yes' : 'no');
    update_option('wc_ticket_seller_delete_data_on_uninstall', isset($_POST['delete_data']) ? 'yes' : 'no');
    
    $settings_saved = true;
    
    // Refresh settings after save
    $ticket_expiry = get_option('wc_ticket_seller_ticket_expiry', '2');
    $enable_qr_codes = get_option('wc_ticket_seller_enable_qr_codes', 'yes');
    $barcode_type = get_option('wc_ticket_seller_barcode_type', 'qrcode');
    $send_tickets = get_option('wc_ticket_seller_send_tickets', 'completed');
    $email_ticket_subject = get_option('wc_ticket_seller_email_ticket_subject', __('Your Tickets for {event_name}', 'wc-ticket-seller'));
    $email_ticket_heading = get_option('wc_ticket_seller_email_ticket_heading', __('Your Tickets', 'wc-ticket-seller'));
    $email_reminder_subject = get_option('wc_ticket_seller_email_reminder_subject', __('Upcoming Event: {event_name}', 'wc-ticket-seller'));
    $email_reminder_heading = get_option('wc_ticket_seller_email_reminder_heading', __('Event Reminder', 'wc-ticket-seller'));
    $reminder_days = get_option('wc_ticket_seller_reminder_days', '2');
    $pdf_size = get_option('wc_ticket_seller_pdf_size', 'A4');
    $pdf_orientation = get_option('wc_ticket_seller_pdf_orientation', 'portrait');
    $ticket_terms = get_option('wc_ticket_seller_ticket_terms', '');
    $enable_passbook = get_option('wc_ticket_seller_enable_passbook', 'no');
    $delete_data = get_option('wc_ticket_seller_delete_data_on_uninstall', 'no');
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <hr class="wp-header-end">
    
    <?php if ($settings_saved) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'wc-ticket-seller'); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="" class="wc-ticket-seller-admin-form">
        <?php wp_nonce_field('wc_ticket_seller_save_settings', 'wc_ticket_seller_settings_nonce'); ?>
        
        <div class="nav-tab-wrapper">
            <a href="#general-settings" class="nav-tab nav-tab-active"><?php esc_html_e('General', 'wc-ticket-seller'); ?></a>
            <a href="#email-settings" class="nav-tab"><?php esc_html_e('Emails', 'wc-ticket-seller'); ?></a>
            <a href="#pdf-settings" class="nav-tab"><?php esc_html_e('PDF & Tickets', 'wc-ticket-seller'); ?></a>
            <a href="#advanced-settings" class="nav-tab"><?php esc_html_e('Advanced', 'wc-ticket-seller'); ?></a>
        </div>
        
        <div class="tab-content">
            <!-- General Settings -->
            <div id="general-settings" class="tab-pane active">
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('General Settings', 'wc-ticket-seller'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ticket_expiry"><?php esc_html_e('Ticket Expiry', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="ticket_expiry" name="ticket_expiry" value="<?php echo esc_attr($ticket_expiry); ?>" min="0" step="1" class="small-text">
                                    <p class="description"><?php esc_html_e('Number of days after the event when tickets will expire. Set to 0 for never.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="enable_qr_codes"><?php esc_html_e('Enable QR Codes', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="enable_qr_codes" name="enable_qr_codes" <?php checked($enable_qr_codes, 'yes'); ?>>
                                    <p class="description"><?php esc_html_e('Include QR codes in tickets for easy check-in.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="barcode_type"><?php esc_html_e('Barcode Type', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <select id="barcode_type" name="barcode_type">
                                        <option value="qrcode" <?php selected($barcode_type, 'qrcode'); ?>><?php esc_html_e('QR Code', 'wc-ticket-seller'); ?></option>
                                        <option value="barcode" <?php selected($barcode_type, 'barcode'); ?>><?php esc_html_e('Barcode', 'wc-ticket-seller'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('Type of barcode to use on tickets.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="send_tickets"><?php esc_html_e('Send Tickets', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <select id="send_tickets" name="send_tickets">
                                        <option value="processing" <?php selected($send_tickets, 'processing'); ?>><?php esc_html_e('When order is processing', 'wc-ticket-seller'); ?></option>
                                        <option value="completed" <?php selected($send_tickets, 'completed'); ?>><?php esc_html_e('When order is completed', 'wc-ticket-seller'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('When to send tickets to customers.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Email Settings -->
            <div id="email-settings" class="tab-pane">
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('Email Settings', 'wc-ticket-seller'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="email_ticket_subject"><?php esc_html_e('Ticket Email Subject', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="email_ticket_subject" name="email_ticket_subject" value="<?php echo esc_attr($email_ticket_subject); ?>" class="regular-text">
                                    <p class="description"><?php esc_html_e('Subject line for ticket emails. Use {event_name} as a placeholder.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="email_ticket_heading"><?php esc_html_e('Ticket Email Heading', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="email_ticket_heading" name="email_ticket_heading" value="<?php echo esc_attr($email_ticket_heading); ?>" class="regular-text">
                                    <p class="description"><?php esc_html_e('Heading for ticket emails. Use {event_name} as a placeholder.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="email_reminder_subject"><?php esc_html_e('Reminder Email Subject', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="email_reminder_subject" name="email_reminder_subject" value="<?php echo esc_attr($email_reminder_subject); ?>" class="regular-text">
                                    <p class="description"><?php esc_html_e('Subject line for event reminder emails. Use {event_name} as a placeholder.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="email_reminder_heading"><?php esc_html_e('Reminder Email Heading', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="email_reminder_heading" name="email_reminder_heading" value="<?php echo esc_attr($email_reminder_heading); ?>" class="regular-text">
                                    <p class="description"><?php esc_html_e('Heading for event reminder emails. Use {event_name} as a placeholder.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="reminder_days"><?php esc_html_e('Send Reminder', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="reminder_days" name="reminder_days" value="<?php echo esc_attr($reminder_days); ?>" min="0" step="1" class="small-text">
                                    <p class="description"><?php esc_html_e('Number of days before the event to send reminder emails. Set to 0 to disable.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- PDF & Tickets Settings -->
            <div id="pdf-settings" class="tab-pane">
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('PDF & Tickets Settings', 'wc-ticket-seller'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="pdf_size"><?php esc_html_e('PDF Size', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <select id="pdf_size" name="pdf_size">
                                        <option value="A4" <?php selected($pdf_size, 'A4'); ?>>A4</option>
                                        <option value="LETTER" <?php selected($pdf_size, 'LETTER'); ?>>Letter</option>
                                        <option value="LEGAL" <?php selected($pdf_size, 'LEGAL'); ?>>Legal</option>
                                    </select>
                                    <p class="description"><?php esc_html_e('Paper size for PDF tickets.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pdf_orientation"><?php esc_html_e('PDF Orientation', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <select id="pdf_orientation" name="pdf_orientation">
                                        <option value="portrait" <?php selected($pdf_orientation, 'portrait'); ?>><?php esc_html_e('Portrait', 'wc-ticket-seller'); ?></option>
                                        <option value="landscape" <?php selected($pdf_orientation, 'landscape'); ?>><?php esc_html_e('Landscape', 'wc-ticket-seller'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('Orientation for PDF tickets.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="enable_passbook"><?php esc_html_e('Enable Apple Wallet', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="enable_passbook" name="enable_passbook" <?php checked($enable_passbook, 'yes'); ?>>
                                    <p class="description"><?php esc_html_e('Allow customers to add tickets to Apple Wallet.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="ticket_terms"><?php esc_html_e('Ticket Terms', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    wp_editor($ticket_terms, 'ticket_terms', array(
                                        'textarea_name' => 'ticket_terms',
                                        'textarea_rows' => 5,
                                        'media_buttons' => false,
                                        'teeny' => true,
                                        'quicktags' => true,
                                    ));
                                    ?>
                                    <p class="description"><?php esc_html_e('Terms and conditions to display on tickets. Leave empty for none.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Settings -->
            <div id="advanced-settings" class="tab-pane">
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('Advanced Settings', 'wc-ticket-seller'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="delete_data"><?php esc_html_e('Delete Data on Uninstall', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="delete_data" name="delete_data" <?php checked($delete_data, 'yes'); ?>>
                                    <p class="description"><?php esc_html_e('Delete all plugin data when the plugin is uninstalled.', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="postbox">
                    <h2 class="hndle"><span><?php esc_html_e('System Information', 'wc-ticket-seller'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Plugin Version', 'wc-ticket-seller'); ?></th>
                                <td><code><?php echo esc_html(WC_TICKET_SELLER_VERSION); ?></code></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('WordPress Version', 'wc-ticket-seller'); ?></th>
                                <td><code><?php echo esc_html(get_bloginfo('version')); ?></code></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('WooCommerce Version', 'wc-ticket-seller'); ?></th>
                                <td><code><?php echo defined('WC_VERSION') ? esc_html(WC_VERSION) : esc_html__('Not detected', 'wc-ticket-seller'); ?></code></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('PHP Version', 'wc-ticket-seller'); ?></th>
                                <td><code><?php echo esc_html(phpversion()); ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <?php submit_button(__('Save Settings', 'wc-ticket-seller')); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Hide all tab panes
        $('.tab-pane').removeClass('active');
        
        // Remove active class from tabs
        $('.nav-tab').removeClass('nav-tab-active');
        
        // Get target tab
        var target = $(this).attr('href');
        
        // Activate target tab
        $(target).addClass('active');
        $(this).addClass('nav-tab-active');
    });
});
</script>

<style>
.tab-content {
    margin-top: 20px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}
</style>