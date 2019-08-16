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
    $_POST['metadata'] = $globalConfig['TestMetaDataGD'];
    $_POST['state'] = $globalConfig['TestStateGD'];
};

$metadata = json_decode(urldecode($_POST['metadata']), true);
$state = json_decode(urldecode($_POST['state']), true);
//$add_external_id = (string)"?salt=".rand(1, 10000000);
$add_external_id = "";

if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Pull has been requested. State: " . $_POST['state'] . ", Metadata: " . $_POST['metadata']);
else write_log("Pull has been requested.");

$state_compare = $state['last_comment_timestamp'];

if ($metadata['content_type'] == 'community') {
    $url = 'https://' . $metadata['hc_domain'] . '/api/v2/community/topics/' . $metadata['topic_section'] . '/posts.json?sort_by=recent_activity&include=users';
    $pull_array = array();
    $pull_array_temp = array();
    header('Content-type: application/json; charset=utf-8');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
    curl_setopt($ch, CURLOPT_USERPWD, $metadata['token_username'] . ':' . $metadata['token_api']);
    $data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Got response from Zendesk, status " . $status);
    else write_log("Got response from Zendesk");

    $posts = json_decode($data);

    if ($metadata['channelback']=='true') $channelback=true;
    else $channelback=false;

    foreach (array_reverse($posts->posts) as $item) {
        if ($item->updated_at > $state_compare) {
            $url_comments = 'https://' . $metadata['hc_domain'] . '/api/v2/community/posts/' . $item->id . '/comments.json?include=users';
            $ch_comments = curl_init();
            curl_setopt($ch_comments, CURLOPT_URL, $url_comments);
            curl_setopt($ch_comments, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch_comments, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch_comments, CURLOPT_CONNECTTIMEOUT, 900);
            $data_comments = curl_exec($ch_comments);
            $status_comments = curl_getinfo($ch_comments, CURLINFO_HTTP_CODE);
            curl_close($ch_comments);
            $comments = json_decode($data_comments);

            if ($metadata['content_type'] == 'community') $external_id = "https://" . $metadata['hc_domain'] . "/hc/en-us/community/posts/" . $item->id . $add_external_id;
            else $external_id = 'NOT_YET_IMPLEMENTED';

            //ADD LINK FOR JetBrains-owned integrations HACK TO BE ADDED TO OPTIONS SOON
            $additional_info="";
            if ($metadata['email']=="mikhail.vink@jetbrains.com" or $metadata['email']=="serge@jetbrains.com" or $metadata['email']=="mikhail.vink@gmail.com" or $metadata['email']=='jiri.fait@jetbrains.com'){
                $additional_info = "<a href='".$item->html_url."'>".$item->html_url."</a><br/><br/>";
            }

            $message = strip_tags($item->details);
            if ($message == '') $message = ' ';

            $html_message = $item->details;
            if ($html_message == '') $html_message = ' ';
	
			$image_url = (string)$posts->users[array_search($item->author_id, array_column($posts->users, 'id'))]->photo->content_url;
			if ($image_url == '') $image_url = ' ';

            $temp_array = array(
                "external_id" => $external_id,
                "message" => substr((string)$additional_info.$message,0,65536),
                "html_message" => substr((string)$additional_info.$html_message,0,65536),
                "created_at" => $item->created_at,
                "author" => array(
                    "external_id" => (string)$posts->users[array_search($item->author_id, array_column($posts->users, 'id'))]->id,
                    "name" => html_entity_decode($posts->users[array_search($item->author_id, array_column($posts->users, 'id'))]->name, ENT_QUOTES),
                    "image_url" => $image_url
				),
                "fields" => array(
                    array(
                        "id" => "subject",
                        "value" => html_entity_decode($item->title, ENT_QUOTES)
                    )
                ),
                "allow_channelback" => $channelback
            );
            array_push($pull_array_temp, $temp_array);

            foreach (array_reverse($comments->comments) as $item_comments) {
                if ($metadata['content_type'] == 'community') $external_id = "https://" . $metadata['hc_domain'] . "/hc/en-us/community/posts/" . $item_comments->post_id . "/comments/" . $item_comments->id . $add_external_id;
                else $external_id = 'NOT_YET_IMPLEMENTED';

                if ($metadata['content_type'] == 'community') $parent_id = "https://" . $metadata['hc_domain'] . "/hc/en-us/community/posts/" . $item_comments->post_id . $add_external_id;
                else $parent_id = 'NOT_YET_IMPLEMENTED';

                $message = strip_tags($item_comments->body);
                if ($message == '') $message = ' ';

                $html_message = $item_comments->body;
                if ($html_message == '') $html_message = ' ';
	
				$image_url = (string)$comments->users[array_search($item_comments->author_id, array_column($comments->users, 'id'))]->photo->content_url;
				if ($image_url == '') $image_url = ' ';

                $temp_array = array(
                    "external_id" => $external_id,
                    "parent_id" => $parent_id,
                    "message" => substr((string)$message,0,65536),
                    "html_message" => substr((string)$html_message,0,65536),
                    "created_at" => $item_comments->created_at,
                    "author" => array(
                        "external_id" => (string)$comments->users[array_search($item_comments->author_id, array_column($comments->users, 'id'))]->id,
                        "name" => html_entity_decode($comments->users[array_search($item_comments->author_id, array_column($comments->users, 'id'))]->name, ENT_QUOTES),
                        "image_url" => $image_url
                    ),
                    "allow_channelback" => $channelback
                );
                array_push($pull_array_temp, $temp_array);
            }

            $last_comment_timestamp = $item->updated_at;
        }
    }
} elseif ($metadata['content_type'] == 'kb') {
    $url = 'https://' . $metadata['hc_domain'] . '/api/v2/help_center/'.$metadata['hc_locale'].'/sections/'.$metadata['topic_section'].'/articles.json?sort_by=updated_at&sort_order=desc&include=users';
    write_log(sprintf("Trying url: %s", $url));

    $pull_array = array();
    $pull_array_temp = array();
    header('Content-type: application/json; charset=utf-8');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
    curl_setopt($ch, CURLOPT_USERPWD, $metadata['token_username'] . ':' . $metadata['token_api']);
    $data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($metadata['debugging'] == true) write_log("ADVANCED DEBUGGING: Got response from Zendesk, status " . $status);
    else write_log("Got response from Zendesk");

    $articles = json_decode($data);

    if ($metadata['channelback'] == 'true') $channelback = true;
    else $channelback = false;

    foreach (array_reverse($articles->articles) as $item) {
        if ($item->updated_at > $state_compare) {
            $url_comments = 'https://' . $metadata['hc_domain'] . '/api/v2/help_center/'.$metadata['hc_locale'].'/articles/'.$item->id.'/comments.json?include=users';
            $ch_comments = curl_init();
            curl_setopt($ch_comments, CURLOPT_URL, $url_comments);
            curl_setopt($ch_comments, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch_comments, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch_comments, CURLOPT_CONNECTTIMEOUT, 900);
            $data_comments = curl_exec($ch_comments);
            $status_comments = curl_getinfo($ch_comments, CURLINFO_HTTP_CODE);
            curl_close($ch_comments);
            $comments = json_decode($data_comments);

            $external_id = "https://" . $metadata['hc_domain'] . "/hc/" . $metadata['hc_locale'] . "/articles/" . $item->id . $add_external_id;

            $item->body="<h1>".$item->title."</h1>".$item->body;
            
            $message = strip_tags($item->body);
            if ($message == '') $message = ' ';
	
			$image_url = (string)$articles->users[array_search($item->author_id, array_column($articles->users, 'id'))]->photo->content_url;
			if ($image_url == '') $image_url = ' ';

            $temp_array = array(
                "external_id" => $external_id,
                "message" => substr((string)$message,0,65536),
                "html_message" => substr($item->body,0,65536),
                "created_at" => $item->created_at,
                "author" => array(
                    "external_id" => (string)$articles->users[array_search($item->author_id, array_column($articles->users, 'id'))]->id,
                    "name" => html_entity_decode($articles->users[array_search($item->author_id, array_column($articles->users, 'id'))]->name, ENT_QUOTES),
                    "image_url" => $image_url
                ),
                "allow_channelback" => $channelback
            );
            array_push($pull_array_temp, $temp_array);

            foreach (array_reverse($comments->comments) as $item_comments) {
                $external_id = "https://" . $metadata['hc_domain'] . "/hc/" . $metadata['hc_locale'] . "/articles/" . $item_comments->source_id . "/comments/" . $item_comments->id . $add_external_id;

                $parent_id = "https://" . $metadata['hc_domain'] . "/hc/" . $metadata['hc_locale'] . "/articles/" . $item_comments->source_id . $add_external_id;

                $message = strip_tags($item_comments->body);
                if ($message == '') $message = ' ';
	
				$image_url = (string)$comments->users[array_search($item_comments->author_id, array_column($comments->users, 'id'))]->photo->content_url;
				if ($image_url == '') $image_url = ' ';

                $temp_array = array(
                    "external_id" => $external_id,
                    "parent_id" => $parent_id,
                    "message" => substr((string)$message,0,65536),
                    "html_message" => substr($item_comments->body,0,65536),
                    "created_at" => $item_comments->created_at,
                    "author" => array(
                        "external_id" => (string)$comments->users[array_search($item_comments->author_id, array_column($comments->users, 'id'))]->id,
                        "name" => html_entity_decode($comments->users[array_search($item_comments->author_id, array_column($comments->users, 'id'))]->name, ENT_QUOTES),
                        "image_url" => $image_url
                    ),
                    "allow_channelback" => $channelback
                );
                array_push($pull_array_temp, $temp_array);
            }

            $last_comment_timestamp = $item->updated_at;
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

