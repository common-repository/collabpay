<?php

class CollabpayRestApi
{
    const DESCRIPTION = 'CollabPay API';
    const PERMISSION = 'read';

    protected $userId;

    public function __construct()
    {
        $this->userId = wp_get_current_user()->ID;
    }

    public function delete()
    {
        global $wpdb;

        if ($this->is_rest_api_exists()) {
            $wpdb->delete(
                "{$wpdb->prefix}woocommerce_api_keys",
                ['key_id' => $this->get_rest_api()->key_id]
            );
        }
    }

    public function create($collabPayApiKey, $roll = false)
    {
        global $wpdb;

        $response = [
            'shop_name' => get_bloginfo(),
            'wp_version' => get_bloginfo( 'version' ),
            'plugin_version' => COLLABPAY_PLUGIN_VERSION,
            'woo_country' => get_option( 'woocommerce_default_country' ),
            'wp_timezone' => wp_timezone_string(),
            'roll' => $roll,
        ];

        if (! $this->is_rest_api_exists()) {
            $consumer_key    = 'ck_' . wc_rand_hash();
            $consumer_secret = 'cs_' . wc_rand_hash();

            $data = [
                'user_id' => $this->userId,
                'description' => self::DESCRIPTION,
                'permissions' => self::PERMISSION,
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key' => substr($consumer_key, -7)
            ];

            $wpdb->insert(
                $wpdb->prefix . 'woocommerce_api_keys',
                $data,
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );

            $response['consumer_key'] = $consumer_key;
            $response['consumer_secret_key'] = $consumer_secret;
        } else {
            $this->delete_rest_api();
            $this->create($collabPayApiKey);
        }

        (new Collabpay())->updateRestApiData($response, $collabPayApiKey, $roll);
    }

    public function get_rest_api()
    {
        global $wpdb;

        $description = self::DESCRIPTION;
        $permission = self::PERMISSION;

        return $wpdb->get_row(
            "select * from {$wpdb->prefix}woocommerce_api_keys where description = '{$description}' and permissions = '{$permission}'"
        );
    }

    public function delete_rest_api()
    {
        global $wpdb;

        $wpdb->delete("{$wpdb->prefix}woocommerce_api_keys", [
            'description' => self::DESCRIPTION,
            'permissions' => self::PERMISSION,
        ]);
    }

    protected function is_rest_api_exists()
    {
        return ! empty($this->get_rest_api());
    }
}
