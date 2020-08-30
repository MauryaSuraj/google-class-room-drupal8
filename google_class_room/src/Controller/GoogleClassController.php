<?php
/**
 * @file
 * Contains \Drupal\google_class_room\Controller\GoogleClassController.
 */
namespace Drupal\google_class_room\Controller;
error_reporting(E_ALL);
ini_set('display_errors', 1);
use Google_Client;
use Google_Service_Classroom;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\google_class_room\Controller\GoogleApiClient;
use stdClass;
use Google_Service_Classroom_Course;
use Google_Service_Classroom_Student;
use Google_Service_Classroom_Teacher;
use Google_Service_Classroom_Resource_Invitations;
use Google_Service_Classroom_Invitation;
use Google_Service_Classroom_Topic;
use Google_Service_Classroom_CourseWork;
use Google_Service_Classroom_IndividualStudentsOptions;
use Google_Service_Classroom_MultipleChoiceQuestion;
use Google_Service_Classroom_Date;
use Google_Service_Classroom_TimeOfDay;
use Google_Service_Classroom_ListCourseWorkResponse;

/**
* @check destination url needs to implement on all the routes
*  it's used in callback to redirect to that page.
*/


class GoogleClassController {

	function __construct()
	{
		$this->saveClassidWithEnrollcode();
	}
  

  public function index() {
    /*  Set destination url for call back */
    $classes = array();
     $request = \Drupal::request();
     $session = $request->getSession();
     $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
     $host = \Drupal::request()->getSchemeAndHttpHost();
     $gooleapiclient = new GoogleApiClient;
    // Get the API client and construct the service object.
    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());
    // Print the first 10 courses the user has access to.
    $optParams = array(
      'pageSize' => 10
    );
    $results = $service->courses->listCourses($optParams);
    if (count($results->getCourses()) == 0) {
    } else {
      foreach ($results->getCourses() as $course) {
        $classgoogle = new stdClass;
        $classgoogle->id = $course->getId();
        $classgoogle->name = $course->getName();
        $classgoogle->courselink = $host.'/googleclassroom/classdetails/'.$course->getId();
        array_push($classes, $classgoogle);
      }
    $this->checkForEnrollment($results->getCourses());
  
    }

