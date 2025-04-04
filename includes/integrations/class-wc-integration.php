<?php
/**
 * The WooCommerce integration functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 */

namespace WC_Ticket_Seller\Integrations;

/**
 * The WooCommerce integration functionality of the plugin.
 *
 * Handles the integration with WooCommerce core.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 * @author     BigDrop <info@bigdrop.gr>
 */
class WC_Integration {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Flag to track if WooCommerce dependencies are available.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $dependencies_available    Whether WooCommerce dependencies are available.
     */
    private $dependencies_available = false;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Check if WooCommerce core classes are available
        $this->dependencies_available = class_exists('WC_Product') && class_exists('WC_Order');
        
        // Only load dependencies if WooCommerce is available
        if ($this->dependencies_available) {
            $this->load_dependencies();
        } else {
            add_action('admin_notices', array($this, 'wc_missing_notice'));
        }
    }
    
    /**
     * Display notice if WooCommerce classes are not available.
     *
     * @since    1.0.0
     */
    public function wc_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e('WooCommerce Ticket Seller: WooCommerce core classes not available. Some functionality may not work correctly.', 'wc-ticket-seller');
        echo '</p></div>';
    }
    
    /**
     * Load WooCommerce dependencies.
     *
     * @since    1.0.0
     */
    private function load_dependencies() {
        // Product type
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/integrations/class-wc-product-ticket.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/integrations/class-wc-order-handler.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/integrations/class-wc-cart-handler.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/integrations/class-wc-product-data-fields.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/integrations/class-wc-product-display.php';
        
        // Email class - only load if WC_Email exists
        if (class_exists('WC_Email')) {
            require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/integrations/class-wc-email-tickets.php';
        }
    }

    /**
     * Add the ticket product type to WooCommerce.
     *
     * @since     1.0.0
     * @param     array    $product_types    Existing product types.
     * @return    array                      Modified product types.
     */
    public function add_ticket_product_type($product_types) {
        if (!$this->dependencies_available) {
            return $product_types;
        }
        
        $product_types['ticket'] = __('Event Ticket', 'wc-ticket-seller');
        return $product_types;
    }

    /**
     * Set the product class for the ticket product type.
     *
     * @since     1.0.0
     * @param     string    $classname    Product class name.
     * @param     string    $product_type Product type.
     * @return    string                  Modified class name.
     */
    public function set_ticket_product_class($classname, $product_type) {
        if (!$this->dependencies_available) {
            return $classname;
        }
        
        if ($product_type === 'ticket') {
            $classname = 'WC_Ticket_Seller\Integrations\WC_Product_Ticket';
        }
        return $classname;
    }

    /**
     * Process a completed ticket order.
     *
     * @since    1.0.0
     * @param    int    $order_id    WooCommerce order ID.
     */
    public function process_completed_ticket_order($order_id) {
        if (!$this->dependencies_available) {
            return;
        }
        
        $order_handler = new WC_Order_Handler();
        $order_handler->process_ticket_order($order_id);
    }

    /**
     * Cancel tickets for a cancelled or refunded order.
     *
     * @since    1.0.0
     * @param    int    $order_id    WooCommerce order ID.
     */
    public function cancel_ticket_order($order_id) {
        if (!$this->dependencies_available) {
            return;
        }
        
        $order_handler = new WC_Order_Handler();
        $order_handler->cancel_ticket_order($order_id);
    }

    /**
     * Validate ticket products when added to cart.
     *
     * @since     1.0.0
     * @param     bool     $passed        Whether validation passed.
     * @param     int      $product_id    Product ID.
     * @param     int      $quantity      Quantity.
     * @param     int      $variation_id  Variation ID.
     * @param     array    $variations    Variations.
     * @return    bool                    Whether validation passed.
     */
    public function validate_ticket_add_to_cart($passed, $product_id, $quantity, $variation_id = 0, $variations = array()) {
        if (!$this->dependencies_available) {
            return $passed;
        }
        
        $product = wc_get_product($product_id);
        
        // Skip if not a ticket product
        if (!$product || $product->get_type() !== 'ticket') {
            return $passed;
        }
        
        $cart_handler = new WC_Cart_Handler();
        return $cart_handler->validate_add_to_cart($passed, $product, $quantity, $variations);
    }

    /**
     * Add custom ticket checkout fields.
     *
     * @since     1.0.0
     * @param     array    $fields    Checkout fields.
     * @return    array               Modified checkout fields.
     */
    public function add_ticket_checkout_fields($fields) {
        if (!$this->dependencies_available) {
            return $fields;
        }
        
        $cart_handler = new WC_Cart_Handler();
        return $cart_handler->add_checkout_fields($fields);
    }

    /**
     * Add ticket email classes.
     *
     * @since     1.0.0
     * @param     array    $email_classes    Email classes.
     * @return    array                     Modified email classes.
     */
    public function add_ticket_email_classes($email_classes) {
        if (!$this->dependencies_available || !class_exists('WC_Email')) {
            return $email_classes;
        }
        
        // Use the fully qualified namespace for the WC_Email_Tickets class
        $email_classes['WC_Email_Tickets'] = new \WC_Ticket_Seller\Integrations\WC_Email_Tickets();
        return $email_classes;
    }
    
    /**
     * Show event details on product page.
     * 
     * @since    1.0.0
     */
    public function show_event_details() {
        global $product;
        
        if (!$product || !$product->is_type('ticket')) {
            return;
        }
        
        // Get event ID
        $event_id = $product->get_meta('_wc_ticket_seller_event_id', true);
        
        if (!$event_id) {
            return;
        }
        
        // Display event details
        if (class_exists('WC_Ticket_Seller\Modules\Events\Event')) {
            $event = new \WC_Ticket_Seller\Modules\Events\Event($event_id);
            
            if ($event->get_id()) {
                // We're using proper CSS classes here instead of inline styles
                echo '<div class="wc-ticket-seller-event-details">';
                echo '<h3>' . esc_html__('Event Details', 'wc-ticket-seller') . '</h3>';
                echo '<p><strong>' . esc_html__('Date:', 'wc-ticket-seller') . '</strong> ' . esc_html($event->get_start('F j, Y')) . '</p>';
                echo '<p><strong>' . esc_html__('Time:', 'wc-ticket-seller') . '</strong> ' . esc_html($event->get_start('g:i a')) . ' - ' . esc_html($event->get_end('g:i a')) . '</p>';
                
                if ($event->get_venue_name()) {
                    echo '<p><strong>' . esc_html__('Venue:', 'wc-ticket-seller') . '</strong> ' . esc_html($event->get_venue_name()) . '</p>';
                    
                    if ($event->get_venue_full_address()) {
                        echo '<p><strong>' . esc_html__('Address:', 'wc-ticket-seller') . '</strong> ' . esc_html($event->get_venue_full_address()) . '</p>';
                    }
                }
                
                // Show available tickets
                $available_tickets = $product->get_available_tickets_count();
                echo '<p><strong>' . esc_html__('Available Tickets:', 'wc-ticket-seller') . '</strong> ' . esc_html($available_tickets) . '</p>';
                
                echo '</div>';
            }
        }
    }
}