<?php

/**
 * Plugin Name: Group Pay
 * Description: A payment gateway for Splittypay Groups Checkout.
 * Version: 1.1.7
 * Author: Splitty Pay
 * Author URI: https://www.splittypay.com/
 * Developer: Federico Gatti
 * Text Domain: woocommerce-gateway-splittypay-checkout
 * Domain Path: /languages
 *
 * WC requires at least: 2.2
 * WC tested up to: 2.3
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + Splitty Pay gateway
 */
function wc_splittypay_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Splittypay_Gateway';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_splittypay_add_to_gateways' );

/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_splittypay_gateway_plugin_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=splittypay_gateway' ) . '">' . __( 'Configure', 'woocommerce' ) . '</a>'
    );
    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_splittypay_gateway_plugin_links' );

/**
 * Offline Payment Gateway
 *
 * Provides an Offline Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Splittypay_Gateway
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      Federico Gatti.
 */
add_action( 'plugins_loaded', 'wc_splittypay_gateway_init', 11 );
function wc_splittypay_gateway_init() {
    class WC_Splittypay_Gateway extends WC_Payment_Gateway {

        private $config = array(
            "baseUrl" => 'https://app.groups.splittypay.com/#',
            "postOrderUrl" => 'https://api-prod.groups.splittypay.com/orders?token=',
            "port"    => '',
        );

        /**
         * Constructor for the gateway.
         */
        public function __construct() {
            // Config
            $this->id                 = 'splittypay_gateway';
            /*$this->icon               = apply_filters('woocommerce_splittypay_icon', WC_HTTPS::force_https_url( WC()->plugin_url() . '-splittypaygrouppayments/assets/images/splittypay.png' ));*/
            $this->has_fields         = false;
            $this->method_title       = __( 'Group Pay', 'woocommerce' );
            $this->method_description = __( 'Imposta l\'API key che ti è stata fornita per attivare i servizi Splittypay! <br>Se non hai una API key, richiedila al seguente indirizzo: <a href="https://merchants.groups.splittypay.com">API key modalità LIVE</a>. <br>Per avere una API key di TEST, invece, utilizza questo indirizzo: <a href="https://merchants.dev.groups.splittypay.com">API key modalità TEST</a>.', 'woocommerce' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title              = $this->get_option( 'title' );
            $this->description        = $this->get_option( 'description' );
            $this->instructions       = $this->get_option( 'instructions', $this->description );
            $this->testmode           = 'yes' === $this->get_option( 'testmode', 'no' );
            $this->config['api_key']  = $this->get_option( 'api_key' );
            $this->config['api_key_test']  = $this->get_option( 'api_key_test' );


            if ( $this->testmode ) {
                $this->config['baseUrl'] = 'https://sandbox.dev.groups.splittypay.com/#';
                $this->config['postOrderUrl'] = 'https://api.dev.groups.splittypay.com/orders?token=';
                $this->config['port'] = '';
                $this->description .= ' ' . sprintf( __( 'SANDBOX ENABLED. You can use sandbox testing accounts only.', 'woocommerce'), 'https://sandbox.splittypay.it/' );
                $this->description  = trim( $this->description );
            }

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            add_action( 'woocommerce_api_wc_splittypay_gateway', array($this, 'handle_callback') ); 
        }


        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'woocommerce' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Abilita il gateway di pagamento Splittypay', 'woocommerce' ),
                    'default' => 'yes'
                ),

                'title' => array(
                    'title'       => __( 'Title', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'woocommerce' ),
                    'default'     => __( 'Paga in gruppo con Splittypay', 'woocommerce' ),
                    'desc_tip'    => true,
                ),

                'description' => array(
                    'title'       => __( 'Description', 'woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
                    'default'     => __( 'Il metodo di pagamento ideale per i regali e i pagamenti di gruppo. Inserisci più carte e/o invita i tuoi amici al pagamento tramite email', 'woocommerce' ),
                    'desc_tip'    => true,
                ),

                'testmode'    => array(
                    'title'       => __( 'SplittyPay - Groups sandbox', 'woocommerce' ),
                    'type'        => 'checkbox',
                    'label'       => __( 'Abilita la sandbox di SplittyPay e ricevi pagamenti solo in modalità TEST', 'woocommerce' ),
                    'default'     => 'no',
                    'description' => sprintf( __( 'La sandbox è un ambiente di TEST di Group Pay', 'woocommerce' ), 'https://sandbox.splittypay.it/'),
                ),

                'api_key' => array(
                    'title'       => __( 'API key LIVE', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Inserisci l\'API key per l\'ambiente LIVE.', 'woocommerce'),
                    'default'     => '',
                    'desc_tip'    => true,
                    'placeholder' => __( 'Required', 'woocommerce' ),
                ),

                'api_key_test' => array(
                    'title'       => __( 'API key TEST', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Inserisci l\'API key per l\'ambiente TEST.', 'woocommerce'),
                    'default'     => '',
                    'desc_tip'    => true,
                    'placeholder' => __( 'Required', 'woocommerce' ),
                )
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            if ( $this->instructions ) {
                echo wpautop( wptexturize( $this->instructions ) );
            }
        }


        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }


        /**
         * Callback action
         */
        public function handle_callback() {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $json = file_get_contents('php://input');
                $data = json_decode($json);

                $order = wc_get_order( $data->content->order_id );
                if ($data->content->order_id != null && $data->content->status != null
                    && ($data->content->status == 'COMPLETED' || $data->content->status == 'PRE_AUTHORIZED')){
                    $this->payment_complete( $order, '', __( 'IPN payment completed', 'woocommerce' ));
                    $redirect = $this->get_return_url( $order );
                    header('HTTP/1.1 200 OK');
                    echo wp_send_json(array('returnUrl'=> $redirect), 200);
                } else {
                    wp_die( 'Splitty Pay Request Failure', 'Splitty Pay IPN', array( 'response' => 500 ) );
                }   
            }
        }

        protected function payment_complete( $order, $txn_id = '', $note = '' ) {
            $order->add_order_note( $note );
            $order->payment_complete();
            $order->update_status('processing', __('Processing order', 'woocommerce'));
            WC()->cart->empty_cart();
        }

        protected function postOrder($postUrl, $params){
            $args = array(
                'body'        => $params,
                'timeout'     => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    "Cache-Control: no-cache",
                    "Content-Type: application/json"
                ),
                'cookies'     => array(),
            );
            $response = wp_remote_post( $postUrl, $args );
            $http_code = wp_remote_retrieve_response_code( $response );
            switch ($http_code){
                case 200:
                    return $response;
                default:
                    return null;
            }
        }

        public function process_payment( $order_id ) {
            $wc_order = wc_get_order( $order_id );
            $order_data = array(
                'content' => array (
                    'order_id'  => $wc_order->get_id(),
                    'order_ref' => WC()->api_request_url( 'wc_splittypay_gateway' ),
                    'amount'    => $wc_order->get_total( 'view' )*100,
                    'status'    => 'PENDING'
                )
            );
            $order = json_encode($order_data);
            
            if ($this->testmode != null && $this->testmode == true) {
                $request_token = $this->config['api_key_test'];
            } else {
                $request_token = $this->config['api_key'];
            }
            $response = $this->postOrder($this->config['postOrderUrl'] . $request_token, $order);
            if ($response != null) {
                $redirectUrl = $this->config['baseUrl'] . "/owner/" . $request_token . "/" . json_decode($response['body'])->key->uri;
            } else {
                $redirectUrl = $this->config['baseUrl'] . "404";
            }

            return array(
                'result'   => 'success',
                'redirect' => $redirectUrl,
            );
        }

        protected function console_log($output, $with_script_tags = true) {
            $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
            if ($with_script_tags) {
                $js_code = '<script>' . $js_code . '</script>';
            }
            echo $js_code;
        }
    }
}
