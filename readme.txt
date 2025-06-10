=== Shipped Order & Tracking for WooCommerce ===
Contributors: milangru79
Tags: shipping, tracking, woocommerce, order status, shipped, tracking number
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a "Shipped" status and tracking number fields to WooCommerce orders. Automatically includes tracking info in emails.

== Description ==

This plugin enhances your WooCommerce store by adding:

- A new **“Shipped”** order status
- Fields to input **tracking number** and **shipping provider**
- A custom **email notification** sent when the order is marked as shipped
- Tracking info displayed in:
  - The **order admin**
  - The **email sent to customers**

= Features =
* Easily manage tracking numbers for each order
* Supports custom or predefined shipping providers
* Customizable email sent when order is shipped
* Clean WooCommerce integration
* Fully translatable and secure

No third-party accounts needed. Works with your existing shipping process.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the "Plugins" menu in WordPress
3. Go to a WooCommerce order, add tracking number and provider, then mark it as "Shipped"

Configure Tracking Providers:
        Navigate to WooCommerce > Settings > Emails.
        Locate the settings for the Shipped Order email and configure tracking providers under the Tracking Providers section.


== Frequently Asked Questions ==

= Does this plugin support all shipping providers? =
Yes. You can manually enter any provider or URL. It also includes popular ones like FedEx and Post of Serbia by default.

= Will my customers be notified automatically? =
Yes. When you change the order status to **“Shipped”**, an email is automatically sent to the customer with tracking details.

= Can I customize the shipped email? =
Yes, the plugin uses a template that you can override in your theme, like WooCommerce templates.

= Does it work with custom order statuses? =
This plugin specifically uses a new “Shipped” status but plays nicely with other custom statuses.

== Screenshots ==

1. Admin order page with tracking fields
2. Customer email showing tracking info
3. Shipped order status highlighted in admin

== Changelog ==

= 1.0.0 =
* Initial release
* Added "Shipped" order status
* Added admin fields for tracking number & provider
* Sent email to customer with tracking info on status change
* Added default provider list (Post of Serbia, FedEx)
* Fully internationalized and secure

== Upgrade Notice ==
= 1.2.0
Increased security
= 1.0.0 =
Initial release
