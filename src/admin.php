<html>
<head>
	<title>Spomenik admin</title>

	<script type="text/javascript" src="data.php"></script>
	<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
	<script type="text/javascript" src="js/jquery.jmp3.js?1">
	</script>
	<script type="text/javascript">
	$(document).ready(function(){
		// default options
		$(".mp3").jmp3({
			showfilename: "false",
			backcolor: "aaaaaa",
			forecolor: "cccccc",
			width: 150,
			showdownload: "false"
		}); 
		// custom options
		$("#player").jmp3({
			showfilename: "false",
			backcolor: "aaaaaa",
			forecolor: "cccccc",
			width: 150,
			showdownload: "false"
		});
	});
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
	{
		var newDate = new Date();
		newDate.setTime(data.visit_stats.last_visit * 1000);
		dateString = newDate.toUTCString();
	}
	document.getElementById("last_visit").innerHTML = dateString;
	document.getElementById("num_visits").innerHTML 
		= data.visit_stats.num_visits;
	for (var i = 0; i < data.recordings.length; ++i)
	{
		document.getElementById("recording_set").innerHTML += 
			"<span id='player'>" + data.recordings[i].url + "</span>";
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
	ID: <input type="text" name="callID">
	Lang: <input type="text" name="lang" value="1">
	Station: <select name="station">
<?php
include_once("header.php");
echo "<option value='" . Station::NOT_SET . "'>Not set</option>";
echo "<option value='" . Station::STATION1 . "'>Station 1</option>";
echo "<option value='" . Station::STATION2 . "'>Station 2</option>";
echo "<option value='" . Station::STATION2_PART3 . "'>Station 2, part 3</option>";
echo "<option value='" . Station::POST_VISIT . "'>Post visit</option>";
?>
	</select>

	<input type="submit" value="Submit">
</form>
</p>
<p>
<h3>Upload audio block files</h3>
<form action="upload.php" enctype="multipart/form-data" method="POST">
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
	File: <input type="file" name="filename">
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
			default: $lang = "Not set"; break;
		}
		$station = "";
		switch ($row['station'])
		{
			case Station::STATION1: $station = "Station 1"; break;
			case Station::STATION2: $station = "Station 2"; break;
			case Station::STATION2_PART3: $station = "Station 2, part 3"; break;
			case Station::POST_VISIT: $station = "Post visit"; break;
			default: $station = "Not set"; break;
		}
		
		$recordingStr = "None";
		$recording = substr(strrchr($row['recording'], "/"), 1);
		if (strlen($recording) > 0)
		{
			$recordingStr = "recording=$recording "
							. "<span id='player'>uploads/$recording</span>";
		}

		echo "id=<a href='log.php?id=" . $row['id'] . "'>" . $row['id']
			 . "</a>, station=$station, lang=$lang, $recordingStr\n";
	}
}

echo "</pre></p>";

mysql_close();
?>

</body>
</html>
