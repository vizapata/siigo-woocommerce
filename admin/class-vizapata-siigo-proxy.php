<?php

class Vizapata_Siigo_Proxy
{
  private const ACCESS_TOKEN_LIFESPAN_TRESHOLD = 5 * 1000;
  private $authInfo;
  private $apiUrls;

  public function __construct()
  {
    $baseApiUrl = get_option('vizapata_sw_integration_siigo_api_url');
    $this->apiUrls = array(
      'auth' => $baseApiUrl . '/auth',
      'customers' => $baseApiUrl . '/v1/customers',
      'products' => $baseApiUrl . '/v1/products',
      'invoices' => $baseApiUrl . '/v1/invoices',
    );
  }

  public function authenticate()
  {
    $request = array(
      'headers' => array(
        'content-type' => 'application/json; charset=utf-8'
      ),
      'body' => json_encode(array(
        'username' => get_option('vizapata_sw_integration_siigo_username'),
        'access_key' => get_option('vizapata_sw_integration_siigo_apikey'),
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
      if ($contents->pagination->total_results == 1) return $contents->results;
      if ($contents->pagination->total_results == 0) return false;
      throw new Exception('Multiple customers found');
    }
    throw new Exception('Error trying to find the customer details');
  }

  private function isAuthenticated()
  {
    return $this->authInfo != null &&
      $this->authInfo->access_token != null &&
      $this->authInfo->expires > time();
  }

  private function isResponseOK($response)
  {
    return !is_wp_error($response) && isset($response['response']) && isset($response['response']['code']) && $response['response']['code'] === 200;
  }
}
