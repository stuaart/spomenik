<html>
<head><title>Spomenik admin</title></head>
<body>
<p>
<form action="tracker.php" method="POST">
	ID: <input type="text" name="id">
	Lang: <input type="text" name="lang">
	Station: <input type="text" name="station">
	<input type="submit" value="Submit">
</form>
</p>
<p>
<form action="record.php" enctype="multipart/form-data" method="POST">
	ID: <input type="text" name="id">
	File: <input type="file" name="filename">
	<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
	<input type="submit" value="Upload">
</form>
</p>
<?php

include("header.php");

$user = MySQL::USER;
$host = MySQL::HOST;
$password = file_get_contents(MySQL::PASSWD_FILE);
$database = MySQL::DBNAME;

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

echo "<p>User list:</p><pre>";

$res = mysql_query("SELECT * FROM user");
while ($row = mysql_fetch_assoc($res))
{
	$lang = "";
	switch ($row['lang'])
	{
		case Lang::ENG: $lang = "English"; break;
		case Lang::SLO: $lang = "Slovenian"; break;
		default: $lang = "Not set"; break;
	}
	$station = "";
	switch ($row['station'])
	{
		case Station::STATION1: $station = "Station 1"; break;
		case Station::STATION2: $station = "Station 2"; break;
		case Station::POST_VISIT: $station = "Post visit"; break;
		default: $station = "Not set"; break;
	}
	
	$recording = substr(strrchr($row['recording'], "/"), 1);

	echo "id=<a href='log.php?id=" . $row['id'] . "'>" . $row['id']
		 . "</a>, station=$station, lang=$lang, "
		 . "recording=<a href='" . Sys::UPLOAD_URL . $recording
		 . "'>$recording</a>\n";
}

echo "</pre>";

mysql_close();
?>

</body>
</html>
