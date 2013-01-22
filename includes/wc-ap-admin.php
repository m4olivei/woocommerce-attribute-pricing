<?php
/**
 * @file
 * Admin side form controls, logic and adjustments.
 */
include_once('wc-ap-price-functions.php');

/***********************************************************************************************************************
 * Attribute form edits
 **********************************************************************************************************************/

/**
 * If it doesn't exist, let's create a multi-dimensional associative array
 * that will contain all of the term/price associations
 *
 * @param $attribute
 */
function wc_ap_attribute_price_field($attribute) {
	$term_prices = get_option('_woo_ap_term_prices');

	if (is_object($attribute)) {
		$term_id = $attribute->term_id;
  }

	if (empty($term_prices) || !is_array($term_prices)) {

		$term_prices = array();
		if (isset($term_id)) {
			$term_prices[$term_id]['price'] = '';
			$term_prices[$term_id]['checked'] = '';
		}
		add_option('term_prices', $term_prices);
	}

	if (isset($term_id) && array_key_exists($term_id, $term_prices)) {
		$attr_price = $term_prices[$term_id];
  }

  // Convenient way to detect if we are editing the Add New term form or the edit form,
  // as each requires slightly different markup.
  $description = __('You can list a default price here for this attribute.  You can list a regular price (18.99), differential price (+1.99 / -2) or even a percentage-based price (+50% / -25%).');
	if(!isset($_GET['action'])) {

    // Output the field that will always be there, regardless of currency support
    ?>
    <div class="form-field">
      <label for="attribute_price"><?php _e('Attribute Price'); ?></label>
      <input type="text" name="attribute_price" id="attribute_price" style="width: 50px;" value="<?php echo isset($attr_price['price']) ? esc_attr($attr_price['price']) : ''; ?>"><br />
      <span class="description"><?php echo $description; ?></span>
    </div>
    <?php

    if (function_exists('wc_mc_get_currencies')) {
      $currencies = wc_mc_get_currencies(TRUE);
      foreach ($currencies as $slug => $currency):
        ?>
        <div class="form-field">
          <label for="attribute_price_<?php echo $slug; ?>"><?php echo __('Attribute Price') . ' (' . $slug . ')'; ?></label>
          <input type="text" name="attribute_price_<?php echo $slug; ?>" id="attribute_price_<?php echo $slug; ?>" style="width: 50px;" value="<?php echo isset($attr_price['price_' . $slug]) ? $attr_price['price_' . $slug] : ''; ?>"><br />
          <span class="description"><?php echo $description; ?></span>
        </div>
        <?php
      endforeach;
    }
	}
  else {
    ?>
    <tr class="form-field">
      <th scope="row" valign="top">
        <label for="attribute_price"><?php _e('Attribute Price'); ?></label>
      </th>
      <td>
        <input type="text" name="attribute_price" id="attribute_price" style="width: 50px;" value="<?php echo isset($attr_price['price']) ? esc_attr($attr_price['price']) : ''; ?>"><br />
        <span class="description"><?php echo $description; ?></span>
      </td>
    </tr>
    <?php

    if (function_exists('wc_mc_get_currencies')) {
      $currencies = wc_mc_get_currencies(TRUE);
      foreach ($currencies as $slug => $currency):
        ?>
        <tr class="form-field">
          <th scope="row" valign="top">
            <label for="attribute_price_<?php echo $slug; ?>"><?php echo __('Attribute Price') . ' (' . $slug . ')'; ?></label>
          </th>
          <td>
            <input type="text" name="attribute_price_<?php echo $slug; ?>" id="attribute_price_<?php echo $slug; ?>" style="width: 50px;" value="<?php echo isset($attr_price['price_' . $slug]) ? esc_attr($attr_price['price_' . $slug]) : ''; ?>"><br />
            <span class="description"><?php echo $description; ?></span>
          </td>
        </tr>
        <?php
      endforeach;
    }
  }
}

