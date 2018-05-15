# Centrobill plugin for WooCommerce  

## Description
CentroBill Payment Gateway plugin for WooCommerce   




##  Plugin Installation Instructions  

### Preparation 
* Verify that you are running the latest version of __Wordpress__ and __Woocommerce__. Plugin requires at least __WP 4.0__  & __WooCommerce 2.2+__, and has been tested to work with versions up to: __WP 4.8__ & __WooCommerce 3.1.1__.
* Install any pending updates if necessary. 
* In case __WooCommerce__ plugin is not installed, please install it and update it.

### Personal Centrobill API Token and Merchant Portal login credentials
* Obtain your personal account's __Token__ from your Centrobill account manager.
* Obtain __Centrobill Merchant Portal__ login credentials from your Centrobill account manager.

### Install the plugin 
* Download plugin repository as a single zip file 
* Log into your Wordpress as admin, click on __Plugins__ section in the menu, click __Add new__. 
* Click __Upload Plugin__, click __Choose File__ to select the zip file from your computer. 
* Install the plugin and activate it.  

### Plugin configuration 
* Go to __WooCommerce__ menu, click __Settings__. 
* In __Checkout__ menu tab, under __Checkout options__ click on __Centrobill__ and make sure __Enable/Disable__ option is __checked__. 
* Locate __Auth key__ input form field on a page and paste your __API auth key__ into it. 
* If you don't have your Personal Centrobill __API Auth Key__, please contact your Centrobill account manager.
* __Save Changes__.  

## Setup your account in the Centrobill Merchant Portal

### Set up your site
* Log in to your __Centrobill Merchant Portal__ using login credentials provided by your Centrobill account manager.
* Navigate to __Sites&Products__ sections by selecting the tab in the upper menu.
* Click blue __Create Site__ button.
* Input the name of your site into the __Name of site__ field. 
* In the __Site type__ choose __Woocommerce website__
* Add your website URL to the __Wordpress URL__ field. All the following links will be auto-populated (Success URL, Decline URL and IPN URL) and can be changed if you will need it.
* Below on this page, in the __Colors__ menu you can also modify style of the payment page if you want it to be styled to your website.
* __Tracking code__ field is meant for GA pixel or for conversion pixels of other analytical platforms. So, if you want to track sales, you can paste the conversion pixel to this field.
* After you feel you everything is correct and ready click blue __Create site__ button at the bottom.

### Set up your products
* Navigate to __Sites&Products__ sections by selecting the tab in the upper menu.
* Click orange __Manage products__ button.
* To a new product click blue __Add new product__ button.
* Choose the __Currency__ this product will have.
* Choose __Billing model__.
* Specify the __Price__.
* Add the name of the Product your customers will see on the payment page to the __Product name__ field. You can add names in different languages by clicking the green __+__. 
* Please, mind the price users will see on the page will be in the Currency you have chosen for this Product. If you need a price in different currency, please create a new product.
* When everything is ready click the blue __Add new__ button.
* In case existing product should be changed, please click __Edit__ button near the product you need to change.

## Verification 
* Contact your Centrobill account manager to verify your setup
* Please, send the IP address('s) of your server, which will send calls to Centrobill payment feed to your Centrobill account manager.
