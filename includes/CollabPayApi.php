<?php

class CollabPayApi
{
    public function __construct()
    {
        add_action('rest_api_init', function () {
            register_rest_route('collabpay/v1', 'ping', [
                'methods' => 'GET',
                'callback' => [$this, 'collabpay_ping'],
                'permission_callback' => function ($request) {
                    return $this->validateRequest($request);
                }
            ]);

            register_rest_route('collabpay/v1', 'webhooks/deactivate', [
                'methods' => 'GET',
                'callback' => [$this, 'collabpay_webhooks_deactivate'],
                'permission_callback' => function ($request) {
                    return $this->validateRequest($request);
                }
            ]);

            register_rest_route('collabpay/v1', 'webhooks/activate', [
                'methods' => 'GET',
                'callback' => [$this, 'collabpay_webhooks_activate'],
                'permission_callback' => function ($request) {
                    return $this->validateRequest($request);
                }
            ]);

            register_rest_route('collabpay/v1', 'revoke', [
                'methods' => 'GET',
                'callback' => [$this, 'collabpay_disconnect_plugin'],
                'permission_callback' => function ($request) {
                    return $this->validateRequest($request);
                }
            ]);

            register_rest_route('collabpay/v1', 'refunds', [
                'methods' => 'GET',
                'callback' => [$this, 'collabpay_refunds'],
                'permission_callback' => function ($request) {
                    return $this->validateRequest($request);
                }
            ]);
        });
    }

    public function validateRequest($request)
    {
        return get_option('collabpay_api_key') === $request->get_header('collabpay');
    }

    public function collabpay_ping($request)
    {
        return [
            'response' => 'pong',
            'shop_name' => get_bloginfo(),
            'wp_version' => get_bloginfo( 'version' ),
            'plugin_version' => COLLABPAY_PLUGIN_VERSION,
            'woo_country' => get_option( 'woocommerce_default_country' ),
            'wp_timezone' => wp_timezone_string(),
        ];
    }

    public function collabpay_webhooks_deactivate($request)
    {
        (new CollabpayWebhook())->delete();

        return ['success' => true];
    }

    public function collabpay_webhooks_activate($request)
    {
        (new CollabpayWebhook())->create();

        return ['success' => true];
    }

    public function collabpay_disconnect_plugin($request)
    {
        deactivate_collabpay();

        deactivate_plugins(plugin_basename(__FILE__));

        return ['success' => true];
    }

    public function collabpay_refunds($request)
    {
        // added in v1.7.0

        $query_args = array(
            'fields'         => 'id=>parent',
            'post_type'      => 'shop_order_refund',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'date_query'     => array(
                'column' => 'post_date_gmt',
                'after' => $request->get_param( 'after' ), // strtotime() format
                'inclusive' => true,
            )
        );

        $refunds = get_posts( $query_args );

        return [
            'order_ids' => array_values( array_unique( $refunds ) ),
        ];
    }
}