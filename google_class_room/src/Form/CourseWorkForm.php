<?php

namespace Drupal\google_class_room\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\google_class_room\Controller\GoogleClassController;
use Google_Service_Exception;
use stdClass;

class CourseWorkForm extends FormBase {

  var $googleclassroom;
  var $topicListsFromClassroom;
  var $studentsListsFromClassroom;
  var $courseid;

  function __construct()
  {
    $this->googleclassroom = new GoogleClassController;
    $this->topicListsFromClassroom = array();
    $this->studentsListsFromClassroom = array();
    $this->courseid = NULL;
  }

  public function getFormId() {
    return 'course_work_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL, $courseid = null) {
      /*
      * Set course id.
      */
      if (!is_null($courseid)) {
        $this->courseid = $courseid;
      }
      /*
      * Fetch All Topics from Class room.
      */
      try{
          $topicLists = $this->googleclassroom->gettopicsLists($courseid)->getTopic();
          if (!empty($topicLists)&&count($topicLists)>0) {
            foreach ($topicLists as $topics) {
              $this->topicListsFromClassroom[$topics->getTopicId()] = $topics->getName(); 
            }
          }
        }
        catch(Google_Service_Exception $e){
          if ($e->getCode()) {
            $message = json_decode($e->getMessage())->error->message;
                \Drupal::messenger()->addError($message);
          }
        }
     /*
     *  Fetch All Students from classroom
     */   

      try{
      $studentsLists = $this->googleclassroom->getStudentsLists($courseid)->students;        
        if (!empty($studentsLists) && count($studentsLists) > 0) {
            foreach ($studentsLists as $student) {
              $this->studentsListsFromClassroom[$student->getProfile()->emailAddress] = $student->profile->name->fullName;
            }
          } 
      } catch (Google_Service_Exception $e){
        if ($e->getCode()) {
          $message = json_decode($e->getMessage())->error->message;
            \Drupal::messenger()->addError($message);
        }else{
          throw $e;
        }
      }

      $form['#prefix'] = '<div id="course_work">';
      $form['#suffix'] = '</div>';
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];

