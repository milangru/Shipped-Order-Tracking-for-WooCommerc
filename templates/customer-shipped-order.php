<?php
if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);

?>

<p><?php echo esc_html__('Your order has been shipped. Here are the details:', 'wc-shipped-tracking'); ?></p>

<h2><?php echo esc_html__('Tracking Information:', 'wc-shipped-tracking'); ?></h2>

<table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th><?php echo esc_html__('Provider', 'wc-shipped-tracking'); ?></th>
            <th><?php echo esc_html__('Tracking Number', 'wc-shipped-tracking'); ?></th>
            <th><?php echo esc_html__('Date', 'wc-shipped-tracking'); ?></th>
            <th><?php echo esc_html__('Track', 'wc-shipped-tracking'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <?php
            // Get tracking provider information from the new plugin
            $providers = $email->get_tracking_providers();
            $tracking_provider_name = isset($providers[$tracking_provider]['name']) ? $providers[$tracking_provider]['name'] : $tracking_provider;

            $tracking_link = '';
            if (isset($providers[$tracking_provider]['url']) && $tracking_number) {
                $tracking_link = $providers[$tracking_provider]['url'] . $tracking_number;
            }

            $tracking_number_html = esc_html($tracking_number);
            if ($tracking_link) {
                $tracking_number_html = '<a href="' . esc_url($tracking_link) . '" target="_blank">' . esc_html($tracking_number) . '</a>';
            }

            $track_html = '';
            if ($tracking_link) {
                $track_html = '<a href="' . esc_url($tracking_link) . '" target="_blank">' . __('Track your order', 'wc-shipped-tracking') . '</a>';
            }
            ?>
            <td><?php echo esc_html($tracking_provider_name); ?></td>
            <td><?php echo wp_kses_post($tracking_number_html); ?></td>
            <td><?php echo esc_html($shipped_date); ?></td>
            <td><?php echo wp_kses_post($track_html); ?></td>
        </tr>
    </tbody>
</table>
<br/>
<br/>

<?php
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);
do_action('woocommerce_email_footer', $email);
?>
