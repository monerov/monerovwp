<?php

/* 
 * Main Gateway of MoneroV using a daemon online 
 * Authors: Serhack and cryptochangements
 */

require_once("cryptonote.php");

class MoneroV_Gateway extends WC_Payment_Gateway
{
    private $reloadTime = 17000;
    private $discount;
    private $confirmed = false;
    private $monerov_daemon;
    private $non_rpc = false;
    private $zero_cofirm = false;
    private $cryptonote;
    private $testnet = false;

    function __construct()
    {
        $this->id = "monerov_gateway";
        $this->method_title = __("MoneroV GateWay", 'monerov_gateway');
        $this->method_description = __("MoneroV Payment Gateway Plug-in for WooCommerce. You can find more information about this payment gateway on our website. You'll need a daemon online for your address.", 'monerov_gateway');
        $this->title = __("MoneroV Gateway", 'monerov_gateway');
        $this->version = "2.0";
        //
        $this->icon = apply_filters('woocommerce_offline_icon', '');
        $this->has_fields = false;

        $this->log = new WC_Logger();

        $this->init_form_fields();
        $this->host = $this->get_option('daemon_host');
        $this->port = $this->get_option('daemon_port');
        $this->address = $this->get_option('monerov_address');
        $this->viewKey = $this->get_option('viewKey');
        $this->discount = $this->get_option('discount');
        $this->accept_zero_conf = $this->get_option('zero_conf');
        
        $this->use_viewKey = $this->get_option('use_viewKey');
        $this->use_rpc = $this->get_option('use_rpc');
        
        $env = $this->get_option('environment');
        
        if($this->use_viewKey == 'yes')
        {
            $this->non_rpc = true;
        }
        if($this->use_rpc == 'yes')
        {
            $this->non_rpc = false;
        }
        if($this->accept_zero_conf == 'yes')
        {
            $this->zero_confirm = true;
        }
        
        if($env == 'yes')
        {
            $this->testnet = true;
        }

        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option('title' );
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        add_action('admin_notices', array($this, 'do_ssl_check'));
        add_action('admin_notices', array($this, 'validate_fields'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'instruction'));
        if (is_admin()) {
            /* Save Settings */
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_filter('woocommerce_currencies', array($this,'add_my_currency'));
            add_filter('woocommerce_currency_symbol', array($this,'add_my_currency_symbol'), 10, 2);
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 2);
        }
        $this->monerov_daemon = new MoneroV_Library($this->host, $this->port);
        $this->cryptonote = new Cryptonote();
	    
