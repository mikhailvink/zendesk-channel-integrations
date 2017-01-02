<?php

$provider = require 'oauth_provider.php';
require_once '../config.php';
require_once 'logs.php';
require 'header.html';
require 'footer.html';
write_log("OAUTH Process Started");
if (!empty($_GET['error'])) {

    // Got an error, probably user denied access
    write_log('Got error: ' . htmlspecialchars($_GET['error']));
    exit('Got error: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'));

} elseif (empty($_GET['code'])) {

    if ($_SESSION['form_data']['youtube_type'] == "full") $scope = "https://www.googleapis.com/auth/youtube.force-ssl";
    else $scope = "https://www.googleapis.com/auth/youtube.readonly";
    write_log("OAUTH Request with Scope " . $scope);
    // If we don't have an authorization code then get one
    if ($_SESSION['form_data']['state'] == "") $authUrl = $provider->getAuthorizationUrl(['scope' => $scope, 'access_type' => 'offline', 'approval_prompt' => 'force']);
    else $authUrl = $provider->getAuthorizationUrl(['scope' => $scope, 'access_type' => 'offline']);

    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;

} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    write_log("State is invalid, possible CSRF attack in progress");
    // State is invalid, possible CSRF attack in progress
    unset($_SESSION['oauth2state']);
    write_log("OAUTH Invalid State");
    exit('Invalid state');

} else {

    write_log("Try to get an access token (using the authorization code grant)");
    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    $_SESSION['token'] = serialize($token);

    // Optional: Now you have a token you can look up a users profile data
    echo "
    <script>
    parent.close();
    </script>
    ";
}