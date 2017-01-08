<?php
session_start();
require_once '../config.php';
require_once 'logs.php';

header('Content-type: application/json; charset=utf-8');

    $metadata = json_decode(urldecode($_POST['metadata']), true);
    $access_token = $metadata['access_token'];

if ($metadata['debugging']==true) write_log("ADVANCED DEBUGGING: Channelback requested with Metadata ".$_POST['metadata'].", parent_id=".$_POST['parent_id'].", message=".$_POST['message']);
else write_log("Channelback requested");

$url = '';
$ch = curl_init();

$parent_id=$_POST['parent_id'];

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
