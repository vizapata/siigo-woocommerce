<?php

class Vizapata_sw_integration_Settings
{
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
    $api_settings = array(
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
      )
    );

    $siigo_settings = array(
      'siigo_api_results' => array(
        'id'       => 'wc_settings_siigo_settings_api_results',
        'name'     => __('Billing settings', 'vizapata_sw_integration'),
        'type'     => 'title',
        'desc'     => __('Please complete the API connection settings before continue', 'vizapata_sw_integration'),
      ),
    );

    $api_settings['section_title']['id'] = 'wc_settings_siigo_settings_section_title';

    if (get_option('wc_settings_woo_siigo_api_url') && get_option('wc_settings_woo_siigo_username') && get_option('wc_settings_woo_siigo_api_key')) {
      try {
        $siigo_proxy = new Vizapata_Siigo_Proxy();
        $siigo_proxy->authenticate();

        $list = $siigo_proxy->get_users();
        $user_setting = array(
          'id'   => 'wc_settings_woo_siigo_seller_id',
          'name' => __('Seller', 'vizapata_sw_integration'),
          'type' => 'select',
          'desc' => __('The seller associated to the orders.', 'vizapata_sw_integration'),
          'placeholder'   => __('Seller', 'vizapata_sw_integration') . '...',
          'options' => array()
        );
        foreach ($list as $item) {
          if ($item->active) {
            $user_setting['options'][$item->id] = "$item->first_name $item->last_name";
          }
        }

        $list = $siigo_proxy->get_warehouses();
        $warehouse_setting = array(
          'id'   => 'wc_settings_woo_siigo_warehouse_id',
          'name' => __('Warehouse', 'vizapata_sw_integration'),
          'type' => 'select',
          'desc' => __('The warehouse where the products will be shipped from', 'vizapata_sw_integration'),
          'placeholder'   => __('Warehouse', 'vizapata_sw_integration') . '...',
        );
        foreach ($list as $item) {
          if ($item->active) {
            $warehouse_setting['options'][$item->id] = $item->name;
          }
        }

        $list = $siigo_proxy->get_document_types();
        $invoice_setting = array(
          'id'   => 'wc_settings_woo_siigo_invoice_id',
          'name' => __('Document type', 'vizapata_sw_integration'),
          'type' => 'select',
          'desc' => __('The document type that will be generated', 'vizapata_sw_integration'),
          'placeholder'   => __('Document type', 'vizapata_sw_integration') . '...',
          'options'   => array(),
        );
        foreach ($list as $item) {
          if ($item->active) {
            $invoice_setting['options'][$item->id] = $item->name;
          }
        }

        $list = $siigo_proxy->get_payment_types();
        update_option('wc_settings_woo_siigo_payment_type_list', $list);
        $payment_types_setting =  array(
          'id'   => 'wc_settings_woo_siigo_payment_type_id',
          'name' => __('Payment type', 'vizapata_sw_integration'),
          'type' => 'select',
          'desc' => __('The payment type that will be associated to the invoices', 'vizapata_sw_integration'),
          'placeholder'   => __('Payment type', 'vizapata_sw_integration') . '...',
          'options'   => array(),
        );
        foreach ($list as $item) {
          $payment_types_setting['options'][$item->id] = $item->name;
        }

        $list = $siigo_proxy->get_cost_centers();
        $cost_center_setting =  array(
          'id'   => 'wc_settings_woo_siigo_cost_center_id',
          'name' => __('Cost center', 'vizapata_sw_integration'),
          'type' => 'select',
          'desc' => __('The cost center that will be applied to invoices', 'vizapata_sw_integration'),
          'placeholder'   => __('Cost center', 'vizapata_sw_integration') . '...',
          'options'   => array(),
        );
        foreach ($list as $item) {
          $cost_center_setting['options'][$item->id] = $item->name;
        }

        $list = $siigo_proxy->get_products();
        $shipping_setting =  array(
          'id'   => 'wc_settings_woo_siigo_shipping_id',
          'name' => __('Shipping method', 'vizapata_sw_integration'),
          'type' => 'select',
          'desc' => __('The product that will be act as shipping method. It will be included in all invoices', 'vizapata_sw_integration'),
          'placeholder'   => __('Shipping method', 'vizapata_sw_integration') . '...',
          'options'   => array(),
        );
        foreach ($list as $item) {
          if ($item->active) {
            $shipping_setting['options'][$item->code] = $item->name;
          }
        }

        $list = $siigo_proxy->get_taxes();
        $product_taxes_setting = array(
          'id'   => 'wc_settings_woo_siigo_taxes_id',
          'name' => __('Product tax', 'vizapata_sw_integration'),
          'type' => 'select',
          'desc' => __('The taxes aplicable for all products.', 'vizapata_sw_integration'),
          'placeholder'   => __('Select tax', 'vizapata_sw_integration') . '...',
          'options'   => array(),
        );
        $shipping_taxes_setting = array(
          'id'   => 'wc_settings_woo_siigo_shipping_taxes_id',
          'name' => __('Shipping taxes', 'vizapata_sw_integration'),
          'type' => 'select',
          'desc' => __('The taxes that will be apply to the shipping method', 'vizapata_sw_integration'),
          'placeholder'   => __('Shipping taxes', 'vizapata_sw_integration') . '...',
          'options'   => array(),
        );
        foreach ($list as $item) {
          if ($item->active) {
            $product_taxes_setting['options'][$item->id] = $item->name;
            $shipping_taxes_setting['options'][$item->id] = $item->name;
          }
        }

        $siigo_settings = array(
          'billing_settings' => array(
            'id'       => 'wc_settings_siigo_settings_section_billing',
            'name'     => __('Billing settings', 'vizapata_sw_integration'),
            'type'     => 'title',
            'desc'     => __('Billing parameters used to generate the invoices', 'vizapata_sw_integration'),
          ),

          'invoice_id' => $invoice_setting,
          'taxes_id' => $product_taxes_setting,
          'cost_center_id' => $cost_center_setting,
          'seller_id' => $user_setting,
          'warehouse_id' => $warehouse_setting,
          'payment_type_id' => $payment_types_setting,
          'shipping_id' => $shipping_setting,
          'shipping_taxes_id' => $shipping_taxes_setting,

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
      } catch (Exception $ex) {
        $siigo_settings = array(
          'siigo_api_results' => array(
            'id'       => 'wc_settings_siigo_settings_api_results',
            'name'     => __('Connection error', 'vizapata_sw_integration'),
            'type'     => 'title',
            'desc'     => __('Please check that the URL, username and API key are correct. Save the changes', 'vizapata_sw_integration'),
          ),
        );

        $api_settings['section_title']['id'] = 'wc_settings_siigo_settings_section_title_error';
        $api_settings['section_title']['desc'] = __('Error connecting to Siigo API. Please verify the connection settings before continue', 'vizapata_sw_integration');
      }
    }

    return apply_filters('wc_settings_tab_siigo_settings', array_merge($api_settings, $siigo_settings));
  }

  public function http_request_timeout($timeout, $url)
  {
    $api_url = get_option('wc_settings_woo_siigo_api_url');
    if ($api_url && strpos($url, $api_url) !== false) {
      $timeout = 15;
    }
    return $timeout;
  }
}
