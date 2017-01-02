<?php
//E.G. https://zendesk.mvink.me/integrations/youtube/clickthrough.php?external_id=z13giha54qvfx5vgt04cdppaskqbf1uz3z40k
require_once '../config.php';
require_once 'logs.php';
require 'header.html';
require 'footer.html';
$APIKEY=$globalConfig['YouTubeAPIKey'];

if ($_GET['external_id']) {
    $url = 'https://www.googleapis.com/youtube/v3/commentThreads?part=snippet&id=' . $_GET['external_id'] . '&key=' . $APIKEY;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
    $data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $comments = json_decode($data);
    $redirect_to_comment = "https://www.youtube.com/watch?v=" . $comments->items[0]->snippet->videoId . "&lc=" . $_GET['external_id'];

    write_log("Clickthrough redirect");

    header("Location: " . $redirect_to_comment);
}
