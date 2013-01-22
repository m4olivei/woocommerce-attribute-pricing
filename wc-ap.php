<?php
/**
 * @file
 * Plugin Name: Woocommerce Attribute Pricing
 * Description: Add the ability for administrators to affect variable product pricing by attributes.
 * Version: 0.1
 * Author: Peapod Studios
 * Author URI: http://www.peapod.ca
 *
 * Inspired by WP Ecommerce.
 *
 * Current Limitations
 * -When using the simple 'Add Variation' button, the attributes are not known when the corresponding product_variation
 * is inserted into the database.  Thus the initial attribute pricing cannot be set, and will need to be set manually.
 * A workaround would be to just always use Link All Variations.
 * -Slightly awkward is when a user sets a Base Price and then goes to Link All Variations without a Save in between.
 * The base price is not incorporated on Link All Variations.  Only on the next save is the base price incorporated.
 * Might be nice to update the base price on that text field changing
 */

/**
 * Dependancies.
 */
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if (in_array('woocommerce/woocommerce.php', $active_plugins)) {

  /**
   * Constants and globals
   */
  if (in_array('woocommerce-multicurrency/wc_mc.php', get_option('active_plugins'))) {
    define('WC_AP_WC_MC_ENABLED', TRUE);
  }
  else {
    define('WC_AP_WC_MC_ENABLED', FALSE);
  }

  /**
   * Includes
   */
  include('includes/wc-ap-admin.php');
}