<?php

class CollabpaySettingsPageAdmin {

    const API_KEY_OPTION_NAME = 'collabpay_api_key';

    private $page_name;

    public function __construct($plugin_name) {
        $this->page_name = $plugin_name;

        add_action('admin_menu', [$this, 'addPluginAdminMenu'], 9);
        add_action('admin_init', [$this, 'registerAndBuildFields']);

        add_action('update_option', [$this, 'validateUpdateAPiKey'], 10, 3);
        add_action('add_option', [$this, 'validateAddAPiKey'], 10, 3);

        add_action('admin_post_update_roll', [$this, 'roll'], 10, 3);
    }

    public function addPluginAdminMenu() {
        $icon = plugin_dir_url(dirname(__FILE__)) . '/assets/collabpay.svg' ;
        add_menu_page($this->page_name, 'CollabPay', 'administrator', $this->page_name, [$this, 'displayPluginAdminDashboard'], $icon, 26);
    }

    public function displayPluginAdminDashboard() {
        require_once plugin_dir_path(dirname( __FILE__ ) ) . 'partials/collabpay-settings-page.php';
    }

    public function settingsPageSettingsMessages($error_message) {
        switch ($error_message) {
            case '1':
                $message = __('There was an error adding this setting. Please try again.');
                $err_code = esc_attr(self::API_KEY_OPTION_NAME);
                $setting_field = self::API_KEY_OPTION_NAME;
                break;
        }

        $type = 'error';

        add_settings_error(
            `$setting_field`,
            $err_code,
            $message,
            $type
        );
    }

    public function registerAndBuildFields() {
        add_settings_section(
            'settings_page_general_section',
            '',
            [$this, 'settings_page_display_general_account'],
            'settings_page_general_settings'
        );

        add_settings_field(
            self::API_KEY_OPTION_NAME,
            'API Key',
            [$this, 'settings_page_render_settings_field'],
            'settings_page_general_settings',
            'settings_page_general_section',
            [
                'type' => 'input',
                'subtype' => 'text',
                'id' => self::API_KEY_OPTION_NAME,
                'name' => self::API_KEY_OPTION_NAME,
                'required' => 'true',
                'get_options_list' => '',
                'value_type' => 'normal',
                'wp_data' => 'option'
            ]
        );

        register_setting('settings_page_general_settings', self::API_KEY_OPTION_NAME);
    }

    public function settings_page_display_general_account() {
        echo '<p>Providing an API key will allow the plugin to sync data to CollabPay. <a href="https://collabpay.app/register" target="_blank">Register for CollabPay</a> and generate an API key.</p>';
    }

    public function settings_page_render_settings_field($args) {
        $value = esc_attr(get_option($args['name']));

        if (isset($args['disabled'])) {
            echo sprintf(
                '<input type="text" id="%s" name="%s" size="40" disabled value="%s" /><input type="hidden" id="%s" name="%s" size="40" value="%s" />',
                $args['id'].'_disabled',
                $args['name'].'_disabled',
                $value,
                $args['id'],
                $args['name'],
                $value
            );
        } else {
            echo sprintf(
                '<input type="text" id="%s" name="%s" required size="40" value="%s" />',
                $args['id'],
                $args['name'],
                $value
            );
        }
    }

    public function validateAddAPiKey($option, $value)
    {
        require_once plugin_dir_path(dirname( __FILE__ ) ) . 'includes/Collabpay.php';

        if ($option == self::API_KEY_OPTION_NAME) {
            $response = (new Collabpay())->validateApiKey($value);

            if ($response['response']['code'] == 200) {
                $this->updateApiListener($option, null, $value);

                return;
            }

            $message = json_decode($response['body'], true);
            wp_die(__($message['message']), $message['message'], ['back_link' => true]);
        }
    }

    public function validateUpdateAPiKey($option, $value, $newValue)
    {
        require_once plugin_dir_path(dirname( __FILE__ ) ) . 'includes/Collabpay.php';

        if ($option == self::API_KEY_OPTION_NAME) {
            $response = (new Collabpay())->validateApiKey($newValue);

            if ($response['response']['code'] == 200) {
                $this->updateApiListener($option, $value, $newValue);

                return;
            }

            $message = json_decode($response['body'], true);
            wp_die(__($message['message']), $message['message'], ['back_link' => true]);
        }
    }

    public function roll()
    {
        // Roll API key's and webhooks to regenerate them
        // this is useful if the user that installed
        // the plugin is removed, as it'll regenerate
        // required webhooks with a new user that has
        // permissions.

        $collabPayApiKey = $this->getApiKey();

        if (! $collabPayApiKey) {
            add_settings_error('ct_msg', 'ct_msg_option', __("Error - could not find API key."), 'warning');
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_safe_redirect('/wp-admin/admin.php?page='.$this->page_name);

            exit;
        }

        require_once plugin_dir_path(dirname( __FILE__ ) ) . 'includes/CollabpayWebhook.php';
        require_once plugin_dir_path(dirname( __FILE__ ) ) . 'includes/CollabpayRestApi.php';

        (new CollabpayWebhook())->delete();
        (new CollabpayRestApi())->delete();

        $this->updateWebhookSecret($collabPayApiKey, true);

        add_settings_error('ct_msg', 'ct_msg_option', __("Successfully rolled API keys."), 'success');
        set_transient('settings_errors', get_settings_errors(), 30);
        wp_safe_redirect('/wp-admin/admin.php?page='.$this->page_name);

        exit;
    }

    public function getApiKey()
    {
        global $wpdb;

        $row = $wpdb->get_row(
            "select option_value from {$wpdb->prefix}options where option_name = '".self::API_KEY_OPTION_NAME."'"
        );

        if (isset($row->option_value)) {
            return $row->option_value;
        }

        return null;
    }

    public function updateApiListener($option, $oldValue, $newValue)
    {
        if ($option == self::API_KEY_OPTION_NAME) {
            $this->updateWebhookSecret($newValue);
        }
    }

    public function addApiKeyListener($option, $value)
    {
        if ($option == self::API_KEY_OPTION_NAME) {
            $this->updateWebhookSecret($value);
        }
    }

    private function updateWebhookSecret($secret, $roll = false)
    {
        require_once plugin_dir_path(dirname( __FILE__ ) ) . 'includes/CollabpayWebhook.php';
        require_once plugin_dir_path(dirname( __FILE__ ) ) . 'includes/CollabpayRestApi.php';

        (new CollabpayWebhook())->create();
        (new CollabpayWebhook())->updateWebhookSecret($secret);
        (new CollabpayRestApi())->create($secret, $roll);
    }
}
