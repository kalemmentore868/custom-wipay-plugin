<?php
/**
 * Plugin Name: Wipay by Hexakode Agency
 * Plugin URI: https://hexakodeagency.com
 * Author Name: Kalem Mentore
 * Author URI: https://hexakodeagency.com
 * Description: This plugin allows for credit/debit card payments.
 * Version: 0.1.0
 * License: 0.1.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wipay-pay-woo
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}




if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action('before_woocommerce_init', function() {
    if ( class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil') ) {
        // Enable block checkout + HPOS compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

add_action('plugins_loaded', function() {
    if (class_exists('WC_Payment_Gateway')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-wipay.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-wipay-blocks.php';
    }
}, 11);

add_filter('woocommerce_payment_gateways', function($gateways) {
   
    $gateways[] = 'WC_Gateway_Wipay';
    return $gateways;
});


add_action('woocommerce_blocks_loaded', function () {
    
    if (! class_exists('WC_Wipay_Blocks')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-wipay-blocks.php';
       
    }

    add_action('woocommerce_blocks_payment_method_type_registration', function ($registry) {
        if (
            class_exists('\\Automattic\\WooCommerce\\Blocks\\Payments\\PaymentMethodRegistry') &&
            class_exists('\\Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType')
        ) {
            
            $registry->register(new WC_Wipay_Blocks());
        }
    });
});

add_filter('woocommerce_virtual_and_downloadable_product_payment_gateways', function($gateways) {
    $gateways[] = 'wipay_by_hexakode';
    return $gateways;
});

add_filter('woocommerce_available_payment_gateways', function($gateways) {
    if (isset($gateways['wipay_by_hexakode'])) {
        $gateways['wipay_by_hexakode']->supports[] = 'virtual';
    }
    return $gateways;
});

add_action('woocommerce_admin_order_data_after_order_details', function($order) {
    $txn_id = $order->get_meta('_wipay_transaction_id');
    if ($txn_id) {
        echo '<p><strong>WiPay Transaction ID:</strong> ' . esc_html($txn_id) . '</p>';
    }
});




