<?php

class Vizapata_sw_integration_Admin
{

	private $plugin_name;
	private $version;
	private const state_code_field = 'cod_depto';
	private const city_code_field = 'cod_mpio';
	private const state_name_field = 'dpto';
	private const city_name_field = 'nom_mpio';

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
		register_setting('vizapata_sw_integration_options', 'vizapata_sw_integration_siigo_invoice_id');
		register_setting('vizapata_sw_integration_options', 'vizapata_sw_integration_siigo_taxes_id');
		register_setting('vizapata_sw_integration_options', 'vizapata_sw_integration_siigo_seller_id');
		register_setting('vizapata_sw_integration_options', 'vizapata_sw_integration_siigo_warehouse_id');
		register_setting('vizapata_sw_integration_options', 'vizapata_sw_integration_siigo_observations');

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

		add_settings_field(
			'vizapata_sw_integration_siigo_invoice_id',
			sprintf('%s: *', __('Document type ID', 'vizapata_sw_integration')),
			array($this, 'siigo_invoice_id'),
			'vizapata_sw_integration_options',
			'vizapata_sw_integration_general'
		);

		add_settings_field(
			'vizapata_sw_integration_siigo_taxes_id',
			sprintf('%s: *', __('Taxes ID', 'vizapata_sw_integration')),
			array($this, 'siigo_taxes_id'),
			'vizapata_sw_integration_options',
			'vizapata_sw_integration_general'
		);

		add_settings_field(
			'vizapata_sw_integration_siigo_seller_id',
			sprintf('%s: *', __('Seller ID', 'vizapata_sw_integration')),
			array($this, 'siigo_seller_id'),
			'vizapata_sw_integration_options',
			'vizapata_sw_integration_general'
		);

		add_settings_field(
			'vizapata_sw_integration_siigo_warehouse_id',
			sprintf('%s: *', __('Wharehouse ID', 'vizapata_sw_integration')),
			array($this, 'siigo_warehouse_id'),
			'vizapata_sw_integration_options',
			'vizapata_sw_integration_general'
		);

