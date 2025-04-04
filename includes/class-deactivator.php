<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Includes
 */

namespace WC_Ticket_Seller\Includes;

/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Includes
 * @author     Your Name <info@yourwebsite.com>
 */
class Deactivator {

    /**
     * Deactivate the plugin.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clean up transients
        delete_transient( 'wc_ticket_seller_activation_redirect' );
        
        // Optional: Flush rewrite rules
        flush_rewrite_rules();
    }
}
