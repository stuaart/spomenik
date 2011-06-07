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
	const ANSWER_WAIT = 3000;
	const POST_VISIT_WAIT = 10000;
	const MAX_RECORD_TIME = 60;
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

	const MYSQL_USER = "root";
	const MYSQL_DB = "spomenik";
	const UPLOAD_DIR = "/home/stuart/public_html/spomenik/uploads/";
}

class SMSPayload
{
	const MESSAGE1 = "Message number 1";
	const MESSAGE2 = "Message number 2";
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