      $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<div id="result-message"></div>'
      ];

      $client_secret="";
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#placeholder' => 'Please Enter Title ',
      ];
      $form['description'] = array(
        '#type' => 'text_format',
        '#title' => t('Description'),
        '#placeholder' => 'Please Enter Description',
      );

      $form['state'] = [
        '#type' => 'select',
        '#empty_value' => '',
        '#title' => $this->t('Course State'),
        '#empty_option' => '- Select a state -',
        '#options' => [
          'PUBLISHED' => 'PUBLISHED',
          'DRAFT' => 'DRAFT',
          'DELETED' => 'DELETED'
        ],
      ];

      $form['coursetopics'] = [
        '#type' => 'select',
        '#title' => $this->t('Topic'),
        '#empty_value' => '',
        '#empty_option' => '- Select a Topic -',
        '#options' => $this->topicListsFromClassroom,
      ];

      $form['maxPoints'] = [
        '#type' => 'number',
        '#placeholder' => 'Enter max points in number',
        '#title' => 'Max Grade Points',

      ];


      $form['assigneeMode'] = [
        '#type' => 'select',
        '#title' => $this->t('Assign to'),
        '#empty_value' => '',
        '#empty_option' => '- Select a assignee mode -',
        '#options' => [
          'ALL_STUDENTS' => 'ALL STUDENTS',
          'INDIVIDUAL_STUDENTS' => 'INDIVIDUAL STUDENTS'
        ],
      ]; 

      $form['students_ids'] = array(
        '#type' => 'select',
        '#multiple' => TRUE,
        '#title' => t('Select Students'),
        '#required' => FALSE,
        '#options' => $this->studentsListsFromClassroom,
        '#size' => 5,
        '#attributes' => [
          'id' => 'Students-ids',
        ],
        '#states' => [
          'visible' => [
            ':input[name="assigneeMode"]' => ['value' => 'INDIVIDUAL_STUDENTS'],
          ],
        ],
      );

      $form['workType'] = [
        '#type' => 'select',
        '#title' => $this->t('Select work type'),
        '#description' => 'Select type of course work eg:- Assignment, short answer question, multiple choice question',
        '#empty_value' => '',
        '#empty_option' => '- Select a work type -',
        '#options' => [
          'ASSIGNMENT' => 'ASSIGNMENT',
          'SHORT_ANSWER_QUESTION' => 'SHORT ANSWER QUESTION',
          'MULTIPLE_CHOICE_QUESTION' => 'MULTIPLE CHOICE QUESTION',
        ],
      ];

      $format = 'd-m-y';
      $form['due_date'] = array(
        '#title' => t('Due Date'),
        '#type' => 'date',
        '#date_format' => $format,
      );

      $form['time'] = [
        '#type' => 'datetime',
        '#title' => $this->t('Time'),
        '#size' => 20,
        '#date_date_element' => 'none', // hide date element
        '#date_time_element' => 'time', // you can use text element here as well
        '#date_time_format' => 'H:i',
        '#default_value' => '00:00',
      ]; 

      $i = 0;
      $name_field = $form_state->get('choices_names');
      $form['#tree'] = TRUE;
      $form['choices_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Choices'),
        '#prefix' => '<div id="names-fieldset-wrapper">',
        '#suffix' => '</div>',
        '#states' => [
          'visible' => [
            ':input[name="workType"]' => ['value' => 'MULTIPLE_CHOICE_QUESTION'],
          ],
        ],
      ];

      if (empty($name_field)) {
          $name_field = $form_state->set('choices_names', 1);
      }

      if ($form_state->get('choices_names')>0) {
          $value = $form_state->get('choices_names');
      }
      else {
          $value=1;
      }

      for ($i = 0; $i < $value; $i++) {
        $form['choices_fieldset']['name'][$i] = [
          '#type' => 'textfield',
          '#title' => t('Choice Name'),
        ];
      }

      $form['actions'] = array('#type' => 'actions');

      $form['choices_fieldset']['actions']['add_name'] = [
        '#type' => 'submit',
        '#value' => t('Add one more'),
        '#submit' => array('::addOne'),
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'names-fieldset-wrapper',
        ],
      ];

      if ($value>1) {
        $form['choices_fieldset']['actions']['remove_name'] = [
          '#type' => 'submit',
          '#value' => t('Remove one'),
          '#submit' => array('::removeCallback'),
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => 'names-fieldset-wrapper',
          ]
        ];
      }

      $form['actions']['send'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add Course Work'),
        '#attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
        '#ajax' => [
          'callback' => [$this, 'AddCourseWork'],
          'event' => 'click',
        ],
      ];

    return $form;
  }


  public function AddCourseWork(array $form, FormStateInterface $form_state) {
        $students_ids = array();
        $choices_fieldset = array();

    if (!is_null($this->courseid)) {
        $title = $form_state->getValue('title');
        $description = $form_state->getValue('description');
        $description = strip_tags($description['value']);
        $state = $form_state->getValue('state');
        $coursetopics = $form_state->getValue('coursetopics');
        $assigneeMode = $form_state->getValue('assigneeMode');
        $time = $form_state->getValue('time');
        $maxPoints = $form_state->getValue('maxPoints');

        if ($assigneeMode == 'INDIVIDUAL_STUDENTS') {
            $students_ids = $form_state->getValue('students_ids');
        }

        $workType = $form_state->getValue('workType');

        if ($workType == 'MULTIPLE_CHOICE_QUESTION') {
            $choices_fieldset = $form_state->getValue('choices_fieldset');
        }
        $due_date = $form_state->getValue('due_date');
          
        try{
          $classworkadd = $this->googleclassroom->createCourseWork(
            $this->courseid,
            $title,
            $description,
            $state,
            $workType,
            $due_date,
            $coursetopics,
            $assigneeMode,
            $students_ids,
            $choices_fieldset['name'],
            $time,
            $maxPoints
          );
 
          if ($classworkadd) {
            \Drupal::messenger()->addMessage($this->t('Form Submitted Successfully'), 'status', TRUE);
            $message = ['#theme' => 'status_messages','#message_list' => drupal_get_messages(),];
            $messages = \Drupal::service('renderer')->render($message);
            $response = new AjaxResponse();
            $response->addCommand(new HtmlCommand('#result-message', $messages));
            return $response;
          }
        }catch(Google_Service_Exception  $e){
          if ($e->getCode()) {
            $message = json_decode($e->getMessage())->error->message;
               \Drupal::messenger()->addMessage($message, 'status', TRUE);
                $message = ['#theme' => 'status_messages','#message_list' => drupal_get_messages(),];
                $messages = \Drupal::service('renderer')->render($message);
                $response = new AjaxResponse();
                $response->addCommand(new HtmlCommand('#result-message', $messages));
                return $response;
          }
        }
    }
  }


  public function validateForm(array &$form, FormStateInterface $form_state) {}

  public function submitForm(array &$form, FormStateInterface $form_state) {}

  protected function getEditableConfigNames() {
    return ['config.course_work_form'];
  }

  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('choices_names');
    $add_button = $name_field + 1;
    $form_state->set('choices_names', $add_button);
    $form_state->setRebuild();
  }

  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('choices_names');
    return $form['choices_fieldset'];
  }

  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('choices_names');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('choices_names', $remove_button);
    }
   $form_state->setRebuild();
  }


}