<?php
/**
 * The WooCommerce Cart Handler class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 */

namespace WC_Ticket_Seller\Integrations;

/**
 * The WooCommerce Cart Handler class.
 *
 * Handles WooCommerce cart functionality for ticket products.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 * @author     Your Name <info@yourwebsite.com>
 */
class WC_Cart_Handler {

    /**
     * Validate ticket products when added to cart.
     *
     * @since     1.0.0
     * @param     bool         $passed      Whether validation passed.
     * @param     WC_Product   $product     Product object.
     * @param     int          $quantity    Quantity.
     * @param     array        $variations  Variations.
     * @return    bool                      Whether validation passed.
     */
    public function validate_add_to_cart( $passed, $product, $quantity, $variations = array() ) {
        // Check if event has already ended
        if ( $product->has_event_ended() ) {
            wc_add_notice( __( 'This event has already ended.', 'wc-ticket-seller' ), 'error' );
            return false;
        }
        
        // Check if tickets are available
        $available_tickets = $product->get_available_tickets_count();
        
        if ( $available_tickets < $quantity ) {
            if ( $available_tickets <= 0 ) {
                wc_add_notice( __( 'Sorry, tickets for this event are sold out.', 'wc-ticket-seller' ), 'error' );
            } else {
                wc_add_notice( 
                    sprintf( 
                        __( 'Sorry, only %d tickets are available for this event.', 'wc-ticket-seller' ), 
                        $available_tickets 
                    ), 
                    'error' 
                );
            }
            return false;
        }
        
        // Check for selected seats if seat selection is enabled
        if ( $product->is_seat_selection_enabled() ) {
            if ( empty( $_POST['seat_id'] ) || count( $_POST['seat_id'] ) !== $quantity ) {
                wc_add_notice( __( 'Please select seats for all tickets.', 'wc-ticket-seller' ), 'error' );
                return false;
            }
            
            // Validate seats
            $seats = $this->validate_seats( $product, $_POST['seat_id'] );
            
            if ( is_wp_error( $seats ) ) {
                wc_add_notice( $seats->get_error_message(), 'error' );
                return false;
            }
        }
        
        // Check for ticket types
        if ( ! empty( $_POST['ticket_type'] ) ) {
            $ticket_types = $product->get_ticket_types();
            $selected_type = sanitize_text_field( $_POST['ticket_type'] );
            
            if ( ! empty( $ticket_types ) && ! isset( $ticket_types[$selected_type] ) ) {
                wc_add_notice( __( 'Invalid ticket type selected.', 'wc-ticket-seller' ), 'error' );
                return false;
            }
        }
        
        // All checks passed
        return $passed;
    }
    
