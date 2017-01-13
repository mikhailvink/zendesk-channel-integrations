<?php
/**
 * Created by PhpStorm.
 * User: mikhailvink
 * Date: 08/01/2017
 * Time: 18:49
 */
require_once '../config.php';

$result=array();

for ($i=1;$i<=4;$i++) {
    $url = 'https://api.stackexchange.com/2.2/sites?pagesize=100&page='.$i.'&key=' . $globalConfig['SEAPIKey'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    $data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    foreach (json_decode($data)->items as $k) {
        array_push($result, array('name' => $k->name, 'api_site_parameter' => $k->api_site_parameter, 'site_url' => $k->site_url));
    }
}

$fp = fopen('./stackexchange_sites.json', 'w');
fwrite($fp, json_encode($result));
fclose($fp);
