<?php
/**
 * Functions for event management
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Process the event form submission
 */
function wc_ticket_seller_process_event_form() {
    // Check if our form was submitted
    if (!isset($_POST['action']) || $_POST['action'] !== 'wc_ticket_seller_save_event') {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['wc_ticket_seller_nonce']) || !wp_verify_nonce($_POST['wc_ticket_seller_nonce'], 'wc_ticket_seller_admin_nonce')) {
        wp_die(__('Security check failed.', 'wc-ticket-seller'));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions.', 'wc-ticket-seller'));
    }
    
    // Sanitize and validate form data
    $event_name = isset($_POST['event_name']) ? sanitize_text_field($_POST['event_name']) : '';
    $event_description = isset($_POST['event_description']) ? wp_kses_post($_POST['event_description']) : '';
    $event_start = isset($_POST['event_start']) ? sanitize_text_field($_POST['event_start']) : '';
    $event_end = isset($_POST['event_end']) ? sanitize_text_field($_POST['event_end']) : '';
    $event_capacity = isset($_POST['event_capacity']) ? intval($_POST['event_capacity']) : 0;
    $event_status = isset($_POST['event_status']) ? sanitize_text_field($_POST['event_status']) : 'draft';
    
    $venue_name = isset($_POST['venue_name']) ? sanitize_text_field($_POST['venue_name']) : '';
    $venue_address = isset($_POST['venue_address']) ? sanitize_text_field($_POST['venue_address']) : '';
    $venue_city = isset($_POST['venue_city']) ? sanitize_text_field($_POST['venue_city']) : '';
    $venue_state = isset($_POST['venue_state']) ? sanitize_text_field($_POST['venue_state']) : '';
    $venue_country = isset($_POST['venue_country']) ? sanitize_text_field($_POST['venue_country']) : '';
    $venue_postcode = isset($_POST['venue_postcode']) ? sanitize_text_field($_POST['venue_postcode']) : '';
    
    // Validate required fields
    if (empty($event_name)) {
        wp_die(__('Event name is required.', 'wc-ticket-seller'));
    }
    
    if (empty($event_start) || empty($event_end)) {
        wp_die(__('Event start and end dates are required.', 'wc-ticket-seller'));
    }
    
    // Format dates for database
    $event_start = str_replace('T', ' ', $event_start);
    $event_end = str_replace('T', ' ', $event_end);
    
    // Create event
    global $wpdb;
    $events_table = $wpdb->prefix . 'wc_ticket_seller_events';
    $now = current_time('mysql');
    
    $result = $wpdb->insert(
        $events_table,
        array(
            'event_name' => $event_name,
            'event_description' => $event_description,
            'event_start' => $event_start,
            'event_end' => $event_end,
            'venue_name' => $venue_name,
            'venue_address' => $venue_address,
            'venue_city' => $venue_city,
            'venue_state' => $venue_state,
            'venue_country' => $venue_country,
            'venue_postcode' => $venue_postcode,
            'event_status' => $event_status,
            'event_capacity' => $event_capacity,
            'organizer_id' => get_current_user_id(),
            'created_at' => $now,
            'updated_at' => $now
        ),
        array(
            '%s', // event_name
            '%s', // event_description
            '%s', // event_start
            '%s', // event_end
            '%s', // venue_name
            '%s', // venue_address
            '%s', // venue_city
            '%s', // venue_state
            '%s', // venue_country
            '%s', // venue_postcode
            '%s', // event_status
            '%d', // event_capacity
            '%d', // organizer_id
            '%s', // created_at
            '%s'  // updated_at
        )
    );
    
    if ($result === false) {
        wp_die(__('Error creating event. Please try again.', 'wc-ticket-seller'));
    }
    
    $event_id = $wpdb->insert_id;
    
    // Process ticket types
    $ticket_types_table = $wpdb->prefix . 'wc_ticket_seller_ticket_types';
    
    // Ticket name array
    $ticket_names = isset($_POST['ticket_name']) ? $_POST['ticket_name'] : array();
    
    if (!empty($ticket_names) && is_array($ticket_names)) {
        foreach ($ticket_names as $index => $name) {
            if (empty($name)) {
                continue;
            }
            
            $description = isset($_POST['ticket_description'][$index]) ? sanitize_textarea_field($_POST['ticket_description'][$index]) : '';
            $price = isset($_POST['ticket_price'][$index]) ? floatval($_POST['ticket_price'][$index]) : 0;
            $capacity = isset($_POST['ticket_capacity'][$index]) ? intval($_POST['ticket_capacity'][$index]) : 0;
            
            $wpdb->insert(
                $ticket_types_table,
                array(
                    'event_id' => $event_id,
                    'type_name' => sanitize_text_field($name),
                    'description' => $description,
                    'capacity' => $capacity,
                    'price' => $price,
                    'created_at' => $now,
                    'updated_at' => $now
                ),
                array(
                    '%d', // event_id
                    '%s', // type_name
                    '%s', // description
                    '%d', // capacity
                    '%f', // price
                    '%s', // created_at
                    '%s'  // updated_at
                )
            );
        }
    }
    
    // Redirect to event listing with success message
    wp_redirect(add_query_arg('event_created', '1', admin_url('admin.php?page=wc-ticket-seller-events')));
    exit;
}
add_action('admin_post_wc_ticket_seller_save_event', 'wc_ticket_seller_process_event_form');

