<?php
/*
This sample Request Push file was submitted to UserScape by Tara Kelly of Passpack (http://www.passpack.com)

Request Push API information can be found at:
http://www.userscape.com/helpdesk/index.php?pg=kb.page&id=153
*/

// SECURITY: This prevents this script from being called from outside the context of HelpSpot
if (!defined('cBASEPATH')) die();

class RequestPush_ActiveCollab{

  	//ActiveCollab API variables
	//MODIFY THESE FOR YOUR ACCOUNT
 	var $acollab_projid = '';	//The project ID we want to add a ticket to. You can find this in the URL of the project when you enter it. ex: /projects/16/  - 16 is the ID
	var $acollab_token	= '';	// API token. Different for each ActiveCollab user. Create a user fo the API, then grab the API Token under User Profile > Settings
	var $acollab_url	= '';	// Find it in the same place you find your API token.


  /*  Private variables  */

    // Errors
	var $errorMsg = "";


	function push($request){

		//Modify the comment to include a request ID
		$comment = "Request: " . $request['xRequest'] . " | " . $request['staff_comment'];

		/*  Send it  */
		$ch = curl_init();
		
		// ActivCollab wants the pathinfo and token in the URL, not in the POST info.
		curl_setopt($ch, CURLOPT_URL, $this->acollab_url . '?path_info=/projects/'.$this->acollab_projid.'/tickets/add&token='.$this->acollab_token);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    	// The POST data
    	// submitted=submitted is a quirk in ActivCollab API. Ask no questions, just has to be there.
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'ticket[name]' => $comment,
			'submitted' => 'submitted'
		));

		curl_exec($ch);
		curl_close($ch);

		//No ID to return
		return '';


	}
	
	function details($id){
		/* Retrieve update about push using $id here */
		
		/*
		return "HTML"; //Return HTML to be displayed within HelpSpot
		 .... too lazy to do this. Maybe some other day...
		*/	
	}
}

?>