	$this->supports = array( 'subscriptions', 'products' );
	$this->supports = array( 
               'products', 
               'subscriptions',
               'subscription_cancellation', 
               'subscription_suspension', 
               'subscription_reactivation',
               'subscription_amount_changes',
               'subscription_date_changes',
               'subscription_payment_method_change'
          );
    }

    public function get_icon()
    {
        return apply_filters('woocommerce_gateway_icon', "<img src='http://cdn.monerointegrations.com/logomonerov.png' />");
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable / Disable', 'monerov_gateway'),
                'label' => __('Enable this payment gateway', 'monerov_gateway'),
                'type' => 'checkbox',
                'default' => 'no'
            ),

            'title' => array(
                'title' => __('Title', 'monerov_gateway'),
                'type' => 'text',
                'desc_tip' => __('Payment title the customer will see during the checkout process.', 'monerov_gateway'),
                'default' => __('MoneroV XMV Payment', 'monerov_gateway')
            ),
            'description' => array(
                'title' => __('Description', 'monerov_gateway'),
                'type' => 'textarea',
                'desc_tip' => __('Payment description the customer will see during the checkout process.', 'monerov_gateway'),
                'default' => __('Pay securely using XMV.', 'monerov_gateway')

            ),
            'use_viewKey' => array(
                'title' => __('Use ViewKey', 'monerov_gateway'),
                'label' => __(' Verify Transaction with ViewKey ', 'monerov_gateway'),
                'type' => 'checkbox',
                'description' => __('Fill in the Address and ViewKey fields to verify transactions with your ViewKey', 'monerov_gateway'),
                'default' => 'no'
            ),
            'monerov_address' => array(
                'title' => __('MoneroV Address', 'monerov_gateway'),
                'label' => __('Useful for people that have not a daemon online'),
                'type' => 'text',
                'desc_tip' => __('MoneroV Wallet Address', 'monerov_gateway')
            ),
            'viewKey' => array(
                'title' => __('Secret ViewKey', 'monerov_gateway'),
                'label' => __('Secret ViewKey'),
                'type' => 'text',
                'desc_tip' => __('Your secret ViewKey', 'monerov_gateway')
            ),
            'use_rpc' => array(
                'title' => __('Use monerov-wallet-rpc', 'monerov_gateway'),
                'label' => __(' Verify transactions with the monerov-wallet-rpc ', 'monerov_gateway'),
                'type' => 'checkbox',
                'description' => __('This must be setup seperately', 'monerov_gateway'),
                'default' => 'no'
            ),
            'daemon_host' => array(
                'title' => __('MoneroV wallet RPC Host/ IP', 'monerov_gateway'),
                'type' => 'text',
                'desc_tip' => __('This is the Daemon Host/IP to authorize the payment with port', 'monero_gateway'),
                'default' => 'localhost',
            ),
            'daemon_port' => array(
                'title' => __('MoneroV wallet RPC port', 'monerov_gateway'),
                'type' => 'text',
                'desc_tip' => __('This is the Daemon Host/IP to authorize the payment with port', 'monerov_gateway'),
                'default' => '18080',
            ),
            'discount' => array(
                'title' => __('% discount for using XMV', 'monerov_gateway'),

                'desc_tip' => __('Provide a discount to your customers for making a private payment with XMV!', 'monerov_gateway'),
                'description' => __('Do you want to spread the word about MoneroV? Offer a small discount! Leave this empty if you do not wish to provide a discount', 'monerov_gateway'),
                'type' => __('number'),
                'default' => '5'

            ),
            'environment' => array(
                'title' => __(' Testnet', 'monerov_gateway'),
                'label' => __(' Check this if you are using testnet ', 'monerov_gateway'),
                'type' => 'checkbox',
                'description' => __('Check this box if you are using testnet', 'monerov_gateway'),
                'default' => 'no'
            ),
            'zero_conf' => array(
                'title' => __(' Accept 0 conf txs', 'monerov_gateway'),
                'label' => __(' Accept 0-confirmation transactions ', 'monerov_gateway'),
                'type' => 'checkbox',
                'description' => __('This is faster but less secure', 'monerov_gateway'),
                'default' => 'no'
            ),
            'onion_service' => array(
                'title' => __(' SSL warnings ', 'monerov_gateway'),
                'label' => __(' Check to Silence SSL warnings', 'monerov_gateway'),
                'type' => 'checkbox',
                'description' => __('Check this box if you are running on an Onion Service (Suppress SSL errors)', 'monerov_gateway'),
                'default' => 'no'
            ),
        );
    }

    public function add_my_currency($currencies)
    {
        $currencies['XMV'] = __('MoneroV', 'woocommerce');
        return $currencies;
    }

    public function add_my_currency_symbol($currency_symbol, $currency)
    {
        switch ($currency) {
            case 'XMV':
                $currency_symbol = 'XMV';
                break;
        }
        return $currency_symbol;
    }

    public function admin_options()
    {
        $this->log->add('MoneroV_gateway', '[SUCCESS] MoneroV Settings OK');
        echo "<h1>MoneroV Payment Gateway</h1>";

        echo "<p>Welcome to MoneroV Extension for WooCommerce. Getting started: Make a connection with daemon <a href='https://reddit.com/u/serhack'>Contact Me</a>";
        echo "<div style='border:1px solid #DDD;padding:5px 10px;font-weight:bold;color:#223079;background-color:#9ddff3;'>";
        
        if(!$this->non_rpc) // only try to get balance data if using wallet-rpc
            $this->getamountinfo();
        
        echo "</div>";
        echo "<table class='form-table'>";
        $this->generate_settings_html();
        echo "</table>";
        echo "<h4>Learn more about using monerov-wallet-rpc <a href=\"https://github.com/monerov-integrations/monerowp/blob/master/README.md\">here</a> </h4>";
    }

    public function getamountinfo()
    {
        $wallet_amount = $this->monerov_daemon->getbalance();
        if (!isset($wallet_amount)) {
            $this->log->add('MoneroV_gateway', '[ERROR] Cannot connect to monerov-wallet-rpc');
            echo "</br>Your balance is: Not Available </br>";
            echo "Unlocked balance: Not Available";
        }
        else
        {
            $real_wallet_amount = $wallet_amount['balance'] / 1000000000000;
            $real_amount_rounded = round($real_wallet_amount, 6);

            $unlocked_wallet_amount = $wallet_amount['unlocked_balance'] / 1000000000000;
            $unlocked_amount_rounded = round($unlocked_wallet_amount, 6);
        
            echo "Your balance is: " . $real_amount_rounded . " XMV </br>";
            echo "Unlocked balance: " . $unlocked_amount_rounded . " XMV </br>";
        }
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->update_status('on-hold', __('Awaiting offline payment', 'monerov_gateway'));
        // Reduce stock levels
        $order->reduce_order_stock();

        // Remove cart
        WC()->cart->empty_cart();

        // Return thank you redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );

    }

    // Submit payment and handle response

    public function validate_fields()
    {
        if ($this->check_monerov() != TRUE) {
            echo "<div class=\"error\"><p>Your MoneroV Address doesn't look valid. Have you checked it?</p></div>";
        }
        if(!$this->check_viewKey())
        {
            echo "<div class=\"error\"><p>Your ViewKey doesn't look valid. Have you checked it?</p></div>";
        }
        if($this->check_checkedBoxes())
        {
            echo "<div class=\"error\"><p>You must choose to either use monerov-wallet-rpc or a ViewKey, not both</p></div>";
        }

    }


    // Validate fields

    public function check_monerov()
    {
        $monerov_address = $this->settings['monerov_address'];
        if (strlen($monerov_address) == 95 && substr($monerov_address, 1)) 
        {
			if($this->cryptonote->verify_checksum($monerov_address))
			{
				return true;
			}
        }
        return false;
    }
    public function check_viewKey()
    {
        if($this->use_viewKey == 'yes')
        {
            if (strlen($this->viewKey) == 64) {
                return true;
            }
            return false;
        }
        return true;
    }
    public function check_checkedBoxes()
    {
        if($this->use_viewKey == 'yes')
        {
            if($this->use_rpc == 'yes')
            {
                return true;
            }
        }
        else
            return false;
    }
    
    public function is_virtual_in_cart($order_id)
    {
        $order = wc_get_order( $order_id );
        $items = $order->get_items();
        $cart_size = count($items);
        $virtual_items = 0;
	
        foreach ( $items as $item ) {
            $product = new WC_Product( $item['product_id'] );
            if ( $product->is_virtual() ) {
                 $virtual_items += 1;
            }
        }
        if($virtual_items == $cart_size)
        {
	    return true;
        }
	else{
            return false;
	}
    }
    
    public function instruction($order_id)
    {
        if($this->non_rpc)
        {
            echo "<noscript><h1>You must enable javascript in order to confirm your order</h1></noscript>";
            $order = wc_get_order($order_id);
            $amount = floatval(preg_replace('#[^\d.]#', '', $order->get_total()));
            $payment_id = $this->set_paymentid_cookie(8);
            $currency = $order->get_currency();
            $amount_xmv2 = $this->changeto($amount, $currency, $payment_id);
            $address = $this->address;
            
            $order->update_meta_data( "Payment ID", $payment_id);
            $order->update_meta_data( "Amount requested (XMV)", $amount_xmv2);
            $order->save();
            
            if (!isset($address)) {
                // If there isn't address (merchant missed that field!), $address will be the MoneroV address for donating :)
                $address = "44AFFq5kSiGBoZ4NMDwYtN18obc8AemS33DBLWs3H7otXft3XjrpDtQGv7SqSsaBYBb98uNbr2VBBEt7f2wfn3RVGQBEP3A";
            }

         
         
            $decoded_address = $this->cryptonote->decode_address($address);
            $pub_spendKey = $decoded_address['spendKey'];
            $pub_viewKey = $decoded_address['viewKey'];
            
            $integrated_addr = $this->cryptonote->integrated_addr_from_keys($pub_spendKey, $pub_viewKey, $payment_id);
            
            $uri = urlencode("monerov:".$address."?tx_amount=".$amount_xmv2."&tx_payment_id=".$payment_id);                
            $this->verify_non_rpc($payment_id, $amount_xmv2, $order_id, $this->zero_confirm);
            if($this->confirmed == false)
            {
               echo "<h4><font color=DC143C> We are waiting for your transaction to be confirmed </font></h4>";
            }
            if($this->confirmed)
            {
                echo "<h4><font color=006400> Your transaction has been successfully confirmed! </font></h4>";
            }
            
            echo "
            <head>
            <!--Import Google Icon Font-->
            <link href='https://fonts.googleapis.com/icon?family=Material+Icons' rel='stylesheet'>
            <link href='https://fonts.googleapis.com/css?family=Montserrat:400,800' rel='stylesheet'>
            <link href='http://cdn.monerointegrations.com/style.css' rel='stylesheet'>
            <!--Let browser know website is optimized for mobile-->
                <meta name='viewport' content='width=device-width, initial-scale=1.0'/>
                </head>
                <body>
                <!-- page container  -->
                <div class='page-container'>
                <!-- MoneroV container payment box -->
                <div class='container-xmr-payment'>
                <!-- header -->
                <div class='header-xmr-payment'>
                <span class='logo-xmr'><img src='http://cdn.monerointegrations.com/logomonero.png' /></span>
                <span class='xmr-payment-text-header'><h2>MONEROV PAYMENT</h2></span>
                </div>
                <!-- end header -->
                <!-- xmr content box -->
                <div class='content-xmr-payment'>
                <div class='xmr-amount-send'>
                <span class='xmr-label'>Send:</span>
                <div class='xmr-amount-box'>".$amount_xmv2."</div>
                </div>
                <div class='xmr-address'>
                <span class='xmr-label'>To this address:</span>
                <div class='xmr-address-box'>".$integrated_addr."</div>
                </div>
                <div class='xmr-qr-code'>
                <span class='xmr-label'>Or scan QR:</span>
                <div class='xmr-qr-code-box'><img src='https://api.qrserver.com/v1/create-qr-code/? size=200x200&data=".$uri."' /></div>
                </div>
                <div class='clear'></div>
                </div>
                <!-- end content box -->
                <!-- footer xmr payment -->
                <div class='footer-xmr-payment'>
                <a href='https://monerov.org' target='_blank'>Help</a> | <a href='https://monerov.org' target='_blank'>About MoneroV</a>
                </div>
                <!-- end footer xmr payment -->
                </div>
                <!-- end MoneroV container payment box -->
                </div>
                <!-- end page container  -->
                </body>
                ";
                
                echo "
                <script type='text/javascript'>setTimeout(function () { location.reload(true); }, $this->reloadTime);</script>";
        }
        else
        {
            $order = wc_get_order($order_id);
            $amount = floatval(preg_replace('#[^\d.]#', '', $order->get_total()));
            $payment_id = $this->set_paymentid_cookie(8);
            $currency = $order->get_currency();
            $amount_xmv2 = $this->changeto($amount, $currency, $payment_id);
            
            $order->update_meta_data( "Payment ID", $payment_id);
            $order->update_meta_data( "Amount requested (XMV)", $amount_xmv2);
            $order->save();

            $uri = urlencode("monerov:".$address."?tx_amount=".$amount_xmv2."&tx_payment_id=".$payment_id);
            $array_integrated_address = $this->monerov_daemon->make_integrated_address($payment_id);
            if (!isset($array_integrated_address)) {
                $this->log->add('MoneroV_Gateway', '[ERROR] Unable get integrated address');
                // Seems that we can't connect with daemon, then set array_integrated_address, little hack
                $array_integrated_address["integrated_address"] = $address;
            }
            $message = $this->verify_payment($payment_id, $amount_xmv2, $order);
            if ($this->confirmed) {
                $color = "006400";
            } else {
                $color = "DC143C";
            }
            echo "<h4><font color=$color>" . $message . "</font></h4>";
        
            echo "
            <head>
            <!--Import Google Icon Font-->
            <link href='https://fonts.googleapis.com/icon?family=Material+Icons' rel='stylesheet'>
            <link href='https://fonts.googleapis.com/css?family=Montserrat:400,800' rel='stylesheet'>
            <link href='http://cdn.monerointegrations.com/style.css' rel='stylesheet'>
            <!--Let browser know website is optimized for mobile-->
                <meta name='viewport' content='width=device-width, initial-scale=1.0'/>
                </head>
                <body>
                <!-- page container  -->
                <div class='page-container'>
                <!-- MoneroV container payment box -->
                <div class='container-xmr-payment'>
                <!-- header -->
                <div class='header-xmr-payment'>
                <span class='logo-xmr'><img src='http://cdn.monerointegrations.com/logomonero.png' /></span>
                <span class='xmr-payment-text-header'><h2>MONEROV PAYMENT</h2></span>
                </div>
                <!-- end header -->
                <!-- xmr content box -->
                <div class='content-xmr-payment'>
                <div class='xmr-amount-send'>
                <span class='xmr-label'>Send:</span>
                <div class='xmr-amount-box'>".$amount_xmv2."</div>
                </div>
                <div class='xmr-address'>
                <span class='xmr-label'>To this address:</span>
                <div class='xmr-address-box'>".$array_integrated_address['integrated_address']."</div>
                </div>
                <div class='xmr-qr-code'>
                <span class='xmr-label'>Or scan QR:</span>
                <div class='xmr-qr-code-box'><img src='https://api.qrserver.com/v1/create-qr-code/? size=200x200&data=".$uri."' /></div>
                </div>
                <div class='clear'></div>
                </div>
                <!-- end content box -->
                <!-- footer xmv payment -->
                <div class='footer-xmr-payment'>
                <a href='https://monerov.org' target='_blank'>Help</a> | <a href='https://monerov.org' target='_blank'>About MoneroV</a>
                </div>
                <!-- end footer xmv payment -->
                </div>
                <!-- end MoneroV container payment box -->
                </div>
                <!-- end page container  -->
                </body>
            ";

            echo "
          <script type='text/javascript'>setTimeout(function () { location.reload(true); }, $this->reloadTime);</script>";
        }
    }

    private function set_paymentid_cookie($size)
    {
        if (!isset($_COOKIE['payment_id'])) {
            $payment_id = bin2hex(openssl_random_pseudo_bytes($size));
            setcookie('payment_id', $payment_id, time() + 2700);
        }
        else{
            $payment_id = $this->sanatize_id($_COOKIE['payment_id']);
        }
        return $payment_id;
    }
	
    public function sanatize_id($payment_id)
    {
        // Limit payment id to alphanumeric characters
        $sanatized_id = preg_replace("/[^a-zA-Z0-9]+/", "", $payment_id);
	return $sanatized_id;
    }

    public function changeto($amount, $currency, $payment_id)
    {
        global $wpdb;
        // This will create a table named whatever the payment id is inside the database "WordPress"
        $create_table = "CREATE TABLE IF NOT EXISTS $payment_id (
									rate INT
									)";
        $wpdb->query($create_table);
        $rows_num = $wpdb->get_results("SELECT count(*) as count FROM $payment_id");
        if ($rows_num[0]->count > 0) // Checks if the row has already been created or not
        {
            $stored_rate = $wpdb->get_results("SELECT rate FROM $payment_id");

            $stored_rate_transformed = $stored_rate[0]->rate / 100; //this will turn the stored rate back into a decimaled number

            if (isset($this->discount)) {
                $sanatized_discount = preg_replace('/[^0-9]/', '', $this->discount);
                $discount_decimal = $sanatized_discount / 100;
                $new_amount = $amount / $stored_rate_transformed;
                $discount = $new_amount * $discount_decimal;
                $final_amount = $new_amount - $discount;
                $rounded_amount = round($final_amount, 12);
            } else {
                $new_amount = $amount / $stored_rate_transformed;
                $rounded_amount = round($new_amount, 12); //the MoneroV wallet can't handle decimals smaller than 0.000000000001
            }
        } else // If the row has not been created then the live exchange rate will be grabbed and stored
        {
            $xmv_live_price = $this->retriveprice($currency);
            $live_for_storing = $xmv_live_price * 100; //This will remove the decimal so that it can easily be stored as an integer

            $wpdb->query("INSERT INTO $payment_id (rate) VALUES ($live_for_storing)");
            if(isset($this->discount))
            {
               $new_amount = $amount / $xmv_live_price;
               $discount = $new_amount * $this->discount / 100;
               $discounted_price = $new_amount - $discount;
               $rounded_amount = round($discounted_price, 12);
            }
            else
            {
               $new_amount = $amount / $xmv_live_price;
               $rounded_amount = round($new_amount, 12);
            }
        }

        return $rounded_amount;
    }


    // Check if we are forcing SSL on checkout pages
    // Custom function not required by the Gateway

    public function retriveprice($currency)
    {
	$api_link = 'https://min-api.cryptocompare.com/data/price?fsym=XMV&tsyms=BTC,USD,EUR,CAD,INR,GBP,COP,SGD' . ',' . $currency . '&extraParams=monerov_woocommerce';
        $xmv_price = file_get_contents($api_link);
        $price = json_decode($xmv_price, TRUE);
        if (!isset($price)) {
            $this->log->add('MoneroV_Gateway', '[ERROR] Unable to get the price of MoneroV');
        }
        switch ($currency) {
            case 'USD':
                return $price['USD'];
            case 'EUR':
                return $price['EUR'];
            case 'CAD':
                return $price['CAD'];
            case 'GBP':
                return $price['GBP'];
            case 'INR':
                return $price['INR'];
            case 'COP':
                return $price['COP'];
            case 'SGD':
                return $price['SGD'];
	    case $currency:
		return $price[$currency];
            case 'XMV':
                $price = '1';
                return $price;
        }
    }
    
    private function on_verified($payment_id, $amount_atomic_units, $order_id)
    {
        $message = "Payment has been received and confirmed. Thanks!";
        $this->log->add('MoneroV_gateway', '[SUCCESS] Payment has been recorded. Congratulations!');
        $this->confirmed = true;
        $order = wc_get_order($order_id);
        
        if($this->is_virtual_in_cart($order_id) == true){
            $order->update_status('completed', __('Payment has been received.', 'monerov_gateway'));
        }
        else{
            $order->update_status('processing', __('Payment has been received.', 'monerov_gateway')); // Show payment id used for order
        }
        global $wpdb;
        $wpdb->query("DROP TABLE $payment_id"); // Drop the table from database after payment has been confirmed as it is no longer needed
                         
        $this->reloadTime = 3000000000000; // Greatly increase the reload time as it is no longer needed
        return $message;
    }
    
    public function verify_payment($payment_id, $amount, $order_id)
    {
        /*
         * function for verifying payments
         * Check if a payment has been made with this payment id then notify the merchant
         */
        $message = "We are waiting for your payment to be confirmed";
        $amount_atomic_units = $amount * 10000000000000;
        $get_payments_method = $this->monerov_daemon->get_payments($payment_id);
        if (isset($get_payments_method["payments"][0]["amount"])) {
            if ($get_payments_method["payments"][0]["amount"] >= $amount_atomic_units)
            {
                $message = $this->on_verified($payment_id, $amount_atomic_units, $order_id);
            }
            if ($get_payments_method["payments"][0]["amount"] < $amount_atomic_units)
            {
                $totalPayed = $get_payments_method["payments"][0]["amount"];
                $outputs_count = count($get_payments_method["payments"]); // number of outputs recieved with this payment id
                $output_counter = 1;

                while($output_counter < $outputs_count)
                {
                         $totalPayed += $get_payments_method["payments"][$output_counter]["amount"];
                         $output_counter++;
                }
                if($totalPayed >= $amount_atomic_units)
                {
                    $message = $this->on_verified($payment_id, $amount_atomic_units, $order_id);
                }
            }
        }
        return $message;
    }
    public function last_block_seen($height) // sometimes 2 blocks are mined within a few seconds of each other. Make sure we don't miss one
    {
        if (!isset($_COOKIE['last_seen_block']))
        {
            setcookie('last_seen_block', $height, time() + 2700);
            return 0;
        }
        else{
            $cookie_block = $_COOKIE['last_seen_block'];
            $difference = $height - $cookie_block;
            setcookie('last_seen_block', $height, time() + 2700);
            return $difference;
        }
    }
    
    public function verify_non_rpc($payment_id, $amount, $order_id, $accept_zero_conf = false)
    {
        $tools = new NodeTools($this->testnet);
            
        $amount_atomic_units = $amount * 10000000000000;
        
        $outputs = $tools->get_outputs($this->address, $this->viewKey, $accept_zero_conf);
        $outs_count = count($outputs);
        
        $i = 0;
        $tx_hash;
        if($outs_count != 0)
        {
            while($i < $outs_count )
            {
                if($outputs[$i]['payment_id'] == $payment_id)
                {
                    if($outputs[$i]['amount'] >= $amount_atomic_units)
                    {
                        $this->on_verified($payment_id, $amount_atomic_units, $order_id);
                        return true;
                    }
                }
                $i++;
            }
        }
        return false;
    }

    public function do_ssl_check()
    {
        if ($this->enabled == "yes" && !$this->get_option('onion_service')) {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }

    public function connect_daemon()
    {
        $host = $this->settings['daemon_host'];
        $port = $this->settings['daemon_port'];
        $monerov_library = new MoneroV($host, $port);
        if ($monerov_library->works() == true) {
            echo "<div class=\"notice notice-success is-dismissible\"><p>Everything works! Congratulations and welcome to MoneroV. <button type=\"button\" class=\"notice-dismiss\">
						<span class=\"screen-reader-text\">Dismiss this notice.</span>
						</button></p></div>";

        } else {
            $this->log->add('MoneroV_gateway', '[ERROR] Plugin cannot reach wallet RPC.');
            echo "<div class=\" notice notice-error\"><p>Error with connection of daemon, see documentation!</p></div>";
        }
    }
}
