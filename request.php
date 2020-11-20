<?php
if ($_POST["urls"]) {
  $lines = explode("\n", $_POST['urls']);
  $quotedLines = array();
  foreach ($lines as &$line) {
    $line = trim($line);
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