/**
 * Add script on tag edit pages.
 */
function wc_ap_admin_init() {

  if (strstr($_SERVER['REQUEST_URI'], 'edit-tags.php')) {
    wp_enqueue_script('wc-ap', plugin_dir_url('woocommerce-attribute-pricing/wc-ap.php') . 'js/wc-ap.js');
    wp_localize_script('wc-ap', 'wc_ap', array('invalid_price' => __('Invalide attribute price.')));

    // Add a script to kick off
    add_action('admin_footer', create_function('', 'echo \'<script type="text/javascript">jQuery(document).ready(function() { WC_AP.tag_form_init(); });</script>\';'));
  }
}
add_action('admin_init', 'wc_ap_admin_init');

/**
 * Render a checkbox on the edit form of an attribute, that asks the user it the price adjustment
 * they save should be applied.
 *
 * @param $attribute
 */
function wc_ap_attribute_price_field_checkbox($attribute) {
	$term_prices = get_option('_woo_ap_term_prices');
  $checked = '';

	if (is_array($term_prices) && array_key_exists($attribute->term_id, $term_prices)) {
		$checked = ($term_prices[$attribute->term_id]['checked'] == 'checked') ? 'checked' : '';
  }
  ?>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="apply_to_current"><?php _e('Apply to current variations?'); ?></label></th>
		<td>
			<span class="description"><input type="checkbox" name="apply_to_current" id="apply_to_current" style="width: auto;"<?php echo $checked; ?> /> <?php _e('By checking this box, the price rule you implement above will be applied to all variations that currently exist.  If you leave it unchecked, it will only apply to products that use this variation created or edited from now on.  Take note, this will apply this rule to <strong>every</strong> product using this variation.  If you need to override it for any reason on a specific product, simply go to that product and change the price.'); ?></span>
		</td>
	</tr>
  <?php
}

/**
 * Hook into saving an attribute in order to update pricing if need be.
 * @param $term_id
 */
