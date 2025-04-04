<?php
/**
 * The Event Manager class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Events
 */

namespace WC_Ticket_Seller\Modules\Events;

/**
 * The Event Manager class.
 *
 * Handles events CRUD operations.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Events
 * @author     Your Name <info@yourwebsite.com>
 */
class Event_Manager {

    /**
     * Create a new event.
     *
     * @since    1.0.0
     * @param    array    $data    Event data.
     * @return   int|WP_Error      Event ID on success, WP_Error on failure.
     */
    public function create_event( $data ) {
        // Validate required fields
        $required_fields = array(
            'event_name',
            'event_start',
            'event_end'
        );
        
        foreach ( $required_fields as $field ) {
            if ( empty( $data[$field] ) ) {
                return new \WP_Error( 'missing_required_field', sprintf( __( 'Missing required field: %s', 'wc-ticket-seller' ), $field ) );
            }
        }
        
        // Validate dates
        $start = strtotime( $data['event_start'] );
        $end = strtotime( $data['event_end'] );
        
        if ( ! $start ) {
            return new \WP_Error( 'invalid_start_date', __( 'Invalid start date format.', 'wc-ticket-seller' ) );
        }
        
        if ( ! $end ) {
            return new \WP_Error( 'invalid_end_date', __( 'Invalid end date format.', 'wc-ticket-seller' ) );
        }
        
        if ( $end <= $start ) {
            return new \WP_Error( 'invalid_date_range', __( 'End date must be after start date.', 'wc-ticket-seller' ) );
        }
        
        // Create event
        $event = new Event( $data );
        $event_id = $event->save();
        
        return $event_id;
    }
    
    /**
     * Get an event.
     *
     * @since    1.0.0
     * @param    int      $event_id    Event ID.
     * @return   Event|false           Event object on success, false on failure.
     */
    public function get_event( $event_id ) {
        $event = new Event( $event_id );
        
        if ( ! $event->get_id() ) {
            return false;
        }
        
        return $event;
    }
    
    /**
     * Update an event.
     *
     * @since    1.0.0
     * @param    int      $event_id    Event ID.
     * @param    array    $data        Event data.
     * @return   int|WP_Error          Event ID on success, WP_Error on failure.
     */
    public function update_event( $event_id, $data ) {
        $event = $this->get_event( $event_id );
        
        if ( ! $event ) {
            return new \WP_Error( 'event_not_found', __( 'Event not found.', 'wc-ticket-seller' ) );
        }
        
        // Update event data
        if ( isset( $data['event_name'] ) ) {
            $event->set_name( $data['event_name'] );
        }
        
        if ( isset( $data['event_description'] ) ) {
            $event->set_description( $data['event_description'] );
        }
        
        if ( isset( $data['event_start'] ) ) {
            $start = strtotime( $data['event_start'] );
            
            if ( ! $start ) {
                return new \WP_Error( 'invalid_start_date', __( 'Invalid start date format.', 'wc-ticket-seller' ) );
            }
            
            $event->set_start( $data['event_start'] );
        }
        
        if ( isset( $data['event_end'] ) ) {
            $end = strtotime( $data['event_end'] );
            
            if ( ! $end ) {
                return new \WP_Error( 'invalid_end_date', __( 'Invalid end date format.', 'wc-ticket-seller' ) );
            }
            
            $event->set_end( $data['event_end'] );
        }
        
        // Validate dates
        $start = strtotime( $event->get_start() );
        $end = strtotime( $event->get_end() );
        
        if ( $end <= $start ) {
            return new \WP_Error( 'invalid_date_range', __( 'End date must be after start date.', 'wc-ticket-seller' ) );
        }
        
        if ( isset( $data['venue_name'] ) ) {
            $event->set_venue_name( $data['venue_name'] );
        }
        
        if ( isset( $data['venue_address'] ) ) {
            $event->venue_address = $data['venue_address'];
        }
        
        if ( isset( $data['venue_city'] ) ) {
            $event->venue_city = $data['venue_city'];
        }
        
        if ( isset( $data['venue_state'] ) ) {
            $event->venue_state = $data['venue_state'];
        }
        
        if ( isset( $data['venue_country'] ) ) {
            $event->venue_country = $data['venue_country'];
        }
        
        if ( isset( $data['venue_postcode'] ) ) {
            $event->venue_postcode = $data['venue_postcode'];
        }
        
        if ( isset( $data['event_status'] ) ) {
            $event->set_status( $data['event_status'] );
        }
        
        if ( isset( $data['event_capacity'] ) ) {
            $event->set_capacity( $data['event_capacity'] );
        }
        
        // Save event
        return $event->save();
    }
    
