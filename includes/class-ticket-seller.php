<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Includes
 */

namespace WC_Ticket_Seller\Includes;

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Includes
 * @author     BigDrop <info@bigdrop.gr>
 */
class Ticket_Seller {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = WC_TICKET_SELLER_VERSION;
        $this->plugin_name = 'wc-ticket-seller';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_woocommerce_hooks();
        $this->define_rest_api();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Loader. Orchestrates the hooks of the plugin.
     * - I18n. Defines internationalization functionality.
     * - Admin. Defines all hooks for the admin area.
     * - Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters of the core plugin.
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/class-loader.php';

        // The class responsible for defining internationalization functionality of the plugin.
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/class-i18n.php';

        // Core modules
        $this->load_modules();
        
        // The REST API functionality
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'includes/api/class-api.php';

        $this->loader = new Loader();
    }

    /**
     * Load the core modules for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_modules() {
        // Load events module
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/events/class-events-module.php';
        
        // Load tickets module
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/tickets/class-tickets-module.php';
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/tickets/class-ticket-manager.php';
        
        // Load seating module
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/seating/class-seating-module.php';
        
        // Load check-in module
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/check-in/class-check-in-module.php';
        
        // Load reports module
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'modules/reports/class-reports-module.php';
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the I18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new I18n();
        $plugin_i18n->set_domain($this->get_plugin_name());

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new \WC_Ticket_Seller\Admin\Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new \WC_Ticket_Seller\Public_Area\Public_Area($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
    }

    /**
     * Register all of the hooks related to WooCommerce integration.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_woocommerce_hooks() {
        $wc_integration = new \WC_Ticket_Seller\Integrations\WC_Integration($this->get_plugin_name(), $this->get_version());

        // Product type
        $this->loader->add_filter('product_type_selector', $wc_integration, 'add_ticket_product_type');
        $this->loader->add_filter('woocommerce_product_class', $wc_integration, 'set_ticket_product_class', 10, 2);
        
        // Order processing
        $this->loader->add_action('woocommerce_order_status_completed', $wc_integration, 'process_completed_ticket_order');
        $this->loader->add_action('woocommerce_order_status_cancelled', $wc_integration, 'cancel_ticket_order');
        $this->loader->add_action('woocommerce_order_status_refunded', $wc_integration, 'cancel_ticket_order');
        
        // Cart validation
        $this->loader->add_filter('woocommerce_add_to_cart_validation', $wc_integration, 'validate_ticket_add_to_cart', 10, 5);
        
        // Checkout fields
        $this->loader->add_filter('woocommerce_checkout_fields', $wc_integration, 'add_ticket_checkout_fields');
        
        // Email customization
        $this->loader->add_filter('woocommerce_email_classes', $wc_integration, 'add_ticket_email_classes');
        
        // Product display
        $this->loader->add_action('woocommerce_single_product_summary', $wc_integration, 'show_event_details', 15);
    }

    /**
     * Register the REST API endpoints.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_rest_api() {
        $api = new \WC_Ticket_Seller\API\API($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('rest_api_init', $api, 'register_routes');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}