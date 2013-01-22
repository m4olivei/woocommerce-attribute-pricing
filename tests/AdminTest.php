<?php
/*
 * Woocommerce Attribute Pricing Plugin Tests
 */

class AdminTest extends WP_UnitTestCase {
  public function setUp() {
    parent::setUp();
  }

  // Scenario: Test the most simple of scenarios.
  public function test_wc_ap_get_variation_price_for_simple_scenarios() {
    $test_cases = array(
      // Test no base price and no variable prices.
      '0' => array(
        'prices' => array(),
        'base_price' => 0,
        'expected_result' => 0
      ),
      // Test base price only.
      '1' => array(
        'prices' => array(),
        'base_price' => 100,
        'expected_result' => 100
      ),
      // Test negative base price.
      '2' => array(
        'prices' => array(),
        'base_price' => -100,
        'expected_result' => 0
      ),
    );

    foreach ($test_cases as $test_case) {
      $_result = wc_ap_get_variation_price($test_case['prices'], $test_case['base_price']);
      $this->assertEquals($test_case['expected_result'], $_result);
    }
  }

  // Scenario: Test that flat-priced variables produce expected results.
  public function test_wc_ap_get_variation_price_for_flat_prices() {
    $test_cases = array(
      // Test variable prices and no base price.
      '0' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => 100),
          array('type' => 'flat', 'value' => 200),
          array('type' => 'flat', 'value' => 400),
        ),
        'base_price' => '0',
        'expected_result' => '400',
      ),
      // Test variable prices with base price less than max variable price.
      '1' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => 100),
          array('type' => 'flat', 'value' => 200),
          array('type' => 'flat', 'value' => 400),
        ),
        'base_price' => 100,
        'expected_result' => 400,
      ),
      // Test variable prices with base price greater than max variable price.
      '2' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => 100),
          array('type' => 'flat', 'value' => 200),
          array('type' => 'flat', 'value' => 400),
        ),
        'base_price' => 500,
        'expected_result' => 400,
      ),
      // Test variable prices with mix of positive and negative values and no base price.
      '3' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => -100),
          array('type' => 'flat', 'value' => 200),
          array('type' => 'flat', 'value' => -400),
        ),
        'base_price' => 0,
        'expected_result' => 200,
      ),
      // Test variable prices with mix of positive and negative values and base price greater than max variable price.
      '4' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => -100),
          array('type' => 'flat', 'value' => 200),
          array('type' => 'flat', 'value' => -400),
        ),
        'base_price' => 300,
        'expected_result' => 200,
      ),
      // Test variable prices with mix of positive and negative values and base price less than max variable price.
      '5' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => -100),
          array('type' => 'flat', 'value' => 200),
          array('type' => 'flat', 'value' => -400),
        ),
        'base_price' => 150,
        'expected_result' => 200,
      ),
      // Test variable prices with all negative values.
      '6' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => -100),
          array('type' => 'flat', 'value' => -200),
          array('type' => 'flat', 'value' => -300),
        ),
        'base_price' => 0,
        'expected_result' => 0,
      ),
      // Test variable prices with all negative values and base price.
      '7' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => -100),
          array('type' => 'flat', 'value' => -200),
          array('type' => 'flat', 'value' => -300),
        ),
        'base_price' => 1,
        'expected_result' => 1,
      ),
      // Test variable prices with a mix of positive and negative and decimal places
      '8' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => -100.50),
          array('type' => 'flat', 'value' => 200.89),
          array('type' => 'flat', 'value' => -300.95),
        ),
        'base_price' => 100,
        'expected_result' => 200.89,
      ),
    );

    foreach ($test_cases as $test_case) {
      $_result = wc_ap_get_variation_price($test_case['prices'], $test_case['base_price']);
      $this->assertEquals($test_case['expected_result'], $_result);
    }
  }

  // Scenario: Test that differential-priced variables produce expected results.
  public function test_wc_ap_get_variation_price_for_differential_prices() {
    $test_cases = array(
      // Test that positive- and negative-values when applied in balance do not change base price.
      '0' => array(
        'prices' => array(
          array('type' => 'differential', 'value' => 10),
          array('type' => 'differential', 'value' => -10),
        ),
        'base_price' => 10,
        'expected_result' => 10,
      ),
      '1' => array(
        'prices' => array(
          array('type' => 'differential', 'value' => -90),
          array('type' => 'differential', 'value' => 10),
          array('type' => 'differential', 'value' => -10),
          array('type' => 'differential', 'value' => 90),
        ),
        'base_price' => 0,
        'expected_result' => 0,
      ),
      // Test that positive variable values raise the price.
      '2' => array(
        'prices' => array(
          array('type' => 'differential', 'value' => 5),
          array('type' => 'differential', 'value' => 10),
          array('type' => 'differential', 'value' => 15),
        ),
        'base_price' => 10,
        'expected_result' => 40,
      ),
      // Tests that a positive balance of variable values raises the price.
      '3' => array(
        'prices' => array(
            array('type' => 'differential', 'value' => -5),
            array('type' => 'differential', 'value' => 10),
            array('type' => 'differential', 'value' => 15),
          ),
        'base_price' => 10,
        'expected_result' => 30,
      ),
      // Tests that negative variable values lower the price.
      '4' => array(
        'prices' => array(
          array('type' => 'differential', 'value' => -5),
          array('type' => 'differential', 'value' => -10),
          array('type' => 'differential', 'value' => -15),
        ),
        'base_price' => 40,
        'expected_result' => 10,
      ),
      // Tests that a negative balance of variable values lowers the price.
      '5' => array(
        'prices' => array(
          array('type' => 'differential', 'value' => 5),
          array('type' => 'differential', 'value' => -10),
          array('type' => 'differential', 'value' => -15),
        ),
        'base_price' => 20,
        'expected_result' => 0,
      ),
      // Tests that the price cannot dip below zero.
      '6' => array(
        'prices' => array(
          array('type' => 'differential', 'value' => -5),
          array('type' => 'differential', 'value' => -10),
          array('type' => 'differential', 'value' => -15),
        ),
        'base_price' => 20,
        'expected_result' => 0,
      ),
      '7' => array(
        'prices' => array(
          array('type' => 'differential', 'value' => -5),
          array('type' => 'differential', 'value' => -10),
          array('type' => 'differential', 'value' => -15),
        ),
        'base_price' => -10,
        'expected_result' => 0,
      ),
    );

    foreach ($test_cases as $test_case) {
      $_result = wc_ap_get_variation_price($test_case['prices'], $test_case['base_price']);
      $this->assertEquals($test_case['expected_result'], $_result);
    }
  }

  // Scenario: Test that percentile-priced variables produce expected results.
  function test_wc_ap_get_variation_price_for_percentile_prices() {
    $test_cases = array(
      // Test that positive- and negative-values when applied in balance do not change base price.
      '0' => array(
        'prices' => array(
          array('type' => 'percentile', 'value' => 10),
          array('type' => 'percentile', 'value' => -10),
        ),
        'base_price' => 10,
        'expected_result' => 10,
      ),
      '1' => array(
        'prices' => array(
          array('type' => 'percentile', 'value' => -90),
          array('type' => 'percentile', 'value' => 10),
          array('type' => 'percentile', 'value' => -10),
          array('type' => 'percentile', 'value' => 90),
        ),
        'base_price' => 0,
        'expected_result' => 0,
      ),
      // Test that positive variable values raise the price.
      '2' => array(
        'prices' => array(
          array('type' => 'percentile', 'value' => 5),
          array('type' => 'percentile', 'value' => 10),
          array('type' => 'percentile', 'value' => 15),
        ),
        'base_price' => 10,
        'expected_result' => 13,
      ),
      // Tests that a positive balance of variable values raises the price.
      '3' => array(
        'prices' => array(
          array('type' => 'percentile', 'value' => -5),
          array('type' => 'percentile', 'value' => 10),
          array('type' => 'percentile', 'value' => 15),
        ),
        'base_price' => 10,
        'expected_result' => 12,
      ),
      // Tests that negative variable values lower the price.
      '4' => array(
        'prices' => array(
          array('type' => 'percentile', 'value' => -5),
          array('type' => 'percentile', 'value' => -10),
          array('type' => 'percentile', 'value' => -15),
        ),
        'base_price' => 40,
        'expected_result' => 28,
      ),
      // Tests that a negative balance of variable values lowers the price.
      '5' => array(
        'prices' => array(
          array('type' => 'percentile', 'value' => 5),
          array('type' => 'percentile', 'value' => -10),
          array('type' => 'percentile', 'value' => -15),
        ),
        'base_price' => 20,
        'expected_result' => 16,
      ),
      // Tests that the price cannot dip below zero.
      '6' => array(
        'prices' => array(
          array('type' => 'percentile', 'value' => -101),
        ),
        'base_price' => 20,
        'expected_result' => 0,
      ),
    );

    foreach ($test_cases as $test_case) {
      $_result = wc_ap_get_variation_price($test_case['prices'], $test_case['base_price']);
      $this->assertEquals($test_case['expected_result'], $_result);
    }
  }

  // Scenario: Test that mixed-types of variables produce expected results.
  function test_wc_ap_get_variation_price_for_mixed_type_prices() {
    $test_cases = array(
      // Test that the differential is applied to the max fixed value when the max fixed value is less than the base.
      '0' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => 100),
          array('type' => 'flat', 'value' => 50),
          array('type' => 'differential', 'value' => '10'),
        ),
        'base_price' => 200,
        'expected_result' => 110,
      ),
      // Test that the differential is applied to the max fixed value when the max fixed value is greater than the base.
      '1' => array(
        'prices' => array(
            array('type' => 'flat', 'value' => 100),
            array('type' => 'flat', 'value' => 250),
            array('type' => 'differential', 'value' => '10'),
          ),
        'base_price' => 200,
        'expected_result' => 260,
      ),
      '2' => array(
        'prices' => array(
            array('type' => 'flat', 'value' => 100),
            array('type' => 'flat', 'value' => -200),
            array('type' => 'differential', 'value' => 100),
            array('type' => 'differential', 'value' => 1000),
            array('type' => 'differential', 'value' => -900),
            array('type' => 'percentile', 'value' => 10),
            array('type' => 'percentile', 'value' => 100),
            array('type' => 'percentile', 'value' => -100),
        ),
        'base_price' => 100,
        'expected_result' => 310,
      ),
      // Test that the base price is being used for percentiles, even when there is a flat price.
      '3' => array(
        'prices' => array(
          array('type' => 'flat', 'value' => 100),
          array('type' => 'flat', 'value' => -200),
          array('type' => 'differential', 'value' => 100),
          array('type' => 'differential', 'value' => 1000),
          array('type' => 'differential', 'value' => -900),
          array('type' => 'percentile', 'value' => 10),
          array('type' => 'percentile', 'value' => 100),
          array('type' => 'percentile', 'value' => -100),
        ),
        'base_price' => 1000,
        'expected_result' => 400,
      ),
    );

    foreach ($test_cases as $test_case) {
      $_result = wc_ap_get_variation_price($test_case['prices'], $test_case['base_price']);
      $this->assertEquals($test_case['expected_result'], $_result);
    }
  }
}