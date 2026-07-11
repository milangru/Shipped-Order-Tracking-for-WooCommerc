<?php

if (!defined('ABSPATH')) {
    exit;
}

class SOTW_Shipped_Order_Email extends WC_Email
{
    public function __construct()
    {
        $this->id = 'shipped_order';
        $this->title = 'Order Shipped';
        $this->description = 'This email is sent to customer when an order is shipped.';
        $this->heading = 'Your order has been shipped';
        $this->subject = 'Order Shipped: {order_number}';
        $this->template_base = dirname(__FILE__) . '/templates/';
        $this->template_html = 'customer-shipped-order.php';
        $this->template_plain = 'customer-shipped-order.php';

        // Define settings fields
        $this->init_form_fields();
        $this->init_settings();

        // Hook for saving settings
        add_action('woocommerce_update_options_email_' . $this->id, array($this, 'process_admin_options'));

        // Call parent constructor to load any other defaults not explicitly defined here
        parent::__construct();
    }

    public function trigger($order_id, $order = false)
    {
        if (!$order_id) {
            return;
        }

        $this->object = wc_get_order($order_id);

        if (!$this->object) {
            return;
        }

        // Get the recipients
        $this->recipient = $this->object->get_billing_email();

        $additional_recipients = $this->get_option('additional_recipients');
        if (!empty($additional_recipients)) {
            $this->recipient .= ', ' . $additional_recipients;
        }

        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }

        // Replace the placeholder with the actual order number
        $this->subject = str_replace('{order_number}', $this->object->get_order_number(), $this->get_subject());

        $this->tracking_number = get_post_meta($order_id, '_tracking_number', true);
        $this->tracking_provider = get_post_meta($order_id, '_tracking_provider', true);
        $this->shipped_date = $this->object->get_date_modified()->date('Y-m-d');

        $this->send($this->get_recipient(), $this->subject, $this->get_content(), $this->get_headers(), $this->get_attachments());
    }

    public function get_content_html()
    {
        return wc_get_template_html($this->template_html, array(
            'order' => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text' => false,
            'email' => $this,
            'tracking_number' => isset($this->tracking_number) ? $this->tracking_number : '',
            'tracking_provider' => isset($this->tracking_provider) ? $this->tracking_provider : '',
            'shipped_date' => isset($this->shipped_date) ? $this->shipped_date : '',
        ), '', $this->template_base);
    }

    public function get_content_plain()
    {
        return wc_get_template_html($this->template_plain, array(
            'order' => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text' => true,
            'email' => $this,
            'tracking_number' => isset($this->tracking_number) ? $this->tracking_number : '',
            'tracking_provider' => isset($this->tracking_provider) ? $this->tracking_provider : '',
            'shipped_date' => isset($this->shipped_date) ? $this->shipped_date : '',
        ), '', $this->template_base);
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'milans-shipped-order-tracking-for-woo'),
                'type' => 'checkbox',
                'label' => __('Enable this email notification', 'milans-shipped-order-tracking-for-woo'),
                'default' => 'yes',
            ),
            'subject' => array(
                'title' => __('Subject', 'milans-shipped-order-tracking-for-woo'),
                'type' => 'text',
                // Translators: %s will be replaced with a list of available placeholders such as {order_number}.
                'description' => sprintf(__('Available placeholders: %s', 'milans-shipped-order-tracking-for-woo'), '<code>{order_number}</code>'),
                'default' => $this->subject,
                'placeholder' => '',
            ),
            'heading' => array(
                'title' => __('Email Heading', 'milans-shipped-order-tracking-for-woo'),
                'type' => 'text',
                // Translators: %s will be replaced with a list of available placeholders such as {order_number}.
                'description' => sprintf(__('Available placeholders: %s', 'milans-shipped-order-tracking-for-woo'), '<code>{order_number}</code>'),
                'default' => $this->heading,
                'placeholder' => '',
            ),
            'additional_recipients' => array(
                'title' => __('Additional Recipients', 'milans-shipped-order-tracking-for-woo'),
                'type' => 'text',
                'description' => __('Enter additional email recipients (comma separated).', 'milans-shipped-order-tracking-for-woo'),
                'default' => '',
                'placeholder' => __('example@example.com', 'milans-shipped-order-tracking-for-woo'),
            ),
            'tracking_providers' => array(
                'title' => __('Tracking Providers', 'milans-shipped-order-tracking-for-woo'),
                'type' => 'textarea',
                'description' => __('Enter tracking providers in the format: provider_key|Provider Name|http://tracking-url.com?tracking_number=', 'milans-shipped-order-tracking-for-woo'),
                'default' => 'post_of_serbia|Post of Serbia|https://t.17track.net/en#nums=' . PHP_EOL . 'fedex|FedEx|https://www.fedex.com/apps/fedextrack/?tracknumbers=',
                'placeholder' => '',
            ),
            'email_type' => array(
                'title' => __('Email type', 'milans-shipped-order-tracking-for-woo'),
                'type' => 'select',
                'description' => __('Choose which format of email to send.', 'milans-shipped-order-tracking-for-woo'),
                'default' => 'html',
                'class' => 'email_type',
                'options' => $this->get_email_type_options(),
            ),
        );
    }

    // Function to get tracking providers as an array
    public function get_tracking_providers()
    {
        $providers = array();
        $tracking_providers = $this->get_option('tracking_providers', '');
        $lines = explode(PHP_EOL, $tracking_providers);

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) === 3) {
                $providers[$parts[0]] = array(
                    'name' => $parts[1],
                    'url' => $parts[2],
                );
            }
        }

        return $providers;
    }
}
