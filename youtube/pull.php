<?php
/**
 * Created by PhpStorm.
 * User: mikhailvink
 * Date: 05/09/16
 * Time: 23:55
 */

require_once '../config.php';
$provider = require 'oauth_provider.php';
require_once 'logs.php';
header('Content-type: application/json; charset=utf-8');

if (!isset($_POST['metadata'])) {
        $_POST['metadata']=$globalConfig['TestMetaDataYT'];
        $_POST['state']=$globalConfig['TestStateYT'];
}

$metadata = json_decode(urldecode($_POST['metadata']), true);
$state = json_decode(urldecode($_POST['state']), true);
$channel = $metadata['channel'];
$youtube_type = $metadata['youtube_type'];
$refresh_token = $metadata['refresh_token'];
$oauth_token = $state['oauth_token'];
$oauth_token_expires = $state['oauth_token_expires'];

if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Pull has been requested. State: " . $_POST['state'] . ", Metadata: " . $_POST['metadata']);
else write_log("Pull has been requested.");

if ($oauth_token_expires - time() < 180) {
    $provider = new League\OAuth2\Client\Provider\Google([
        'clientId' => $clientId,
        'clientSecret' => $clientSecret,
        'redirectUri' => $redirectUri
    ]);

    $grant = new League\OAuth2\Client\Grant\RefreshToken();
    try {
        $token = $provider->getAccessToken($grant, ['refresh_token' => $refresh_token]);
        $_SESSION['token'] = serialize($token);
        $oauth_token = $token->getToken();
        $oauth_token_expires = $token->getExpires();
    } catch (\Exception $e) {
        write_log(sprintf("cannot get access token: %s", $e->getMessage()));
    }
}

//$state = json_decode($_POST['state'], true)['last_comment_timestamp'];

$state_compare = strtotime($state['last_comment_timestamp']);

if (rand(0,3)==1) {
    $url = 'https://www.googleapis.com/youtube/v3/commentThreads?part=snippet%2Creplies&allThreadsRelatedToChannelId=' . $channel . '&maxResults=100&order=time&access_token=' . $oauth_token;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
    $data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $comments = json_decode($data);
//print_r($comments);
    $pull_array = array();
    $pull_array_temp = array();
    if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Got response from YouTube, status " . $status);
    else write_log("Got response from YouTube");
//http_response_code($status);

    $last_comment_timestamp = "";
    foreach (array_reverse($comments->items) as $item) {
        if (strtotime($item->snippet->topLevelComment->snippet->publishedAt) > $state_compare) {
            /*        echo strtotime($item->snippet->topLevelComment->snippet->publishedAt).">".$state_compare."
                    ";*/
            if ($youtube_type == "full" and $item->snippet->canReply == "1") {
                $allow_channelback = true;
            } else {
                $allow_channelback = false;
            }

            if (strip_tags(html_entity_decode($item->snippet->topLevelComment->snippet->textDisplay, ENT_QUOTES))=="")
                $item->snippet->topLevelComment->snippet->textDisplay=" ";


            $temp_array = array(
                "external_id" => $item->id,
                "message" => strip_tags(html_entity_decode($item->snippet->topLevelComment->snippet->textDisplay, ENT_QUOTES)),
                "html_message" => $item->snippet->topLevelComment->snippet->textDisplay,
                "created_at" => $item->snippet->topLevelComment->snippet->publishedAt,
                "author" => array(
                    "external_id" => $item->snippet->topLevelComment->snippet->authorChannelId->value,
                    "name" => $item->snippet->topLevelComment->snippet->authorDisplayName,
                    "image_url" => $item->snippet->topLevelComment->snippet->authorProfileImageUrl
                ),
                "allow_channelback" => $allow_channelback
            );
            array_push($pull_array_temp, $temp_array);

            $last_comment_timestamp = $item->snippet->topLevelComment->snippet->publishedAt;
        }

        if ($item->snippet->totalReplyCount > 0) {
            foreach ($item->replies->comments as $reply) {
                if (strtotime($reply->snippet->publishedAt) > $state_compare) {
                    if ($youtube_type == "full" and $item->snippet->canReply == "1") {
                        $allow_channelback = true;
                    } else {
                        $allow_channelback = false;
                    }

                    if (strip_tags(html_entity_decode($reply->snippet->textDisplay, ENT_QUOTES))=="")
                        $reply->snippet->textDisplay=" ";

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
        }
    }
}
if ($last_comment_timestamp == '') $last_comment_timestamp = $state['last_comment_timestamp'];
$pull_array['external_resources'] = $pull_array_temp;
if (is_null($pull_array['external_resources'])) $pull_array['external_resources']=array();
$pull_array['state'] = urlencode(json_encode(array(
    "last_comment_timestamp" => (string)$last_comment_timestamp,
    "oauth_token" => (string)$oauth_token,
    "oauth_token_expires" => (string)$oauth_token_expires
)));

echo json_encode($pull_array);
if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Pull has been returned: " . json_encode($pull_array));
else write_log("Pull has been returned");
