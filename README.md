# Woocommerce Attribute Pricing

Add the ability for administrators to affect variable product pricing by attributes.

## Current Limitations

* When using the simple 'Add Variation' button, the attributes are not known when the corresponding product_variation is inserted into the database.  Thus the initial attribute pricing cannot be set, and will need to be set manually. A workaround would be to just always use Link All Variations. 
* Slightly awkward is when a user sets a Base Price and then goes to Link All Variations without a Save in between. The base price is not incorporated on Link All Variations.  Only on the next save is the base price incorporated. Might be nice to update the base price on that text field changing.
