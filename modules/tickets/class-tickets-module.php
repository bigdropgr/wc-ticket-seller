<?php
/**
 * The Tickets module functionality.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Tickets
 */

namespace WC_Ticket_Seller\Modules\Tickets;

/**
 * The Tickets module class.
 *
 * Manages tickets functionality.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Tickets
 * @author     Your Name <info@yourwebsite.com>
 */
class Tickets_Module {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Load dependencies
        $this->load_dependencies();
        
        // Register hooks
        $this->register_hooks();
    }
    
    /**
     * Load dependencies.
     *
     * @since    1.0.0
     */
    private function load_dependencies() {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/tickets/class-ticket-generator.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/tickets/class-ticket-manager.php';
    }
    
    /**
     * Register hooks.
     *
     * @since    1.0.0
     */
    private function register_hooks() {
        // Ajax handlers
        add_action( 'wp_ajax_wc_ticket_seller_get_tickets', array( $this, 'ajax_get_tickets' ) );
        add_action( 'wp_ajax_wc_ticket_seller_cancel_ticket', array( $this, 'ajax_cancel_ticket' ) );
        add_action( 'wp_ajax_wc_ticket_seller_check_in_ticket', array( $this, 'ajax_check_in_ticket' ) );
        add_action( 'wp_ajax_wc_ticket_seller_verify_ticket', array( $this, 'ajax_verify_ticket' ) );
        add_action( 'wp_ajax_nopriv_wc_ticket_seller_verify_ticket', array( $this, 'ajax_verify_ticket' ) );
        add_action( 'wp_ajax_wc_ticket_seller_download_ticket', array( $this, 'ajax_download_ticket' ) );
        add_action( 'wp_ajax_nopriv_wc_ticket_seller_download_ticket', array( $this, 'ajax_download_ticket' ) );
        
        // User tickets
        add_action( 'woocommerce_account_tickets_endpoint', array( $this, 'account_tickets_endpoint' ) );
        
        // Add tickets tab to WooCommerce My Account
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_tickets_account_menu_item' ) );
        add_filter( 'woocommerce_get_query_vars', array( $this, 'add_tickets_query_var' ) );
    }
    
    /**
     * Ajax handler for getting tickets.
     *
     * @since    1.0.0
     */
    public function ajax_get_tickets() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_admin_nonce', 'nonce' );
        
        // Check capabilities
        if ( ! current_user_can( 'manage_wc_ticket_seller_tickets' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to manage tickets.', 'wc-ticket-seller' )
            ) );
        }
        
        // Prepare query args
        $args = array();
        
        if ( ! empty( $_POST['event_id'] ) ) {
            $args['event_id'] = intval( $_POST['event_id'] );
        }
        
        if ( ! empty( $_POST['status'] ) ) {
            $args['status'] = sanitize_text_field( $_POST['status'] );
        }
        
        if ( ! empty( $_POST['search'] ) ) {
            $args['search'] = sanitize_text_field( $_POST['search'] );
        }
        
        if ( ! empty( $_POST['order_id'] ) ) {
            $args['order_id'] = intval( $_POST['order_id'] );
        }
        
        if ( ! empty( $_POST['customer_id'] ) ) {
            $args['customer_id'] = intval( $_POST['customer_id'] );
        }
        
        // Pagination
        $page = ! empty( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $per_page = ! empty( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 20;
        
        $args['offset'] = ( $page - 1 ) * $per_page;
        $args['limit'] = $per_page;
        
        // Get tickets
        $ticket_manager = new Ticket_Manager();
        $tickets = $ticket_manager->get_tickets( $args );
        $total = $ticket_manager->get_tickets_count( $args );
        
        wp_send_json_success( array(
            'tickets' => $tickets,
            'total' => $total,
            'pages' => ceil( $total / $per_page )
        ) );
    }
    
    /**
     * Ajax handler for cancelling a ticket.
     *
     * @since    1.0.0
     */
    public function ajax_cancel_ticket() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_admin_nonce', 'nonce' );
        
        // Check capabilities
        if ( ! current_user_can( 'manage_wc_ticket_seller_tickets' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to cancel tickets.', 'wc-ticket-seller' )
            ) );
        }
        
        // Validate ticket ID
        if ( empty( $_POST['ticket_id'] ) ) {
            wp_send_json_error( array(
                'message' => __( 'Ticket ID is required.', 'wc-ticket-seller' )
            ) );
        }
        
        $ticket_id = intval( $_POST['ticket_id'] );
        
        // Cancel ticket
        $ticket_manager = new Ticket_Manager();
        $result = $ticket_manager->cancel_ticket( $ticket_id );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Ticket cancelled successfully.', 'wc-ticket-seller' )
        ) );
    }
    
    /**
     * Ajax handler for checking in a ticket.
     *
     * @since    1.0.0
     */
    public function ajax_check_in_ticket() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_admin_nonce', 'nonce' );
        
        // Check capabilities
        if ( ! current_user_can( 'check_in_wc_ticket_seller_tickets' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to check in tickets.', 'wc-ticket-seller' )
            ) );
        }
        
        // Validate ticket code or ID
        if ( empty( $_POST['ticket_code'] ) && empty( $_POST['ticket_id'] ) ) {
            wp_send_json_error( array(
                'message' => __( 'Ticket code or ID is required.', 'wc-ticket-seller' )
            ) );
        }
        
        $ticket_manager = new Ticket_Manager();
        
        if ( ! empty( $_POST['ticket_code'] ) ) {
            $ticket_code = sanitize_text_field( $_POST['ticket_code'] );
            $ticket = $ticket_manager->get_ticket_by_code( $ticket_code );
        } else {
            $ticket_id = intval( $_POST['ticket_id'] );
            $ticket = $ticket_manager->get_ticket( $ticket_id );
        }
        
        if ( ! $ticket ) {
            wp_send_json_error( array(
                'message' => __( 'Ticket not found.', 'wc-ticket-seller' )
            ) );
        }
        
        // Event validation if event_id is provided
        if ( ! empty( $_POST['event_id'] ) ) {
            $event_id = intval( $_POST['event_id'] );
            
            if ( $ticket['event_id'] != $event_id ) {
                wp_send_json_error( array(
                    'message' => __( 'Ticket is for a different event.', 'wc-ticket-seller' )
                ) );
            }
        }
        
        // Additional data
        $station_id = ! empty( $_POST['station_id'] ) ? sanitize_text_field( $_POST['station_id'] ) : '';
        $notes = ! empty( $_POST['notes'] ) ? sanitize_textarea_field( $_POST['notes'] ) : '';
        $location = ! empty( $_POST['location'] ) ? sanitize_text_field( $_POST['location'] ) : '';
        
        // Check in ticket
        $result = $ticket_manager->check_in_ticket( $ticket['ticket_id'], get_current_user_id(), $station_id, $notes, $location );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Ticket checked in successfully.', 'wc-ticket-seller' ),
            'ticket' => $ticket
        ) );
    }
    
    /**
     * Ajax handler for verifying a ticket.
     *
     * @since    1.0.0
     */
    public function ajax_verify_ticket() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_public_nonce', 'nonce' );
        
        // Validate ticket code
        if ( empty( $_POST['ticket_code'] ) ) {
            wp_send_json_error( array(
                'message' => __( 'Ticket code is required.', 'wc-ticket-seller' )
            ) );
        }
        
        $ticket_code = sanitize_text_field( $_POST['ticket_code'] );
        
        // Verify ticket
        $ticket_manager = new Ticket_Manager();
        $ticket = $ticket_manager->get_ticket_by_code( $ticket_code );
        
        if ( ! $ticket ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid ticket code.', 'wc-ticket-seller' ),
                'status' => 'invalid'
            ) );
        }
        
        // Check ticket status
        if ( $ticket['ticket_status'] === 'cancelled' ) {
            wp_send_json_error( array(
                'message' => __( 'This ticket has been cancelled.', 'wc-ticket-seller' ),
                'status' => 'cancelled'
            ) );
        }
        
        if ( $ticket['ticket_status'] === 'checked-in' ) {
            wp_send_json_error( array(
                'message' => __( 'This ticket has already been checked in.', 'wc-ticket-seller' ),
                'status' => 'checked-in',
                'check_in_time' => $ticket['checked_in_at']
            ) );
        }
        
        // Get event data
        $event = new \WC_Ticket_Seller\Modules\Events\Event( $ticket['event_id'] );
        
        if ( ! $event->get_id() ) {
            wp_send_json_error( array(
                'message' => __( 'Event not found.', 'wc-ticket-seller' ),
                'status' => 'error'
            ) );
        }
        
        // Check if event has ended
        if ( $event->has_ended() ) {
            wp_send_json_error( array(
                'message' => __( 'This event has already ended.', 'wc-ticket-seller' ),
                'status' => 'expired'
            ) );
        }
        
        // Return ticket and event data
        wp_send_json_success( array(
            'message' => __( 'Valid ticket.', 'wc-ticket-seller' ),
            'status' => 'valid',
            'ticket' => $ticket,
            'event' => $event->to_array()
        ) );
    }
    
    /**
     * Ajax handler for downloading a ticket.
     *
     * @since    1.0.0
     */
    public function ajax_download_ticket() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_public_nonce', 'nonce' );
        
        // Validate ticket ID and format
        if ( empty( $_GET['ticket_id'] ) ) {
            wp_die( __( 'Ticket ID is required.', 'wc-ticket-seller' ) );
        }
        
        $ticket_id = intval( $_GET['ticket_id'] );
        $format = ! empty( $_GET['format'] ) ? sanitize_text_field( $_GET['format'] ) : 'pdf';
        
        // Get ticket
        $ticket_manager = new Ticket_Manager();
        $ticket = $ticket_manager->get_ticket( $ticket_id );
        
        if ( ! $ticket ) {
            wp_die( __( 'Ticket not found.', 'wc-ticket-seller' ) );
        }
        
        // Check user permissions
        if ( ! current_user_can( 'manage_wc_ticket_seller_tickets' ) && get_current_user_id() != $ticket['customer_id'] ) {
            wp_die( __( 'You do not have permission to download this ticket.', 'wc-ticket-seller' ) );
        }
        
        // Generate ticket
        $ticket_generator = new Ticket_Generator();
        
        if ( $format === 'pdf' ) {
            $file_path = $ticket_generator->generate_pdf( $ticket_id );
        } elseif ( $format === 'passbook' ) {
            $file_path = $ticket_generator->generate_passbook( $ticket_id );
        } else {
            wp_die( __( 'Invalid format.', 'wc-ticket-seller' ) );
        }
        
        if ( is_wp_error( $file_path ) ) {
            wp_die( $file_path->get_error_message() );
        }
        
        // Check if file exists
        if ( ! file_exists( $file_path ) ) {
            wp_die( __( 'File not found.', 'wc-ticket-seller' ) );
        }
        
        // Get event data for filename
        $event = new \WC_Ticket_Seller\Modules\Events\Event( $ticket['event_id'] );
        $event_name = $event->get_id() ? sanitize_title( $event->get_name() ) : 'event';
        
        // Set appropriate headers for download
        if ( $format === 'pdf' ) {
            header( 'Content-Type: application/pdf' );
            $filename = 'ticket-' . $ticket_id . '-' . $event_name . '.pdf';
        } elseif ( $format === 'passbook' ) {
            header( 'Content-Type: application/vnd.apple.pkpass' );
            $filename = 'ticket-' . $ticket_id . '-' . $event_name . '.pkpass';
        }
        
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $file_path ) );
        header( 'Cache-Control: private, max-age=0, must-revalidate' );
        header( 'Pragma: public' );
        
        readfile( $file_path );
        exit;
    }
    
    /**
     * Add tickets endpoint to WooCommerce My Account menu.
     *
     * @since    1.0.0
     * @param    array    $items    Menu items.
     * @return   array              Modified menu items.
     */
    public function add_tickets_account_menu_item( $items ) {
        // Add new item after orders
        $new_items = array();
        
        foreach ( $items as $key => $item ) {
            $new_items[$key] = $item;
            
            if ( $key === 'orders' ) {
                $new_items['tickets'] = __( 'My Tickets', 'wc-ticket-seller' );
            }
        }
        
        return $new_items;
    }
    
    /**
     * Add tickets query var to WooCommerce.
     *
     * @since    1.0.0
     * @param    array    $vars    Query vars.
     * @return   array             Modified query vars.
     */
    public function add_tickets_query_var( $vars ) {
        $vars['tickets'] = 'tickets';
        return $vars;
    }
    
    /**
     * Add tickets endpoint content to WooCommerce My Account.
     *
     * @since    1.0.0
     */
    public function account_tickets_endpoint() {
        $ticket_manager = new Ticket_Manager();
        $tickets = $ticket_manager->get_customer_tickets( get_current_user_id() );
        
        \WC_Ticket_Seller\Public_Area\Public_Area::load_template( 'account/tickets.php', array(
            'tickets' => $tickets
        ) );
    }
}

// Initialize the module
new Tickets_Module();