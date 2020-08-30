<?php 

/*
* @class return the details about invitations.
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
 * @class Invitations.
 */
class Invitations
{
	var $googleclassroom;
	var $returninvitations;
	
	function __construct()
	{

		$this->googleclassroom = new GoogleClassController;
		$this->returninvitations = new stdClass;	
	}
	/*
	* @function shows the invitations 
	*/
	public function index(){

		print_r("HELLO");
		die;
		// return "HELLO WORLD";
	}


}

