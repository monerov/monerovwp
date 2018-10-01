<?php
/*
Plugin Name: MoneroV - WooCommerce Gateway
Plugin URI: https://monerointegrations.com
Description: Extends WooCommerce by Adding the MoneroV Gateway
Version: 2.0
Author: SerHack
Author URI: https://monerointegrations.com
*/

// This code isn't for Dark Net Markets, please report them to Authority!
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action('plugins_loaded', 'monerov_init', 0);
function monerov_init()
{
    /* If the class doesn't exist (== WooCommerce isn't installed), return NULL */
    if (!class_exists('WC_Payment_Gateway')) return;


    /* If we made it this far, then include our Gateway Class */
    include_once('include/monerov_payments.php');
    require_once('library.php');

    // Lets add it too WooCommerce
    add_filter('woocommerce_payment_gateways', 'monerov_gateway');
    function monerov_gateway($methods)
    {
        $methods[] = 'MoneroV_Gateway';
        return $methods;
    }
}

/*
 * Add custom link
 * The url will be http://yourworpress/wp-admin/admin.php?=wc-settings&tab=checkout
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'monerov_payment');
function monerov_payment($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'monerov_payment') . '</a>',
    );

    return array_merge($plugin_links, $links);
}

add_action('admin_menu', 'monerov_create_menu');
function monero_create_menu()
{
    add_menu_page(
        __('MoneroV', 'textdomain'),
        'MoneroV',
        'manage_options',
        'admin.php?page=wc-settings&tab=checkout&section=monerov_gateway',
        '',
        plugins_url('monerov/assets/monerov_icon.png'),
        56 // Position on menu, woocommerce has 55.5, products has 55.6

    );
}


