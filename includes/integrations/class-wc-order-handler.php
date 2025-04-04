<?php
/**
 * The WooCommerce Order Handler class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 */

namespace WC_Ticket_Seller\Integrations;

use WC_Ticket_Seller\Modules\Tickets\Ticket_Generator;

/**
 * The WooCommerce Order Handler class.
 *
 * Handles WooCommerce orders with ticket products.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 * @author     Your Name <info@yourwebsite.com>
 */
class WC_Order_Handler {

    /**
     * Process a ticket order.
     *
     * @since    1.0.0
     * @param    int    $order_id    WooCommerce order ID.
     */
    public function process_ticket_order( $order_id ) {
        // Get order
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return;
        }
        
        // Only process if not already processed
        if ( $order->get_meta( '_wc_ticket_seller_processed' ) === 'yes' ) {
            return;
        }
        
        // Check if this order contains ticket products
        $has_tickets = false;
        
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            
            if ( $product && $product->is_type( 'ticket' ) ) {
                $has_tickets = true;
                $this->process_ticket_item( $order, $item_id, $item, $product );
            }
        }
        
        // Mark order as processed
        if ( $has_tickets ) {
            $order->update_meta_data( '_wc_ticket_seller_processed', 'yes' );
            $order->save();
            
            // Trigger email if enabled
            $send_tickets = get_option( 'wc_ticket_seller_send_tickets', 'completed' );
            
            if ( $send_tickets === 'completed' && $order->has_status( 'completed' ) ) {
                $this->send_ticket_email( $order_id );
            } elseif ( $send_tickets === 'processing' && ( $order->has_status( 'processing' ) || $order->has_status( 'completed' ) ) ) {
                $this->send_ticket_email( $order_id );
            }
        }
    }
    
    /**
     * Process a ticket item.
     *
     * @since    1.0.0
     * @param    WC_Order       $order      WooCommerce order.
     * @param    int            $item_id    Order item ID.
     * @param    WC_Order_Item  $item       Order item.
     * @param    WC_Product     $product    Product.
     */
    private function process_ticket_item( $order, $item_id, $item, $product ) {
        $customer_id = $order->get_customer_id();
        $customer_email = $order->get_billing_email();
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        
        // Get event ID
        $event_id = $product->get_event_id();
        
        if ( ! $event_id ) {
            // Log error
            error_log( sprintf( 'WC Ticket Seller: No event ID found for product %d in order %d', $product->get_id(), $order->get_id() ) );
            return;
        }
        
        // Get ticket type from item meta or default to General Admission
        $ticket_type = $item->get_meta( '_ticket_type' );
        if ( empty( $ticket_type ) ) {
            $ticket_type = 'general';
        }
        
        // Get seat ID from item meta if any
        $seat_id = $item->get_meta( '_seat_id' );
        
        // Get attendee info from item meta
        $attendee_info = $item->get_meta( '_attendee_info' );
        
        // Create ticket for each quantity
        $quantity = $item->get_quantity();
        $tickets = array();
        
        $ticket_generator = new Ticket_Generator();
        
        for ( $i = 0; $i < $quantity; $i++ ) {
            // Set attendee details from attendee info if available
            $attendee_first_name = $first_name;
            $attendee_last_name = $last_name;
            $attendee_email = $customer_email;
            
            if ( ! empty( $attendee_info ) && ! empty( $attendee_info[$i] ) ) {
                if ( ! empty( $attendee_info[$i]['first_name'] ) ) {
                    $attendee_first_name = $attendee_info[$i]['first_name'];
                }
                
                if ( ! empty( $attendee_info[$i]['last_name'] ) ) {
                    $attendee_last_name = $attendee_info[$i]['last_name'];
                }
                
                if ( ! empty( $attendee_info[$i]['email'] ) ) {
                    $attendee_email = $attendee_info[$i]['email'];
                }
            }
            
            // Generate ticket
            $ticket_id = $ticket_generator->create_ticket(
                $order->get_id(),
                $item_id,
                $product->get_id(),
                $event_id,
                $ticket_type,
                $customer_id,
                $customer_email,
                $attendee_first_name,
                $attendee_last_name,
                $seat_id
            );
            
            if ( $ticket_id ) {
                $tickets[] = $ticket_id;
                
                // Store custom field values if available
                if ( ! empty( $attendee_info ) && ! empty( $attendee_info[$i] ) && ! empty( $attendee_info[$i]['custom_fields'] ) ) {
                    $this->store_custom_field_values( $ticket_id, $attendee_info[$i]['custom_fields'] );
                }
            }
        }
        
        // Store ticket IDs in item meta
        if ( ! empty( $tickets ) ) {
            $item->update_meta_data( '_ticket_ids', $tickets );
            $item->save_meta_data();
        }
    }
    
    /**
     * Store custom field values.
     *
     * @since    1.0.0
     * @param    int      $ticket_id       Ticket ID.
     * @param    array    $custom_fields   Custom fields.
     */
    private function store_custom_field_values( $ticket_id, $custom_fields ) {
        global $wpdb;
        $values_table = $wpdb->prefix . 'wc_ticket_seller_custom_field_values';
        $now = current_time( 'mysql' );
        
        foreach ( $custom_fields as $field_id => $value ) {
            $wpdb->insert(
                $values_table,
                array(
                    'field_id' => $field_id,
                    'ticket_id' => $ticket_id,
                    'field_value' => maybe_serialize( $value ),
                    'created_at' => $now,
                    'updated_at' => $now
                ),
                array( '%d', '%d', '%s', '%s', '%s' )
            );
        }
    }
    
    /**
     * Cancel tickets for an order.
     *
     * @since    1.0.0
     * @param    int    $order_id    WooCommerce order ID.
     */
    public function cancel_ticket_order( $order_id ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        $now = current_time( 'mysql' );
        
        // Get all tickets for this order
        $tickets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ticket_id, seat_id FROM $tickets_table WHERE order_id = %d",
                $order_id
            )
        );
        
        foreach ( $tickets as $ticket ) {
            // Update ticket status to cancelled
            $wpdb->update(
                $tickets_table,
                array(
                    'ticket_status' => 'cancelled',
                    'updated_at' => $now
                ),
                array( 'ticket_id' => $ticket->ticket_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
            
            // Release seat if assigned
            if ( ! empty( $ticket->seat_id ) ) {
                $seats_table = $wpdb->prefix . 'wc_ticket_seller_seats';
                
                $wpdb->update(
                    $seats_table,
                    array(
                        'status' => 'available',
                        'updated_at' => $now
                    ),
                    array( 'seat_id' => $ticket->seat_id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
            }
            
            // Fire action for cancelled ticket
            do_action( 'wc_ticket_seller_ticket_cancelled', $ticket->ticket_id );
        }
    }
    
    /**
     * Send ticket email.
     *
     * @since    1.0.0
     * @param    int    $order_id    WooCommerce order ID.
     */
    public function send_ticket_email( $order_id ) {
        $mailer = WC()->mailer();
        $mails = $mailer->get_emails();
        
        if ( ! empty( $mails ) && isset( $mails['WC_Email_Tickets'] ) ) {
            $mails['WC_Email_Tickets']->trigger( $order_id );
        }
    }
}
