<?php
/*
Request Push API information can be found at:
http://www.userscape.com/helpdesk/index.php?pg=kb.page&id=153

ABOUT THIS REQUEST PUSH SCRIPT
- This Request Push will send a comment into Campfire. This can be used from the request page or may be more useful from an
- automation rule (as of HS version 2.6). 

INSTALLATION
- Place this file in your /custom_code folder
- Modify the variables below with your URL, Room ID and account information

REQUIREMENTS
- PHP cURL Library installed in PHP
- PHP 5.2.1+ better, though any version should be OK as long as you have upload_tmp_dir set. If not, see the trouble note below

TROUBLESHOOTING NOTE: 
- If you have trouble with this script try hardcoding a temp path in $cookiefile below as the script requires a place to write a file.

EXPANSION IDEAS
- You could log the full history of a request, status information, etc
*/

// SECURITY: This prevents this script from being called from outside the context of HelpSpot
if (!defined('cBASEPATH')) die();

class RequestPush_Campfire{
	
	//Campfire API variables 
	//MODIFY THESE FOR YOUR ACCOUNT
	var $campfire_url		= '';	//The full URL to your account. ex: http://[account].campfirenow.com
	var $campfire_username	= ''; 	//The Campfire user you want to log messages as. (usually an email address)
	var $campfire_password	= ''; 	//The users Campfire password
	var $campfire_roomid	= '';	//The room ID we want to log to. You can find this in the URL of the room when you enter it. ex: /room/182650  - 182650 is the ID
	
	/*  Private variables  */
	
	//Errors
	var $errorMsg = "";
	
	function push($request){		
		//Campfire cookie file, where the login cookie is stored
		$cookiefile = (ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir()) . '/campfire_cookie.txt';
		
		//Login to Campfire
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->campfire_url . '/login');
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile); 
	 	
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'email_address' => $this->campfire_username,
			'password' => $this->campfire_password
		));
		
		curl_exec($ch);
		curl_close($ch);

		//Modify the comment to include a request ID
		$comment = "Request: " . $request['xRequest'] . "\n---------------\n" . $request['staff_comment'];
		
		//Log the comment to the room.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->campfire_url . '/room/'.$this->campfire_roomid.'/speak');
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, '/room/'.$this->campfire_roomid);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile); 
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'message' => $comment,
			'paste' => 'true'
		));
		
		curl_exec($ch);
		curl_close($ch);	
		
		//No ID to return
		return '';
	}
	
	function details($id){
		/* This is not applicable to Campfire */
	}

}
?>