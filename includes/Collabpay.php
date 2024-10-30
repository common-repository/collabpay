<?php

class Collabpay
{
    public $host = 'https://collabpay.app';

    public function __construct()
    {
        if (wp_get_environment_type() == 'local') {
            $this->host = 'https://collabpay.test';
        }
    }

    public function updateRestApiData($data, $collabPayApiKey, $roll = false)
    {
        $postData = [
            'headers' => [
                'x-wc-webhook-source' => site_url(),
                'x-wc-webhook-signature' => $collabPayApiKey,
            ],
            'body' => $data
        ];

        wp_remote_post("{$this->host}/api/woocommerce/api-keys", $postData);
    }

    public function validateApiKey($apiKey)
    {
        $postData = [
            'headers' => [
                'x-wc-webhook-source' => site_url(),
                'x-wc-webhook-signature' => $apiKey,
            ],
        ];

        $response = wp_remote_post("{$this->host}/api/woocommerce/api-keys/validate", $postData);

        return json_decode(json_encode($response), true);
    }

    public function deleteIntegration($apiKey)
    {
        wp_remote_request("{$this->host}/api/woocommerce/api-keys", [
            'method' => 'DELETE',
            'headers' => [
                'x-wc-webhook-source' => site_url(),
                'x-wc-webhook-signature' => $apiKey,
            ],
        ]);
    }
}
