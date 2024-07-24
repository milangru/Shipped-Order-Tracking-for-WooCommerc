<?php
/*
Plugin Name: Shiped order & tracking for Woocommerce
Description: Adds tracking information and shipped option to WooCommerce orders and emails.
Version: 1.0.0
Author: Milan Grujic
Text Domain: wc-shipped-tracking
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Ensure the file is not accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue the plugin stylesheet
function wc_shipped_tracking_enqueue_styles() {
    // Check if we're on a WooCommerce order edit page in the admin area
    if (is_admin() && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') {
        wp_enqueue_style(
            'wc-shipped-tracking-style',
            plugin_dir_url(__FILE__) . 'css/style.css',
            array(),
            '1.0.0' // Version number or file modification time
        );
    } elseif (is_page('tracking')) {
        // Change 'tracking' to the slug of the page where the styles are needed
        wp_enqueue_style(
            'wc-shipped-tracking-style',
            plugin_dir_url(__FILE__) . 'css/style.css',
            array(),
            '1.0.0' // Version number or file modification time
        );
    }
}
add_action('wp_enqueue_scripts', 'wc_shipped_tracking_enqueue_styles');
add_action('admin_enqueue_scripts', 'wc_shipped_tracking_enqueue_styles');

// Function to get tracking providers from the email settings
function get_tracking_providers_from_settings() {
    $providers = array();
    $email_settings = get_option('woocommerce_shipped_order_settings', array());

    if (isset($email_settings['tracking_providers'])) {
        $lines = explode(PHP_EOL, $email_settings['tracking_providers']);

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) === 3) {
                $providers[$parts[0]] = array(
                    'name' => $parts[1], // Provider name
                    'url' => $parts[2]   // URL with tracking number placeholder
                );
            }
        }
    }

    return $providers;
}

// Add tracking fields to the order edit page
add_action('woocommerce_admin_order_data_after_order_details', 'add_tracking_fields_to_order_edit_page');
function add_tracking_fields_to_order_edit_page($order) {
    $tracking_number = get_post_meta($order->get_id(), '_tracking_number', true);
    $tracking_provider = get_post_meta($order->get_id(), '_tracking_provider', true);

    // Fetch the tracking providers from settings
    $providers = get_tracking_providers_from_settings();
    $provider_options = array();

    foreach ($providers as $key => $url) {
        // Update this line to display provider name in the select options
        $provider_options[$key] = isset($providers[$key]['name']) ? $providers[$key]['name'] : $key;
    }

    ?>
    <div class="tracking-info-meta-box">
        <?php
        // Nonce field for verification
        wp_nonce_field('save_tracking_fields_nonce_action', 'tracking_fields_nonce');

        // Display the select box for tracking provider
        woocommerce_wp_select(array(
            'id' => '_tracking_provider',
            'label' => __('Tracking Provider', 'wc-shipped-tracking'),
            'options' => $provider_options,
            'value' => $tracking_provider,
        ));
        
        // Display the input box for tracking number
        woocommerce_wp_text_input(array(
            'id' => '_tracking_number',
            'label' => __('Tracking Number', 'wc-shipped-tracking'),
            'placeholder' => '',
            'description' => __('Enter the tracking number for this order.', 'wc-shipped-tracking'),
            'type' => 'text',
            'desc_tip' => true,
            'value' => $tracking_number,
        ));
        ?>
    </div>
    <?php
}

// Save tracking fields
add_action('woocommerce_process_shop_order_meta', 'save_tracking_fields');
function save_tracking_fields($order_id) {
    // Check if the nonce is set and valid
    if (isset($_POST['tracking_fields_nonce']) && wp_verify_nonce($_POST['tracking_fields_nonce'], 'save_tracking_fields_nonce_action')) {
        if (isset($_POST['_tracking_number'])) {
            update_post_meta($order_id, '_tracking_number', sanitize_text_field($_POST['_tracking_number']));
        }
        if (isset($_POST['_tracking_provider'])) {
            update_post_meta($order_id, '_tracking_provider', sanitize_text_field($_POST['_tracking_provider']));
        }
    } else {
        // Nonce verification failed
        wp_die(
            esc_html__('Nonce verification failed', 'wc-shipped-tracking'), 
            esc_html__('Error', 'wc-shipped-tracking'), 
            array('response' => 403)
        );
    }
}

// Add tracking information to the order details in the admin dashboard
add_action('woocommerce_admin_order_data_after_order_details', 'display_tracking_info_in_admin_order_meta_box');
function display_tracking_info_in_admin_order_meta_box($order) {
    $tracking_provider = get_post_meta($order->get_id(), '_tracking_provider', true);
    $tracking_number = get_post_meta($order->get_id(), '_tracking_number', true);

    // Fetch the tracking providers dynamically
    $providers = get_tracking_providers_from_settings();
    $tracking_url = '';

    if (isset($providers[$tracking_provider])) {
        $tracking_url = $providers[$tracking_provider]['url'];
        
        // Ensure URL appending works correctly
        if ($tracking_url && $tracking_number) {
            $tracking_url .= $tracking_number; // Append the tracking number directly
        }
    }

    if ($tracking_number && $tracking_url) {
        echo '<h3>' . esc_html__('Tracking Information', 'wc-shipped-tracking') . '</h3>';
        echo '<p>' . esc_html__('Tracking Number:', 'wc-shipped-tracking') . ' ' . esc_html($tracking_number) . '<br>';
        echo esc_html__('Tracking URL:', 'wc-shipped-tracking') . ' <a href="' . esc_url($tracking_url) . '" target="_blank">' . esc_html__('Track your order', 'wc-shipped-tracking') . '</a></p>';
    }
}

// Register custom order status
function register_shipped_order_status() {
    register_post_status('wc-shipped', array(
        'label'                     => _x('Shipped', 'Order status', 'wc-shipped-tracking'),
        'public'                    => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'wc-shipped-tracking')
    ));
}
add_action('init', 'register_shipped_order_status');

// Add new order status to list of WooCommerce order statuses
function add_shipped_to_order_statuses($order_statuses) {
    $new_order_statuses = array();

    // Add new status after processing
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-shipped'] = 'Shipped';
        }
    }
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_shipped_to_order_statuses');

// Add custom email class
add_filter('woocommerce_email_classes', 'add_custom_woocommerce_email');
function add_custom_woocommerce_email($email_classes) {
    require_once dirname(__FILE__) . '/class-wc-shipped-order-email.php';
    if (class_exists('WC_Shipped_Order_Email')) {
        $email_classes['WC_Shipped_Order_Email'] = new WC_Shipped_Order_Email();
    }
    return $email_classes;
}

// Trigger custom email on order status change
add_action('woocommerce_order_status_shipped', 'trigger_shipped_order_email', 10, 2);
function trigger_shipped_order_email($order_id, $order = false) {
    if (!$order) {
        $order = wc_get_order($order_id);
    }

    if (!$order) {
        return;
    }

    $mailer = WC()->mailer();
    $emails = $mailer->get_emails();
    if (!empty($emails) && !empty($emails['WC_Shipped_Order_Email'])) {
        $emails['WC_Shipped_Order_Email']->trigger($order_id, $order);
    }
}

/*// Add custom column for tracking info
function add_tracking_info_column($columns) {
    $new_columns = array();

    // Add new column in the desired position
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'order_status') {
            $new_columns['order_tracking_info'] = __('Tracking Info', 'wc-shipped-tracking');
        }
    }

    return $new_columns;
}
add_filter('manage_edit-shop_order_columns', 'add_tracking_info_column');
*/
// Add custom column for tracking info at the end
function add_tracking_info_column($columns) {
    // Remove the 'order_tracking_info' column if it already exists
    if (isset($columns['order_tracking_info'])) {
        unset($columns['order_tracking_info']);
    }

    // Add the custom column for tracking info at the end
    $columns['order_tracking_info'] = __('Tracking Info', 'wc-shipped-tracking');
    
    return $columns;
}
add_filter('manage_edit-shop_order_columns', 'add_tracking_info_column');
// Display tracking info in the custom column
function display_tracking_info_column($column) {
    global $post;

    if ('order_tracking_info' === $column) {
        $tracking_number = get_post_meta($post->ID, '_tracking_number', true);
        $tracking_provider = get_post_meta($post->ID, '_tracking_provider', true);

        if ($tracking_number && $tracking_provider) {
            $providers = get_tracking_providers_from_settings();
            $tracking_url = '';

            // Fetch the URL for the tracking provider
            if (isset($providers[$tracking_provider])) {
                $tracking_url = $providers[$tracking_provider]['url'];
                
                // Debugging output
                error_log("Initial Tracking URL: $tracking_url");

                // Append the tracking number directly to the URL
                $tracking_url .= $tracking_number;

                // Debugging output
                error_log("Tracking URL after appending number: $tracking_url");
            }

            // Output the link with the tracking number appended
            if ($tracking_url) {
                echo '<a href="' . esc_url($tracking_url) . '" target="_blank">' . esc_html($tracking_number) . '</a>';
            } else {
                echo esc_html__('No Tracking Info', 'wc-shipped-tracking');
            }
        } else {
            echo esc_html__('No Tracking Info', 'wc-shipped-tracking');
        }
    }
}
add_action('manage_shop_order_posts_custom_column', 'display_tracking_info_column');

// Add CSS to background shipping method
add_action('admin_head', 'my_custom_fonts');
function my_custom_fonts() {
    echo '<style>
        .tracking-info-meta-box {
            display: flex;
            min-width: 100%;
            justify-content: space-between;
        }
        .tracking-info-meta-box .woocommerce-help-tip {
            display: none;
        }
        .order-status.status-shipped {
            content: url(/wp-content/plugins/wc-shipped-tracking/assets/images/truck-icon.png);
            max-width: 44px;
        }
        @media screen and (max-width: 781px) {
            .tracking-info-meta-box {
                display: block;
            }
            #order_data .order_data_column .form-field {
                width: 100%;
            }
            a.order-view, a.order-preview {
                min-width: fit-content;
            }
        }
    </style>';
}
?>
