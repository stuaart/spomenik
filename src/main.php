<?php //main.php

// Note: this is so that the Tropo server can execute the script
header('content-type: text/plain');
echo '<?php
//
';

$header = file_get_contents("./header.php");
$smsKey = file_get_contents("/home/stuart/tropo_key.txt");
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


define ("SMS_URL", SMS_BASE_URL . $smsKey);

// Globals
$lang = Lang::SLO; // Language is always Slovenian // Lang::NOT_SET;
$station = Station::NOT_SET;
$callID = $currentCall->callerID;

// UK only
function parseNumber($num)
{
	if (substr($num, 0, 1) == 0)
		$out = substr($num, 1, strlen($num));

	return "+44" . $out; 
}

function logger($entry)
{
	global $callID;

	$postData = array();
	$postData["id"] = "$callID";
	$postData["entry"] = $entry;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, Config::SYSTEM_LOG_URL);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_exec($ch);
}

function sms($type)
{
	global $callID;
	$smsURL = SMS_URL . "&type=" . $type . "&number=" . $callID;
	_log("Setting SMS callback, URL being called is $smsURL");
	logger("sms=$type");
	$ch = curl_init($smsURL);
	curl_exec($ch);
	curl_close($ch);
}

function errorMsg($event) 
{
	say(Config::ERROR_MESSAGE);
}

function opt($opts, $choices, $handler, $timeoutHandler)
{
	_log("opt(), opts=$opts, choices=$choices");
	$res = ask($opts,
			   array("choices" => $choices, 
					 "mode" => "dtmf", "onChoice" => "$handler", 
					 "timeout" => 10,
					 "onTimeout" => "$timeoutHandler",
					 "onBadChoice" => "$handler",
					 "onHangup" => "hangupHandler"
				)
		   );
}

function hangupHandler($event)
{
	_log("User hungup");
	logger("hangupHandler");
	// Exit to preserve state
	exit;
}

function getBlockFile($num, $langSet)
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

	return $file;
}


function blockSay($num, $langSet=true)
{
	global $currentCall;
	$file = getBlockFile($num, $langSet);
	_log("Saying from file $file");
	logger("saying=$num,file=$file");
	if (!$currentCall->isActive)
		logger("inactive");
	else 
		logger("active");
	say($file);
}

function blockAsk($num, $opts, $handler, $timeoutHandler, $langSet=true)
{
	$file = getBlockFile($num, $langSet);
	_log("Asking from file $file");
	logger("asking=$num,file=$file");
	opt($file, $opts, $handler, $timeoutHandler);
}