/**
 * Display events in a list
 */
function wc_ticket_seller_display_events_list() {
    global $wpdb;
    $events_table = $wpdb->prefix . 'wc_ticket_seller_events';
    
    // Get search term if any
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Get status filter if any
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    
    // Build query
    $query = "SELECT * FROM $events_table WHERE 1=1";
    $params = array();
    
    if (!empty($search)) {
        $query .= " AND (event_name LIKE %s OR venue_name LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($status_filter)) {
        $query .= " AND event_status = %s";
        $params[] = $status_filter;
    }
    
    // Add ordering
    $query .= " ORDER BY event_start DESC";
    
    // Prepare the query if we have parameters
    if (!empty($params)) {
        $query = $wpdb->prepare($query, $params);
    }
    
    // Execute query
    $events = $wpdb->get_results($query, ARRAY_A);
    
    return $events;
}

/**
 * Get event by ID
 * 
 * @param int $event_id Event ID
 * @return array|null Event data or null if not found
 */
function wc_ticket_seller_get_event($event_id) {
    global $wpdb;
    $events_table = $wpdb->prefix . 'wc_ticket_seller_events';
    
    $event = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $events_table WHERE event_id = %d",
            $event_id
        ),
        ARRAY_A
    );
    
    return $event;
}

/**
 * Get ticket types for an event
 * 
 * @param int $event_id Event ID
 * @return array Ticket types
 */
function wc_ticket_seller_get_ticket_types($event_id) {
    global $wpdb;
    $ticket_types_table = $wpdb->prefix . 'wc_ticket_seller_ticket_types';
    
    $ticket_types = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $ticket_types_table WHERE event_id = %d ORDER BY price ASC",
            $event_id
        ),
        ARRAY_A
    );
    
    return $ticket_types;
}

/**
 * Format event date for display
 * 
 * @param string $date MySQL datetime
 * @param bool $include_time Whether to include time
 * @return string Formatted date
 */
function wc_ticket_seller_format_event_date($date, $include_time = true) {
    $date_format = get_option('date_format');
    $time_format = get_option('time_format');
    
    if ($include_time) {
        return date_i18n($date_format . ' ' . $time_format, strtotime($date));
    } else {
        return date_i18n($date_format, strtotime($date));
    }
}

/**
 * Get event status text
 * 
 * @param string $status Status code
 * @return string Status text
 */
function wc_ticket_seller_get_event_status_text($status) {
    $statuses = array(
        'draft' => __('Draft', 'wc-ticket-seller'),
        'published' => __('Published', 'wc-ticket-seller'),
        'cancelled' => __('Cancelled', 'wc-ticket-seller'),
    );
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

/**
 * Get event status color class
 * 
 * @param string $status Status code
 * @return string CSS class
 */
function wc_ticket_seller_get_event_status_class($status) {
    $classes = array(
        'draft' => 'status-draft',
        'published' => 'status-published',
        'cancelled' => 'status-cancelled',
    );
    
    return isset($classes[$status]) ? $classes[$status] : '';
}