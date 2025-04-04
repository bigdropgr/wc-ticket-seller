<?php
/**
 * Event Ticket Product Add to Cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/ticket.php.
 *
 * @package    WC_Ticket_Seller
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Make sure this is a ticket product
if ( ! $product->is_type( 'ticket' ) ) {
    return;
}

// Get event ID
$event_id = $product->get_meta( '_wc_ticket_seller_event_id', true );

if ( ! $event_id ) {
    echo '<div class="woocommerce-info">' . esc_html__( 'This ticket is not linked to any event. Please contact the administrator.', 'wc-ticket-seller' ) . '</div>';
    return;
}

// Get event data
$event = new WC_Ticket_Seller\Modules\Events\Event( $event_id );

if ( ! $event->get_id() ) {
    echo '<div class="woocommerce-info">' . esc_html__( 'Event not found. Please contact the administrator.', 'wc-ticket-seller' ) . '</div>';
    return;
}

// Check if event has ended
if ( $event->has_ended() ) {
    echo '<div class="woocommerce-info">' . esc_html__( 'This event has already ended.', 'wc-ticket-seller' ) . '</div>';
    return;
}

// Check if tickets are available
$available_tickets = $product->get_available_tickets_count();
if ( $available_tickets <= 0 ) {
    echo '<div class="woocommerce-info">' . esc_html__( 'This event is sold out.', 'wc-ticket-seller' ) . '</div>';
    return;
}

// Get ticket types
$ticket_types = $product->get_ticket_types();

// Check if seat selection is enabled
$enable_seat_selection = $product->is_seat_selection_enabled();

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<div class="wc-ticket-seller-event-details">
    <h3><?php esc_html_e( 'Event Details', 'wc-ticket-seller' ); ?></h3>
    
    <div class="wc-ticket-seller-event-info">
        <p><strong><?php esc_html_e( 'Date:', 'wc-ticket-seller' ); ?></strong> <?php echo esc_html( $event->get_start( 'F j, Y' ) ); ?></p>
        <p><strong><?php esc_html_e( 'Time:', 'wc-ticket-seller' ); ?></strong> <?php echo esc_html( $event->get_start( 'g:i a' ) . ' - ' . $event->get_end( 'g:i a' ) ); ?></p>
        
        <?php if ( $event->get_venue_name() ) : ?>
            <p><strong><?php esc_html_e( 'Venue:', 'wc-ticket-seller' ); ?></strong> <?php echo esc_html( $event->get_venue_name() ); ?></p>
            <p><strong><?php esc_html_e( 'Address:', 'wc-ticket-seller' ); ?></strong> <?php echo esc_html( $event->get_venue_full_address() ); ?></p>
        <?php endif; ?>
        
        <p><strong><?php esc_html_e( 'Available Tickets:', 'wc-ticket-seller' ); ?></strong> <?php echo esc_html( $available_tickets ); ?></p>
    </div>
</div>

<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data">
    <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
    
    <?php
    // Show ticket types if there are multiple types
    if ( ! empty( $ticket_types ) ) :
    ?>
        <div class="wc-ticket-seller-ticket-types">
            <label for="ticket_type"><?php esc_html_e( 'Ticket Type', 'wc-ticket-seller' ); ?></label>
            <select name="ticket_type" id="ticket_type" class="ticket-type-select">
                <?php foreach ( $ticket_types as $type ) : ?>
                    <option value="<?php echo esc_attr( $type['type_id'] ); ?>">
                        <?php 
                        echo esc_html( $type['type_name'] . ' - ' . wc_price( $type['price'] ) ); 
                        if ( $type['capacity'] > 0 ) {
                            $type_available = min( $type['capacity'], $available_tickets );
                            echo ' (' . esc_html( sprintf( __( '%d available', 'wc-ticket-seller' ), $type_available ) ) . ')';
                        }
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    
    <?php
    // Seat selection (placeholder for now, actual implementation would be more complex)
    if ( $enable_seat_selection ) :
    ?>
        <div class="wc-ticket-seller-seat-selection">
            <p><?php esc_html_e( 'Seat selection is enabled for this event. Seats will be selected after adding to cart.', 'wc-ticket-seller' ); ?></p>
            <input type="hidden" name="seat_selection_enabled" value="1">
        </div>
    <?php endif; ?>
    
    <div class="quantity">
        <label for="quantity"><?php esc_html_e( 'Quantity', 'wc-ticket-seller' ); ?></label>
        <?php
        woocommerce_quantity_input(
            array(
                'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
                'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
                'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(),
            )
        );
        ?>
    </div>
    
    <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
    
    <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
</form>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<div class="wc-ticket-seller-event-description">
    <h3><?php esc_html_e( 'Event Description', 'wc-ticket-seller' ); ?></h3>
    <div class="wc-ticket-seller-event-description-content">
        <?php echo wp_kses_post( wpautop( $event->get_description() ) ); ?>
    </div>
</div>