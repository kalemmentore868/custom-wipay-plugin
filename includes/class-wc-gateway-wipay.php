<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Wipay extends WC_Payment_Gateway {
    public $id;
    public $method_title;
    public $method_description;
    public $has_fields;
    public $supports;
    public $title;
    public $description;
    public $enabled;
    public $sandbox_mode;
    public $account_number;
    public $api_key;
    public $sandbox_account;
    public $sandbox_api_key;
    public $fee_structure;
    

    public function __construct() {
        $this->id = 'wipay_by_hexakode';
        $this->method_title = __( 'WiPay by Hexakode', 'wipay-pay-woo' );
        $this->method_description = __( 'Accept credit/debit card payments via WiPay.', 'wipay-pay-woo' );
        $this->has_fields = false;
        $this->supports = [ 'products' ];
        //$this->icon = plugin_dir_url(__FILE__) . '../assets/wipay-icon.png';

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
        $this->enabled            = $this->get_option( 'enabled' );
        $this->sandbox_mode       = $this->get_option( 'sandbox_mode' ) === 'yes';
        $this->account_number     = $this->get_option( 'account_number' );
        $this->api_key            = $this->get_option( 'api_key' );
        $this->sandbox_account    = $this->get_option( 'sandbox_account' );
        $this->sandbox_api_key    = $this->get_option( 'sandbox_api_key' );
        $this->fee_structure      = $this->get_option( 'fee_structure', 'merchant_absorb' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
        add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'handle_wipay_redirect' ], 10, 1 );

    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Enable Plugin', 'wipay-pay-woo' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable WiPay payment method', 'wipay-pay-woo' ),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __( 'Title', 'wipay-pay-woo' ),
                'type'        => 'text',
                'description' => __( 'Displayed title on the checkout page.', 'wipay-pay-woo' ),
                'default'     => 'WiPay Payment',
            ],
            'description' => [
                'title'       => __( 'Description', 'wipay-pay-woo' ),
                'type'        => 'textarea',
                'description' => __( 'Displayed description on the checkout page.', 'wipay-pay-woo' ),
                'default'     => 'Pay securely using your credit or debit card via WiPay.',
            ],
            'sandbox_mode' => [
                'title'       => __( 'Sandbox Mode', 'wipay-pay-woo' ),
                'type'        => 'checkbox',
                'label'       => __( 'Use sandbox/test environment', 'wipay-pay-woo' ),
                'default'     => 'yes',
            ],
            'fee_structure' => [
    'title'       => __( 'Fee Structure', 'wipay-pay-woo' ),
    'type'        => 'select',
    'description' => __( 'Choose who will absorb the WiPay transaction fee.', 'wipay-pay-woo' ),
    'default'     => 'merchant_absorb',
    'options'     => [
        'customer_pay'    => __( 'Customer Pays Fee', 'wipay-pay-woo' ),
        'merchant_absorb' => __( 'Merchant Absorbs Fee', 'wipay-pay-woo' ),
        'split'           => __( 'Split Fee', 'wipay-pay-woo' ),
    ],
],
            'account_number' => [
                'title'       => __( 'WiPay Account Number (Live)', 'wipay-pay-woo' ),
                'type'        => 'text',
                'default'     => '',
            ],
            'api_key' => [
                'title'       => __( 'WiPay API Key (Live)', 'wipay-pay-woo' ),
                'type'        => 'text',
                'default'     => '',
            ],
            'sandbox_account' => [
                'title'       => __( 'WiPay Account Number (Sandbox)', 'wipay-pay-woo' ),
                'type'        => 'text',
                'default'     => '',
            ],
            'sandbox_api_key' => [
                'title'       => __( 'WiPay API Key (Sandbox)', 'wipay-pay-woo' ),
                'type'        => 'text',
                'default'     => '',
            ]
        ];
    }

    public function is_available() {
        return 'yes' === $this->enabled;
    }

    private function is_accessing_settings() {
		if ( is_admin() ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['section'] ) || 'hexakode' !== $_REQUEST['section'] ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return true;
		}

		return false;
	}

    private function load_shipping_method_options() {
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if ( ! $this->is_accessing_settings() ) {
			return array();
		}

		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		$options = array();
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = array();

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'hexa-payments-woo' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'hexa-payments-woo' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'hexa-payments-woo' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'hexa-payments-woo' ), $option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}

    private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

		$canonical_rate_ids = array();

		foreach ( $order_shipping_items as $order_shipping_item ) {
			$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
		}

		return $canonical_rate_ids;
	}

    private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

		$shipping_packages  = WC()->shipping()->get_packages();
		$canonical_rate_ids = array();

		if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
			foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
				if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
					$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
					$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
				}
			}
		}

		return $canonical_rate_ids;
	}

    private function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
	}

  public function process_payment( $order_id ) {
    $order = wc_get_order( $order_id );

    $redirect_url = $this->create_wipay_payment_redirect_url( $order );

    if ( $redirect_url ) {
        return [
            'result' => 'success',
            'redirect' => $redirect_url,
            'wipay_url' => $redirect_url, // optional, in case you want to debug
        ];
    } else {
        wc_add_notice( __( 'WiPay payment failed. Please try again.', 'wipay-pay-woo' ), 'error' );
        return [
            'result' => 'failure',
        ];
    }
}



