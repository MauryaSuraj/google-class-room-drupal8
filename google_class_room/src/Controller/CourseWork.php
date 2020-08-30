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
 * @class CourseWork 
 */

/*
The Classroom API currently allows developers to read and write two of these types: Assignments and Questions.

To access this functionality, you can use the CourseWork resource, which represents an Assignment or Question that has been assigned to students in a particular course, including any additional materials and details, like due date or max score.

https://developers.google.com/classroom/reference/rest/v1/courses.courseWork

*/

class CourseWork
{

	var $googleclassroom;
	
	function __construct()
	{
		$this->googleclassroom = new GoogleClassController;
	}

	public function index($courseid = null){
		print_r("HELLO");
		die;
		return FALSE;
	}

	public function create(){
		return FALSE;
	}

	public function list(){
		return FALSE;
	}

	public function patch(){
		return FALSe;
	}
}


// curl --request POST \
//   'https://classroom.googleapis.com/v1/courses/142213776920/courseWork' \
//   --header 'Authorization: Bearer [YOUR_ACCESS_TOKEN]' \
//   --header 'Accept: application/json' \
//   --header 'Content-Type: application/json' \
//   --data '{"title":"First Course Work","description":"Some Lorem Details To the course work","workType":"ASSIGNMENT","state":"PUBLISHED","dueDate":{"day":21,"month":3,"year":2021},"dueTime":{"hours":0,"minutes":0,"nanos":0,"seconds":0},"topicId":"142419208595","maxPoints":100,"assigneeMode":"INDIVIDUAL_STUDENTS","individualStudentsOptions":{"studentIds":["surajmauryalorem@gmail.com"]}}' \
//   --compressed
