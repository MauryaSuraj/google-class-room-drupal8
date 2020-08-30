<?php

namespace Drupal\google_class_room\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Role;
use Drupal\user\UserInterface;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\google_class_room\Controller\GoogleApiClient;
/**
 * Class CredentailSetting.
 */
class CredentailSetting extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'credentailsetting';
  }

  /**
   * {@inheritdoc}
   */

  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form = parent::buildForm($form, $form_state);
    $clientID = $project_id = $client_secret = $redirect_uris = NULL;
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $redirect_uri = $host . '/googleclassroom/callbackurl';

    $googleapiClient = new GoogleApiClient;

    if (file_exists($googleapiClient->getCredentials()) && filesize($googleapiClient->getCredentials())) {
       $jsondata = json_decode(file_get_contents($googleapiClient->getCredentials(), true)); 
        $tmp = (array) $jsondata;
        if (!empty($tmp) && property_exists($jsondata, "web")) {
          $clientdata = $jsondata->web;

          if (property_exists($clientdata, "client_id")) {
            $clientID = $clientdata->client_id;
          }

          if (property_exists($clientdata, "project_id")) {
            $project_id = $clientdata->project_id;
          }

          if (property_exists($clientdata, "client_secret")) {
            $client_secret = $clientdata->client_secret;
          }

          if (property_exists($clientdata, "redirect_uris")) {
            $redirect_uris = $clientdata->redirect_uris;
          }

        }
    }

      $form['client_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client Id'),
        '#default_value' => $clientID,
        '#placeholder' => 'Please Enter client',
      ];

      $form['project_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Project Id'),
        '#default_value' => $project_id,
        '#placeholder' => 'Please Enter project id from ',
      ];

      $form['client_secret'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client Secrect'),
        '#default_value' => $client_secret,
        '#placeholder' => 'Please Enter client Secrect',
      ];

      $form['redirect_uris'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Redirect url'),
        '#default_value' => $redirect_uri,
        '#attributes' => array('readonly' => 'readonly'),
      ];


       $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t(' Submit '),
        '#weight' => 1,
      ];

   
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  	$googleapiClient = new GoogleApiClient;
    $clientpredata = new \stdClass();
    $clientpredata->client_id = $form_state->getValue('client_id');
    $clientpredata->project_id = $form_state->getValue('project_id');
    $clientpredata->auth_uri = "https://accounts.google.com/o/oauth2/auth";
    $clientpredata->token_uri = "https://oauth2.googleapis.com/token";
    $clientpredata->auth_provider_x509_cert_url = "https://www.googleapis.com/oauth2/v1/certs";
    $clientpredata->client_secret = $form_state->getValue('client_secret'); 
    $clientpredata->redirect_uris = array($form_state->getValue('redirect_uris'));
    $clientdata = new \stdClass();
    $clientdata->web = $clientpredata;
    if (file_exists($googleapiClient->getCredentials()) && filesize($googleapiClient->getCredentials())) {
      unlink($googleapiClient->getCredentials());
    if (file_put_contents($googleapiClient->getCredentials(), json_encode($clientdata))) {
        \Drupal::messenger()->addMessage("Credentails Updated");
      }else{
        \Drupal::messenger()->addMessage("Check File Permission " .$googleapiClient->getCredentials());
      }  
    }else{
      // create file
      if (file_put_contents($googleapiClient->getCredentials(), json_encode($clientdata))) {
        \Drupal::messenger()->addMessage("Credentails Updated");
      }
    }
    return;
  }

}
