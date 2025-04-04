<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Admin
 */

namespace WC_Ticket_Seller\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and admin-specific hooks.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Admin
 * @author     Your Name <info@bigdrop.gr>
 */
class Admin {

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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Load admin dependencies
        $this->load_dependencies();
    }
    
    /**
     * Load admin dependencies.
     *
     * @since    1.0.0
     */
    private function load_dependencies() {
        // Meta boxes
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/meta-boxes/class-event-meta-box.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/meta-boxes/class-ticket-product-meta-box.php';
        
        // List tables
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/list-tables/class-events-list-table.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/list-tables/class-tickets-list-table.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/list-tables/class-venues-list-table.php';
        
        // Settings
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/settings/class-settings.php';
        
        // Dashboard widgets
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/dashboard/class-dashboard-widgets.php';
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        
        // Only load on plugin pages
        if (strpos($screen->id, 'wc-ticket-seller') !== false || 
            strpos($screen->id, 'product') !== false) {
            
            // Main admin styles
            wp_enqueue_style(
                $this->plugin_name, 
                WC_TICKET_SELLER_PLUGIN_URL . 'admin/css/admin.css', 
                array(), 
                $this->version, 
                'all' 
            );
            
            // jQuery UI styles for datepickers, etc.
            wp_enqueue_style(
                $this->plugin_name . '-jquery-ui', 
                WC_TICKET_SELLER_PLUGIN_URL . 'admin/css/jquery-ui.min.css', 
                array(), 
                $this->version, 
                'all' 
            );
            
            // Tickets admin page specific styles
            if (strpos($screen->id, 'wc-ticket-seller-tickets') !== false) {
                wp_enqueue_style(
                    $this->plugin_name . '-tickets-admin', 
                    WC_TICKET_SELLER_PLUGIN_URL . 'admin/css/tickets-admin.css', 
                    array($this->plugin_name), 
                    $this->version, 
                    'all' 
                );
            }
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        // Only load on plugin pages
        if (strpos($screen->id, 'wc-ticket-seller') !== false || 
            strpos($screen->id, 'product') !== false) {
             
            // Core admin scripts
            wp_enqueue_script(
                $this->plugin_name, 
                WC_TICKET_SELLER_PLUGIN_URL . 'admin/js/admin.js', 
                array('jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable'), 
                $this->version, 
                false 
            );
            
            // Localize script with common data
            wp_localize_script(
                $this->plugin_name, 
                'wc_ticket_seller_admin', 
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wc_ticket_seller_admin_nonce'),
                    'i18n' => array(
                        'confirm_delete' => __('Are you sure you want to delete this? This action cannot be undone.', 'wc-ticket-seller'),
                        'select_event' => __('Please select an event', 'wc-ticket-seller'),
                        'add_seat' => __('Add Seat', 'wc-ticket-seller'),
                        'remove_seat' => __('Remove Seat', 'wc-ticket-seller'),
                        'add_section' => __('Add Section', 'wc-ticket-seller'),
                        'remove_section' => __('Remove Section', 'wc-ticket-seller'),
                    )
                ) 
            );
            
            // Tickets admin page specific scripts
            if (strpos($screen->id, 'wc-ticket-seller-tickets') !== false) {
                wp_enqueue_script(
                    $this->plugin_name . '-tickets-admin', 
                    WC_TICKET_SELLER_PLUGIN_URL . 'admin/js/tickets-admin.js', 
                    array($this->plugin_name), 
                    $this->version, 
                    false 
                );
                
                // Localize ticket-specific data
                wp_localize_script(
                    $this->plugin_name . '-tickets-admin', 
                    'wc_ticket_seller_tickets', 
                    array(
                        'check_in_success' => __('Ticket checked in successfully.', 'wc-ticket-seller'),
                        'check_in_error' => __('Error checking in ticket:', 'wc-ticket-seller'),
                        'cancel_success' => __('Ticket cancelled successfully.', 'wc-ticket-seller'),
                        'cancel_error' => __('Error cancelling ticket:', 'wc-ticket-seller'),
                        'confirm_cancel' => __('Are you sure you want to cancel this ticket? This action cannot be undone.', 'wc-ticket-seller')
                    )
                );
            }
            
            // Seating chart scripts (only on relevant pages)
            if (strpos($screen->id, 'seating') !== false || 
                strpos($screen->id, 'product') !== false) {
                
                wp_enqueue_script(
                    $this->plugin_name . '-seating', 
                    WC_TICKET_SELLER_PLUGIN_URL . 'admin/js/seating-chart.js', 
                    array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'), 
                    $this->version, 
                    false 
                );
            }
        }
    }

    /**
     * Add menu pages.
     * 
     * Unified admin menu registration function that replaces the duplicate functionality
     * from admin-menu-functions.php
     *
     * @since    1.0.0
     */
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            __('Ticket Seller', 'wc-ticket-seller'),
            __('Ticket Seller', 'wc-ticket-seller'),
            'manage_wc_ticket_seller',
            'wc-ticket-seller',
            array($this, 'display_dashboard_page'),
            'dashicons-tickets-alt',
            57 // Position after WooCommerce
        );
        
        // Events submenu
        add_submenu_page(
            'wc-ticket-seller',
            __('Events', 'wc-ticket-seller'),
            __('Events', 'wc-ticket-seller'),
            'edit_wc_ticket_seller_events',
            'wc-ticket-seller-events',
            array($this, 'display_events_page')
        );
        
        // Add Event submenu
        add_submenu_page(
            'wc-ticket-seller',
            __('Add Event', 'wc-ticket-seller'),
            __('Add Event', 'wc-ticket-seller'),
            'create_wc_ticket_seller_events',
            'wc-ticket-seller-add-event',
            array($this, 'display_add_event_page')
        );
        
        // Tickets submenu
        add_submenu_page(
            'wc-ticket-seller',
            __('Tickets', 'wc-ticket-seller'),
            __('Tickets', 'wc-ticket-seller'),
            'manage_wc_ticket_seller_tickets',
            'wc-ticket-seller-tickets',
            array($this, 'display_tickets_page')
        );
        
        // Venues submenu
        add_submenu_page(
            'wc-ticket-seller',
            __('Venues', 'wc-ticket-seller'),
            __('Venues', 'wc-ticket-seller'),
            'edit_wc_ticket_seller_events',
            'wc-ticket-seller-venues',
            array($this, 'display_venues_page')
        );
        
        // Seating Charts submenu
        add_submenu_page(
            'wc-ticket-seller',
            __('Seating Charts', 'wc-ticket-seller'),
            __('Seating Charts', 'wc-ticket-seller'),
            'edit_wc_ticket_seller_events',
            'wc-ticket-seller-seating',
            array($this, 'display_seating_page')
        );
        
        // Check-in submenu
        add_submenu_page(
            'wc-ticket-seller',
            __('Check-in', 'wc-ticket-seller'),
            __('Check-in', 'wc-ticket-seller'),
            'check_in_wc_ticket_seller_tickets',
            'wc-ticket-seller-check-in',
            array($this, 'display_check_in_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'wc-ticket-seller',
            __('Reports', 'wc-ticket-seller'),
            __('Reports', 'wc-ticket-seller'),
            'view_wc_ticket_seller_reports',
            'wc-ticket-seller-reports',
            array($this, 'display_reports_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'wc-ticket-seller',
            __('Settings', 'wc-ticket-seller'),
            __('Settings', 'wc-ticket-seller'),
            'manage_wc_ticket_seller_settings',
            'wc-ticket-seller-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Display the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }
    
    /**
     * Display the events page.
     *
     * @since    1.0.0
     */
    public function display_events_page() {
        // Check if we're editing an event
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['event_id'])) {
            require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/edit-event.php';
            return;
        }
        
        // Otherwise show events list
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/events.php';
    }
    
    /**
     * Display the add event page.
     *
     * @since    1.0.0
     */
    public function display_add_event_page() {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/add-event.php';
    }
    
    /**
     * Display the tickets page.
     * 
     * Implemented the tickets management functionality that was previously
     * just a placeholder.
     *
     * @since    1.0.0
     */
    public function display_tickets_page() {
        // Get event ID filter if present
        $event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
        
        // Get ticket ID if viewing a single ticket
        $ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;
        
        // Handle single ticket view
        if ($ticket_id > 0) {
            require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/ticket-details.php';
            return;
        }
        
        // Load the tickets list view
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/tickets.php';
    }
    
    /**
     * Display the venues page.
     *
     * @since    1.0.0
     */
    public function display_venues_page() {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/venues.php';
    }
    
    /**
     * Display the seating page.
     *
     * @since    1.0.0
     */
    public function display_seating_page() {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/seating.php';
    }
    
    /**
     * Display the check-in page.
     *
     * @since    1.0.0
     */
    public function display_check_in_page() {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/check-in.php';
    }
    
    /**
     * Display the reports page.
     *
     * @since    1.0.0
     */
    public function display_reports_page() {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/reports.php';
    }
    
    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/settings.php';
    }
}