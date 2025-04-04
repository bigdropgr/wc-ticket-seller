<?php
/**
 * The Ticket Generator class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Tickets
 */

namespace WC_Ticket_Seller\Modules\Tickets;

/**
 * The Ticket Generator class.
 *
 * Generates and manages tickets.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Tickets
 * @author     Your Name <info@yourwebsite.com>
 */
class Ticket_Generator {

    /**
     * Generate a unique ticket code.
     *
     * @since    1.0.0
     * @return   string    Unique ticket code.
     */
    public function generate_ticket_code() {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        // Generate a unique code
        do {
            // Generate a random code
            $code = wp_generate_password( 12, false );
            
            // Check if code exists
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $tickets_table WHERE ticket_code = %s",
                    $code
                )
            );
        } while ( $exists );
        
        return $code;
    }

    /**
     * Create a ticket.
     *
     * @since    1.0.0
     * @param    int      $order_id          WooCommerce order ID.
     * @param    int      $order_item_id     WooCommerce order item ID.
     * @param    int      $product_id        WooCommerce product ID.
     * @param    int      $event_id          Event ID.
     * @param    string   $ticket_type       Ticket type.
     * @param    int      $customer_id       Customer user ID.
     * @param    string   $customer_email    Customer email.
     * @param    string   $first_name        Attendee first name.
     * @param    string   $last_name         Attendee last name.
     * @param    int      $seat_id           Seat ID (optional).
     * @return   int|WP_Error               Ticket ID on success, WP_Error on failure.
     */
    public function create_ticket( $order_id, $order_item_id, $product_id, $event_id, $ticket_type, $customer_id, $customer_email, $first_name, $last_name, $seat_id = null ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        $now = current_time( 'mysql' );
        
        // Generate ticket code
        $ticket_code = $this->generate_ticket_code();
        
        // Prepare data
        $data = array(
            'order_id'       => $order_id,
            'order_item_id'  => $order_item_id,
            'product_id'     => $product_id,
            'event_id'       => $event_id,
            'ticket_code'    => $ticket_code,
            'ticket_type'    => $ticket_type,
            'customer_id'    => $customer_id,
            'customer_email' => $customer_email,
            'first_name'     => $first_name,
            'last_name'      => $last_name,
            'ticket_status'  => 'pending',
            'seat_id'        => $seat_id,
            'created_at'     => $now,
        );
        
        $format = array(
            '%d', // order_id
            '%d', // order_item_id
            '%d', // product_id
            '%d', // event_id
            '%s', // ticket_code
            '%s', // ticket_type
            '%d', // customer_id
            '%s', // customer_email
            '%s', // first_name
            '%s', // last_name
            '%s', // ticket_status
            '%d', // seat_id
            '%s', // created_at
        );
        
        // Insert ticket
        $result = $wpdb->insert(
            $tickets_table,
            $data,
            $format
        );
        
        if ( $result === false ) {
            return new \WP_Error( 'db_insert_error', __( 'Could not insert ticket into the database.', 'wc-ticket-seller' ) );
        }
        
        $ticket_id = $wpdb->insert_id;
        
        // Assign seat if needed
        if ( $seat_id ) {
            $seats_table = $wpdb->prefix . 'wc_ticket_seller_seats';
            
            $wpdb->update(
                $seats_table,
                array(
                    'status'     => 'sold',
                    'updated_at' => $now
                ),
                array( 'seat_id' => $seat_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
        }
        
        // Fire action for ticket creation
        do_action( 'wc_ticket_seller_ticket_created', $ticket_id, $order_id );
        
        return $ticket_id;
    }

    /**
     * Generate PDF ticket.
     *
     * @since    1.0.0
     * @param    int      $ticket_id    Ticket ID.
     * @return   string|WP_Error        PDF file path on success, WP_Error on failure.
     */
    public function generate_pdf( $ticket_id ) {
        // Get ticket data
        $ticket = $this->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            return new \WP_Error( 'ticket_not_found', __( 'Ticket not found.', 'wc-ticket-seller' ) );
        }
        
        // Check if TCPDF is included
        if ( ! class_exists( 'TCPDF' ) ) {
            require_once WC_TICKET_SELLER_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';
        }
        
        // Get event data
        $event = new \WC_Ticket_Seller\Modules\Events\Event( $ticket['event_id'] );
        
        if ( ! $event->get_id() ) {
            return new \WP_Error( 'event_not_found', __( 'Event not found.', 'wc-ticket-seller' ) );
        }
        
        // Generate QR code
        $qr_data = array(
            'ticket_id'   => $ticket_id,
            'ticket_code' => $ticket['ticket_code'],
            'event_id'    => $ticket['event_id'],
        );
        
        $qr_data = json_encode( $qr_data );
        $qr_data = base64_encode( $qr_data );
        
        // Get plugin options
        $pdf_size = get_option( 'wc_ticket_seller_pdf_size', 'A4' );
        $pdf_orientation = get_option( 'wc_ticket_seller_pdf_orientation', 'portrait' );
        
        // Create PDF
        $pdf = new \TCPDF( $pdf_orientation, 'mm', $pdf_size, true, 'UTF-8', false );
        
        // Set document information
        $pdf->SetCreator( get_bloginfo( 'name' ) );
        $pdf->SetAuthor( get_bloginfo( 'name' ) );
        $pdf->SetTitle( __( 'Ticket', 'wc-ticket-seller' ) . ' - ' . $event->get_name() );
        $pdf->SetSubject( __( 'Event Ticket', 'wc-ticket-seller' ) );
        
        // Remove header and footer
        $pdf->setPrintHeader( false );
        $pdf->setPrintFooter( false );
        
        // Set margins
        $pdf->SetMargins( 10, 10, 10 );
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak( true, 10 );
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont( 'helvetica', '', 12 );
        
        // Logo
        $logo_path = apply_filters( 'wc_ticket_seller_pdf_logo', '' );
        
        if ( ! empty( $logo_path ) && file_exists( $logo_path ) ) {
            $pdf->Image( $logo_path, 10, 10, 30, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false );
        }
        
        // Event name
        $pdf->SetFont( 'helvetica', 'B', 16 );
        $pdf->SetY( 20 );
        $pdf->Cell( 0, 10, $event->get_name(), 0, 1, 'C' );
        
        // Event details
        $pdf->SetFont( 'helvetica', '', 12 );
        $pdf->Ln( 5 );
        
        // Date and time
        $pdf->Cell( 0, 8, __( 'Date:', 'wc-ticket-seller' ) . ' ' . $event->get_start( 'F j, Y' ), 0, 1 );
        $pdf->Cell( 0, 8, __( 'Time:', 'wc-ticket-seller' ) . ' ' . $event->get_start( 'g:i a' ) . ' - ' . $event->get_end( 'g:i a' ), 0, 1 );
        
        // Venue
        if ( $event->get_venue_name() ) {
            $pdf->Cell( 0, 8, __( 'Venue:', 'wc-ticket-seller' ) . ' ' . $event->get_venue_name(), 0, 1 );
            $pdf->Cell( 0, 8, $event->get_venue_full_address(), 0, 1 );
        }
        
        // Ticket details
        $pdf->Ln( 5 );
        $pdf->SetFont( 'helvetica', 'B', 14 );
        $pdf->Cell( 0, 10, __( 'Ticket Information', 'wc-ticket-seller' ), 0, 1 );
        
        $pdf->SetFont( 'helvetica', '', 12 );
        $pdf->Cell( 0, 8, __( 'Ticket Type:', 'wc-ticket-seller' ) . ' ' . $ticket['ticket_type'], 0, 1 );
        $pdf->Cell( 0, 8, __( 'Attendee:', 'wc-ticket-seller' ) . ' ' . $ticket['first_name'] . ' ' . $ticket['last_name'], 0, 1 );
        
        // Seat information if available
        if ( ! empty( $ticket['seat_id'] ) ) {
            $seat = $this->get_seat( $ticket['seat_id'] );
            
            if ( $seat ) {
                $pdf->Cell( 0, 8, __( 'Seat:', 'wc-ticket-seller' ) . ' ' . $seat['section_name'] . ', ' . __( 'Row', 'wc-ticket-seller' ) . ' ' . $seat['row_name'] . ', ' . __( 'Seat', 'wc-ticket-seller' ) . ' ' . $seat['seat_number'], 0, 1 );
            }
        }
        
        // Ticket code
        $pdf->Cell( 0, 8, __( 'Ticket Code:', 'wc-ticket-seller' ) . ' ' . $ticket['ticket_code'], 0, 1 );
        
        // QR code
        $pdf->Ln( 10 );
        $pdf->write2DBarcode( $qr_data, 'QRCODE,L', 80, $pdf->GetY(), 50, 50 );
        
        // Footer text
        $pdf->Ln( 60 );
        $pdf->SetFont( 'helvetica', 'I', 8 );
        $pdf->Cell( 0, 5, __( 'This ticket is valid only for the event, date and time specified.', 'wc-ticket-seller' ), 0, 1, 'C' );
        $pdf->Cell( 0, 5, __( 'Please present this ticket at the entrance.', 'wc-ticket-seller' ), 0, 1, 'C' );
        
        // Ticket terms if set
        $ticket_terms = get_option( 'wc_ticket_seller_ticket_terms', '' );
        
        if ( ! empty( $ticket_terms ) ) {
            $pdf->Ln( 5 );
            $pdf->SetFont( 'helvetica', '', 7 );
            $pdf->MultiCell( 0, 4, $ticket_terms, 0, 'L' );
        }
        
        // Save PDF to file
        $upload_dir = wp_upload_dir();
        $tickets_dir = $upload_dir['basedir'] . '/wc-ticket-seller/tickets';
        
        // Create directory if it doesn't exist
        if ( ! file_exists( $tickets_dir ) ) {
            wp_mkdir_p( $tickets_dir );
            
            // Create .htaccess file for security
            file_put_contents( $tickets_dir . '/.htaccess', 'deny from all' );
        }
        
        $filename = 'ticket-' . $ticket_id . '-' . $ticket['ticket_code'] . '.pdf';
        $filepath = $tickets_dir . '/' . $filename;
        
        // Save PDF
        $pdf->Output( $filepath, 'F' );
        
        return $filepath;
    }

    /**
     * Generate PDF tickets for an order.
     *
     * @since    1.0.0
     * @param    int      $order_id    WooCommerce order ID.
     * @return   string|WP_Error       PDF file path on success, WP_Error on failure.
     */
    public function generate_pdf_for_order( $order_id ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        // Get all tickets for this order
        $tickets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ticket_id FROM $tickets_table WHERE order_id = %d",
                $order_id
            )
        );
        
        if ( empty( $tickets ) ) {
            return new \WP_Error( 'no_tickets', __( 'No tickets found for this order.', 'wc-ticket-seller' ) );
        }
        
        // Check if TCPDF is included
        if ( ! class_exists( 'TCPDF' ) ) {
            require_once WC_TICKET_SELLER_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';
        }
        
        // Get plugin options
        $pdf_size = get_option( 'wc_ticket_seller_pdf_size', 'A4' );
        $pdf_orientation = get_option( 'wc_ticket_seller_pdf_orientation', 'portrait' );
        
        // Create PDF
        $pdf = new \TCPDF( $pdf_orientation, 'mm', $pdf_size, true, 'UTF-8', false );
        
        // Set document information
        $pdf->SetCreator( get_bloginfo( 'name' ) );
        $pdf->SetAuthor( get_bloginfo( 'name' ) );
        $pdf->SetTitle( __( 'Event Tickets', 'wc-ticket-seller' ) . ' - ' . __( 'Order', 'wc-ticket-seller' ) . ' #' . $order_id );
        $pdf->SetSubject( __( 'Event Tickets', 'wc-ticket-seller' ) );
        
        // Remove header and footer
        $pdf->setPrintHeader( false );
        $pdf->setPrintFooter( false );
        
        // Set margins
        $pdf->SetMargins( 10, 10, 10 );
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak( true, 10 );
        
        // Generate each ticket
        foreach ( $tickets as $ticket_data ) {
            // Add a page
            $pdf->AddPage();
            
            // Generate ticket content
            $this->generate_ticket_pdf_content( $pdf, $ticket_data->ticket_id );
        }
        
        // Save PDF to file
        $upload_dir = wp_upload_dir();
        $tickets_dir = $upload_dir['basedir'] . '/wc-ticket-seller/orders';
        
        // Create directory if it doesn't exist
        if ( ! file_exists( $tickets_dir ) ) {
            wp_mkdir_p( $tickets_dir );
            
            // Create .htaccess file for security
            file_put_contents( $tickets_dir . '/.htaccess', 'deny from all' );
        }
        
        $filename = 'order-' . $order_id . '-tickets.pdf';
        $filepath = $tickets_dir . '/' . $filename;
        
        // Save PDF
        $pdf->Output( $filepath, 'F' );
        
        return $filepath;
    }

    /**
     * Generate ticket PDF content.
     *
     * @since    1.0.0
     * @param    TCPDF    $pdf          TCPDF object.
     * @param    int      $ticket_id    Ticket ID.
     */
    private function generate_ticket_pdf_content( $pdf, $ticket_id ) {
        // Get ticket data
        $ticket = $this->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            return;
        }
        
        // Get event data
        $event = new \WC_Ticket_Seller\Modules\Events\Event( $ticket['event_id'] );
        
        if ( ! $event->get_id() ) {
            return;
        }
        
        // Generate QR code
        $qr_data = array(
            'ticket_id'   => $ticket_id,
            'ticket_code' => $ticket['ticket_code'],
            'event_id'    => $ticket['event_id'],
        );
        
        $qr_data = json_encode( $qr_data );
        $qr_data = base64_encode( $qr_data );
        
        // Set font
        $pdf->SetFont( 'helvetica', '', 12 );
        
        // Logo
        $logo_path = apply_filters( 'wc_ticket_seller_pdf_logo', '' );
        
        if ( ! empty( $logo_path ) && file_exists( $logo_path ) ) {
            $pdf->Image( $logo_path, 10, 10, 30, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false );
        }
        
        // Event name
        $pdf->SetFont( 'helvetica', 'B', 16 );
        $pdf->SetY( 20 );
        $pdf->Cell( 0, 10, $event->get_name(), 0, 1, 'C' );
        
        // Event details
        $pdf->SetFont( 'helvetica', '', 12 );
        $pdf->Ln( 5 );
        
        // Date and time
        $pdf->Cell( 0, 8, __( 'Date:', 'wc-ticket-seller' ) . ' ' . $event->get_start( 'F j, Y' ), 0, 1 );
        $pdf->Cell( 0, 8, __( 'Time:', 'wc-ticket-seller' ) . ' ' . $event->get_start( 'g:i a' ) . ' - ' . $event->get_end( 'g:i a' ), 0, 1 );
        
        // Venue
        if ( $event->get_venue_name() ) {
            $pdf->Cell( 0, 8, __( 'Venue:', 'wc-ticket-seller' ) . ' ' . $event->get_venue_name(), 0, 1 );
            $pdf->Cell( 0, 8, $event->get_venue_full_address(), 0, 1 );
        }
        
        // Ticket details
        $pdf->Ln( 5 );
        $pdf->SetFont( 'helvetica', 'B', 14 );
        $pdf->Cell( 0, 10, __( 'Ticket Information', 'wc-ticket-seller' ), 0, 1 );
        
        $pdf->SetFont( 'helvetica', '', 12 );
        $pdf->Cell( 0, 8, __( 'Ticket Type:', 'wc-ticket-seller' ) . ' ' . $ticket['ticket_type'], 0, 1 );
        $pdf->Cell( 0, 8, __( 'Attendee:', 'wc-ticket-seller' ) . ' ' . $ticket['first_name'] . ' ' . $ticket['last_name'], 0, 1 );
        
        // Seat information if available
        if ( ! empty( $ticket['seat_id'] ) ) {
            $seat = $this->get_seat( $ticket['seat_id'] );
            
            if ( $seat ) {
                $pdf->Cell( 0, 8, __( 'Seat:', 'wc-ticket-seller' ) . ' ' . $seat['section_name'] . ', ' . __( 'Row', 'wc-ticket-seller' ) . ' ' . $seat['row_name'] . ', ' . __( 'Seat', 'wc-ticket-seller' ) . ' ' . $seat['seat_number'], 0, 1 );
            }
        }
        
        // Ticket code
        $pdf->Cell( 0, 8, __( 'Ticket Code:', 'wc-ticket-seller' ) . ' ' . $ticket['ticket_code'], 0, 1 );
        
        // QR code
        $pdf->Ln( 10 );
        $pdf->write2DBarcode( $qr_data, 'QRCODE,L', 80, $pdf->GetY(), 50, 50 );
        
        // Footer text
        $pdf->Ln( 60 );
        $pdf->SetFont( 'helvetica', 'I', 8 );
        $pdf->Cell( 0, 5, __( 'This ticket is valid only for the event, date and time specified.', 'wc-ticket-seller' ), 0, 1, 'C' );
        $pdf->Cell( 0, 5, __( 'Please present this ticket at the entrance.', 'wc-ticket-seller' ), 0, 1, 'C' );
        
        // Ticket terms if set
        $ticket_terms = get_option( 'wc_ticket_seller_ticket_terms', '' );
        
        if ( ! empty( $ticket_terms ) ) {
            $pdf->Ln( 5 );
            $pdf->SetFont( 'helvetica', '', 7 );
            $pdf->MultiCell( 0, 4, $ticket_terms, 0, 'L' );
        }
    }

    /**
     * Get ticket data.
     *
     * @since    1.0.0
     * @param    int      $ticket_id    Ticket ID.
     * @return   array|false            Ticket data or false if not found.
     */
    private function get_ticket( $ticket_id ) {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $tickets_table WHERE ticket_id = %d",
                $ticket_id
            ),
            ARRAY_A
        );
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
     * Generate Apple Wallet pass.
     *
     * @since    1.0.0
     * @param    int      $ticket_id    Ticket ID.
     * @return   string|WP_Error        Pass file path on success, WP_Error on failure.
     */
    public function generate_passbook( $ticket_id ) {
        // Check if PassKit library is included
        if ( ! class_exists( 'PKPass' ) ) {
            if ( ! file_exists( WC_TICKET_SELLER_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
                return new \WP_Error( 'missing_passkit', __( 'PassKit library not found. Please contact the administrator.', 'wc-ticket-seller' ) );
            }
            
            require_once WC_TICKET_SELLER_PLUGIN_DIR . 'vendor/autoload.php';
        }
        
        // Get ticket data
        $ticket = $this->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            return new \WP_Error( 'ticket_not_found', __( 'Ticket not found.', 'wc-ticket-seller' ) );
        }
        
        // Get event data
        $event = new \WC_Ticket_Seller\Modules\Events\Event( $ticket['event_id'] );
        
        if ( ! $event->get_id() ) {
            return new \WP_Error( 'event_not_found', __( 'Event not found.', 'wc-ticket-seller' ) );
        }
        
        // Get certificates path
        $certs_path = apply_filters( 'wc_ticket_seller_passbook_certs_path', WC_TICKET_SELLER_PLUGIN_DIR . 'certs/' );
        
        // Check if certificates exist
        if ( ! file_exists( $certs_path . 'pass.cer' ) || ! file_exists( $certs_path . 'pass.key' ) || ! file_exists( $certs_path . 'wwdr.pem' ) ) {
            return new \WP_Error( 'missing_certificates', __( 'PassKit certificates not found. Please contact the administrator.', 'wc-ticket-seller' ) );
        }
        
        // Create pass data
        $pass = array(
            'formatVersion' => 1,
            'passTypeIdentifier' => get_option( 'wc_ticket_seller_passbook_type_identifier', '' ),
            'serialNumber' => $ticket['ticket_code'],
            'teamIdentifier' => get_option( 'wc_ticket_seller_passbook_team_identifier', '' ),
            'organizationName' => get_bloginfo( 'name' ),
            'description' => sprintf( __( 'Ticket for %s', 'wc-ticket-seller' ), $event->get_name() ),
            'logoText' => get_bloginfo( 'name' ),
            'foregroundColor' => 'rgb(255, 255, 255)',
            'backgroundColor' => 'rgb(60, 65, 76)',
            'eventTicket' => array(
                'headerFields' => array(
                    array(
                        'key' => 'event',
                        'label' => __( 'EVENT', 'wc-ticket-seller' ),
                        'value' => $event->get_name(),
                    ),
                ),
                'primaryFields' => array(
                    array(
                        'key' => 'ticket-type',
                        'label' => __( 'TICKET', 'wc-ticket-seller' ),
                        'value' => $ticket['ticket_type'],
                    ),
                ),
                'secondaryFields' => array(
                    array(
                        'key' => 'date',
                        'label' => __( 'DATE', 'wc-ticket-seller' ),
                        'value' => $event->get_start( 'F j, Y' ),
                    ),
                    array(
                        'key' => 'time',
                        'label' => __( 'TIME', 'wc-ticket-seller' ),
                        'value' => $event->get_start( 'g:i a' ),
                    ),
                ),
                'auxiliaryFields' => array(
                    array(
                        'key' => 'venue',
                        'label' => __( 'VENUE', 'wc-ticket-seller' ),
                        'value' => $event->get_venue_name(),
                    ),
                ),
                'backFields' => array(
                    array(
                        'key' => 'name',
                        'label' => __( 'ATTENDEE', 'wc-ticket-seller' ),
                        'value' => $ticket['first_name'] . ' ' . $ticket['last_name'],
                    ),
                    array(
                        'key' => 'ticket-code',
                        'label' => __( 'TICKET CODE', 'wc-ticket-seller' ),
                        'value' => $ticket['ticket_code'],
                    ),
                ),
            ),
            'barcode' => array(
                'format' => 'PKBarcodeFormatQR',
                'message' => $ticket['ticket_code'],
                'messageEncoding' => 'iso-8859-1',
                'altText' => $ticket['ticket_code'],
            ),
            'relevantDate' => date( 'c', strtotime( $event->get_start() ) ),
            'expirationDate' => date( 'c', strtotime( $event->get_end() ) + 3600 ), // 1 hour after event end
        );
        
        // Seat information if available
        if ( ! empty( $ticket['seat_id'] ) ) {
            $seat = $this->get_seat( $ticket['seat_id'] );
            
            if ( $seat ) {
                $pass['eventTicket']['secondaryFields'][] = array(
                    'key' => 'seat',
                    'label' => __( 'SEAT', 'wc-ticket-seller' ),
                    'value' => $seat['section_name'] . ', ' . __( 'Row', 'wc-ticket-seller' ) . ' ' . $seat['row_name'] . ', ' . __( 'Seat', 'wc-ticket-seller' ) . ' ' . $seat['seat_number'],
                );
            }
        }
        
        // Create PKPass
        $pass_path = $certs_path . 'pass-template/';
        
        // Create pass
        $PKPass = new \PKPass\PKPass( $pass, $pass_path, $certs_path . 'pass.cer', $certs_path . 'pass.key', $certs_path . 'wwdr.pem', get_option( 'wc_ticket_seller_passbook_password', '' ) );
        
        // Save pass to file
        $upload_dir = wp_upload_dir();
        $passes_dir = $upload_dir['basedir'] . '/wc-ticket-seller/passes';
        
        // Create directory if it doesn't exist
        if ( ! file_exists( $passes_dir ) ) {
            wp_mkdir_p( $passes_dir );
            
            // Create .htaccess file for security
            file_put_contents( $passes_dir . '/.htaccess', 'deny from all' );
        }
        
        $filename = 'pass-' . $ticket_id . '-' . $ticket['ticket_code'] . '.pkpass';
        $filepath = $passes_dir . '/' . $filename;
        
        // Save pass
        file_put_contents( $filepath, $PKPass->getPackage() );
        
        return $filepath;
    }
}