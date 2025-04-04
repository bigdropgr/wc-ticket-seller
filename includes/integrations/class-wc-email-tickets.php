<?php
/**
 * The WooCommerce Email Tickets class.
 *
 * @since      1.0.0
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 */

namespace WC_Ticket_Seller\Integrations;

/**
 * The WooCommerce Email Tickets class.
 *
 * Sends tickets to customers via email.
 *
 * @package    WC_Ticket_Seller
 * @subpackage WC_Ticket_Seller/Integrations
 * @author     Your Name <info@yourwebsite.com>
 */
class WC_Email_Tickets extends \WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        // Set properties
        $this->id             = 'wc_ticket_seller_tickets';
        $this->title          = __( 'Tickets', 'wc-ticket-seller' );
        $this->description    = __( 'This email is sent to customers containing their tickets after purchase.', 'wc-ticket-seller' );
        $this->template_html  = 'emails/tickets.php';
        $this->template_plain = 'emails/plain/tickets.php';
        $this->template_base  = WC_TICKET_SELLER_PLUGIN_DIR . 'templates/';
        $this->placeholders   = array(
            '{site_title}'   => $this->get_blogname(),
            '{order_date}'   => '',
            '{order_number}' => '',
            '{event_name}'   => '',
        );

        // Call parent constructor
        parent::__construct();
        
        // Set default email subject and heading
        $this->subject = $this->get_option( 'subject', __( 'Your tickets for {event_name}', 'wc-ticket-seller' ) );
        $this->heading = $this->get_option( 'heading', __( 'Your Tickets', 'wc-ticket-seller' ) );
    }

    /**
     * Get email subject.
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'Your tickets for {event_name}', 'wc-ticket-seller' );
    }

    /**
     * Get email heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Your Tickets', 'wc-ticket-seller' );
    }

    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'              => $this->order,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'              => $this->order,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
            ),
            '',
            $this->template_base
        );
    }
}