    /**
     * Validate seats.
     *
     * @since     1.0.0
     * @param     WC_Product   $product    Product object.
     * @param     array        $seat_ids   Selected seat IDs.
     * @return    bool|WP_Error            True on success, WP_Error on failure.
     */
    private function validate_seats( $product, $seat_ids ) {
        global $wpdb;
        $seats_table = $wpdb->prefix . 'wc_ticket_seller_seats';
        $chart_id = $product->get_seating_chart_id();
        
        if ( ! $chart_id ) {
            return new \WP_Error( 'invalid_chart', __( 'Invalid seating chart.', 'wc-ticket-seller' ) );
        }
        
        // Sanitize seat IDs
        $seat_ids = array_map( 'intval', $seat_ids );
        
        // Check if seats exist and are available
        $placeholders = implode( ',', array_fill( 0, count( $seat_ids ), '%d' ) );
        $query = $wpdb->prepare(
            "SELECT seat_id, status FROM $seats_table WHERE seat_id IN ($placeholders) AND chart_id = %d",
            array_merge( $seat_ids, array( $chart_id ) )
        );
        
        $seats = $wpdb->get_results( $query );
        
        // Check if all seats were found
        if ( count( $seats ) !== count( $seat_ids ) ) {
            return new \WP_Error( 'invalid_seats', __( 'One or more selected seats do not exist.', 'wc-ticket-seller' ) );
        }
        
        // Check if all seats are available
        foreach ( $seats as $seat ) {
            if ( $seat->status !== 'available' ) {
                return new \WP_Error( 'unavailable_seats', __( 'One or more selected seats are not available.', 'wc-ticket-seller' ) );
            }
        }
        
        // Temporarily reserve seats
        foreach ( $seat_ids as $seat_id ) {
            $wpdb->update(
                $seats_table,
                array(
                    'status' => 'reserved',
                    'updated_at' => current_time( 'mysql' )
                ),
                array( 'seat_id' => $seat_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
        }
        
        // Schedule cleanup of reserved seats after 15 minutes
        $args = array(
            'seat_ids' => $seat_ids,
        );
        
        wp_schedule_single_event( time() + ( 15 * MINUTE_IN_SECONDS ), 'wc_ticket_seller_release_reserved_seats', $args );
        
        return true;
    }
    
    /**
     * Add custom ticket checkout fields.
     *
     * @since     1.0.0
     * @param     array    $fields    Checkout fields.
     * @return    array               Modified checkout fields.
     */
    public function add_checkout_fields( $fields ) {
        // Check if cart has ticket products that need attendee info
        $need_attendee_info = false;
        $attendee_fields = array();
        
        if (!WC()->cart) {
            return $fields;
        }
        
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = $cart_item['data'];
            
            if ( $product && $product->is_type( 'ticket' ) ) {
                $need_attendee_info = true;
                
                // Get custom attendee fields for this product
                $product_attendee_fields = $product->get_attendee_fields();
                
                if ( ! empty( $product_attendee_fields ) ) {
                    foreach ( $product_attendee_fields as $field ) {
                        $attendee_fields[$field['id']] = $field;
                    }
                }
            }
        }
        
        if ( $need_attendee_info ) {
            // Add attendee info section
            $fields['attendee_info'] = array(
                'attendee_info_heading' => array(
                    'type'        => 'heading',
                    'label'       => __( 'Attendee Information', 'wc-ticket-seller' ),
                    'class'       => array( 'form-row-wide' ),
                    'priority'    => 110,
                ),
            );
            
            // Dynamically add fields for each ticket
            $cart_contents = WC()->cart->get_cart_contents();
            $item_count = 1;
            
            foreach ( $cart_contents as $cart_item_key => $cart_item ) {
                $product = $cart_item['data'];
                
                if ( $product && $product->is_type( 'ticket' ) ) {
                    $event_data = $product->get_event_data();
                    $event_name = ! empty( $event_data['event_name'] ) ? $event_data['event_name'] : __( 'Event', 'wc-ticket-seller' );
                    
                    for ( $i = 0; $i < $cart_item['quantity']; $i++ ) {
                        $fields['attendee_info']['attendee_' . $item_count . '_heading'] = array(
                            'type'        => 'heading',
                            'label'       => sprintf( __( 'Ticket #%d - %s', 'wc-ticket-seller' ), $item_count, $event_name ),
                            'class'       => array( 'form-row-wide' ),
                            'priority'    => 110 + $item_count,
                        );
                        
                        $fields['attendee_info']['attendee_' . $item_count . '_first_name'] = array(
                            'type'        => 'text',
                            'label'       => __( 'First Name', 'wc-ticket-seller' ),
                            'placeholder' => __( 'First Name', 'wc-ticket-seller' ),
                            'class'       => array( 'form-row-first' ),
                            'priority'    => 110 + $item_count,
                        );
                        
                        $fields['attendee_info']['attendee_' . $item_count . '_last_name'] = array(
                            'type'        => 'text',
                            'label'       => __( 'Last Name', 'wc-ticket-seller' ),
                            'placeholder' => __( 'Last Name', 'wc-ticket-seller' ),
                            'class'       => array( 'form-row-last' ),
                            'priority'    => 110 + $item_count,
                        );
                        
                        $fields['attendee_info']['attendee_' . $item_count . '_email'] = array(
                            'type'        => 'email',
                            'label'       => __( 'Email', 'wc-ticket-seller' ),
                            'placeholder' => __( 'Email', 'wc-ticket-seller' ),
                            'class'       => array( 'form-row-wide' ),
                            'priority'    => 110 + $item_count,
                        );
                        
                        // Add custom fields if any
                        if ( ! empty( $attendee_fields ) ) {
                            foreach ( $attendee_fields as $field_id => $field ) {
                                $field_type = 'text';
                                
                                switch ( $field['type'] ) {
                                    case 'select':
                                        $field_type = 'select';
                                        break;
                                        
                                    case 'checkbox':
                                        $field_type = 'checkbox';
                                        break;
                                        
                                    case 'textarea':
                                        $field_type = 'textarea';
                                        break;
                                }
                                
                                $fields['attendee_info']['attendee_' . $item_count . '_custom_' . $field_id] = array(
                                    'type'        => $field_type,
                                    'label'       => $field['label'],
                                    'placeholder' => $field['label'],
                                    'class'       => array( 'form-row-wide' ),
                                    'priority'    => 110 + $item_count,
                                    'required'    => ! empty( $field['required'] ),
                                    'options'     => ! empty( $field['options'] ) ? $field['options'] : array(),
                                );
                            }
                        }
                        
                        $item_count++;
                    }
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * Process checkout attendee fields.
     *
     * @since     1.0.0
     * @param     int      $order_id    Order ID.
     * @param     array    $data        Posted data.
     */
    public function process_checkout_attendee_fields( $order_id, $data ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return;
        }
        
        // Get attendee fields from POST data
        $attendee_data = array();
        
        foreach ( $_POST as $key => $value ) {
            if ( strpos( $key, 'attendee_' ) === 0 && strpos( $key, '_heading' ) === false ) {
                $parts = explode( '_', $key );
                
                if ( count( $parts ) >= 3 ) {
                    $attendee_number = intval( $parts[1] );
                    $field_name = $parts[2];
                    
                    // Handle custom fields
                    if ( $field_name === 'custom' && isset( $parts[3] ) ) {
                        $field_id = $parts[3];
                        $attendee_data[$attendee_number]['custom_fields'][$field_id] = sanitize_text_field( $value );
                    } else {
                        $attendee_data[$attendee_number][$field_name] = sanitize_text_field( $value );
                    }
                }
            }
        }
        
        // Add attendee info to order items
        if ( ! empty( $attendee_data ) ) {
            $items = $order->get_items();
            $item_index = 1;
            
            foreach ( $items as $item_id => $item ) {
                $product = $item->get_product();
                
                if ( $product && $product->is_type( 'ticket' ) ) {
                    $quantity = $item->get_quantity();
                    $item_attendee_data = array();
                    
                    // Collect attendee data for this item
                    for ( $i = 0; $i < $quantity; $i++ ) {
                        if ( isset( $attendee_data[$item_index] ) ) {
                            $item_attendee_data[] = $attendee_data[$item_index];
                            $item_index++;
                        }
                    }
                    
                    // Save attendee data to item meta
                    if ( ! empty( $item_attendee_data ) ) {
                        $item->update_meta_data( '_attendee_info', $item_attendee_data );
                        $item->save_meta_data();
                    }
                }
            }
        }
    }
}

// Add hook for processing checkout fields
add_action( 'woocommerce_checkout_update_order_meta', array( new WC_Cart_Handler(), 'process_checkout_attendee_fields' ), 10, 2 );

// Add hook for releasing reserved seats
add_action( 'wc_ticket_seller_release_reserved_seats', 'wc_ticket_seller_release_reserved_seats_callback' );

/**
 * Release reserved seats that have expired.
 *
 * @since    1.0.0
 * @param    array    $args    Arguments including seat IDs.
 */
function wc_ticket_seller_release_reserved_seats_callback( $args ) {
    if ( empty( $args['seat_ids'] ) ) {
        return;
    }
    
    global $wpdb;
    $seats_table = $wpdb->prefix . 'wc_ticket_seller_seats';
    
    foreach ( $args['seat_ids'] as $seat_id ) {
        // Only update if seat is still reserved
        $wpdb->query( $wpdb->prepare(
            "UPDATE $seats_table SET status = 'available', updated_at = %s WHERE seat_id = %d AND status = 'reserved'",
            current_time( 'mysql' ),
            $seat_id
        ) );
    }
}