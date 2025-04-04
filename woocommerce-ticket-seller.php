<?php
/**
 * Plugin Name: WooCommerce Ticket Seller
 * Plugin URI: https://bigdrop.gr/woocommerce-ticket-seller
 * Description: A premium ticket selling plugin for WooCommerce with advanced features including seating charts, QR codes, and event management.
 * Version: 1.0.0
 * Author: BigDrop
 * Author URI: https://bigdrop.gr
 * Text Domain: wc-ticket-seller
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * WC requires at least: 3.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WC_TICKET_SELLER_VERSION', '1.0.0');
define('WC_TICKET_SELLER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_TICKET_SELLER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if WooCommerce is active
 *
 * @return bool
 */
function wc_ticket_seller_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', []);
    
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', []));
    }
    
    return in_array('woocommerce/woocommerce.php', $active_plugins, true) 
           || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

/**
 * Display admin notice if WooCommerce is not active
 */
function wc_ticket_seller_woocommerce_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('WooCommerce Ticket Seller requires WooCommerce to be installed and activated.', 'wc-ticket-seller'); ?></p>
    </div>
    <?php
}

/**
 * Plugin initialization
 */
function wc_ticket_seller_init() {
    // Only proceed if WooCommerce is active
    if (!wc_ticket_seller_is_woocommerce_active()) {
        add_action('admin_notices', 'wc_ticket_seller_woocommerce_notice');
        return;
    }

    // Load core files
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/class-loader.php';
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/class-i18n.php';
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/class-ticket-seller.php';
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/class-admin.php';
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'public/class-public.php';
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/integrations/class-wc-integration.php';

    // Critical hook for displaying ticket products
    add_action('woocommerce_ticket_add_to_cart', 'woocommerce_simple_add_to_cart', 30);

    // Get the main plugin instance
    $ticket_seller = new WC_Ticket_Seller\Includes\Ticket_Seller();
    
    // Run the plugin
    $ticket_seller->run();
}

// The following Ajax hooks need to be registered early
add_action('wp_ajax_wc_ticket_seller_check_in_ticket', 'wc_ticket_seller_ajax_check_in_ticket');
add_action('wp_ajax_wc_ticket_seller_cancel_ticket', 'wc_ticket_seller_ajax_cancel_ticket');
add_action('wp_ajax_wc_ticket_seller_download_ticket', 'wc_ticket_seller_ajax_download_ticket');
add_action('wp_ajax_nopriv_wc_ticket_seller_download_ticket', 'wc_ticket_seller_ajax_download_ticket');

/**
 * Ajax handler for checking in a ticket
 */
function wc_ticket_seller_ajax_check_in_ticket() {
    // Check nonce
    check_ajax_referer('wc_ticket_seller_admin_nonce', 'nonce');
    
    // Check capabilities
    if (!current_user_can('check_in_wc_ticket_seller_tickets')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to check in tickets.', 'wc-ticket-seller')
        ));
    }
    
    // Validate ticket ID
    if (empty($_POST['ticket_id'])) {
        wp_send_json_error(array(
            'message' => __('Ticket ID is required.', 'wc-ticket-seller')
        ));
    }
    
    $ticket_id = intval($_POST['ticket_id']);
    
    // Get ticket manager and check in ticket
    $ticket_manager = new WC_Ticket_Seller\Modules\Tickets\Ticket_Manager();
    $result = $ticket_manager->check_in_ticket($ticket_id, get_current_user_id());
    
    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }
    
    wp_send_json_success(array(
        'message' => __('Ticket checked in successfully.', 'wc-ticket-seller')
    ));
}

/**
 * Ajax handler for cancelling a ticket
 */
function wc_ticket_seller_ajax_cancel_ticket() {
    // Check nonce
    check_ajax_referer('wc_ticket_seller_admin_nonce', 'nonce');
    
    // Check capabilities
    if (!current_user_can('manage_wc_ticket_seller_tickets')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to cancel tickets.', 'wc-ticket-seller')
        ));
    }
    
    // Validate ticket ID
    if (empty($_POST['ticket_id'])) {
        wp_send_json_error(array(
            'message' => __('Ticket ID is required.', 'wc-ticket-seller')
        ));
    }
    
    $ticket_id = intval($_POST['ticket_id']);
    
    // Get ticket manager and cancel ticket
    $ticket_manager = new WC_Ticket_Seller\Modules\Tickets\Ticket_Manager();
    $result = $ticket_manager->cancel_ticket($ticket_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }
    
    wp_send_json_success(array(
        'message' => __('Ticket cancelled successfully.', 'wc-ticket-seller')
    ));
}

/**
 * Ajax handler for downloading a ticket
 */
function wc_ticket_seller_ajax_download_ticket() {
    // Check nonce
    check_ajax_referer('wc_ticket_seller_admin_nonce', 'nonce');
    
    // Validate ticket ID and format
    if (empty($_GET['ticket_id'])) {
        wp_die(__('Ticket ID is required.', 'wc-ticket-seller'));
    }
    
    $ticket_id = intval($_GET['ticket_id']);
    $format = !empty($_GET['format']) ? sanitize_text_field($_GET['format']) : 'pdf';
    
    // Get ticket
    $ticket_manager = new WC_Ticket_Seller\Modules\Tickets\Ticket_Manager();
    $ticket = $ticket_manager->get_ticket($ticket_id);
    
    if (!$ticket) {
        wp_die(__('Ticket not found.', 'wc-ticket-seller'));
    }
    
    // Check user permissions
    if (!current_user_can('manage_wc_ticket_seller_tickets') && get_current_user_id() != $ticket['customer_id']) {
        wp_die(__('You do not have permission to download this ticket.', 'wc-ticket-seller'));
    }
    
    // Generate ticket
    if ($format === 'pdf') {
        $file_path = $ticket_manager->generate_pdf($ticket_id);
    } elseif ($format === 'passbook') {
        $file_path = $ticket_manager->generate_passbook($ticket_id);
    } else {
        wp_die(__('Invalid format.', 'wc-ticket-seller'));
    }
    
    if (is_wp_error($file_path)) {
        wp_die($file_path->get_error_message());
    }
    
    // Check if file exists
    if (!file_exists($file_path)) {
        wp_die(__('File not found.', 'wc-ticket-seller'));
    }
    
    // Get event data for filename
    $event = new \WC_Ticket_Seller\Modules\Events\Event($ticket['event_id']);
    $event_name = $event->get_id() ? sanitize_title($event->get_name()) : 'event';
    
    // Set appropriate headers for download
    if ($format === 'pdf') {
        header('Content-Type: application/pdf');
        $filename = 'ticket-' . $ticket_id . '-' . $event_name . '.pdf';
    } elseif ($format === 'passbook') {
        header('Content-Type: application/vnd.apple.pkpass');
        $filename = 'ticket-' . $ticket_id . '-' . $event_name . '.pkpass';
    }
    
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    readfile($file_path);
    exit;
}

// Initiate the plugin on plugins_loaded action
add_action('plugins_loaded', 'wc_ticket_seller_init', 20); // Priority 20 to ensure WooCommerce is loaded first

// Activation hook
register_activation_hook(__FILE__, function() {
    // Check if WooCommerce is active
    if (!wc_ticket_seller_is_woocommerce_active()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('WooCommerce Ticket Seller requires WooCommerce to be installed and activated.', 'wc-ticket-seller'));
    }
    
    // Create database tables
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/class-activator.php';
    WC_Ticket_Seller\Includes\Activator::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/class-deactivator.php';
    WC_Ticket_Seller\Includes\Deactivator::deactivate();
});