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

// TODO: some security / guards
$id = $_POST['id'];
$lang = $_POST['lang'];
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