    /**
     * Delete an event.
     *
     * @since    1.0.0
     * @param    int      $event_id    Event ID.
     * @return   bool|WP_Error         True on success, WP_Error on failure.
     */
    public function delete_event( $event_id ) {
        global $wpdb;
        $event_table = $wpdb->prefix . 'wc_ticket_seller_events';
        
        // Check if event exists
        $event = $this->get_event( $event_id );
        
        if ( ! $event ) {
            return new \WP_Error( 'event_not_found', __( 'Event not found.', 'wc-ticket-seller' ) );
        }
        
        // Check if event has tickets
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        $tickets_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tickets_table WHERE event_id = %d",
                $event_id
            )
        );
        
        if ( $tickets_count > 0 ) {
            return new \WP_Error( 'event_has_tickets', __( 'Cannot delete event with existing tickets.', 'wc-ticket-seller' ) );
        }
        
        // Delete ticket types
        $this->delete_ticket_types( $event_id );
        
        // Delete event
        $result = $wpdb->delete(
            $event_table,
            array( 'event_id' => $event_id ),
            array( '%d' )
        );
        
        if ( $result === false ) {
            return new \WP_Error( 'db_delete_error', __( 'Could not delete event from the database.', 'wc-ticket-seller' ) );
        }
        
        do_action( 'wc_ticket_seller_event_deleted', $event_id );
        
        return true;
    }
    
    /**
     * Get events.
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments.
     * @return   array             Events data.
     */
    public function get_events( $args = array() ) {
        global $wpdb;
        $events_table = $wpdb->prefix . 'wc_ticket_seller_events';
        
        // Default arguments
        $defaults = array(
            'status'    => '',
            'search'    => '',
            'from_date' => '',
            'to_date'   => '',
            'organizer' => 0,
            'orderby'   => 'event_start',
            'order'     => 'ASC',
            'offset'    => 0,
            'limit'     => 20,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Build query
        $where = array( '1=1' );
        $values = array();
        
        if ( ! empty( $args['status'] ) ) {
            $where[] = 'event_status = %s';
            $values[] = $args['status'];
        }
        
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(event_name LIKE %s OR venue_name LIKE %s)';
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if ( ! empty( $args['from_date'] ) ) {
            $where[] = 'event_start >= %s';
            $values[] = $args['from_date'];
        }
        
        if ( ! empty( $args['to_date'] ) ) {
            $where[] = 'event_end <= %s';
            $values[] = $args['to_date'];
        }
        
        if ( ! empty( $args['organizer'] ) ) {
            $where[] = 'organizer_id = %d';
            $values[] = $args['organizer'];
        }
        
        // Build the WHERE clause
        $where_clause = implode( ' AND ', $where );
        
        // Sanitize orderby
        $allowed_orderby = array( 'event_id', 'event_name', 'event_start', 'event_end', 'created_at', 'updated_at' );
        $orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'event_start';
        
        // Sanitize order
        $order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
        
        // Limit and offset
        $limit = intval( $args['limit'] );
        $offset = intval( $args['offset'] );
        
        // Prepare and execute query
        $query = $wpdb->prepare(
            "SELECT * FROM $events_table WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d",
            array_merge( $values, array( $limit, $offset ) )
        );
        
        $events = $wpdb->get_results( $query, ARRAY_A );
        
        // Load ticket types and enhance event data
        $enhanced_events = array();
        
        foreach ( $events as $event_data ) {
            $event = new Event( $event_data );
            $enhanced_events[] = $event->to_array();
        }
        
        return $enhanced_events;
    }
    
    /**
     * Get events count.
     *
     * @since    1.0.0
     * @param    array    $args    Query arguments.
     * @return   int               Events count.
     */
    public function get_events_count( $args = array() ) {
        global $wpdb;
        $events_table = $wpdb->prefix . 'wc_ticket_seller_events';
        
        // Default arguments
        $defaults = array(
            'status'    => '',
            'search'    => '',
            'from_date' => '',
            'to_date'   => '',
            'organizer' => 0,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Build query
        $where = array( '1=1' );
        $values = array();
        
        if ( ! empty( $args['status'] ) ) {
            $where[] = 'event_status = %s';
            $values[] = $args['status'];
        }
        
        if ( ! empty( $args['search'] ) ) {
            $where[] = '(event_name LIKE %s OR venue_name LIKE %s)';
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if ( ! empty( $args['from_date'] ) ) {
            $where[] = 'event_start >= %s';
            $values[] = $args['from_date'];
        }
        
        if ( ! empty( $args['to_date'] ) ) {
            $where[] = 'event_end <= %s';
            $values[] = $args['to_date'];
        }
        
        if ( ! empty( $args['organizer'] ) ) {
            $where[] = 'organizer_id = %d';
            $values[] = $args['organizer'];
        }
        
        // Build the WHERE clause
        $where_clause = implode( ' AND ', $where );
        
        // Prepare and execute query
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $events_table WHERE $where_clause",
            $values
        );
        
        return $wpdb->get_var( $query );
    }
    
    /**
     * Add a ticket type to an event.
     *
     * @since    1.0.0
     * @param    int      $event_id     Event ID.
     * @param    array    $ticket_data  Ticket type data.
     * @return   int|WP_Error           Ticket type ID on success, WP_Error on failure.
     */
    public function add_ticket_type( $event_id, $ticket_data ) {
        global $wpdb;
        $ticket_types_table = $wpdb->prefix . 'wc_ticket_seller_ticket_types';
        $now = current_time( 'mysql' );
        
        // Validate event
        $event = $this->get_event( $event_id );
        
        if ( ! $event ) {
            return new \WP_Error( 'event_not_found', __( 'Event not found.', 'wc-ticket-seller' ) );
        }
        
        // Validate required fields
        if ( empty( $ticket_data['type_name'] ) ) {
            return new \WP_Error( 'missing_type_name', __( 'Ticket type name is required.', 'wc-ticket-seller' ) );
        }
        
        if ( ! isset( $ticket_data['price'] ) ) {
            return new \WP_Error( 'missing_price', __( 'Ticket price is required.', 'wc-ticket-seller' ) );
        }
        
        // Prepare data
        $data = array(
            'event_id'    => $event_id,
            'type_name'   => $ticket_data['type_name'],
            'description' => isset( $ticket_data['description'] ) ? $ticket_data['description'] : '',
            'capacity'    => isset( $ticket_data['capacity'] ) ? intval( $ticket_data['capacity'] ) : 0,
            'price'       => floatval( $ticket_data['price'] ),
            'start_sale'  => isset( $ticket_data['start_sale'] ) && $ticket_data['start_sale'] ? $ticket_data['start_sale'] : null,
            'end_sale'    => isset( $ticket_data['end_sale'] ) && $ticket_data['end_sale'] ? $ticket_data['end_sale'] : null,
            'created_at'  => $now,
            'updated_at'  => $now,
        );
        
        $format = array(
            '%d', // event_id
            '%s', // type_name
            '%s', // description
            '%d', // capacity
            '%f', // price
            '%s', // start_sale
            '%s', // end_sale
            '%s', // created_at
            '%s', // updated_at
        );
        
        // Insert ticket type
        $result = $wpdb->insert(
            $ticket_types_table,
            $data,
            $format
        );
        
        if ( $result === false ) {
            return new \WP_Error( 'db_insert_error', __( 'Could not insert ticket type into the database.', 'wc-ticket-seller' ) );
        }
        
        $type_id = $wpdb->insert_id;
        
        do_action( 'wc_ticket_seller_ticket_type_added', $type_id, $data );
        
        return $type_id;
    }
    
    /**
     * Delete ticket types for an event.
     *
     * @since    1.0.0
     * @param    int      $event_id    Event ID.
     * @return   bool|WP_Error         True on success, WP_Error on failure.
     */
    public function delete_ticket_types( $event_id ) {
        global $wpdb;
        $ticket_types_table = $wpdb->prefix . 'wc_ticket_seller_ticket_types';
        
        // Delete ticket types
        $result = $wpdb->delete(
            $ticket_types_table,
            array( 'event_id' => $event_id ),
            array( '%d' )
        );
        
        if ( $result === false ) {
            return new \WP_Error( 'db_delete_error', __( 'Could not delete ticket types from the database.', 'wc-ticket-seller' ) );
        }
        
        return true;
    }
}