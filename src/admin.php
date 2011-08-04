<html>
<head>
	<title>Spomenik admin</title>

	<script type="text/javascript" src="data.php"></script>
	<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
	<script type="text/javascript" src="js/jquery.jmp3.js?1"></script>
	<script type="text/javascript">
	$(document).ready(function(){
		$(".player").jmp3({
			showfilename: "false",
			backcolor: "aaaaaa",
			forecolor: "cccccc",
			width: 150,
			showdownload: "false"
		});
	});
	
	function readableTimestamp(ts)
	{
		var theDate = new Date();
		var now = new Date();
		theDate.setTime(ts * 1000);
		var diff = (now.getTime() - theDate.getTime()) / 1000;
		var hours = Math.floor((diff / 60 / 60));
		var days = Math.floor(hours / 24);
		var weeks = Math.floor(days / 7);
		var dateString = "just now";
		if (hours > 0 && hours < 24)
		{
			dateString = hours;
			if (hours == 1)
				dateString += " hour ago";
			else
				dateString += " hours ago";
		}
		else if (days > 0 && days < 7)
		{
			dateString = days;
			if (days == 1)
				dateString += " day ago";
			else
				dateString += " days ago";
		}
		else if (weeks > 0 && weeks < 52)
		{
			dateString = weeks;
			if (weeks == 1)
				dateString += " week ago";
			else
				dateString += " weeks ago";
		}
		return dateString;
	}
	</script>

</head>
<body>
<p>
<h3><a href="data.php">Data</a></h3>
<p>
<span id="num_visits"></span> visits to the physical site.
Last visit was at: <span id="last_visit"></span>.
</p>

<p>
<div id="recording_set"></div>
</p>

<script type="text/javascript">
	var dateString = "&lt;no last visit&gt;";
	if (data.visit_stats.last_visit > 0)
		dateString = readableTimestamp(data.visit_stats.last_visit);
	document.getElementById("last_visit").innerHTML = dateString;
	document.getElementById("num_visits").innerHTML 
		= data.visit_stats.num_visits;
	for (var i = 0; i < data.recordings.length; ++i)
	{
		document.getElementById("recording_set").innerHTML += 
			"<span class='player'>" + data.recordings[i].url + "</span>" 
			+ readableTimestamp(data.recordings[i].timestamp);
	}

</script>

<p>
<h3>Set configuration</h3>
<form action="config.php" method="POST">
<?php

include_once("header_shared.php");
include_once("header.php");

echo readConfig();

echo "First text message payload:<br/>\n
	<input type='textarea' name='sms1' cols='40' rows='6' value='" . Config::$SMS1 . "'><br/>\n
Second text message payload:<br/>\n
	<input type='textarea' name='sms2' cols='40' rows='6' value='" . Config::$SMS2 . "'><br/>\n
Amount of time to wait before playing the first block of audio after answering the phonecall:\n
	<input type='text' name='answer_wait' value='" . Config::$ANSWER_WAIT . "'> seconds<br/>\n
Gap between the end of the experience and receiving the second text message:\n
	<input type='text' name='post_visit_wait' value='" . Config::$POST_VISIT_WAIT . "'> seconds<br/>\n
Maximum amount of time someone can record a response for at the end of the experience:\n
	<input type='text' name='max_record_time' value='" . Config::$MAX_RECORD_TIME . "'> seconds<br/>\n
Length of the silence during the user response recording that signals a timeout:
	<input type='text' name='record_silence_timeout' value='" . Config::$RECORD_SILENCE_TIMEOUT . "'> seconds<br/>\n
Amount of time within which someone must press 1, 2 or any other option button before a timeout message kicks in:\n
	<input type='text' name='input_timeout' value='" . Config::$INPUT_TIMEOUT . "'> seconds<br/>\n
Maximum number of times someone can continually not press buttons when requested without the system hanging up on them:\n
	<input type='text' name='max_repeats' value='" . Config::$MAX_REPEATS . "'>";
?>

	<input type="submit" value="Submit">
</form>
</p>

<p>
<h3>Set user state</h3>
<form action="tracker.php" method="POST">
	Phone number: <input type="text" name="callID">
<?php
include_once("header_shared.php");

echo "Language: <select name='lang'>";
echo "<option value='" . Lang::SLO . "'>Slovenian</option>";
echo "<option value='" . Lang::ENG . "'>English</option>";
echo "</select>";

echo "Station: <select name='station'>";
echo "<option value='" . Station::NOT_SET . "'>Not set</option>";
echo "<option value='" . Station::STATION1 . "'>Station 1</option>";
echo "<option value='" . Station::STATION2 . "'>Station 2</option>";
echo "<option value='" . Station::STATION2_PART3 . "'>Station 2, part 3</option>";
echo "<option value='" . Station::POST_VISIT . "'>Post visit</option>";
echo "</select>";
?>

	<input type="submit" value="Submit">
