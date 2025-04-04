<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Includes
 */

namespace WC_Ticket_Seller\Includes;

/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Includes
 * @author     Your Name <info@yourwebsite.com>
 */
class Activator {

    /**
     * Activate the plugin.
     *
     * Creates necessary database tables and sets up initial options.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        
        // Set up database tables
        self::create_tables();
        
        // Initialize plugin options
        self::initialize_options();
        
        // Set version
        update_option( 'wc_ticket_seller_version', WC_TICKET_SELLER_VERSION );
        
        // Set activation flag for redirect
        set_transient( 'wc_ticket_seller_activation_redirect', true, 30 );
    }
    
    /**
     * Create the database tables.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Events table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_events';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            event_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_name varchar(255) NOT NULL,
            event_description longtext,
            event_start datetime NOT NULL,
            event_end datetime NOT NULL,
            venue_name varchar(255),
            venue_address text,
            venue_city varchar(100),
            venue_state varchar(100),
            venue_country varchar(100),
            venue_postcode varchar(20),
            event_status varchar(20) NOT NULL DEFAULT 'draft',
            event_capacity int(11),
            organizer_id bigint(20) UNSIGNED,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (event_id),
            KEY event_status (event_status),
            KEY event_start (event_start),
            KEY organizer_id (organizer_id)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Tickets table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_tickets';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            ticket_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED,
            order_item_id bigint(20) UNSIGNED,
            product_id bigint(20) UNSIGNED,
            event_id bigint(20) UNSIGNED NOT NULL,
            ticket_code varchar(32) NOT NULL,
            ticket_type varchar(50) NOT NULL,
            customer_id bigint(20) UNSIGNED,
            customer_email varchar(100) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            ticket_status varchar(20) NOT NULL DEFAULT 'pending',
            seat_id bigint(20) UNSIGNED,
            created_at datetime NOT NULL,
            checked_in_at datetime,
            checked_in_by bigint(20) UNSIGNED,
            PRIMARY KEY  (ticket_id),
            UNIQUE KEY ticket_code (ticket_code),
            KEY event_id (event_id),
            KEY order_id (order_id),
            KEY product_id (product_id),
            KEY customer_id (customer_id),
            KEY ticket_status (ticket_status),
            KEY seat_id (seat_id)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Venues table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_venues';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            venue_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            venue_name varchar(255) NOT NULL,
            venue_description longtext,
            venue_address text,
            venue_city varchar(100),
            venue_state varchar(100),
            venue_country varchar(100),
            venue_postcode varchar(20),
            venue_capacity int(11),
            venue_status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (venue_id),
            KEY venue_status (venue_status)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Seating charts table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_seating_charts';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            chart_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            venue_id bigint(20) UNSIGNED,
            chart_name varchar(255) NOT NULL,
            chart_description text,
            chart_status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (chart_id),
            KEY venue_id (venue_id),
            KEY chart_status (chart_status)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Sections table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_sections';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            section_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            chart_id bigint(20) UNSIGNED NOT NULL,
            section_name varchar(100) NOT NULL,
            section_label varchar(50) NOT NULL,
            section_color varchar(7),
            capacity int(11),
            price_modifier decimal(10,2) DEFAULT '0.00',
            position_data longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (section_id),
            KEY chart_id (chart_id)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Seats table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_seats';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            seat_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            section_id bigint(20) UNSIGNED NOT NULL,
            chart_id bigint(20) UNSIGNED NOT NULL,
            row_name varchar(10) NOT NULL,
            seat_number varchar(10) NOT NULL,
            x_position decimal(10,2),
            y_position decimal(10,2),
            status varchar(20) NOT NULL DEFAULT 'available',
            price_modifier decimal(10,2) DEFAULT '0.00',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (seat_id),
            KEY section_id (section_id),
            KEY chart_id (chart_id),
            KEY status (status),
            UNIQUE KEY section_seat (section_id, row_name, seat_number)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Ticket types table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_ticket_types';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            type_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id bigint(20) UNSIGNED NOT NULL,
            type_name varchar(100) NOT NULL,
            description text,
            capacity int(11),
            price decimal(10,2) NOT NULL,
            start_sale datetime,
            end_sale datetime,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (type_id),
            KEY event_id (event_id)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Pricing rules table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_pricing_rules';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            rule_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id bigint(20) UNSIGNED NOT NULL,
            ticket_type_id bigint(20) UNSIGNED,
            rule_type varchar(50) NOT NULL,
            condition_type varchar(50) NOT NULL,
            condition_data longtext,
            price_type varchar(20) NOT NULL,
            price_value decimal(10,2) NOT NULL,
            priority int(11) NOT NULL DEFAULT '10',
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (rule_id),
            KEY event_id (event_id),
            KEY ticket_type_id (ticket_type_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Waitlist table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_waitlist';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            waitlist_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id bigint(20) UNSIGNED NOT NULL,
            ticket_type_id bigint(20) UNSIGNED,
            customer_id bigint(20) UNSIGNED,
            customer_email varchar(100) NOT NULL,
            customer_name varchar(200) NOT NULL,
            quantity int(11) NOT NULL DEFAULT '1',
            status varchar(20) NOT NULL DEFAULT 'waiting',
            notification_sent datetime,
            created_at datetime NOT NULL,
            PRIMARY KEY  (waitlist_id),
            KEY event_id (event_id),
            KEY ticket_type_id (ticket_type_id),
            KEY customer_id (customer_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Check-ins table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_check_ins';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            check_in_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) UNSIGNED NOT NULL,
            event_id bigint(20) UNSIGNED NOT NULL,
            checked_in_by bigint(20) UNSIGNED,
            check_in_time datetime NOT NULL,
            station_id varchar(50),
            notes text,
            location varchar(100),
            created_at datetime NOT NULL,
            PRIMARY KEY  (check_in_id),
            KEY ticket_id (ticket_id),
            KEY event_id (event_id)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Custom fields table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_custom_fields';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            field_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id bigint(20) UNSIGNED NOT NULL,
            ticket_type_id bigint(20) UNSIGNED,
            field_name varchar(100) NOT NULL,
            field_label varchar(100) NOT NULL,
            field_type varchar(50) NOT NULL,
            field_options longtext,
            field_description text,
            is_required tinyint(1) NOT NULL DEFAULT '0',
            sort_order int(11) NOT NULL DEFAULT '0',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (field_id),
            KEY event_id (event_id),
            KEY ticket_type_id (ticket_type_id)
        ) $charset_collate;";
        dbDelta( $sql );
        
        // Custom field values table
        $table_name = $wpdb->prefix . 'wc_ticket_seller_custom_field_values';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            value_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            field_id bigint(20) UNSIGNED NOT NULL,
            ticket_id bigint(20) UNSIGNED NOT NULL,
            field_value longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (value_id),
            KEY field_id (field_id),
            KEY ticket_id (ticket_id)
        ) $charset_collate;";
        dbDelta( $sql );
    }
    
    /**
     * Initialize plugin options.
     *
     * @since    1.0.0
     */
    private static function initialize_options() {
        // General settings
        add_option( 'wc_ticket_seller_ticket_expiry', '2' ); // Days before event
        add_option( 'wc_ticket_seller_enable_qr_codes', 'yes' );
        add_option( 'wc_ticket_seller_barcode_type', 'qrcode' ); // qrcode, barcode
        add_option( 'wc_ticket_seller_send_tickets', 'completed' ); // processing, completed
        
        // Email settings
        add_option( 'wc_ticket_seller_email_ticket_subject', __('Your Tickets for {event_name}', 'wc-ticket-seller') );
        add_option( 'wc_ticket_seller_email_ticket_heading', __('Your Tickets', 'wc-ticket-seller') );
        add_option( 'wc_ticket_seller_email_reminder_subject', __('Upcoming Event: {event_name}', 'wc-ticket-seller') );
        add_option( 'wc_ticket_seller_email_reminder_heading', __('Event Reminder', 'wc-ticket-seller') );
        add_option( 'wc_ticket_seller_reminder_days', '2' ); // Days before event
        
        // PDF settings
        add_option( 'wc_ticket_seller_pdf_size', 'A4' );
        add_option( 'wc_ticket_seller_pdf_orientation', 'portrait' );
        
        // Capabilities
        self::add_capabilities();
    }
    
    /**
     * Add capabilities to roles.
     *
     * @since    1.0.0
     */
    private static function add_capabilities() {
        // Admin capabilities
        $admin = get_role( 'administrator' );
        
        $capabilities = array(
            'manage_wc_ticket_seller',
            'view_wc_ticket_seller_reports',
            'manage_wc_ticket_seller_settings',
            'create_wc_ticket_seller_events',
            'edit_wc_ticket_seller_events',
            'delete_wc_ticket_seller_events',
            'manage_wc_ticket_seller_tickets',
            'check_in_wc_ticket_seller_tickets',
        );
        
        foreach ( $capabilities as $capability ) {
            $admin->add_cap( $capability );
        }
        
        // Shop manager capabilities
        $manager = get_role( 'shop_manager' );
        if ( $manager ) {
            foreach ( $capabilities as $capability ) {
                $manager->add_cap( $capability );
            }
        }
    }
}