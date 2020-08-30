<?php

namespace Drupal\google_class_room\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\google_class_room\Controller\GoogleApiClient;

/**
 * Callback class uses for redirect call back url from google auth. 
 *
 */
class Callback
{

	
	public function callbackUrl(Request $request){
		$googleApiClient = new GoogleApiClient;
			/* set code and redirect to class room index page. */
		

		if (isset($_GET['code'])) {
			$googleApiClient->setTokentoClientUsingAuthCode($request);
		}
		
		$session = $request->getSession();
		$destinationUrl = $session->get('destinationUrl');
		if ($session->get('destinationUrl') != "" ) {
			$session->remove('destinationUrl');
		}

		if ($destinationUrl != "") {
			$response = new RedirectResponse($destinationUrl);
			$response->send();
			return;
		}else{
			$host = \Drupal::request()->getSchemeAndHttpHost();
			$response = new RedirectResponse($host.'/googleclassroom');
	        $response->send();	
			return;
		}

		if ($request->get('error')) {
			if ($request->get('error') == 'access_denied') {
	        \Drupal::messenger()->addError($this->t('You denied access so account is not authenticated'));
	      }
	      else {
	        \Drupal::messenger()->addError($this->t('Something caused error in authentication.'));
	      }
		}
		return;
	}
}