function wc_ap_save_attribute_prices($term_id, $tt_id) {
  global $wpdb;
  // First we must needs determine the $taxonomy from the $term_id and $tt_id
  $taxonomy = $wpdb->get_var($wpdb->prepare('SELECT taxonomy FROM ' . $wpdb->prefix . 'term_taxonomy WHERE term_taxonomy_id = %d', $tt_id));
  $term = get_term($term_id, $taxonomy);
  $term_price = array(
    'price' => '',
    'checked' => '',
  );

  if (!$taxonomy) {
    return;
  }

	// First: saves options from input
  // Validate it
  $price = isset($_POST['attribute_price']) ? $_POST['attribute_price'] : '';
  if (!wc_ap_is_flat_price($price) && !wc_ap_is_differential_price($price) && !wc_ap_is_percentile_price($price)) {
    // Invalid format for the attribute price, leave it alone, Wordpress doesn't let us properly show an error and
    // make them fix it on the form.
    return;
  }

  // Gather default attribute price and the value of the checkbox
	if (!empty($_POST['attribute_price']) || !empty($_POST['apply_to_current'])) {
    $term_price['price'] = $_POST['attribute_price'];
		$term_price['checked'] = isset($_POST['apply_to_current']) ? 'checked' : 'unchecked';
	}

  // Save prices for currencies if supported
  if (function_exists('wc_mc_get_currencies')) {
    $currencies = wc_mc_get_currencies(TRUE);

    foreach ($currencies as $slug => $currency) {

      if (isset($_POST['attribute_price_' . $slug])) {
        $price = $_POST['attribute_price_' . $slug];

        if (wc_ap_is_flat_price($price) || wc_ap_is_differential_price($price) || wc_ap_is_percentile_price($price)) {
          $term_price['price_' . $slug] = $_POST['attribute_price_' . $slug];
        }
      }
    }
  }

  // Save
  $term_prices = get_option('_woo_ap_term_prices');
  $term_prices[$term_id] = $term_price;
  update_option('_woo_ap_term_prices', $term_prices);

	// Second: if box was checked, then let's apply the pricing to every product appropriately
	if (isset($_POST['apply_to_current'])) {

		// Now, find all products with this term_id, update their pricing structure (terms returned include
    // only parents at this point, we'll grab relevant children soon)
    // NOTE: Woocommerce only attaches terms to the parent product.  The following will thus retrieve
    // only parent products that use the term being edited.  We want to update their variation children.
    // The parent having the term DOES NOT guarantee the child will have the term.
    // 1. Figure out where the attribute data is stored in the children
    // 2. Answer the question, does this child use this term
		$products_to_mod = get_objects_in_term($term_id, $taxonomy);
		$product_parents = array();

		foreach ($products_to_mod as $parent) {
			$post = get_post($parent);

			if (!$post->post_parent) {
				$product_parents[] = $post->ID;
      }
		}

		// Now that we have all parent IDs with this term, we can get the children (only the ones that are also
    // in $products_to_mod, we don't want to apply pricing to ALL kids)
		foreach ($product_parents as $parent) {
			$args = array(
				'post_parent' => $parent,
				'post_type' => 'product_variation',
			);
			$children = get_children($args);

			foreach ($children as $child) {
        $variation = new WC_Product_Variation($child->ID);
        $attributes = $variation->get_variation_attributes();

        if (is_array($attributes) && in_array($term->slug, $attributes)) {
          // The term updated here is used on this variation, update the price
          $price = wc_ap_determine_variation_price($child->ID, $child);
          update_post_meta($child->ID, '_price', $price);

          // If multi-currency plugin is active, update multicurrency prices as well
          if (function_exists('wc_mc_get_currencies')) {
            $currencies = wc_mc_get_currencies(TRUE);

            foreach ($currencies as $slug => $currency) {
              $price = wc_ap_determine_variation_price($child->ID, $child, $slug);
              update_post_meta($child->ID, '_price_' . $slug, $price);
            }
          }
        }
			}
		}
	}
}

/**
 * React when an attribute term is deleted, in order to remove any attribute prices that might be
 * kicking around for it.
 *
 * @param $term_id
 * @param $tt_id
 */
function wc_ap_delete_attribute_prices($term_id, $tt_id) {
  $term_prices = get_option('_woo_ap_term_prices');

  if (isset($term_prices[$term_id])) {
    unset($term_prices[$term_id]);
    update_option('_woo_ap_term_prices', $term_prices);
  }
}

/**
 * Retrieves a variation from the database if we pass it an id. Otherwise,
 * it returns a copy of the variation.
 *
 * @param mixed $variation
 * @return WC_Product_Variation instance
 */

function wc_ap_get_variation($variation) {
  if (is_a($variation, 'WC_Product_Variation')) {
    return $variation;
  }

  return get_post($variation);
}

/**
 * Retrieves the price for a variation.
 *
 * @param WC_Product_Variation instance
 * @return Float
 */
function wc_ap_get_variation_base_price($variation, $currency = NULL) {
  if ($currency) {
    return (float)get_post_meta($variation->post_parent, '_base_price_' . $currency, TRUE);
  }
  return (float)get_post_meta($variation->post_parent, '_base_price', TRUE);
}

/**
 * Retrieves the attributes for a variation.
 *
 * @param WC_Product_Variation $variation
 * @return Array
 */
function wc_ap_get_variation_attributes($variation) {
  $variation_obj = new WC_Product_Variation($variation->ID);
  $attributes = $variation_obj->get_variation_attributes();

  if ($attributes) {
    return $attributes;
  }

  return array(); // For composibility.
}

/**
 * Retrieves the prices for a variation - one price for each attribute.
 *
 * @param Array $attributes
 * @param string $currency
 * @return Array
 */
