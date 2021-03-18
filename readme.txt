==== CentroBill Payment Gateway Addon ====
Plugin Name: Centrobill WooCommerce Addon
Plugin URI: https://wordpress.org/plugins/wc_centrobill/
Tags: woocommerce plugin centrobill
Requires at least: WP 4.0 & WooCommerce 2.2+
Tested up to: 5.3.1 & WooCommerce 3.7.0
Stable tag: 2.0.0
Version: 2.0.0
License: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==
CentroBill Payment Gateway plugin for WooCommerce

== Preparation ==
* Verify that you are running the latest version of WordPress and Woocommerce. Plugin requires at least WP 4.0 & WooCommerce 2.2+, and has been tested to work with versions up to: WP 5.3.1 & WooCommerce 3.8.1.
* Install any pending updates if necessary.
* In case WooCommerce plugin is not installed, please install it and update it.

== Personal Centrobill API Token and Merchant Portal login credentials ==
* Obtain your personal account's Token from your Centrobill account manager.
* Obtain Centrobill Merchant Portal login credentials from your Centrobill account manager.

== Install the plugin ==
* Download plugin repository as a single zip file
* Log into your WordPress as admin, click on Plugins section in the menu, click Add new.
* Click Upload Plugin, click Choose File to select the zip file from your computer.
* Install the plugin and activate it.

== Plugin configuration ==
* Go to WooCommerce menu, click Settings.
* In Checkout menu tab, under Checkout options click on Centrobill and make sure Enable/Disable option is checked.
* Locate Auth key input form field on a page and paste your API auth key into it.
* If you don't have your Personal Centrobill API Auth Key, please contact your Centrobill account manager.
* Save Changes.

== Setup your account in the Centrobill Merchant Portal ==

= Set up your site =
* Log in to your Centrobill Merchant Portal using login credentials provided by your Centrobill account manager.
* Navigate to Sites&Products sections by selecting the tab in the upper menu.
* Click blue Create Site button.
* Input the name of your site into the Name of site field.
* In the Site type choose Woocommerce website
* Add your website URL to the WordPress URL field. All the following links will be auto-populated (Success URL, Decline URL and IPN URL) and can be changed if you will need it.
* Below on this page, in the Colors menu you can also modify style of the payment page if you want it to be styled to your website.
* Tracking code field is meant for GA pixel or for conversion pixels of other analytical platforms. So, if you want to track sales, you can paste the conversion pixel to this field.
* After you feel you everything is correct and ready click blue Create site button at the bottom.

= Set up your products =
* Navigate to Sites&Products sections by selecting the tab in the upper menu.
* Click orange Manage products button.
* To a new product click blue Add new product button.
* Choose the Currency this product will have.
* Choose Billing model.
* Specify the Price.
* Add the name of the Product your customers will see on the payment page to the Product name field. You can add names in different languages by clicking the green +.
* Please, mind the price users will see on the page will be in the Currency you have chosen for this Product. If you need a price in different currency, please create a new product.
* When everything is ready click the blue Add new button.
In case existing product should be changed, please click Edit button near the product you need to change.

== Verification ==
*Contact your Centrobill account manager to verify your setup
* Please, send the IP address('s) of your server, which will send calls to Centrobill payment feed to your Centrobill account manager.
