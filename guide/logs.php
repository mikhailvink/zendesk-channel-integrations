<?php
/**
 * Created by PhpStorm.
 * User: mikhailvink
 * Date: 24/09/16
 * Time: 18:22
 */
require_once '../config.php';

function write_log($message)
{
    global $globalConfig;

    if ($globalConfig['LogsDBType'] == '1' or $globalConfig['LogsDBType'] == '3') {
        if (!is_file("../logs/guide_" . date("Y_m_d") . ".log")) {
            $fh = fopen("../logs/guide_" . date("Y_m_d") . ".log", 'w');
            fclose($fh);
        }
        $logfile = "../logs/guide_" . date("Y_m_d") . ".log";

        // Get time of request
        if (($time = $_SERVER['REQUEST_TIME']) == '') {
            $time = time();
        }

        // Get IP address
        if (($remote_addr = $_SERVER['REMOTE_ADDR']) == '') {
            $remote_addr = "REMOTE_ADDR_UNKNOWN";
        }

        // Get requested script
        if (($request_uri = $_SERVER['REQUEST_URI']) == '') {
            $request_uri = "REQUEST_URI_UNKNOWN";
        }

        // Format the date and time
        $date = date("Y-m-d H:i:s", $time);

        // Append to the log file
        if ($fd = @fopen($logfile, "a")) {
            fputcsv($fd, array($date, $remote_addr, $request_uri, $message));
            fclose($fd);
        }
    }

    if ($globalConfig['LogsDBType'] == '2' or $globalConfig['LogsDBType'] == '3') {

        // Get time of request
        if (($time = $_SERVER['REQUEST_TIME']) == '') {
            $time = time();
        }

        // Get IP address
        if (($remote_addr = $_SERVER['REMOTE_ADDR']) == '') {
            $remote_addr = "REMOTE_ADDR_UNKNOWN";
        }

        // Get requested script
        if (($request_uri = $_SERVER['REQUEST_URI']) == '') {
            $request_uri = "REQUEST_URI_UNKNOWN";
        }

        // Format the date and time
        $date = date("Y-m-d H:i:s", $time);

        $mysqli = new mysqli($globalConfig['LogsDBHost'], $globalConfig['LogsDBUser'], $globalConfig['LogsDBPassword'], $globalConfig['LogsDBName'], $globalConfig['LogsDBPort']);

        $mysqli->query("INSERT INTO  zendesk_integrations_logs_app (
`CREATEDON` ,
`IP` ,
`URL` ,
`MESSAGE` ,
`TYPE` ,
`SUBDOMAIN`
)
VALUES (
'" . $date . "',  '" . $remote_addr . "',  '" . $request_uri . "', '" . $message . "',  'Guide',  '');");

        $mysqli->close();
    }
}

function write_log_callback($message)
{
    global $globalConfig;

    if ($globalConfig['LogsDBType'] == '1' or $globalConfig['LogsDBType'] == '3') {
        if (!is_file("../logs_callback/guide_" . date("Y_m_d") . ".log")) {
            $fh = fopen("../logs_callback/guide_" . date("Y_m_d") . ".log", 'w');
            fclose($fh);
        }
        $logfile = "../logs_callback/guide_" . date("Y_m_d") . ".log";

        // Get time of request
        if (($time = $_SERVER['REQUEST_TIME']) == '') {
            $time = time();
        }

        // Get IP address
        if (($remote_addr = $_SERVER['REMOTE_ADDR']) == '') {
            $remote_addr = "REMOTE_ADDR_UNKNOWN";
        }

        // Get requested script
        if (($request_uri = $_SERVER['REQUEST_URI']) == '') {
            $request_uri = "REQUEST_URI_UNKNOWN";
        }

        // Format the date and time
        $date = date("Y-m-d H:i:s", $time);

        // Append to the log file
        if ($fd = @fopen($logfile, "a")) {
            fputcsv($fd, array($date, $remote_addr, $request_uri, $message));
            fclose($fd);
        }
    }

    if ($globalConfig['LogsDBType'] == '2' or $globalConfig['LogsDBType'] == '3') {

        // Get time of request
        if (($time = $_SERVER['REQUEST_TIME']) == '') {
            $time = time();
        }

        // Get IP address
        if (($remote_addr = $_SERVER['REMOTE_ADDR']) == '') {
            $remote_addr = "REMOTE_ADDR_UNKNOWN";
        }

        // Get requested script
        if (($request_uri = $_SERVER['REQUEST_URI']) == '') {
            $request_uri = "REQUEST_URI_UNKNOWN";
        }

        // Format the date and time
        $date = date("Y-m-d H:i:s", $time);

        $mysqli = new mysqli($globalConfig['LogsDBHost'], $globalConfig['LogsDBUser'], $globalConfig['LogsDBPassword'], $globalConfig['LogsDBName'], $globalConfig['LogsDBPort']);

        if (json_decode($message, true)['events'][0]['error']) $error=1;
        else $error=0;

        $mysqli->query("INSERT INTO  zendesk_integrations_logs_callback (
`CREATEDON` ,
`IP` ,
`URL` ,
`CALLBACK` ,
`TYPE` ,
`SUBDOMAIN` ,
`EVENT_TYPE_ID`,
`ERROR_FLAG`
)
VALUES (
'" . $date . "',  '" . $remote_addr . "',  '" . $request_uri . "', '" . addslashes($message) . "',  'Guide',  '" . json_decode($message, true)['events'][0]['subdomain'] . "', '" . json_decode($message, true)['events'][0]['type_id'] . "', '".$error."');");

        $mysqli->close();
    }

}
