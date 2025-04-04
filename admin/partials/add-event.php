<?php
/**
 * Provide a admin area view for adding events
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

// Create nonce for security
$nonce = wp_create_nonce('wc_ticket_seller_admin_nonce');

?>

<div class="wrap wc-ticket-seller-admin-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
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
    ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wc-ticket-seller-admin-form">
        <input type="hidden" name="action" value="wc_ticket_seller_save_event">
        <input type="hidden" name="wc_ticket_seller_nonce" value="<?php echo esc_attr($nonce); ?>">
        
        <!-- Event Information -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('Event Information', 'wc-ticket-seller'); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="event_name"><?php esc_html_e('Event Name', 'wc-ticket-seller'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="event_name" name="event_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event_description"><?php esc_html_e('Event Description', 'wc-ticket-seller'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_editor('', 'event_description', array(
                                'textarea_name' => 'event_description',
                                'textarea_rows' => 10,
                                'media_buttons' => true,
                                'teeny' => false,
                                'quicktags' => true,
                            ));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event_start"><?php esc_html_e('Start Date & Time', 'wc-ticket-seller'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="datetime-local" id="event_start" name="event_start" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event_end"><?php esc_html_e('End Date & Time', 'wc-ticket-seller'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="datetime-local" id="event_end" name="event_end" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event_capacity"><?php esc_html_e('Event Capacity', 'wc-ticket-seller'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="event_capacity" name="event_capacity" min="1" value="100" class="regular-text">
                            <p class="description"><?php esc_html_e('Maximum number of tickets that can be sold.', 'wc-ticket-seller'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event_status"><?php esc_html_e('Status', 'wc-ticket-seller'); ?></label>
                        </th>
                        <td>
                            <select id="event_status" name="event_status">
                                <option value="draft"><?php esc_html_e('Draft', 'wc-ticket-seller'); ?></option>
                                <option value="published"><?php esc_html_e('Published', 'wc-ticket-seller'); ?></option>
                                <option value="cancelled"><?php esc_html_e('Cancelled', 'wc-ticket-seller'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Venue Information -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('Venue Information', 'wc-ticket-seller'); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="venue_name"><?php esc_html_e('Venue Name', 'wc-ticket-seller'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="venue_name" name="venue_name" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="venue_address"><?php esc_html_e('Address', 'wc-ticket-seller'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="venue_address" name="venue_address" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="venue_city"><?php esc_html_e('City', 'wc-ticket-seller'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="venue_city" name="venue_city" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="venue_state"><?php esc_html_e('State/Province', 'wc-ticket-seller'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="venue_state" name="venue_state" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="venue_country"><?php esc_html_e('Country', 'wc-ticket-seller'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="venue_country" name="venue_country" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="venue_postcode"><?php esc_html_e('Postal Code', 'wc-ticket-seller'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="venue_postcode" name="venue_postcode" class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Ticket Types -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('Ticket Types', 'wc-ticket-seller'); ?></span></h2>
            <div class="inside">
                <div id="ticket-types-container">
                    <div class="ticket-type" data-index="0">
                        <h3><?php esc_html_e('Ticket Type', 'wc-ticket-seller'); ?> <span class="ticket-number">1</span></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ticket_name_0"><?php esc_html_e('Name', 'wc-ticket-seller'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" id="ticket_name_0" name="ticket_name[]" value="General Admission" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="ticket_description_0"><?php esc_html_e('Description', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <textarea id="ticket_description_0" name="ticket_description[]" rows="3" class="regular-text"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="ticket_price_0"><?php esc_html_e('Price', 'wc-ticket-seller'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="number" id="ticket_price_0" name="ticket_price[]" min="0" step="0.01" value="49.99" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="ticket_capacity_0"><?php esc_html_e('Capacity', 'wc-ticket-seller'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="ticket_capacity_0" name="ticket_capacity[]" min="0" value="100" class="regular-text">
                                    <p class="description"><?php esc_html_e('Leave empty for unlimited (up to event capacity)', 'wc-ticket-seller'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="button remove-ticket-type"><?php esc_html_e('Remove Ticket Type', 'wc-ticket-seller'); ?></button>
                        <hr>
                    </div>
                </div>

                <button type="button" id="add-ticket-type" class="button button-secondary">
                    <?php esc_html_e('Add Another Ticket Type', 'wc-ticket-seller'); ?>
                </button>
            </div>
        </div>

        <?php submit_button(__('Create Event', 'wc-ticket-seller'), 'primary', 'submit', true); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Set current datetime as default for event start/end
    var now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    
    var nowString = now.toISOString().slice(0, 16);
    $('#event_start').val(nowString);
    
    // Set end time to 2 hours later by default
    var end = new Date(now);
    end.setHours(end.getHours() + 2);
    var endString = end.toISOString().slice(0, 16);
    $('#event_end').val(endString);
    
    // Handle adding new ticket types
    var ticketIndex = 0;
    
    $('#add-ticket-type').on('click', function() {
        ticketIndex++;
        var template = $('.ticket-type').first().clone();
        
        // Update attributes and clear values
        template.attr('data-index', ticketIndex);
        template.find('.ticket-number').text(ticketIndex + 1);
        
        // Update IDs and names
        template.find('[id^="ticket_"]').each(function() {
            var oldId = $(this).attr('id');
            var newId = oldId.replace('_0', '_' + ticketIndex);
            $(this).attr('id', newId);
        });
        
        // Clear values except for required fields
        template.find('input[type="text"], textarea').val('');
        template.find('input[name="ticket_name[]"]').val('Ticket Type ' + (ticketIndex + 1));
        template.find('input[name="ticket_price[]"]').val('49.99');
        template.find('input[name="ticket_capacity[]"]').val('100');
        
        // Add to container
        $('#ticket-types-container').append(template);
    });
    
    // Handle removing ticket types
    $(document).on('click', '.remove-ticket-type', function() {
        // Don't remove if it's the only one
        if ($('.ticket-type').length > 1) {
            $(this).closest('.ticket-type').remove();
            
            // Renumber remaining ticket types
            $('.ticket-type').each(function(index) {
                $(this).find('.ticket-number').text(index + 1);
            });
        } else {
            alert('<?php echo esc_js(__('You must have at least one ticket type.', 'wc-ticket-seller')); ?>');
        }
    });
});
</script>