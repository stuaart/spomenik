<?php // logger.php

include("header.php");

$user = Config::MYSQL_USER;
$host = Config::MYSQL_HOST;
$password = file_get_contents(Config::MYSQL_PASSWD_FILE);
$database = Config::MYSQL_DB;

if (strlen($_POST['id']) == 0)
{
	echo "ID variable is not set, print_r = " . print_r($_POST);
	exit;
}

$id = cleanVar($_POST['id']);
$entry = cleanVar($_POST['entry']);


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

if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'log'")))
{
	mysql_query("CREATE TABLE log (id VARCHAR(50) NOT NULL, 
								   entry TEXT, timestamp DATETIME NOT NULL)");
}
mysql_query("INSERT INTO log VALUES('" . $id . "', '" . $entry . "', NOW())");

?>