    return [
      '#theme' => 'googleclassroommain_template',
      '#test_var' => ('Test Value'),
      '#classes' => $classes,
    ];
  }

  /**
   * @createGoogleClassRoomCourse function is use to create class on google class room 
  */

  public function createGoogleClassRoomCourse(
    $coursename = null, 
    $section = null, 
    $descriptionHeading = null, 
    $description = null, 
    $room = null, 
    $courseState = 'PROVISIONED' ){
     
     $request = \Drupal::request();
     $session = $request->getSession();
     $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
     $gooleapiclient = new GoogleApiClient;
     
     $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());
     
     $course = new Google_Service_Classroom_Course(array(
          'name' => $coursename,
          'section' => $section,
          'descriptionHeading' => $descriptionHeading,
          'description' => $description,
          'room' => '301',
          'ownerId' => 'me',
          'courseState' => $courseState
        ));
        return $course = $service->courses->create($course);
  }

  /**
   * @function  getCourseDetails return details about course
   * Needed course id 
  */

  public function getCourseDetails($courseid = null){
    if (!is_null($courseid)) {
     $request = \Drupal::request();
     $session = $request->getSession();
     $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
     $gooleapiclient = new GoogleApiClient;
     $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());
    try {
      $course = $service->courses->get($courseid);
      /* Course found  */
      return $course; 
    } catch (Google_Service_Exception $e) {
      if ($e->getCode() == 404) {
        printf("Course with ID '%s' not found.\n", $courseId);
      } else {
        throw $e;
      }
    }
    }else{
        /* Return No course id found */
        return "Course Id IS NULL";
    }
  }


  /**
   * @function  getCourseList return All courses from you id.
   * Needed course id 
  */

  public function getCourseList(){

  	 $request = \Drupal::request();
     $session = $request->getSession();
     $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
     $gooleapiclient = new GoogleApiClient;
     $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

  	$pageToken = NULL;
	$courses = array();

	do {
	  $params = array(
	    'pageSize' => 100,
	    'pageToken' => $pageToken
	  );
	  // client.classroom.courses.students.list({})
	  $response = $service->courses->listCourses($params);
	  $courses = array_merge($courses, $response->courses);
	  $pageToken = $response->nextPageToken;
	} while (!empty($pageToken));

	
	return $courses;
  }

  /**
   * @function updatecourse
   * Needed course id 
  */  
  public function updateGoogleClassCourse($courseId = null,$classname,$sectionname,$descriptionHeading,$details){

  	if (!is_null($courseId)) {

	  	$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

		$course = $service->courses->get($courseId);
		$course->name = $classname;
		$course->section = $sectionname;
		$course->descriptionHeading = $descriptionHeading;
		$course->description = $details;

		$course = $service->courses->update($courseId, $course);

		return $course;   		
  	
  	}
  }

  	/* <= Managing Student and teachers From here => */

  	/*
	* @function addTeacherToClass 
	* adds teachers to specific course
	* course id and email id needed to add them
  	*/
	
	public function addTeacherToClass($courseId = null, $teacherEmail = null){

	  	$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

		$teacher = new Google_Service_Classroom_Teacher(array(
		  'userId' => $teacherEmail
		));
		try {
		  $teacher = $service->courses_teachers->create($courseId, $teacher);
		  return $teacher;
		} catch (Google_Service_Exception $e) {
		  if ($e->getCode() == 409) {
		  	return "User '%s' is already a member of this course.\n" . $teacherEmail;
		  } else {
		    throw $e;
		  }
		}
	}

	/*
	* @function addStudentToClass 
	* adds Student to specific course
	* course id and enrollmentCode needed to add them
  	*/
	
	public function addStudentToClass($courseId,  $enrollmentCode, $userId){
		
		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

		$student = new Google_Service_Classroom_Student(array(
		  'userId' => $userId
		));
		$params = array(
		  'enrollmentCode' => $enrollmentCode
		);
		
		$student = $service->courses_students->create($courseId, $student, $params);
		return $student;
	}

	/*
	* @function removeStudentFromClass 
	* remove Student to specific course
	* course id and email id needed to remove them
  	*/
	
	public function removeStudentFromClass($courseId = null , $enrollmentCode = null){
		
		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

		$student = new Google_Service_Classroom_Student(array(
		  'userId' => 'me'
		));
		$params = array(
		  'enrollmentCode' => $enrollmentCode
		);
		try {
		  $student = $service->courses_students->delete($courseId, $student, $params);
		  printf("User '%s' was enrolled  as a student in the course with ID '%s'.\n",
		      $student->profile->name->fullName, $courseId);
		} catch (Google_Service_Exception $e) {
		  if ($e->getCode() == 409) {
		    print "You are already a member of this course.\n";
		  } else {
		    throw $e;
		  }
		}
	}

		/*
	* @function removeTeacherFromClass 
	* remove Teacher to specific course
	* course id and email id needed to remove them
  	*/
	
	public function removeTeacherFromClass($courseId, $teacherEmail){

		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

	    $teacher = new Google_Service_Classroom_Teacher(array(
		  'userId' => $teacherEmail
		));
		try {
		  $teacher = $service->courses_teachers->delete($courseId, $teacher);
		  printf("User '%s' was added as a teacher to the course with ID '%s'.\n",
		      $teacher->profile->name->fullName, $courseId);

		} catch (Google_Service_Exception $e) {
		  if ($e->getCode() == 409) {
		    printf("User '%s' is already a member of this course.\n", $teacherEmail);
		  } else {
		    throw $e;
		  }
		}

	}


	/*
	* @function create Invitation to add students and teacher to course.
	*/

	public function createInvitations($courseid, $role, $userId){

		if ($courseid && $role && $userId) {
			$request = \Drupal::request();
		    $session = $request->getSession();
		    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
		    $gooleapiclient = new GoogleApiClient;
		    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

		    $invite	= new Google_Service_Classroom_Invitation(
		    	array(
					'courseId' => $courseid,
					'role' => $role,
					'userId' => $userId,
				)
		    );

		    if (!is_null($userId)) {
				$createinvite = $service->invitations->create($invite);
				return $createinvite;
		    }else{
		    	return FALSE;
		    }
			
		}else{
			return FALSE;
		}
	}

	public function getUserProfileDetails($userId = null){
	  	$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

	    if (!is_null($userId)) {
			$usersdetails = $service->userProfiles->get($userId);
			return $usersdetails;
	    }else{
	    	return FALSE;
	    }
	    	
	}

	/*
	* @function get Students Lists from course
	*/

	public function getStudentsLists($course_id = null){

		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

		$optParams = array(
		    'courseId' => $course_id
		);
		$response = $service->courses_students->listCoursesStudents($course_id);
		return $response;
	}

	/*
	* @function get teachers lists
	*/

	public function getTeachersLists($course_id = null){
		
		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

		$optParams = array(
		    'courseId' => $course_id
		);
		$response = $service->courses_teachers->listCoursesTeachers($course_id);
		return $response;

	}


	/*
	* @function creates Topics In Courses
	*/

	public function createTopicsForCourse($courseid = null, $topicName = null){
		
		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

	    $newtopic = new Google_Service_Classroom_Topic(
	    	array('courseId' => $courseid, 'name' => $topicName )
	    ); 
	    $newlycreatetopic = $service->courses_topics->create($courseid, $newtopic);
	    return $newlycreatetopic;
	}


	/*
	* @function return lists of topics in course.
	*/

	public function gettopicsLists($courseid = null){


		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());

	    $newtopic = new Google_Service_Classroom_Topic(
	    	array('courseId' => $courseid)
	    ); 
	    $newlycreatetopic = $service->courses_topics->listCoursesTopics($courseid);
	    return $newlycreatetopic;	
	}


	/* 
	* @function update the topics
	*/

	public function updateTopic($courseid = null, $topicid= null, $topicName){
		
		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());
	    $optParams = array("updateMask" => "name" );

	    $newtopic = new Google_Service_Classroom_Topic(
	    	array('courseId' => $courseid, 'name' => $topicName)
	    ); 
	    $newlycreatetopic = $service->courses_topics->patch($courseid,$topicid,$newtopic, $optParams);
	    return $newlycreatetopic;
	}

	/*
	* @function get the topic list 
	*/

	public function getTopic($courseid = null, $topicid = null){

		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());
	    $newtopic = new Google_Service_Classroom_Topic(
	    	array('courseId' => $courseid)
	    ); 

	    $newlycreatetopic = $service->courses_topics->get($courseid, $topicid);
	    return $newlycreatetopic;
	}


	/*
	* @functions Get invitation List By Users.
	*/

	public function getInvitationsList(){
		$request = \Drupal::request();
		$session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());
	}


	/*
	* @functions  save class with id enroll code.
	*/

	public function saveClassidWithEnrollcode(){
		$tablename = "googleclassroomsaveclasswithid";
        $database = \Drupal::database();
        $tablePrefix = \Drupal::database()->getconnectionOptions()['prefix']['default'];
        $table =  $tablePrefix.$tablename;
        if (!\Drupal::database()->schema()->tableExists($tablename)) {
            $sql = "CREATE TABLE $table (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            classid VARCHAR(255) NOT NULL,
            enrollmentcode VARCHAR(255) NOT NULL,
            created VARCHAR(30) NOT NULL
            )";
          $database->query($sql);
        }
	}

	public function saveEnrollCodeWithCourseid($classid = null,$enrollmentcode = null){

		$tablename = "googleclassroomsaveclasswithid";
        $database = \Drupal::database();
        $tablePrefix = \Drupal::database()->getconnectionOptions()['prefix']['default'];
        $table =  $tablePrefix.$tablename;

		$result = $database->insert($tablename)
          ->fields([
            'classid' => $classid,
            'enrollmentcode' => $enrollmentcode,
            'created' => \Drupal::time()->getRequestTime(),
          ])
          ->execute();
	}


	public function viewEnrollCodeWithCourseid($enrollmentcode = null){

		if (!is_null($enrollmentcode)) {
			$tablename = "googleclassroomsaveclasswithid";
	        $database = \Drupal::database();
	        $tablePrefix = \Drupal::database()->getconnectionOptions()['prefix']['default'];
	        $table =  $tablePrefix.$tablename;
	        $database = \Drupal::database();
	        try{
	        	$query = $database->query("SELECT * FROM {$table} Where enrollmentcode = '$enrollmentcode'");
	        	$result = $query->fetchObject();
		        if ($result) {
		          return $result;
		        }else{ return FALSE; }

	        }catch(DatabaseExceptionWrapper $e){
	        	if ($e->getCode()) {
	        		\Drupal::messenger()->addError($e->getMessage());
	        	}
	        }
		}else{
			return FALSE;
		}
	}

	public function checkForEnrollment($courselist = array()){
		if (!empty($courselist) && count($courselist) > 0) {
			foreach ($courselist as  $courses) {
				if (!($this->viewEnrollCodeWithCourseid($courses->enrollmentCode))) {
					$this->saveEnrollCodeWithCourseid($courses->id, $courses->enrollmentCode);
				}
			}

		}else{
			return FALSE;
		}
	}

	/*
	* @function createCourseWork, Function adds the functionality to add course works question assignments..
	*/

	public function createCourseWork(
		$courseid = null,
		$title = null,
		$description=null,
		$state=null,
		$workType=null,
		$due_date=null,
		$coursetopics=null,
		$assigneeMode=null,
		$students_ids=array(),
		$choices_fieldset=array(),
		$time,
		$maxPoints){


		$data_array = array(
    		  "title" => $title,
    		  "description" => $description,
    		  "workType" => $workType,
			  "state" =>  $state,
			  "topicId"=> $coursetopics,
			  "assigneeMode" => $assigneeMode
	    	 );

		if (!empty($students_ids)) {
			$data_array['individualStudentsOptions'] = new Google_Service_Classroom_IndividualStudentsOptions(
				array('studentIds' => array_values($students_ids))
			);
		}

		if (!is_null($maxPoints)) {
			$data_array['maxPoints'] = $maxPoints;
		}

		if (!empty($choices_fieldset)) {
		 	$data_array['multipleChoiceQuestion'] = new Google_Service_Classroom_MultipleChoiceQuestion(
		 		array( 'choices' => $choices_fieldset)
		 	);
		}

		if (!is_null($due_date)) {	
			$data_array['dueDate'] = new Google_Service_Classroom_Date(
				array(
					'day' => ltrim(date('d',strtotime($due_date)), "0"), 
					"month" => ltrim(date('m',strtotime($due_date)), "0"), 
					"year" => ltrim(date('Y',strtotime($due_date)), "0")  
				)
			);
	
			if (!is_null($time)) {
				$data_array['dueTime'] = new Google_Service_Classroom_TimeOfDay(
					array(
						'hours' => ltrim(date('H',strtotime($time)), "0"), 
						"minutes" => ltrim(date('i',strtotime($time)), "0") , 
						"nanos" => 0, 
						"seconds" => 0 
					)
				);
			}
		}

		$request = \Drupal::request();
		$session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());
	    $optional_params = array();

	    $courseWorks =  new Google_Service_Classroom_CourseWork(
	    	$data_array
	    );

	    $course_work = $service->courses_courseWork->create($courseid, $courseWorks , $optional_params);
	    return $data_array;
	}

	/*
	* @function getCourse works
	*/

	public function getCourseWorkList($courseId = null){

		$request = \Drupal::request();
	    $session = $request->getSession();
	    $session->set('destinationUrl', \Drupal::service('path.current')->getPath());
	    $gooleapiclient = new GoogleApiClient;
	    $service = new Google_Service_Classroom($gooleapiclient->getGoogleAccessToken());
	    $courseWork = new Google_Service_Classroom_ListCourseWorkResponse(
	    	array('courseId' => $courseId)
	    ); 

	    $get_CourseWork_List = $service->courses_courseWork->listCoursesCourseWork($courseId, $optParams = array());
	    return $get_CourseWork_List;
	}



  }

