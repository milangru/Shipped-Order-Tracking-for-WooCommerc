=== Shiped order & tracking for Woocommerce ===

Contributors: milangru79

Tags: shipping, tracking, woocommerce

Requires at least: 5.0

Tested up to: 6.6

Requires PHP: 7.2

Stable tag: 1.0.0

License: GPLv2 or later

License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shipped Order and Tracking Info for WooCommerce

== Description ==

This WooCommerce plugin adds functionality to manage and display tracking information for orders. It allows you to add tracking numbers and providers, view tracking info in the admin dashboard, and include tracking details in custom email notifications.

==Features==

    Add and save tracking information for orders.
    Display tracking details on the order edit page in the WooCommerce admin.
    Show tracking information in a custom column on the orders list.
    Include tracking information in custom WooCommerce emails.
    Add a new order status called "Shipped."

==Installation==

    Upload the Plugin:
        Upload the wc-shipped-tracking folder to the /wp-content/plugins/ directory.

    Activate the Plugin:
        Go to the WordPress admin dashboard.
        Navigate to Plugins > Installed Plugins.
        Find Shipped Order and Tracking Info for WooCommerce and click Activate.

    Configure Tracking Providers:
        Navigate to WooCommerce > Settings > Emails.
        Locate the settings for the Shipped Order email and configure tracking providers under the Tracking Providers section.

==Usage==

    Add Tracking Information:
        Go to WooCommerce > Orders.
        Edit an order.
        In the order details, you will see fields to select a tracking provider and enter a tracking number.

    View Tracking Information:
        On the order edit page, you will see the tracking information displayed.
        In the orders list, a custom column shows tracking info with clickable links.

    Custom Email Notifications:
        When an order status changes to "Shipped," the plugin triggers a custom email including tracking information.

==Customizing Tracking Providers==

    Format for Tracking Providers:
        In the email settings, tracking providers should be listed in the format: provider_key|Provider Name|http://tracking-url.com?tracking_number=.
        provider_key should be unique and used to identify the provider.
        Provider Name is the human-readable name of the provider.
        http://tracking-url.com?tracking_number=    where the actual number will be appended.

==Code Overview==
==Main Functions==

    wc_shipped_tracking_enqueue_styles(): Enqueues plugin stylesheets.
    get_tracking_providers_from_settings(): Retrieves tracking provider settings from WooCommerce.
    save_tracking_fields(): Saves tracking number and provider information.
    display_tracking_info_in_admin_order_meta_box(): Displays tracking information on the order edit page.
    register_shipped_order_status(): Registers the "Shipped" order status.
    add_shipped_to_order_statuses(): Adds "Shipped" to the list of WooCommerce order statuses.
    add_custom_woocommerce_email(): Adds a custom email class for shipped orders.
    trigger_shipped_order_email(): Sends a custom email when the order status changes to "Shipped."
    add_tracking_info_column(): Adds a custom column for tracking info in the orders list.
    display_tracking_info_column(): Displays tracking info in the custom column.

==Changelog==
1.0

    Initial release with tracking info management, custom email integration, and new "Shipped" status.

Support

For support, please open an issue on the GitHub repository.
License

This plugin is licensed under the GNU General Public License v2.0.
