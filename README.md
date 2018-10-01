# MonerovWP
A WooCommerce extension for accepting MoneroV

## Dependencies
This plugin is rather simple but there are a few things that need to be set up beforehand.

* A web server! Ideally with the most recent versions of PHP and mysql

* A MoneroV wallet. You can find the official wallet [here](https://monerov.org)

* [WordPress](https://wordpress.org)
WordPress is the backend tool that is needed to use WooCommerce and this MoneroV plugin

* [WooCommerce](https://woocommerce.com)
This MoneroV plugin is an extension of WooCommerce, which works with WordPress

* [BCMath](http://php.net/manual/en/book.bc.php)
A PHP extension used for arbitrary precision maths

## Step 1: Activating the plugin
* Downloading: First of all, you will need to download the plugin. You can download the latest release as a .zip file. If you wish, you can also download the latest source code from GitHub. This can be done with the command `git clone` or can be downloaded as a zip file from the GitHub web page.

* Unzip the file monerovwp_release.zip if you downloaded the zip from the releases page.

* Put the plugin in the correct directory: You will need to put the folder named `monerov` from this repo/unzipped release into the WordPress plugins directory. This can be found at `path/to/wordpress/folder/wp-content/plugins`

* Activate the plugin from the WordPress admin panel: Once you login to the admin panel in WordPress, click on "Installed Plugins" under "Plugins". Then simply click "Activate" where it says "MoneroV - WooCommerce Gateway"

## Step 2 Option 1: Use your wallet address and viewkey

* Get your MoneroV wallet address starting with '4'
* Get your wallet secret viewkey from your wallet

A note on privacy: When you validate transactions with your private viewkey, your viewkey is sent to (but not stored on) monerovexplorer.com over HTTPS. This could potentially allow an attacker to see your incoming, but not outgoing, transactions if he were to get his hands on your viewkey. Even if this were to happen, your funds would still be safe and it would be impossible for somebody to steal your money. For maximum privacy use your own monerov-wallet-rpc instance.

## Step 2 Option 2: Get a MoneroV daemon to connect to

### Option 1: Running a full node yourself

To do this: start the MoneroV daemon on your server and leave it running in the background. This can be accomplished by running `./monerovd` inside your MoneroV downloads folder. The first time that you start your node, the MoneroV daemon will download and sync the entire MoneroV blockchain. This can take several hours and is best done on a machine with at least 4GB of ram, an SSD hard drive (with at least 40GB of free space), and a high speed internet connection.

### Option 2: Connecting to a remote node
It is probably easiest to use the official remote node which will automatically connect you to a random node.

### Setup your MoneroV wallet-rpc

* Setup a MoneroV wallet using the monerov-wallet-cli tool. 

* Create a view-only wallet from that wallet for security.

* Start the Wallet RPC and leave it running in the background. This can be accomplished by running `./monerov-wallet-rpc --rpc-bind-port 19092 --disable-rpc-login --log-level 2 --wallet-file /path/viewOnlyWalletFile` where "/path/viewOnlyWalletFile" is the wallet file for your view-only wallet. If you wish to use a remote node you can add the `--daemon-address` flag followed by the address of the node. 

## Step 4: Setup MoneroV Gateway in WooCommerce

* Navigate to the "settings" panel in the WooCommerce widget in the WordPress admin panel.

* Click on "Checkout"

* Select "MoneroV GateWay"

* Check the box labeled "Enable this payment gateway"

* Check either "Use ViewKey" or "Use monerov-wallet-rpc"

If You chose to use viewkey:

* Enter your MoneroV wallet address in the box labeled "MoneroV Address". If you do not know your address, you can run the `address` command in your MoneroV wallet

* Enter your secret viewkey in the box labeled "ViewKey"

If you chose to use monerov-wallet-rpc:

* Enter your MoneroV wallet address in the box labeled "MoneroV Address". If you do not know your address, you can run the `address` command in your MoneroV wallet

* Enter the IP address of your server in the box labeled "MoneroV wallet RPC Host/IP"

* Enter the port number of the Wallet RPC in the box labeled "MoneroV wallet RPC port" (will be `19092` if you used the above example).

Finally:

* Click on "Save changes"


