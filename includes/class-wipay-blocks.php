<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Wipay_Blocks extends AbstractPaymentMethodType {
    protected $name = 'wipay_by_hexakode';
    protected $gateway;

    public function initialize() {
        
        $gateways = WC()->payment_gateways()->payment_gateways();
        $this->gateway = $gateways['wipay_by_hexakode'] ?? null;

     
    }

    public function is_active() {
        $is = $this->gateway && $this->gateway->is_available();
   
    return $is;
    }

    public function get_payment_method_script_handles() {
       
        wp_enqueue_script(
            'wc-wipay-blocks-integration',
            plugins_url('block/wipay-block.js', __DIR__),
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n'],
            null,
            true
        );

        $settings = get_option('woocommerce_wipay_by_hexakode_settings', []);
        wp_add_inline_script(
            'wc-wipay-blocks-integration',
            'window.wc = window.wc || {}; window.wc.wcSettings = window.wc.wcSettings || {}; window.wc.wcSettings["wipay_by_hexakode_data"] = ' . wp_json_encode([
                'title' => $settings['title'] ?? 'WiPay Payment',
                'description' => $settings['description'] ?? 'Pay securely using WiPay.',
                'ariaLabel' => $settings['title'] ?? 'WiPay Payment',
            ]) . ';',
            'before'
        );
        return ['wc-wipay-blocks-integration'];
    }


    public function get_payment_method_data() {


    $settings = get_option('woocommerce_wipay_by_hexakode_settings', []);
    return [
        'title' => $settings['title'] ?? 'WiPay Payment',
        'description' => $settings['description'] ?? '',
        'ariaLabel' => $settings['title'] ?? 'WiPay Payment',
        'supports' => ['products'],
    ];
}


public function enqueue_payment_method_script() {
    wp_enqueue_script(
        'wc-wipay-blocks-integration',
        plugins_url('block/wipay-block.js', __DIR__),
        ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n'],
        null,
        true
    );
}
}