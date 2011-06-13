<?php // tracker.php

include_once("header.php");
include_once("header_shared.php");

$user = MySQL::USER;
$host = MySQL::HOST;
$password = file_get_contents(MySQL::PASSWD_FILE);
$database = MySQL::DBNAME;

if (strlen($_POST['callID']) == 0 && strlen($_GET['callID']) == 0)
{
	echo "ID variable is not set, id=" . $_POST['callID'] . 
		 ", _POST=" . $_POST['callID'] . ", _callID=" . $_GET['callID'];
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

$id = "-1";

if (isset($_POST['callID']))
	$id = mysql_real_escape_string($_POST['callID']);
else if (isset($_GET['callID']))
	$id = mysql_real_escape_string($_GET['callID']);

if (isset($_POST['lang']) && $_POST['lang'] != "")
	$lang = mysql_real_escape_string($_POST['lang']);
else if (isset($_GET['lang']) && $_GET['lang'] != "")
	$lang =  mysql_real_escape_string($_GET['lang']);
if (isset($_POST['station']) && $_POST['station'] != "")
	$station = mysql_real_escape_string($_POST['station']);
else if (isset($_GET['station']) && $_GET['station'] != "")
	$station = mysql_real_escape_string($_GET['station']);

$response = "";

$res = mysql_query("SELECT * FROM user WHERE id = '$id'");
if (mysql_num_rows($res) == 0)
{
	if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'user'")))
	{
		mysql_query("CREATE TABLE user (id VARCHAR(50) NOT NULL, 
										station INT(2), lang INT(2), 
										recording VARCHAR(255))"
		);
	}

	mysql_query("INSERT INTO user VALUES('" . $id . "', " . Station::NOT_SET . 
										 ", " . Lang::NOT_SET . ", NULL)");
}
else
{
	$row = mysql_fetch_assoc($res);
	if (isset($lang))
	{
		if (mysql_query("UPDATE user SET lang = '$lang' WHERE id = '$id'"))
			$response = $lang;
	}
	else 
		$response = $row['lang'];
	if (isset($station))
	{
		if (mysql_query("UPDATE user SET station = '$station' 
						 WHERE id = '$id'"))
		{
			if ($response != "")
				$response .= ",";
			$response .= $station;
		}
	}
	else
	{
		if ($response != "")
			$response .= ",";
		$response .= $row['station'];
	}

}

mysql_close();

echo $response;
?>
