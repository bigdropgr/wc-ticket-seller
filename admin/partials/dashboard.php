<?php
/**
 * Provide a admin area view for the plugin dashboard
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Include event functions
if (!function_exists('wc_ticket_seller_get_event')) {
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/event-functions.php';
}

// Get event statistics
global $wpdb;
$events_table = $wpdb->prefix . 'wc_ticket_seller_events';
$tickets_table = $wpdb->prefix . 'wc_ticket_seller_tickets';

// Total events
$total_events = $wpdb->get_var("SELECT COUNT(*) FROM $events_table");

// Upcoming events
$upcoming_events = $wpdb->get_var("SELECT COUNT(*) FROM $events_table WHERE event_start > NOW() AND event_status = 'published'");

// Total tickets
$total_tickets = $wpdb->get_var("SELECT COUNT(*) FROM $tickets_table");

// Tickets checked in
$tickets_checked_in = $wpdb->get_var("SELECT COUNT(*) FROM $tickets_table WHERE ticket_status = 'checked-in'");

// Recent events
$recent_events = $wpdb->get_results(
    "SELECT * FROM $events_table ORDER BY event_start DESC LIMIT 5",
    ARRAY_A
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <hr class="wp-header-end">
    
    <div class="notice notice-info">
        <p>
            <?php esc_html_e('Welcome to WooCommerce Ticket Seller! This plugin allows you to sell tickets for events through WooCommerce.', 'wc-ticket-seller'); ?>
        </p>
    </div>
    
    <!-- Dashboard Widgets -->
    <div class="dashboard-widgets-wrap">
        <div id="dashboard-widgets" class="metabox-holder">
            <!-- First Column -->
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables">
                    <!-- Events Summary -->
                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('Events Summary', 'wc-ticket-seller'); ?></span></h2>
                        <div class="inside wc-ticket-seller-dashboard-widget">
                            <div class="main">
                                <div class="wc-ticket-seller-stats-grid">
                                    <div class="wc-ticket-seller-dashboard-stat">
                                        <div class="wc-ticket-seller-dashboard-stat-value"><?php echo esc_html($total_events); ?></div>
                                        <div class="wc-ticket-seller-dashboard-stat-label"><?php esc_html_e('Total Events', 'wc-ticket-seller'); ?></div>
                                    </div>
                                    
                                    <div class="wc-ticket-seller-dashboard-stat">
                                        <div class="wc-ticket-seller-dashboard-stat-value"><?php echo esc_html($upcoming_events); ?></div>
                                        <div class="wc-ticket-seller-dashboard-stat-label"><?php esc_html_e('Upcoming Events', 'wc-ticket-seller'); ?></div>
                                    </div>
                                </div>
                                
                                <p class="wc-ticket-seller-action-links">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-events')); ?>" class="button button-primary">
                                        <?php esc_html_e('Manage Events', 'wc-ticket-seller'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-add-event')); ?>" class="button">
                                        <?php esc_html_e('Add Event', 'wc-ticket-seller'); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ticket Sales -->
                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('Ticket Sales', 'wc-ticket-seller'); ?></span></h2>
                        <div class="inside wc-ticket-seller-dashboard-widget">
                            <div class="main">
                                <div class="wc-ticket-seller-stats-grid">
                                    <div class="wc-ticket-seller-dashboard-stat">
                                        <div class="wc-ticket-seller-dashboard-stat-value"><?php echo esc_html($total_tickets); ?></div>
                                        <div class="wc-ticket-seller-dashboard-stat-label"><?php esc_html_e('Total Tickets', 'wc-ticket-seller'); ?></div>
                                    </div>
                                    
                                    <div class="wc-ticket-seller-dashboard-stat">
                                        <div class="wc-ticket-seller-dashboard-stat-value">
                                            <?php 
                                            if ($total_tickets > 0) {
                                                echo esc_html(round(($tickets_checked_in / $total_tickets) * 100)) . '%';
                                            } else {
                                                echo '0%';
                                            }
                                            ?>
                                        </div>
                                        <div class="wc-ticket-seller-dashboard-stat-label"><?php esc_html_e('Check-in Rate', 'wc-ticket-seller'); ?></div>
                                    </div>
                                </div>
                                
                                <p class="wc-ticket-seller-action-links">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-tickets')); ?>" class="button button-primary">
                                        <?php esc_html_e('Manage Tickets', 'wc-ticket-seller'); ?>
                                    </a>
                                    </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Second Column -->
            <div id="postbox-container-2" class="postbox-container">
                <div class="meta-box-sortables">
                    <!-- Recent Events -->
                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('Recent Events', 'wc-ticket-seller'); ?></span></h2>
                        <div class="inside wc-ticket-seller-dashboard-widget">
                            <div class="main">
                                <?php if (!empty($recent_events)) : ?>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e('Event', 'wc-ticket-seller'); ?></th>
                                                <th><?php esc_html_e('Date', 'wc-ticket-seller'); ?></th>
                                                <th><?php esc_html_e('Status', 'wc-ticket-seller'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_events as $event) : ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-events&action=edit&event_id=' . $event['event_id'])); ?>">
                                                            <?php echo esc_html($event['event_name']); ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <?php echo esc_html(wc_ticket_seller_format_event_date($event['event_start'], false)); ?>
                                                    </td>
                                                    <td>
                                                        <span class="event-status <?php echo esc_attr(wc_ticket_seller_get_event_status_class($event['event_status'])); ?>">
                                                            <?php echo esc_html(wc_ticket_seller_get_event_status_text($event['event_status'])); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else : ?>
                                    <p><?php esc_html_e('No events found.', 'wc-ticket-seller'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Start Guide -->
                    <div class="postbox">
                        <h2 class="hndle"><span><?php esc_html_e('Quick Start Guide', 'wc-ticket-seller'); ?></span></h2>
                        <div class="inside wc-ticket-seller-dashboard-widget">
                            <div class="main">
                                <ol class="wc-ticket-seller-quick-start">
                                    <li>
                                        <strong><?php esc_html_e('Create an Event', 'wc-ticket-seller'); ?></strong><br>
                                        <?php esc_html_e('Set up your event details including date, time, venue, and ticket types.', 'wc-ticket-seller'); ?>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Create a Ticket Product', 'wc-ticket-seller'); ?></strong><br>
                                        <?php esc_html_e('Create a WooCommerce product with the "Event Ticket" type and link it to your event.', 'wc-ticket-seller'); ?>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Sell Tickets', 'wc-ticket-seller'); ?></strong><br>
                                        <?php esc_html_e('Customers can purchase tickets through your WooCommerce store.', 'wc-ticket-seller'); ?>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Manage Tickets', 'wc-ticket-seller'); ?></strong><br>
                                        <?php esc_html_e('View and manage tickets for each event, track sales, and check in attendees.', 'wc-ticket-seller'); ?>
                                    </li>
                                </ol>
                                
                                <p class="wc-ticket-seller-action-links">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-add-event')); ?>" class="button button-primary">
                                        <?php esc_html_e('Create Your First Event', 'wc-ticket-seller'); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.wc-ticket-seller-stats-grid {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
}

.wc-ticket-seller-dashboard-stat {
    text-align: center;
    padding: 15px;
}

.wc-ticket-seller-dashboard-stat-value {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
}

.wc-ticket-seller-dashboard-stat-label {
    color: #777;
}

.wc-ticket-seller-action-links {
    text-align: center;
    margin-top: 15px;
}

.wc-ticket-seller-quick-start li {
    margin-bottom: 15px;
}

#dashboard-widgets {
    display: flex;
    flex-wrap: wrap;
}

#dashboard-widgets .postbox-container {
    width: 50%;
    padding: 0 8px;
    box-sizing: border-box;
}

@media screen and (max-width: 1024px) {
    #dashboard-widgets .postbox-container {
        width: 100%;
    }
}
</style>
