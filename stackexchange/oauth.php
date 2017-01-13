<?php
session_start();
require_once '../config.php';
require_once 'logs.php';
require '../vendor/autoload.php';
require 'header.html';
require 'footer.html';
write_log("OAUTH Process Started");

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => $globalConfig['SEClientID'],    // The client ID assigned to you by the provider
    'clientSecret'            => $globalConfig['SEClientSecret'],   // The client password assigned to you by the provider
    'redirectUri'             => 'https://'.$globalConfig['Domain'].'/integrations/stackexchange/oauth',
    'urlAuthorize'            => 'https://stackexchange.com/oauth',
    'urlAccessToken'          => 'https://stackexchange.com/oauth/access_token',
    'urlResourceOwnerDetails' => ''
]);

if (!empty($_GET['error'])) {

    // Got an error, probably user denied access
    write_log('Got error: ' . htmlspecialchars($_GET['error']));
    exit('Got error: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'));

} elseif (!isset($_GET['code'])) {
// If we don't have an authorization code then get one

    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    if ($_SESSION['form_data']['stackexchange_type'] == "full") $scope = "no_expiry, write_access";
    else $scope = "no_expiry";
    write_log("OAUTH Request with Scope " . $scope);
    $authorizationUrl = $provider->getAuthorizationUrl(['scope' => $scope]);

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    write_log("State is invalid, possible CSRF attack in progress");
    unset($_SESSION['oauth2state']);
    write_log("OAUTH Invalid State");
    exit('Invalid state');

} else {

    try {

        write_log("Try to get an access token using the authorization code grant");

        //HACK because of https://github.com/thephpleague/oauth2-client/issues/466
        //OTHERWISE USE THE LIB

        // Try to get an access token using the authorization code grant.
        //$accessToken = $provider->getAccessToken('authorization_code', [
//            'code' => $_GET['code']
//        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://stackexchange.com/oauth/access_token");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('client_id' => $globalConfig['SEClientID'],'client_secret'=>$globalConfig['SEClientSecret'],'code'=>$_GET['code'],'redirect_uri'=>'https://'.$globalConfig['Domain'].'/integrations/stackexchange/oauth')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec ($ch);
        curl_close ($ch);

        $_SESSION['access_token']=str_replace("access_token=","",$data);

        echo "
    <script>
    parent.close();
    </script>
    ";

    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

        // Failed to get the access token or user details.
        exit($e->getMessage());

    }

}
