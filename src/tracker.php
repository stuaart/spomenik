<?php // tracker.php

$user = "root";
$password = "r4nd0m";
$database = "spomenik";

class Lang
{
	const ENG = 1;
	const SLO = 2;
	const NOT_SET = -1;
}

class Station
{
	const NOT_SET = -1;
	const STATION1 = 1;
	const STATION2 = 2;
	const STATION3 = 3;
	const POST_VISIT = 4;
}


if (!isset($_POST['id']))
	exit(0);

$id = $_POST['id'];
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
	if (isset($_POST['setlang']))
	{
	}

	$row = mysql_fetch_assoc($res);
	$response = $row['lang'] . "," . $row['station'];
}

mysql_close();

echo $response;
?>
