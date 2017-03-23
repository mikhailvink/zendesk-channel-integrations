<?php
/**
 * Created by PhpStorm.
 * User: mikhailvink
 * Date: 05/09/16
 * Time: 23:55
 */
session_start();
require_once '../config.php';
require_once 'logs.php';
header('Content-type: application/json; charset=utf-8');

if (!isset($_POST['metadata'])) {
    $_POST['metadata'] = $globalConfig['TestMetaDataDH'];
    $_POST['state'] = $globalConfig['TestStateDH'];
}

$metadata = json_decode(urldecode($_POST['metadata']), true);
$state = json_decode(urldecode($_POST['state']), true);
$repository = $metadata['repository'];

if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Pull has been requested. State: " . $_POST['state'] . ", Metadata: " . $_POST['metadata']);
else write_log("Pull has been requested.");

//$state = json_decode($_POST['state'], true)['last_comment_timestamp'];

$state_compare = $state['last_comment_timestamp'];

$url = 'https://registry.hub.docker.com/v2/repositories/' . $repository . '/comments/?page=1';
$pull_array = array();
$pull_array_temp = array();
header('Content-type: application/json; charset=utf-8');
while (!empty($url)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
    $data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Got response from Docker Hub, status " . $status);
    else write_log("Got response from Docker Hub");

    $comments = json_decode($data);
    if ($state['last_comment_timestamp'] == '0') $url = $comments->next;
    else $url = '';

    foreach (array_reverse($comments->results) as $item) {
        if ($item->created_on > $state_compare) {

            //$external_id = (string)rand(1, 10000000);

            $temp_array = array(
                "external_id" => $repository . "?comment_id=" . $item->id,
                "message" => strip_tags($item->comment),
                "created_at" => $item->created_on,
                "author" => array(
                    "external_id" => (string)$item->user,
                    "name" => html_entity_decode($item->user, ENT_QUOTES)
                ),
                "allow_channelback" => false
            );
            array_push($pull_array_temp, $temp_array);

            $last_comment_timestamp = $item->created_on;
        }
    }
}

if ($last_comment_timestamp == '') $last_comment_timestamp = $state['last_comment_timestamp'];
$pull_array['external_resources'] = $pull_array_temp;
$pull_array['state'] = urlencode(json_encode(array(
    "last_comment_timestamp" => (string)$last_comment_timestamp
)));
echo json_encode($pull_array);
if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Pull has been returned: " . json_encode($pull_array));
else write_log("Pull has been returned");

