<?php
/**
 * Ticket template
 *
 * This template can be overridden by copying it to yourtheme/wc-ticket-seller/ticket.php.
 *
 * @package    WC_Ticket_Seller
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Ticket data must be available
if ( empty( $ticket ) ) {
    return;
}

// Get event data
$event = new WC_Ticket_Seller\Modules\Events\Event( $ticket['event_id'] );

// Generate QR code data
$qr_data = array(
    'ticket_id'   => $ticket['ticket_id'],
    'ticket_code' => $ticket['ticket_code'],
    'event_id'    => $ticket['event_id'],
);

$qr_data = json_encode( $qr_data );
$qr_data = base64_encode( $qr_data );
$qr_id = 'qrcode-' . $ticket['ticket_id'];
?>

<div class="wc-ticket-seller-ticket">
    <div class="wc-ticket-seller-ticket-header">
        <?php if ( has_custom_logo() ) : ?>
            <div class="wc-ticket-seller-ticket-logo">
                <?php the_custom_logo(); ?>
            </div>
        <?php else : ?>
            <div class="wc-ticket-seller-ticket-site-title">
                <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
            </div>
        <?php endif; ?>
        
        <h2 class="wc-ticket-seller-ticket-title"><?php echo esc_html( $event->get_name() ); ?></h2>
    </div>
    
    <div class="wc-ticket-seller-ticket-details">
        <div class="wc-ticket-seller-ticket-info">
            <div class="wc-ticket-seller-ticket-info-item">
                <span class="wc-ticket-seller-ticket-label"><?php esc_html_e( 'Date:', 'wc-ticket-seller' ); ?></span>
                <span class="wc-ticket-seller-ticket-value"><?php echo esc_html( $event->get_start( 'F j, Y' ) ); ?></span>
            </div>
            
            <div class="wc-ticket-seller-ticket-info-item">
                <span class="wc-ticket-seller-ticket-label"><?php esc_html_e( 'Time:', 'wc-ticket-seller' ); ?></span>
                <span class="wc-ticket-seller-ticket-value">
                    <?php 
                    echo esc_html( $event->get_start( 'g:i a' ) . ' - ' . $event->get_end( 'g:i a' ) ); 
                    ?>
                </span>
            </div>
            
            <?php if ( $event->get_venue_name() ) : ?>
                <div class="wc-ticket-seller-ticket-info-item">
                    <span class="wc-ticket-seller-ticket-label"><?php esc_html_e( 'Venue:', 'wc-ticket-seller' ); ?></span>
                    <span class="wc-ticket-seller-ticket-value"><?php echo esc_html( $event->get_venue_name() ); ?></span>
                </div>
                
                <div class="wc-ticket-seller-ticket-info-item">
                    <span class="wc-ticket-seller-ticket-label"><?php esc_html_e( 'Address:', 'wc-ticket-seller' ); ?></span>
                    <span class="wc-ticket-seller-ticket-value"><?php echo esc_html( $event->get_venue_full_address() ); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="wc-ticket-seller-ticket-info-item">
                <span class="wc-ticket-seller-ticket-label"><?php esc_html_e( 'Ticket Type:', 'wc-ticket-seller' ); ?></span>
                <span class="wc-ticket-seller-ticket-value"><?php echo esc_html( $ticket['ticket_type'] ); ?></span>
            </div>
            
            <div class="wc-ticket-seller-ticket-info-item">
                <span class="wc-ticket-seller-ticket-label"><?php esc_html_e( 'Attendee:', 'wc-ticket-seller' ); ?></span>
                <span class="wc-ticket-seller-ticket-value"><?php echo esc_html( $ticket['first_name'] . ' ' . $ticket['last_name'] ); ?></span>
            </div>
            
            <?php if ( ! empty( $ticket['seat_id'] ) && ! empty( $ticket['seat'] ) ) : ?>
                <div class="wc-ticket-seller-ticket-info-item">
                    <span class="wc-ticket-seller-ticket-label"><?php esc_html_e( 'Seat:', 'wc-ticket-seller' ); ?></span>
                    <span class="wc-ticket-seller-ticket-value">
                        <?php 
                        echo esc_html( 
                            $ticket['seat']['section_name'] . ', ' . 
                            __( 'Row', 'wc-ticket-seller' ) . ' ' . $ticket['seat']['row_name'] . ', ' . 
                            __( 'Seat', 'wc-ticket-seller' ) . ' ' . $ticket['seat']['seat_number'] 
                        ); 
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="wc-ticket-seller-ticket-info-item">
                <span class="wc-ticket-seller-ticket-label"><?php esc_html_e( 'Ticket Code:', 'wc-ticket-seller' ); ?></span>
                <span class="wc-ticket-seller-ticket-value"><?php echo esc_html( $ticket['ticket_code'] ); ?></span>
            </div>
        </div>
        
        <div class="wc-ticket-seller-ticket-qr">
            <div id="<?php echo esc_attr( $qr_id ); ?>" class="wc-ticket-seller-ticket-qrcode"></div>
            <script>
                (function() {
                    if (typeof QRCode !== 'undefined') {
                        new QRCode(document.getElementById("<?php echo esc_js( $qr_id ); ?>"), {
                            text: "<?php echo esc_js( $qr_data ); ?>",
                            width: 150,
                            height: 150,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    }
                })();
            </script>
        </div>
    </div>
    
    <div class="wc-ticket-seller-ticket-actions">
        <a href="<?php echo esc_url( add_query_arg( array(
            'action' => 'wc_ticket_seller_download_ticket',
            'ticket_id' => $ticket['ticket_id'],
            'format' => 'pdf',
            'nonce' => wp_create_nonce( 'wc_ticket_seller_public_nonce' )
        ), admin_url( 'admin-ajax.php' ) ) ); ?>" class="wc-ticket-seller-ticket-download-pdf">
            <?php esc_html_e( 'Download PDF', 'wc-ticket-seller' ); ?>
        </a>
        
        <?php if ( get_option( 'wc_ticket_seller_enable_passbook', 'no' ) === 'yes' ) : ?>
            <a href="<?php echo esc_url( add_query_arg( array(
                'action' => 'wc_ticket_seller_download_ticket',
                'ticket_id' => $ticket['ticket_id'],
                'format' => 'passbook',
                'nonce' => wp_create_nonce( 'wc_ticket_seller_public_nonce' )
            ), admin_url( 'admin-ajax.php' ) ) ); ?>" class="wc-ticket-seller-ticket-download-passbook">
                <?php esc_html_e( 'Add to Apple Wallet', 'wc-ticket-seller' ); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <div class="wc-ticket-seller-ticket-footer">
        <p class="wc-ticket-seller-ticket-note"><?php esc_html_e( 'This ticket is valid only for the event, date and time specified. Please present this ticket at the entrance.', 'wc-ticket-seller' ); ?></p>
        
        <?php
        // Ticket terms if set
        $ticket_terms = get_option( 'wc_ticket_seller_ticket_terms', '' );
        if ( ! empty( $ticket_terms ) ) :
        ?>
            <div class="wc-ticket-seller-ticket-terms">
                <?php echo wp_kses_post( wpautop( $ticket_terms ) ); ?>
            </div>
        <?php endif; ?>
    </div>
</div>