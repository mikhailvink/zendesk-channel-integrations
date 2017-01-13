<?php
require_once '../config.php';
$provider = require 'oauth_provider.php';
require 'header.html';
require_once 'logs.php';

if (!empty($_SESSION['token'])) {
    $token = unserialize($_SESSION['token']);
}

if (isset($_POST['save']) and $_POST['save'] == '1') {
    if ($_POST['save'] == '1') $_SESSION['form_data'] = $_POST;
    if (isset($token)) {
        $url = 'https://www.googleapis.com/oauth2/v3/tokeninfo?access_token=' . $token->getToken();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
        $header = "Authorization: Bearer " . $token->getToken();
        curl_setopt($ch, CURLOPT_HEADER, $header);
        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    } else $status = 0;

    if ($status == '200') {

        if ($_SESSION['form_data']['refresh_token'] == "" or $_SESSION['form_data']['refresh_token'] == "null") $refresh_token = $token->getRefreshToken();
        else $refresh_token = $_SESSION['form_data']['refresh_token'];

        //MAILCHIMP
        $data=array(
            "email_address"=>$_SESSION['form_data']['email'],
            "status_if_new"=>"subscribed",
            "merge_fields"=>array(
                "FNAME"=>$_SESSION['form_data']['first_name'],
                "LNAME"=>$_SESSION['form_data']['last_name'],
                "ZDDOMAIN"=>$_SESSION['form_data']['subdomain'],
                "ZDLOCALE"=>$_SESSION['form_data']['locale'],
                "ZDYTINT"=>"True"
            )
        );
        $url = 'https://us14.api.mailchimp.com/3.0/lists/'.$globalConfig['MailChimp_List'].'/members/'.md5(strtolower($_SESSION['form_data']['email']));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_USERPWD, $globalConfig['MailChimp_Auth']);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));
        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (isset($_SESSION['form_data']['debugging']) and $_SESSION['form_data']['debugging']==true) write_log("ADVANCED DEBUGGING: Saved information to MailChimp, status ".$status.", data ".$data);
        else write_log("Saved information to MailChimp");

        if (!isset($_SESSION['form_data']['debugging'])) $_SESSION['form_data']['debugging']='';

        $metadata_array = array(
            "first_name" => $_SESSION['form_data']['first_name'],
            "last_name" => $_SESSION['form_data']['last_name'],
            "email" => $_SESSION['form_data']['email'],
            "channel" => $_SESSION['form_data']['channel'],
            "youtube_type" => $_SESSION['form_data']['youtube_type'],
            "refresh_token" => $refresh_token,
            "legal" => $_SESSION['form_data']['legal'],
            "debugging" => $_SESSION['form_data']['debugging']
        );
        $metadata = urlencode(json_encode($metadata_array));

        $state_array = json_decode(urldecode($_SESSION['form_data']['state']), true);
        if (!$state_array['last_comment_timestamp']) $state_array['last_comment_timestamp'] = 0;
        $state_array['oauth_token'] = $token->getToken();
        $state_array['oauth_token_expires'] = $token->getExpires();
        $state = urlencode(json_encode($state_array));
        if ($metadata_array['debugging']==true) write_log("ADVANCED DEBUGGING: Saving config data to Zendesk, return_url=" . $_SESSION['form_data']['return_url'] . ". State: " . $state . ", Metadata: " . $metadata . ", Name: " . $_SESSION['form_data']['int_name']);
        else write_log("Saving config data to Zendesk");
        echo '
          <form id="finish"
                method="post"
                action="' . $_SESSION['form_data']['return_url'] . '">
            <input type="hidden"
                   name="name"
                   value="' . $_SESSION['form_data']['int_name'] . '">
            <input type="hidden"
                   name="metadata"
                   value=\'' . $metadata . '\'>
                   <input type="hidden"
                   name="state"
                   value=\'' . $state . '\'>
          </form>
          <script type="text/javascript">
            // Post the form
            var form = document.forms[\'finish\'];
            form.submit();
          </script>
        ';
        unset($_SESSION['token'], $_SESSION['state']);
        echo "The information has been saved to Zendesk channel information";
    } else {
        echo '
        <script>
        $(document).ready(function(){
    var win;
    var checkConnect;
    var $connect = $("#youtube_auth");
    var oAuthURL = "https://'.$globalConfig['Domain'].'/integrations/youtube/oauth";
    $connect.click(function() {
        win = window.open(oAuthURL, \'YouTubeAuth\', \'width=972,height=660,modal=yes,alwaysRaised=yes,toolbar=no,location=no,status=no,menubar=no\');
    });

    checkConnect = setInterval(function() {
        if (!win || !win.closed) return;
        clearInterval(checkConnect);
        window.location.reload();
    }, 100);
});
</script>
        ';
        echo '<div style="text-align: center;padding:20px;">
<span style="margin-top:25px; font-weight: bold; ">1. Make sure you are logged in to Google in the same browser window! (work-arounding limitations)</span><br/><br/>
<span style="margin-top:25px; font-weight: bold; ">2. Sign in with:</span><br/>
<a href="#" id="youtube_auth"><img src="https://'.$globalConfig['Domain'].'/integrations/youtube/btn_google_signin_light_pressed_web.png" alt="Sign in with Google" width="191"></a></div>';
        write_log("New OAuth via Google YouTube");
    }

} else {
    $zendesk_data = array();
    $zendesk_data['name'] = $_POST['name'];
    $zendesk_data['metadata'] = $_POST['metadata'];
    $zendesk_data['state'] = $_POST['state'];
    $zendesk_data['return_url'] = $_POST['return_url'];
    $zendesk_data['subdomain'] = $_POST['subdomain'];
    $zendesk_data['locale'] = $_POST['locale'];
    $metadata = json_decode(urldecode($_POST['metadata']), true);
    $state = $_POST['state'];

    echo '
<script src="/youtube/jquery.validate.min.js"></script>
<script>
$(document).ready(function () {
    $(\'#config-form\').validate({
        rules: {
        first_name: {
                required: true
            },
            last_name: {
                required: true
            },
            int_name: {
                required: true
            },
            channel: {
                required: true
            },
            email: {
                required: true,
                email: true
            },
            legal: {
                required: true
            }
        }
    });

});
</script>
<div>
<form action="https://'.$globalConfig['Domain'].'/integrations/youtube/admin_ui?save=1" method="post" enctype="application/x-www-form-urlencoded" id="config-form">
<div class="form-group">
<label class="control-label">Integration Name: <span style="color:red;">*</span></label>
<input type="text" class="form-control" name="int_name" id="int_name" value="' . $zendesk_data['name'] . '">
</div>
<div class="form-group">
<label class="control-label">First Name: <span style="color:red;">*</span></label>
<input type="text" class="form-control" name="first_name" id="first_name" value="' . $metadata['first_name'] . '">
</div>
<div class="form-group">
<label class="control-label">Last Name: <span style="color:red;">*</span></label>
<input type="text" class="form-control" name="last_name" id="last_name" value="' . $metadata['last_name'] . '">
</div>
<div class="form-group">
<label class="control-label">E-mail: <span style="color:red;">*</span></label>
<input type="text" class="form-control" name="email" id="email" value="' . $metadata['email'] . '">
</div>
<div class="form-group">
<label class="control-label">YouTube Channel ID: <span style="color:red;">*</span></label>
<br>(you can find it at <a href="https://www.youtube.com/account_advanced" target="_blank">https://www.youtube.com/account_advanced</a>)
<input type="text" class="form-control" name="channel" id="channel" value="' . $metadata['channel'] . '">
</div>
<div class="form-group">
<label class="control-label">Type of integration:</label>
<select class="form-control" name="youtube_type">
<option value="full" ';
    if ($metadata['youtube_type'] == "full") echo 'selected';
    echo '>Full</option>
<option value="read_only" ';
    if ($metadata['youtube_type'] == "read_only") echo 'selected';
    echo '>Read Only</option>
</select>
</div>
<a href="#" id="advanced">Show advanced configuration</a>
<div id="advanced-config" style="display:none;">
  <div class="form-group">
<label class="control-label">State:</label>
<textarea class="form-control" name="state" rows="7" cols="30">' . $state . '</textarea>
</div>
<div class="form-group">
<label class="control-label">Metadata:</label>
<textarea class="form-control" name="metadata" rows="7" cols="30">' . $_POST['metadata'] . '</textarea>
</div>
<div class="form-group">
<label class="control-label" for="debugging">Send advanced debugging data to app developers:</label>
<input type="checkbox" id="debugging" name="debugging" value="true"';
    if ($metadata['debugging'] == "true") echo 'checked';
    echo '>
</div>
</div>
<script>
$( "#advanced" ).click(function() {
  $( "#advanced-config" ).slideToggle( "slow" );
  $( "#advanced" ).hide();
});
</script>

<div class="form-group">
<label class="control-label" for="legal">I agree to subscribe to an email newsletter (sent in case of significant changes or news, you can unsubscribe at any time) and <a href="https://'.$globalConfig['Domain'].'/legal.html">accept privacy policy</a>: <span style="color:red;">*</span></label>
<input type="checkbox" id="legal" name="legal" value="true" ';
    if ($metadata['legal'] == "true") echo 'checked';
    echo '>
</div>
<input type="hidden" name="save" value="1">
<input type="hidden" name="return_url" value="' . $zendesk_data['return_url'] . '">
<input type="hidden" name="refresh_token" value="' . $metadata['refresh_token'] . '">
<input type="hidden" name="subdomain" value="' . $zendesk_data['subdomain'] . '">
<input type="hidden" name="locale" value="' . $zendesk_data['locale'] . '">
<input type="submit" type="button" class="btn btn-lg btn-success" value="Save">
</form></div>';
    if ($metadata['debugging']==true) write_log("ADVANCED DEBUGGING: Admin UI Opened with parameters: " . print_R($_POST, true));
    else write_log("Admin UI Opened");
}
require 'footer.html';