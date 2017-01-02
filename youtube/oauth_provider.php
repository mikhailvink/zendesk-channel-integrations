<?php
require_once '../config.php';
require '../vendor/autoload.php';
require_once 'logs.php';

use League\OAuth2\Client\Provider\Google;

// Replace these with your token settings
// Create a project at https://console.developers.google.com/
$clientId     = $globalConfig['YouTubeOAuthClientID'];
$clientSecret = $globalConfig['YouTubeOAuthClientSecret'];

// Change this if you are not using the built-in PHP server
$redirectUri  = 'https://'.$globalConfig['Domain'].'/integrations/youtube/oauth';

// Start the session
session_start();

// Initialize the provider
$provider = new Google(compact('clientId', 'clientSecret', 'redirectUri'));

// No HTML for demo, prevents any attempt at XSS
header('Content-Type', 'text/plain');

return $provider;
