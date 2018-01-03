<?php

namespace Pem;

function curl_get($url, array $get = NULL, array $options = array())
{
  $defaults = array(
    CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get),
    CURLOPT_HEADER => 0,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_TIMEOUT => 15
  );

  $ch = curl_init();
  curl_setopt_array($ch, ($options + $defaults));
  if( ! $result = curl_exec($ch))
  {
    trigger_error(curl_error($ch));
  }
  curl_close($ch);
  return $result;
}

class Ghost
{
  const clientId = Config::GHOST_CLIENT;
  const clientSecret = Config::GHOST_SECRET;
  const ghostURL = Config::GHOST_ENDPOINT;

  public $response;

  public function __construct($endpoint, array $api_params = array())
  {
     $params = array_merge(
                array(
                      'client_id' => self::clientId,
                      'client_secret' => self::clientSecret
                     ),
                      $api_params
               );
     $this->response = json_decode(curl_get(self::ghostURL.$endpoint.'/', $params ));
  }
}


?>