function wc_ap_get_variation_prices($attributes, $currency = NULL) {
  $term_prices = get_option('_woo_ap_term_prices');
  $prices = array();

  foreach ($attributes as $taxonomy => $slug) {
    $_term = get_term_by('slug', $slug, preg_replace('~^attribute_~', '', $taxonomy));

    if ($_term && isset($term_prices[$_term->term_id])) {
      if ($currency) {
        $_price = trim($term_prices[$_term->term_id]['price_' . $currency]);
      }
      else {
        $_price = trim($term_prices[$_term->term_id]['price']);
      }
    }
    else {
      continue; // no price set.
    }

    if (wc_ap_is_flat_price($_price)) {
      $prices[] = array(
        'type' => 'flat',
        'value' => (float)$_price,
      );
    }
    else if (wc_ap_is_differential_price($_price)) {
      $prices[] = array(
        'type' => 'differential',
        'value' => (float)$_price,
      );
    }
    else if (wc_ap_is_percentile_price($_price)) {
      $prices[] = array(
        'type' => 'percentile',
        'value' => (float)$_price,
      );
    }
  }

  return $prices;
}

/**
 * Determine the price of a variation product based on the attribute it's assigned.
 * Because each variation term can have its own price (eg. 10, +10, -5%), this
 * function also takes those into account.
 *
 * @param int $variation_id
 *  ID of the variation product
 * @param obj $variation
 *  If you already have the variation object in hand, pass it and save a call to get_post
 * @return float
 *  Calculated price of the variation
 */
function wc_ap_determine_variation_price($variation_id, $variation = NULL, $currency = NULL) {
  if (!$variation) {
    $variation = wc_ap_get_variation($variation_id);
  }

  if ($variation) {
    $base_price = wc_ap_get_variation_base_price($variation, $currency);
    $attributes = wc_ap_get_variation_attributes($variation);
    $prices = wc_ap_get_variation_prices($attributes, $currency);
    $price = wc_ap_get_variation_price($prices, $base_price);
  }

  return $price;
}

/**
 * Add actions to alter the Woocommerce attribute taxonomy forms.  Also add actions
 * that react on edit/create of a Woocommerce attribute term.
 */
function wc_ap_plugins_loaded() {
  global $woocommerce;

  foreach ($woocommerce->get_attribute_taxonomies() as $taxonomy) {
    $name = $woocommerce->attribute_taxonomy_name($taxonomy->attribute_name);

    // Alter the add and edit attribute forms, only add the checkbox to the edit
    add_action($name . '_edit_form_fields', 'wc_ap_attribute_price_field');
    add_action($name . '_add_form_fields', 'wc_ap_attribute_price_field');
    add_action($name . '_edit_form_fields', 'wc_ap_attribute_price_field_checkbox');

    // React when a attribute term is edited/saved
    add_action('edited_' . $name, 'wc_ap_save_attribute_prices', 10, 2);
    add_action('created_' . $name, 'wc_ap_save_attribute_prices', 10, 2);

    // React when a attributte term is deleted
    add_action('delete_' . $name, 'wc_ap_delete_attribute_prices', 10, 2);
  }
}
// Only need these actions on the admin side
if (is_admin()) {
  add_action('plugins_loaded', 'wc_ap_plugins_loaded');
}

/**
 * Does the given price string represent a flat price?
 * Allowable format is a positive numeric value consisting of only digits and optional decimal point
 *
 * @param string price
 */
function wc_ap_is_flat_price($price) {
	if (preg_match('~\d~', $price) && preg_match('~^\d*\.?\d*$~', $price)) {
		return TRUE;
  }
}

/**
 * Does the given price string represent a percentile price?
 * Allowed format is a +/- sign followed by a numeric value, followed by a % sign.
 *
 * @param string price
 */
function wc_ap_is_percentile_price($price) {

	if (preg_match('~\d~', $price) && preg_match('~^(-|\+)\d*\.?\d*%$~', $price)) {
		return TRUE;
  }
}

