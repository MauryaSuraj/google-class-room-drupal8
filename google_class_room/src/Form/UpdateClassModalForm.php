<?php

namespace Drupal\google_class_room\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\google_class_room\Controller\GoogleClassController;
use stdClass;

/**
 * SendToDestinationsForm class.
 */
class UpdateClassModalForm extends FormBase {



  var $googleclassroom;
  var $returncoursedetails;

  function __construct()
  {
    $this->googleclassroom = new GoogleClassController;
    $this->returncoursedetails = new stdClass;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_class_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {

    $classid = \Drupal::request()->query->get('classid');
    
    if (!is_null($classid)) {
      $coursedetailsformid = $this->googleclassroom->getCourseDetails($classid);
      $this->returncoursedetails->name = $coursedetailsformid->name;
      $this->returncoursedetails->id = $coursedetailsformid->id;
      $this->returncoursedetails->description = str_replace("&nbsp;", ' ', $coursedetailsformid->description);
      $this->returncoursedetails->section = $coursedetailsformid->section;
      $this->returncoursedetails->descriptionHeading = $coursedetailsformid->descriptionHeading;
    }




    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['#prefix'] = '<div id="updateclass_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

      $client_secret="";
      $form['classname'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' =>  $this->returncoursedetails->name,
        '#placeholder' => 'Please Enter Class Name',
      ];

      $form['sectionname'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Section'),
        '#default_value' => $this->returncoursedetails->section,
        '#placeholder' => 'Please Enter Section name',
      ];

      $form['descriptionHeading'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Description Heading'),
        '#default_value' => $this->returncoursedetails->descriptionHeading,
        '#placeholder' => 'Please Enter Description Heading',
      ];

      $form['description'] = array(
        '#type' => 'text_format',
        '#title' => t('Description'),
        '#default_value' => $this->returncoursedetails->description,
        '#placeholder' => 'Please Enter Description',
      );

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

    if ($this->returncoursedetails->id && $classname && $sectionname && $descriptionHeading && strip_tags($description['value'])) {
        $details = strip_tags($description['value']);
        $id = $this->returncoursedetails->id;
        $classupdate = $this->googleclassroom->updateGoogleClassCourse($id,$classname,$sectionname,$descriptionHeading,$details);
        if ($classupdate) {
          print_r($classupdate);
        }
    }
    

    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_example_form', $form));
    }
    else {
      $response->addCommand(new OpenModalDialogCommand("Success!", 'The modal form has been submitted.', ['width' => 700]));
    }

    return $response;
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
    return ['config.update_class_modal_form'];
  }

}