<?php // tracker.php

include("header.php");

$user = "root";
$password = "";
$database = "spomenik";

if (!isset($_POST['id']) || $_POST['id'] == "")
	exit(0);

// TODO: some security / guards
$id = $_POST['id'];
if (isset($_POST['lang']) && $_POST['lang'] != "")
	$lang = $_POST['lang'];
if (isset($_POST['station']) && $_POST['station'] != "")
	$station = $_POST['station'];
$response = "";

// New user
if (!mysql_connect(localhost, $user, $password))
{
	echo "Unable to connect to database: " . mysql_error();
	exit;
}

if (!mysql_select_db($database))
{
	echo "Unable to select database: " . mysql_error();
	exit;
}

if (!isset($id))
{
		echo "No ID provided";
			exit;
}

$res = mysql_query("SELECT * FROM user WHERE id = '$id'");
if (mysql_num_rows($res) == 0)
{
	if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'user'")))
	{
		mysql_query("CREATE TABLE user (id VARCHAR(20) NOT NULL, 
										station INT(2), lang INT(2))"
		);
	}
	else 
		exit(0);

	mysql_query("INSERT INTO user VALUES('" . $id . "', " . Station::NOT_SET . 
										 ", " . Lang::NOT_SET . ")");
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
