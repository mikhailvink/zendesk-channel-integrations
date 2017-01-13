<?php
//E.G. https://zendesk.mvink.me/integrations/stackoverflow/clickthrough.php?external_id=12345
session_start();
require_once '../config.php';
require_once 'logs.php';
require 'header.html';
require 'footer.html';

    if ($_GET['external_id']) {
        $site='stackoverflow';

        if (strpos($_GET['external_id'],"_")){
            $site=explode("_",$_GET['external_id'])[0];
        }

        $redirect_to_comment = "https://".$site.".com/a/" . explode("_",$_GET['external_id'])[1];
        header("Location: " . $redirect_to_comment);
    }

