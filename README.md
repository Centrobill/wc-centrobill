# Centrobill plugin for WooCommerce

## Description
CentroBill Payment Gateway plugin for WooCommerce


##  Plugin Installation Instructions

### Install the plugin
* Download plugin repository as a single zip file
* Log into your Wordpress as admin, click on __Plugins__ section in the menu, click __Add new__
* Click __Upload Plugin__, click __Choose File__ to select the zip file from your computer.
* Install the plugin and activate it.

### Plugin configuration
* Go to WooCommerce menu, click __Settings__
* In __Checkout__ menu tab, under __Checkout options__ click on __Centrobill__ and make sure __Enable/Disable__ option is checked.
* Log into your Centrobill account, Go to __Sites and Products__ menu, click on __Edit site__ button next to your website name.
* Locate __Secret Key__ value on a page and copy it into your clipboard
* Return to WooCommerce settings page, and enter your personal __Secret key__ into the corresponding form field 
* __Save Changes__

### Products configuration
* Log into your Centrobill account, Go to __Sites and Products__ menu, click on __Manage Products__ button next to your website name.
* Follow the instructions and add your products to your website. For each product note SKU name.
* In Wordpress menu select __Products__
* Locate a product and click __Edit__
* Under the __Product data__ menu click on __Attributes__ and then click on __cb_sku_id__. In __Values__ field enter the SKU name for this product and click __Save attributes__
* Repeat last two steps for all your products

## Testing
* Contact your Centrobill account manager to verify your setup


