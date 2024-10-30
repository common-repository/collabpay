<?php

class CollabPayWooCommerceFields
{
    public function __construct()
    {
        // cost field to standard products
        add_action('woocommerce_product_options_general_product_data', [$this, 'addCostToStandardProducts'], 10, 3);
        add_action('woocommerce_process_product_meta', [$this, 'saveCostFromStandardProducts'], 10, 3);

        // cost field to variable products
        add_action('woocommerce_variation_options_pricing', [$this, 'addCostToVariationProducts'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'saveCostFromVariationProducts'], 10, 3);
        add_filter('woocommerce_available_variation', [$this, 'loadCostFromVariationProducts']);

        // vendor field to standard products
        add_action('woocommerce_product_options_advanced', [$this, 'addVendorToStandardProducts'], 10, 3);
        add_action('woocommerce_process_product_meta', [$this, 'saveVendorToStandardProducts'], 10, 3);
    }

    public function addCostToVariationProducts($loop, $variation_data, $variation)
    {
        echo '<div class="form-row form-row-full">';
        
        woocommerce_wp_text_input(
            [
                'id' => "_collabpay_cost{$loop}",
                'name' => "_collabpay_cost[{$loop}]",
                'placeholder' => '',
                'label' => __('CollabPay cost ('.get_woocommerce_currency_symbol().')', 'woocommerce'),
                'value' => get_post_meta($variation->ID, '_collabpay_cost', true),
                'type' => 'number',
                'class' => 'short wc_input_price',
                'custom_attributes' => [
                    'step' => '0.01',
                    'min' => '0'
                ]
            ]
        );

        echo "</div>";
    }

    public function addVendorToStandardProducts()
    {
        echo '<div class="product_custom_field">';

        woocommerce_wp_text_input(
            [
                'id' => '_collabpay_vendor',
                'placeholder' => '',
                'label' => __('CollabPay Vendor', 'woocommerce'),
                'type' => 'text',
            ]
        );

        echo '</div>';
    }

    public function saveVendorToStandardProducts($post_id)
    {
        // set a default vendor as the site name / URL
        $value = get_bloginfo() ?: site_url();

        if (isset($_POST['_collabpay_vendor']) && $_POST['_collabpay_vendor']) {
            $value = esc_attr($_POST['_collabpay_vendor']);
        }

        update_post_meta($post_id, '_collabpay_vendor', $value);
    }

    public function saveCostFromVariationProducts($variation_id, $loop)
    {
        update_post_meta($variation_id,
            '_collabpay_cost',
            esc_attr(isset($_POST['_collabpay_cost'][$loop]) ? $_POST['_collabpay_cost'][$loop] : null)
        );
    }

    public function loadCostFromVariationProducts($variation)
    {
        $variation['_collabpay_cost'] = get_post_meta($variation[ 'variation_id' ], '_collabpay_cost', true);

        return $variation;
    }

    public function addCostToStandardProducts()
    {
        woocommerce_wp_text_input(
            [
                'id' => '_collabpay_cost',
                'placeholder' => '',
                'label' => __('CollabPay cost ('.get_woocommerce_currency_symbol().')', 'woocommerce'),
                'type' => 'number',
                'custom_attributes' => [
                    'step' => '0.01',
                    'min' => '0'
                ]
            ]
        );
    }

    public function saveCostFromStandardProducts($post_id)
    {
        update_post_meta(
            $post_id,
            '_collabpay_cost',
            esc_attr(isset($_POST['_collabpay_cost']) ? $_POST['_collabpay_cost'] : null)
        );
    }
}
