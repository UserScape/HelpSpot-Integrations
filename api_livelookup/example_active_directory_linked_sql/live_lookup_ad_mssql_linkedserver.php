<?php
/*
Sample Live Lookup with Active Directory implemented through a linked server.
Live Lookup documentation: http://www.userscape.com/helpdesk/index.php?pg=kb.page&id=6
Fields passed in via $_GET:
customer_id
first_name
last_name
email
phone
*/

header('Content-type: text/xml');

// MSSQL Connection parameters
$myServer = "SERVERNAME";
$myUser = "USERNAME";
$myPass = "PASSWORD";
$myDB = "HelpSpot";

// Specify only those parameters we're interested in displaying
// These are the fields you will use below to create the Live Lookup XML
$attrs = array('givenname','sn','mail','samaccountname','company','telephonenumber','title');

/***** LIVE LOOKUP LOGIC *****/

//This is very simple logic. You could expand on this by adding a first name + last name serach, search by email domains, etc
if(!empty($_GET['customer_id'])){	//If an ID is passed in use that to make a direct lookup
	
	$filter = "AND samaccountname=''".$_GET['customer_id']."''";	
	
}elseif(!empty($_GET['email'])){			//If no ID then try email
	
	$filter = "AND mail=''".$_GET['email']."''";

}elseif(!empty($_GET['last_name'])){	//If no ID or email then search on last name
	
	$filter = "AND sn=''".$_GET['last_name']."''";
	
}elseif(!empty($_GET['first_name'])){	//Try first name if no ID,email,last name
	
	$filter = "AND givenname=''".$_GET['first_name']."''";	

}else{
	$filter = '';	//Return everyone
}

//connection to the database
$connectionInfo = array("Database"=>$myDB,'UID'=>$myUser,'PWD'=>$myPass);
$link=sqlsrv_connect($myServer,$connectionInfo);

if(!is_resource($link)){
        echo 'Unable to connect to database';
        die( print_r( sqlsrv_errors(), true));
}else{

        //declare the SQL statement that will query the database
        $query = "SELECT * FROM "; 
        $query .= "OpenQuery(ADSI, 'SELECT givenname,sn,mail,samaccountname,company,telephonenumber,title FROM ''LDAP://DC=example,DC=com'' ";
        $query .= "WHERE objectClass = ''User'' ".$filter." ')";

        //echo $query;

        //execute the SQL query and return records
         $entries = sqlsrv_query($link,$query);
}

echo '<'.'?xml version="1.0" encoding="utf-8"?'.'>';
?>
<livelookup version="1.0" columns="first_name,last_name,email">
        <?php while($row = sqlsrv_fetch_array($entries,SQLSRV_FETCH_ASSOC)): ?>
        <customer>
                <customer_id><?php if(!empty($row['samaccountname'])) { echo $row['samaccountname']; } else { echo 'Not available'; }?></customer_id>
                <first_name><?php if(!empty($row['givenname'])) { echo $row['givenname']; } else { echo 'Not available'; }?></first_name>
                <last_name><?php if(!empty($row['sn'])) { echo $row['sn']; } else { echo 'Not available'; }?></last_name>
                <email><?php if(!empty($row['mail'])) { echo $row['mail']; } else { echo 'Not available'; }?></email>
                <phone><?php if(!empty($row['telephonenumber'])) { echo $row['telephonenumber']; } else { echo 'Not available'; }?></phone>
                <company><?php if(!empty($row['company'])) { echo $row['company']; } else { echo 'Not available'; }?></company>
                <title><?php if(!empty($row['title'])) { echo $row['title']; } else { echo 'Not available'; }?></title>
        </customer>
        <?php endWhile; ?>
</livelookup>