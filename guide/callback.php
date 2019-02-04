<?php
/**
 * Created by PhpStorm.
 * User: mikhailvink
 * Date: 29/09/16
 * Time: 00:47
 */
session_start();
require_once '../config.php';
require_once 'logs.php';

write_log_callback(file_get_contents("php://input"));
