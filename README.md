PHP class for SurveyMonkey API
==============================


Basic usage
----
```

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
```

All methods
----

**getSurveyList**
```
/**
 * Retrieves a paged list of surveys in a user's account.
 * @see https://developer.surveymonkey.com/mashery/get_survey_list
 * @param array $params optional request array
 * @return array Result
 */
public function getSurveyList($params = array()){}
```

**getSurveyDetails**
```
/**
 * Retrieve a given survey's metadata.
 * @see https://developer.surveymonkey.com/mashery/get_survey_details
 * @param string $surveyId Survey ID
 * @return array Results
 */
public function getSurveyDetails($surveyId){}
```

**getCollectorList**
```
/**
 * Retrieves a paged list of collectors for a survey in a user's account.
 * @see https://developer.surveymonkey.com/mashery/get_collector_list
 * @param string $surveyId Survey ID
 * @param array $params optional request array
 * @return array Results
 */
public function getCollectorList($surveyId, $params = array()){}
```

**getRespondentList**
```
/**
 * Retrieves a paged list of respondents for a given survey and optionally collector
 * @see https://developer.surveymonkey.com/mashery/get_respondent_list
 * @param string $surveyId Survey ID
 * @param array $params optional request array
 * @return array Results
 */
public function getRespondentList($surveyId, $params = array()){}
```

**getResponses**
```
/**
 * Takes a list of respondent ids and returns the responses that correlate to them.
 * @see https://developer.surveymonkey.com/mashery/get_responses
 * @param string $surveyId Survey ID
 * @param array $respondentIds Array of respondents IDs to retrieve
 * @param integer $chunkSize optional number of respondants to fetch in each chunk. We split it to multiple requests to conform with SurveyMonkey's API limits.  If successful, the returned array is a joined array of all chunks.
 * @return array Results
 */
public function getResponses($surveyId, $respondentIds, $chunkSize = 100){}
```

**getResponseCount**
```
/**
 * Returns how many respondents have started and/or completed the survey for the given collector
 * @see https://developer.surveymonkey.com/mashery/get_response_counts
 * @param string $collectorId Collector ID
 * @return array Results
 */
public function getResponseCount($collectorId){}
```

**getUserDetails**
```
/**
 * Returns basic information about the logged-in user
 * @see https://developer.surveymonkey.com/mashery/get_user_details
 * @return array Results
 */
public function getUserDetails(){}
```

**getTemplateList**
```
/**
 * Retrieves a paged list of templates provided by survey monkey.
 * @see https://developer.surveymonkey.com/mashery/get_template_list
 * @param array $params optional request array
 * @return array Results
 */
public function getTemplateList($params = array()){}
```

**createCollector**
```
/**
 * Retrieves a paged list of templates provided by survey monkey.
 * @see https://developer.surveymonkey.com/mashery/create_collector
 * @param string $surveyId Survey ID
 * @param string $collectorName optional Collector Name - defaults to 'New Link'
 * @param string $collectorType required Collector Type - only 'weblink' currently supported
 * @param array $params optional request array
 * @return array Results
 */
public function createCollector($surveyId, $collectorName = null, $collectorType = 'weblink'){}
```

**createFlow**
```
/**
 * Create a survey, email collector and email message based on a template or existing survey.
 * @see https://developer.surveymonkey.com/mashery/create_flow
 * @param string $surveyTitle Survey Title
 * @param array $params optional request array
 * @return array Results
 */
public function createFlow($surveyTitle, $params = array()){}
```

**sendFlow**
```
/**
 * Create an email collector and email message attaching them to an existing survey.
 * @see https://developer.surveymonkey.com/mashery/send_flow
 * @param string $surveyId Survey ID
 * @param array $params optional request array
 * @return array Results
 */
public function sendFlow($surveyId, $params = array()){}
```

API version
-----------
v2


Tests
-----
See /tests/all_methods.php


License
----
**No** rights reserved.
*Do whatever you want with it,  It's free*