</form>
</p>
<p>
<h3>Upload audio block files</h3>
<form action="upload.php" enctype="multipart/form-data" method="POST">
Block to replace: 
<select name="block">
<?php

include_once("header.php");
include_once("header_shared.php");

if ($fh = opendir(Sys::AUDIO_DIR))
{
	$list = array();
	while (($file = readdir($fh)) != false)
	{
		if ($file != "." && $file != "..")
			$list[] = $file;
	}
	closedir($fh);

	sort($list);
	foreach ($list as $file)
		echo "<option value='$file'>$file</option>";
}

?>
	</select>
	Replace with file: <input type="file" name="filename">
	<input type="submit" value="Upload">
</form>
</p>
<?php

include_once("header.php");
include_once("header_shared.php");

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

if (isset($_GET['delete_all']))
{
	$user = mysql_real_escape_string($_GET['delete_all']);
	mysql_query("DELETE FROM log WHERE id='$user'");
	$res = mysql_query("SELECT recording FROM user WHERE id='$user'");
	if (mysql_num_rows($res) > 0)
	{
		$row = mysql_fetch_row($res);
		if ($row[0]!= "");
			unlink($row[0]);
	}
	mysql_query("DELETE FROM user WHERE id='$user'");

	unset($_GET['delete_all']);

	echo "<script type='text/javascript'>window.location='admin.php'</script>";
}

if (isset($_GET['delete_user']))
{
	$user = mysql_real_escape_string($_GET['delete_user']);
	mysql_query("DELETE FROM user WHERE id='$user'");
	unset($_GET['delete']);
	echo "<script type='text/javascript'>window.location='admin.php'</script>";
}

if (isset($_GET['delete_log']))
{
	$user = mysql_real_escape_string($_GET['delete_log']);
	mysql_query("DELETE FROM log WHERE id='$user'");
	unset($_GET['delete_log']);
	echo "<script type='text/javascript'>window.location='admin.php'</script>";
}

if (isset($_GET['delete_upload']))
{
	$user = mysql_real_escape_string($_GET['delete_upload']);
	$res = mysql_query("SELECT recording FROM user WHERE id='$user'");
	if (mysql_num_rows($res) > 0)
	{
		$row = mysql_fetch_row($res);
		if ($row[0]!= "");
			unlink($row[0]);
	}
	mysql_query("UPDATE user SET recording = NULL WHERE id=$user");
	unset($_GET['delete_upload']);
	echo "<script type='text/javascript'>window.location='admin.php'</script>";
}


echo "<p><h3>User list</h3><pre>";

$res = mysql_query("SELECT * FROM user");
if ($res && mysql_num_rows($res) > 0)
{
	while ($row = mysql_fetch_assoc($res))
	{
		$lang = "";
		switch ($row['lang'])
		{
			case Lang::ENG: $lang = "English"; break;
			case Lang::SLO: $lang = "Slovenian"; break;
			default: $lang = "not set"; break;
		}
		$station = "";
		switch ($row['station'])
		{
			case Station::STATION1: $station = "Station 1"; break;
			case Station::STATION2: $station = "Station 2"; break;
			case Station::STATION2_PART3: $station = "Station 2, part 3"; break;
			case Station::POST_VISIT: $station = "Post visit"; break;
			case Station::NOT_SET: $station = "not set"; break;
			default: $station = "error, undefined"; break;
		}
	
		$deleteAllStr = "<a href='?delete_all=" . $row['id'] . 
						 "'>delete all</a>";
		$deleteUserStr = "<a href='?delete_user=" . $row['id'] . 
						 "'>delete user</a>";
		$deleteLogStr = "<a href='?delete_log=" . $row['id'] . 
						"'>delete logs</a>";
		$deleteUploadStr = "<a href='?delete_upload=" . $row['id'] .
						   "'>delete recording</a>";

		$recordingStr = "";
		$recording = substr(strrchr($row['recording'], "/"), 1);
		if (strlen($recording) > 0)
			$recordingStr = "Recording file: $recording";

		echo "<p>";
		echo "<strong>Phone number: <a href='log.php?id=" . $row['id'] . "'>" 
			  . $row['id'] . "</a></strong><br/>";
		echo "Station: <em>$station</em>. Language: <em>$lang</em>. " 
			 . "$recordingStr<br/>";
		echo "ACTIONS: [$deleteUserStr] [$deleteLogStr] [$deleteUploadStr] "
			 . "<strong>[$deleteAllStr]</strong>";
		echo "</p>";
	}
}

echo "</pre></p>";

mysql_close();
?>

</body>
</html>
