##TODO:
- move checkout_index_index to theme, so this checkout step doesn't take effect all stores
- Skip donation step if cart already contains a donation
- Donation is being taxes
- Figure out why "Checkout" has to be enabled on the donation products
    - Hide donation block on final checkout (currently hidden with CSS on Payment page)
- Investigate automation JSON (never looked at)




Investigate: 
open one modal, close it, then open another modal--- issues?

Knockout "Unable to process binding" issues? Try blowing away the static content:
- rm -rf pub/static/frontend/

html: function(){return content }"
Message: Unexpected token l in JSON at position 271
