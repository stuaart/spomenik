<?php //main.php

// Note: this is so that the Tropo server can execute the script
header('content-type: text/plain');
echo '<?php
//
';

$header = file_get_contents("./header.php");
$token = file_get_contents("./tropo_key.txt");
if (!$header)
{
	echo "Error getting header ?>";
	exit;
}
if (!$token)
{
	echo "Error getting key ?>";
	exit;
}
echo $header;
echo "\$token = $token";
?>

// Consts to configure
class Config
{
	const SMS_URL = 
		"http://api.tropo.com/1.0/sessions?action=create&token=$token";
	const ANSWER_WAIT = 3000;
	const ERROR_MESSAGE = "http://url.to.mp3";
	const AUDIO_BASE_URL = "http://url.to.mp3/audio";
	const CALL_TRACK_URL = "http://horizab1.miniserver.com/~stuart/tracker.php";
}

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
	$ch = curl_init(Config::SMS_URL . "&type=" . $type . "&numberToSMS=" . 
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
			   		 "attempts" => 3, "timeout" => 10.0, 
					 "mode" => "dtmf", "onChoice" => $handler, 
					 "onBadChoice" => $handler
				)
		   );
}

function blockSay($num)
{
	global $lang;

	$blockStr = "block" . $num;
	switch ($lang)
	{
		case Lang::ENG: case Lang::SLO: { $blockStr .= ("-" . $lang); break; }
		default: { break; }
	}

	say(Config::AUDIO_BASE_URL . "/" . $blockStr . ".mp3");
}

function blockAsk($num, $opts, $handler)
{
	global $lang;

	$blockStr = "block" . $num;
	switch ($lang)
	{
		case Lang::ENG: case Lang::SLO: { $blockStr .= ("-" . $lang); break; }
		default: { break; }
	}

	opt(Config::AUDIO_BASE_URL . "/" . $blockStr . ".mp3", $opts, $handler);
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

	if ($event->value != $station)
		blockAsk(6, "" . $station . "", "stationHandler2");
}

function stationHandler2($event)
{
	global $station;
	if ($event->value != $station)
		blockAsk(5, "2", "stationHandler1");
}

function trackCall($id)
{
	global $lang, $station;

	$postData = array('id' => $id);
	if ($lang != Lang::NOT_SET)
		$postData['setlang'] = $lang;
	if ($station != Station::NOT_SET)
		$postData['setstation'] = $station;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, Config::CALL_TRACK_URL);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	$response = curl_exec($ch);
	curl_close($ch);

	if ($response != "")
	{
		$response_ = explode(",", $response);
		$lang = $response_[0];
		$station = $respose_[1];
	}
}



// SMS callback
if (isset($numberToSMS) && isset($type))
{
	_log("attempting SMS, number=$numberToSMS, type=$type");
	call(parseNumber($numberToSMS), array("network" => "SMS"));
	if ($type == "stage1")
		say("SMS 1");
	else if ($type == "stage2")
		say("SMS 2");
	else
		_log("Error selecting SMS type");
	exit(0);
} 
else if (isset($token))
{
	_log("Ignoring SMS / callback request");
	exit(0);
}


answer();
wait(Config::ANSWER_WAIT);

trackCall($currentCall->callerID);

switch ($station)
{

	case Station::STATION1:
	{
		blockSay(1);
		blockAsk(2, Lang::ENG . "," . Lang::SLO, "langHandler");
		blockSay(3);
		blockSay(4);
		trackCall($currentCall->callerID);
		hangup();

		break;
	}

	case Station::STATION2:
	{
		blockAsk(5, "2", "stationHandler1");
		blockSay(8);
		blockSay(9);
		trackCall($currentCall->callerID);
		hangup();
		break;
	}

	case Station::STATION3:
	{
		break;
	}

	case Station::POST_VISIT:
	{
		break;
	}

	case Station::NOT_SET: default:
	{
		_log("Error occured determining station");
		break;
	}

}

?>
