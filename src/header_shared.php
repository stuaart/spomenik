<?php

class Lang
{
	const ENG = 2;
	const SLO = 1;
	const NOT_SET = -1;
}

class Station
{
	const NOT_SET = -1;
	const STATION1 = 1;
	const STATION2 = 2;
	const POST_VISIT = 4;
	// Part 3 is not a standard station
	const STATION2_PART3 = 3;
}

// Config defaults
class Config
{
	public static $SMS1 = "Message number 1";
	public static $SMS2 = "Message number 2";
	public static $ANSWER_WAIT = 3;
	public static $POST_VISIT_WAIT = 10;
	public static $MAX_RECORD_TIME = 60;
	public static $RECORD_SILENCE_TIMEOUT = 5;
	public static $INPUT_TIMEOUT = 10;
	public static $MAX_REPEATS = 4;
}

class Sys
{
	const AUDIO_BASE_URL = 
		"http://url.to/audio/";
	const SMS_BASE_URL = 
		"http://api.tropo.com/1.0/sessions?action=create&token=";
	const CALL_TRACK_URL = 
		"http://url.to/tracker.php";
	const RECORD_URL = 
		"http://url.to/record.php";
	const SYSTEM_LOG_URL = 
		"http://url.to/logger.php";
	const UPLOAD_URL =
		"http://url.to/uploads/";
	
	const TROPO_KEY_FILE = "/path/to/tropo_key.txt";
	const UPLOAD_DIR = "/path/to/uploads/";
	const AUDIO_DIR = "/path/to/audio/";
}

class Routing
{
	const UK_INT_PREFIX = "+44";
	const SLO_INT_PREFIX = "+386";
	// Local prefixes only work for mobiles
	const UK_LOCAL_PREFIX = "07";
	const SLO_LOCAL_PREFIX = "01";
}


// TODO: clean function
function cleanVar($str)
{
/*	$user = MySQL::USER;
	$host = MySQL::HOST;
	$password = file_get_contents(MySQL::PASSWD_FILE);

	if (!mysql_connect($host, $user, $password))
	{
		mysql_close();
		return;
	}
	else
	{
		$out = mysql_real_escape_string($str);
		mysql_close();
		return $out;
	}*/

	return $str;
}

function logger($id, $entry)
{
	$postData = array();
	$postData["id"] = "$id";
	$postData["entry"] = $entry;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, Sys::SYSTEM_LOG_URL);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_exec($ch);
}

?>
