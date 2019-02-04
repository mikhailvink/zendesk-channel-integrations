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
    "name" => "Stack Exchange Questions",
    "id" => "zendesk-stack-exchange-integration",
    "author" => "Mikhail Vink",
    "version" => "v0.1.0",
    "urls" => array(
        "admin_ui" => "https://".$globalConfig['Domain']."/integrations/stackexchange/admin_ui",
        "pull_url" => "https://".$globalConfig['Domain']."/integrations/stackexchange/pull",
        "channelback_url" => "https://".$globalConfig['Domain']."/integrations/stackexchange/channelback",
        "clickthrough_url" => "https://".$globalConfig['Domain']."/integrations/stackexchange/clickthrough",
        "dashboard_url" => "https://".$globalConfig['Domain']."/integrations/stackexchange/dashboard",
        "about_url" => "https://".$globalConfig['Domain']."/",
        "event_callback_url" => "https://".$globalConfig['Domain']."/integrations/stackexchange/callback"
    )
);
echo json_encode($manifest_array);
write_log("Stack Exchange manifest has been requested. Response: ".json_encode($manifest_array));
