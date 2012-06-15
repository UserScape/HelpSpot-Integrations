<?php
/*
The file integrates HelpSpot's Live Lookup with 37Signals Highrise CRM tool
PHP Versions required: 5+
PHP Modules required: curl
*/

/*
MODIFY THESE FOR YOUR HIGHRISE ACCOUNT */
$highrise_url = ''; //Your Highrise URL, something like http://userscape.highrisehq.com
$api_token = ''; //You can find this in your Highrise account in the "my info" screen

/****************** NO MODIFICATIONS REQUIRED BELOW HERE ******************/

/*
GET HIGHRISE XML */
//Highrise currently doesn't have a contact search, so we need to pull in all the contacts and search ourselves
$curl = curl_init($highrise_url.'/people.xml');

//Return XML don't output it
curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

//Set Basic auth information
curl_setopt($curl,CURLOPT_USERPWD,$api_token.':x'); //Username (api token, fake password as per Highrise api)

//Don't verify for SSL if you have an SSL Highrise account
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

//Call Highrise
$xml = curl_exec($curl);
curl_close($curl);

//Parse the XML returned from Highrise
$people = simplexml_load_string($xml);

/*
SEARCH PEOPLE (BY FULL NAME, LAST NAME OR EMAIL)*/
$matches = array();
foreach($people->person AS $person){
	if( ($person->{'first-name'} == $_GET['first_name'] && $person->{'last-name'} == $_GET['last_name']) ||
		(!empty($person->{'last-name'}) && strtolower($person->{'last-name'}) == strtolower($_GET['last_name'])) ||
		(!empty($person->{'contact-data'}->{'email-addresses'}->{'email-address'}[0]->address) && strtolower($person->{'contact-data'}->{'email-addresses'}->{'email-address'}[0]->address) == strtolower($_GET['email'])) ){

		//This person matched
		$matches[] = $person;
		
	}
}

/*
RETURN RESULTS TO HELPSPOT */
header('Content-type: text/xml');
echo '<?xml version="1.0" encoding="ISO-8859-1"'."?".">\n";
	
echo '<livelookup version="1.0" columns="customer_id,first_name,last_name">';
	//If we found some matches then output them	
	if(count($matches)){
		//Output each contact, these will be shown to the help desk user. The user can then pick the right one (if more than one returned). 
		//The data can also be automatically inserted.
		foreach($matches AS $person){
			echo '
			<customer>
				<first_name>'.$person->{'first-name'}.'</first_name>
				<last_name>'.$person->{'last-name'}.'</last_name>
				<email>'.$person->{'contact-data'}->{'email-addresses'}->{'email-address'}[0]->address.'</email>
				<phone>'.$person->{'contact-data'}->{'phone-numbers'}->{'phone-number'}[0]->number.'</phone>
				<title>'.$person->title.'</title>
				<background>'.$person->background.'</background>
			</customer>';
		}
	}
echo '</livelookup>';		
	
?>