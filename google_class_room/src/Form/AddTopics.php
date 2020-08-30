<?php

namespace Drupal\google_class_room\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\google_class_room\Controller\GoogleClassController;
use Google_Service_Exception;

/**
 * SendToDestinationsForm class.
 */
class AddTopics extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modal_form_add_topics_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
  	$classid = \Drupal::request()->query->get('classid');
  	$topicid = \Drupal::request()->query->get('topicid');
  	$topicname = "";
  	if (!is_null($topicid) && !is_null($classid)) {
  		$googleclassroom = new GoogleClassController;
  		$topic = $googleclassroom->getTopic($classid,$topicid);
  		$topicname =  $topic->getName();
  	}

    $form['#prefix'] = '<div id="modal_example_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div id="result-message"></div>'
    ];

      $form['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => $topicname,
        '#placeholder' => 'Please Enter Topic Name',
      ];



    if (!is_null($topicid) && !is_null($classid)) {
			 $form['actions'] = array('#type' => 'actions');
		    
		    $form['actions']['send'] = [
		      '#type' => 'submit',
		      '#value' => $this->t('Update Topic'),
		      '#attributes' => [
		        'class' => [
		          'use-ajax',
		        ],
		      ],
		      '#ajax' => [
		        'callback' => [$this, 'updateTopic'],
		        'event' => 'click',
		      ],
		    ];    	
    }else{

	    $form['actions'] = array('#type' => 'actions');
	    $form['actions']['send'] = [
	      '#type' => 'submit',
	      '#value' => $this->t('Create Topic'),
	      '#attributes' => [
	        'class' => [
	          'use-ajax',
	        ],
	      ],
	      '#ajax' => [
	        'callback' => [$this, 'submitModalFormAjax'],
	        'event' => 'click',
	      ],
	    ];    	
    }

    // $form['#attached']['library'][] = 'google_class_room/googlecssclass';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';


    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    
    $name = $form_state->getValue('name');
    $googleclassroom = new GoogleClassController;
    if ($name) {
        try{
          $classid = \Drupal::request()->query->get('classid');
          $classcreate = $googleclassroom->createTopicsForCourse($classid,$name);
          if ($classcreate) {
             \Drupal::messenger()->addMessage($this->t('Form Submitted Successfully'), 'status', TRUE);
            $message = ['#theme' => 'status_messages','#message_list' => drupal_get_messages(),];
            $messages = \Drupal::service('renderer')->render($message);
            $response = new AjaxResponse();
            $response->addCommand(new HtmlCommand('#result-message', $messages));
            return $response;
          }
        }catch(Google_Service_Exception $e){
          if ($e->getCode()) {
            /* Prinnts */
            $message = json_decode($e->getMessage())->error->message;
              \Drupal::messenger()->addMessage($message, 'status', TRUE);
            $message = ['#theme' => 'status_messages','#message_list' => drupal_get_messages(),];
            $messages = \Drupal::service('renderer')->render($message);
            $response = new AjaxResponse();
            $response->addCommand(new HtmlCommand('#result-message', $messages));
            return $response;

          }
        }        
    }else{
            $message = "All Fields are mandatory";
              \Drupal::messenger()->addMessage($message, 'status', TRUE);
            $message = ['#theme' => 'status_messages','#message_list' => drupal_get_messages(),];
            $messages = \Drupal::service('renderer')->render($message);
            $response = new AjaxResponse();
            $response->addCommand(new HtmlCommand('#result-message', $messages));
            return $response;

    }
  }

  public function updateTopic(array $form, FormStateInterface $form_state){
  	    $name = $form_state->getValue('name');
    $googleclassroom = new GoogleClassController;
    if ($name) {
        try{
          $classid = \Drupal::request()->query->get('classid');
          $topicid = \Drupal::request()->query->get('topicid');
          $classcreate = $googleclassroom->updateTopic($classid,$topicid,$name);
          if ($classcreate) {
             \Drupal::messenger()->addMessage($this->t('Form Submitted Successfully'), 'status', TRUE);
            $message = ['#theme' => 'status_messages','#message_list' => drupal_get_messages(),];
            $messages = \Drupal::service('renderer')->render($message);
            $response = new AjaxResponse();
            $response->addCommand(new HtmlCommand('#result-message', $messages));
            return $response;
          }
        }catch(Google_Service_Exception $e){
          if ($e->getCode()) {
            /* Prinnts */
            $message = json_decode($e->getMessage())->error->message;
              \Drupal::messenger()->addMessage($message, 'status', TRUE);
            $message = ['#theme' => 'status_messages','#message_list' => drupal_get_messages(),];
            $messages = \Drupal::service('renderer')->render($message);
            $response = new AjaxResponse();
            $response->addCommand(new HtmlCommand('#result-message', $messages));
            return $response;

          }
        }        
    }else{
            $message = "All Fields are mandatory";
              \Drupal::messenger()->addMessage($message, 'status', TRUE);
            $message = ['#theme' => 'status_messages','#message_list' => drupal_get_messages(),];
            $messages = \Drupal::service('renderer')->render($message);
            $response = new AjaxResponse();
            $response->addCommand(new HtmlCommand('#result-message', $messages));
            return $response;

    }
  }



  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.modal_form_add_topics_modal_form'];
  }

}