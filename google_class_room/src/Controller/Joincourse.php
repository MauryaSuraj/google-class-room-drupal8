<?php 

namespace Drupal\google_class_room\Controller;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\google_class_room\Controller\GoogleClassController;
use stdClass;
use Drupal\Core\Controller\ControllerBase;
use Google_Service_Exception;


/**
 * Join Class 
 */
class Joincourse 
{
	var $googleclassroom;
	var $joindata;
	var $isEnrolled;
	
	function __construct()
	{
		$this->googleclassroom = new GoogleClassController;
		$this->joindata = new stdClass;
		$this->isEnrolled = FALSE;
	}

	public function index($joinid = null){
		if (!is_null($joinid)) {
			if ($this->googleclassroom->viewEnrollCodeWithCourseid($joinid)) {
				$classdata = $this->googleclassroom->viewEnrollCodeWithCourseid($joinid);
				$this->joindata->classid = $classdata->classid; 
				if ($classdata->classid) {
					/*
					* Check if already enrol to the course 
					*/
					if (!empty($this->googleclassroom->getCourseList())) {
						foreach ($this->googleclassroom->getCourseList() as $couselist) {
							if ($couselist->id === $classdata->classid) {
								$this->isEnrolled = TRUE;
							}
						}
					}

					if (!$this->isEnrolled) {
						try{
							$addStudentToClass = $this->googleclassroom->addStudentToClass($classdata->classid,$joinid,'me');
							if ($addStudentToClass->courseId == $classdata->classid) {

								\Drupal::messenger()->addStatus("You are Enrol to the course ");			

								$host = \Drupal::request()->getSchemeAndHttpHost();
								$redURL = $host.'/googleclassroom/classdetails/'.$classdata->classid;
								$response = new TrustedRedirectResponse($redURL);
			         			$response->send();
							}
						} catch (Google_Service_Exception $e){
							if ($e->getCode()) {
								$message = json_decode($e->getMessage())->error->message;
								\Drupal::messenger()->addError($message);
							}
						}
					}else{

						$host = \Drupal::request()->getSchemeAndHttpHost();
						$redURL = $host.'/googleclassroom/classdetails/'.$classdata->classid;
						$response = new TrustedRedirectResponse($redURL);
	         			$response->send();	
					}

					
				}


			}else{
				/*
				* Show Error Message
				*/
				\Drupal::messenger()->addError("Wrong code");
			}
		}

		return [
			'#theme' => 'googleclassroomjoin_template',
        	'#join' => $this->joindata,
    	];
	}

}