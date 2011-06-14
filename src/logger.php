<?php // logger.php

include_once("header.php");

$user = MySQL::USER;
$host = MySQL::HOST;
$password = file_get_contents(MySQL::PASSWD_FILE);
$database = MySQL::DBNAME;

if (strlen($_POST['id']) == 0)
{
	echo "ID variable is not set, print_r = " . print_r($_POST);
	exit;
}



if (!mysql_connect($host, $user, $password))
{
	echo "Unable to connect to database: " . mysql_error();
	exit;
}

if (!mysql_select_db($database))
{
	echo "Unable to select database: " . mysql_error();
	exit;
}

$id = mysql_real_escape_string($_POST['id']);
$entry = mysql_real_escape_string($_POST['entry']);

if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'log'")))
{
	mysql_query("CREATE TABLE log (id VARCHAR(50) NOT NULL, 
								   entry TEXT, timestamp DATETIME NOT NULL)");
}
mysql_query("INSERT INTO log VALUES('" . $id . "', '" . $entry . "', NOW())");

?>
