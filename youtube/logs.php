<?php
/**
 * Created by PhpStorm.
 * User: mikhailvink
 * Date: 24/09/16
 * Time: 18:22
 */

require_once '../config.php';

function write_log($message) {
    if (!is_file("../logs/youtube_".date("Y_m_d").".log")){
        $fh = fopen("../logs/youtube_".date("Y_m_d").".log", 'w');
        fclose($fh);
    }
    $logfile="../logs/youtube_".date("Y_m_d").".log";

    // Get time of request
    if( ($time = $_SERVER['REQUEST_TIME']) == '') {
        $time = time();
    }

    // Get IP address
    if( ($remote_addr = $_SERVER['REMOTE_ADDR']) == '') {
        $remote_addr = "REMOTE_ADDR_UNKNOWN";
    }

    // Get requested script
    if( ($request_uri = $_SERVER['REQUEST_URI']) == '') {
        $request_uri = "REQUEST_URI_UNKNOWN";
    }

    // Format the date and time
    $date = date("Y-m-d H:i:s", $time);

    // Append to the log file
    if($fd = @fopen($logfile, "a")) {
        fputcsv($fd, array($date, $remote_addr, $request_uri, $message));
        fclose($fd);
    }
}

function write_log_callback($message) {
    if (!is_file("../logs_callback/youtube_".date("Y_m_d").".log")){
        $fh = fopen("../logs_callback/youtube_".date("Y_m_d").".log", 'w');
        fclose($fh);
    }
    $logfile="../logs_callback/youtube_".date("Y_m_d").".log";

    // Get time of request
    if( ($time = $_SERVER['REQUEST_TIME']) == '') {
        $time = time();
    }

    // Get IP address
    if( ($remote_addr = $_SERVER['REMOTE_ADDR']) == '') {
        $remote_addr = "REMOTE_ADDR_UNKNOWN";
    }

    // Get requested script
    if( ($request_uri = $_SERVER['REQUEST_URI']) == '') {
        $request_uri = "REQUEST_URI_UNKNOWN";
    }

    // Format the date and time
    $date = date("Y-m-d H:i:s", $time);

    // Append to the log file
    if($fd = @fopen($logfile, "a")) {
        fputcsv($fd, array($date, $remote_addr, $request_uri, $message));
        fclose($fd);
    }
}
