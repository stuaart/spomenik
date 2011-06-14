<?php // config.php

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

if (mysql_num_rows(mysql_query("SHOW TABLES LIKE 'config'")))
{
	$sms1 = mysql_real_escape_string($_POST['sms1']);
	$sms2 = mysql_real_escape_string($_POST['sms2']);
	$answer_wait = mysql_real_escape_string($_POST['answer_wait']);
	$post_visit_wait = mysql_real_escape_string($_POST['post_visit_wait']);
	$max_record_time = mysql_real_escape_string($_POST['max_record_time']);
	$record_silence_timeout = 
		mysql_real_escape_string($_POST['record_silence_timeout']);
	$input_timeout = mysql_real_escape_string($_POST['input_timeout']);
	$max_repeats = mysql_real_escape_string($_POST['max_repeats']);

	mysql_query("UPDATE config SET value = '$sms1' WHERE id = 'sms1'");
	mysql_query("UPDATE config SET value = '$sms2' WHERE id = 'sms2'");
	mysql_query("UPDATE config SET value = '$answer_wait' 
					WHERE id = 'answer_wait'");
	mysql_query("UPDATE config SET value = '$post_visit_wait' 
					WHERE id = 'post_visit_wait'");
	mysql_query("UPDATE config SET value = '$max_record_time' 
					WHERE id = 'max_record_time'");
	mysql_query("UPDATE config SET value = '$record_silence_timeout' 
					WHERE id = 'record_silence_timeout");
	mysql_query("UPDATE config SET value = '$input_timeout' 
					WHERE id = 'input_timeout'");
	mysql_query("UPDATE config SET value = '$max_repeats' 
					WHERE id = 'max_repeats'");
}

mysql_close();

echo "<a href='admin.php'>Back</a>";
?>
