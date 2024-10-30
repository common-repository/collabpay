<?php

class CollabpayWebhook extends Collabpay
{
    public $topics = [
        'product.updated' => 'products-update',
        'product.created' => 'products-create',
        'product.deleted' => 'products-delete',
        'product.restored' => 'products-restored',

        'order.updated' => 'orders-update',
        'order.created' => 'orders-create',
        'order.deleted' => 'orders-delete',
        'order.restored' => 'orders-restored',
    ];

    public $path = 'api/woocommerce/webhook';

    public function create()
    {
        foreach ($this->topics as $key => $value) {
            $name = ucwords(str_replace('.', ' ', $key));
            $url = "{$this->host}/{$this->path}/{$value}";

            if (! $this->is_existing($url)) {
                $webhook = new WC_Webhook();
                $webhook->set_topic($key);
                $webhook->set_delivery_url($url);
                $webhook->set_status('active');
                $webhook->set_name('CollabPay - '.$name);
                $webhook->set_user_id(wp_get_current_user()->ID);
                $webhook->save();
            }
        }
    }

    public function delete()
    {
        $data_store = WC_Data_Store::load('webhook');
        $webhooks = $data_store->search_webhooks();

        foreach ($webhooks as $webhook) {
            $webhook = wc_get_webhook($webhook);

            foreach ($this->topics as $value) {
                $url = "{$this->host}/{$this->path}/{$value}";

                if ($webhook->get_delivery_url() == $url) {
                    $webhook->delete();
                }
            }
        }
    }

    protected function is_existing($delivery_url)
    {
        $is_existing = false;

        foreach ($this->get_webhooks() as $webhook) {
            $webhook = wc_get_webhook($webhook);

            if ($webhook->get_delivery_url() == $delivery_url) {
                $is_existing = true;
            }
        }

        return $is_existing;
    }

    public function get_webhooks()
    {
        $data_store = WC_Data_Store::load('webhook');

        return $data_store->search_webhooks();
    }

    public function get_webhooks_by_id($id)
    {
        $_webhook = null;

        foreach ($this->get_webhooks() as $webhook) {
            $webhook = wc_get_webhook($webhook);

            if ($webhook->get_id() == $id) {
                $_webhook = $webhook;
            }
        }

        return $_webhook;
    }

    public function updateWebhookSecret($secret)
    {
        foreach ($this->get_webhooks() as $webhook) {
            $webhook = wc_get_webhook($webhook);

            foreach ($this->topics as $value) {
                $url = "{$this->host}/{$this->path}/{$value}";

                if ($webhook->get_delivery_url() == $url) {
                    $webhook->set_secret($secret);
                    $webhook->save();
                }
            }
        }
    }
}