/**
 * Does the given price string represent differential price?
 * Allowed format is a +/- sign followed by a numeric value
 *
 * @param string price
 */
function wc_ap_is_differential_price($price) {
  if (preg_match('~\d~', $price) && preg_match('~^(-|\+)\d*\.?\d*$~', $price)) {
		return TRUE;
  }
}

/***********************************************************************************************************************
 * Post form related tweaks
 **********************************************************************************************************************/
/**
 * Take action when a new variation is inserted and it's attribute_* data is set for the first time.
 * This is a custom action that I patched into Woocommerce.
 *
 * @param $variation_id
 */
function wc_ap_product_variation_attributes_added($variation_id) {
  $post = get_post($variation_id);

  if ($post && $post->post_type == 'product_variation') {
    $price = wc_ap_determine_variation_price($post->ID, $post);
    if ($price > 0) {
      update_post_meta($post->ID, '_price', $price);
    }

    // Update all the currencies if the multicurrency module is on
    if (function_exists('wc_mc_get_currencies')) {
      $currencies = wc_mc_get_currencies(TRUE);

      foreach ($currencies as $slug => $currency) {
        $price = wc_ap_determine_variation_price($post->ID, $post, $slug);
        if ($price > 0) {
          update_post_meta($post->ID, '_price_' . $slug, $price);
        }
      }
    }
  }
}
add_action('product_variation_attributes_added', 'wc_ap_product_variation_attributes_added');

/***********************************************************************************************************************
 * Post meta box for a base price
 **********************************************************************************************************************/
/**
 * Add our meta box to the product post type.
 */
function wc_ap_add_meta_boxes($post_type, $post) {
  global $wpdb;

  // Only show for product edit
  if (empty($post->ID)) {
    return;
  }

  // Only interested in showing up for variable products, which are set using a taxonomy
  $terms = wp_get_object_terms($post->ID, 'product_type', array('fields' => 'names'));
  $product_type = isset($terms[0]) ? sanitize_title($terms[0]) : 'simple';

  if ($post_type == 'product' && $product_type == 'variable') {
    add_meta_box('wc_ap-meta', __('Base Price'), 'wc_ap_post_meta_box', 'product', 'side');

    // Add script that adds some admin aids surrounding variations
    wp_enqueue_script('wc-ap', plugin_dir_url('woocommerce-attribute-pricing/wc-ap.php') . 'js/wc-ap.js');

    $term_prices = get_option('_woo_ap_term_prices');
    $new_term_prices = array();
    // We need the slug, not the term_id, loop through and change it up
    foreach ($term_prices as $term_id => $term_price) {
      $slug = $wpdb->get_var($wpdb->prepare('SELECT slug FROM ' . $wpdb->prefix . 'terms WHERE term_id = %d', $term_id));

      if (!empty($slug)) {
        $new_term_prices[$slug] = $term_price;
      }
    }
    $args = array(
      'term_prices' => $new_term_prices,
      'base_price' => get_post_meta($post->ID, '_base_price', TRUE),
    );
    if (function_exists('wc_mc_get_currencies')) {
      $args['currencies'] = wc_mc_get_currencies(TRUE);
      foreach ($args['currencies'] as $slug => $currency) {
        $args['base_price_' . $slug] = get_post_meta($post->ID, '_base_price_' . $slug, TRUE);
      }
    }
    wp_localize_script('wc-ap', 'wc_ap', $args);

    // Add script to kick off
    add_action('admin_footer', create_function('', 'echo \'<script type="text/javascript">jQuery(document).ready(function() { WC_AP.post_form_enhance_init(); });</script>\';'));
  }
}
add_action('add_meta_boxes', 'wc_ap_add_meta_boxes', 10, 2);

/**
 * Render our post meta box.  This post meta box is a tool to help users
 * manage the base price of a variable product.
 *
 * @param $post
 *  Post object
 */
