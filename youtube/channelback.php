<?php

require_once '../config.php';
$provider = require 'oauth_provider.php';
require_once 'logs.php';

header('Content-type: application/json; charset=utf-8');

    $metadata = json_decode(urldecode($_POST['metadata']), true);
    $refresh_token = $metadata['refresh_token'];

if ($metadata['debugging']==true) write_log("ADVANCED DEBUGGING: Channelback requested with Metadata ".$_POST['metadata'].", parent_id=".$_POST['parent_id'].", message=".$_POST['message']);
else write_log("Channelback requested");

    $provider = new League\OAuth2\Client\Provider\Google([
        'clientId' => $clientId,
        'clientSecret' => $clientSecret,
        'redirectUri'  => $redirectUri
    ]);

    $grant = new League\OAuth2\Client\Grant\RefreshToken();
    $token = $provider->getAccessToken($grant, ['refresh_token' => $refresh_token]);
    $_SESSION['token'] = serialize($token);
    $oauth_token = $token->getToken();
    $oauth_token_expires = $token->getExpires();

$url = 'https://www.googleapis.com/youtube/v3/comments?part=snippet&access_token='.$oauth_token;
$ch = curl_init();

$parent_id=$_POST['parent_id'];
//Changing external ID for reply to comment reply
if (strpos($parent_id,".")){
    $new_parent_id=explode(".",$parent_id);
    $parent_id=$new_parent_id[0];
}

$post_fields=array(
    "snippet"=>array(
        "parentId"=>$parent_id,
        "textOriginal"=>strip_tags($_POST['message'])
    )
);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-type: application/json'
));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
curl_setopt($ch,CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
$data = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$item = json_decode($data);
if ($metadata['debugging']==true) write_log("ADVANCED DEBUGGING: Channelback YouTube answer status ".$status);
else write_log("Channelback YouTube answer received");

http_response_code($status);

    $response_array = array(
    "external_id"=>$item->id,
    "allow_channelback"=>true
);

echo json_encode($response_array);

if ($metadata['debugging']==true) write_log("ADVANCED DEBUGGING: Channelback response sent:".json_encode($response_array));
else write_log("Channelback response sent");
