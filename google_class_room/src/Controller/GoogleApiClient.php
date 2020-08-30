<?php
namespace Drupal\google_class_room\Controller;
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Google_Client;
use Google_Service_Classroom;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;


/**
 * GoogleApiClient class refers to the 
 */

class GoogleApiClient 
{	

	public static $client;
	

	  function __construct() {
	    $this->createTokenTable();
	  }

	/**
	*	function return the credentials like client id 
	*/
	public function getCredentials(){
	    $module_handler = \Drupal::service('module_handler');
	    $path = $module_handler->getModule('google_class_room')->getPath();

	    if (!empty(get_included_files())) {
	        $files = get_included_files()[0];
	        $filesarray =  explode("/", $files);
	        $urlarray = array();
	        foreach ($filesarray as  $value) {
	            $ext = pathinfo($value, PATHINFO_EXTENSION);
	            if (!$ext) {
	               array_push($urlarray, $value);
	            }
	        }
	        $filepath = implode("/", $urlarray);
	    }
	    $realpath = $filepath.'/'.$path.'/credentials.json';
	    return $realpath;
	}

	/**
	* function return Service scope
	* it return array of Scopes
	*/

	public function getGoogleClientScope(){
		return array(
			"https://www.googleapis.com/auth/classroom.courses",
			"https://www.googleapis.com/auth/classroom.courses.readonly",
			"https://www.googleapis.com/auth/classroom.rosters",
			"https://www.googleapis.com/auth/classroom.rosters.readonly",
			"https://www.googleapis.com/auth/classroom.coursework.me",
			"https://www.googleapis.com/auth/classroom.coursework.me.readonly",
			"https://www.googleapis.com/auth/classroom.coursework.students",
			"https://www.googleapis.com/auth/classroom.coursework.students.readonly",
			"https://www.googleapis.com/auth/classroom.announcements",
			"https://www.googleapis.com/auth/classroom.announcements.readonly",
			"https://www.googleapis.com/auth/classroom.guardianlinks.students",
			"https://www.googleapis.com/auth/classroom.guardianlinks.students.readonly",
			"https://www.googleapis.com/auth/classroom.guardianlinks.me.readonly",
			"https://www.googleapis.com/auth/classroom.push-notifications",
			"https://www.googleapis.com/auth/classroom.profile.photos",
			"https://www.googleapis.com/auth/classroom.profile.emails",
			"https://www.googleapis.com/auth/classroom.topics",
			"https://www.googleapis.com/auth/classroom.topics.readonly",
			
		);
	}

	/**
	* Read token.json file for existing access token
	*/	

	public function getGoogleAccessToken(){
		/* here checking file for access token => change code for all user save in data base and then check  */
	        $client = $this->createClient();
		if ($this->viewToken()) {

	        /* Set access token from file */
	        if (!is_object($this->viewToken()) && is_string($this->viewToken())) {
	        	$accessToken = json_decode($this->viewToken()->token, true);
	        }else{
	        	$accessToken = $this->viewToken()->token;
	        }

	        $client->setAccessToken($accessToken);
	    
	       
	        /* Check for previous token */
	        if ($this->checkPrevToken($client)) {
	        	/* Get refresh token */
	    		if ($this->getRefreshTokenGoogle($client)) {
	    			/* return refresh token */
	    			return $this->getRefreshTokenGoogle($client);
	    		}else{
	    			// create auth url here 
	    			return $this->createRediectAuthUserUrl($client);
	    		}
	    	}
	    	
	        return $client;
	    }else{
	    	/* create auth url here */
	    	return $this->createRediectAuthUserUrl($client);
	    }
	}

	/**
	*	create google api client if it doesn't exits
	*/

	public function createClient(){

	    $client = new Google_Client();
	    $client->setApplicationName('Google Classroom');
	    $client->setScopes($this->getGoogleClientScope());
	    $client->setAuthConfig($this->getCredentials());
	    $client->setAccessType('offline');
	    $host = \Drupal::request()->getSchemeAndHttpHost();
	    $redirect_uri = $host . '/googleclassroom/callbackurl';
	    $client->setRedirectUri($redirect_uri);
		
		return $client;
	}

	/**
	*  function return the token file url.
	*/

