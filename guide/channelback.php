<?php

require_once '../config.php';
require_once 'logs.php';

header('Content-type: application/json; charset=utf-8');

    $metadata = json_decode(urldecode($_POST['metadata']), true);

if ($metadata['debugging']==true) write_log("ADVANCED DEBUGGING: Channelback requested with Metadata ".$_POST['metadata'].", parent_id=".$_POST['parent_id'].", message=".$_POST['message']);
else write_log("Channelback requested");

if ($metadata['content_type'] == 'community') {
    $parent_id = explode("/", str_replace("https://" . $metadata['hc_domain'] . "/hc/en-us/community/posts/", "", $_POST['parent_id']))[0];

    $url = 'https://' . $metadata['hc_domain'] . '/api/v2/community/posts/' . $parent_id . '/comments.json';
    $ch = curl_init();

    $post_fields = array(
        "comment" => array(
            "body" => $_POST['message']//,
            //"author_id"=>""
        )
    );
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json',
        'Authorization: Basic ' . base64_encode($metadata['token_username'] . ":" . $metadata['token_api'])
    ));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
    $data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $item = json_decode($data);

    if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Channelback Guide answer status " . $status);
    else write_log("Channelback Guide answer received");

    http_response_code($status);

    if ($metadata['content_type'] == 'community') $external_id = "https://" . $metadata['hc_domain'] . "/hc/en-us/community/posts/" . $parent_id . "/comments/" . $item->comment->id;
    else $external_id = 'NOT_YET_IMPLEMENTED';

    $response_array = array(
        "external_id" => $external_id,
        "allow_channelback" => true
    );
} elseif ($metadata['content_type'] == 'kb') {  
    $parent_id = explode("/", str_replace("https://" . $metadata['hc_domain'] . "/hc/".$metadata['hc_locale']."/articles/", "", $_POST['parent_id']))[0];

    $url = 'https://' . $metadata['hc_domain'] . '/api/v2/help_center/articles/' . $parent_id . '/comments.json';
    $ch = curl_init();

    $post_fields = array(
        "comment" => array(
            "body" => $_POST['message'],
            "locale" => $metadata['hc_locale']//,
            //"author_id"=>""
        )
    );
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json',
        'Authorization: Basic ' . base64_encode($metadata['token_username'] . ":" . $metadata['token_api'])
    ));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
    $data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $item = json_decode($data);

    if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Channelback Guide answer status " . $status);
    else write_log("Channelback Guide answer received");

    http_response_code($status);

    $external_id = "https://" . $metadata['hc_domain'] . "/hc/".$metadata['hc_locale']."/articles/" . $parent_id . "/comments/" . $item->comment->id;

    $response_array = array(
        "external_id" => $external_id,
        "allow_channelback" => true
    );
}

echo json_encode($response_array);

if ($metadata['debugging']==true) write_log("ADVANCED DEBUGGING: Channelback response sent:".json_encode($response_array));
else write_log("Channelback response sent");
