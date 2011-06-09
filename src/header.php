<?
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
	const POST_VISIT = 3;
}

class Config
{
	public static $SMS1 = "Message number 1";
	public static $SMS2 = "Message number 2";
	public static $ANSWER_WAIT = 3000; // 3 seconds
	public static $POST_VISIT_WAIT = 10000; // 10 seconds
	public static $MAX_RECORD_TIME = 60; // 60 seconds
	public static $RECORD_SILENCE_TIMEOUT = 5;
	public static $INPUT_TIMEOUT = 10;
}

class Sys
{
	const ERROR_MESSAGE = 
		"http://horiz1ab1.miniserver.com/~stuart/spomenik/audio/error.mp3";
	const AUDIO_BASE_URL = 
		"http://horizab1.miniserver.com/~stuart/spomenik/audio/";
	const SMS_BASE_URL = 
		"http://api.tropo.com/1.0/sessions?action=create&token=";
	const CALL_TRACK_URL = 
		"http://horizab1.miniserver.com/~stuart/spomenik/tracker.php";
	const RECORD_URL = 
		"http://horizab1.miniserver.com/~stuart/spomenik/record.php";
	const SYSTEM_LOG_URL = 
		"http://horizab1.miniserver.com/~stuart/spomenik/logger.php";
	const UPLOAD_URL =
		"http://horizab1.miniserver.com/~stuart/spomenik/uploads/";
	
	const TROPO_KEY_FILE = "/home/stuart/tropo_key.txt";
	const UPLOAD_DIR = "/home/stuart/public_html/spomenik/uploads/";
	const AUDIO_DIR = "/home/stuart/public_html/spomenik/audio/";
}

class Routing
{
	const UK_INT_PREFIX = "+44";
	const SLO_INT_PREFIX = "+386";
	// Local prefixes only work for mobiles
	const UK_LOCAL_PREFIX = "07";
	const SLO_LOCAL_PREFIX = "01";
}

class MySQL
{
	const USER = "root";
	const HOST = "localhost";
	const DBNAME = "spomenik";
	const PASSWD_FILE = "/home/stuart/mysql-passwd.txt";
}


// TODO: clean function
function cleanVar($str)
{
	return $str;
/*	return is_array($str) ? array_map('_clean', $str) : 
				str_replace("\\", "\\\\", 
							htmlspecialchars((get_magic_quotes_gpc() ? 
							stripslashes($str) : $str), ENT_QUOTES)
				);*/
}
?>
