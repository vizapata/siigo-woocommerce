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
		$this->generate_electronic_invoice($order_id);
	}

	public function woocommerce_order_status_changed($order_id, $from, $to)
	{
		if ($to === 'completed') {
			$this->generate_electronic_invoice($order_id);
		}
	}

	public function generate_invoice_ajax()
	{
		check_ajax_referer('create-electronic-invoice', 'security');
		$order_id = absint($_POST['post_id']);
		$response = $this->generate_electronic_invoice($order_id);
		if (!$response['error']) {
			ob_start();
			$order = new Vizapata_sw_integration_Order();
			$post = get_post($order_id);
			$order->the_meta_box($post);
			$response['content'] = ob_get_clean();
		}
		wp_send_json($response);
	}

	public function generate_electronic_invoice($order_id)
	{
		$response = array(
			'error' => false,
			'message' => '',
		);

		if ($this->invoice_exists($order_id)) {
			$response['error'] = true;
			$response['message'] = __('This order already has an electronic invoice');
			return $response;
		};
		$siigo_proxy = new Vizapata_Siigo_Proxy();
		$order = wc_get_order($order_id);
		$local_customer = $this->build_customer($order);

		try {
			$siigo_proxy->authenticate();
			$remote_customer = $siigo_proxy->findCustomerByDocument($local_customer['identification']);
			if ($remote_customer === false) {
				$remote_customer = $siigo_proxy->createCustomer($local_customer);
			}
			$taxes = $siigo_proxy->get_taxes();
			$local_order = $this->build_order($order, $local_customer, $taxes);
			update_post_meta($order_id, '_siigo_customer_id', $remote_customer->id);
			$remote_order = $siigo_proxy->createInvoice($local_order);
			update_post_meta($order_id, '_siigo_invoice_id', $remote_order->id);
			update_post_meta($order_id, '_siigo_invoice_name', $remote_order->name);

			$response['message'] = sprintf(__('Invoice created with document number: %s', 'vizapata_sw_integration'), $remote_order->name);
			$order->add_order_note($response['message']);
		} catch (Exception $ex) {
			$response['error'] = true;
			$response['message'] = __('Error during invoice creation: ', 'vizapata_sw_integration') . $ex->getMessage();
			$order->add_order_note($response['message']);
		}
		return $response;
	}

	private function find_tax_by_id($taxes, $tax_id)
	{
		$tax = array_filter($taxes, function ($item) use ($tax_id) {
			return isset($item->id) && $item->id == $tax_id;
		});
		return array_values($tax)[0];
	}

	private function build_order($order, $customer, $taxes)
	{
		$items = array();
		$product_tax  = $this->find_tax_by_id($taxes, get_option('wc_settings_woo_siigo_taxes_id'));

		foreach ($order->get_items() as $item) {
			$product = $item->get_product();
			$new_item = array(
				'code' => $product->get_sku(),
				'description' => $product->get_name(),
				'price' => round($product->get_price() * 100 / (100 + $product_tax->percentage), 6),
				'quantity' => $item->get_quantity(),
				'warehouse' => get_option('wc_settings_woo_siigo_warehouse_id'),
				'taxes' => array(
					array(
						'id' => $product_tax->id,
					)
				),
			);
			array_push($items, $new_item);
		}
		$shipping_tax = $this->find_tax_by_id($taxes, get_option('wc_settings_woo_siigo_shipping_taxes_id'));

		array_push($items, array(
			'code' => get_option('wc_settings_woo_siigo_shipping_id'),
			'description' => $order->get_shipping_method(),
			'price' => round($order->get_shipping_total() * 100 / (100 + $shipping_tax->percentage), 6),
			'quantity' => 1,
			'taxes' => array(
				array(
					'id' => $shipping_tax->id,
				),
			)
		));


		$local_order = array(
			'document' => array(
				'id' => get_option('wc_settings_woo_siigo_invoice_id'),
			),
			'date' => date('Y-m-d'),
			'customer' => $customer,
			'seller' => get_option('wc_settings_woo_siigo_seller_id'),
			'items' => $items,
			'observations' => get_option('wc_settings_woo_siigo_observations'),
			'payments' => array(
				array(
					'id' => get_option('wc_settings_woo_siigo_payment_type_id'),
					'value' => $order->get_total(),
				)
			),
		);
		return $local_order;
	}

	private function build_customer($order)
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
			'first_name' => $order_meta['_billing_first_name'][0],
			'last_name' => $order_meta['_billing_last_name'][0],
			'email' => $order_meta['_billing_email'][0],
			'phone' => $billing_pone,
		);

		if ($is_person) {
			array_push($name, $order_meta['_billing_first_name'][0]);
			array_push($name, $order_meta['_billing_last_name'][0]);
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
			'vat_responsible' => boolval($order_meta['_billing_vat_responsible'][0]),
			'fiscal_responsibilities' => array(
				array("code" => "R-99-PN")
			),
			'address' => array(
				'address' => $order_meta['_billing_address_1'][0] . ' ' . $order_meta['_billing_address_2'][0],
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

	public function download_electronic_invoice()
	{
		if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'download-invoice') {
			check_admin_referer('_order' . $_GET['post']);

			$order = wc_get_order($_GET['post']);
			$is_paid = $order->is_paid();
			$invoice_id = get_post_meta($_GET['post'], '_siigo_invoice_id', true);
			$invoice_number = get_post_meta($_GET['post'], '_siigo_invoice_name', true);

			if ($is_paid && !empty($invoice_id)) {
				$siigo_proxy = new Vizapata_Siigo_Proxy();
				try {
					$siigo_proxy->authenticate();
					$invoice = base64_decode($siigo_proxy->generate_invoice_pdf($invoice_id));
					header('Content-Type: application/pdf');
					header('Cache-Control: public, must-revalidate, max-age=86400');
					header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+1 month')) . ' GMT'); // Date in the future
					header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
					header('Content-Disposition: inline; filename="' . $invoice_number . '.pdf"');
					echo $invoice;
					exit;
				} catch (Exception $ex) {
					wp_die(__('Error during invoice request: ', 'vizapata_sw_integration') . $ex->getMessage());
				}
			} else {
				wp_die(__('The order that you are looking for does not have any electronic invoice', 'vizapata_sw_integration'));
			}
		}
	}

	public function load_invoice_id($order_id)
	{
		return get_post_meta($order_id, '_siigo_invoice_id', true);
	}

	public function invoice_exists($order_id)
	{
		$invoice_id = $this->load_invoice_id($order_id);
		return $invoice_id !== false && !empty($invoice_id);
	}
}
