<?php
/**
 * The Event class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Events
 */

namespace WC_Ticket_Seller\Modules\Events;

/**
 * The Event class.
 *
 * Represents a single event.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Modules/Events
 * @author     Your Name <info@yourwebsite.com>
 */
class Event {

    /**
     * Event ID.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $id    Event ID.
     */
    private $id;

    /**
     * Event name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $name    Event name.
     */
    private $name;

    /**
     * Event description.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $description    Event description.
     */
    private $description;

    /**
     * Event start time.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $start    Event start time (MySQL datetime format).
     */
    private $start;

    /**
     * Event end time.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $end    Event end time (MySQL datetime format).
     */
    private $end;

    /**
     * Venue name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $venue_name    Venue name.
     */
    private $venue_name;

    /**
     * Venue address.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $venue_address    Venue address.
     */
    private $venue_address;

    /**
     * Venue city.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $venue_city    Venue city.
     */
    private $venue_city;

    /**
     * Venue state/province.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $venue_state    Venue state/province.
     */
    private $venue_state;

    /**
     * Venue country.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $venue_country    Venue country.
     */
    private $venue_country;

    /**
     * Venue postal code.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $venue_postcode    Venue postal code.
     */
    private $venue_postcode;

    /**
     * Event status.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $status    Event status (draft, published, cancelled).
     */
    private $status;

    /**
     * Event capacity.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $capacity    Event capacity.
     */
    private $capacity;

    /**
     * Organizer user ID.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $organizer_id    Organizer user ID.
     */
    private $organizer_id;

    /**
     * Creation timestamp.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $created_at    Creation timestamp (MySQL datetime format).
     */
    private $created_at;

    /**
     * Last update timestamp.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $updated_at    Last update timestamp (MySQL datetime format).
     */
    private $updated_at;

    /**
     * Ticket types.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $ticket_types    Ticket types.
     */
    private $ticket_types = array();

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    int|array    $event    Event ID or event data.
     */
    public function __construct( $event = 0 ) {
        if ( is_numeric( $event ) && $event > 0 ) {
            $this->load_event( $event );
        } elseif ( is_array( $event ) ) {
            $this->populate_data( $event );
        }
    }

