<?php
/*
    Plugin Name: CollabPay
    Plugin URI:  https://collabpay.app/wordpress-plugin
    Description: CollabPay webhook and rest API plugin
    Version:     1.9.0
    Author:      collabpay
    Author URI:  https://collabpay.app
*/

const COLLABPAY_PLUGIN_VERSION = '1.9.0';

require('includes/Collabpay.php');
require('includes/CollabpayWebhook.php');
require('includes/CollabpayRestApi.php');
require('includes/CollabpaySettingsPage.php');
require('includes/CollabPayWooCommerceFields.php');
require('includes/CollabPayApi.php');

register_activation_hook(__FILE__, function ($networkWide) {
    if (! is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));

        wp_die(__('Please install and activate WooCommerce.', 'woocommerce-addon-slug'), 'Plugin dependency check', ['back_link' => true]);
    }

    if (is_multisite() && $networkWide) {
        wp_die(__('CollabPay can not be activated network wide. Please activate per site.', 'woocommerce-addon-slug'), 'Plugin dependency check', ['back_link' => true]);
    }
});

function deactivate_collabpay()
{
    (new CollabpayWebhook())->delete();
    (new CollabpayRestApi())->delete();

    if ($key = get_option('collabpay_api_key')) {
        (new Collabpay())->deleteIntegration($key);
        delete_option('collabpay_api_key');
    }
}

register_deactivation_hook(__FILE__, function () {
    deactivate_collabpay();
});

add_action('init', function () {
    if (isset($_GET['cp_ref'])) {
        setcookie( "cp_ref", $_GET['cp_ref'], strtotime( '+30 days' ));
    }
});

// doing this allows CollabPay to get the data via webhooks and the API.
add_action('woocommerce_checkout_order_processed', function ($order) {
    // if AffiliateWP cookie is present then we can add the affiliate ref reference to the order metadata.
    $affiliateWPCookieName = 'affwp_ref';
    $affiliateWPCookieValue = isset($_COOKIE[$affiliateWPCookieName])
        ? sanitize_text_field($_COOKIE[$affiliateWPCookieName])
        : null;

    if ($affiliateWPCookieValue) {
        add_post_meta($order->id, 'affwp_ref', $affiliateWPCookieValue);
    }

    // if there is a collbapay cookie then we can add that to the order metadata
    $collabPayCookieName = 'cp_ref';
    $collabPayCookieValue = isset($_COOKIE[$collabPayCookieName])
        ? sanitize_text_field($_COOKIE[$collabPayCookieName])
        : null;

    if ($collabPayCookieValue) {
        add_post_meta($order->id, '_cp_ref', $collabPayCookieValue);
    }
}, 9, 3);

add_filter('woocommerce_max_webhook_delivery_failures', function ($number) {
    // only try five times, after this it'll be picked up by a
    // scheduled task to find any missing orders / products.

    return 5;
});

if (wp_get_environment_type() == 'local') {
    function allow_unsafe_urls($args)
    {
        $args['reject_unsafe_urls'] = false;
        return $args;
    }

    add_filter('http_request_args', 'allow_unsafe_urls');
    add_filter('https_ssl_verify', '__return_false');
}

(new CollabpaySettingsPage())->run();
new CollabPayWooCommerceFields();
new CollabPayApi();
