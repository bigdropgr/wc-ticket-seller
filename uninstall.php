<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    WC_Ticket_Seller
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Load main plugin file to get constants
require_once 'woocommerce-ticket-seller.php';

/**
 * Clean up database when uninstalling the plugin.
 */
function wc_ticket_seller_uninstall() {
    global $wpdb;
    
    // Check if we should delete data
    $delete_data = get_option( 'wc_ticket_seller_delete_data_on_uninstall', 'no' );
    
    if ( $delete_data === 'yes' ) {
        // Drop custom tables
        $tables = array(
            'wc_ticket_seller_events',
            'wc_ticket_seller_tickets',
            'wc_ticket_seller_venues',
            'wc_ticket_seller_seating_charts',
            'wc_ticket_seller_sections',
            'wc_ticket_seller_seats',
            'wc_ticket_seller_ticket_types',
            'wc_ticket_seller_pricing_rules',
            'wc_ticket_seller_waitlist',
            'wc_ticket_seller_check_ins',
            'wc_ticket_seller_custom_fields',
            'wc_ticket_seller_custom_field_values'
        );
        
        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
        }
        
        // Delete plugin options
        $options = array(
            'wc_ticket_seller_version',
            'wc_ticket_seller_delete_data_on_uninstall',
            'wc_ticket_seller_ticket_expiry',
            'wc_ticket_seller_enable_qr_codes',
            'wc_ticket_seller_barcode_type',
            'wc_ticket_seller_send_tickets',
            'wc_ticket_seller_email_ticket_subject',
            'wc_ticket_seller_email_ticket_heading',
            'wc_ticket_seller_email_reminder_subject',
            'wc_ticket_seller_email_reminder_heading',
            'wc_ticket_seller_reminder_days',
            'wc_ticket_seller_pdf_size',
            'wc_ticket_seller_pdf_orientation',
            'wc_ticket_seller_passbook_type_identifier',
            'wc_ticket_seller_passbook_team_identifier',
            'wc_ticket_seller_passbook_password',
            'wc_ticket_seller_ticket_terms'
        );
        
        foreach ( $options as $option ) {
            delete_option( $option );
        }
        
        // Delete ticket files
        $upload_dir = wp_upload_dir();
        $ticket_dirs = array(
            $upload_dir['basedir'] . '/wc-ticket-seller/tickets',
            $upload_dir['basedir'] . '/wc-ticket-seller/orders',
            $upload_dir['basedir'] . '/wc-ticket-seller/passes',
            $upload_dir['basedir'] . '/wc-ticket-seller'
        );
        
        foreach ( $ticket_dirs as $dir ) {
            wc_ticket_seller_remove_directory( $dir );
        }
        
        // Remove capabilities from roles
        $capabilities = array(
            'manage_wc_ticket_seller',
            'view_wc_ticket_seller_reports',
            'manage_wc_ticket_seller_settings',
            'create_wc_ticket_seller_events',
            'edit_wc_ticket_seller_events',
            'delete_wc_ticket_seller_events',
            'manage_wc_ticket_seller_tickets',
            'check_in_wc_ticket_seller_tickets',
        );
        
        $roles = array( 'administrator', 'shop_manager' );
        
        foreach ( $roles as $role_name ) {
            $role = get_role( $role_name );
            
            if ( $role ) {
                foreach ( $capabilities as $capability ) {
                    $role->remove_cap( $capability );
                }
            }
        }
    }
}

/**
 * Recursively remove a directory.
 *
 * @param string $dir Directory path.
 */
function wc_ticket_seller_remove_directory( $dir ) {
    if ( ! is_dir( $dir ) ) {
        return;
    }
    
    $objects = scandir( $dir );
    
    foreach ( $objects as $object ) {
        if ( $object === '.' || $object === '..' ) {
            continue;
        }
        
        $path = $dir . '/' . $object;
        
        if ( is_dir( $path ) ) {
            wc_ticket_seller_remove_directory( $path );
        } else {
            unlink( $path );
        }
    }
    
    rmdir( $dir );
}

// Run uninstall
wc_ticket_seller_uninstall();