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
    "name" => "Guide Comments",
    "id" => "zendesk-guide-channel-integration",
    "author" => "Mikhail Vink",
    "version" => "v0.1.0",
    "urls" => array(
        "admin_ui" => "https://".$globalConfig['Domain']."/integrations/guide/admin_ui",
        "pull_url" => "https://".$globalConfig['Domain']."/integrations/guide/pull",
        "channelback_url" => "https://".$globalConfig['Domain']."/integrations/guide/channelback",
        "clickthrough_url" => "https://".$globalConfig['Domain']."/integrations/guide/clickthrough",
        "dashboard_url" => "https://".$globalConfig['Domain']."/integrations/guide/dashboard",
        "about_url" => "https://".$globalConfig['Domain']."/",
        "event_callback_url" => "https://".$globalConfig['Domain']."/integrations/guide/callback"
    )
);
echo json_encode($manifest_array);
write_log("Guide manifest has been requested. Response: ".json_encode($manifest_array));
