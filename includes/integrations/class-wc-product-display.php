<?php
/**
 * The WooCommerce Product Display class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 */

namespace WC_Ticket_Seller\Integrations;

/**
 * The WooCommerce Product Display class.
 *
 * Handles display templates for ticket products.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 * @author     Your Name <info@yourwebsite.com>
 */
class WC_Product_Display {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Register hooks
        $this->register_hooks();
    }

    /**
     * Register hooks.
     *
     * @since    1.0.0
     */
    private function register_hooks() {
        // Add custom template for ticket product
        add_action('woocommerce_ticket_add_to_cart', array($this, 'ticket_add_to_cart'), 10);
        
        // Add ticket tab
        add_filter('woocommerce_product_tabs', array($this, 'add_ticket_information_tab'), 10);
        
        // Add CSS for ticket product page
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Enqueue styles for ticket product display.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        global $post;
        
        // Only load styles on single product pages
        if (is_product() && $post) {
            $product = wc_get_product($post->ID);
            
            if ($product && $product->is_type('ticket')) {
                wp_enqueue_style(
                    'wc-ticket-seller-product',
                    WC_TICKET_SELLER_PLUGIN_URL . 'public/css/product.css',
                    array(),
                    WC_TICKET_SELLER_VERSION
                );
            }
        }
    }

    /**
     * Output the ticket add to cart area.
     *
     * @since    1.0.0
     */
    public function ticket_add_to_cart() {
        // Load ticket template
        wc_get_template(
            'single-product/add-to-cart/ticket.php',
            array(),
            '',
            WC_TICKET_SELLER_PLUGIN_DIR . 'templates/'
        );
    }

    /**
     * Add ticket information tab on single product page.
     *
     * @since    1.0.0
     * @param    array    $tabs    Product tabs.
     * @return   array             Modified product tabs.
     */
    public function add_ticket_information_tab($tabs) {
        global $product;
        
        if (!$product || !$product->is_type('ticket')) {
            return $tabs;
        }
        
        // Add Event Information tab
        $tabs['event_information'] = array(
            'title'    => __('Event Information', 'wc-ticket-seller'),
            'priority' => 15,
            'callback' => array($this, 'event_information_tab_content')
        );
        
        // Add Venue Information tab if venue exists
        $event_id = $product->get_meta('_wc_ticket_seller_event_id', true);
        if ($event_id) {
            $event = new \WC_Ticket_Seller\Modules\Events\Event($event_id);
            
            if ($event->get_id() && $event->get_venue_name()) {
                $tabs['venue_information'] = array(
                    'title'    => __('Venue Information', 'wc-ticket-seller'),
                    'priority' => 20,
                    'callback' => array($this, 'venue_information_tab_content')
                );
            }
        }
        
        // Add Ticket Terms tab if terms exist
        $ticket_terms = get_option('wc_ticket_seller_ticket_terms', '');
        if (!empty($ticket_terms)) {
            $tabs['ticket_terms'] = array(
                'title'    => __('Ticket Terms', 'wc-ticket-seller'),
                'priority' => 30,
                'callback' => array($this, 'ticket_terms_tab_content')
            );
        }
        
        return $tabs;
    }

    /**
     * Event Information tab content.
     *
     * @since    1.0.0
     */
    public function event_information_tab_content() {
        global $product;
        
        $event_id = $product->get_meta('_wc_ticket_seller_event_id', true);
        
        if (!$event_id) {
            echo '<p>' . esc_html__('No event information available.', 'wc-ticket-seller') . '</p>';
            return;
        }
        
        $event = new \WC_Ticket_Seller\Modules\Events\Event($event_id);
        
        if (!$event->get_id()) {
            echo '<p>' . esc_html__('Event not found.', 'wc-ticket-seller') . '</p>';
            return;
        }
        
        ?>
        <h2><?php echo esc_html($event->get_name()); ?></h2>
        
        <div class="wc-ticket-seller-event-details-tab">
            <p><strong><?php esc_html_e('Date:', 'wc-ticket-seller'); ?></strong> <?php echo esc_html($event->get_start('F j, Y')); ?></p>
            <p><strong><?php esc_html_e('Time:', 'wc-ticket-seller'); ?></strong> <?php echo esc_html($event->get_start('g:i a') . ' - ' . $event->get_end('g:i a')); ?></p>
            
            <?php if ($event->get_description()) : ?>
                <div class="wc-ticket-seller-event-description-tab">
                    <?php echo wp_kses_post(wpautop($event->get_description())); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Venue Information tab content.
     *
     * @since    1.0.0
     */
    public function venue_information_tab_content() {
        global $product;
        
        $event_id = $product->get_meta('_wc_ticket_seller_event_id', true);
        
        if (!$event_id) {
            echo '<p>' . esc_html__('No venue information available.', 'wc-ticket-seller') . '</p>';
            return;
        }
        
        $event = new \WC_Ticket_Seller\Modules\Events\Event($event_id);
        
        if (!$event->get_id() || !$event->get_venue_name()) {
            echo '<p>' . esc_html__('Venue information not available.', 'wc-ticket-seller') . '</p>';
            return;
        }
        
        ?>
        <h2><?php echo esc_html($event->get_venue_name()); ?></h2>
        
        <div class="wc-ticket-seller-venue-details">
            <?php if ($event->get_venue_full_address()) : ?>
                <p><strong><?php esc_html_e('Address:', 'wc-ticket-seller'); ?></strong> <?php echo esc_html($event->get_venue_full_address()); ?></p>
            <?php endif; ?>
            
            <?php
            // Add Google Maps embed if available (you would need to implement this)
            do_action('wc_ticket_seller_venue_map', $event->get_id());
            ?>
        </div>
        <?php
    }

    /**
     * Ticket Terms tab content.
     *
     * @since    1.0.0
     */
    public function ticket_terms_tab_content() {
        $ticket_terms = get_option('wc_ticket_seller_ticket_terms', '');
        
        if (empty($ticket_terms)) {
            echo '<p>' . esc_html__('No ticket terms available.', 'wc-ticket-seller') . '</p>';
            return;
        }
        
        ?>
        <h2><?php esc_html_e('Ticket Terms & Conditions', 'wc-ticket-seller'); ?></h2>
        
        <div class="wc-ticket-seller-ticket-terms-content">
            <?php echo wp_kses_post(wpautop($ticket_terms)); ?>
        </div>
        <?php
    }
}

// Initialize the class
new WC_Product_Display();