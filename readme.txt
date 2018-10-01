=== MoneroV WooCommerce Extension ===
Contributors: miltonf
Tags: monerov, woocommerce, integration, payment, merchant, cryptocurrency, accept monerv, monerov woocommerce
Requires at least: 4.0
Tested up to: 4.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
MoneroV WooCommerce Extension is a Wordpress plugin that allows to accept bitcoins at WooCommerce-powered online stores.

== Description ==

An extension to WooCommerce for accepting MoneroV as payment in your store.

= Benefits =

* Accept payment directly into your personal MoneroV wallet.
* Accept payment in monerov for physical and digital downloadable products.
* Add monerov payments option to your existing online store with alternative main currency.
* Flexible exchange rate calculations fully managed via administrative settings.
* Zero fees and no commissions for monerov payments processing from any third party.
* Automatic conversion to MoneroV via real time exchange rate feed and calculations.
* Ability to set exchange rate calculation multiplier to compensate for any possible losses due to bank conversions and funds transfer fees.

== Installation ==

1. Install "MoneroV WooCommerce extension" WordPress plugin just like any other WordPress plugin.
2. Activate
3. Setup your monerov-wallet-rpc with a view-only wallet
4. Add your monerov-wallet-rpc host address and MoneroV address in the settings panel
5. Click “Enable this payment gateway”
6. Enjoy it!

== Remove plugin ==

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Screenshots == 
1. MoneroV Payment Box
2. MoneroV Options

== Changelog ==

= 0.1 =
* First version ! Yay!

= 0.2 =
* Bug fixes

== Upgrade Notice ==

soon

== Frequently Asked Questions ==

* What is MoneroV ?
MoneroV is a finite, completely private, cryptographically secure, digital cash used across the globe. See https://monerov.org for more information

* What is a MoneroV wallet?
A MoneroV wallet is a piece of software that allows you to store your funds and interact with the MoneroV network. 

* What is monerov-wallet-rpc ?
The monerov-wallet-rpc is an RPC server that will allow this plugin to communicate with the MoneroV network. 

* Why do I see `[ERROR] Failed to connect to monerov-wallet-rpc at localhost port 19090
Syntax error: Invalid response data structure: Request id: 1 is different from Response id: ` ?
This is most likely because this plugin can not reach your monerov-wallet-rpc. Make sure that you have supplied the correct host IP and port to the plugin in their fields. If your monerov-wallet-rpc is on a different server than your wordpress site, make sure that the appropriate port is open with port forwarding enabled.