function blockRec($num, $langSet=true)
{
	global $callID;
	$file = getBlockFile($num, $langSet);
	logger("recording=$num,file=$file");
	record($file, array(
		   "beep" => true,
		   "timeout" => 10,
		   "maxTime" => Config::MAX_RECORD_TIME,
		   "silenceTimeout" => 5,
		   "recordFormat" => "audio/mp3",
		   "recordMethod" => "POST",
		   "recordURI" => Config::RECORD_URL . "?id=$callID")
	);
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

function timeoutHandler($event)
{
	// TODO
}

// Handlers for Station::STATION1
function stationHandler1Timeout($event)
{
	//TODO
}
function stationHandler1_1($event)
{
	global $station;
	_log("stationHandler1_1() value=" . $event->value . " station=$station");
	if ($event->value != $station)
	{
		blockAsk(3, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler1_2", "stationHandler1Timeout");
	}
}
function stationHandler1_2($event)
{
	global $station;
	_log("stationHandler1_2() value=" . $event->value . " station=$station");
	if ($event->value != $station)
	{
		_log("Looping back to blockAsk(2...)");
		blockAsk(2, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler1_1", "stationHandler1Timeout");
	}
}

// Handlers for Station::STATION2
function stationHandler2Timeout($event)
{
	//TODO
}
function stationHandler2_1($event)
{
	global $station;
	_log("stationHandler2_1() value=" . $event->value . " station=$station");
	if ($event->value != $station)
		blockAsk(8, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler2_2", "stationHandler2Timeout");
}
function stationHandler2_2($event)
{
	global $station;
	_log("stationHandler2_2() value=" . $event->value . " station=$station");

	if ($event->value == Station::STATION1)
	{
		blockAsk(10, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler2_3", "stationHandler2Timeout");
	}
	else if ($event->value != Station::STATION2)
	{
		_log("Looping back to blockAsk(7...)");
		blockAsk(7, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler1_1", "stationHandler2Timeout");
	}
	
	// Else continue along back to main call
	_log("stationHandler2_2(), back to main call, i.e., station 2");
}
function stationHandler2_3($event)
{
	if ($event->value == Station::STATION1)
	{
		blockSay(12);
		blockAsk(13, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler2_4", "stationHandler2Timeout");
	}
	else if ($event->value != Station::STATION2)
	{
		blockAsk(10, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler2_3", "stationHandler2Timeout");
	}
	_log("stationHandler2_3(), back to main call, i.e., station 2");
}
function stationHandler2_4($event)
{
	global $station;
	$station = Station::STATION1;
	trackCall();
	_log("stationhandler2_4(), fall out to main call with Station::STATION1");
}

function trackCall()
{
	global $lang, $station, $callID;
	_log("trackCall(): globals... lang=$lang, station=$station, id=$callID");

	$postData = array();
	$postData["id"] = "$callID";
	if ($lang != Lang::NOT_SET)
		$postData["lang"] = $lang;
	if ($station != Station::NOT_SET)
		$postData["station"] = $station;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, Config::CALL_TRACK_URL);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);

	$arrayStr = "[";
	foreach (array_keys($postData) as $key)
		$arrayStr .= ("" . $key . " => " . $postData[$key] . ", ");
	$arrayStr .= "]";
	_log("Posted request array " . $arrayStr . " to URL " 
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


_log("Answering call from \"" . $callID . "\"");
logger("answer");
answer();

// SMS callback
if (isset($number) && isset($type))
{
	trackCall();
	// Note: SMSes only work in POST_VISIT state
	if ($station == Station::POST_VISIT)
	{
		_log("Triggered SMS callback, creating SMS for number=$number, 
			  type=$type");
		call(parseNumber($number), array("network" => "SMS"));
		if ($type == "sms1")
			say(SMSPayload::MESSAGE1);
		else if ($type == "sms2")
			say(SMSPayload::MESSAGE2);
		else
			_log("Error selecting SMS type");
	}
	exit;
} 
else if (isset($token))
{
	_log("Ignoring SMS / callback request");
	exit;
}


_log("Wait for " . Config::ANSWER_WAIT);
wait(Config::ANSWER_WAIT);

trackCall();

if ($station == Station::NOT_SET)
{
	_log("Initialising station to STATION1");
	logger("init");
	$station = Station::STATION1;
}
else
	_log("Call tracked, station=$station, lang=$lang");

logger("station=$station");

function station1()
{
	global $station;
	blockSay(1);
	//blockAsk(2, Lang::ENG . "," . Lang::SLO, "langHandler", false);
	blockAsk(2, "" . Station::STATION1 . "," . Station::STATION2 . "", 
			 "stationHandler1_1", "stationHandler1Timeout");
	blockSay(5);
	blockSay(6);
}

switch ($station)
{
	case Station::STATION1:
	{
		station1();
		// Transition to next station
		$station = Station::STATION2;
		trackCall();
		hangup();

		break;
	}

	case Station::STATION2:
	{
		blockAsk(7, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler2_1", "stationHandler2Timeout");

		// Deeply ugly
		if ($station != Station::STATION2)
		{
			station1();
			$station = Station::STATION2;
			trackCall();
		}
		else
		{
			blockSay(11);
			blockSay(14);
			blockSay(15);
		
			$station = Station::POST_VISIT;
			trackCall();

			blockSay(16);
			blockRec($callID, 17);
			sms("sms1");
		}
		hangup();
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

if ($station == Station::POST_VISIT)
{
	logger("station=" . Station::POST_VISIT);
	_log("Waiting for " . Config::POST_VISIT_WAIT . " before sms2");
	wait(Config::POST_VISIT_WAIT);

	sms("sms2");

	_log("Resetting user to " . Station::STATION1);
	$station = Station::STATION1;
	trackCall();
}

hangup();

exit;

?>
