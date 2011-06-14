<?php

include("header.php");

$user = MySQL::USER;
$host = MySQL::HOST;
$password = file_get_contents(MySQL::PASSWD_FILE);
$database = MySQL::DBNAME;

if (strlen($_GET['id']) == 0)
{
	echo "ID variable is not set, print_r = " . print_r($_GET);
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

$id = mysql_real_escape_string($_GET['id']);

echo "<p>Logs for user $id</p><pre>";
$res = mysql_query("SELECT * FROM log WHERE id = '$id' 
					ORDER BY timestamp ASC");
if ($res && mysql_num_rows($res) > 0)
{
	while ($row = mysql_fetch_assoc($res))
	{
		echo "[" . $row['timestamp'] . "] " . $row['entry'] . "\n";
	}
}

echo "</pre>";

mysql_close();
?>
