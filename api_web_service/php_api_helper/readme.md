# PHP API Helper

A wrapper class for the public and private web service API's. You can drop this class in and access the HelpSpot API in just a few lines of code. Here's a few examples:

## MOST BASIC USAGE
Desc: Make a public API call which returns an array with the current API version installed.

	include('HelpSpotAPI.php');
	$hsapi = new HelpSpotAPI(array("helpSpotApiURL" => "http://.../api/index.php")); 
	$result = $hsapi->version();

	print_r($result); //show returned array

## PUBLIC REQUEST CREATION
Desc: Create a request via the public api which does not require a password. This is the same as creating a request via the portal.

	include('HelpSpotAPI.php');
	$hsapi = new HelpSpotAPI(array("helpSpotApiURL" => "http://.../api/index.php")); 
	$result = $hsapi->requestCreate(array(
					'sFirstName' => 'Bob',
					'sLastName' => 'Jones',
					'sEmail' => 'bjones@company.com',
					'tNote' => 'This is a test note'
				));	

## PRIVATE REQUEST CREATION
Desc: This uses the private API to create a request which opens up many more possibilities for populating fields, assigning the request and more

	include('HelpSpotAPI.php');
	$hsapi = new HelpSpotAPI(array(
								'helpSpotApiURL' => 'http://.../api/index.php',
								'username' => 'todd@company.com',
								'password' => 'pass'
							)); 

	$result = $hsapi->privateRequestCreate(array(
								'sFirstName' => 'Bob',
								'sLastName' => 'Jones',
								'sEmail' => 'bjones@company.com',
								'tNote' => 'This is a test note',
								'xCategory' => 1,
								'xPersonAssignedTo' => 0
							));	

## SEARCHING FOR REQUESTS
Desc: Search for requests. It's also possible to retreive filters of requests as well, see docs for details.

	include('HelpSpotAPI.php');
	$hsapi = new HelpSpotAPI(array(
								'helpSpotApiURL' => 'http://.../api/index.php',
								'username' => 'todd@company.com',
								'password' => 'pass'
							)); 
							
	$result = $hsapi->privateRequestSearch(array(
								'sUserId' => '76489',		//Customer ID to search for
								'relativedate' => 'past_7'	//Limits results to requests created in the past 7 days
							));	
