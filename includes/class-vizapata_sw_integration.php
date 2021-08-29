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
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-vizapata_sw_integration-admin.php';
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
