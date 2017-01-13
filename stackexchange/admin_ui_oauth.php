<?php
session_start();
require_once '../config.php';
require 'header.html';
require_once 'logs.php';
//print_r($_SESSION);
//print_r($_POST);

if (isset($_POST['save']) and $_POST['save'] == '1') {
    if ($_POST['save'] == '1' and $_POST['save_at']!='1') $_SESSION['form_data'] = $_POST;

    if ($_SESSION['access_token']) {
        $url = 'https://api.stackexchange.com/2.2/access-tokens/'.$_SESSION['access_token'].'?key='.$globalConfig['SEAPIKey'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 900);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        $data = curl_exec($ch);
        if (json_decode($data)->items[0]->account_id) $status=200;
    } else $status = 0;

    if ($status == '200') {

        //MAILCHIMP
        $data=array(
            "email_address"=>$_SESSION['form_data']['email'],
            "status_if_new"=>"subscribed",
            "merge_fields"=>array(
                "FNAME"=>$_SESSION['form_data']['first_name'],
                "LNAME"=>$_SESSION['form_data']['last_name'],
                "ZDDOMAIN"=>$_SESSION['form_data']['subdomain'],
                "ZDLOCALE"=>$_SESSION['form_data']['locale'],
                "ZDSEINT"=>"True"
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
            "site" => $_SESSION['form_data']['site'],
            "tag" => $_SESSION['form_data']['tag'],
            "stackexchange_type" => $_SESSION['form_data']['stackexchange_type'],
            "access_token" => $_SESSION['access_token'],
            "legal" => $_SESSION['form_data']['legal'],
            "debugging" => $_SESSION['form_data']['debugging']
        );
        $metadata = urlencode(json_encode($metadata_array));

        $state_array = json_decode(urldecode($_SESSION['form_data']['state']), true);
        if (!$state_array['last_comment_timestamp']) $state_array['last_comment_timestamp'] = 0;
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
        unset($_SESSION['access_token'], $_SESSION['oauth2state']);
        echo "The information has been saved to Zendesk channel information";
    } else {
        echo '
        <script>
        $(document).ready(function(){
    var win;
    var checkConnect;
    var $connect = $("#stackexchange_auth");
    var oAuthURL = "https://'.$globalConfig['Domain'].'/integrations/stackexchange/oauth";
    $connect.click(function() {
        win = window.open(oAuthURL, \'StackExchangeAuth\', \'width=972,height=660,modal=yes,alwaysRaised=yes,toolbar=no,location=no,status=no,menubar=no\');
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
<span style="margin-top:25px; font-weight: bold; ">1. Make sure you are logged in to Stack Exchange in the same browser window! (work-arounding limitations)</span><br/><br/>
<span style="margin-top:25px; font-weight: bold; ">2. Sign in with</span><br/>
<a href="#" id="stackexchange_auth"><img src="https://'.$globalConfig['Domain'].'/integrations/stackexchange/se-logo.png" alt="Sign in with Stack Exchange" width="191"></a></div>';
        write_log("New OAuth via Stack Exchange");

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
<script src="/stackexchange/jquery.validate.min.js"></script>
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
            tag: {
                required: true
            },
            site: {
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
<form action="https://'.$globalConfig['Domain'].'/integrations/stackexchange/admin_ui?save=1" method="post" enctype="application/x-www-form-urlencoded" id="config-form">
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
<label class="control-label">Stack Exchange Site: <span style="color:red;">*</span></label>
<br>(you can find more information at <a href="http://stackexchange.com/sites" target="_blank">http://stackexchange.com/sites</a>)<br/>
<select name="site" id="site" style="width:410px;">
';

    $data=json_decode(file_get_contents('stackexchange_sites.json'));

        foreach ($data as $k) {
            if ($k->api_site_parameter == $metadata['site'])
                echo "<option value='" . $k->api_site_parameter . "' selected>" . $k->name . " (" . $k->site_url . ")</option>\r\n";
            else echo "<option value='" . $k->api_site_parameter . "'>" . $k->name . " (" . $k->site_url . ")</option>\r\n";
        }

echo '</select>
</div>
<div class="form-group">
<label class="control-label">Stack Exchange Site Tag: <span style="color:red;">*</span></label>
<br>(please make sure to specify one tag which is avalailable for the Stack Exchange site you\'ve chosen in the previous field)
<input type="text" class="form-control" name="tag" id="tag" value="' . $metadata['tag'] . '">
</div>
<div class="form-group">
<label class="control-label">Type of integration:</label>
<select class="form-control" name="stackexchange_type">
<option value="full" ';
    if ($metadata['stackexchange_type'] == "full") echo 'selected';
    echo '>Full</option>
<option value="read_only" ';
    if ($metadata['stackexchange_type'] == "read_only") echo 'selected';
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
    if ($metadata['debugging'] == "true") echo ' checked';
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
<input type="hidden" name="access_token" value="' . $metadata['access_token'] . '">
<input type="hidden" name="subdomain" value="' . $zendesk_data['subdomain'] . '">
<input type="hidden" name="locale" value="' . $zendesk_data['locale'] . '">
<input type="submit" type="button" class="btn btn-lg btn-success" value="Save">
</form></div>';
    if ($metadata['debugging']==true) write_log("ADVANCED DEBUGGING: Admin UI Opened with parameters: " . print_R($_POST, true));
    else write_log("Admin UI Opened");
}
require 'footer.html';
