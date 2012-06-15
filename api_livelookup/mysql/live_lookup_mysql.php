<?php
/*
Sample Live Lookup against a MySQL database
This script uses the Live Lookup setting: HTTP - GET

Live Lookup documentation: http://www.userscape.com/helpdesk/index.php?pg=kb.page&id=6

This script assumes a customer database table as defined below. You'll probably need to make slight adjustments to the script for your particular database.
id
firstname
lastname
email
phone
*/

/***** DATABASE CONNECTION VARIABLES *****/
$host = "localhost";
$user = "";
$pswd = "";
$name = ""; //database name

/***** CONNECT TO DATABASE *****/
$link=mysql_connect($host,$user,$pswd);

/***** LIVE LOOKUP LOGIC *****/

//This is very simple logic. You could expand on this by adding a first name + last name serach, search by email domains, etc

if(!empty($_GET['customer_id'])){	//If an ID is passed in use that to make a direct lookup
	
	$filter = 'id='.mysql_real_escape_string($_GET['customer_id']);	//Assumes a numeric ID, wrap in quotes for an alpha-numeric ID
	
}elseif(!empty($_GET['email'])){			//If no ID then try email
	
	$filter = 'email="'.mysql_real_escape_string($_GET['email']).'"';

}elseif(!empty($_GET['last_name'])){	//If no ID or email then search on last name
	
	$filter = 'lastname="'.mysql_real_escape_string($_GET['last_name']).'"';
	
}elseif(!empty($_GET['first_name'])){	//Try first name if no ID,email,last name
	
	$filter = 'firstname="'.mysql_real_escape_string($_GET['first_name']).'"';	

}else{
	$filter = '1=0'; //Don't return any results
}

/***** QUERY DATABASE *****/
if(!is_resource($link)){
	echo 'Unable to connect to database';
}else{
	//Select the database to query
	mysql_select_db($name, $link);
	//Query the db
	$result = mysql_query("SELECT * FROM customers WHERE ".$filter,$link);
}

/***** OUTPUT LIVE LOOKUP XML *****/
header('Content-type: text/xml');
echo '<?xml version="1.0" encoding="ISO-8859-1"?>';
?>
<livelookup version="1.0" columns="first_name,last_name,email">
        <?php while($row = mysql_fetch_assoc($result)): ?>
        <customer>
                <customer_id><?php echo $row['id'] ?></customer_id>
                <first_name><?php echo $row['firstname'] ?></first_name>
                <last_name><?php echo $row['lastname'] ?></last_name>
                <email><?php echo $row['email'] ?></email>
                <phone><?php echo $row['phone'] ?></phone>
                <!-- Add custom elements here. -->
        </customer>
        <?php endwhile; ?>
</livelookup>