function wc_ap_post_meta_box($post) {
  $base_price = get_post_meta($post->ID, '_base_price', TRUE);
?>
  <p>
    <label for="_base_price"><?php _e('Base Price ($)'); ?></label>
    <input type="text" name="_base_price" id="_base_price" value="<?php echo esc_attr($base_price); ?>" style="width: 80px;" />
  </p>
  <?php if (function_exists('wc_mc_get_currencies')): ?>
    <?php $currencies = wc_mc_get_currencies(TRUE); ?>
    <?php foreach ($currencies as $slug => $currency): ?>
      <?php $base_price = get_post_meta($post->ID, '_base_price_' . $slug, TRUE); ?>
      <p>
        <label for="_base_price_<?php echo $slug; ?>"><?php echo __('Base Price') . ' ' . $slug . ' (' . get_woocommerce_currency_symbol($slug) . ')'; ?></label>
        <input type="text" name="_base_price_<?php echo $slug; ?>" id="_base_price_<?php echo $slug; ?>" value="<?php echo esc_attr($base_price); ?>" style="width: 80px;" />
      </p>
    <?php endforeach; ?>
  <?php endif; ?>
  <p class="howto"><?php _e('Enter the base price(s) of your variable product.  When you change the base price, the difference between the old base and the new base will be added to all variations'); ?></p>
<?php
}

/**
 * Save post box meta data for products.  Save the base price into some post meta action.
 *
 * @param $post_id
 */
function wc_ap_save_post($post_id, $post) {

  // We are only interested in when product's are saved and under certain conditions
  // @see woocommerce_meta_boxes_save()
  if (empty($post_id) || empty($post) || empty($_POST)) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (is_int(wp_is_post_revision($post))) return;
  if (is_int(wp_is_post_autosave($post))) return;
  if (empty($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' )) return;
  if (!current_user_can('edit_post', $post_id)) return;
  if ($post->post_type != 'product') return;

  $currencies = array('default' => '');
  if (function_exists('wc_mc_get_currencies')) {
    $currencies = array_merge($currencies, wc_mc_get_currencies(TRUE));
  }

  foreach ($currencies as $slug => $currency) {
    // Save the _base_price for the product, update variation prices.  Track the min price to set for the parent
    $min_price = NULL;

    if ($slug == 'default') {
      $base_key = '_base_price';
      $key = '_price';
    }
    else {
      $base_key = '_base_price_' . $slug;
      $key = '_price_' . $slug;
    }

    // Make sure it's been submitted, otherwise we are wasting our time
    if (!isset($_POST[$base_key])) {
      continue;
    }

    $old_base_price = (float)get_post_meta($post_id, $base_key, TRUE);
    $base_price = (float)$_POST[$base_key];

    // Only do stuff when there is a change in the base_price
    if ($old_base_price != $base_price) {
      $diff = $base_price - $old_base_price;

      // Apply the diff to all of the variations
      $args = array(
        'post_parent' => $post_id,
        'post_type' => 'product_variation',
      );
      $children = get_children($args);
      foreach ($children as $child) {
        $curr_price = get_post_meta($child->ID, $key, TRUE);
        $curr_price = $curr_price ? (float)$curr_price : 0;
        $curr_price += $diff;

        // Don't go lower than zero, that makes no sense
        if ($curr_price < 0) {
          $curr_price = 0;
        }
        if (!$min_price || $curr_price < $min_price) {
          $min_price = $curr_price;
        }
        update_post_meta($child->ID, $key, $curr_price);
        update_post_meta($child->ID, $key, $curr_price);
      }

      // Update the _base_price and _price of the parent
      if ($min_price) {
        update_post_meta($post_id, $key, $min_price);
      }
      update_post_meta($post_id, $base_key, $base_price);
    }
  }

}
add_action('save_post', 'wc_ap_save_post', 100, 2);
