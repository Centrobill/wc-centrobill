![wc centrobill logo](assets/images/github-logo.png?raw=true)

![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/centrobill-payment-gateway?style_=flat-square)
![WordPress Plugin: Tested WP Version](https://img.shields.io/wordpress/plugin/tested/centrobill-payment-gateway?color=green&logo_=wordpress&style_=flat-square)
![License](https://img.shields.io/github/license/Centrobill/wc-centrobill?style_=flat-square&color=green)
[![Release to WordPress.org](https://github.com/Centrobill/wc-centrobill/actions/workflows/wordpress-release.yml/badge.svg)](https://github.com/Centrobill/wc-centrobill/actions/workflows/wordpress-release.yml)

[Centrobill](https://centrobill.com) Payment Gateway plugin for WooCommerce.

##  Getting Started

### Minimal Requirements
* PHP >= 5.6
* WordPress >= 5.1
* WooCommerce >= 3.5

Verify that you are running the latest version of **WordPress** and **WooCommerce**. \
Plugin requires at least **WP 5.1** & **WooCommerce 3.5**, and has been tested to work with versions up to: **WP 6.1** & **WooCommerce 7.0.1**.

### Installation

#### From WordPress.org
* Log in to your WordPress site as an administrator.
* Go to Plugins menu, then click **Add New**.
* Search for **"Centrobill Payment Gateway"**.
* Click **Install Now**, and then **Activate**.

#### Manually
* Download plugin repository as a single zip file
* Log into your WordPress site as an administrator, click on **Plugins** section in the menu, click **Add new**.
* Click **Upload Plugin**, click **Choose File** to select the zip file from your computer.
* Install the plugin and activate it.


### Personal Centrobill API Token and Merchant Portal login credentials
* Obtain your personal account's **Token** from your Centrobill account manager.
* Obtain **Centrobill Merchant Portal** login credentials from your Centrobill account manager.

### Plugin configuration
* Go to **WooCommerce** menu -> **Settings** and click on **Payments** menu tab.
* In **Payments** menu tab click on **Centrobill Credit Cards** and make sure **Enable/Disable** option is **checked**.
* Locate **Authentication key** input form field on a page and paste your **API auth key** into it.
* Locate **Site ID** input form field on a page and paste your **site ID** into it.
* If you don't have your Personal Centrobill **API Auth Key**, please contact your Centrobill account manager.
* **Save Changes**.

### Migrate from v1 to v2
* Install a new plugin (see **Install the plugin** section)
* Go to **WooCommerce** -> **Settings** and click on **Payments** menu tab.
* In **Payments** menu tab click on **Centrobill Credit Cards**.
* Locate **Authentication key** input form field on a page with a value (ex. **{AuthKey}:{siteId}**).
* Split the value by "**:**". **{AuthKey}** leave in the **Authentication key** field and **{siteId}** move to the **Site ID** input field.
* **Save Changes**.

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
Link to [CSV file](https://raw.githubusercontent.com/Centrobill/wc-centrobill/master/vat_rates.csv)

* **Configuring Tax for your product.** \
Go to Product > Choose your product > Click Product data
(Enable Tax status and Tax class as it described on a screenshot "Tax. Product data")

Important! Step 4 should be repeated for every product you want to sell.
After all this has been setup taxes for EU users would be added on top of the product price and you will receive full product price as a revenue.
At the same time EU VAT will be calculated correctly and paid accordingly.

Just in case, WooCommerce detailed manual about the tax setup is available by following link: [Setting up Taxes in WooCommerce](https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce)


## Setup your account in the Centrobill Merchant Portal

### Set up your site
* Log in to your **Centrobill Merchant Portal** using login credentials provided by your Centrobill account manager.
* Navigate to **Sites&Products** sections by selecting the tab in the upper menu.
* Click blue **Create Site** button.
* Input the name of your site into the **Name of site** field.
* In the **Site type** choose **WooCommerce website**
* Add your website URL to the **WordPress URL** field. All the following links will be auto-populated (Success URL, Decline URL and IPN URL) and can be changed if you will need it.
* Below on this page, in the **Colors** menu you can also modify style of the payment page if you want it to be styled to your website.
* **Tracking code** field is meant for GA pixel or for conversion pixels of other analytical platforms. So, if you want to track sales, you can paste the conversion pixel to this field.
* After you feel you everything is correct and ready click blue **Create site** button at the bottom.

### Set up your products
* Navigate to **Sites&Products** sections by selecting the tab in the upper menu.
* Click orange **Manage products** button.
* To a new product click blue **Add new product** button.
* Choose the **Currency** this product will have.
* Choose **Billing model**.
* Specify the **Price**.
* Add the name of the Product your customers will see on the payment page to the **Product name** field. You can add names in different languages by clicking the green **+**.
* Please, mind the price users will see on the page will be in the Currency you have chosen for this Product. If you need a price in different currency, please create a new product.
* When everything is ready click the blue **Add new** button.
* In case existing product should be changed, please click **Edit** button near the product you need to change.

## Verification 
* Contact your Centrobill account manager to verify your setup
* Please, send the IP address('s) of your server, which will send calls to Centrobill payment feed to your Centrobill account manager.
