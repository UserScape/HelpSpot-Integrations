<?php
/*

NOTE: THIS FILE MUST BE RENAMED TO   BlackBoxPortal.php   IN ORDER TO WORK!

The black box function allows you to use any authentication scheme you like with the HelpSpot customer portal. Once you enable
black box authentication in the admin settings page, the function below will be used to authenticate users rather than
HelpSpots built in authentication. 

This function must return an email account address for a valid username/password or false for a invalid username/password

*/

// SECURITY: This prevents this script from being called from outside the context of HelpSpot
if (!defined('cBASEPATH')) die();


/*
When using Black Box Portal auth, HelpSpot's portal request check login page will ask the user for a username and password.
Authenticate these against your internal auth system using LDAP, Password file, Database call, or whatever system your company uses.
HelpSpot expects this function to return A VALID EMAIL ADDRESS if the user is valid or FALSE if not. The email is used to then lookup the 
request belonging to the user.
*/

//include the class
require_once("adLDAP.php");

function BlackBoxPortal($username, $password){

	//create the AD LDAP connection
	$adldap = new adLDAP();

	//authenticate a user
	if ($adldap->user()->authenticate($username, $password))
	{
		$user = $adldap->user()->infoCollection($username, array("*"));
		
		return $user->mail;
	}
	else
	{
		return false;
	}

}

?>