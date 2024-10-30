<?php

class CollabpaySettingsPage
{
    const PAGE_NAME = 'CollabPay Settings';

	protected $loader;

	protected $version;

    public function __construct()
    {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname( __FILE__ )) . 'includes/CollabpaySettingsPageLoader.php';
        require_once plugin_dir_path(dirname( __FILE__ )) . 'includes/CollabpaySettingsPageAdmin.php';

        $this->loader = new CollabpaySettingsPageLoader();
    }

    private function define_admin_hooks()
    {
        new CollabpaySettingsPageAdmin(self::PAGE_NAME);
    }

    public function run() {
        $this->loader->run();
    }
}
