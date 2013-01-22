# Woocommerce Attribute Pricing

Add the ability for administrators to affect variable product pricing by attributes.

## Usage

* Enable the plugin in the usual way.
* Once enabled you will find two new fields on each Attribute term labeled 'Attribute Price' and 'Apply to current variations?'.  Read the descriptions there to use them properly.
* There is also a post meta box added to the Product add/edit screen labeled 'Base Price'.  This will allow you to set a starting price point for a product, which the attribute prices will add to to end up at the price for the variable product.

## Current Limitations

* Build on Woocommerce 1.6.5.1.  I make no guarantees about it working with other versions as this module is extreamly tightly coupled to Woocommerce.
* When using the simple 'Add Variation' button, the attributes are not known when the corresponding product_variation is inserted into the database.  Thus the initial attribute pricing cannot be set, and will need to be set manually. A workaround would be to just always use Link All Variations. 
* Slightly awkward is when a user sets a Base Price and then goes to Link All Variations without a Save in between. The base price is not incorporated on Link All Variations.  Only on the next save is the base price incorporated. Might be nice to update the base price on that text field changing.

## Credits

Built by the fine people at Peapod Studios (http://peapod.ca).  Sponsored by The Fairtrade Jewllery Company (http://ftjco.com).
