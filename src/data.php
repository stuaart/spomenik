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

$res = mysql_query("SELECT unix_timestamp(timestamp) FROM log 
					WHERE entry LIKE 'blockSay,num=5%' 
					ORDER BY timestamp DESC");

$num = 0;
$ts = 0;
if ($res)
	$num = mysql_num_rows($res);
if ($num > 0)
{
	$first = mysql_fetch_row($res);
	$ts = $first[0];
}
else
	$ts = 0;
echo "var visit_stats = { \"num_visits\": $num, \"last_visit\": $ts }";

mysql_close();
?>