    /**
     * Load event from database.
     *
     * @since    1.0.0
     * @param    int    $event_id    Event ID.
     * @return   bool                True on success, false on failure.
     */
    private function load_event( $event_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_ticket_seller_events';
        
        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE event_id = %d",
                $event_id
            ),
            ARRAY_A
        );
        
        if ( ! $event ) {
            return false;
        }
        
        $this->populate_data( $event );
        $this->load_ticket_types();
        
        return true;
    }

    /**
     * Populate object data from array.
     *
     * @since    1.0.0
     * @param    array    $data    Event data.
     */
    private function populate_data( $data ) {
        $this->id = isset( $data['event_id'] ) ? intval( $data['event_id'] ) : 0;
        $this->name = isset( $data['event_name'] ) ? $data['event_name'] : '';
        $this->description = isset( $data['event_description'] ) ? $data['event_description'] : '';
        $this->start = isset( $data['event_start'] ) ? $data['event_start'] : '';
        $this->end = isset( $data['event_end'] ) ? $data['event_end'] : '';
        $this->venue_name = isset( $data['venue_name'] ) ? $data['venue_name'] : '';
        $this->venue_address = isset( $data['venue_address'] ) ? $data['venue_address'] : '';
        $this->venue_city = isset( $data['venue_city'] ) ? $data['venue_city'] : '';
        $this->venue_state = isset( $data['venue_state'] ) ? $data['venue_state'] : '';
        $this->venue_country = isset( $data['venue_country'] ) ? $data['venue_country'] : '';
        $this->venue_postcode = isset( $data['venue_postcode'] ) ? $data['venue_postcode'] : '';
        $this->status = isset( $data['event_status'] ) ? $data['event_status'] : 'draft';
        $this->capacity = isset( $data['event_capacity'] ) ? intval( $data['event_capacity'] ) : 0;
        $this->organizer_id = isset( $data['organizer_id'] ) ? intval( $data['organizer_id'] ) : 0;
        $this->created_at = isset( $data['created_at'] ) ? $data['created_at'] : current_time( 'mysql' );
        $this->updated_at = isset( $data['updated_at'] ) ? $data['updated_at'] : current_time( 'mysql' );
    }

    /**
     * Load ticket types from database.
     *
     * @since    1.0.0
     */
    private function load_ticket_types() {
        if ( ! $this->id ) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_ticket_seller_ticket_types';
        
        $this->ticket_types = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE event_id = %d ORDER BY price ASC",
                $this->id
            ),
            ARRAY_A
        );
    }

    /**
     * Get event ID.
     *
     * @since    1.0.0
     * @return   int    Event ID.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get event name.
     *
     * @since    1.0.0
     * @return   string    Event name.
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Set event name.
     *
     * @since    1.0.0
     * @param    string    $name    Event name.
     */
    public function set_name( $name ) {
        $this->name = $name;
    }

    /**
     * Get event description.
     *
     * @since    1.0.0
     * @return   string    Event description.
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Set event description.
     *
     * @since    1.0.0
     * @param    string    $description    Event description.
     */
    public function set_description( $description ) {
        $this->description = $description;
    }

    /**
     * Get event start time.
     *
     * @since    1.0.0
     * @param    string    $format    Optional. Date format.
     * @return   string               Event start time.
     */
    public function get_start( $format = '' ) {
        if ( ! empty( $format ) ) {
            return date_i18n( $format, strtotime( $this->start ) );
        }
        
        return $this->start;
    }

    /**
     * Set event start time.
     *
     * @since    1.0.0
     * @param    string    $start    Event start time.
     */
    public function set_start( $start ) {
        $this->start = $start;
    }

    /**
     * Get event end time.
     *
     * @since    1.0.0
     * @param    string    $format    Optional. Date format.
     * @return   string               Event end time.
     */
    public function get_end( $format = '' ) {
        if ( ! empty( $format ) ) {
            return date_i18n( $format, strtotime( $this->end ) );
        }
        
        return $this->end;
    }

    /**
     * Set event end time.
     *
     * @since    1.0.0
     * @param    string    $end    Event end time.
     */
    public function set_end( $end ) {
        $this->end = $end;
    }

    /**
     * Get venue name.
     *
     * @since    1.0.0
     * @return   string    Venue name.
     */
    public function get_venue_name() {
        return $this->venue_name;
    }

    /**
     * Set venue name.
     *
     * @since    1.0.0
     * @param    string    $venue_name    Venue name.
     */
    public function set_venue_name( $venue_name ) {
        $this->venue_name = $venue_name;
    }

    /**
     * Get full venue address.
     *
     * @since    1.0.0
     * @return   string    Full venue address.
     */
    public function get_venue_full_address() {
        $address_parts = array();
        
        if ( ! empty( $this->venue_address ) ) {
            $address_parts[] = $this->venue_address;
        }
        
        if ( ! empty( $this->venue_city ) ) {
            $address_parts[] = $this->venue_city;
        }
        
        if ( ! empty( $this->venue_state ) ) {
            $address_parts[] = $this->venue_state;
        }
        
        if ( ! empty( $this->venue_postcode ) ) {
            $address_parts[] = $this->venue_postcode;
        }
        
        if ( ! empty( $this->venue_country ) ) {
            $address_parts[] = $this->venue_country;
        }
        
        return implode( ', ', $address_parts );
    }

    /**
     * Get event status.
     *
     * @since    1.0.0
     * @return   string    Event status.
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Set event status.
     *
     * @since    1.0.0
     * @param    string    $status    Event status.
     */
    public function set_status( $status ) {
        $this->status = $status;
    }

    /**
     * Get event capacity.
     *
     * @since    1.0.0
     * @return   int    Event capacity.
     */
    public function get_capacity() {
        return $this->capacity;
    }

    /**
     * Set event capacity.
     *
     * @since    1.0.0
     * @param    int    $capacity    Event capacity.
     */
    public function set_capacity( $capacity ) {
        $this->capacity = intval( $capacity );
    }

    /**
     * Get organizer ID.
     *
     * @since    1.0.0
     * @return   int    Organizer ID.
     */
    public function get_organizer_id() {
        return $this->organizer_id;
    }

    /**
     * Get organizer name.
     *
     * @since    1.0.0
     * @return   string    Organizer name.
     */
    public function get_organizer_name() {
        if ( ! $this->organizer_id ) {
            return '';
        }
        
        $user = get_userdata( $this->organizer_id );
        return $user ? $user->display_name : '';
    }

    /**
     * Get creation timestamp.
     *
     * @since    1.0.0
     * @param    string    $format    Optional. Date format.
     * @return   string               Creation timestamp.
     */
    public function get_created_at( $format = '' ) {
        if ( ! empty( $format ) ) {
            return date_i18n( $format, strtotime( $this->created_at ) );
        }
        
        return $this->created_at;
    }

    /**
     * Get last update timestamp.
     *
     * @since    1.0.0
     * @param    string    $format    Optional. Date format.
     * @return   string               Last update timestamp.
     */
    public function get_updated_at( $format = '' ) {
        if ( ! empty( $format ) ) {
            return date_i18n( $format, strtotime( $this->updated_at ) );
        }
        
        return $this->updated_at;
    }

    /**
     * Get ticket types.
     *
     * @since    1.0.0
     * @return   array    Ticket types.
     */
    public function get_ticket_types() {
        return $this->ticket_types;
    }

    /**
     * Get ticket type by ID.
     *
     * @since    1.0.0
     * @param    int      $type_id    Ticket type ID.
     * @return   array|null           Ticket type data or null if not found.
     */
    public function get_ticket_type( $type_id ) {
        foreach ( $this->ticket_types as $ticket_type ) {
            if ( $ticket_type['type_id'] == $type_id ) {
                return $ticket_type;
            }
        }
        
        return null;
    }

    /**
     * Check if the event has already started.
     *
     * @since    1.0.0
     * @return   bool    True if the event has started, false otherwise.
     */
    public function has_started() {
        $now = current_time( 'timestamp' );
        $event_start = strtotime( $this->start );
        
        return $now >= $event_start;
    }

    /**
     * Check if the event has already ended.
     *
     * @since    1.0.0
     * @return   bool    True if the event has ended, false otherwise.
     */
    public function has_ended() {
        $now = current_time( 'timestamp' );
        $event_end = strtotime( $this->end );
        
        return $now > $event_end;
    }

    /**
     * Get available tickets count.
     *
     * @since    1.0.0
     * @return   int      Available tickets count.
     */
    public function get_available_tickets_count() {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        // Get sold tickets count
        $sold_tickets = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tickets_table WHERE event_id = %d AND ticket_status != 'cancelled'",
                $this->id
            )
        );
        
        $available = $this->capacity - intval( $sold_tickets );
        return max( 0, $available );
    }

    /**
     * Get sold tickets count.
     *
     * @since    1.0.0
     * @return   int      Sold tickets count.
     */
    public function get_sold_tickets_count() {
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        // Get sold tickets count
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tickets_table WHERE event_id = %d AND ticket_status != 'cancelled'",
                $this->id
            )
        );
    }

    /**
     * Check if tickets are available.
     *
     * @since    1.0.0
     * @return   bool    True if tickets are available, false otherwise.
     */
    public function has_available_tickets() {
        if ( $this->has_ended() ) {
            return false;
        }
        
        return $this->get_available_tickets_count() > 0;
    }

    /**
     * Get event data as array.
     *
     * @since    1.0.0
     * @return   array    Event data.
     */
    public function to_array() {
        return array(
            'event_id'          => $this->id,
            'event_name'        => $this->name,
            'event_description' => $this->description,
            'event_start'       => $this->start,
            'event_end'         => $this->end,
            'venue_name'        => $this->venue_name,
            'venue_address'     => $this->venue_address,
            'venue_city'        => $this->venue_city,
            'venue_state'       => $this->venue_state,
            'venue_country'     => $this->venue_country,
            'venue_postcode'    => $this->venue_postcode,
            'event_status'      => $this->status,
            'event_capacity'    => $this->capacity,
            'organizer_id'      => $this->organizer_id,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'ticket_types'      => $this->ticket_types,
            'available_tickets' => $this->get_available_tickets_count(),
            'sold_tickets'      => $this->get_sold_tickets_count(),
            'has_started'       => $this->has_started(),
            'has_ended'         => $this->has_ended(),
        );
    }

    /**
     * Save event to database.
     *
     * @since    1.0.0
     * @return   int|WP_Error    Event ID on success, WP_Error on failure.
     */
    public function save() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_ticket_seller_events';
        $now = current_time( 'mysql' );
        
        $data = array(
            'event_name'        => $this->name,
            'event_description' => $this->description,
            'event_start'       => $this->start,
            'event_end'         => $this->end,
            'venue_name'        => $this->venue_name,
            'venue_address'     => $this->venue_address,
            'venue_city'        => $this->venue_city,
            'venue_state'       => $this->venue_state,
            'venue_country'     => $this->venue_country,
            'venue_postcode'    => $this->venue_postcode,
            'event_status'      => $this->status,
            'event_capacity'    => $this->capacity,
            'organizer_id'      => $this->organizer_id,
            'updated_at'        => $now,
        );
        
        $format = array(
            '%s', // event_name
            '%s', // event_description
            '%s', // event_start
            '%s', // event_end
            '%s', // venue_name
            '%s', // venue_address
            '%s', // venue_city
            '%s', // venue_state
            '%s', // venue_country
            '%s', // venue_postcode
            '%s', // event_status
            '%d', // event_capacity
            '%d', // organizer_id
            '%s', // updated_at
        );
        
        if ( $this->id ) {
            // Update existing event
            $result = $wpdb->update(
                $table_name,
                $data,
                array( 'event_id' => $this->id ),
                $format,
                array( '%d' )
            );
            
            if ( $result === false ) {
                return new \WP_Error( 'db_update_error', __( 'Could not update event in the database.', 'wc-ticket-seller' ) );
            }
            
            do_action( 'wc_ticket_seller_event_updated', $this->id, $this->to_array() );
            
            return $this->id;
        } else {
            // Insert new event
            $data['created_at'] = $now;
            $format[] = '%s'; // created_at
            
            $result = $wpdb->insert(
                $table_name,
                $data,
                $format
            );
            
            if ( $result === false ) {
                return new \WP_Error( 'db_insert_error', __( 'Could not insert event into the database.', 'wc-ticket-seller' ) );
            }
            
            $this->id = $wpdb->insert_id;
            
            do_action( 'wc_ticket_seller_event_created', $this->id, $this->to_array() );
            
            return $this->id;
        }
    }
}