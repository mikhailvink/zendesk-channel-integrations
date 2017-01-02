<?php
/**
 * Created by PhpStorm.
 * User: mikhailvink
 * Date: 03/09/16
 * Time: 01:17
 */
header('Content-Type: application/json');
require_once '../config.php';
require_once 'logs.php';
$manifest_array = array(
    "name" => "YouTube",
    "id" => "zendesk-youtube-integration",
    "author" => "Mikhail Vink",
    "version" => "v0.1.0",
    "urls" => array(
        "admin_ui" => "https://".$globalConfig['Domain']."/integrations/youtube/admin_ui",
        "pull_url" => "https://".$globalConfig['Domain']."/integrations/youtube/pull",
        "channelback_url" => "https://".$globalConfig['Domain']."/integrations/youtube/channelback",
        "clickthrough_url" => "https://".$globalConfig['Domain']."/integrations/youtube/clickthrough",
        "dashboard_url" => "https://".$globalConfig['Domain']."/integrations/youtube/dashboard",
        "about_url" => "https://".$globalConfig['Domain']."/",
        "event_callback_url" => "https://".$globalConfig['Domain']."/integrations/youtube/callback"
    )
);
echo json_encode($manifest_array);
write_log("Manifest has been requested. Response: ".json_encode($manifest_array));