		add_settings_field(
			'vizapata_sw_integration_siigo_observations',
			sprintf('%s: *', __('Invoice observations', 'vizapata_sw_integration')),
			array($this, 'siigo_observations'),
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

	public function siigo_invoice_id()
	{
		printf('<input type="text" class="regular-text" name="vizapata_sw_integration_siigo_invoice_id" value="%s" required>', get_option('vizapata_sw_integration_siigo_invoice_id'));
		printf('<p class="description" id="tagline-description">%s.</p>', __('The identifier of the document type to be used. Please check in the Siigo cloud invoice document types', 'vizapata_sw_integration'));
	}

	public function siigo_taxes_id()
	{
		printf('<input type="text" class="regular-text" name="vizapata_sw_integration_siigo_taxes_id" value="%s" required>', get_option('vizapata_sw_integration_siigo_taxes_id'));
		printf('<p class="description" id="tagline-description">%s.</p>', __('The identifier of the taxes aplicable for all products', 'vizapata_sw_integration'));
	}

	public function siigo_seller_id()
	{
		printf('<input type="text" class="regular-text" name="vizapata_sw_integration_siigo_seller_id" value="%s" required>', get_option('vizapata_sw_integration_siigo_seller_id'));
		printf('<p class="description" id="tagline-description">%s.</p>', __('The ID for the seller associated to the orders. Please check in the Siigo cloud users', 'vizapata_sw_integration'));
	}

	public function siigo_warehouse_id()
	{
		printf('<input type="text" class="regular-text" name="vizapata_sw_integration_siigo_warehouse_id" value="%s" required>', get_option('vizapata_sw_integration_siigo_warehouse_id'));
		printf('<p class="description" id="tagline-description">%s.</p>', __('The wharehouse ID where the products will beshipped from. Please check in the Siigo cloud wharehouse, if any', 'vizapata_sw_integration'));
	}

	public function siigo_observations()
	{
		printf('<input type="text" class="regular-text" name="vizapata_sw_integration_siigo_observations" value="%s" required>', get_option('vizapata_sw_integration_siigo_observations'));
		printf('<p class="description" id="tagline-description">%s.</p>', __('Additional observations to include in generated invoices', 'vizapata_sw_integration'));
	}

	public function general_settings_section_callback()
	{
	}

	private function load_location_codes()
	{
		$deptos = json_decode(file_get_contents(plugin_dir_path(__FILE__)  . 'CO.json'), true);
		$codes = array();
		foreach ($deptos as $depto) {
			$state_name = strtoupper(remove_accents($depto[self::state_name_field]));
			$city_name = strtoupper(remove_accents($depto[self::city_name_field]));
			if (!isset($codes[$state_name])) {
				$codes[$state_name] = array();
				$codes[$state_name]['code'] = str_pad($depto[self::state_code_field], 2, '0', STR_PAD_LEFT);
				$codes[$state_name]['cities'] = array();
			}
			$codes[$state_name]['cities'][$city_name] = $depto[self::city_code_field];
		}
		return $codes;
	}

	public function woocommerce_payment_complete($order_id)
	{
		$siigo_proxy = new Vizapata_Siigo_Proxy();

		$order = wc_get_order($order_id);
		$local_customer = $this->build_customer_order($order);
		
		try {
			$siigo_proxy->authenticate();
			$remote_customer = $siigo_proxy->findCustomerByDocument($local_customer['identification']);
			if ($remote_customer === false) {
				$remote_customer = $siigo_proxy->createCustomer($local_customer);
			}
			update_post_meta($order_id, '_siigo_customer_id', $remote_customer->id);
			$local_order = $this->build_order($order, $local_customer);
			$remote_order = $siigo_proxy->createInvoice($local_order);
			update_post_meta($order_id, '_siigo_invoice_id', $remote_order->id);
			update_post_meta($order_id, '_siigo_invoice_number', $remote_order->number);
			update_post_meta($order_id, '_siigo_invoice_document_id', $remote_order->document->id);
			update_post_meta($order_id, '_siigo_invoice_name', $remote_order->name);
		} catch (Exception $ex) {
			// TODO: add exception as a local order note 
		}
	}

	private function build_order($order, $customer)
	{
		$shipping_products = array(
			'5000' => '1',
			'10000' => '2',
			'15000' => '3',
			'20000' => '4',
			'25000' => '5',
		);
		$items = array(
			array(
				'code' => $shipping_products[$order->get_shipping_total()],
				'description' => __('Shipping', 'vizapata_sw_integration'),
				'price' => $order->get_shipping_total(),
				'quantity' => 1,
			)
		);
		foreach ($order->get_items() as $item) {
			$product = $item->get_product();
			$new_item = array(
				'code' => $product->get_sku(),
				'description' => $product->get_name(),
				'price' => $product->get_price(),
				'quantity' => $item->get_quantity(),
				'seller' => get_option('vizapata_sw_integration_siigo_seller_id'),
				'wharehouse' => array(
					'id' => get_option('vizapata_sw_integration_siigo_warehouse_id')
				),
				'taxes' => array(
					'id' => get_option('vizapata_sw_integration_siigo_taxes_id')
				)
			);
			array_push($items, $new_item);
		}

		$local_order = array(
			'document' => array(
				'id' => get_option('vizapata_sw_integration_siigo_invoice_id'),
			),
			'customer' => array(
				'person_type' => $customer['person_type'],
				'id_type' => $customer['id_type'],
				'identification' => $customer['identification']
			),
			'seller' => get_option('vizapata_sw_integration_siigo_seller_id'),
			'items' => $items,
			'observations' => get_option('vizapata_sw_integration_siigo_observations'),
		);
		return $local_order;
	}

	private function build_customer_order($order)
	{
		$order_meta = get_post_meta($order->get_id());
		$codes = $this->load_location_codes();
		$states = apply_filters('woocommerce_states', array());
		$country_code = 'CO';

		$billing_state_abbr = $order_meta['_billing_state'][0];
		$billing_state_name = strtoupper(remove_accents($states[$country_code][$billing_state_abbr]));
		$billing_city_name = strtoupper(remove_accents($order_meta['_billing_city'][0]));
		$billing_state_code = $codes[$billing_state_name]['code'];
		$billing_city_code = $codes[$billing_state_name]['cities'][$billing_city_name];

		$person_type = $order_meta['_billing_person_type'][0];
		$is_person = $person_type == 'Person';
		$id_type = $is_person ? 13 : 31;
		$name = array();
		$billing_pone = array(
			'number' => $order_meta['_billing_phone'][0],
		);
		$contacts = array(
			'first_name' => $order_meta['_billing_firstname'][0],
			'last_name' => $order_meta['_billing_lastname'][0],
			'email' => $order_meta['_billing_email'][0],
			'phone' => $billing_pone,
		);

		if ($is_person) {
			array_push($name, $order_meta['_billing_firstname'][0]);
			array_push($name, $order_meta['_billing_lastname'][0]);
		} else {
			array_push($name, $order_meta['_billing_company'][0]);
			$contacts['first_name'] = $order_meta['_billing_contact_firstname'][0];
			$contacts['last_name'] = $order_meta['_billing_contact_lastname'][0];
		}

		$customer = array(
			'type' => 'Customer',
			'person_type' => $person_type, // Person, Company
			'id_type' => "$id_type",
			'identification' => $order_meta['_billing_identification'][0],
			'check_digit' => $order_meta['_billing_check_digit'][0],
			'name' => $name,
			'fiscal_responsibilities' => array(
				array("code" => "R-99-PN")
			),
			'address' => array(
				'address' => $order_meta['_billing_address'][0],
				'city' => array(
					'country_code' => $country_code,
					'city_code' => $billing_city_code,
					'state_code' => $billing_state_code,
				),
				'postal_code' => '',
			),
			'phones' => array($billing_pone),
			'contacts' => array($contacts),
			'comments' => __('User created by the Woocommerce-Siigo integration app', 'vizapata_sw_integration')
		);

		return $customer;
	}
}
