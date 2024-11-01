<?php
/**
 * WC BitPay Gateway Class.
 *
 * Built the BitPay method.
 */
class WC_BitPay_Gateway extends WC_Payment_Gateway {

    /**
     * Gateway's Constructor.
     *
     * @return void
     */
    public function __construct() {
        global $woocommerce;

        $this->id                  = 'bitpay';
        $this->icon                = apply_filters( 'woocommerce_bitpay_icon', plugins_url( 'images/bitcoin.png', __FILE__ ) );
        $this->has_fields          = false;
        $this->method_title        = __( 'BitPay', 'wcbitpay' );

        // API URLs.
        $this->invoice_url         = 'https://bitpay.com/api/invoice';

        // Supported Currencies.
        $this->supported_currencies = apply_filters( 'woocommerce_bitpay_supported_currencies', array(
            'USD', 'EUR', 'GBP', 'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY',
            'CZK', 'DKK', 'HKD', 'HRK', 'HUF', 'IDR', 'ILS', 'INR', 'JPY',
            'KRW', 'LTL', 'LVL', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN',
            'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'ZAR', 'BTC'
        ) );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user setting variables.
        $this->title              = $this->settings['title'];
        $this->description        = $this->settings['description'];
        $this->api_key            = $this->settings['api_key'];
        $this->notification_email = $this->settings['notification_email'];
        $this->invoice_prefix     = ! empty( $this->settings['invoice_prefix'] ) ? $this->settings['invoice_prefix'] : 'WC-';
        $this->debug              = $this->settings['debug'];

        // Actions.
        add_action( 'woocommerce_api_wc_bitpay_gateway', array( &$this, 'check_ipn_response' ) );
        add_action( 'valid_bitpay_ipn_request', array( &$this, 'successful_request' ) );
        add_action( 'woocommerce_receipt_bitpay', array( &$this, 'receipt_page' ) );
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) )
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
        else
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

        // Valid for use.
        $this->enabled = ( 'yes' == $this->settings['enabled'] ) && ! empty( $this->api_key ) && $this->is_valid_for_use();

        // Checking if api_key is not empty.
        if ( empty( $this->api_key ) )
            add_action( 'admin_notices', array( &$this, 'api_key_missing_message' ) );

        // Active logs.
        if ( 'yes' == $this->debug )
            $this->log = $woocommerce->logger();
    }

    /**
     * Checking if this gateway is enabled and available in the user's currency.
     *
     * @return bool
     */
    public function is_valid_for_use() {

        if ( ! in_array( get_woocommerce_currency(), $this->supported_currencies ) )
            return false;

        return true;
    }

    /**
     * Admin Panel Options
     *
     * @return Admin option form.
     */
    public function admin_options() {
        echo '<h3>' . __( 'BitPay standard', 'wcbitpay' ) . '</h3>';
        echo '<p>' . __( 'BitPay standard displays a BitPay button with payment information in Bitcoin.', 'wcbitpay' ) . '</p>';

        // Checks if is valid for use.
        if ( ! $this->is_valid_for_use() ) {
            echo '<div class="inline error"><p><strong>' . __( 'BitPay Disabled', 'wcbitpay' ) . '</strong>: ' . sprintf( __( 'Works only with the following currencies: %s.', 'wcbitpay' ), '<code>' . implode( ', ', $this->supported_currencies ) . '</code>' ) . '</p></div>';
        } else {
            // Generate the HTML For the settings form.
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }
    }

    /**
     * Start Gateway Settings Form Fields.
     *
     * @return array Form fields.
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'wcbitpay' ),
                'type' => 'checkbox',
                'label' => __( 'Enable BitPay standard', 'wcbitpay' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __( 'Title', 'wcbitpay' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'wcbitpay' ),
                'desc_tip' => true,
                'default' => __( 'BitPay', 'wcbitpay' )
            ),
            'description' => array(
                'title' => __( 'Description', 'wcbitpay' ),
                'type' => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'wcbitpay' ),
                'default' => __( 'Pay with Bitcoin', 'wcbitpay' )
            ),
            'api_key' => array(
                'title' => __( 'API Key ID', 'wcbitpay' ),
                'type' => 'text',
                'description' => __( 'Please enter your BitPay API Key ID.', 'wcbitpay' ) . ' ' . sprintf( __( 'You can to get this information in: %sBitPay Account%s.', 'wcbitpay' ), '<a href="https://bitpay.com/api-keys" target="_blank">', '</a>' ),
                'default' => ''
            ),
            'transaction_speed' => array(
                'title' => __( 'Transaction Speed', 'wcbitpay' ),
                'type' => 'select',
                'description' => __( 'Choose a transaction speed:<br /><strong>High</strong>: An invoice is considered to be "confirmed" immediately upon receipt of payment.<br /><strong>Medium</strong>: An invoice is considered to be "confirmed" after 1 block confirmation (~10 minutes).<br /><strong>Low</strong>: An invoice is considered to be "confirmed" after 6 block confirmations (~1 hour).', 'wcbitpay' ),
                'desc_tip' => true,
                'default' => 'high',
                'options' => array(
                    'high' => __( 'High', 'wcbitpay' ),
                    'medium' => __( 'Medium', 'wcbitpay' ),
                    'low' => __( 'Low', 'wcbitpay' ),
                )
            ),
            'notification_email' => array(
                'title' => __( 'Notification email', 'wcbitpay' ),
                'type' => 'text',
                'description' => __( 'BitPay will send an email to this email address when the invoice status changes.', 'wcbitpay' ),
                'desc_tip' => true,
                'default' => ''
            ),
            'full_notifications' => array(
                'title' => __( 'Full Notifications', 'wcbitpay' ),
                'label' => __( 'Enable Full Notifications', 'wcbitpay' ),
                'type' => 'checkbox',
                'description' => __( '<strong>Enabled</strong>: Notifications will be sent on every status change.<br /><strong>Disabled</strong>: Notifications are only sent when an invoice is confirmed (according the Transaction Speed setting).', 'wcbitpay'),
                'desc_tip' => true,
                'default' => 'no'
            ),
            'invoice_prefix' => array(
                'title' => __( 'Invoice Prefix', 'wcbitpay' ),
                'type' => 'text',
                'description' => __( 'Please enter a prefix for your invoice numbers. If you use your BitPay account for multiple stores ensure this prefix is unqiue as BitPay will not allow orders with the same invoice number.', 'wcbitpay' ),
                'desc_tip' => true,
                'default' => 'WC-'
            ),
            'testing' => array(
                'title' => __( 'Gateway Testing', 'wcbitpay' ),
                'type' => 'title',
                'description' => ''
            ),
            'debug' => array(
                'title' => __( 'Debug Log', 'wcbitpay' ),
                'type' => 'checkbox',
                'label' => __( 'Enable logging', 'wcbitpay' ),
                'default' => 'no',
                'description' => sprintf( __( 'Log BitPay events, such as API requests, inside %s', 'wcbitpay' ), '<code>woocommerce/logs/bitpay-' . sanitize_file_name( wp_hash( 'bitpay' ) ) . '.txt</code>' )
            )
        );
    }

    /**
     * Generate the args to payment.
     *
     * @param  object $order Order data.
     *
     * @return array         Payment arguments.
     */
    public function get_payment_args( $order ) {

        $invoice = $this->invoice_prefix . $order->id;
        $transaction_speed = ( isset( $this->settings['transaction_speed'] ) && ! empty( $this->settings['transaction_speed'] ) ) ? $this->settings['transaction_speed'] : 'high';;
        $full_notifications = ( isset( $this->settings['full_notifications'] ) && 'yes' == $this->settings['full_notifications'] ) ? 'true' : 'false';

        $args = array(
            'price'             => (float) $order->order_total,
            'currency'          => get_woocommerce_currency(),
            'posData'           => '{"posData": "' . $invoice . '", "hash": "' . crypt( $invoice, $this->api_key ) . '"}',
            'orderID'           => $order->id,
            'redirectURL'       => esc_url( $this->get_return_url( $order ) ),
            'transactionSpeed'  => $transaction_speed,
            'fullNotifications' => $full_notifications
        );

        $buyer_args = array(
            'buyerName'         => $order->billing_first_name . ' ' . $order->billing_last_name,
            'buyerAddress1'     => $order->billing_address_1,
            'buyerAddress2'     => $order->billing_address_2,
            'buyerCity'         => $order->billing_city,
            'buyerState'        => $order->billing_state,
            'buyerZip'          => $order->billing_postcode,
            'buyerCountry'      => $order->billing_country,
            'buyerEmail'        => $order->billing_email,
            'buyerPhone'        => $order->billing_phone
        );

        // Fix max lenght.
        foreach ( $buyer_args as $key => $value )
            $args[ $key ] = substr( $value, 0, 100 );

        if ( is_ssl() )
            $args['notificationURL'] = str_replace( 'http:', 'https:', add_query_arg( 'wc-api', 'WC_BitPay_Gateway', home_url( '/' ) ) );

        if ( ! empty( $this->notification_email ) )
            $args['notificationEmail'] = $this->notification_email;

        $args = apply_filters( 'woocommerce_bitpay_args', $args );

        return $args;
    }

    /**
     * Generate the payment HTML.
     *
     * @param int     $order_id Order ID.
     *
     * @return string           Payment HTML.
     */
    public function generate_payment_html( $order_id ) {

        $order = new WC_Order( $order_id );

        $args = $this->get_payment_args( $order );

        if ( 'yes' == $this->debug )
            $this->log->add( 'bitpay', 'Payment arguments for order ' . $order->get_order_number() . ': ' . print_r( $args, true ) );

        $details = $this->create_invoice( $args );

        if ( $details ) {

            // Displays BitPay iframe.
            $html = '<iframe src="' . esc_url( $details->url ) . '&view=iframe" style="display: block; border: none; margin: 0 auto 25px; width: 500px;"></iframe>';

            $html .= '<a id="submit-payment" href="' . $args['redirectURL'] . '" class="button alt">' . __( 'Payment done, close the order', 'wcbitpay' ) . '</a> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'wcbitpay' ) . '</a>';

            if ( 'yes' == $this->debug )
                $this->log->add( 'bitpay', 'Payment link generated with success from BitPay' );

            // Register order details.
            update_post_meta( $order->id, 'BitPay ID', esc_attr( $details->id ) );
            update_post_meta( $order->id, 'BTC Price', esc_attr( $details->btcPrice ) );

            return $html;

        } else {
            if ( 'yes' == $this->debug )
                $this->log->add( 'bitpay', 'Set details error.' );

            return $this->btc_order_error( $order );
        }
    }

    /**
     * Order error button.
     *
     * @param  object $order Order data.
     *
     * @return string        Error message and cancel button.
     */
    protected function btc_order_error( $order ) {

        // Display message if there is problem.
        $html = '<p>' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wcbitpay' ) . '</p>';

        $html .= '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Click to try again', 'wcbitpay' ) . '</a>';

        return $html;
    }

    /**
     * Process the payment and return the result.
     *
     * @param int    $order_id Order ID.
     *
     * @return array           Redirect.
     */
    public function process_payment( $order_id ) {
        $order = new WC_Order( $order_id );

        if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url( true )
            );
        } else {
            return array(
                'result'    => 'success',
                'redirect'  => add_query_arg( 'order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink( woocommerce_get_page_id( 'pay' ) ) ) )
            );
        }
    }

    /**
     * Output for the order received page.
     *
     * @return void
     */
    public function receipt_page( $order ) {
        echo $this->generate_payment_html( $order );
    }

    /**
     * Create order invoice.
     *
     * @param  array $args Order argumments.
     *
     * @return mixed       Object with order details or false.
     */
    public function create_invoice( $args ) {

        // Built wp_remote_post params.
        $params = array(
            'body'       => json_encode( $args ),
            'method'     => 'POST',
            'sslverify'  => false,
            'timeout'    => 30,
            'headers'    => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $this->api_key )
            )
        );

        $response = wp_remote_post( $this->invoice_url, $params );

        // Log response.
        if ( 'yes' == $this->debug && isset( $response['body'] ) )
            $this->log->add( 'bitpay', 'BitPay Server Response:' . print_r( json_decode( $response['body'] ), true ) );

        // Check to see if the request was valid.
        if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] )
            return json_decode( $response['body'] );

        return false;
    }

    /**
     * Check IPN validity.
     *
     * @param  array $post $_POST data.
     *
     * @return mixed       Array with post data or false.
     */
    public function valid_ipn_request( $post ) {

        if ( ! $post ) {
            if ( 'yes' == $this->debug )
                $this->log->add( 'bitpay', 'Authentication Failed - No Post Data' );

            return false;
        }

        $json = json_decode( $post, true );

        if ( is_string( $json ) || ! array_key_exists( 'posData', $json ) ) {
            if ( 'yes' == $this->debug )
                $this->log->add( 'bitpay', 'Authentication Failed - Bad Data:' . print_r( $post, true ) );

            return false;
        }

        $data = json_decode( $json['posData'], true );

        if ( $data['hash'] != crypt( $data['posData'], $this->api_key ) ) {
            if ( 'yes' == $this->debug )
                $this->log->add( 'bitpay', 'Authentication Failed - Bad Hash!' );

            return false;
        }

        if ( 'yes' == $this->debug )
            $this->log->add( 'bitpay', 'Received valid posData from BitPay' );

        $json['posData'] = $data['posData'];

        return $json;
    }

    /**
     * Valid API Response.
     *
     * @return void
     */
    public function check_ipn_response() {
        @ob_clean();

        $posted = $this->valid_ipn_request( $_POST );

        if ( is_ssl() && is_array( $posted ) ) {
            header( 'HTTP/1.0 200 OK' );
            do_action( 'valid_bitpay_ipn_request', $posted );
        } else {
            wp_die( __( 'BitPay Request Failure', 'wcbitpay' ) );
        }
    }

    /**
     * Successful Payment!
     *
     * @param array $posted IPN data.
     *
     * @return void
     */
    public function successful_request( $posted ) {

        if ( ! empty( $posted['posData'] ) ) {
            $order_key = $posted['posData'];
            $order_id = (int) str_replace( $this->invoice_prefix, '', $order_key );

            $order = new WC_Order( $order_id );

            // Checks whether the invoice number matches the order.
            // If true processes the payment.
            if ( $order->id === $order_id ) {

                if ( 'yes' == $this->debug )
                    $this->log->add( 'bitpay', 'Payment status from order ' . $order->get_order_number() . ': ' . $posted['status'] );

                switch ( $posted['status'] ) {
                    case 'confirmed':
                    case 'complete':
                        if ( in_array( $order->status, array( 'on-hold', 'pending', 'failed' ) ) ) {
                            $order->add_order_note( __( 'BitPay: Payment confirmed.', 'wcbitpay' ) );

                            // Changing the order for processing and reduces the stock.
                            $order->payment_complete();
                        }

                        break;
                    case 'expired':
                        $order->update_status( 'cancelled', __( 'BitPay: Payment expired.', 'wcbitpay' ) );

                        break;
                    case 'invalid':
                        $order->update_status( 'cancelled', __( 'BitPay: Payment canceled.', 'wcbitpay' ) );

                        break;

                    default:
                        // No action xD.
                        break;
                }
            }
        }
    }

    /**
     * Adds error message when not configured the api_key.
     *
     * @return string Error Mensage.
     */
    public function api_key_missing_message() {
        echo '<div class="error"><p><strong>' . __( 'BitPay Disabled', 'wcbitpay' ) . '</strong>: ' . sprintf( __( 'You should inform your API Key ID of BitPay. %s', 'wcbitpay' ), '<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_BitPay_Gateway' ) . '">' . __( 'Click here to configure!', 'wcbitpay' ) . '</a>' ) . '</p></div>';
    }
}
