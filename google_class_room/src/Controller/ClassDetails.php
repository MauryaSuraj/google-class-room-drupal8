<?php
/*
* @class Is responsible for return Details of  the google class room.
* 
*/
namespace Drupal\google_class_room\Controller;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\google_class_room\Controller\GoogleClassController;
use stdClass;
use Drupal\Core\Controller\ControllerBase;
use Google_Service_Exception;

/**
 * 
 */
class ClassDetails extends ControllerBase
{
	var $googleclassroom;
	var $returncoursedetails;
	var $studentsListsFromClassroom;
	var $teachersListFromClassroom;
	var $topicListsFromClassroom;
	var $courseworkFromClassroom;

	function __construct()
	{
		$this->googleclassroom = new GoogleClassController;
		$this->returncoursedetails = new stdClass;
		$this->coursecreatordetail = new stdClass;
		$this->studentsListsFromClassroom = array();
		$this->teachersListFromClassroom = array();
		$this->topicListsFromClassroom = array();
		$this->courseworkFromClassroom = array();
	}

	public function coursedetails($classid = null){

		if (!is_null($classid)) {
		    $coursedetailsformid = $this->googleclassroom->getCourseDetails($classid);

			$this->returncoursedetails->name = $coursedetailsformid->name;
			$this->returncoursedetails->id = $coursedetailsformid->id;
			$this->returncoursedetails->description = str_replace("&nbsp;", ' ', $coursedetailsformid->description);
			$this->returncoursedetails->section = $coursedetailsformid->section; 
			$this->returncoursedetails->alternateLink = $coursedetailsformid->alternateLink;
			$this->returncoursedetails->enrollmentCode = $coursedetailsformid->enrollmentCode;
			
			if ($coursedetailsformid->ownerId) {
				$courseOwner = $this->googleclassroom->getUserProfileDetails($coursedetailsformid->ownerId);
				$this->coursecreatordetail->name = $courseOwner->getName()->fullName;
				$this->coursecreatordetail->usericon = 'https:'.$courseOwner->getPhotoUrl();
				$this->returncoursedetails->coursecreatordetail = $this->coursecreatordetail;
				
			}

				try{
					$topicLists = $this->googleclassroom->gettopicsLists($classid)->getTopic();
					if (!empty($topicLists)&&count($topicLists)>0) {
						foreach ($topicLists as $topics) {
							$newTopics = new stdClass;
							$newTopics->topicid = $topics->getTopicId();
							$newTopics->topicname = $topics->getName();
							array_push($this->topicListsFromClassroom, $newTopics);
						}
					}
					$this->returncoursedetails->topics = $this->topicListsFromClassroom;
				}
				catch(Google_Service_Exception $e){
					if ($e->getCode()) {
						$message = json_decode($e->getMessage())->error->message;
			        	\Drupal::messenger()->addError($message);
					}
				}
			 
			    try {
			    	$getCourseWorks = $this->googleclassroom->getCourseWorkList($classid)->getCourseWork();
			    	if (!empty($getCourseWorks)) {

						foreach ($this->topicListsFromClassroom as $topicsdata) {
							$newarraywithtopicsdata = array();
							
				    		foreach ($getCourseWorks as $coursWork) {
				    			if ($topicsdata->topicid == $coursWork->getTopicId()) {
					    			
					    			$newCourseWork = new stdClass;
					    			$newCourseWork->getAlternateLink  = $coursWork->getAlternateLink( );
									$newCourseWork->getAssigneeMode  = $coursWork->getAssigneeMode( );
									$newCourseWork->getAssignment  = $coursWork->getAssignment( );
									$newCourseWork->getAssociatedWithDeveloper  = $coursWork->getAssociatedWithDeveloper( );
									$newCourseWork->getCourseId  = $coursWork->getCourseId( );
									$newCourseWork->getCreationTime  = $coursWork->getCreationTime( );
									$newCourseWork->getCreatorUserId  = $coursWork->getCreatorUserId( );
									$newCourseWork->getDescription  = $coursWork->getDescription( );
									$newCourseWork->getDueDate  = $coursWork->getDueDate( );
									$newCourseWork->getDueTime  = $coursWork->getDueTime( );	
									$newCourseWork->getId  = $coursWork->getId( );
									$newCourseWork->getIndividualStudentsOptions  = $coursWork->getIndividualStudentsOptions( );
									$newCourseWork->getMaterials  = $coursWork->getMaterials( );
									$newCourseWork->getMaxPoints  = $coursWork->getMaxPoints( );
									$newCourseWork->getMultipleChoiceQuestion  = $coursWork->getMultipleChoiceQuestion( );
									$newCourseWork->getScheduledTime  = $coursWork->getScheduledTime( );
									$newCourseWork->getState  = $coursWork->getState( );
									$newCourseWork->getSubmissionModificationMode  = $coursWork->getSubmissionModificationMode( );
									$newCourseWork->getTitle  = $coursWork->getTitle( );
									$newCourseWork->getTopicId  = $coursWork->getTopicId( );
									$newCourseWork->getUpdateTime  = $coursWork->getUpdateTime( );
									$newCourseWork->getWorkType  = $coursWork->getWorkType( );
									$newCourseWork->topicname= $topicsdata->topicname;
				    				array_push($newarraywithtopicsdata, $newCourseWork);		
				    			}
				    		}	
				    			$newdatacoursedatatopiscs = new stdClass;
				    			$newdatacoursedatatopiscs->topicname= $topicsdata->topicname;
				    			$newdatacoursedatatopiscs->topicid = $topicsdata->topicid;
				    			$newdatacoursedatatopiscs->data =  $newarraywithtopicsdata;	
								array_push($this->courseworkFromClassroom, $newdatacoursedatatopiscs);
						}
						
			    	}
			    	$this->returncoursedetails->courseworks = $this->courseworkFromClassroom;	
			    	
			    } catch (Google_Service_Exception $e) {
			      if ($e->getCode()) {
			        $message = json_decode($e->getMessage())->error->message;
			        print_r($message);
			        \Drupal::messenger()->addError($message);
			      } else {
			        throw $e;
			      }
			    }
			    

				/* 
				* Get List of Students
				*/
			    try{
					$studentsLists = $this->googleclassroom->getStudentsLists($classid)->students;	    	
			    	if (!empty($studentsLists) && count($studentsLists) > 0) {
			    			foreach ($studentsLists as $student) {
			    				$newstudent = new stdClass;
			    				$newstudent->studentid = $student->userId;
			    				$newstudent->profileurl = $student->profile->photoUrl;
			    				$newstudent->fullname = $student->profile->name->fullName;
			    				array_push($this->studentsListsFromClassroom, $newstudent);
			    			}
			    		}	
			    	$this->returncoursedetails->students = $this->studentsListsFromClassroom;
			    } catch (Google_Service_Exception $e){
			    	if ($e->getCode()) {
			    		$message = json_decode($e->getMessage())->error->message;
			        	\Drupal::messenger()->addError($message);
			    	}else{
			    		throw $e;
			    	}
			    }
			    /*
				* code to get the Teachers List in Course.
			    */
			    try{
			    	$teacherslist = $this->googleclassroom->getTeachersLists($classid)->getTeachers();
			    	if (!empty($teacherslist) && count($teacherslist)>0) {
			    		foreach ($teacherslist as  $teacher) {
			    			$newteacher = new stdClass;
			    			$newteacher->teacherid = $teacher->getUserId();
			    			$newteacher->profileurl = $teacher->getProfile()->getPhotoUrl();
			    			$newteacher->fullname = $teacher->getProfile()->getName()->fullName;
			    			array_push($this->teachersListFromClassroom, $newteacher);
			    		}
			    		$this->returncoursedetails->teachers = $this->teachersListFromClassroom;
			    	}

			    }catch (Google_Service_Exception $e){
			    	if ($e->getCode()) {
			    		$message = json_decode($e->getMessage())->error->message;
			    		\Drupal::messenger()->addError($message);
			    	}else{
			    		throw $e;
			    	}
			    }
		}

		return [
			'#theme' => 'googleclassroomcoursedetails_template',
        	'#class' => $this->returncoursedetails,
    	];
	}



}
