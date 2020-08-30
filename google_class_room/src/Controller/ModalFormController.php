<?php

namespace Drupal\google_class_room\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;

/**
 * ModalFormExampleController class.
 */
class ModalFormController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Callback for opening the modal form.
   */
  public function openModalForm() {
    $response = new AjaxResponse();
    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\google_class_room\Form\ModalForm');
    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand('Add Class', $modal_form, ['width' => '800']));
    return $response;
  }

  /*
  * @function Opens Update Class Modal Form
  */

  public function openUpdateClassModalForm(){
    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm('Drupal\google_class_room\Form\UpdateClassModalForm');
    $response->addCommand(new OpenModalDialogCommand(' Update Class', $modal_form, ['width' => '800']));
    return $response;
  }

  /*
  * @function add topics 
  */

  public function addtopicsForm(){
    $response = new AjaxResponse();
    $addtopicsform = $this->formBuilder->getForm('Drupal\google_class_room\Form\AddTopics');
    $response->addCommand(new OpenModalDialogCommand('Add Topics' , $addtopicsform, ['wisth' => '800']));
    return $response;
  }

}