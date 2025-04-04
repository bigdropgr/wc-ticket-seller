<?php
/**
 * The Events module functionality.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Events
 */

namespace WC_Ticket_Seller\Modules\Events;

/**
 * The Events module class.
 *
 * Manages event data and functionality.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Events
 * @author     Your Name <info@yourwebsite.com>
 */
class Events_Module {

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
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/events/class-event.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/events/class-event-manager.php';
    }
    
    /**
     * Register hooks.
     *
     * @since    1.0.0
     */
    private function register_hooks() {
        // Ajax handlers
        add_action( 'wp_ajax_wc_ticket_seller_create_event', array( $this, 'ajax_create_event' ) );
        add_action( 'wp_ajax_wc_ticket_seller_update_event', array( $this, 'ajax_update_event' ) );
        add_action( 'wp_ajax_wc_ticket_seller_delete_event', array( $this, 'ajax_delete_event' ) );
        add_action( 'wp_ajax_wc_ticket_seller_get_events', array( $this, 'ajax_get_events' ) );
        add_action( 'wp_ajax_nopriv_wc_ticket_seller_get_public_events', array( $this, 'ajax_get_public_events' ) );
    }
    
    /**
     * Ajax handler for creating an event.
     *
     * @since    1.0.0
     */
    public function ajax_create_event() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_admin_nonce', 'nonce' );
        
        // Check capabilities
        if ( ! current_user_can( 'create_wc_ticket_seller_events' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to create events.', 'wc-ticket-seller' )
            ) );
        }
        
        // Validate required fields
        $required_fields = array(
            'event_name',
            'event_start',
            'event_end',
        );
        
        foreach ( $required_fields as $field ) {
            if ( empty( $_POST[$field] ) ) {
                wp_send_json_error( array(
                    'message' => sprintf( __( 'Missing required field: %s', 'wc-ticket-seller' ), $field )
                ) );
            }
        }
        
        // Sanitize and prepare data
        $data = array(
            'event_name'        => sanitize_text_field( $_POST['event_name'] ),
            'event_description' => isset( $_POST['event_description'] ) ? wp_kses_post( $_POST['event_description'] ) : '',
            'event_start'       => sanitize_text_field( $_POST['event_start'] ),
            'event_end'         => sanitize_text_field( $_POST['event_end'] ),
            'venue_name'        => isset( $_POST['venue_name'] ) ? sanitize_text_field( $_POST['venue_name'] ) : '',
            'venue_address'     => isset( $_POST['venue_address'] ) ? sanitize_text_field( $_POST['venue_address'] ) : '',
            'venue_city'        => isset( $_POST['venue_city'] ) ? sanitize_text_field( $_POST['venue_city'] ) : '',
            'venue_state'       => isset( $_POST['venue_state'] ) ? sanitize_text_field( $_POST['venue_state'] ) : '',
            'venue_country'     => isset( $_POST['venue_country'] ) ? sanitize_text_field( $_POST['venue_country'] ) : '',
            'venue_postcode'    => isset( $_POST['venue_postcode'] ) ? sanitize_text_field( $_POST['venue_postcode'] ) : '',
            'event_status'      => isset( $_POST['event_status'] ) ? sanitize_text_field( $_POST['event_status'] ) : 'draft',
            'event_capacity'    => isset( $_POST['event_capacity'] ) ? intval( $_POST['event_capacity'] ) : 0,
            'organizer_id'      => get_current_user_id(),
        );
        
        // Create event
        $event_manager = new Event_Manager();
        $event_id = $event_manager->create_event( $data );
        
        if ( is_wp_error( $event_id ) ) {
            wp_send_json_error( array(
                'message' => $event_id->get_error_message()
            ) );
        }
        
        // Handle ticket types if provided
        if ( ! empty( $_POST['ticket_types'] ) && is_array( $_POST['ticket_types'] ) ) {
            foreach ( $_POST['ticket_types'] as $ticket_type ) {
                if ( empty( $ticket_type['name'] ) || ! isset( $ticket_type['price'] ) ) {
                    continue;
                }
                
                $ticket_data = array(
                    'event_id'    => $event_id,
                    'type_name'   => sanitize_text_field( $ticket_type['name'] ),
                    'description' => isset( $ticket_type['description'] ) ? sanitize_textarea_field( $ticket_type['description'] ) : '',
                    'capacity'    => isset( $ticket_type['capacity'] ) ? intval( $ticket_type['capacity'] ) : 0,
                    'price'       => floatval( $ticket_type['price'] ),
                    'start_sale'  => isset( $ticket_type['start_sale'] ) ? sanitize_text_field( $ticket_type['start_sale'] ) : null,
                    'end_sale'    => isset( $ticket_type['end_sale'] ) ? sanitize_text_field( $ticket_type['end_sale'] ) : null,
                );
                
                $event_manager->add_ticket_type( $event_id, $ticket_data );
            }
        }
        
        wp_send_json_success( array(
            'event_id' => $event_id,
            'message'  => __( 'Event created successfully.', 'wc-ticket-seller' )
        ) );
    }
    
    /**
     * Ajax handler for updating an event.
     *
     * @since    1.0.0
     */
    public function ajax_update_event() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_admin_nonce', 'nonce' );
        
        // Check capabilities
        if ( ! current_user_can( 'edit_wc_ticket_seller_events' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to update events.', 'wc-ticket-seller' )
            ) );
        }
        
        // Validate event ID
        if ( empty( $_POST['event_id'] ) ) {
            wp_send_json_error( array(
                'message' => __( 'Event ID is required.', 'wc-ticket-seller' )
            ) );
        }
        
        $event_id = intval( $_POST['event_id'] );
        
        // Check if event exists
        $event_manager = new Event_Manager();
        $event = $event_manager->get_event( $event_id );
        
        if ( ! $event ) {
            wp_send_json_error( array(
                'message' => __( 'Event not found.', 'wc-ticket-seller' )
            ) );
        }
        
        // Sanitize and prepare data
        $data = array();
        
        if ( isset( $_POST['event_name'] ) ) {
            $data['event_name'] = sanitize_text_field( $_POST['event_name'] );
        }
        
        if ( isset( $_POST['event_description'] ) ) {
            $data['event_description'] = wp_kses_post( $_POST['event_description'] );
        }
        
        if ( isset( $_POST['event_start'] ) ) {
            $data['event_start'] = sanitize_text_field( $_POST['event_start'] );
        }
        
        if ( isset( $_POST['event_end'] ) ) {
            $data['event_end'] = sanitize_text_field( $_POST['event_end'] );
        }
        
        if ( isset( $_POST['venue_name'] ) ) {
            $data['venue_name'] = sanitize_text_field( $_POST['venue_name'] );
        }
        
        if ( isset( $_POST['venue_address'] ) ) {
            $data['venue_address'] = sanitize_text_field( $_POST['venue_address'] );
        }
        
        if ( isset( $_POST['venue_city'] ) ) {
            $data['venue_city'] = sanitize_text_field( $_POST['venue_city'] );
        }
        
        if ( isset( $_POST['venue_state'] ) ) {
            $data['venue_state'] = sanitize_text_field( $_POST['venue_state'] );
        }
        
        if ( isset( $_POST['venue_country'] ) ) {
            $data['venue_country'] = sanitize_text_field( $_POST['venue_country'] );
        }
        
        if ( isset( $_POST['venue_postcode'] ) ) {
            $data['venue_postcode'] = sanitize_text_field( $_POST['venue_postcode'] );
        }
        
        if ( isset( $_POST['event_status'] ) ) {
            $data['event_status'] = sanitize_text_field( $_POST['event_status'] );
        }
        
        if ( isset( $_POST['event_capacity'] ) ) {
            $data['event_capacity'] = intval( $_POST['event_capacity'] );
        }
        
        // Update event
        $result = $event_manager->update_event( $event_id, $data );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ) );
        }
        
        // Handle ticket types if provided
        if ( ! empty( $_POST['ticket_types'] ) && is_array( $_POST['ticket_types'] ) ) {
            // First, remove existing ticket types
            $event_manager->delete_ticket_types( $event_id );
            
            // Then add new ones
            foreach ( $_POST['ticket_types'] as $ticket_type ) {
                if ( empty( $ticket_type['name'] ) || ! isset( $ticket_type['price'] ) ) {
                    continue;
                }
                
                $ticket_data = array(
                    'event_id'    => $event_id,
                    'type_name'   => sanitize_text_field( $ticket_type['name'] ),
                    'description' => isset( $ticket_type['description'] ) ? sanitize_textarea_field( $ticket_type['description'] ) : '',
                    'capacity'    => isset( $ticket_type['capacity'] ) ? intval( $ticket_type['capacity'] ) : 0,
                    'price'       => floatval( $ticket_type['price'] ),
                    'start_sale'  => isset( $ticket_type['start_sale'] ) ? sanitize_text_field( $ticket_type['start_sale'] ) : null,
                    'end_sale'    => isset( $ticket_type['end_sale'] ) ? sanitize_text_field( $ticket_type['end_sale'] ) : null,
                );
                
                $event_manager->add_ticket_type( $event_id, $ticket_data );
            }
        }
        
        wp_send_json_success( array(
            'message' => __( 'Event updated successfully.', 'wc-ticket-seller' )
        ) );
    }
    
    /**
     * Ajax handler for deleting an event.
     *
     * @since    1.0.0
     */
    public function ajax_delete_event() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_admin_nonce', 'nonce' );
        
        // Check capabilities
        if ( ! current_user_can( 'delete_wc_ticket_seller_events' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to delete events.', 'wc-ticket-seller' )
            ) );
        }
        
        // Validate event ID
        if ( empty( $_POST['event_id'] ) ) {
            wp_send_json_error( array(
                'message' => __( 'Event ID is required.', 'wc-ticket-seller' )
            ) );
        }
        
        $event_id = intval( $_POST['event_id'] );
        
        // Delete event
        $event_manager = new Event_Manager();
        $result = $event_manager->delete_event( $event_id );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ) );
        }
        
        wp_send_json_success( array(
            'message' => __( 'Event deleted successfully.', 'wc-ticket-seller' )
        ) );
    }
    
    /**
     * Ajax handler for getting events.
     *
     * @since    1.0.0
     */
    public function ajax_get_events() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_admin_nonce', 'nonce' );
        
        // Check capabilities
        if ( ! current_user_can( 'edit_wc_ticket_seller_events' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to view events.', 'wc-ticket-seller' )
            ) );
        }
        
        // Prepare query args
        $args = array();
        
        if ( ! empty( $_POST['status'] ) ) {
            $args['status'] = sanitize_text_field( $_POST['status'] );
        }
        
        if ( ! empty( $_POST['search'] ) ) {
            $args['search'] = sanitize_text_field( $_POST['search'] );
        }
        
        if ( ! empty( $_POST['from_date'] ) ) {
            $args['from_date'] = sanitize_text_field( $_POST['from_date'] );
        }
        
        if ( ! empty( $_POST['to_date'] ) ) {
            $args['to_date'] = sanitize_text_field( $_POST['to_date'] );
        }
        
        // Pagination
        $page = ! empty( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $per_page = ! empty( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 20;
        
        $args['offset'] = ( $page - 1 ) * $per_page;
        $args['limit'] = $per_page;
        
        // Get events
        $event_manager = new Event_Manager();
        $events = $event_manager->get_events( $args );
        $total = $event_manager->get_events_count( $args );
        
        wp_send_json_success( array(
            'events' => $events,
            'total' => $total,
            'pages' => ceil( $total / $per_page )
        ) );
    }
    
    /**
     * Ajax handler for getting public events.
     *
     * @since    1.0.0
     */
    public function ajax_get_public_events() {
        // Check nonce
        check_ajax_referer( 'wc_ticket_seller_public_nonce', 'nonce' );
        
        // Prepare query args - only published events
        $args = array(
            'status' => 'published'
        );
        
        if ( ! empty( $_POST['search'] ) ) {
            $args['search'] = sanitize_text_field( $_POST['search'] );
        }
        
        if ( ! empty( $_POST['from_date'] ) ) {
            $args['from_date'] = sanitize_text_field( $_POST['from_date'] );
        }
        
        if ( ! empty( $_POST['to_date'] ) ) {
            $args['to_date'] = sanitize_text_field( $_POST['to_date'] );
        }
        
        // Pagination
        $page = ! empty( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $per_page = ! empty( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 20;
        
        $args['offset'] = ( $page - 1 ) * $per_page;
        $args['limit'] = $per_page;
        
        // Get events
        $event_manager = new Event_Manager();
        $events = $event_manager->get_events( $args );
        $total = $event_manager->get_events_count( $args );
        
        wp_send_json_success( array(
            'events' => $events,
            'total' => $total,
            'pages' => ceil( $total / $per_page )
        ) );
    }
}

// Initialize the module
new Events_Module();