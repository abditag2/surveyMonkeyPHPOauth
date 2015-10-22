<?php
require __DIR__ . "/vendor/autoload.php";
/**
 * Class for SurveyMonkey API v2
 * @package default
 */
class SurveyMonkey
{
    /**
     * @var string API key
     * @access protected
     */
    protected $_apiKey;

    /**
     * @var is the mashery username for surveyMonkey
     */
    protected $_clientID;

    /**
     * @var is the api secret provided by mashery
     */
    protected $_apiSecret;

    /**
     * @var redirect URI
     */
    protected $_redirectUri;


    /**
     * @var string API access token
     * @access protected
     */
    protected $_accessToken;

    /**
     * @var string API protocol
     * @access protected
     */
    protected $_protocol;

    /**
     * @var string API hostname
     * @access protected
     */
    protected $_hostname;

    /**
     * @var string API version
     * @access protected
     */
    protected $_version;

    /**
     * @var resource $conn The client connection instance to use.
     * @access private
     */
    private $conn = null;

    /**
     * @var array (optional) cURL connection options
     * @access protected
     */
    protected $_connectionOptions;

    /**
     * @const SurveyMonkey Status code:  Success
     */
    const SM_STATUS_SUCCESS = 0;

    public static function successfulHttpResponse($code)
    {
        if ($code >= 200 and $code < 300) {
            return true;
        }
        return false;
    }

    /**
     * SurveyMonkey API Status code definitions
     */
    public static $SM_STATUS_CODES = array(
        0 => "Success",
        1 => "Not Authenticated",
        2 => "Invalid User Credentials",
        3 => "Invalid Request",
        4 => "Unknown User",
        5 => "System Error",
        6 => "Plan Limit Exceeded"
    );

    /**
     * Explain Survey Monkey status code
     * @param integer $code Status code
     * @return string Definition
     */
    public static function explainStatusCode($code)
    {
        return self::$SM_STATUS_CODES[$code];
    }

    /**
     * The SurveyMonkey Constructor.
     *
     * This method is used to create a new SurveyMonkey object with a connection to a
     * specific api key and access token
     *
     * @param string $apiKey A valid api key
     * @param string $accessToken A valid access token
     * @param array $options (optional) An array of options
     * @param array $connectionOptions (optional) cURL connection options
     * @throws SurveyMonkey_Exception If an error occurs creating the instance.
     * @return SurveyMonkey A unique SurveyMonkey instance.
     */


    public function __construct($apiKey, $clientID, $apiSecret, $redirectURI, $options = array(), $connectionOptions = array())
    {

        if (empty($apiKey)) throw new SurveyMonkey_Exception('Missing apiKey');
        if (empty($clientID)) throw new SurveyMonkey_Exception('Missing clientID');
        if (empty($apiSecret)) throw new SurveyMonkey_Exception('Missing apiSecret');
        if (empty($redirectURI)) throw new SurveyMonkey_Exception('Missing redirectURI');

        $this->_apiKey = $apiKey;
        $this->_clientID = $clientID;
        $this->_apiSecret = $apiSecret;
        $this->_redirectUri = $redirectURI;

        $this->_protocol = (!empty($options['protocol'])) ? $options['protocol'] : 'https';
        $this->_hostname = (!empty($options['hostname'])) ? $options['hostname'] : 'api.surveymonkey.net';
        $this->_version = (!empty($options['version'])) ? $options['version'] : 'v2';

        $this->_connectionOptions = $connectionOptions;
    }

    /**
     * Build the request URI
     * @param string $endpoint API endpoint to call in the form: resource/method
     * @return string Constructed URI
     */
    protected function buildUri($endpoint)
    {
        return $this->_protocol . '://' . $this->_hostname . '/' . $this->_version . '/' . $endpoint . '?api_key=' . $this->_apiKey;
    }

    protected function builtUriWithoutAPIKey($endpoint)
    {
        return $this->_protocol . '://' . $this->_hostname . '/' . $endpoint;
    }

    /**
     * Get the connection
     * @return boolean
     */
    protected function getConnection()
    {
        $this->conn = curl_init();
        return is_resource($this->conn);
    }

    /**
     * Close the connection
     */
    protected function closeConnection()
    {
        curl_close($this->conn);
    }


