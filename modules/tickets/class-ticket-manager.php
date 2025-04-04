<?php
/**
 * The Ticket Manager class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Tickets
 */

namespace WC_Ticket_Seller\Modules\Tickets;

/**
 * The Ticket Manager class.
 *
 * Handles tickets data and operations.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Tickets
 * @author     Your Name <info@yourwebsite.com>
 */
class Ticket_Manager {

    /**
     * Get a ticket by ID.
     *
     * @since    1.0.0
     * @param    int      $ticket_id    Ticket ID.
     * @return   array|false            Ticket data or false if not found.
     */
    public function get_ticket( $ticket_id ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        $ticket = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $tickets_table WHERE ticket_id = %d",
                $ticket_id
            ),
            ARRAY_A
        );
        
        return $ticket;
    }
    
    /**
     * Get a ticket by code.
     *
     * @since    1.0.0
     * @param    string   $ticket_code  Ticket code.
     * @return   array|false            Ticket data or false if not found.
     */
    public function get_ticket_by_code( $ticket_code ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        $ticket = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $tickets_table WHERE ticket_code = %s",
                $ticket_code
            ),
            ARRAY_A
        );
        
        return $ticket;
    }
    
    /**
     * Update ticket details.
     *
     * @since    1.0.0
     * @param    int      $ticket_id    Ticket ID.
     * @param    array    $data         Ticket data.
     * @return   bool|WP_Error          True on success, WP_Error on failure.
     */
    public function update_ticket( $ticket_id, $data ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        // Get ticket
        $ticket = $this->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            return new \WP_Error( 'ticket_not_found', __( 'Ticket not found.', 'wc-ticket-seller' ) );
        }
        
        // Prepare update data
        $update_data = array();
        $format = array();
        
        if ( isset( $data['first_name'] ) ) {
            $update_data['first_name'] = $data['first_name'];
            $format[] = '%s';
        }
        
        if ( isset( $data['last_name'] ) ) {
            $update_data['last_name'] = $data['last_name'];
            $format[] = '%s';
        }
        
        if ( isset( $data['customer_email'] ) ) {
            $update_data['customer_email'] = $data['customer_email'];
            $format[] = '%s';
        }
        
        if ( isset( $data['ticket_status'] ) ) {
            $update_data['ticket_status'] = $data['ticket_status'];
            $format[] = '%s';
        }
        
        if ( isset( $data['seat_id'] ) ) {
            $update_data['seat_id'] = $data['seat_id'];
            $format[] = '%d';
        }
        
        if ( empty( $update_data ) ) {
            return true; // Nothing to update
        }
        
        // Update ticket
        $result = $wpdb->update(
            $tickets_table,
            $update_data,
            array( 'ticket_id' => $ticket_id ),
            $format,
            array( '%d' )
        );
        
        if ( $result === false ) {
            return new \WP_Error( 'update_failed', __( 'Failed to update ticket.', 'wc-ticket-seller' ) );
        }
        
        do_action( 'wc_ticket_seller_ticket_updated', $ticket_id, $update_data );
        
        return true;
    }
    
    /**
     * Cancel a ticket.
     *
     * @since    1.0.0
     * @param    int      $ticket_id    Ticket ID.
     * @return   bool|WP_Error          True on success, WP_Error on failure.
     */
    public function cancel_ticket( $ticket_id ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        $now = current_time( 'mysql' );
        
        // Get ticket
        $ticket = $this->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            return new \WP_Error( 'ticket_not_found', __( 'Ticket not found.', 'wc-ticket-seller' ) );
        }
        
        // Update ticket status
        $result = $wpdb->update(
            $tickets_table,
            array(
                'ticket_status' => 'cancelled',
                'updated_at' => $now
            ),
            array( 'ticket_id' => $ticket_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
        
        if ( $result === false ) {
            return new \WP_Error( 'update_failed', __( 'Failed to cancel ticket.', 'wc-ticket-seller' ) );
        }
        
        // Release seat if assigned
        if ( ! empty( $ticket['seat_id'] ) ) {
            $seats_table = $wpdb->prefix . 'wc_ticket_seller_seats';
            
            $wpdb->update(
                $seats_table,
                array(
                    'status' => 'available',
                    'updated_at' => $now
                ),
                array( 'seat_id' => $ticket['seat_id'] ),
                array( '%s', '%s' ),
                array( '%d' )
            );
        }
        
        do_action( 'wc_ticket_seller_ticket_cancelled', $ticket_id );
        
        return true;
    }
    
    /**
     * Check in a ticket.
     *
     * @since    1.0.0
     * @param    int      $ticket_id     Ticket ID.
     * @param    int      $user_id       User ID checking in the ticket.
     * @param    string   $station_id    Station ID (optional).
     * @param    string   $notes         Check-in notes (optional).
     * @param    string   $location      Check-in location (optional).
     * @return   bool|WP_Error           True on success, WP_Error on failure.
     */
    public function check_in_ticket( $ticket_id, $user_id, $station_id = '', $notes = '', $location = '' ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        $check_ins_table = $wpdb->prefix . 'wc_ticket_seller_check_ins';
        $now = current_time( 'mysql' );
        
        // Get ticket
        $ticket = $this->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            return new \WP_Error( 'ticket_not_found', __( 'Ticket not found.', 'wc-ticket-seller' ) );
        }
        
        // Check if ticket is already checked in
        if ( $ticket['ticket_status'] === 'checked-in' ) {
            return new \WP_Error( 'already_checked_in', __( 'Ticket is already checked in.', 'wc-ticket-seller' ) );
        }
        
        // Check if ticket is cancelled
        if ( $ticket['ticket_status'] === 'cancelled' ) {
            return new \WP_Error( 'ticket_cancelled', __( 'Cannot check in a cancelled ticket.', 'wc-ticket-seller' ) );
        }
        
        // Start transaction
        $wpdb->query( 'START TRANSACTION' );
        
        // Update ticket status
        $ticket_update = $wpdb->update(
            $tickets_table,
            array(
                'ticket_status' => 'checked-in',
                'checked_in_at' => $now,
                'checked_in_by' => $user_id
            ),
            array( 'ticket_id' => $ticket_id ),
            array( '%s', '%s', '%d' ),
            array( '%d' )
        );
        
        if ( $ticket_update === false ) {
            $wpdb->query( 'ROLLBACK' );
            return new \WP_Error( 'update_failed', __( 'Failed to update ticket status.', 'wc-ticket-seller' ) );
        }
        
        // Record check-in
        $check_in_insert = $wpdb->insert(
            $check_ins_table,
            array(
                'ticket_id' => $ticket_id,
                'event_id' => $ticket['event_id'],
                'checked_in_by' => $user_id,
                'check_in_time' => $now,
                'station_id' => $station_id,
                'notes' => $notes,
                'location' => $location,
                'created_at' => $now
            ),
            array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
        );
        
        if ( $check_in_insert === false ) {
            $wpdb->query( 'ROLLBACK' );
            return new \WP_Error( 'insert_failed', __( 'Failed to record check-in.', 'wc-ticket-seller' ) );
        }
        
        // Commit transaction
        $wpdb->query( 'COMMIT' );
        
        do_action( 'wc_ticket_seller_ticket_checked_in', $ticket_id, $user_id );
        
        return true;
    }
    
    /**
     * Get tickets.
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments.
     * @return   array             Tickets data.
     */
    public function get_tickets( $args = array() ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        $events_table = $wpdb->prefix . 'wc_ticket_seller_events';
        
        // Default arguments
        $defaults = array(
            'event_id'    => 0,
            'status'      => '',
            'search'      => '',
            'order_id'    => 0,
            'customer_id' => 0,
            'orderby'     => 'created_at',
            'order'       => 'DESC',
            'offset'      => 0,
            'limit'       => 20,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Build query
        $where = array( '1=1' );
        $values = array();
        
        if ( ! empty( $args['event_id'] ) ) {
            $where[] = 't.event_id = %d';
            $values[] = $args['event_id'];
        }
        
        if ( ! empty( $args['status'] ) ) {
            $where[] = 't.ticket_status = %s';
            $values[] = $args['status'];
        }
        
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(t.ticket_code LIKE %s OR t.first_name LIKE %s OR t.last_name LIKE %s OR t.customer_email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if ( ! empty( $args['order_id'] ) ) {
            $where[] = 't.order_id = %d';
            $values[] = $args['order_id'];
        }
        
        if ( ! empty( $args['customer_id'] ) ) {
            $where[] = 't.customer_id = %d';
            $values[] = $args['customer_id'];
        }
        
        // Build the WHERE clause
        $where_clause = implode( ' AND ', $where );
        
        // Sanitize orderby
        $allowed_orderby = array( 'ticket_id', 'created_at', 'checked_in_at', 'ticket_status' );
        $orderby = in_array( $args['orderby'], $allowed_orderby ) ? 't.' . $args['orderby'] : 't.created_at';
        
        // Sanitize order
        $order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
        
        // Limit and offset
        $limit = absint( $args['limit'] );
        $offset = absint( $args['offset'] );
        
        // Prepare and execute query
        $query = $wpdb->prepare(
            "SELECT t.*, e.event_name, e.event_start, e.event_end, e.venue_name 
             FROM $tickets_table AS t 
             LEFT JOIN $events_table AS e ON t.event_id = e.event_id 
             WHERE $where_clause 
             ORDER BY $orderby $order 
             LIMIT %d OFFSET %d",
            array_merge( $values, array( $limit, $offset ) )
        );
        
        $tickets = $wpdb->get_results( $query, ARRAY_A );
        
        // Enhance ticket data
        foreach ( $tickets as &$ticket ) {
            // Add order details
            if ( ! empty( $ticket['order_id'] ) ) {
                $order = wc_get_order( $ticket['order_id'] );
                if ( $order ) {
                    $ticket['order_number'] = $order->get_order_number();
                    $ticket['order_status'] = $order->get_status();
                    $ticket['order_date'] = $order->get_date_created()->date_i18n( get_option( 'date_format' ) );
                }
            }
            
            // Add seat details if assigned
            if ( ! empty( $ticket['seat_id'] ) ) {
                $seat = $this->get_seat( $ticket['seat_id'] );
                if ( $seat ) {
                    $ticket['seat'] = $seat;
                }
            }
        }
        
        return $tickets;
    }
    
    /**
     * Get tickets count.
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments.
     * @return   int               Tickets count.
     */
    public function get_tickets_count( $args = array() ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        // Default arguments
        $defaults = array(
            'event_id'    => 0,
            'status'      => '',
            'search'      => '',
            'order_id'    => 0,
            'customer_id' => 0,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Build query
        $where = array( '1=1' );
        $values = array();
        
        if ( ! empty( $args['event_id'] ) ) {
            $where[] = 'event_id = %d';
            $values[] = $args['event_id'];
        }
        
        if ( ! empty( $args['status'] ) ) {
            $where[] = 'ticket_status = %s';
            $values[] = $args['status'];
        }
        
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(ticket_code LIKE %s OR first_name LIKE %s OR last_name LIKE %s OR customer_email LIKE %s)';
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if ( ! empty( $args['order_id'] ) ) {
            $where[] = 'order_id = %d';
            $values[] = $args['order_id'];
        }
        
        if ( ! empty( $args['customer_id'] ) ) {
            $where[] = 'customer_id = %d';
            $values[] = $args['customer_id'];
        }
        
        // Build the WHERE clause
        $where_clause = implode( ' AND ', $where );
        
        // Prepare and execute query
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $tickets_table WHERE $where_clause",
            $values
        );
        
        return $wpdb->get_var( $query );
    }
    
    /**
     * Get tickets for a customer.
     *
     * @since    1.0.0
     * @param    int      $customer_id    Customer ID.
     * @param    string   $status         Ticket status (optional).
     * @return   array                    Tickets data.
     */
    public function get_customer_tickets( $customer_id, $status = '' ) {
        $args = array(
            'customer_id' => $customer_id,
            'limit'       => 100,
        );
        
        if ( ! empty( $status ) ) {
            $args['status'] = $status;
        }
        
        return $this->get_tickets( $args );
    }
    
    /**
     * Get tickets for an event.
     *
     * @since    1.0.0
     * @param    int      $event_id    Event ID.
     * @param    string   $status      Ticket status (optional).
     * @return   array                 Tickets data.
     */
    public function get_event_tickets( $event_id, $status = '' ) {
        $args = array(
            'event_id' => $event_id,
            'limit'    => 1000,
        );
        
        if ( ! empty( $status ) ) {
            $args['status'] = $status;
        }
        
        return $this->get_tickets( $args );
    }
    
    /**
     * Get seat data.
     *
     * @since    1.0.0
     * @param    int      $seat_id    Seat ID.
     * @return   array|false          Seat data or false if not found.
     */
    private function get_seat( $seat_id ) {
        global $wpdb;
        $seats_table = $wpdb->prefix . 'wc_ticket_seller_seats';
        $sections_table = $wpdb->prefix . 'wc_ticket_seller_sections';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT s.*, sec.section_name 
                 FROM $seats_table AS s
                 LEFT JOIN $sections_table AS sec ON s.section_id = sec.section_id
                 WHERE s.seat_id = %d",
                $seat_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get check-in status for a ticket.
     *
     * @since    1.0.0
     * @param    int      $ticket_id    Ticket ID.
     * @return   array|false            Check-in data or false if not checked in.
     */
    public function get_check_in_status( $ticket_id ) {
        global $wpdb;
        $check_ins_table = $wpdb->prefix . 'wc_ticket_seller_check_ins';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $check_ins_table WHERE ticket_id = %d ORDER BY check_in_time DESC LIMIT 1",
                $ticket_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get check-in statistics for an event.
     *
     * @since    1.0.0
     * @param    int      $event_id    Event ID.
     * @return   array                 Check-in statistics.
     */
    public function get_check_in_stats( $event_id ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        // Get total tickets
        $total_tickets = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tickets_table WHERE event_id = %d AND ticket_status != 'cancelled'",
                $event_id
            )
        );
        
        // Get checked-in tickets
        $checked_in_tickets = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tickets_table WHERE event_id = %d AND ticket_status = 'checked-in'",
                $event_id
            )
        );
        
        // Calculate percentage
        $check_in_percent = $total_tickets > 0 ? round( ( $checked_in_tickets / $total_tickets ) * 100, 2 ) : 0;
        
        return array(
            'total_tickets' => $total_tickets,
            'checked_in_tickets' => $checked_in_tickets,
            'check_in_percent' => $check_in_percent
        );
    }
    
    /**
     * Export tickets for an event.
     *
     * @since    1.0.0
     * @param    int      $event_id    Event ID.
     * @param    string   $format      Export format (csv or excel).
     * @return   string|WP_Error       File path or WP_Error on failure.
     */
    public function export_tickets( $event_id, $format = 'csv' ) {
        // Get tickets
        $tickets = $this->get_event_tickets( $event_id );
        
        if ( empty( $tickets ) ) {
            return new \WP_Error( 'no_tickets', __( 'No tickets found for this event.', 'wc-ticket-seller' ) );
        }
        
        // Prepare data
        $data = array();
        
        // Header row
        $data[] = array(
            __( 'Ticket ID', 'wc-ticket-seller' ),
            __( 'Ticket Code', 'wc-ticket-seller' ),
            __( 'Ticket Type', 'wc-ticket-seller' ),
            __( 'First Name', 'wc-ticket-seller' ),
            __( 'Last Name', 'wc-ticket-seller' ),
            __( 'Email', 'wc-ticket-seller' ),
            __( 'Status', 'wc-ticket-seller' ),
            __( 'Section', 'wc-ticket-seller' ),
            __( 'Row', 'wc-ticket-seller' ),
            __( 'Seat', 'wc-ticket-seller' ),
            __( 'Order ID', 'wc-ticket-seller' ),
            __( 'Created At', 'wc-ticket-seller' ),
            __( 'Checked In At', 'wc-ticket-seller' )
        );
        
        // Ticket rows
        foreach ( $tickets as $ticket ) {
            $row = array(
            $ticket['ticket_id'],
            $ticket['ticket_code'],
            $ticket['ticket_type'],
            $ticket['first_name'],
            $ticket['last_name'],
            $ticket['customer_email'],
            $ticket['ticket_status'],
            isset( $ticket['seat'] ) ? $ticket['seat']['section_name'] : '',
            isset( $ticket['seat'] ) ? $ticket['seat']['row_name'] : '',
            isset( $ticket['seat'] ) ? $ticket['seat']['seat_number'] : '',
            $ticket['order_id'],
            $ticket['created_at'],
            $ticket['checked_in_at'] ? $ticket['checked_in_at'] : ''  // <-- Remove the comma here
        );
            
            $data[] = $row;
        }
        
        // Create export file
        $upload_dir = wp_upload_dir();
        $exports_dir = $upload_dir['basedir'] . '/wc-ticket-seller/exports';
        
        // Create directory if it doesn't exist
        if ( ! file_exists( $exports_dir ) ) {
            wp_mkdir_p( $exports_dir );
            
            // Create .htaccess file for security
            file_put_contents( $exports_dir . '/.htaccess', 'deny from all' );
        }
        
        // Get event name for filename
        $event = new \WC_Ticket_Seller\Modules\Events\Event( $event_id );
        $event_name = $event->get_id() ? sanitize_title( $event->get_name() ) : 'event';
        
        $filename = 'tickets-' . $event_id . '-' . $event_name . '-' . date( 'Y-m-d' );
        
        if ( $format === 'csv' ) {
            $filepath = $exports_dir . '/' . $filename . '.csv';
            
            // Create CSV
            $fp = fopen( $filepath, 'w' );
            
            foreach ( $data as $row ) {
                fputcsv( $fp, $row );
            }
            
            fclose( $fp );
            
            return $filepath;
        } elseif ( $format === 'excel' ) {
            // Check if PhpSpreadsheet is included
            if ( ! class_exists( '\PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
                if ( ! file_exists( WC_TICKET_SELLER_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
                    return new \WP_Error( 'missing_phpspreadsheet', __( 'PhpSpreadsheet library not found. Please contact the administrator.', 'wc-ticket-seller' ) );
                }
                
                require_once WC_TICKET_SELLER_PLUGIN_DIR . 'vendor/autoload.php';
            }
            
            $filepath = $exports_dir . '/' . $filename . '.xlsx';
            
            // Create Excel file
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Add data
            $rowIndex = 1;
            foreach ( $data as $rowData ) {
                $colIndex = 1;
                foreach ( $rowData as $cellData ) {
                    $sheet->setCellValueByColumnAndRow( $colIndex++, $rowIndex, $cellData );
                }
                $rowIndex++;
            }
            
            // Auto-size columns
            foreach ( range( 'A', $sheet->getHighestColumn() ) as $col ) {
                $sheet->getColumnDimension( $col )->setAutoSize( true );
            }
            
            // Format header row
            $sheet->getStyle( '1:1' )->getFont()->setBold( true );
            
            // Save file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
            $writer->save( $filepath );
            
            return $filepath;
        }
        
        return new \WP_Error( 'invalid_format', __( 'Invalid export format.', 'wc-ticket-seller' ) );
    }
    
    /**
     * Get custom field values for a ticket.
     *
     * @since    1.0.0
     * @param    int      $ticket_id    Ticket ID.
     * @return   array                  Custom field values.
     */
    public function get_custom_field_values( $ticket_id ) {
        global $wpdb;
        $values_table = $wpdb->prefix . 'wc_ticket_seller_custom_field_values';
        $fields_table = $wpdb->prefix . 'wc_ticket_seller_custom_fields';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT v.*, f.field_name, f.field_label, f.field_type 
                 FROM $values_table AS v
                 LEFT JOIN $fields_table AS f ON v.field_id = f.field_id
                 WHERE v.ticket_id = %d",
                $ticket_id
            ),
            ARRAY_A
        );
        
        $values = array();
        
        foreach ( $results as $result ) {
            $values[$result['field_id']] = array(
                'field_id' => $result['field_id'],
                'field_name' => $result['field_name'],
                'field_label' => $result['field_label'],
                'field_type' => $result['field_type'],
                'field_value' => maybe_unserialize( $result['field_value'] )
            );
        }
        
        return $values;
    }
}