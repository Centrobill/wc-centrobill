# Centrobill plugin for WooCommerce  
Version 2.2.3

## Description
CentroBill Payment Gateway plugin for WooCommerce   

##  Plugin Installation Instructions  

### Preparation 
* Verify that you are running the latest version of __WordPress__ and __WooCommerce__. Plugin requires at least __WP 4.7__  & __WooCommerce 3.0__, and has been tested to work with versions up to: __WP 5.7.1__ & __WooCommerce 5.2.2__.
* Install any pending updates if necessary. 
* In case __WooCommerce__ plugin is not installed, please install it and update it.

### Personal Centrobill API Token and Merchant Portal login credentials
* Obtain your personal account's __Token__ from your Centrobill account manager.
* Obtain __Centrobill Merchant Portal__ login credentials from your Centrobill account manager.

### Install the plugin 
* Download plugin repository as a single zip file 
* Log into your WordPress as admin, click on __Plugins__ section in the menu, click __Add new__. 
* Click __Upload Plugin__, click __Choose File__ to select the zip file from your computer. 
* Install the plugin and activate it.  

### Plugin configuration
* Go to __WooCommerce__ menu -> __Settings__ and click on __Payments__ menu tab.
* In __Payments__ menu tab click on __Centrobill Credit Cards__ and make sure __Enable/Disable__ option is __checked__.
* Locate __Authentication key__ input form field on a page and paste your __API auth key__ into it.
* Locate __Site ID__ input form field on a page and paste your __site ID__ into it.
* If you don't have your Personal Centrobill __API Auth Key__, please contact your Centrobill account manager.
* __Save Changes__.

### Migrate from v1 to v2
* Install a new plugin (see __Install the plugin__ section)
* Go to __WooCommerce__ -> __Settings__ and click on __Payments__ menu tab.
* In __Payments__ menu tab click on __Centrobill Credit Cards__.
* Locate __Authentication key__ input form field on a page with a value (ex. __{AuthKey}:{siteId}__).
* Split the value by "__:__". __{AuthKey}__ leave in the __Authentication key__ field and __{siteId}__ move to the __Site ID__ input field.
* __Save Changes__.

### How to apply VAT for EU users on top of the product price
* **Enabling taxes:** \
Go to your WooCommerce tab WooCommerce > Settings > General.
Select 'Geolocate' option in Default customer location button. (Screenshot "Tax. General options")
Select the Enable Taxes and Tax Calculations checkbox.

* **Configuring Tax Options:** \
WooCommerce > Settings > Tax. This tab is only visible if taxes are enabled.
Make sure that following options are set as it is described on a screenshot.
(Select all your options as it described on a screenshot "Tax options")

* **Importing and exporting EU VAT rates:** \
WooCommerce > Settings > Tax > Standard rates.
Click on 'Import CSV' button on the bottom right to upload country codes with corresponding VAT rates.
[Link to CSV file](https://raw.githubusercontent.com/Centrobill/wc-centrobill/master/vat_rates.csv)

* **Configuring Tax for your product.** \
Go to Product > Choose your product > Click Product data
(Enable Tax status and Tax class as it described on a screenshot "Tax. Product data")

Important! Step 4 should be repeated for every product you want to sell.
After all this has been setup taxes for EU users would be added on top of the product price and you will receive full product price as a revenue.
At the same time EU VAT will be calculated correctly and paid accordingly.

Just in case, WooCommerce detailed manual about the tax setup is available by following link: [Setting up Taxes in WooCommerce](https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce)


## Setup your account in the Centrobill Merchant Portal

### Set up your site
* Log in to your __Centrobill Merchant Portal__ using login credentials provided by your Centrobill account manager.
* Navigate to __Sites&Products__ sections by selecting the tab in the upper menu.
* Click blue __Create Site__ button.
* Input the name of your site into the __Name of site__ field. 
* In the __Site type__ choose __WooCommerce website__
* Add your website URL to the __WordPress URL__ field. All the following links will be auto-populated (Success URL, Decline URL and IPN URL) and can be changed if you will need it.
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
