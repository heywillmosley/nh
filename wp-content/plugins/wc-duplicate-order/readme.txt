=== WC Duplicate Order ===
Contributors: Patchgill
Donate link: http://jamiegill.com/plugin-development/
Tags: woocommerce duplicate order, woocommerce clone order
Requires at least: 4.4
Tested up to: 4.9.4
Stable tag: 1.4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple plugin to add Order Duplication to Woocommerce at the click of a button.

== Description ==

After activation there will  be a Duplicate link in the order overview page within the order actions on hover. 

Duplicates all order Meta data and product data across into the new order ID. 

Order is created and a note is left in the new order of the older order ID for future reference. Order status is then set on hold awaiting admin to confirm payment. 

Supports Bulk order duplication.

Includes custom order field hook

Feature requests welcome for future development.
 
== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. There will now be a Duplicate link in the to Woocommerce  order overview page within the order actions on hover. Alternatively there is a bulk option to duplicate multiple orders.


== Frequently Asked Questions ==

== Screenshots ==

1. Order duplicate button displays on hover
2. After clicking the button order is duplicated and note is made on order with referance of duplicated order
3. Bulk Duplication

== Changelog ==

= 1.0 =
* stable release 
* Tested Woocommerce 2.6.4+

= 1.0.1 =
* Stock Reduction added 

= 1.1 =
* Bulk Duplication added
* Bug fixes thanks to Shaun @ Rubious

= 1.2 =
* Bug fix to keep order item meta in correct format contributed by @Kevin
* 2 New hooks for extra fields for other developers 'clone_extra_billing_fields_hook' & 'clone_extra_shipping_fields_hook'. Passes through orders IDs so you can pass in custom fields, just simply chacge the meta key to your new fields name see below (meta key been '_billing_mobile_phone' in this case)

add_action('clone_extra_billing_fields_hook', 'clone_extra_billing_fields', 10, 2);

function clone_extra_billing_fields($order_id, $original_order_id)
{
    update_post_meta( $order_id, '_billing_mobile_phone', get_post_meta($original_order_id, '_billing_mobile_phone', true));
}

= 1.3 =

* Update of clone items using CRUD methods due to incompatability

= 1.4 =

* Update of clone fees and coupons using CRUD methods due to incompatability
* Security patch thanks to @dungengronovius report

= 1.4.1 =

* Introduce Nonce verification as an initial check before proceeding

= 1.4.2 =

* Added in version check to make sure compatible, if false deactivate and display friendly error message
* Duplicate other product meta data (attribute / variation data)

= 1.5 =

* Modifications to work with 3.3.1 and onwards

= 1.7 =

* Stock reduction fix
* Fees name and other minor fixes
* Duplicate button on quick view
