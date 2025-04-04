<?php
/**
 * Provide a admin area view for the events listing
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
if (!function_exists('wc_ticket_seller_display_events_list')) {
    require_once WC_TICKET_SELLER_PLUGIN_DIR . 'admin/event-functions.php';
}

// Get events
$events = wc_ticket_seller_display_events_list();

// Get current status filter
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Get search term if any
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-add-event')); ?>" class="page-title-action">
        <?php esc_html_e('Add New Event', 'wc-ticket-seller'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php
    // Show success message if event was created
    if (isset($_GET['event_created']) && $_GET['event_created'] == '1') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Event created successfully!', 'wc-ticket-seller'); ?></p>
        </div>
        <?php
    }
    
    // Show message if no events
    if (empty($events)) {
        ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('No events found. Create your first event to get started!', 'wc-ticket-seller'); ?></p>
        </div>
        <?php
    }
    ?>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="wc-ticket-seller-events">
                <select name="status">
                    <option value=""><?php esc_html_e('All statuses', 'wc-ticket-seller'); ?></option>
                    <option value="published" <?php selected($current_status, 'published'); ?>><?php esc_html_e('Published', 'wc-ticket-seller'); ?></option>
                    <option value="draft" <?php selected($current_status, 'draft'); ?>><?php esc_html_e('Draft', 'wc-ticket-seller'); ?></option>
                    <option value="cancelled" <?php selected($current_status, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'wc-ticket-seller'); ?></option>
                </select>
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'wc-ticket-seller'); ?>">
            </form>
        </div>
        
        <form method="get" class="search-box">
            <input type="hidden" name="page" value="wc-ticket-seller-events">
            <?php if (!empty($current_status)): ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($current_status); ?>">
            <?php endif; ?>
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search events...', 'wc-ticket-seller'); ?>">
            <input type="submit" class="button" value="<?php esc_attr_e('Search Events', 'wc-ticket-seller'); ?>">
        </form>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-name column-primary"><?php esc_html_e('Event', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-date"><?php esc_html_e('Date', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-venue"><?php esc_html_e('Venue', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-capacity"><?php esc_html_e('Capacity', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'wc-ticket-seller'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($events)) : ?>
                <?php foreach ($events as $event) : ?>
                    <tr>
                        <td class="column-primary" data-colname="<?php esc_attr_e('Event', 'wc-ticket-seller'); ?>">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-events&action=edit&event_id=' . $event['event_id'])); ?>" class="row-title">
                                    <?php echo esc_html($event['event_name']); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-events&action=edit&event_id=' . $event['event_id'])); ?>">
                                        <?php esc_html_e('Edit', 'wc-ticket-seller'); ?>
                                    </a> | 
                                </span>
                                <span class="view">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-ticket-seller-tickets&event_id=' . $event['event_id'])); ?>">
                                        <?php esc_html_e('View Tickets', 'wc-ticket-seller'); ?>
                                    </a> | 
                                </span>
                                <span class="trash">
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wc-ticket-seller-events&action=delete&event_id=' . $event['event_id']), 'delete_event_' . $event['event_id'])); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this event?', 'wc-ticket-seller'); ?>');">
                                        <?php esc_html_e('Delete', 'wc-ticket-seller'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td data-colname="<?php esc_attr_e('Date', 'wc-ticket-seller'); ?>">
                            <?php echo esc_html(wc_ticket_seller_format_event_date($event['event_start'], false)); ?>
                            <br>
                            <small>
                                <?php 
                                    echo esc_html(
                                        date_i18n(get_option('time_format'), strtotime($event['event_start'])) 
                                        . ' - ' . 
                                        date_i18n(get_option('time_format'), strtotime($event['event_end']))
                                    ); 
                                ?>
                            </small>
                        </td>
                        <td data-colname="<?php esc_attr_e('Venue', 'wc-ticket-seller'); ?>">
                            <?php echo !empty($event['venue_name']) ? esc_html($event['venue_name']) : '<em>' . esc_html__('No venue', 'wc-ticket-seller') . '</em>'; ?>
                        </td>
                        <td data-colname="<?php esc_attr_e('Capacity', 'wc-ticket-seller'); ?>">
                            <?php echo intval($event['event_capacity']); ?>
                        </td>
                        <td data-colname="<?php esc_attr_e('Status', 'wc-ticket-seller'); ?>">
                            <span class="event-status <?php echo esc_attr(wc_ticket_seller_get_event_status_class($event['event_status'])); ?>">
                                <?php echo esc_html(wc_ticket_seller_get_event_status_text($event['event_status'])); ?>
                            </span>
                        </td>
                        <td data-colname="<?php esc_attr_e('Actions', 'wc-ticket-seller'); ?>">
                            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=product&ticket_event_id=' . $event['event_id'])); ?>" class="button">
                                <?php esc_html_e('Create Ticket Product', 'wc-ticket-seller'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6">
                        <?php esc_html_e('No events found.', 'wc-ticket-seller'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-name column-primary"><?php esc_html_e('Event', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-date"><?php esc_html_e('Date', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-venue"><?php esc_html_e('Venue', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-capacity"><?php esc_html_e('Capacity', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'wc-ticket-seller'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'wc-ticket-seller'); ?></th>
            </tr>
        </tfoot>
    </table>
</div>