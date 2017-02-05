# Magento 2 Per-Product Flatrate Shipping
The Magento 2 Per-Product Flaterate Shipping gives the option of setting  
the flatrate shipping price at the product level. This is the packaged module  
from a Magento StackExchange answer I wrote here:  
http://magento.stackexchange.com/questions/151482/magento2-custom-different-flat-shipping-charge-for-few-products

## Installation
This module has never ran in a production environment and for that  
reason only a dev release is available. Installing with Composer requires the  
the `minimum-stability` value in `{{magento-root}}/composer.json` be changed  
to `dev`.  

In your Magento 2 root directory run:  
`composer require pmclain/module-product-flatrate-shipping`  
`bin/magento setup:upgrade`

## Configuration
Module settings are found in the Magento 2 admin panel under  
Stores->Configuration->Sales->Shipping Methods->Flat Rate

The following configuration should be used for this module to work:  
* Enabled: Yes
* Type: Per Item

The install script will add a product attribute titled 'Flatrate Shipping Price'  
to the Default product attribute set. This value will be used as the item  
item shipping rate if it is greater than the Default Flatrate Price. 

## License
GNU GENERAL PUBLIC LICENSE Version 3