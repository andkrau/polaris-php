<?php

$base = 'https://SUBDOMAIN-HERE.polarislibrary.com/papiservice/REST';
$key = 'API-KEY-HERE';
$id = 'API-ID-HERE';

  function search($term, $limit=null) {
    $max = 100;
    if (strlen($limit)) {
      $max = 200;
    }
    $url = '/public/v1/1033/100/1/search/bibs/keyword/kw?bibsperpage=' . $max . '&q=';
    $method = 'GET';
    $query = rawurlencode($term);
    if (strlen($limit) > 0) {
      $query = $query . '&limit=' . $limit;
    }
    echo $url . $query;
    $result = requestPolaris($url, $method, $query);
    return $result;
  }

  function getInfo($info) {
    $url = '/public/v1/1033/100/1/' . $info;
    $method = 'GET';
    $result = requestPolaris($url, $method);
    return $result;
  }

  function authenticate($barcode, $pass) {
    $url = '/public/v1/1033/100/1/authenticator/patron';
    $method = 'POST';
    $body = json_encode(array( 'Barcode' => $barcode, 'Password' => $pass ));
    $result = requestPolaris($url, $method, $body);
    return json_encode($result);
  }

  function requestPolaris($url, $method, $request=null) {
    global $base, $id, $key;
    $api = $base;
    $accessID = $id;
    $accessKey = $key;
    $url = $api . $url;

    if ($method == 'GET') {
      $url = $url . $request;
    }

    $date = gmdate('r');
    $concat = $method . $url . $date;
    $signature = base64_encode(hash_hmac('sha1', $concat, $accessKey, true));

    $headers = array(
      "PolarisDate: ". $date,
      "Authorization: PWS " . $accessID . ":" . $signature,
      "Accept: application/json"
    );

    $result = null;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    if ($method == 'POST') {
      array_push($headers, "Content-Type: application/json","Content-Length: " . strlen($request));
      curl_setopt($curl, CURLOPT_POST, TRUE);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($curl);
    $code = curl_getinfo($curl , CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($code != 200) {
      $result = '{"PAPIErrorCode":-' . $code . '}';
    }
    return json_decode($result);
  }

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST');

if(isset($_GET['q']) || isset($_GET['limit'])) {
  $items = search($_GET['q'], rawurlencode($_GET['limit']));
  echo '<br><br>' . 'Matches: ' . count($items->BibSearchRows) . '<br><br>';
  $title = 'Search Results';
  if (isset($_GET['title'])) {
    $title = $_GET['title'];
  }
  foreach ($items->BibSearchRows as $row) {
    echo 'Title: ' . $row->Title . "<br>";
    echo 'Description: ' . $row->Description . "<br>";
  }
}
?>