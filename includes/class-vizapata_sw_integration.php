<?php

class Vizapata_sw_integration
{
	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct()
	{
		if (defined('VIZAPATA_SW_INTEGRATION_VERSION')) {
			$this->version = VIZAPATA_SW_INTEGRATION_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'vizapata_sw_integration';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	private function load_dependencies()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vizapata_sw_integration-loader.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vizapata_sw_integration-i18n.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-vizapata-siigo-proxy.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-vizapata_sw_integration-settings.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-vizapata_sw_integration-admin.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-vizapata_sw_integration-order.php';
		$this->loader = new Vizapata_sw_integration_Loader();
	}

	private function set_locale()
	{
		$plugin_i18n = new Vizapata_sw_integration_i18n();
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	private function define_admin_hooks()
	{
		$plugin_admin = new Vizapata_sw_integration_Admin($this->get_plugin_name(), $this->get_version());
		$settings = new Vizapata_sw_integration_Settings();
		$order = new Vizapata_sw_integration_Order();

		$this->loader->add_action('woocommerce_payment_complete', $plugin_admin, 'woocommerce_payment_complete');
		$this->loader->add_action('woocommerce_order_status_changed', $plugin_admin, 'woocommerce_order_status_changed', 10, 3);
		$this->loader->add_action('init', $plugin_admin, 'download_electronic_invoice');
		$this->loader->add_action('wp_ajax_vizapata_sw_integration_generate_invoice', $plugin_admin, 'generate_invoice_ajax');
		$this->loader->add_filter('plugin_action_links_' . $this->get_plugin_name() . '/' . $this->get_plugin_name() . '.php', $plugin_admin, 'plugin_action_links', 10, 4);
		$this->loader->add_filter('http_request_timeout', $settings, 'http_request_timeout', 10, 2);

		// Woocommerce tab
		$this->loader->add_filter('woocommerce_settings_tabs_array', $settings,  'add_settings_tab', 50);
		$this->loader->add_action('woocommerce_settings_tabs_siigo_settings', $settings, 'settings_tab');
		$this->loader->add_action('woocommerce_update_options_siigo_settings', $settings, 'update_settings');

		// Order settings
		$this->loader->add_action('add_meta_boxes', $order, 'add_meta_boxes');
		$this->loader->add_action('admin_enqueue_scripts', $order, 'admin_enqueue_scripts');
	}

	public function run()
	{
		$this->loader->run();
	}

	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	public function get_loader()
	{
		return $this->loader;
	}

	public function get_version()
	{
		return $this->version;
	}
}
