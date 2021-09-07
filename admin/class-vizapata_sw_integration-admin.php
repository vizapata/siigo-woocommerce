<?php

class Vizapata_sw_integration_Admin
{

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	function plugin_action_links($actions, $plugin_file, $plugin_data, $context)
	{
		array_unshift(
			$actions,
			sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				menu_page_url('vizapata_sw_integration', false),
				esc_attr__('Siigo integration settings', 'vizapata_sw_integration'),
				esc_html__('Settings', 'default')
			)
		);
		return $actions;
	}

	public function admin_menu()
	{
		add_submenu_page(
			'options-general.php',
			__('Siigo integration settings', 'vizapata_sw_integration'),
			__('Siigo integration settings', 'vizapata_sw_integration'),
			'manage_options',
			'vizapata_sw_integration',
			array($this, 'options_page')
		);
	}

	public function options_page()
	{
		require_once plugin_dir_path(__FILE__)  . 'partials/vizapata_sw_integration-admin-display.php';
	}

	public function admin_init()
	{
		register_setting('vizapata_sw_integration_options', 'vizapata_sw_integration_siigo_username');
		register_setting('vizapata_sw_integration_options', 'vizapata_sw_integration_siigo_apikey');
		register_setting('vizapata_sw_integration_options', 'vizapata_sw_integration_siigo_api_url');

		add_settings_section(
			'vizapata_sw_integration_general',
			__('Siigo cloud API settings', 'vizapata_sw_integration'),
			array($this, 'general_settings_section_callback'),
			'vizapata_sw_integration_options'
		);
		
		add_settings_field(
			'vizapata_sw_integration_siigo_api_url',
			sprintf('%s: *', __('Siigo API URL', 'vizapata_sw_integration')),
			array($this, 'siigo_api_url'),
			'vizapata_sw_integration_options',
			'vizapata_sw_integration_general'
		);

		add_settings_field(
			'vizapata_sw_integration_siigo_username',
			sprintf('%s: *', __('Username', 'vizapata_sw_integration')),
			array($this, 'siigo_username'),
			'vizapata_sw_integration_options',
			'vizapata_sw_integration_general'
		);

		add_settings_field(
			'vizapata_sw_integration_siigo_apikey',
			sprintf('%s: *', __('API Key', 'vizapata_sw_integration')),
			array($this, 'siigo_apikey'),
			'vizapata_sw_integration_options',
			'vizapata_sw_integration_general'
		);
	}

	public function siigo_api_url()
	{
		printf('<input type="url" class="regular-text" name="vizapata_sw_integration_siigo_api_url" value="%s" required pattern="https://.*" placeholder="https://example.com/">', get_option('vizapata_sw_integration_siigo_api_url'));
		printf('<p class="description" id="tagline-description">%s.</p>', __('The URL of the Siigo cloud', 'vizapata_sw_integration'));
	}

	public function siigo_username()
	{
		printf('<input type="text" class="regular-text" name="vizapata_sw_integration_siigo_username" value="%s" required>', get_option('vizapata_sw_integration_siigo_username'));
		printf('<p class="description" id="tagline-description">%s.</p>', __('The username used to connect with the Siigo cloud', 'vizapata_sw_integration'));
	}

	public function siigo_apikey()
	{
		printf('<input type="text" class="regular-text" name="vizapata_sw_integration_siigo_apikey" value="%s" required>', get_option('vizapata_sw_integration_siigo_apikey'));
		printf('<p class="description" id="tagline-description">%s.</p>', __('The API KEY used to connect with the Siigo cloud', 'vizapata_sw_integration'));
	}

	public function general_settings_section_callback()
	{
	}
}
