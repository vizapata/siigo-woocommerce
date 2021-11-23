<?php

class Vizapata_Siigo_Proxy
{
  private const ACCESS_TOKEN_LIFESPAN_TRESHOLD = 5 * 1000;
  private $authInfo;
  private $apiUrls;

  public function __construct()
  {
    $baseApiUrl = get_option('wc_settings_woo_siigo_api_url');
    $this->apiUrls = array(
      'auth' => $baseApiUrl . '/auth',
      'customers' => $baseApiUrl . '/v1/customers',
      'products' => $baseApiUrl . '/v1/products',
      'invoices' => $baseApiUrl . '/v1/invoices',
      'invoice_pdf' => $baseApiUrl . '/v1/invoices/[INVOICE_ID]/pdf',
      'users' => $baseApiUrl . '/v1/users',
      'warehouses' => $baseApiUrl . '/v1/warehouses',
      'document-types' => $baseApiUrl . '/v1/document-types',
      'payment-types' => $baseApiUrl . '/v1/payment-types',
      'taxes' => $baseApiUrl . '/v1/taxes',
    );
  }

  public function authenticate()
  {
    $request = array(
      'headers' => array(
        'content-type' => 'application/json; charset=utf-8'
      ),
      'body' => json_encode(array(
        'username' => get_option('wc_settings_woo_siigo_username'),
        'access_key' => get_option('wc_settings_woo_siigo_api_key'),
      )),
    );

    $response = wp_safe_remote_post($this->apiUrls['auth'], $request);

    if ($this->isResponseOK($response)) {
      $this->authInfo = json_decode($response['body']);
      $this->authInfo->expires = time() + self::ACCESS_TOKEN_LIFESPAN_TRESHOLD + $this->authInfo->expires_on;
    } else {
      $error = is_wp_error($response) ? $response->get_error_message() : 'Error while trying to authenticate against remote server';
      throw new Exception('Unable to get access token: ' . $error);
    }
  }

  public function findCustomerByDocument($documentNumber)
  {
    if (!$this->isAuthenticated()) throw new Exception('Not authenticated');
    $request = array(
      'headers' => array(
        'content-type' => 'application/json; charset=utf-8',
        'Authorization' => 'Bearer ' . $this->authInfo->access_token
      )
    );

    $response = wp_safe_remote_get($this->apiUrls['customers'] . '?identification=' . $documentNumber, $request);
    if ($this->isResponseOK($response)) {
      $contents = json_decode($response['body']);
      if ($contents->pagination->total_results == 1) return $contents->results[0];
      if ($contents->pagination->total_results == 0) return false;
      throw new Exception('Multiple customers found');
    }
    $error = 'Error trying to find the customer details';
    if (is_wp_error($response)) $error = $response->get_error_message();
    else if ($this->isResponseError($response)) $error = $this->getResponseErrorMessage($response);
    throw new Exception($error);
  }

  public function createCustomer($customer)
  {
    if (!$this->isAuthenticated()) throw new Exception('Not authenticated');
    $request = array(
      'headers' => array(
        'content-type' => 'application/json; charset=utf-8',
        'Authorization' => 'Bearer ' . $this->authInfo->access_token
      ),
      'body' => json_encode($customer),
    );

    $response = wp_safe_remote_post($this->apiUrls['customers'], $request);
    if ($this->isResponseOK($response)) {
      return json_decode($response['body']);
    }
    $error = 'Error trying to create the new customer';
    if (is_wp_error($response)) $error = $response->get_error_message();
    else if ($this->isResponseError($response)) $error = $this->getResponseErrorMessage($response);
    throw new Exception($error);
  }

  public function createInvoice($order)
  {
    if (!$this->isAuthenticated()) throw new Exception('Not authenticated');
    $request = array(
      'headers' => array(
        'content-type' => 'application/json; charset=utf-8',
        'Authorization' => 'Bearer ' . $this->authInfo->access_token
      ),
      'body' => json_encode($order),
    );

    $response = wp_safe_remote_post($this->apiUrls['invoices'], $request);
    if ($this->isResponseOK($response)) {
      return json_decode($response['body']);
    }
    $error = 'Error trying to create the invoice';
    if (is_wp_error($response)) $error = $response->get_error_message();
    else if ($this->isResponseError($response)) $error = $this->getResponseErrorMessage($response);
    throw new Exception($error);
  }

  private function get_list($api, $queryParams = array(), $pathVariables = array())
  {
    if (!$this->isAuthenticated()) throw new Exception('Not authenticated');
    $request = array(
      'headers' => array(
        'content-type' => 'application/json; charset=utf-8',
        'Authorization' => 'Bearer ' . $this->authInfo->access_token
      )
    );
    $url = $this->apiUrls[$api];
    foreach ($pathVariables as $param => $value) {
      $url = str_replace("[$param]", $value, $url);
    }
    $response = wp_safe_remote_get(add_query_arg($queryParams, $url), $request);
    if ($this->isResponseOK($response)) {
      return json_decode($response['body']);
    }
    $error = 'Error trying to get the requested list';
    if (is_wp_error($response)) $error = $response->get_error_message();
    else if ($this->isResponseError($response)) $error = $this->getResponseErrorMessage($response);
    throw new Exception($error);
  }

  public function generate_invoice_pdf($invoice_id)
  {
    $invoice_data = $this->get_list('invoice_pdf', array(), array( 'INVOICE_ID' => $invoice_id));
    return $invoice_data->base64;
  }

  private function get_full_page_list($api)
  {
    return $this->get_list($api)->results;
  }
  public function get_users()
  {
    return $this->get_full_page_list('users');
  }
  public function get_products()
  {
    return $this->get_full_page_list('products');
  }
  public function get_warehouses()
  {
    return $this->get_list('warehouses');
  }
  public function get_document_types()
  {
    return $this->get_list('document-types', array('type' => 'FV'));
  }
  public function get_payment_types()
  {
    return $this->get_list('payment-types', array('document_type' => 'FV'));
  }
  public function get_taxes()
  {
    return $this->get_list('taxes');
  }

  private function isAuthenticated()
  {
    return $this->authInfo != null &&
      $this->authInfo->access_token != null &&
      $this->authInfo->expires > time();
  }

  private function isResponseOK($response)
  {
    return !is_wp_error($response) && isset($response['response']) && isset($response['response']['code']) && $response['response']['code'] >= 200 && $response['response']['code'] < 300;
  }

  private function isResponseError($response)
  {
    return !is_wp_error($response) && isset($response['response']) && isset($response['body']) && isset($response['response']['code']) && $response['response']['code'] >= 400;
  }

  private function getResponseErrorMessage($response)
  {
    $error = '';
    $body = json_decode($response['body'], true);
    if (isset($body['Errors'])) {
      foreach ($body['Errors'] as $response_error) {
        $error .= $response_error['Message'] . ', ';
      }
    }
    return $error;
  }
}
