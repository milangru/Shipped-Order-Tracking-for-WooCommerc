<?php
/**
 * Plugin Name:       Milans Shipped Order Tracking for Woo
 * Requires Plugins:  woocommerce
 * Plugin URI:        https://github.com/milangru/milans-shipped-order-tracking-for-woo
 * Description:       Adds a "Shipped" order status and automatically sends tracking emails to customers.
 * Version:           2.0.2
 * Author:            Milan Grujić
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       milans-shipped-order-tracking-for-woo
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Enqueue the plugin stylesheets for admin and public pages
function sotw_enqueue_styles( $hook_suffix ) {
    // 1. Učitavanje ADMIN stila (na listi svih porudžbina i na izmeni pojedinačne porudžbine)
    if ( is_admin() && ( 
        $hook_suffix === 'post.php' || 
        $hook_suffix === 'edit.php' || 
        strpos( $hook_suffix, 'woocommerce_page_wc-orders' ) !== false 
    ) ) {
        wp_enqueue_style(
            'wc-shipping-tracking-admin-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
            array(),
            '2.0.0'
        );
    } 
    
    // 2. frontend styles
    if ( ! is_admin() && is_page( 'tracking' ) ) {
        wp_enqueue_style(
            'wc-shipping-tracking-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
            array(),
            '2.0.0'
        );
    }
}
add_action( 'admin_enqueue_scripts', 'sotw_enqueue_styles' );
add_action( 'wp_enqueue_scripts', 'sotw_enqueue_styles' );

// Function to get tracking providers from the email settings
function sotw_get_tracking_providers() {
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
add_action('woocommerce_admin_order_data_after_order_details', 'sotw_add_tracking_fields');
function sotw_add_tracking_fields($order) {
    $tracking_number = get_post_meta($order->get_id(), '_tracking_number', true);
    $tracking_provider = get_post_meta($order->get_id(), '_tracking_provider', true);

    // Fetch the tracking providers from settings
    $providers = sotw_get_tracking_providers();
    $provider_options = array();

    foreach ($providers as $key => $url) {
        $provider_options[$key] = isset($providers[$key]['name']) ? $providers[$key]['name'] : $key;
    }

    ?>
    <div class="tracking-info-meta-box">
        <?php
        // Nonce field for verification
        wp_nonce_field('sotw_save_tracking_fields_nonce_action', 'tracking_fields_nonce');

        // Display the select box for tracking provider
        woocommerce_wp_select(array(
            'id' => '_tracking_provider',
            'label' => __('Tracking Provider', 'milans-shipped-order-tracking-for-woo'),
            'options' => $provider_options,
            'value' => $tracking_provider,
        ));
        
        // Display the input box for tracking number
        woocommerce_wp_text_input(array(
            'id' => '_tracking_number',
            'label' => __('Tracking Number', 'milans-shipped-order-tracking-for-woo'),
            'placeholder' => '',
            'description' => __('Enter the tracking number for this order.', 'milans-shipped-order-tracking-for-woo'),
            'type' => 'text',
            'desc_tip' => true,
            'value' => $tracking_number,
        ));
        ?>
    </div>
    <?php
}

// Save tracking fields
add_action('woocommerce_process_shop_order_meta', 'sotw_save_tracking_fields');
function sotw_save_tracking_fields($order_id) {
    // Check if the nonce is set and valid
    if (isset($_POST['tracking_fields_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tracking_fields_nonce'])), 'sotw_save_tracking_fields_nonce_action')) {
        if (isset($_POST['_tracking_number'])) {
            update_post_meta($order_id, '_tracking_number', sanitize_text_field(wp_unslash($_POST['_tracking_number'])));
        }
        if (isset($_POST['_tracking_provider'])) {
            update_post_meta($order_id, '_tracking_provider', sanitize_text_field(wp_unslash($_POST['_tracking_provider'])));
        }
    } else {
        // Nonce verification failed
        wp_die(
            esc_html__('Nonce verification failed', 'milans-shipped-order-tracking-for-woo'), 
            esc_html__('Error', 'milans-shipped-order-tracking-for-woo'), 
            array('response' => 403)
        );
    }
}

// Add tracking information to the order details in the admin dashboard
add_action('woocommerce_admin_order_data_after_order_details', 'sotw_display_tracking_info');
function sotw_display_tracking_info($order) {
    $tracking_provider = get_post_meta($order->get_id(), '_tracking_provider', true);
    $tracking_number = get_post_meta($order->get_id(), '_tracking_number', true);

    // Fetch the tracking providers dynamically
    $providers = sotw_get_tracking_providers();
    $tracking_url = '';

    if (isset($providers[$tracking_provider])) {
        $tracking_url = $providers[$tracking_provider]['url'];
        
        if ($tracking_url && $tracking_number) {
            $tracking_url .= $tracking_number; // Append the tracking number directly
        }
    }

    if ($tracking_number && $tracking_url) {
        echo '<h3>' . esc_html__('Tracking Information', 'milans-shipped-order-tracking-for-woo') . '</h3>';
        echo '<p>' . esc_html__('Tracking Number:', 'milans-shipped-order-tracking-for-woo') . ' ' . esc_html($tracking_number) . '<br>';
        echo esc_html__('Tracking URL:', 'milans-shipped-order-tracking-for-woo') . ' <a href="' . esc_url($tracking_url) . '" target="_blank">' . esc_html__('Track your order', 'milans-shipped-order-tracking-for-woo') . '</a></p>';
    }
}

// Register custom order status
function sotw_register_order_status() {
    register_post_status('wc-shipped', array(
        'label'                     => _x('Shipped', 'Order status', 'milans-shipped-order-tracking-for-woo'),
        'public'                    => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        // translators: %s is the number of shipped orders.
        'label_count'               => _n_noop('Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'milans-shipped-order-tracking-for-woo')
    ));
}
add_action('init', 'sotw_register_order_status');

// Add new order status to list of WooCommerce order statuses
function sotw_add_order_statuses($order_statuses) {
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
add_filter('wc_order_statuses', 'sotw_add_order_statuses');

// Add custom email class
add_filter('woocommerce_email_classes', 'sotw_add_custom_email');
function sotw_add_custom_email($email_classes) {
    require_once dirname(__FILE__) . '/class-sotw-shipped-order-email.php';
    if (class_exists('SOTW_Shipped_Order_Email')) {
        $email_classes['SOTW_Shipped_Order_Email'] = new SOTW_Shipped_Order_Email();
    }
    return $email_classes;
}

// Trigger custom email on order status change
add_action('woocommerce_order_status_shipped', 'sotw_trigger_shipped_email', 10, 2);
function sotw_trigger_shipped_email($order_id, $order = false) {
    if (!$order) {
        $order = wc_get_order($order_id);
    }

    if (!$order) {
        return;
    }

    $mailer = WC()->mailer();
    $emails = $mailer->get_emails();
    
    if (!empty($emails) && !empty($emails['SOTW_Shipped_Order_Email'])) {
        $emails['SOTW_Shipped_Order_Email']->trigger($order_id, $order);
    }
}

// Add custom column for tracking info at the end (Compatible with HPOS)
function sotw_add_tracking_column($columns) {
    if (isset($columns['order_tracking_info'])) {
        unset($columns['order_tracking_info']);
    }

    $columns['order_tracking_info'] = __('Tracking Info', 'milans-shipped-order-tracking-for-woo');
    
    return $columns;
}
//Added for HPOS support
add_filter('manage_edit-shop_order_columns', 'sotw_add_tracking_column');
add_filter('manage_screen-id_shop_order_columns', 'sotw_add_tracking_column'); // New WooCommerce versions
add_filter('manage_woocommerce_page_wc-orders_columns', 'sotw_add_tracking_column'); // HPOS table

/// Display tracking info in the custom column
function sotw_display_tracking_column($column, $order_or_post = null) {
    if ('order_tracking_info' === $column) {
        // Obuhvatamo i HPOS (Order objekat) i stari sistem (Post ID / Post objekat)
        $order_id = 0;
        if (is_object($order_or_post) && method_exists($order_or_post, 'get_id')) {
            $order_id = $order_or_post->get_id();
        } elseif (is_numeric($order_or_post)) {
            $order_id = $order_or_post;
        } else {
            global $post;
            $order_id = isset($post->ID) ? $post->ID : 0;
        }

        if (!$order_id) {
            return;
        }

        $tracking_number = get_post_meta($order_id, '_tracking_number', true);
        $tracking_provider = get_post_meta($order_id, '_tracking_provider', true);

        if ($tracking_number && $tracking_provider) {
            $providers = sotw_get_tracking_providers();
            $tracking_url = '';

            if (isset($providers[$tracking_provider])) {
                $tracking_url = $providers[$tracking_provider]['url'];
                $tracking_url .= $tracking_number;
            }

            if ($tracking_url) {
                // target="_blank" otvara link u novom tabu
                echo '<a href="' . esc_url($tracking_url) . '" target="_blank">' . esc_html($tracking_number) . '</a>';
            } else {
                echo esc_html__('No Tracking Info', 'milans-shipped-order-tracking-for-woo');
            }
        } else {
            echo esc_html__('No Tracking Info', 'milans-shipped-order-tracking-for-woo');
        }
    }
}
// Dodate akcije za HPOS podršku
add_action('manage_shop_order_posts_custom_column', 'sotw_display_tracking_column', 10, 1);
add_action('manage_woocommerce_page_wc-orders_custom_column', 'sotw_display_tracking_column', 10, 2); // HPOS prikaz datoteke

// Kreiraj podrazumevana podešavanja prilikom aktivacije plugina
register_activation_hook(__FILE__, 'sotw_activate');
function sotw_activate() {
    if (get_option('woocommerce_shipped_order_settings', false) === false) {
        $default_settings = array(
            'enabled' => 'yes',
            'subject' => 'Order Shipped: {order_number}',
            'heading' => 'Your order has been shipped',
            'additional_recipients' => '',
            'tracking_providers' => "post_of_serbia|Post of Serbia|https://t.17track.net/en#nums=\nfedex|FedEx|https://www.fedex.com/apps/fedextrack/?tracknumbers=",
            'email_type' => 'html',
        );
        update_option('woocommerce_shipped_order_settings', $default_settings);
    }
}