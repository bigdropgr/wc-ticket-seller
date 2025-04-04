<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Public
 */

namespace WC_Ticket_Seller\Public_Area;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and public-facing hooks.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Public
 * @author     Your Name <info@yourwebsite.com>
 */
class Public_Area {

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
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Load dependencies
        $this->load_dependencies();
    }
    
    /**
     * Load public dependencies
     *
     * @since    1.0.0
     */
    private function load_dependencies() {
        // No dependencies required for placeholder
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Placeholder for stylesheet registration
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Placeholder for script registration
    }

    /**
     * Register shortcodes.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        // Placeholder for shortcode registration
    }
    
    /**
     * Get template path.
     * 
     * Looks for template in theme directory first, then plugin directory.
     *
     * @since     1.0.0
     * @param     string    $template_name    Template file name.
     * @param     string    $template_path    Optional. Template path within theme.
     * @return    string                      Template path.
     */
    public static function get_template( $template_name, $template_path = '' ) {
        // Look in yourtheme/wc-ticket-seller/template-name.php
        if ( empty( $template_path ) ) {
            $template_path = 'wc-ticket-seller/';
        }
        
        $template = locate_template( $template_path . $template_name );
        
        // Get default template from plugin
        if ( ! $template ) {
            $template = WC_TICKET_SELLER_PLUGIN_DIR . 'templates/' . $template_name;
        }
        
        // Allow filtering of file path
        $template = apply_filters( 'wc_ticket_seller_locate_template', $template, $template_name, $template_path );
        
        return $template;
    }
    
    /**
     * Load a template.
     *
     * @since     1.0.0
     * @param     string    $template_name    Template file name.
     * @param     array     $args             Optional. Variables to pass to template.
     * @param     string    $template_path    Optional. Template path within theme.
     * @param     bool      $return           Whether to return or output template.
     * @return    string|void                 Template content if $return is true.
     */
    public static function load_template( $template_name, $args = array(), $template_path = '', $return = false ) {
        $template = self::get_template( $template_name, $template_path );
        
        // Extract args to make them available in the template
        if ( is_array( $args ) && ! empty( $args ) ) {
            extract( $args );
        }
        
        if ( $return ) {
            ob_start();
        }
        
        // Include template
        if ( file_exists( $template ) ) {
            include $template;
        }
        
        if ( $return ) {
            return ob_get_clean();
        }
    }
}