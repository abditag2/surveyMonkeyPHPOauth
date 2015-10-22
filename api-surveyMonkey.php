<?php
/**
 * Created by PhpStorm.
 * User: tanish
 * Date: 10/21/15
 * Time: 12:57 PM
 */

session_start();

require __DIR__ . "/SurveyMonkey-api-class.php";


//define('API_KEY', 'wxy8bfy2kdjfjdkuhxcrchsu');
//define('CLIENT_ID', 'apisquarestack');
//define('API_SECRET', 'ZSrge9nssrXqeX7RYEsqAF7mJSe6k4ZT');


define('API_KEY', '9ke3sjx8rcymfftpmtfpfkvj');
define('CLIENT_ID', 'squarestack2');
define('API_SECRET', 'WtJh729n279s7ESP2Uwc3cEKKkJ6faXt');

define('REDIRECT_URL', 'http://localhost:63342/php-surveymonkey/api-surveyMonkey.php/');

//Example :

//THe sleeps between the calls is because the surveymonkey has a limited API rate of 2 calls per
$provider = new SurveyMonkey(API_KEY, CLIENT_ID, API_SECRET, REDIRECT_URL );
$provider->initialAuthentication();

sleep (1 );
//get the list of last 1000 surveys
$last1000SurveysArray = $provider->getLastNSurveyList(1000);
$survey1ID = $last1000SurveysArray[0]['survey_id'];

sleep (1 );

//Left side accourding to task
$countsOverDayWeekMonth = $provider->getResponsesOverDayWeekMonth($survey1ID);
echo "day: ".$countsOverDayWeekMonth['lastDay']."\n";
echo "week: ".$countsOverDayWeekMonth['lastWeek']."\n";
echo "month:  ".$countsOverDayWeekMonth['lastMonth']."\n";

sleep (1 );
//Middle for a given survey ID, it gets the completion rate
$surveyCompletionRate = $provider->getSurveyCompletionRate($survey1ID);
echo "started: ".$surveyCompletionRate['started']."\n";
echo "completed: ".$surveyCompletionRate['completed']."\n";

sleep(1);
//Right side:
$last20Respondents = $provider->getLastNRespondentsForASurvey($survey1ID, 20);
foreach($last20Respondents as $respondent){
    echo 'ID: '.$respondent['date_start']."Name: ".$respondent['first_name']." ".$respondent['first_name']." Link to survey: ".$respondent['analysis_url']."\n";
}