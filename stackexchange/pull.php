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

}

$metadata = json_decode(urldecode($_POST['metadata']), true);
$state = json_decode(urldecode($_POST['state']), true);
$site = $metadata['site'];
$tag = $metadata['tag'];
$stackexchange_type = $metadata['stackexchange_type'];
$access_token = $metadata['access_token'];

if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Pull has been requested. State: " . $_POST['state'] . ", Metadata: " . $_POST['metadata']);
else write_log("Pull has been requested.");

//$state = json_decode($_POST['state'], true)['last_comment_timestamp'];

$state_compare = strtotime($state['last_comment_timestamp']);

$url = 'https://api.stackexchange.com/2.2/questions?pagesize=30&order=desc&sort=activity&tagged='.$tag.'&site='.$site.'&filter=!)Rw3Me(KDfK7W6QoB49q8GC*&access_token='.$access_token.'&key='.$globalConfig['SEAPIKey'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
$data = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Got response from Stack Exchange, status " . $status);
else write_log("Got response from Stack Exchange");

$questions= json_decode($data);
$pull_array = array();
$pull_array_temp = array();
header('Content-type: application/json; charset=utf-8');

foreach (array_reverse($questions->items) as $item) {
    if ($item->last_activity_date > $state_compare) {

        if ($stackexchange_type == "full") {
            $allow_channelback = false;
        } else {
            $allow_channelback = false;
        }

        $external_id=(string)$item->question_id;
        //$external_id=(string)rand(1,10000000);

        $temp_array = array(
            "external_id" => $site . "_" . $external_id,
            "message" => strip_tags($item->body_markdown),
            "html_message" => $item->body,
            "created_at" => date("Y-m-d\TH:i:s\Z", $item->creation_date),
            "author" => array(
                "external_id" => (string)$item->owner->user_id,
                "name" => html_entity_decode($item->owner->display_name, ENT_QUOTES),
                "image_url" => $item->owner->profile_image
            ),
            "fields" => array(
                array(
                    "id" => "subject",
                    "value" => $item->title
                ),
                array(
                    "id" => "type",
                    "value" => "question"
                )
            ),
            "allow_channelback" => $allow_channelback
        );
        array_push($pull_array_temp, $temp_array);

        foreach ($item->answers as $answer){
            $temp_array = array(
                "external_id" => $site."_".(string)$answer->answer_id,
                "message" => strip_tags($answer->body_markdown),
                "html_message" => $answer->body,
                "created_at" => date("Y-m-d\TH:i:s\Z", $answer->creation_date),
                "parent_id" => $site."_".$external_id,
                "author" => array(
                    "external_id" => (string)$answer->owner->user_id,
                    "name" => html_entity_decode($answer->owner->display_name,ENT_QUOTES),
                    "image_url" => $answer->owner->profile_image
                ),
                "allow_channelback" => $allow_channelback
            );
            array_push($pull_array_temp, $temp_array);
        }

        $last_comment_timestamp = $item->last_activity_date;
    }


/*
    if ($item->snippet->totalReplyCount > 0) {
        foreach ($item->replies->comments as $reply) {
            if (strtotime($reply->snippet->publishedAt) > $state_compare) {
                if ($youtube_type == "full" and $item->snippet->canReply == "1") {
                    $allow_channelback = true;
                } else {
                    $allow_channelback = false;
                }

                $temp_array = array(
                    "external_id" => $reply->id,
                    "message" => strip_tags(html_entity_decode($reply->snippet->textDisplay, ENT_QUOTES)),
                    "html_message" => $reply->snippet->textDisplay,
                    "created_at" => $reply->snippet->publishedAt,
                    "parent_id" => $reply->snippet->parentId,
                    "author" => array(
                        "external_id" => $reply->snippet->authorChannelId->value,
                        "name" => $reply->snippet->authorDisplayName,
                        "image_url" => $reply->snippet->authorProfileImageUrl
                    ),
                    "allow_channelback" => $allow_channelback
                );
                array_push($pull_array_temp, $temp_array);
                $last_comment_timestamp = $item->snippet->topLevelComment->snippet->publishedAt;
            }
        }
    }*/



}

if ($last_comment_timestamp == '') $last_comment_timestamp = $state['last_comment_timestamp'];
$pull_array['external_resources'] = $pull_array_temp;
$pull_array['state'] = urlencode(json_encode(array(
    "last_comment_timestamp" => (string)$last_comment_timestamp
)));
echo json_encode($pull_array);
if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Pull has been returned: " . json_encode($pull_array));
else write_log("Pull has been returned");

