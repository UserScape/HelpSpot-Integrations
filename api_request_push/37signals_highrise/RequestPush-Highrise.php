<?php
/*
Request Push for: 37 Signals Highrise API
PHP Versions required: 5.1.0+
PHP Modules required: curl
Notes: This implementation uses the comment supplied by the staff member as the person's background in Highrise
*/

/*
Request Push API information can be found at:
http://www.userscape.com/helpdesk/index.php?pg=kb.page&id=153
*/

// SECURITY: This prevents this script from being called from outside the context of HelpSpot
if (!defined('cBASEPATH')) die();

class RequestPush_Highrise{

	//FogBugz API variables 
	//MODIFY THESE FOR YOUR INSTALLATION
	var $highrise_url = ''; //Your Highrise URL, something like http://userscape.highrisehq.com
	var $api_token = ''; //You can find this in your Highrise account in the "my info" screen
	
	/*  Private variables  */
	
	var $errorMsg = "";
	
	function push($request){
		//Check that person doesn't already exist
		if(!$this->_person_in_highrise($request)){
			//URL to POST data to
			$curl = curl_init($this->highrise_url.'/people.xml');
			
			//Return XML don't output it
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	
			//Set Basic auth information
			curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x'); //Username (api token, fake password as per Highrise api)
			
			//Setup XML to POST
			curl_setopt($curl,CURLOPT_HTTPHEADER,Array("Content-Type: application/xml"));
			curl_setopt($curl,CURLOPT_POST,true);
			curl_setopt($curl,CURLOPT_POSTFIELDS,'<person>
				<first-name>'.htmlspecialchars($request['sFirstName']).'</first-name>
				<last-name>'.htmlspecialchars($request['sLastName']).'</last-name>
				<background>'.htmlspecialchars($request['staff_comment']).'</background>
				<company-name>'.htmlspecialchars($request['sUserId']).'</company-name>
				<contact-data>
					<email-addresses>
						<email-address>
							<address>'.htmlspecialchars($request['sEmail']).'</address>
							<location>Work</location>
						</email-address>
					</email-addresses>
				<phone-numbers>
					<phone-number>
						<number>'.htmlspecialchars($request['sPhone']).'</number>
						<location>Work</location>
					</phone-number>
				</phone-numbers>
				</contact-data>
			</person>');
	
			//Don't verify for SSL if you have an SSL Highrise account
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
	
			//Call Highrise
			$xml = curl_exec($curl);
			curl_close($curl);		
		
		}else{
			$this->errorMsg = "Person already in Highrise";
		}
		
		//We don't return an ID since it doesn't make sense to track this with the request for Highrise.
		return '';
	}
	
	//Search for a person in Highrise 
	function _person_in_highrise($person){
		//URL to search people
		$curl = curl_init($this->highrise_url.'/people/search.xml?term='.urlencode($person['sFirstName'].' '.$person['sLastName']));

		//Return XML don't output it
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	
		//Set Basic auth information
		curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x'); //Username (api token, fake password as per Highrise api)

		//Don't verify for SSL if you have an SSL Highrise account
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

		//Call Highrise
		$xml = curl_exec($curl);
		curl_close($curl);	
		
		//Parse XML
		$people = simplexml_load_string($xml);
		
		if(count($people) > 0){
			return true;
		}else{
			return false;
		}
	}	
	
	function details($id){

	}
}

?>