private function create_wipay_payment_redirect_url( $order ) {
    $is_sandbox = $this->sandbox_mode;
    $api_key    = $is_sandbox ? $this->sandbox_api_key : $this->api_key;
    $account    = $is_sandbox ? $this->sandbox_account : $this->account_number;

    $order_id       = $order->get_id();
    $total          = number_format( $order->get_total(), 2, '.', '' );
    $currency       = $order->get_currency();
    $customer_email = $order->get_billing_email();
    $customer_name  = $order->get_formatted_billing_full_name();

    $response_url = add_query_arg(
        [
            'payment_status' => 'wipay_callback',
            'order_id'       => $order_id,
        ],
        $this->get_return_url( $order )
    );

    $payload = [
        'account_number' => $account,
        'environment'    => $is_sandbox ? 'sandbox' : 'live',
        'response_url'   => $response_url,
        'fee_structure'  => $this->fee_structure ?? 'merchant_absorb',
        'method'         => 'credit_card',
        'order_id'       => $order_id,
        'currency'       => 'TTD',
        'country_code'  =>  'TT',
        'origin'         => 'WooCommerce-PHP',
        'total'          => $total,
        'currency'       => $currency,
    ];

    $response = wp_remote_post( 'https://tt.wipayfinancial.com/plugins/payments/request', [
        'method'    => 'POST',
        'timeout'   => 30,
        'headers'   => [
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => http_build_query( $payload ),
    ]);

    error_log('Response from WiPay: ' . print_r($response, true));

    if ( is_wp_error( $response ) ) {
        error_log( '[WiPay] Error: ' . $response->get_error_message() );
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( isset( $data['url'] ) && filter_var( $data['url'], FILTER_VALIDATE_URL ) ) {
        return $data['url'];
    }

    error_log( '[WiPay] Invalid response: ' . $body );
    return false;
}



    public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}

		wc_print_notices();
	}

	/**
	 * Change payment complete order status to completed for hexakode orders.
	 *
	 * @since  3.1.0
	 * @param  string         $status Current order status.
	 * @param  int            $order_id Order ID.
	 * @param  WC_Order|false $order Order object.
	 * @return string
	 */
	public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
		// if ( $order && 'Hexakode Payments' === $order->get_payment_method() ) {
		// 	$status = 'completed';
		// }
		return $status;
	}
public function handle_wipay_redirect( $order_id ) {
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        echo '<p>' . esc_html__( 'Order not found.', 'wipay-pay-woo' ) . '</p>';
        return;
    }

    $status = sanitize_text_field( $_GET['status'] ?? '' );
    $transaction_id = sanitize_text_field( $_GET['transaction_id'] ?? '' );

    if ( $transaction_id ) {
        $order->update_meta_data( '_wipay_transaction_id', $transaction_id );
    }

    if ( $status === 'success' ) {
        if ( $order->get_status() !== 'completed' ) {
            $order->payment_complete( $transaction_id );
            $order->update_status( 'completed', __( 'Payment completed via WiPay.', 'wipay-pay-woo' ) );
            $order->add_order_note( __( 'Payment completed via WiPay. Transaction ID: ', 'wipay-pay-woo' ) . $transaction_id );
            WC()->cart->empty_cart();
        }
        echo '<p>' . esc_html__( 'Payment successful. Your order is now complete.', 'wipay-pay-woo' ) . '</p>';
    } elseif ( $status === 'failure' || $status === 'failed' ) {
        if ( $order->get_status() !== 'failed' ) {
            $order->update_status( 'failed', __( 'Payment failed via WiPay.', 'wipay-pay-woo' ) );
            $order->add_order_note( __( 'Payment failed via WiPay. Transaction ID: ', 'wipay-pay-woo' ) . $transaction_id );
        }
        echo '<p>' . esc_html__( 'Payment failed. Please try again or contact support.', 'wipay-pay-woo' ) . '</p>';
    }

    $order->save();
}



	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin  Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}
}