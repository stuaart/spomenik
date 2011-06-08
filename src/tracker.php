<?php // tracker.php

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

// TODO: might have to do some clever phone number parsing things here...

$id = cleanVar($_POST['id']);
if (isset($_POST['lang']) && $_POST['lang'] != "")
	$lang = cleanVar($_POST['lang']);
if (isset($_POST['station']) && $_POST['station'] != "")
	$station = cleanVar($_POST['station']);
$response = "";


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
