<?php
/**
 * The WooCommerce Ticket Product class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 */

namespace WC_Ticket_Seller\Integrations;

/**
 * The WooCommerce Ticket Product class.
 *
 * Extends the WooCommerce Product class to add ticket functionality.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 * @author     Your Name <info@yourwebsite.com>
 */
class WC_Product_Ticket extends \WC_Product {

    /**
     * Product type.
     *
     * @var string
     */
    protected $product_type = 'ticket';

    /**
     * Constructor.
     *
     * @param int|WC_Product|object $product Product ID, post object, or product object
     */
    public function __construct( $product = 0 ) {
        parent::__construct( $product );
    }

    /**
     * Get internal type.
     *
     * @return string
     */
    public function get_type() {
        return 'ticket';
    }

    /**
     * Check if a product supports a given feature.
     *
     * @param string $feature The feature being checked.
     * @return bool
     */
    public function supports( $feature ) {
        $supports = array(
            'ajax_add_to_cart'
        );

        return in_array( $feature, $supports, true ) || parent::supports( $feature );
    }

    /**
     * Returns whether or not the product is virtual.
     * Ticket products are always virtual.
     *
     * @return bool
     */
    public function is_virtual() {
        return true;
    }

    /**
     * Returns whether or not the product is downloadable.
     * Ticket products are always downloadable.
     *
     * @return bool
     */
    public function is_downloadable() {
        return true;
    }

    /**
     * Get the add to cart button text.
     *
     * @return string
     */
    public function add_to_cart_text() {
        if ( $this->is_in_stock() ) {
            return apply_filters( 'wc_ticket_seller_add_to_cart_text', __( 'Buy Tickets', 'wc-ticket-seller' ), $this );
        } else {
            return apply_filters( 'wc_ticket_seller_sold_out_text', __( 'Sold Out', 'wc-ticket-seller' ), $this );
        }
    }

    /**
     * Get the add to cart button text for the single page.
     *
     * @return string
     */
    public function single_add_to_cart_text() {
        return apply_filters( 'wc_ticket_seller_single_add_to_cart_text', __( 'Buy Tickets', 'wc-ticket-seller' ), $this );
    }

    /**
     * Get the event ID.
     *
     * @return int
     */
    public function get_event_id() {
        return $this->get_meta( '_wc_ticket_seller_event_id', true );
    }

    /**
     * Set the event ID.
     *
     * @param int $event_id
     */
    public function set_event_id( $event_id ) {
        $this->update_meta_data( '_wc_ticket_seller_event_id', $event_id );
    }

    /**
     * Get ticket types.
     *
     * @return array
     */
    public function get_ticket_types() {
        $ticket_types = $this->get_meta( '_wc_ticket_seller_ticket_types', true );
        return ! empty( $ticket_types ) ? $ticket_types : array();
    }

    /**
     * Set ticket types.
     *
     * @param array $ticket_types
     */
    public function set_ticket_types( $ticket_types ) {
        $this->update_meta_data( '_wc_ticket_seller_ticket_types', $ticket_types );
    }

    /**
     * Get seating chart ID.
     *
     * @return int
     */
    public function get_seating_chart_id() {
        return $this->get_meta( '_wc_ticket_seller_seating_chart_id', true );
    }

    /**
     * Set seating chart ID.
     *
     * @param int $chart_id
     */
    public function set_seating_chart_id( $chart_id ) {
        $this->update_meta_data( '_wc_ticket_seller_seating_chart_id', $chart_id );
    }

    /**
     * Check if seat selection is enabled.
     *
     * @return bool
     */
    public function is_seat_selection_enabled() {
        return 'yes' === $this->get_meta( '_wc_ticket_seller_enable_seat_selection', true );
    }

    /**
     * Enable or disable seat selection.
     *
     * @param bool $enabled
     */
    public function set_seat_selection_enabled( $enabled ) {
        $this->update_meta_data( '_wc_ticket_seller_enable_seat_selection', $enabled ? 'yes' : 'no' );
    }

    /**
     * Get attendee fields.
     *
     * @return array
     */
    public function get_attendee_fields() {
        $fields = $this->get_meta( '_wc_ticket_seller_attendee_fields', true );
        return ! empty( $fields ) ? $fields : array();
    }

    /**
     * Set attendee fields.
     *
     * @param array $fields
     */
    public function set_attendee_fields( $fields ) {
        $this->update_meta_data( '_wc_ticket_seller_attendee_fields', $fields );
    }

    /**
     * Get event data.
     *
     * @return array|false
     */
    public function get_event_data() {
        $event_id = $this->get_event_id();
        
        if ( ! $event_id ) {
            return false;
        }
        
        // Get event from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_ticket_seller_events';
        
        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE event_id = %d",
                $event_id
            ),
            ARRAY_A
        );
        
        return $event;
    }

    /**
     * Get event start date.
     *
     * @param string $format Date format.
     * @return string|false
     */
    public function get_event_start_date( $format = 'Y-m-d H:i:s' ) {
        $event = $this->get_event_data();
        
        if ( ! $event || empty( $event['event_start'] ) ) {
            return false;
        }
        
        return date_i18n( $format, strtotime( $event['event_start'] ) );
    }

    /**
     * Get event end date.
     *
     * @param string $format Date format.
     * @return string|false
     */
    public function get_event_end_date( $format = 'Y-m-d H:i:s' ) {
        $event = $this->get_event_data();
        
        if ( ! $event || empty( $event['event_end'] ) ) {
            return false;
        }
        
        return date_i18n( $format, strtotime( $event['event_end'] ) );
    }

    /**
     * Check if the event has already started.
     *
     * @return bool
     */
    public function has_event_started() {
        $event = $this->get_event_data();
        
        if ( ! $event || empty( $event['event_start'] ) ) {
            return false;
        }
        
        $now = current_time( 'timestamp' );
        $event_start = strtotime( $event['event_start'] );
        
        return $now >= $event_start;
    }

    /**
     * Check if the event has already ended.
     *
     * @return bool
     */
    public function has_event_ended() {
        $event = $this->get_event_data();
        
        if ( ! $event || empty( $event['event_end'] ) ) {
            return false;
        }
        
        $now = current_time( 'timestamp' );
        $event_end = strtotime( $event['event_end'] );
        
        return $now > $event_end;
    }

    /**
     * Get available tickets count.
     *
     * @return int
     */
    public function get_available_tickets_count() {
        $event = $this->get_event_data();
        
        if ( ! $event ) {
            return 0;
        }
        
        global $wpdb;
        $tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
        
        // Get sold tickets count
        $sold_tickets = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tickets_table WHERE event_id = %d AND ticket_status != 'cancelled'",
                $event['event_id']
            )
        );
        
        $capacity = ! empty( $event['event_capacity'] ) ? intval( $event['event_capacity'] ) : 0;
        $available = $capacity - intval( $sold_tickets );
        
        return max( 0, $available );
    }

    /**
     * Is the product in stock?
     *
     * @return bool
     */
    public function is_in_stock() {
        // If the event has ended, it's not in stock
        if ( $this->has_event_ended() ) {
            return false;
        }
        
        // Check available tickets
        return $this->get_available_tickets_count() > 0;
    }
}