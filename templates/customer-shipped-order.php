<?php
if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);

?>

<p><?php echo esc_html__('Your order has been shipped. Here are the details:', 'milans-shipped-order-tracking-for-woo'); ?></p>

<h2><?php echo esc_html__('Tracking Information:', 'milans-shipped-order-tracking-for-woo'); ?></h2>

<table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th><?php echo esc_html__('Provider', 'milans-shipped-order-tracking-for-woo'); ?></th>
            <th><?php echo esc_html__('Tracking Number', 'milans-shipped-order-tracking-for-woo'); ?></th>
            <th><?php echo esc_html__('Date', 'milans-shipped-order-tracking-for-woo'); ?></th>
            <th><?php echo esc_html__('Track', 'milans-shipped-order-tracking-for-woo'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <?php
            // Check if we have tracking data
            if (!empty($tracking_provider) && !empty($tracking_number)) {
                // Get tracking provider information from the email class
                $sotw_providers = $email->get_tracking_providers();
                $sotw_tracking_provider_name = isset($sotw_providers[$tracking_provider]['name']) ? $sotw_providers[$tracking_provider]['name'] : $tracking_provider;

                $sotw_tracking_link = '';
                if (isset($sotw_providers[$tracking_provider]['url']) && $tracking_number) {
                    $sotw_tracking_link = $sotw_providers[$tracking_provider]['url'] . $tracking_number;
                }

                $sotw_tracking_number_html = esc_html($tracking_number);
                if ($sotw_tracking_link) {
                    $sotw_tracking_number_html = '<a href="' . esc_url($sotw_tracking_link) . '" target="_blank">' . esc_html($tracking_number) . '</a>';
                }

                $sotw_track_html = '';
                if ($sotw_tracking_link) {
                    $sotw_track_html = '<a href="' . esc_url($sotw_tracking_link) . '" target="_blank">' . __('Track your order', 'milans-shipped-order-tracking-for-woo') . '</a>';
                }
                ?>
                <td><?php echo esc_html($sotw_tracking_provider_name); ?></td>
                <td><?php echo wp_kses_post($sotw_tracking_number_html); ?></td>
                <td><?php echo esc_html($shipped_date); ?></td>
                <td><?php echo wp_kses_post($sotw_track_html); ?></td>
                <?php
            } else {
                ?>
                <td colspan="4"><?php echo esc_html__('No tracking information available.', 'milans-shipped-order-tracking-for-woo'); ?></td>
                <?php
            }
            ?>
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