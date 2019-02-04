<?php
//E.G. https://zendesk.mvink.me/integrations/stackoverflow/clickthrough.php?external_id=12345
session_start();
require_once '../config.php';
require_once 'logs.php';
require 'header.html';
require 'footer.html';

    if ($_GET['external_id']) {

        $redirect_to_comment = $_GET['external_id'];
        header("Location: " . $redirect_to_comment);
    }

