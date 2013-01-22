<?php
/**
 * @file
 * Testable functions.
 */

/*
 * Retrieves the price for a variation
 *
 * @param Array
 * @param Float
 * @return Float
 */
function wc_ap_get_variation_price($prices, $base_price = 0) {
  $final_price = 0;
  $flat_prices = array();
  $diff_price  = 0;

  if ($base_price > 0) {
    $final_price += $base_price;
  }

  foreach ($prices as $price) {
    switch ($price['type']) {
      case 'flat':
        $flat_prices[] = (float)$price['value'];
        break;
      case 'percentile':
        $diff_price += (float)$price['value'] / 100 * $base_price;
        break;
      default:
        $diff_price += (float)$price['value'];
        break;
    }
  }

  // Price should be at least the maximum of all flat prices. Unless the maximum price of all flat prices is negative.
  // Then the price should be at least the base price.
  if (!empty($flat_prices)) {
    $max_flat_price = max($flat_prices);
    if ($max_flat_price > 0) {
      $final_price = $max_flat_price;
    }
  }

  $final_price += $diff_price;

  if ($final_price < 0) {
    return 0;
  }

  return $final_price;
}
