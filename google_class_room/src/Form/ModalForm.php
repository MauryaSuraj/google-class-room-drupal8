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
class ModalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modal_form_example_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
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

      $client_secret="";
      $form['classname'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => $client_secret,
        '#placeholder' => 'Please Enter Class Name',
      ];

      $form['sectionname'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Section'),
        '#default_value' => $client_secret,
        '#placeholder' => 'Please Enter Section name',
      ];

      $form['descriptionHeading'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Description Heading'),
        '#default_value' => $client_secret,
        '#placeholder' => 'Please Enter Description Heading',
      ];

        $form['description'] = array(
        '#type' => 'text_format',
        '#title' => t('Description'),
        '#placeholder' => 'Please Enter Description',
      );

     $form['courseState'] = [
        '#type' => 'select',
        '#empty_value' => '',
        '#empty_option' => '- Select a value -',
        '#options' => [
          'PROVISIONED' => 'PROVISIONED',
          'ACTIVE' => 'ACTIVE',
        ],
      ]; 



    $form['actions'] = array('#type' => 'actions');
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Class'),
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

    // $form['#attached']['library'][] = 'google_class_room/googlecssclass';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';


    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    
    $classname = $form_state->getValue('classname');
    $sectionname = $form_state->getValue('sectionname');
    $descriptionHeading = $form_state->getValue('descriptionHeading');
    $description = $form_state->getValue('description');
    $courseState = $form_state->getValue('courseState');

    $googleclassroom = new GoogleClassController;
    if ($classname && $sectionname && $descriptionHeading && strip_tags($description['value']) && $courseState) {
        $details = strip_tags($description['value']);
        $room = rand(pow(10, 2), pow(10, 3)-1);
        try{
          $classcreate = $googleclassroom->createGoogleClassRoomCourse($classname,$sectionname,$descriptionHeading,$details,$room,$courseState);

          if ($classcreate) {
            
             \Drupal::messenger()->addMessage($this->t('Form Submitted Successfully'), 'status', TRUE);
            $message = ['#theme' => 'status_messages','#message_list' => drupal_get_messages(),];
            $messages = \Drupal::service('renderer')->render($message);
            $response = new AjaxResponse();
            $response->addCommand(new HtmlCommand('#result-message', $messages));
            return $response;
           
          }

        }catch(Google_Service_Exception $e){
          if ($e->getCodes()) {
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
    return ['config.modal_form_example_modal_form'];
  }

}