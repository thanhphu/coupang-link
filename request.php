<?php

function http_parse_query($query)
{
  $parameters = array();
  $queryParts = explode('&', $query);
  foreach ($queryParts as $queryPart) {
    $keyValue = explode('=', $queryPart, 2);
    if ($keyValue[0] == "itemId" || $keyValue[0] == "vendorItemId") {
      $parameters[$keyValue[0]] = $keyValue[1];
    }
  }
  return $parameters;
}

function build_url(array $parts)
{
  return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
    ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
    (isset($parts['user']) ? "{$parts['user']}" : '') .
    (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
    (isset($parts['user']) ? '@' : '') .
    (isset($parts['host']) ? "{$parts['host']}" : '') .
    (isset($parts['port']) ? ":{$parts['port']}" : '') .
    (isset($parts['path']) ? "{$parts['path']}" : '') .
    (isset($parts['query']) ? "?{$parts['query']}" : '') .
    (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
}

function expand_url($url) {
  $expanded_url = $url;
  if (strpos($url, "coupa.ng") !== false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $a = curl_exec($ch); // $a will contain all headers

    $expanded_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // This is what you need, it will return you the last effective URL

    $parts = parse_url($expanded_url);
    $expanded_url = build_url($parts);
  }
  return $expanded_url;
}

if ($_POST["urls"]) {
  $lines = explode("\n", $_POST['urls']);
  $quotedLines = array();
  foreach ($lines as &$line) {
    $line = expand_url(trim($line));
    $quotedLines[] = "\"${line}\"";
  }
  $coupangUrls = implode(",", $quotedLines);

  date_default_timezone_set("GMT+0");

  $datetime = date("ymd").'T'.date("His").'Z';
  $method = "POST";
  $path = "/v2/providers/affiliate_open_api/apis/openapi/v1/deeplink";

  $message = $datetime.$method.str_replace("?", "", $path);

  include "config.php";

  $algorithm = "HmacSHA256";
  $signature = hash_hmac('sha256', $message, $SECRET_KEY);
  $authorization  = "CEA algorithm=HmacSHA256, access-key=".$ACCESS_KEY.", signed-date=".$datetime.", signature=".$signature;

  $url = 'https://api-gateway.coupang.com'.$path;

  $strjson='
  {
    "coupangUrls": [
        ' . $coupangUrls . '
    ]
  }';

  $curl = curl_init();        
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:  application/json;charset=UTF-8", "Authorization:".$authorization));        
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $strjson);
  $result = curl_exec($curl);
  $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

  curl_close($curl);

  header('Content-Type: application/json');
  echo($result);
}
?>

