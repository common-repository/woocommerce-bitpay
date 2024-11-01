=== WooCommerce BitPay ===
Contributors: claudiosanches
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RBVENSVSKY7JC
Tags: woocommerce, bitpay, bitcoin
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds BitPay gateway to the WooCommerce plugin

== Description ==

### Add BitPay gateway to WooCommerce ###

WooCommerce BitPay is a Bitcoin payment gateway for WooCommerce.

Please notice that WooCommerce must be installed and actived.

The WooCommerce BitPay plugin was developed without any incentive from [BitPay](https://bitpay.com/). None of the plugin developer has ties to this company.

= Contribute =

You can contribute to the source code in our [GitHub](https://github.com/claudiosmweb/woocommerce-bitpay) page.

== Installation ==

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin;
* Create an account on [BitPay](http://bitpay.com/);
* Navigate to WooCommerce -> Settings -> Payment Gateways, choose BitPay and fill in your API Key ID;
* And lastly navigate to WooCommerce -> Settings -> Inventory and clear the `Hold Stock (minutes)` option.

== Frequently Asked Questions ==

= What kind of license is the plugin? =

* This plugin is released under a GPL license.

= What is needed to use this plugin? =

* WooCommerce installed and active;
* One account on [BitPay](https://bitpay.com/ "BitPay").
* Generate an [API Key](https://bitpay.com/api-keys).

= Which currencies the plugin works? =

The pricing currencies currently supported are USD, EUR, BTC, and all of the codes listed on [BitPay : Bitcoin Exchange Rates](https://bitpay.com/bitcoin-exchange-rates).

To use BTC currency use the [WooCommerce BTC Currency](http://wordpress.org/extend/plugins/woocommerce-btc-currency/) plugin.

== Screenshots ==

1. Settings page.
2. Checkout page.
3. Payment page.

== Changelog ==

= 1.2.0 - 14/08/2013 =

* Improved the source code.
* Added `Transaction Speed` option.
* Added `Full Notifications` option.
* Added buyer information in BitPay order data.
* Fixed the IPN.
* Added `woocommerce_bitpay_icon` filter.
* Added `woocommerce_bitpay_supported_currencies` filter.
* Improved the order status change.

= 1.1.2 - 13/04/2013 =

* Fixed the IPN Request validation.

= 1.1.1 - 08/04/2013 =

* Fixed the IPN Request legacy.

= 1.1 - 07/04/2013 =

* Fixed automatic data return in version 2.0.0 or higher of WooCommerce.

= 1.0.2 - 06/03/2013 =

* Fixed compatibility with WooCommerce 2.0.0 or later.

= 1.0.1 - 08/02/2013 =

* Fixed hook responsible for saving options for the WooCommerce version 2.0 RC.

= 1.0 =

* Initial Release.

== Upgrade Notice ==

= 1.2.0 =

* Improved the source code.
* Added `Transaction Speed` option.
* Added `Full Notifications` option.
* Added buyer information in BitPay order data.
* Fixed the IPN.
* Added `woocommerce_bitpay_icon` filter.
* Added `woocommerce_bitpay_supported_currencies` filter.
* Improved the order status change.

== License ==

WooCommerce BitPay is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

WooCommerce BitPay is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with WooCommerce BitPay. If not, see <http://www.gnu.org/licenses/>.