	public function getTokenPath(){
	    $module_handler = \Drupal::service('module_handler');
	    $path = $module_handler->getModule('google_class_room')->getPath();
	    if (!empty(get_included_files())) {
	        $files = get_included_files()[0];
	        $filesarray =  explode("/", $files);
	        $urlarray = array();
	        foreach ($filesarray as  $value) {
	            $ext = pathinfo($value, PATHINFO_EXTENSION);
	            if (!$ext) {
	               array_push($urlarray, $value);
	            }
	        }
	        $filepath = implode("/", $urlarray);
	    }
	    $tokenPath = $filepath.'/'.$path.'/token.json';
	    return $tokenPath;
	}

	public function createTokenTable(){
		$tablename = "googleclassroomtoken";
        $database = \Drupal::database();
        $tablePrefix = \Drupal::database()->getconnectionOptions()['prefix']['default'];
        $table =  $tablePrefix.$tablename;
        if (!\Drupal::database()->schema()->tableExists($tablename)) {
            $sql = "CREATE TABLE $table (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            uid VARCHAR(30) NOT NULL,
            token TEXT NOT NULL,
            created VARCHAR(30) NOT NULL
            )";
          $database->query($sql);
        }
	}

	/**
	* Check if previous token is expired
	*/

	public function checkPrevToken($client = null){
		if (!is_null($client)) {
			/* Check if access token is expired */
			if ($client->isAccessTokenExpired()) {
				return TRUE;
			}else{
				return FALSE;
			}
		}
	}


	public function getRefreshTokenGoogle($client = null){
		if (!is_null($client)) {
			// Check if client can generate refresh token.
			if ($client->getRefreshToken()) {
				$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
				return $client;
			}else{
				return FALSE;
			}			
		}
	}

	public function createRediectAuthUserUrl($client = null ){
			$tempstore = \Drupal::service('tempstore.private');
			$store = $tempstore->get('google_class_room_google_client');
			if ($store->get('redirect_auth_google_client')) {
				$store->delete('redirect_auth_google_client');	
			}

		if (!is_null($client)) {
			$store->set('redirect_auth_google_client', $client);
			$authurl = $client->createAuthUrl();
			if ($authurl) {
				$response = new TrustedRedirectResponse($authurl);
	         	$response->send();	
			}
		}
	}

	public function setTokentoClientUsingAuthCode($authcode = null){
		if ($authcode->get('code')) {

			$tempstore = \Drupal::service('tempstore.private');
			$store = $tempstore->get('google_class_room_google_client');
			$client = $store->get('redirect_auth_google_client');
			
			if (!is_null($client)) {
				$accessToken = $client->fetchAccessTokenWithAuthCode($authcode->get('code'));
	            
	            if (array_key_exists('error', $accessToken)) {
	                // throw new Exception(join(', ', $accessToken));
	                return join(', ', $accessToken);
	            }else{
	            $client->setAccessToken($accessToken);
	            $this->saveTokenForUser(json_encode($client->getAccessToken()));		
				$store->delete('redirect_auth_google_client');
	            return $client;	
	            }
			}

		}
		return;
	}

	public function saveTokenForUser($accessToken = null ){

		$userCurrent = \Drupal::currentUser();
        $current_user = $userCurrent->id();
        $tablename = "googleclassroomtoken";
        $database = \Drupal::database();
        $tablePrefix = \Drupal::database()->getconnectionOptions()['prefix']['default'];
        $table =  $tablePrefix.$tablename;

		if (!is_null($accessToken)) {
			if (\Drupal::database()->schema()->tableExists($tablename)) {

				if ($this->viewToken()) {

			        $result = \Drupal::database()->update($table)
						->condition('uid' , $current_user)
						->fields([
							'token' => $accessToken,
						])
						->execute();


			         if ($result) {
			         	return TRUE;
			         }else{
			         	return FALSE;
			         }
					
				}else{
					
		            $result = $database->insert($tablename)
		          ->fields([
		            'uid' => $current_user,
		            'token' => $accessToken,
		            'created' => \Drupal::time()->getRequestTime(),
		          ])
		          ->execute();
			        if ($result) {
			           return TRUE;
			          }else{
			            return FALSE;
			        }  

				}

	        }
		}else{
			return FALSE;
		}
	}

	public function viewToken(){
		$userCurrent = \Drupal::currentUser();
        $current_user = $userCurrent->id();
        $tablename = "googleclassroomtoken";
        $database = \Drupal::database();
        $tablePrefix = \Drupal::database()->getconnectionOptions()['prefix']['default'];
        $table =  $tablePrefix.$tablename;

        $database = \Drupal::database();
        $query = $database->query("SELECT * FROM {$table} Where uid = $current_user");
        $result = $query->fetchObject();

        if ($result) {
          return $result;
        }else{ return FALSE; }
	}

}


