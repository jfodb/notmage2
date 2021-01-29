##TODO:
- move checkout_index_index to theme, so this checkout step doesn't take effect all stores
- Investigate automation JSON (never looked at)
  
##BUG:
- Donation is being taxes
- if you add a donation, return to homepage, add another item to your cart, then go to checkout the existing donation product is removed

### ADMIN SET UP:
1) Stores > Configuration > Catalog > Donation Product (STORE VIEW)
- add fixed donation amounts
2) Catalog > Product > Add Donation Product (similar to Donations store)
- - Set the Store to ODBP
