<?php
/**
 * My Account - Tickets page
 *
 * This template can be overridden by copying it to yourtheme/wc-ticket-seller/account/tickets.php.
 *
 * @package    WC_Ticket_Seller
 * @version    1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Load ticket template
$template_loader = new WC_Ticket_Seller\Public_Area\Public_Area();
?>

<h2><?php esc_html_e( 'My Tickets', 'wc-ticket-seller' ); ?></h2>

<?php if ( ! empty( $tickets ) ) : ?>
    <div class="wc-ticket-seller-my-tickets">
        <div class="wc-ticket-seller-my-tickets-filter">
            <button class="wc-ticket-seller-filter-button active" data-filter="all">
                <?php esc_html_e( 'All', 'wc-ticket-seller' ); ?>
            </button>
            <button class="wc-ticket-seller-filter-button" data-filter="upcoming">
                <?php esc_html_e( 'Upcoming', 'wc-ticket-seller' ); ?>
            </button>
            <button class="wc-ticket-seller-filter-button" data-filter="past">
                <?php esc_html_e( 'Past', 'wc-ticket-seller' ); ?>
            </button>
        </div>
        
        <div class="wc-ticket-seller-tickets-list">
            <?php foreach ( $tickets as $ticket ) : 
                // Get event data to determine if it's past or upcoming
                $event_timestamp = strtotime( $ticket['event_end'] );
                $current_timestamp = current_time( 'timestamp' );
                $is_past = $event_timestamp < $current_timestamp;
                $status_class = $is_past ? 'past' : 'upcoming';
                
                // Additional status class for cancelled tickets
                if ( $ticket['ticket_status'] === 'cancelled' ) {
                    $status_class .= ' cancelled';
                }
            ?>
                <div class="wc-ticket-seller-ticket-item <?php echo esc_attr( $status_class ); ?>">
                    <div class="wc-ticket-seller-ticket-summary">
                        <div class="wc-ticket-seller-ticket-event">
                            <h3><?php echo esc_html( $ticket['event_name'] ); ?></h3>
                            <div class="wc-ticket-seller-ticket-date">
                                <?php 
                                echo esc_html( 
                                    date_i18n( get_option( 'date_format' ), strtotime( $ticket['event_start'] ) ) . ' ' . 
                                    date_i18n( get_option( 'time_format' ), strtotime( $ticket['event_start'] ) ) 
                                ); 
                                ?>
                            </div>
                            <div class="wc-ticket-seller-ticket-venue">
                                <?php echo esc_html( $ticket['venue_name'] ); ?>
                            </div>
                        </div>
                        
                        <div class="wc-ticket-seller-ticket-type">
                            <?php echo esc_html( $ticket['ticket_type'] ); ?>
                        </div>
                        
                        <div class="wc-ticket-seller-ticket-status">
                            <?php 
                            if ( $ticket['ticket_status'] === 'checked-in' ) {
                                echo '<span class="status-checked-in">' . esc_html__( 'Checked In', 'wc-ticket-seller' ) . '</span>';
                            } elseif ( $ticket['ticket_status'] === 'cancelled' ) {
                                echo '<span class="status-cancelled">' . esc_html__( 'Cancelled', 'wc-ticket-seller' ) . '</span>';
                            } elseif ( $is_past ) {
                                echo '<span class="status-expired">' . esc_html__( 'Expired', 'wc-ticket-seller' ) . '</span>';
                            } else {
                                echo '<span class="status-valid">' . esc_html__( 'Valid', 'wc-ticket-seller' ) . '</span>';
                            }
                            ?>
                        </div>
                        
                        <div class="wc-ticket-seller-ticket-toggle">
                            <button class="wc-ticket-seller-ticket-toggle-button" aria-expanded="false">
                                <span class="screen-reader-text"><?php esc_html_e( 'Show ticket details', 'wc-ticket-seller' ); ?></span>
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="wc-ticket-seller-ticket-details" hidden>
                        <?php
                        // Load full ticket template
                        WC_Ticket_Seller\Public_Area\Public_Area::load_template(
                            'ticket.php',
                            array( 'ticket' => $ticket )
                        );
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        (function() {
            // Toggle ticket details
            const toggleButtons = document.querySelectorAll('.wc-ticket-seller-ticket-toggle-button');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const ticketItem = this.closest('.wc-ticket-seller-ticket-item');
                    const detailsSection = ticketItem.querySelector('.wc-ticket-seller-ticket-details');
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    
                    this.setAttribute('aria-expanded', !isExpanded);
                    detailsSection.hidden = isExpanded;
                    
                    // Change icon
                    const icon = this.querySelector('.dashicons');
                    if (isExpanded) {
                        icon.classList.remove('dashicons-arrow-up-alt2');
                        icon.classList.add('dashicons-arrow-down-alt2');
                    } else {
                        icon.classList.remove('dashicons-arrow-down-alt2');
                        icon.classList.add('dashicons-arrow-up-alt2');
                    }
                });
            });
            
            // Filter tickets
            const filterButtons = document.querySelectorAll('.wc-ticket-seller-filter-button');
            const ticketItems = document.querySelectorAll('.wc-ticket-seller-ticket-item');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter tickets
                    ticketItems.forEach(item => {
                        if (filter === 'all') {
                            item.style.display = '';
                        } else if (filter === 'upcoming' && item.classList.contains('upcoming')) {
                            item.style.display = '';
                        } else if (filter === 'past' && item.classList.contains('past')) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        })();
    </script>
<?php else : ?>
    <p class="wc-ticket-seller-no-tickets">
        <?php esc_html_e( 'You have no tickets yet. Browse our events to purchase tickets.', 'wc-ticket-seller' ); ?>
        <a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="button">
            <?php esc_html_e( 'Browse Events', 'wc-ticket-seller' ); ?>
        </a>
    </p>
<?php endif; ?>