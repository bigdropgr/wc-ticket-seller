<?php
// Add Event Ticket product type tab
add_filter('woocommerce_product_data_tabs', 'wc_ticket_seller_product_data_tab');
function wc_ticket_seller_product_data_tab($tabs) {
    $tabs['ticket_options'] = array(
        'label'    => __('Event Ticket Options', 'wc-ticket-seller'),
        'target'   => 'ticket_product_options',
        'class'    => array('show_if_ticket'),
        'priority' => 15
    );
    return $tabs;
}

// Add Event Ticket product fields
add_action('woocommerce_product_data_panels', 'wc_ticket_seller_product_data_fields');
function wc_ticket_seller_product_data_fields() {
    global $wpdb, $post;
    
    // Get events from database
    $events_table = $wpdb->prefix . 'wc_ticket_seller_events';
    $events = $wpdb->get_results("SELECT event_id, event_name, event_start, event_end, venue_name FROM $events_table WHERE event_status = 'published' ORDER BY event_start DESC", ARRAY_A);
    
    // Get current event_id if any
    $event_id = get_post_meta($post->ID, '_wc_ticket_seller_event_id', true);
    
    ?>
    <div id="ticket_product_options" class="panel woocommerce_options_panel">
        <div class="options_group">
            <?php if (!empty($events)) : ?>
                <p class="form-field">
                    <label for="_wc_ticket_seller_event_id"><?php _e('Select Event', 'wc-ticket-seller'); ?></label>
                    <select id="_wc_ticket_seller_event_id" name="_wc_ticket_seller_event_id" class="select short">
                        <option value=""><?php _e('Choose an event...', 'wc-ticket-seller'); ?></option>
                        <?php foreach ($events as $event) : ?>
                            <option value="<?php echo esc_attr($event['event_id']); ?>" <?php selected($event_id, $event['event_id']); ?>>
                                <?php 
                                echo esc_html($event['event_name']); 
                                $date = date_i18n(get_option('date_format'), strtotime($event['event_start']));
                                $time = date_i18n(get_option('time_format'), strtotime($event['event_start']));
                                echo ' - ' . esc_html($date . ' ' . $time);
                                if (!empty($event['venue_name'])) {
                                    echo ' @ ' . esc_html($event['venue_name']);
                                }
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                
                <p class="form-field">
                    <label for="_wc_ticket_seller_enable_seat_selection"><?php _e('Enable Seat Selection', 'wc-ticket-seller'); ?></label>
                    <input type="checkbox" id="_wc_ticket_seller_enable_seat_selection" name="_wc_ticket_seller_enable_seat_selection" class="checkbox" <?php checked(get_post_meta($post->ID, '_wc_ticket_seller_enable_seat_selection', true), 'yes'); ?> />
                    <span class="description"><?php _e('Allow customers to select seats when purchasing tickets', 'wc-ticket-seller'); ?></span>
                </p>
                
                <div id="event_details" style="margin: 10px; padding: 10px; background: #f8f8f8; border: 1px solid #ddd; display: <?php echo !empty($event_id) ? 'block' : 'none'; ?>;">
                    <h3><?php _e('Event Details', 'wc-ticket-seller'); ?></h3>
                    <div id="event_info_display">
                        <?php if (!empty($event_id)) : 
                            $selected_event = null;
                            foreach ($events as $event) {
                                if ($event['event_id'] == $event_id) {
                                    $selected_event = $event;
                                    break;
                                }
                            }
                            
                            if ($selected_event) : ?>
                                <p><strong><?php _e('Event:', 'wc-ticket-seller'); ?></strong> <?php echo esc_html($selected_event['event_name']); ?></p>
                                <p><strong><?php _e('Date:', 'wc-ticket-seller'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($selected_event['event_start']))); ?></p>
                                <p><strong><?php _e('Time:', 'wc-ticket-seller'); ?></strong> <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($selected_event['event_start']))); ?></p>
                                <?php if (!empty($selected_event['venue_name'])) : ?>
                                    <p><strong><?php _e('Venue:', 'wc-ticket-seller'); ?></strong> <?php echo esc_html($selected_event['venue_name']); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Show/hide ticket options based on product type
                    $('select#product-type').change(function() {
                        if ($(this).val() === 'ticket') {
                            $('.show_if_ticket').show();
                        } else {
                            $('.show_if_ticket').hide();
                        }
                    }).change();
                    
                    // Update event details when an event is selected
                    $('#_wc_ticket_seller_event_id').change(function() {
                        var event_id = $(this).val();
                        if (event_id) {
                            var event_name = $(this).find('option:selected').text();
                            $('#event_details').show();
                            
                            // Use AJAX to get detailed event info
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'wc_ticket_seller_get_event_details',
                                    event_id: event_id,
                                    nonce: '<?php echo wp_create_nonce('wc_ticket_seller_event_nonce'); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $('#event_info_display').html(response.data.html);
                                    }
                                }
                            });
                        } else {
                            $('#event_details').hide();
                        }
                    });
                });
                </script>
            <?php else : ?>
                <p><?php _e('No events found. Please create an event first.', 'wc-ticket-seller'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=wc-ticket-seller-add-event'); ?>" class="button"><?php _e('Create Event', 'wc-ticket-seller'); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Save ticket product data
add_action('woocommerce_process_product_meta_ticket', 'wc_ticket_seller_save_product_ticket_options');
function wc_ticket_seller_save_product_ticket_options($post_id) {
    // Save event ID
    if (isset($_POST['_wc_ticket_seller_event_id'])) {
        update_post_meta($post_id, '_wc_ticket_seller_event_id', sanitize_text_field($_POST['_wc_ticket_seller_event_id']));
    }
    
    // Save seat selection option
    $enable_seat_selection = isset($_POST['_wc_ticket_seller_enable_seat_selection']) ? 'yes' : 'no';
    update_post_meta($post_id, '_wc_ticket_seller_enable_seat_selection', $enable_seat_selection);
    
    // Set product as virtual and purchasable
    update_post_meta($post_id, '_virtual', 'yes');
    update_post_meta($post_id, '_sold_individually', 'no');
}

// AJAX function to get event details
add_action('wp_ajax_wc_ticket_seller_get_event_details', 'wc_ticket_seller_ajax_get_event_details');
function wc_ticket_seller_ajax_get_event_details() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_ticket_seller_event_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'wc-ticket-seller')));
    }
    
    // Get event ID
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    if (!$event_id) {
        wp_send_json_error(array('message' => __('No event ID provided.', 'wc-ticket-seller')));
    }
    
    // Get event details
    global $wpdb;
    $events_table = $wpdb->prefix . 'wc_ticket_seller_events';
    $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM $events_table WHERE event_id = %d", $event_id), ARRAY_A);
    
    if (!$event) {
        wp_send_json_error(array('message' => __('Event not found.', 'wc-ticket-seller')));
    }
    
    // Generate HTML for event details
    $html = '<p><strong>' . __('Event:', 'wc-ticket-seller') . '</strong> ' . esc_html($event['event_name']) . '</p>';
    $html .= '<p><strong>' . __('Date:', 'wc-ticket-seller') . '</strong> ' . esc_html(date_i18n(get_option('date_format'), strtotime($event['event_start']))) . '</p>';
    $html .= '<p><strong>' . __('Time:', 'wc-ticket-seller') . '</strong> ' . esc_html(date_i18n(get_option('time_format'), strtotime($event['event_start']))) . '</p>';
    if (!empty($event['venue_name'])) {
        $html .= '<p><strong>' . __('Venue:', 'wc-ticket-seller') . '</strong> ' . esc_html($event['venue_name']) . '</p>';
    }
    
    // Get ticket types
    $ticket_types_table = $wpdb->prefix . 'wc_ticket_seller_ticket_types';
    $ticket_types = $wpdb->get_results($wpdb->prepare("SELECT * FROM $ticket_types_table WHERE event_id = %d", $event_id), ARRAY_A);
    
    if (!empty($ticket_types)) {
        $html .= '<p><strong>' . __('Ticket Types:', 'wc-ticket-seller') . '</strong></p>';
        $html .= '<ul>';
        foreach ($ticket_types as $type) {
            $html .= '<li>' . esc_html($type['type_name']) . ' - ' . wc_price($type['price']) . '</li>';
        }
        $html .= '</ul>';
    }
    
    wp_send_json_success(array('html' => $html));
}

// Make sure WC recognizes our product type class
add_action('init', 'wc_ticket_seller_register_product_type');
function wc_ticket_seller_register_product_type() {
    if (!class_exists('WC_Product_Ticket') && class_exists('WC_Product')) {
        // Define a fallback class if our main class is not available
        class WC_Product_Ticket extends WC_Product {
            public function get_type() {
                return 'ticket';
            }
            
            public function is_virtual() {
                return true;
            }
        }
    }
}