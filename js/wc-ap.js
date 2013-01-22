/**
 * @file
 */
// Init namespace
var WC_AP = WC_AP || {};

(function($) {

  /**
   * Add error checking to the add/edit tag forms.
   */
  WC_AP.tag_form_init = function() {

    $('form#edittag').submit(function() {
      var we_good = true;

      // Clear messages
      $('.wrap .updated, .wrap .error').remove();

      $('[name^=attribute_price]').each(function() {
        var $this = $(this),
          price = $this.val();

        if (price != '' && !is_flat_price(price) && !is_differential_price(price) && !is_percentile_price(price)) {
          // Show error, don't submit form
          $('.wrap h2').after('<div class="wc-ap-message error"><p>' + wc_ap.invalid_price + '</p></div>');
          we_good = false;
        }
      });

      return we_good;
    });

    $('form#addtag #submit').click(function() {
      var we_good = true;

      $('[name^=attribute_price]').each(function() {
        var $this = $(this),
          price = $this.val();

        if (price != '' && !is_flat_price(price) && !is_differential_price(price) && !is_percentile_price(price)) {
          // Show error, don't submit form
          $this.parents('.form-field').addClass('form-invalid');
          we_good = false;
        }
        else {
          // Clear error
          $this.parents('.form-field').removeClass('form-invalid');
        }
      });

      return we_good;
    });
  };

  /**
   * Enhnace the post form for variable products to aid with Add Variation.
   */
  WC_AP.post_form_enhance_init = function() {

    // NOTE: here we are taking advantage of the fact that Woocommerce assigns a class 'handle' to the h3 of a variation
    // added with the 'Add Variation' button.  This is a fragile assumption that should be carefully considered when
    // upgrading.  However, without the handle class, it will degrade gracefully.
    $('body').on('change', '.woocommerce_variation.wc-metabox h3.handle select', function() {
      var $this = $(this),
        term_price,
        new_price,
        attributes = [],
        $variation = $this.parents('.woocommerce_variation'),
        $price = $variation.find('[name^=variable_price\\[]'),
        curr_price = $price.val(),
        currency;

      // Look up the price if there is one
      $variation.find('select').each(function() {
        var value = $(this).val();

        if (value) {
          attributes.push(value);
        }
      });

      new_price = determine_variation_price(attributes);

      if (new_price >= 0) {
        $price.val(new_price);
      }

      if (wc_ap.currencies) {
        for (currency in wc_ap.currencies) {
          new_price = determine_variation_price(attributes, currency);

          if (new_price >= 0) {
            $variation.find('[name^=variable_price_' + currency + ']').val(new_price);
          }
        }
      }
    });
  }

  /**
   * Does the given price string represent a flat price?
   *
   * @param price
   */
  function is_flat_price(price) {
  	if (price.search(/\d/) >= 0 && price.search(/^\d*\.?\d*$/) >= 0) {
  		return true;
    }
  }

  /**
   * Does the given price string represent a percentile price?
   * Allowed format is a +/- sign followed by a numeric value, followed by a % sign.
   *
   * @param price
   */
  function is_percentile_price(price) {

  	if (price.search(/\d/) >= 0 && price.search(/^(-|\+)\d*\.?\d*%$/) >= 0) {
  		return true;
    }
  }

  /**
   * Does the given price string represent differential price?
   *
   * @param price
   */
  function is_differential_price(price) {
    if (price.search(/\d/) >= 0 && price.search(/^(-|\+)\d*\.?\d*$/) >= 0) {
  		return true;
    }
  }

  function determine_variation_price(attributes, currency) {
    var term_price,
      i,
      flat = [],
      diff = 0,
      temp,
      price = (currency ? parseFloat(wc_ap['base_price_' + currency]) : parseFloat(wc_ap.base_price)) || 0;
    currency = currency || '';

    for (i = 0; i < attributes.length; i++) {
      if (wc_ap.term_prices[attributes[i]]) {
        term_price = wc_ap.term_prices[attributes[i]];

        if (currency) {
          term_price = term_price['price_' + currency] ? term_price['price_' + currency] : '';
        }
        else {
          term_price = term_price['price'] ? term_price['price'] : '';
        }

        if (is_flat_price(term_price)) {
          flat.push(term_price);
        }
        else if (is_differential_price(term_price)) {
          term_price = parseFloat(term_price);
          if (!isNaN(term_price)) {
            diff += term_price;
          }
        }
        else if (is_percentile_price(term_price)) {
          term_price = parseFloat(term_price);
          if (!isNaN(term_price)) {
            diff += term_price / 100 * price;
          }
        }
      }
    }

    // Variation price should at least be the maximum of all flat prices
    if (flat.length > 0) {
      for (i = 0; i < flat.length; i++) {
        if (temp == null || flat[i] > temp) {
          temp = flat[i];
        }
        if (temp < 0) {
          temp = 0;
        }
      }
      price = temp;
    }
    price += diff;

    return price;
  }

})(jQuery);