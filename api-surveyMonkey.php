<?php
/**
 * Created by PhpStorm.
 * User: tanish
 * Date: 10/21/15
 * Time: 12:57 PM
 */

session_start();


require __DIR__."/SurveyMonkey.class.php";
require __DIR__."/vendor/autoload.php";

define('API_KEY', 'wxy8bfy2kdjfjdkuhxcrchsu');
define('CLIENT_ID', 'apisquarestack');
define('API_SECRET', 'ZSrge9nssrXqeX7RYEsqAF7mJSe6k4ZT');

function initialAuthentication(){
    $provider = new \League\OAuth2\Client\Provider\GenericProvider([
        'clientId'                => CLIENT_ID,    // The client ID assigned to you by the provider
        'apiKey'                  => API_KEY,   // The client password assigned to you by the provider
        'clientSecret'            => API_SECRET,
        'redirectUri'             => 'http://localhost:63342/php-surveymonkey/api-surveyMonkey.php/',
        'urlAuthorize'            => 'https://api.surveymonkey.net/oauth/authorize',
        'urlAccessToken'          => 'https://api.surveymonkey.net/oauth/token',
        'urlResourceOwnerDetails' => 'https://api.surveymonkey.net/oauth/resource'
    ]);


// If we don't have an authorization code then get one
    if (!isset($_GET['code'])) {

        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $provider->getAuthorizationUrl();

        // Get the state generated for you and store it to the session.
        $_SESSION['oauth2state'] = $provider->getState();

        // Redirect the user to the authorization URL.
        header('Location: ' . $authorizationUrl);
        exit;

// Check given state against previously stored one to mitigate CSRF attack
    } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

        unset($_SESSION['oauth2state']);
        exit('Invalid state');
    }
    else {
        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);
            $_SESSION['access_token'] = $accessToken;
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

            // Failed to get the access token or user details.
            exit($e->getMessage());
        }
    }
}



initialAuthentication();
$provider = new SurveyMonkey(API_KEY, $_SESSION['access_token']);
$last200SurveysArray = $provider->getLastNSurveyList(200);
$survey1ID = $last200SurveysArray[0]['survey_id'];

$respondentsToSurvey1 = $provider->getLast1000RespondentsForASurvey($survey1ID);
var_dump($respondentsToSurvey1);

//$oneSurveyDetails = $provider->getSurveyDetails($survey1ID);
//var_dump(json_encode($oneSurveyDetails));
