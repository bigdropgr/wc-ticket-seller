<?php
/**
 * Function to register Ticket Seller admin menu items
 */
function wc_ticket_seller_add_admin_menu() {
    // Make sure we have permission functions
    if (!function_exists('current_user_can') || !function_exists('add_menu_page')) {
        return;
    }

    // Main menu
    add_menu_page(
        __('Ticket Seller', 'wc-ticket-seller'),
        __('Ticket Seller', 'wc-ticket-seller'),
        'manage_options', // Use standard WordPress capability
        'wc-ticket-seller',
        'wc_ticket_seller_display_dashboard_page',
        'dashicons-tickets-alt',
        57 // Position after WooCommerce
    );
    
    // Events submenu
    add_submenu_page(
        'wc-ticket-seller',
        __('Events', 'wc-ticket-seller'),
        __('Events', 'wc-ticket-seller'),
        'manage_options',
        'wc-ticket-seller-events',
        'wc_ticket_seller_display_events_page'
    );
    
    // Add Event submenu
    add_submenu_page(
        'wc-ticket-seller',
        __('Add Event', 'wc-ticket-seller'),
        __('Add Event', 'wc-ticket-seller'),
        'manage_options',
        'wc-ticket-seller-add-event',
        'wc_ticket_seller_display_add_event_page'
    );
    
    // Tickets submenu
    add_submenu_page(
        'wc-ticket-seller',
        __('Tickets', 'wc-ticket-seller'),
        __('Tickets', 'wc-ticket-seller'),
        'manage_options',
        'wc-ticket-seller-tickets',
        'wc_ticket_seller_display_tickets_page'
    );
    
    // Settings submenu
    add_submenu_page(
        'wc-ticket-seller',
        __('Settings', 'wc-ticket-seller'),
        __('Settings', 'wc-ticket-seller'),
        'manage_options',
        'wc-ticket-seller-settings',
        'wc_ticket_seller_display_settings_page'
    );
}

/**
 * Display the dashboard page.
 */
function wc_ticket_seller_display_dashboard_page() {
    // Check for partial file
    if (file_exists(WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/dashboard.php')) {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/dashboard.php';
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Ticket Seller Dashboard', 'wc-ticket-seller') . '</h1>';
        echo '<p>' . esc_html__('Welcome to the Ticket Seller plugin. Manage your events and tickets here.', 'wc-ticket-seller') . '</p>';
        echo '</div>';
    }
}

/**
 * Display the events page.
 */
function wc_ticket_seller_display_events_page() {
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
 */
function wc_ticket_seller_display_add_event_page() {
    // Check for partial file
    if (file_exists(WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/add-event.php')) {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/add-event.php';
    } else {
        wc_ticket_seller_display_events_page();
    }
}

/**
 * Display the tickets page.
 */
function wc_ticket_seller_display_tickets_page() {
    // Check for partial file
    if (file_exists(WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/tickets.php')) {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/tickets.php';
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Tickets', 'wc-ticket-seller') . '</h1>';
        echo '<p>' . esc_html__('Manage your tickets here.', 'wc-ticket-seller') . '</p>';
        echo '</div>';
    }
}

/**
 * Display the settings page.
 */
function wc_ticket_seller_display_settings_page() {
    // Check for partial file
    if (file_exists(WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/settings.php')) {
        require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/partials/settings.php';
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Settings', 'wc-ticket-seller') . '</h1>';
        echo '<p>' . esc_html__('Configure your ticket settings here.', 'wc-ticket-seller') . '</p>';
        echo '</div>';
    }
}

// Process event creation
function wc_ticket_seller_process_event_creation() {
    if (!isset($_POST['action']) || $_POST['action'] !== 'wc_ticket_seller_create_test_event') {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['wc_ticket_seller_nonce']) || !wp_verify_nonce($_POST['wc_ticket_seller_nonce'], 'wc_ticket_seller_create_test_event')) {
        wp_die(__('Security check failed.', 'wc-ticket-seller'));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions.', 'wc-ticket-seller'));
    }
    
    global $wpdb;
    $events_table = $wpdb->prefix . 'wc_ticket_seller_events';
    $types_table = $wpdb->prefix . 'wc_ticket_seller_ticket_types';
    
    // Validate form data
    $event_name = sanitize_text_field($_POST['event_name']);
    $event_description = sanitize_textarea_field($_POST['event_description']);
    $event_start = sanitize_text_field($_POST['event_start']);
    $event_end = sanitize_text_field($_POST['event_end']);
    $venue_name = sanitize_text_field($_POST['venue_name']);
    $venue_address = sanitize_text_field($_POST['venue_address']);
    $event_capacity = intval($_POST['event_capacity']);
    
    // Convert datetime format
    $event_start = str_replace('T', ' ', $event_start);
    $event_end = str_replace('T', ' ', $event_end);
    
    // Create event
    $wpdb->insert(
        $events_table,
        array(
            'event_name' => $event_name,
            'event_description' => $event_description,
            'event_start' => $event_start,
            'event_end' => $event_end,
            'venue_name' => $venue_name,
            'venue_address' => $venue_address,
            'event_status' => 'published',
            'event_capacity' => $event_capacity,
            'organizer_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
    );
    
    $event_id = $wpdb->insert_id;
    
    if (!$event_id) {
        wp_die(__('Failed to create event.', 'wc-ticket-seller'));
    }
    
    // Create ticket types
    $ticket_names = isset($_POST['ticket_type_name']) ? $_POST['ticket_type_name'] : array();
    $ticket_prices = isset($_POST['ticket_type_price']) ? $_POST['ticket_type_price'] : array();
    $ticket_capacities = isset($_POST['ticket_type_capacity']) ? $_POST['ticket_type_capacity'] : array();
    
    foreach ($ticket_names as $index => $name) {
        if (empty($name)) continue;
        
        $price = isset($ticket_prices[$index]) ? floatval($ticket_prices[$index]) : 0;
        $capacity = isset($ticket_capacities[$index]) ? intval($ticket_capacities[$index]) : 0;
        
        $wpdb->insert(
            $types_table,
            array(
                'event_id' => $event_id,
                'type_name' => sanitize_text_field($name),
                'description' => '',
                'capacity' => $capacity,
                'price' => $price,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%f', '%s', '%s')
        );
    }
    
    // Redirect to events page with success message
    wp_redirect(add_query_arg('event_created', '1', admin_url('admin.php?page=wc-ticket-seller-events')));
    exit;
}
add_action('admin_init', 'wc_ticket_seller_process_event_creation');

// Add success message
function wc_ticket_seller_admin_notices() {
    if (isset($_GET['page']) && $_GET['page'] === 'wc-ticket-seller-events' && isset($_GET['event_created'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             __('Event created successfully! You can now create a WooCommerce product with the "Event Ticket" product type and link it to this event.', 'wc-ticket-seller') . 
             '</p></div>';
    }
}
add_action('admin_notices', 'wc_ticket_seller_admin_notices');



