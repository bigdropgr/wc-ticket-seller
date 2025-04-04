<?php
/**
 * Provide a admin area view for managing tickets
 *
 * This template has been implemented to replace the placeholder "coming soon" message.
 *
 * @link       https://bigdrop.gr
 * @since      1.0.0
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get Ticket_Manager instance
$ticket_manager = new WC_Ticket_Seller\Modules\Tickets\Ticket_Manager();

// Get pagination parameters
$current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;

// Get filter parameters
$event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;

// Build query args
$args = array(
    'event_id' => $event_id,
    'status' => $status,
    'search' => $search,
    'order_id' => $order_id,
    'offset' => ($current_page - 1) * $per_page,
    'limit' => $per_page,
    'orderby' => 'created_at',
    'order' => 'DESC',
);

// Get tickets and total count
$tickets = $ticket_manager->get_tickets($args);
$total_tickets = $ticket_manager->get_tickets_count(array(
    'event_id' => $event_id,
    'status' => $status,
    'search' => $search,
    'order_id' => $order_id,
));

// Calculate total pages
$total_pages = ceil($total_tickets / $per_page);

// Get available events for filtering
global $wpdb;
$events_table = $wpdb->prefix . 'wc_ticket_seller_events';
$events = $wpdb->get_results(
    "SELECT event_id, event_name FROM $events_table ORDER BY event_name ASC",
    ARRAY_A
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <hr class="wp-header-end">
    
    <?php
    // Show messages
    if (isset($_GET['message']) && $_GET['message'] === 'check-in-success') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Ticket checked in successfully.', 'wc-ticket-seller'); ?></p>
        </div>
        <?php
    } elseif (isset($_GET['message']) && $_GET['message'] === 'cancel-success') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Ticket cancelled successfully.', 'wc-ticket-seller'); ?></p>
        </div>
        <?php
    }
    ?>
    
    <!-- Filter Form -->
    <form method="get" id="ticket-filters-form">
        <input type="hidden" name="page" value="wc-ticket-seller-tickets">
        
        <div class="wc-ticket-seller-filters">
            <!-- Event Filter -->
            <div class="wc-ticket-seller-filter-item">
                <label for="event_id" class="wc-ticket-seller-filter-label"><?php esc_html_e('Event', 'wc-ticket-seller'); ?></label>
                <select name="event_id" id="event_id" class="regular-text">
                    <option value=""><?php esc_html_e('All Events', 'wc-ticket-seller'); ?></option>
                    <?php foreach ($events as $event) : ?>
                        <option value="<?php echo esc_attr($event['event_id']); ?>" <?php selected($event_id, $event['event_id']); ?>>
                            <?php echo esc_html($event['event_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Status Filter -->
            <div class="wc-ticket-seller-filter-item">
                <label for="status" class="wc-ticket-seller-filter-label"><?php esc_html_e('Status', 'wc-ticket-seller'); ?></label>
                <select name="status" id="status" class="regular-text">
                    <option value=""><?php esc_html_e('All Statuses', 'wc-ticket-seller'); ?></option>
                    <option value="pending" <?php selected($status, 'pending'); ?>><?php esc_html_e('Pending', 'wc-ticket-seller'); ?></option>
                    <option value="checked-in" <?php selected($status, 'checked-in'); ?>><?php esc_html_e('Checked In', 'wc-ticket-seller'); ?></option>
                    <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'wc-ticket-seller'); ?></option>
                </select>
            </div>
            
            <!-- Order ID Filter -->
            <div class="wc-ticket-seller-filter-item">
                <label for="order_id" class="wc-ticket-seller-filter-label"><?php esc_html_e('Order ID', 'wc-ticket-seller'); ?></label>
                <input type="number" name="order_id" id="order_id" class="regular-text" value="<?php echo esc_attr($order_id); ?>" min="1">
            </div>
            
            <!-- Search Filter -->
            <div class="wc-ticket-seller-filter-item">
                <label for="s" class="wc-ticket-seller-filter-label"><?php esc_html_e('Search', 'wc-ticket-seller'); ?></label>
                <input type="text" name="s" id="s" class="regular-text" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Ticket code, name or email', 'wc-ticket-seller'); ?>">
            </div>
            
            <!-- Filter Actions -->
            <div class="wc-ticket-seller-filter-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e('Filter', 'wc-ticket-seller'); ?></button>
                <button type="button" id="reset-filters" class="button"><?php esc_html_e('Reset', 'wc-ticket-seller'); ?></button>
            </div>
        </div>
    </form>
    
    <!-- Bulk Actions -->
    <div class="wc-ticket-seller-bulk-actions">
        <select id="bulk-action" class="regular-text">
            <option value=""><?php esc_html_e('Bulk Actions', 'wc-ticket-seller'); ?></option>
            <option value="check-in"><?php esc_html_e('Check In', 'wc-ticket-seller'); ?></option>
            <option value="cancel"><?php esc_html_e('Cancel', 'wc-ticket-seller'); ?></option>
            <option value="export-csv"><?php esc_html_e('Export as CSV', 'wc-ticket-seller'); ?></option>
            <option value="export-excel"><?php esc_html_e('Export as Excel', 'wc-ticket-seller'); ?></option>
        </select>
        <button type="button" id="apply-bulk-action" class="button"><?php esc_html_e('Apply', 'wc-ticket-seller'); ?></button>
    </div>
    
    <!-- Tickets Table -->
    <?php if (!empty($tickets)) : ?>
        <table class="wc-ticket-seller-tickets-table">
            <thead>
                <tr>
                    <th class="column-cb check-column">
                        <input type="checkbox" id="select-all-tickets">
                    </th>
                    <th class="column-ticket-id"><?php esc_html_e('ID', 'wc-ticket-seller'); ?></th>
                    <th class="column-ticket-code"><?php esc_html_e('Ticket Code', 'wc-ticket-seller'); ?></th>
                    <th class="column-event"><?php esc_html_e('Event', 'wc-ticket-seller'); ?></th>
                    <th class="column-attendee"><?php esc_html_e('Attendee', 'wc-ticket-seller'); ?></th>
                    <th class="column-email"><?php esc_html_e('Email', 'wc-ticket-seller'); ?></th>
                    <th class="column-type"><?php esc_html_e('Type', 'wc-ticket-seller'); ?></th>
                    <th class="column-order"><?php esc_html_e('Order', 'wc-ticket-seller'); ?></th>
                    <th class="column-status"><?php esc_html_e('Status', 'wc-ticket-seller'); ?></th>
                    <th class="column-actions"><?php esc_html_e('Actions', 'wc-ticket-seller'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket) : ?>
                    <tr>
                        <td class="column-cb check-column">
                            <input type="checkbox" class="ticket-checkbox" value="<?php echo esc_attr($ticket['ticket_id']); ?>">
                        </td>
                        <td class="column-ticket-id"><?php echo esc_html($ticket['ticket_id']); ?></td>
                        <td class="column-ticket-code"><?php echo esc_html($ticket['ticket_code']); ?></td>
                        <td class="column-event">
                            <?php 
                            echo esc_html($ticket['event_name']);
                            
                            if (!empty($ticket['event_start'])) {
                                echo '<br><small>' . esc_html(date_i18n(get_option('date_format'), strtotime($ticket['event_start']))) . '</small>';
                            }
                            ?>
                        </td>
                        <td class="column-attendee"><?php echo esc_html($ticket['first_name'] . ' ' . $ticket['last_name']); ?></td>
                        <td class="column-email"><?php echo esc_html($ticket['customer_email']); ?></td>
                        <td class="column-type"><?php echo esc_html($ticket['ticket_type']); ?></td>
                        <td class="column-order">
                            <?php if (!empty($ticket['order_id'])) : ?>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $ticket['order_id'] . '&action=edit')); ?>">
                                    #<?php echo esc_html($ticket['order_id']); ?>
                                </a>
                            <?php else : ?>
                                <?php esc_html_e('N/A', 'wc-ticket-seller'); ?>
                            <?php endif; ?>
                        </td>
                        <td class="column-status">
                            <span class="wc-ticket-seller-status <?php echo esc_attr($ticket['ticket_status']); ?>">
                                <?php 
                                if ($ticket['ticket_status'] === 'pending') {
                                    esc_html_e('Pending', 'wc-ticket-seller');
                                } elseif ($ticket['ticket_status'] === 'checked-in') {
                                    esc_html_e('Checked In', 'wc-ticket-seller');
                                } elseif ($ticket['ticket_status'] === 'cancelled') {
                                    esc_html_e('Cancelled', 'wc-ticket-seller');
                                } else {
                                    echo esc_html($ticket['ticket_status']);
                                }
                                ?>
                            </span>
                        </td>
                        <td class="column-actions">
                            <div class="wc-ticket-seller-ticket-actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-tickets&ticket_id=' . $ticket['ticket_id'])); ?>" class="wc-ticket-seller-ticket-action view" title="<?php esc_attr_e('View Details', 'wc-ticket-seller'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                                
                                <?php if ($ticket['ticket_status'] === 'pending') : ?>
                                    <a href="#" class="wc-ticket-seller-ticket-action check-in" data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>" title="<?php esc_attr_e('Check In', 'wc-ticket-seller'); ?>">
                                        <span class="dashicons dashicons-yes"></span>
                                    </a>
                                    
                                    <a href="#" class="wc-ticket-seller-ticket-action cancel" data-ticket-id="<?php echo esc_attr($ticket['ticket_id']); ?>" title="<?php esc_attr_e('Cancel', 'wc-ticket-seller'); ?>">
                                        <span class="dashicons dashicons-no"></span>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo esc_url(add_query_arg(array(
                                    'action' => 'wc_ticket_seller_download_ticket',
                                    'ticket_id' => $ticket['ticket_id'],
                                    'format' => 'pdf',
                                    'nonce' => wp_create_nonce('wc_ticket_seller_admin_nonce')
                                ), admin_url('admin-ajax.php'))); ?>" class="wc-ticket-seller-ticket-action download" title="<?php esc_attr_e('Download PDF', 'wc-ticket-seller'); ?>" target="_blank">
                                    <span class="dashicons dashicons-pdf"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
            <div class="wc-ticket-seller-pagination">
                <div class="wc-ticket-seller-pagination-count">
                    <?php
                    printf(
                        /* translators: %1$s: current page, %2$s: total pages, %3$s: total tickets */
                        esc_html__('Page %1$s of %2$s (%3$s tickets)', 'wc-ticket-seller'),
                        $current_page,
                        $total_pages,
                        $total_tickets
                    );
                    ?>
                </div>
                
                <div class="wc-ticket-seller-pagination-links">
                    <?php
                    // Previous page link
                    if ($current_page > 1) {
                        $prev_url = add_query_arg('paged', $current_page - 1);
                        echo '<a href="' . esc_url($prev_url) . '" class="wc-ticket-seller-pagination-link prev">' . esc_html__('&laquo; Previous', 'wc-ticket-seller') . '</a>';
                    }
                    
                    // Page numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $page_url = add_query_arg('paged', $i);
                        $class = $i === $current_page ? 'wc-ticket-seller-pagination-link current' : 'wc-ticket-seller-pagination-link';
                        echo '<a href="' . esc_url($page_url) . '" class="' . esc_attr($class) . '">' . esc_html($i) . '</a>';
                    }
                    
                    // Next page link
                    if ($current_page < $total_pages) {
                        $next_url = add_query_arg('paged', $current_page + 1);
                        echo '<a href="' . esc_url($next_url) . '" class="wc-ticket-seller-pagination-link next">' . esc_html__('Next &raquo;', 'wc-ticket-seller') . '</a>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <div class="wc-ticket-seller-no-tickets">
            <p><?php esc_html_e('No tickets found.', 'wc-ticket-seller'); ?></p>
        </div>
    <?php endif; ?>
</div>