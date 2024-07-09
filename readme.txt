=== CentroBill Payment Gateway for WooCommerce ===
Contributors: centrobill
Tags: woocommerce, centrobill, payment gateway, online payment, credit card, sepa
Requires at least: 5.1
Tested up to: 6.1
Requires PHP: 5.6
Stable tag: 2.2.7
License: GPL v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Allows you to use CentroBill payment gateway with the WooCommerce plugin.

== Description ==
The CentroBill plugin extends WooCommerce and allow you to take payments directly on your store using CentroBill's API.
Plugin also supports the [WooCommerce Subscriptions extension](https://woocommerce.com/products/woocommerce-subscriptions/).

Please go to the site [centrobill.com](https://centrobill.com/home#contactus) to create a merchant account and start receiving payments.

= Personal CentroBill API Token and Merchant Portal login credentials =
* Obtain your personal account's **Token** from your CentroBill account manager.
* Obtain **CentroBill Merchant Portal** login credentials from your CentroBill account manager.

= Setup your account in the CentroBill Merchant Portal =
* Log in to your **CentroBill Merchant Portal** using login credentials provided by your CentroBill account manager.
* Navigate to *Sites&Products* sections by selecting the tab in the upper menu.
* Click blue *Create Site* button.
* Input the name of your site into the *Name of site* field.
* In the *Site type* choose *Woocommerce website*.
* Add your website URL to the *WordPress URL* field. All the following links will be auto-populated (*Success URL, Decline URL* and *IPN URL*) and can be changed if you will need it.
* Below on this page, in the Colors menu you can also modify style of the payment page if you want it to be styled to your website.
* Tracking code field is meant for GA pixel or for conversion pixels of other analytical platforms. So, if you want to track sales, you can paste the conversion pixel to this field.
* After you feel you everything is correct and ready click blue *Create site* button at the bottom.

= Verification =
* Contact your CentroBill account manager to verify your setup.
* Please, send the IP address('s) of your server, which will send calls to CentroBill payment feed to your CentroBill account manager.

== Installation ==
Verify that you are running the latest version of WordPress and WooCommerce.
Plugin requires at least WordPress 5.1 & WooCommerce 3.5, and has been tested to work with versions up to: WP 6.1 & WooCommerce 7.0.1.

= Automatic installation =
* Download plugin repository as a single zip file.
* Log into your WordPress as admin, click on Plugins section in the menu, click **Add new**.
* Click **Upload Plugin**, click **Choose File** to select the zip file from your computer or just type this plugin's name.
* Install the plugin and activate it.
* Navigate to plugin settings and fill settings.

= Manual installation =
* Unpack and upload the plugin folder to the **/wp-content/plugins/** directory.
* Activate the plugin through the **Plugins** menu in WordPress.
* Navigate to plugin settings and configure.

= Updating =
Automatic updates should work like a charm.
As always though, ensure you backup your site just in case.

= Plugin configuration =
To configure the CentroBill Payment Gateway plugin correctly, please follow the steps below:

* Go to *WooCommerce* menu > *Settings* and click on *Payments menu tab*.
* In *Payments menu tab* click on Centrobill and make sure *Enable/Disable* option is checked.
* Locate *Authentication key* input form field on a page and paste your API auth key into it.
* Locate *Site ID* input form field on a page and paste your CentroBill's site ID into it.
* Save changes.

If you don't have your Personal CentroBill API Auth Key, please contact your CentroBill account manager.


== Frequently Asked Questions ==
= Do I need a merchant account before I can use the CentroBill Payment Gateway plugin? =
Yes.

= Does this plugin support subscriptions? =
Yes. This plugin supports recurring payments those created with [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/).

= How to migrate from plugin v1 to v2? =
* Install a new plugin or update current.
* Go to *WooCommerce* > *Settings* and click on *Payments menu tab*.
* In *Payments menu tab* click on *Centrobill Credit Cards*.
* Locate *Authentication key* input form field on a page with a value (ex. **AuthKey:siteId**).
* Split the value by ":". **AuthKey** leave in the *Authentication key* field and **siteId** move to the *Site ID* input field.
* Save changes.

= How to apply VAT for EU users on top of the product price =
1. **Enabling taxes:**
Go to your WooCommerce tab WooCommerce > Settings > General.
Select 'Geolocate' option in Default customer location button. (Screenshot "Tax. General options")
Select the Enable Taxes and Tax Calculations checkbox.

2. **Configuring Tax Options:**
WooCommerce > Settings > Tax. This tab is only visible if taxes are enabled.
Make sure that following options are set as it is described on a screenshot.
(Select all your options as it described on a screenshot "Tax options")

3. **Importing and exporting EU VAT rates:**
WooCommerce > Settings > Tax > Standard rates.
Click on 'Import CSV' button on the bottom right to upload country codes with corresponding VAT rates.
[Link to CSV file](https://raw.githubusercontent.com/Centrobill/wc-centrobill/master/vat_rates.csv)

4. **Configuring Tax for your product.**
Go to Product > Choose your product > Click Product data
(Enable Tax status and Tax class as it described on a screenshot "Tax. Product data")

Important! Step 4 should be repeated for every product you want to sell.
After all this has been setup taxes for EU users would be added on top of the product price and you will receive full product price as a revenue.
At the same time EU VAT will be calculated correctly and paid accordingly.

Just in case, WooCommerce detailed manual about the tax setup is available by following link: [Setting up Taxes in WooCommerce](https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce)

== Screenshots ==
1. Range of payment methods such as credit cards and alternative payment methods.
2. The plugin settings screen used to configure the Centrobill payment gateway.
3. Change the title and description for every payment gateway.
4. Pay with a credit card.
5. Pay with an alternative payment method.
6. Tax. General options.
7. Tax options.
8. Tax. Product data.

== Changelog ==
= 2.2.7 - 2024-07-05 =
* Update - `wc_centrobill_get_useragent` and `wc_centrobill_get_browser_accept_header` functions explicitly return strings.
* Fix - Remove `userAgent` and `browserAcceptHeader` from default consumer data preparation in `WC_Centrobill_Api::prepareConsumerData` and add them conditionally.

= 2.2.6 - 2022-11-08 =
* Add - Clear out plugin options after uninstall from the site (uninstall.php)

= 2.2.5 - 2022-07-15 =
* Update - Enable plugin availability for all stores

= 2.2.4 - 2021-12-17 =
* Fix - Wrong amount type (must be a number)

= 2.2.3 - 2021-10-18 =
* Fix - Displaying available payment methods on the checkout page affects other gateways
* Fix - Link is now correctly in readme.txt

[See changelog for all versions](https://raw.githubusercontent.com/Centrobill/wc-centrobill/master/changelog.txt)

== Upgrade Notice ==
= 2.0.5 =
* Updated readme.txt

= 2.0.4 =
* Updated readme.txt & changelog.txt

= 1.0.0 =
* Initial release.