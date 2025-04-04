<?php
/**
 * Tickets email template
 *
 * This template can be overridden by copying it to yourtheme/wc-ticket-seller/emails/tickets.php.
 *
 * @package    WC_Ticket_Seller
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php esc_html_e( 'Thank you for your order. Your tickets are attached to this email and also accessible from your account.', 'wc-ticket-seller' ); ?></p>

<?php
// Get tickets for this order
global $wpdb;
$tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';
$events_table = $wpdb->prefix . 'wc_ticket_seller_events';

$tickets = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT t.*, e.event_name, e.event_start, e.event_end, e.venue_name 
         FROM $tickets_table AS t 
         LEFT JOIN $events_table AS e ON t.event_id = e.event_id 
         WHERE t.order_id = %d
         ORDER BY e.event_start ASC",
        $order->get_id()
    ),
    ARRAY_A
);
?>

<?php if ( ! empty( $tickets ) ) : ?>
    <h2><?php esc_html_e( 'Your Tickets', 'wc-ticket-seller' ); ?></h2>
    
    <div style="margin-bottom: 40px;">
        <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #e5e5e5;" border="1">
            <thead>
                <tr>
                    <th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Event', 'wc-ticket-seller' ); ?></th>
                    <th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Date & Time', 'wc-ticket-seller' ); ?></th>
                    <th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Ticket Type', 'wc-ticket-seller' ); ?></th>
                    <th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Attendee', 'wc-ticket-seller' ); ?></th>
                    <th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Ticket Code', 'wc-ticket-seller' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $tickets as $ticket ) : ?>
                    <tr>
                        <td class="td" style="text-align:left; vertical-align:middle;">
                            <?php echo esc_html( $ticket['event_name'] ); ?>
                            <br>
                            <small><?php echo esc_html( $ticket['venue_name'] ); ?></small>
                        </td>
                        <td class="td" style="text-align:left; vertical-align:middle;">
                            <?php 
                            echo esc_html( 
                                date_i18n( get_option( 'date_format' ), strtotime( $ticket['event_start'] ) ) 
                            ); 
                            ?>
                            <br>
                            <?php 
                            echo esc_html( 
                                date_i18n( get_option( 'time_format' ), strtotime( $ticket['event_start'] ) ) . ' - ' .
                                date_i18n( get_option( 'time_format' ), strtotime( $ticket['event_end'] ) )
                            ); 
                            ?>
                        </td>
                        <td class="td" style="text-align:left; vertical-align:middle;">
                            <?php echo esc_html( $ticket['ticket_type'] ); ?>
                            <?php if ( ! empty( $ticket['seat_id'] ) ) : 
                                // Get seat details
                                $seats_table = $wpdb->prefix . 'wc_ticket_seller_seats';
                                $sections_table = $wpdb->prefix . 'wc_ticket_seller_sections';
                                
                                $seat = $wpdb->get_row(
                                    $wpdb->prepare(
                                        "SELECT s.*, sec.section_name 
                                         FROM $seats_table AS s
                                         LEFT JOIN $sections_table AS sec ON s.section_id = sec.section_id
                                         WHERE s.seat_id = %d",
                                        $ticket['seat_id']
                                    ),
                                    ARRAY_A
                                );
                                
                                if ( $seat ) :
                            ?>
                                <br>
                                <small>
                                    <?php 
                                    echo esc_html( 
                                        $seat['section_name'] . ', ' . 
                                        __( 'Row', 'wc-ticket-seller' ) . ' ' . $seat['row_name'] . ', ' . 
                                        __( 'Seat', 'wc-ticket-seller' ) . ' ' . $seat['seat_number'] 
                                    ); 
                                    ?>
                                </small>
                            <?php endif; endif; ?>
                        </td>
                        <td class="td" style="text-align:left; vertical-align:middle;">
                            <?php echo esc_html( $ticket['first_name'] . ' ' . $ticket['last_name'] ); ?>
                        </td>
                        <td class="td" style="text-align:left; vertical-align:middle;">
                            <?php echo esc_html( $ticket['ticket_code'] ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p>
        <?php 
        printf(
            /* translators: %s: My account link */
            esc_html__( 'You can view and download your tickets at any time in your %s.', 'wc-ticket-seller' ),
            sprintf( '<a href="%s">%s</a>', esc_url( wc_get_endpoint_url( 'tickets', '', wc_get_page_permalink( 'myaccount' ) ) ), esc_html__( 'account', 'wc-ticket-seller' ) )
        ); 
        ?>
    </p>

    <p>
        <?php esc_html_e( 'We\'ve attached your tickets to this email as PDF. Please bring them to the event, either printed or on your mobile device.', 'wc-ticket-seller' ); ?>
    </p>
<?php endif; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );