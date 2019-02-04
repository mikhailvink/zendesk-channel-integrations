<?php
session_start();
require_once '../config.php';
require 'header.html';
require_once 'logs.php';

if (isset($_POST['save']) and $_POST['save'] == '1') {
    if ($_POST['save'] == '1' and $_POST['save_at']!='1') $_SESSION['form_data'] = $_POST;

        //MAILCHIMP
        $data=array(
            "email_address"=>$_SESSION['form_data']['email'],
            "status_if_new"=>"subscribed",
            "merge_fields"=>array(
                "FNAME"=>$_SESSION['form_data']['first_name'],
                "LNAME"=>$_SESSION['form_data']['last_name'],
                "ZDDOMAIN"=>$_SESSION['form_data']['subdomain'],
                "ZDLOCALE"=>$_SESSION['form_data']['locale'],
                "ZDGUIDE"=>"True"
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
            "hc_domain" => $_SESSION['form_data']['hc_domain'],
            "content_type" => $_SESSION['form_data']['content_type'],
            "hc_locale" => $_SESSION['form_data']['hc_locale'],
            "handling_type" => $_SESSION['form_data']['handling_type'],
            "topic_section" => $_SESSION['form_data']['topic_section'],
            "channelback" => $_SESSION['form_data']['channelback'],
            "token_username" => $_SESSION['form_data']['token_username'],
            "token_api" => $_SESSION['form_data']['token_api'],
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
        echo "The information has been saved to Zendesk channel information";

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
            hc_domain: {
                required: true
            },
            content_type: {
                required: true
            },
            handling_type: {
                required: true
            },
            topic_section: {
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
<form action="https://'.$globalConfig['Domain'].'/integrations/guide/admin_ui?save=1" method="post" enctype="application/x-www-form-urlencoded" id="config-form">
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
<label class="control-label">Help Center URL: <span style="color:red;">*</span></label>
<br>(please specify a full domain of the help center e.g. <i>sales.jetbrains.com</i>)
<input type="text" class="form-control" name="hc_domain" id="hc_domain" value="' . $metadata['hc_domain'] . '">
</div>
<div class="form-group">
<label class="control-label">Content type: <span style="color:red;">*</span></label>
<select class="form-control" name="content_type">
<option value="community" ';
    if ($metadata['content_type'] == "community") echo 'selected';
    echo '>Community Topic</option>
<option value="kb" ';
    if ($metadata['content_type'] == "kb") echo 'selected';
    echo '>Knowledge Base Section</option>
</select>
</div>
<div class="form-group">
<label class="control-label">Locale: </label>
<br>(only for knowledge base articles e.g. <i>en-US</i>)
<input type="text" class="form-control" name="hc_locale" id="hc_locale" value="' . $metadata['hc_locale'] . '">
</div>

<div class="form-group">
<label class="control-label">Comments handling type: <span style="color:red;">*</span></label>
<select class="form-control" name="handling_type">
<option value="one_ticket" ';
    if ($metadata['one_ticket'] == "one_ticket") echo 'selected';
    echo '>Put all comments for topic/article to a single ticket</option>
<option value="multiple_tickets" ';
    if ($metadata['multiple_tickets'] == "multiple_tickets") echo 'selected';
    echo ' disabled>Put all comments for topic/article to multiple ticket (one per ticket) NOT READY YET</option>
</select>
</div>
<div class="form-group">
<label class="control-label">Community Topic or KB Section: <span style="color:red;">*</span></label>
<br>(please specify a numeric ID of the community topic or knowledge base section here e.g. <i>123456</i>)
<input type="text" class="form-control" name="topic_section" id="topic_section" value="' . $metadata['topic_section'] . '">
</div>
<div class="form-group">
<label class="control-label" for="channelback">Allow posting answers from Zendesk Agent interface:</label>
<input type="checkbox" id="channelback" name="channelback" value="true"';
    if ($metadata['channelback'] == "true") echo ' checked';
    echo '>
<br>(!!! EXPERIMENTAL, will only post under the username specified as "Username for API Connection")
</div>
<div class="form-group">
<label class="control-label">Username for API Connection:</label>
<br>Only to allow posting answers from Zendesk Agent interface (e.g. <i>mikhail.vink@gmail.com/token</i>)
<input type="text" class="form-control" name="token_username" id="token_username" value="' . $metadata['token_username'] . '">
</div>
<div class="form-group">
<label class="control-label">API Key:</label>
<br>Only to allow posting answers from Zendesk Agent interface
<input type="password" class="form-control" name="token_api" id="token_api" value="' . $metadata['token_api'] . '">
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
<label class="control-label" for="legal">I agree to subscribe to an email newsletter (sent in case of significant changes or news, you can unsubscribe at any time) and <a href="https://'.$globalConfig['Domain'].'/legal.htm">accept privacy policy</a>: <span style="color:red;">*</span></label>
<input type="checkbox" id="legal" name="legal" value="true" ';
    if ($metadata['legal'] == "true") echo 'checked';
    echo '>
</div>
<input type="hidden" name="save" value="1">
<input type="hidden" name="return_url" value="' . $zendesk_data['return_url'] . '">
<input type="hidden" name="subdomain" value="' . $zendesk_data['subdomain'] . '">
<input type="hidden" name="locale" value="' . $zendesk_data['locale'] . '">
<input type="submit" type="button" class="btn btn-lg btn-success" value="Save">
</form></div>';
    if ($metadata['debugging']==true) write_log("ADVANCED DEBUGGING: Admin UI Opened with parameters: " . print_R($_POST, true));
    else write_log("Admin UI Opened");
}
require 'footer.html';
