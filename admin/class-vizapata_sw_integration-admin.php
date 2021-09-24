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
				'admin.php?page=wc-settings&tab=siigo_settings',
				esc_attr__('Siigo integration settings', 'vizapata_sw_integration'),
				esc_html__('Settings', 'default')
			)
		);
		return $actions;
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
			update_post_meta($order_id, '_siigo_invoice_name', $remote_order->name);
			$order->add_order_note(sprintf(__('Invoice created with document number: %s'), $remote_order->number));
		} catch (Exception $ex) {
			$order->add_order_note(__('Error during invoice creation: ') . $ex->getMessage());
		}
	}

	private function build_order($order, $customer)
	{
		$items = array();
		foreach ($order->get_items() as $item) {
			$product = $item->get_product();
			$new_item = array(
				'code' => $product->get_sku(),
				'description' => $product->get_name(),
				'price' => $product->get_price(),
				'quantity' => $item->get_quantity(),
				'seller' => get_option('wc_settings_woo_siigo_seller_id'),
				'warehouse' => array(
					'id' => get_option('wc_settings_woo_siigo_warehouse_id')
				),
				'taxes' => array(
					array(
						'id' => get_option('wc_settings_woo_siigo_taxes_id'),
						'value' => $order->get_item_tax($item),
					)
				),
			);
			array_push($items, $new_item);
		}
		array_push($items, array(
			'code' => get_option('wc_settings_woo_siigo_shipping_id'),
			'description' => $order->get_shipping_method(),
			'price' => $order->get_shipping_total(),
			'quantity' => 1,
			'taxes' => array(
				array(
					'id' => get_option('wc_settings_woo_siigo_shipping_taxes_id'),
					// TODO: use the order shipping tax even if the taxes are not enabled
					// 'value' => $order->get_shipping_tax(),
					'value' => 0.19 * $order->get_shipping_total(),
				),
			)
		));


		$local_order = array(
			'document' => array(
				'id' => get_option('wc_settings_woo_siigo_invoice_id'),
			),
			'date' => date('Y-m-d'),
			'customer' => array(
				'person_type' => $customer['person_type'],
				'id_type' => $customer['id_type'],
				'identification' => $customer['identification']
			),
			'seller' => get_option('wc_settings_woo_siigo_seller_id'),
			'items' => $items,
			'observations' => get_option('wc_settings_woo_siigo_observations'),
			'payments' => array(
				array(
					'id' => get_option('wc_settings_woo_siigo_payment_type_id'),
					'price' => $order->get_total(),
				)
			),
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
			'vat_responsible' => $order_meta['_billing_vat_responsible'][0],
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

	public function add_settings_tab($settings_tabs)
	{
		$settings_tabs['siigo_settings'] = __('Siigo Settings', 'vizapata_sw_integration');
		return $settings_tabs;
	}

	public function settings_tab()
	{
		woocommerce_admin_fields($this->get_settings());
	}

	public function update_settings()
	{
		woocommerce_update_options($this->get_settings());
	}

	private function get_settings()
	{
		$settings = array(
			'section_title' => array(
				'id'       => 'wc_settings_siigo_settings_section_title',
				'name'     => __('API connection', 'vizapata_sw_integration'),
				'type'     => 'title',
				'desc'     => __('Required data to connect with Siigo API. Please refer to your Siigo cloud documentation to get these parameters', 'vizapata_sw_integration'),
			),
			'api_url' => array(
				'id'   => 'wc_settings_woo_siigo_api_url',
				'name' => __('Siigo API URL', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The URL of the Siigo cloud', 'vizapata_sw_integration'),
				'desc_tip' => __('Please use the URL without the trailing slash', 'vizapata_sw_integration'),
				'placeholder'   => 'https://api.siigo.com',
			),
			'api_username' => array(
				'id'   => 'wc_settings_woo_siigo_username',
				'name' => __('Username', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The username used to connect with the Siigo cloud', 'vizapata_sw_integration'),
				'placeholder' => 'username@example.com',
			),
			'api_key' => array(
				'id'   => 'wc_settings_woo_siigo_api_key',
				'name' => __('API Key', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The API KEY used to connect with the Siigo cloud', 'vizapata_sw_integration'),
				'placeholder' => strtoupper(base64_encode(__('Put here your API key', 'vizapata_sw_integration'))) . '...',
			),
			'section_end' => array(
				'id' => 'wc_settings_siigo_settings_section_end',
				'type' => 'sectionend',
			),

			'billing_settings' => array(
				'id'       => 'wc_settings_siigo_settings_section_billing',
				'name'     => __('Billing settigns', 'vizapata_sw_integration'),
				'type'     => 'title',
				'desc'     => __('Billing parameters ussed to generate the invoices', 'vizapata_sw_integration'),
			),


			'invoice_id' => array(
				'id'   => 'wc_settings_woo_siigo_invoice_id',
				'name' => __('Document type ID', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The identifier of the document type to be used.', 'vizapata_sw_integration') . ' ' . __(' Example: 123', 'vizapata_sw_integration'),
				'desc_tip' => __('Please check in the Siigo cloud invoice document types', 'vizapata_sw_integration'),
				'placeholder'   => __('Document type ID', 'vizapata_sw_integration') . '...',
			),

			'taxes_id' => array(
				'id'   => 'wc_settings_woo_siigo_taxes_id',
				'name' => __('Taxes ID', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The identifier of the taxes aplicable for all products.', 'vizapata_sw_integration') . ' ' . __(' Example: 123', 'vizapata_sw_integration'),
				'placeholder'   => __('Taxes ID', 'vizapata_sw_integration') . '...',
			),

			'seller_id' => array(
				'id'   => 'wc_settings_woo_siigo_seller_id',
				'name' => __('Seller ID', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The ID for the seller associated to the orders.', 'vizapata_sw_integration') . ' ' . __(' Example: 123', 'vizapata_sw_integration'),
				'desc_tip' => __('This value is not the document ID. It is an id from Siigo Cloud', 'vizapata_sw_integration'),
				'placeholder'   => __('Seller ID', 'vizapata_sw_integration') . '...',
			),

			'warehouse_id' => array(
				'id'   => 'wc_settings_woo_siigo_warehouse_id',
				'name' => __('Warehouse ID', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The warehouse ID where the products will be shipped from. Please check in the Siigo cloud warehouse, if any', 'vizapata_sw_integration') . ' ' . __(' Example: 123', 'vizapata_sw_integration'),
				'desc_tip' => __('Use this only if you want to assign a warehouse to all orders', 'vizapata_sw_integration'),
				'placeholder'   => __('Warehouse ID', 'vizapata_sw_integration') . '...',
			),

			'payment_type_id' => array(
				'id'   => 'wc_settings_woo_siigo_payment_type_id',
				'name' => __('Payment Type ID', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The id to use for the payment type. Please check the Siigo cloud for the payment types', 'vizapata_sw_integration') . ' ' . __(' Example: 123', 'vizapata_sw_integration'),
				'desc_tip' => __('This value is the payment ID, not the code', 'vizapata_sw_integration'),
				'placeholder'   => __('Payment ID', 'vizapata_sw_integration') . '...',
			),

			'shipping_id' => array(
				'id'   => 'wc_settings_woo_siigo_shipping_id',
				'name' => __('Shipping ID', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The id to use for the shipping. Please check the Siigo cloud for the shipping methods', 'vizapata_sw_integration') . ' ' . __(' Example: 123', 'vizapata_sw_integration'),
				'placeholder'   => __('Shipping ID', 'vizapata_sw_integration') . '...',
			),

			'shipping_taxes_id' => array(
				'id'   => 'wc_settings_woo_siigo_shipping_taxes_id',
				'name' => __('Shipping taxes ID', 'vizapata_sw_integration'),
				'type' => 'text',
				'desc' => __('The id to use for the shipping taxes. Please check the Siigo cloud for the shipping taxes', 'vizapata_sw_integration') . ' ' . __(' Example: 123', 'vizapata_sw_integration'),
				'placeholder'   => __('Shipping taxes ID', 'vizapata_sw_integration') . '...',
			),

			'observations' => array(
				'id'   => 'wc_settings_woo_siigo_observations',
				'name' => __('Invoice observations', 'vizapata_sw_integration'),
				'type' => 'textarea',
				'desc' => __('Additional observations to include in generated invoices', 'vizapata_sw_integration'),
				'placeholder'   => __('Additional observations', 'vizapata_sw_integration') . '...',
			),

			'billing_section_end' => array(
				'id' => 'wc_settings_siigo_settings_section_billing_end',
				'type' => 'sectionend',
			),
		);
		return apply_filters('wc_settings_tab_siigo_settings', $settings);
	}
}