    public function initialAuthentication()
    {
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => $this->_clientID,    // The client ID assigned to you by the provider
            'apiKey' => $this->_apiKey,   // The client password assigned to you by the provider
            'clientSecret' => $this->_apiSecret,
            'redirectUri' => $this->_redirectUri,
            'urlAuthorize' => $this->builtUriWithoutAPIKey('oauth/authorize'),
            'urlAccessToken' => $this->builtUriWithoutAPIKey('oauth/token'),
            'urlResourceOwnerDetails' => $this->builtUriWithoutAPIKey('oauth/resource')
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
        } else {
            try {
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
                $_SESSION['access_token'] = $accessToken;
                $this->_accessToken = $accessToken;
            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                // Failed to get the access token or user details.
                exit($e->getMessage());
            }
        }


    }


    /**
     * Run the
     * @param string $method API method to run
     * @param array $params Parameters array
     * @return array Results
     */
    protected function run($endpoint, $params = array())
    {
        if (!is_resource($this->conn)) {
            if (!$this->getConnection()) return $this->failure('Can not initialize connection');
        }

        $request_url = $this->buildUri($endpoint);
        curl_setopt($this->conn, CURLOPT_URL, $request_url);  // URL to post to
        curl_setopt($this->conn, CURLOPT_RETURNTRANSFER, 1);   // return into a variable
        $postBody = (!empty($params)) ? json_encode($params) : "{}";
        $headers = array('Content-type: application/json', 'Authorization: Bearer ' . $this->_accessToken, 'Content-Length: ' . strlen($postBody));
        curl_setopt($this->conn, CURLOPT_HTTPHEADER, $headers); // custom headers
        curl_setopt($this->conn, CURLOPT_HEADER, false);     // return into a variable
//    curl_setopt($this->conn, CURLOPT_POST, true);     // POST
        curl_setopt($this->conn, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($this->conn, CURLOPT_POSTFIELDS, $postBody);
        curl_setopt_array($this->conn, $this->_connectionOptions);  // (optional) additional options

        $result = curl_exec($this->conn);
        if ($result === false) return $this->failure('Curl Error: ' . curl_error($this->conn));
        $responseCode = curl_getinfo($this->conn, CURLINFO_HTTP_CODE);
        if (!self::successfulHttpResponse($responseCode)) {
            return $this->failure('Error [' . $responseCode . ']: ' . $result);
        }

        $this->closeConnection();

        $parsedResult = json_decode($result, true);
        $jsonErr = json_last_error();
        if ($parsedResult === null && $jsonErr !== JSON_ERROR_NONE) return $this->failure("Error [$jsonErr] parsing result JSON");

        $status = $parsedResult['status'];
        if ($status != self::SM_STATUS_SUCCESS) return $this->failure("API Error: Status [$status:" . self::explainStatusCode($status) . '].  Message [' . $parsedResult["errmsg"] . ']');
        else return $this->success($parsedResult["data"]);
    }


    /**
     * Return an error
     * @param string $msg Error message
     * @return array Result
     */
    protected function failure($msg)
    {
        return array(
            'success' => false,
            'message' => $msg
        );
    }

    /**
     * Return a success with data
     * @param string $data Payload
     * @return array Result
     */
    protected function success($data)
    {
        return array(
            'success' => true,
            'data' => $data
        );
    }


    /***************************
     * SurveyMonkey API methods
     ***************************/

    //survey methods

    /**
     * Retrieves a paged list of surveys in a user's account.
     * @see https://developer.surveymonkey.com/mashery/get_survey_list
     * @param array $params optional request array
     * @return array Result
     */
    public function getSurveyList($params = array())
    {
        return $this->run('surveys/get_survey_list', $params);
    }

    /**
     * Retrieve a given survey's metadata.
     * @see https://developer.surveymonkey.com/mashery/get_survey_details
     * @param string $surveyId Survey ID
     * @return array Results
     */
    public function getSurveyDetails($surveyId)
    {
        $params = array('survey_id' => $surveyId);
        return $this->run('surveys/get_survey_details', $params);
    }

    /**
     * Retrieves a paged list of collectors for a survey in a user's account.
     * @see https://developer.surveymonkey.com/mashery/get_collector_list
     * @param string $surveyId Survey ID
     * @param array $params optional request array
     * @return array Results
     */
    public function getCollectorList($surveyId, $params = array())
    {
        $params['survey_id'] = $surveyId;
        return $this->run('surveys/get_collector_list', $params);
    }

    /**
     * Retrieves a paged list of respondents for a given survey and optionally collector
     * @see https://developer.surveymonkey.com/mashery/get_respondent_list
     * @param string $surveyId Survey ID
     * @param array $params optional request array
     * @return array Results
     */
    public function getRespondentList($surveyId, $params = array())
    {
        $params['survey_id'] = $surveyId;
        return $this->run('surveys/get_respondent_list', $params);
    }

    /**
     * Takes a list of respondent ids and returns the responses that correlate to them.
     * @see https://developer.surveymonkey.com/mashery/get_responses
     * @param string $surveyId Survey ID
     * @param array $respondentIds Array of respondents IDs to retrieve
     * @param integer $chunkSize optional number of respondants to fetch in each chunk. We split it to multiple requests to conform with SurveyMonkey's API limits.  If successful, the returned array is a joined array of all chunks.
     * @return array Results
     */
    public function getResponses($surveyId, $respondentIds, $chunkSize = 100)
    {
        // Split requests to multiple chunks, if larger then $chunkSize
        if (count($respondentIds) > $chunkSize) {
            $data = array();
            foreach (array_chunk($respondentIds, $chunkSize) as $r) {
                $result = $this->getResponses($surveyId, $r, $chunkSize);
                if (!$result["success"]) return $result;
                $data = array_merge($data, $result["data"]);
            }
            return $this->success($data);
        }

        $params = array(
            'survey_id' => $surveyId,
            'respondent_ids' => $respondentIds
        );
        return $this->run('surveys/get_responses', $params);
    }

    /**
     * Returns how many respondents have started and/or completed the survey for the given collector
     * @see https://developer.surveymonkey.com/mashery/get_response_counts
     * @param string $collectorId Collector ID
     * @return array Results
     */
    public function getResponseCounts($collectorId)
    {
        $params = array('collector_id' => $collectorId);
        return $this->run('surveys/get_response_counts', $params);
    }

    //user methods

    /**
     * Returns basic information about the logged-in user
     * @see https://developer.surveymonkey.com/mashery/get_user_details
     * @return array Results
     */
    public function getUserDetails()
    {
        return $this->run('user/get_user_details');
    }

    //template methods

    /**
     * Retrieves a paged list of templates provided by survey monkey.
     * @see https://developer.surveymonkey.com/mashery/get_template_list
     * @param array $params optional request array
     * @return array Results
     */
    public function getTemplateList($params = array())
    {
        return $this->run('templates/get_template_list', $params);
    }

    //collector methods

    /**
     * Retrieves a paged list of templates provided by survey monkey.
     * @see https://developer.surveymonkey.com/mashery/create_collector
     * @param string $surveyId Survey ID
     * @param string $collectorName optional Collector Name - defaults to 'New Link'
     * @param string $collectorType required Collector Type - only 'weblink' currently supported
     * @param array $params optional request array
     * @return array Results
     */
    public function createCollector($surveyId, $collectorName = null, $collectorType = 'weblink')
    {
        $params = array(
            'survey_id' => $surveyId,
            'collector' => array(
                'type' => $collectorType,
                'name' => $collectorName
            )
        );
        return $this->run('collectors/create_collector', $params);
    }

    //batch methods

    /**
     * Create a survey, email collector and email message based on a template or existing survey.
     * @see https://developer.surveymonkey.com/mashery/create_flow
     * @param string $surveyTitle Survey Title
     * @param array $params optional request array
     * @return array Results
     */
    public function createFlow($surveyTitle, $params = array())
    {
        if (isset($params['survey'])) {
            $params['survey']['survey_title'] = $surveyTitle;
        } else {
            $params['survey'] = array('survey_title' => $surveyTitle);
        }
        return $this->run('batch/create_flow', $params);
    }

    /**
     * Create an email collector and email message attaching them to an existing survey.
     * @see https://developer.surveymonkey.com/mashery/send_flow
     * @param string $surveyId Survey ID
     * @param array $params optional request array
     * @return array Results
     */
    public function sendFlow($surveyId, $params = array())
    {
        $params['survey_id'] = $surveyId;
        return $this->run('batch/send_flow', $params);
    }


    /**
     * returns a list of last N surveys in an array
     *
     * @param $N is the number last surveys that you want returned (max is 1000) otherwise we should go through multiple pages
     * @return array[][]
     *  Example response
     * ["title"]=> string(18) "Fardin test survey"
     * ["date_modified"]=> string(19) "2015-10-22 00:02:00"
     * ["analysis_url"]=> string(104) "https://www.surveymonkey.com/MySurvey_Responses.aspx?sm=4iYvCj1dXlYixTtfx490_2FIAmx7AlT29BjGezcRTtDZA_3D"
     * ["num_responses"]=> int(2)
     * ["date_created"]=> string(19) "2015-10-22 00:01:00"
     * ["survey_id"]=> string(8) "70620247"
     * ["question_count"]=> int(1)
     *
     *
     */
    public function getLastNSurveyList($N)
    {
        if (!isset($N))
            $N = 1000;
        $surveyList = $this->getSurveyList(
            array(
                "fields" => array(
                    "title",
                    "analysis_url",
                    "date_created",
                    "date_modified",
                    "question_count",
                    "num_responses"
                ),
                'page_size' => $N,
                'page' => 1,
            ));
        if ($surveyList['success'])
            return $surveyList['data']['surveys'];
        else
            throw new SurveyMonkey_Exception('There was a problem fetching list of the surveys');
    }


    /**
     * This functin return the last 1000 respondents to a sruvey (only the date they responded to the survey)
     *
     * @param $surveyID, N is the count of how many last respondents do we need
     * @return mixed
     * Example output
     * ["first_name"]=> string(0) ""
     * ["last_name"]=> string(0) ""
     * ["date_start"]=> string(19) "2015-10-22 00:01:44"
     * ["email"]=> string(0) ""
     * ["respondent_id"]=> string(10) "4274915876"
     * @throws SurveyMonkey_Exception
     */
    public function getLastNRespondentsForASurvey($surveyID, $N)
    {
        $respondentsToSurvey1 = $this->getRespondentList($surveyID, array(
            'page_size' => $N,
            'fields' => array(
            'date_start', 'first_name', 'last_name', 'email')
        ));

        if ($respondentsToSurvey1['success'])
            return $respondentsToSurvey1['data']['respondents'];
        else
            throw new SurveyMonkey_Exception('respondent List Not available');
    }


    /**
     * @param $surveyID
     * @return array
     * Example output is an array with the following indexes:
     * ['started'] : percent of respondents that started the survey and have not completed it yet
     * ['completed'] : percentage of the respondents that started and completed the survey
     */
    public function getSurveyCompletionRate($surveyID)
    {

        $collectorList = $this->getCollectorList($surveyID);
        $collectorID = $collectorList['data']['collectors'][0]['collector_id'];
        $response = $this->getResponseCounts($collectorID);

        $startedCount = $response['data']['started'];
        $completedCount = $response['data']['completed'];

        $startedPercent = $startedCount/ ($startedCount + $completedCount) * 100;
        $completedPercent = $completedCount / ($startedCount + $completedCount) * 100;

        return array('started' => $startedPercent, 'completed' => $completedPercent);
    }


    /**
     * @param $surveyID
     * @return array[]
     * Example output:
     * ['lastDay'] : number of responds in the last day
     * ['lastWeek'] : Number of responses in the last week
     * ['lastMonth'] : number of responses in the last month
     * @throws SurveyMonkey_Exception
     */
    public function getResponsesOverDayWeekMonth($surveyID)
    {
        $respondentsToSurvey = $this->getLastNRespondentsForASurvey($surveyID, 1000);

        // since there are not enough data in the survey account, I tested with these
//    $respondentsToSurvey = array(
//        array('date_start' => '2015-10-22 00:01:37'),
//        array('date_start' => '2015-10-21 00:01:37'),
//        array('date_start' => '2015-10-11 00:01:37'),
//        array('date_start' => '2015-8-11 00:01:37'),
//        array('date_start' => '2015-9-29 00:01:37'));

        $responsesInLastDayCount = 0;
        $responsesInLastWeekCount = 0;
        $responsesInLastMonthCount = 0;

        $yesterday = strtotime(date('Y-m-d', strtotime(' -1 day')));
        $lastWeek = strtotime(date('Y-m-d', strtotime(' -1 week')));
        $lastMonth = strtotime(date('Y-m-d', strtotime(' -1 month')));

        foreach ($respondentsToSurvey as $respondent) {
            $dataAndTime = $respondent['date_start'];

            $responseDate = strtotime(date(substr($dataAndTime, 0, 10)));

            if ($responseDate >= $yesterday) {
                //responses in last dat
                $responsesInLastDayCount++;
            }
            if ($responseDate >= $lastWeek) {
                $responsesInLastWeekCount++;
            }
            if ($responseDate >= $lastMonth) {
                $responsesInLastMonthCount++;
            }

        }

        return array('lastDay' => $responsesInLastDayCount, 'lastWeek' => $responsesInLastWeekCount,
            'lastMonth' => $responsesInLastMonthCount);
    }

}

/**
 * A basic class for SurveyMonkey Exceptions.
 * @package default
 */
class SurveyMonkey_Exception extends Exception
{
}