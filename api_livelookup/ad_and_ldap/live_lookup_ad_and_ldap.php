<?php
/*
Sample Live Lookup with Active Directory.
Live Lookup documentation: http://www.userscape.com/helpdesk/index.php?pg=kb.page&id=6
Fields passed in via $_GET:
customer_id
first_name
last_name
email
phone
*/

header('Content-type: text/xml');

/***** CONNECTION SPECIFIC VARIABLES *****/
$host = "ldap://your.adserver.com";
$user = "user@your.adserver.com";
$pswd = "thepassword";

// Create the DN (sometims DN= needs to be switched to DC=)
$dn = "OU=People,OU=staff,DN=your,DN=adserver,DN=com";

// Specify only those parameters we're interested in displaying
// These are the fields you will use below to create the Live Lookup XML
$attrs = array('givenname','uid','cn','sn','mail','telephonenumber');

/***** LIVE LOOKUP LOGIC *****/

//This is very simple logic. You could expand on this by adding a first name + last name serach, search by email domains, etc

if(!empty($_GET['customer_id'])){	//If an ID is passed in use that to make a direct lookup
	
	$filter = 'uid='.$_GET['customer_id'].'*';	
	
}elseif(!empty($_GET['email'])){			//If no ID then try email
	
	$filter = 'mail='.$_GET['email'].'*';

}elseif(!empty($_GET['last_name'])){	//If no ID or email then search on last name
	
	$filter = 'sn='.$_GET['last_name'].'*';
	
}elseif(!empty($_GET['first_name'])){	//Try first name if no ID,email,last name
	
	$filter = 'givenname='.$_GET['first_name'].'*';	

}else{
	$filter = 'sn='.'*';	//Return everyone
}

/***** START CONNECTION *****/

$ad = ldap_connect($host)
      or die( "Could not connect!" );

// Version number
ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3)
     or die ("Could not set ldap protocol");

// Binding to ad/ldap server
$bd = ldap_bind($ad, $user, $pswd)
      or die ("Could not bind: ".ldap_error($ad));

$search = ldap_search($ad, $dn, $filter, $attrs)
          or die ("ldap search failed: ".ldap_error($ad));

$entries = ldap_get_entries($ad, $search);

echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<livelookup version="1.0" columns="first_name,last_name, email">
        <?php for ($i=0; $i < $entries["count"]; $i++) : ?>
        <customer>
                <customer_id><?php if(isset($entries[$i]['uid'][0])){ echo htmlspecialchars($entries[$i]['uid'][0]); } ?></customer_id>
                <first_name><?php if(isset($entries[$i]['givenname'][0])){ echo htmlspecialchars($entries[$i]['givenname'][0]); } ?></first_name>
                <last_name><?php if(isset($entries[$i]['sn'][0])){ echo htmlspecialchars($entries[$i]['sn'][0]); } ?></last_name>
                <email><?php if(isset($entries[$i]['mail'][0])){ echo htmlspecialchars($entries[$i]['mail'][0]); } ?></email>
                <phone><?php if(isset($entries[$i]['telephonenumber'][0])){ echo htmlspecialchars($entries[$i]['telephonenumber'][0]); } ?></phone>
                <!-- Add custom elements here. Simply add them to $attrs above and then output the tag like the others here -->
        </customer>
        <?php endfor; ?>
</livelookup>

<?php ldap_unbind($ad); ?>