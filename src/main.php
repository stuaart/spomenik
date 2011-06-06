<?php //main.php

// Note: this is so that the Tropo server can execute the script
header('content-type: text/plain');
echo '<?php
//
';

$header = file_get_contents("./header.php");
$smsKey = file_get_contents("./tropo_key.txt");
if (!$header)
{
	echo "Error getting header ?>";
	exit;
}
if (!$smsKey)
{
	echo "Error getting key ?>";
	exit;
}
$cleanHeader = str_replace(array("<?", "?>", "<?php"), "", $header);
echo $cleanHeader;

echo "global \$smsKey; \$smsKey = \"$smsKey\";";
?>


// Consts to configure
class Config
{
	const ANSWER_WAIT = 3000;
	const POST_VISIT_WAIT = 10000;
	const ERROR_MESSAGE = 
		"http://horiz1ab1.miniserver.com/~stuart/spomenik/audio/error.mp3";
	const AUDIO_BASE_URL = 
		"http://horizab1.miniserver.com/~stuart/spomenik/audio/";
	const SMS_BASE_URL = 
		"http://api.tropo.com/1.0/sessions?action=create&token=";
	const CALL_TRACK_URL = 
		"http://horizab1.miniserver.com/~stuart/spomenik/tracker.php";
}

define ("SMS_URL", SMS_BASE_URL . $smsKey);

// Globals
$lang = Lang::NOT_SET;
$station = Station::NOT_SET;

// UK only
function parseNumber($num)
{
	if (substr($num, 0, 1) == 0)
		$out = substr($num, 1, strlen($num));

	return "+44" . $out; 
}

function sms($type)
{
	$ch = curl_init(SMS_URL . "&type=" . $type . "&numberToSMS=" . 
					$currentCall->callerID);
	curl_exec($ch);
	curl_close($ch);
}

function errorMsg($event) 
{
	say(Config::ERROR_MESSAGE);
}

function opt($opts, $choices, $handler)
{
	_log("opt(), opts=$opts, choices=$choices");
	$res = ask($opts,
			   array("choices" => $choices, 
					 "mode" => "dtmf", "onChoice" => "$handler", 
					 "onBadChoice" => "$handler",
					 "onHangup" => "hangupHandler"
				)
		   );
}

function hangupHandler()
{
	_log("User hungup");
	exit;
}

function blockSay($num, $langSet=true)
{
	global $lang;

	$blockStr = "block" . $num;
	if ($langSet)
	{
		switch ($lang)
		{
			case Lang::ENG: 
			case Lang::SLO: 
			{ 
				$blockStr .= ("-" . $lang); 
				break; 
			}
			default: { break; }
		}
	}

	$file = Config::AUDIO_BASE_URL . "/" . $blockStr . ".mp3";
	_log("Saying from file $file");
	say($file);
}

function blockAsk($num, $opts, $handler, $langSet=true)
{
	global $lang;

	$blockStr = "block" . $num;
	if ($langSet)
	{
		switch ($lang)
		{
			case Lang::ENG: 
			case Lang::SLO: 
			{ 
				$blockStr .= ("-" . $lang); 
				break; 
			}
			default: { break; }
		}
	}
	
	$file = Config::AUDIO_BASE_URL . "/" . $blockStr . ".mp3";
	_log("Asking from file $file");
	opt($file, $opts, $handler);
}

function langHandler($event)
{
	global $lang;

	switch ($event->value)
	{
		case Lang::ENG: case Lang::SLO: { $lang = $event->value; break; }
		default: { blockSay(2); break; }
	}

}

function stationHandler1($event)
{
	global $station;
	_log("stationHandler1() value=" . $event->value . " station=$station");
	if ($event->value != $station)
		blockAsk(6, "$station", "stationHandler2");
}

function stationHandler2($event)
{
	global $station;
	_log("stationHandler2() value=" . $event->value . " station=$station");
	if ($event->value != $station)
	{
		_log("Looping back to blockAsk(5, $station, stationHandler1");
		blockAsk(5, "$station", "stationHandler1");
	}
}

function trackCall($id)
{
	global $lang, $station;
	_log("trackCall(): globals... lang=$lang, station=$station");

	$postData = array('id' => $id);
	if ($lang != Lang::NOT_SET)
		$postData['lang'] = $lang;
	if ($station != Station::NOT_SET)
		$postData['station'] = $station;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, Config::CALL_TRACK_URL);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);

	_log("Posted request array [" . implode(",", $postData) . "] to URL " 
		 . Config::CALL_TRACK_URL);
	_log("Response was \"" . $response . "\"");

	if ($response != "")
	{
		$response_ = explode(",", $response);
		$lang = $response_[0];
		$station = $response_[1];
	}

	curl_close($ch);

	_log("Language=$lang, Station=$station");
}


_log("Answering call from \"" . $currentCall->callerID . "\"");
answer();

// SMS callback
if (isset($numberToSMS) && isset($type))
{
	_log("attempting SMS, number=$numberToSMS, type=$type");
	call(parseNumber($numberToSMS), array("network" => "SMS"));
	if ($type == "sms1")
		say("SMS 1");
	else if ($type == "sms2")
		say("SMS 2");
	else
		_log("Error selecting SMS type");
	exit(0);
} 
else if (isset($token))
{
	_log("Ignoring SMS / callback request");
	exit;
}


_log("Wait for " . Config::ANSWER_WAIT);
wait(Config::ANSWER_WAIT);

trackCall($currentCall->callerID);

if ($station == Station::NOT_SET)
{
	_log("Initialising station to STATION1");
	$station = Station::STATION1;
}
else
	_log("Call tracked, station=$station, lang=$lang");

switch ($station)
{

	case Station::STATION1:
	{
		blockSay(1, false); // No language set
		blockAsk(2, Lang::ENG . "," . Lang::SLO, "langHandler", false);
		blockSay(3);
		blockSay(4);

		// Transition to next station
		$station = Station::STATION2;
		trackCall($currentCall->callerID);
		hangup();

		break;
	}

	case Station::STATION2:
	{
		blockAsk(5, "$station", "stationHandler1");
		blockSay(8);
		blockSay(9);

		$station = Station::STATION3;
		trackCall($currentCall->callerID);
		hangup();
		break;
	}

	case Station::STATION3:
	{
		blockAsk(10, "$station", "stationHandler1");
		blockSay(13);
		blockSay(14);
		blockSay(15);
		sms("sms1");
		$station = Station::POST_VISIT;
		break;
	}

	case Station::NOT_SET: default:
	{
		_log("Error occured determining station");
		exit;
		break;
	}

	case Station::POST_VISIT: { exit; break; }
}

wait(Config::POST_VISIT_WAIT);

sms("sms2");

exit;

?>
