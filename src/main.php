<?php //main.php

// Note: this is so that the Tropo server can execute the script
header('content-type: text/plain');
echo '<?php
//
';
include_once("header.php");
include_once("header_shared.php");

$header = file_get_contents("./header_shared.php");
$smsKey = file_get_contents(Sys::TROPO_KEY_FILE);
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
$cleanHeader = str_replace(array("<?php", "?>", "<?"), "", $header);
echo $cleanHeader;

echo "global \$smsKey;\n \$smsKey = \"$smsKey\";\n";

readConfig();
echo "Config::\$SMS1 = \"" . Config::$SMS1 . "\";\n";
echo "Config::\$SMS2 = \"" . Config::$SMS2 . "\";\n";
echo "Config::\$ANSWER_WAIT = " . Config::$ANSWER_WAIT . ";\n";
echo "Config::\$POST_VISIT_WAIT = " . Config::$POST_VISIT_WAIT . ";\n";
echo "Config::\$MAX_RECORD_TIME = " . Config::$MAX_RECORD_TIME . ";\n";
echo "Config::\$RECORD_SILENCE_TIMEOUT = " . 
		Config::$RECORD_SILENCE_TIMEOUT . ";\n";
echo "Config::\$INPUT_TIMEOUT = " . Config::$INPUT_TIMEOUT . ";\n";
echo "Config::\$MAX_REPEATS = " . Config::$MAX_REPEATS . ";\n";


?>


define ("SMS_URL", Sys::SMS_BASE_URL . $smsKey);

// Globals
$callID = $currentCall->callerID;
$repeats = 0;
$lang = Lang::NOT_SET;
$station = Station::NOT_SET;


// UK + SLO only
function parseNumber($num)
{
	$prefixes = array();

	$sloPrefixes = explode(",", Routing::SLO_LOCAL_PREFIX);
	foreach ($sloPrefixes as $p)
		$prefixes[$p] = Routing::SLO_INT_PREFIX;
	$ukPrefixes = explode(",", Routing::UK_LOCAL_PREFIX);
	foreach ($ukPrefixes as $p)
		$prefixes[$p] = Routing::UK_INT_PREFIX;
	$prefix = $prefixes[substr($num, 0, 3)];

	if (substr($num, 0, 1) == 0)
		$out = substr($num, 1, strlen($num));

	_log("parseNumber($num) result = \"" . $prefix . $out . "\"");
	return $prefix . $out; 
}


function sms($type)
{
	global $callID;
	$smsURL = SMS_URL . "&type=" . $type . "&number=" . $callID;
	_log("Setting SMS callback, URL being called is $smsURL");
	logger($callID, "sms,state=callback,type=$type,number=$callID");
	$ch = curl_init($smsURL);
	curl_exec($ch);
	curl_close($ch);
}

function opt($opts, $choices, $handler, $timeoutHandler)
{
	_log("opt(), opts=$opts, choices=$choices");
	$res = ask($opts,
			   array("choices" => $choices, 
					 "mode" => "dtmf", "onChoice" => "$handler", 
					 "timeout" => Config::$INPUT_TIMEOUT,
					 "onTimeout" => "$timeoutHandler",
					 "onBadChoice" => "$handler",
					 "onHangup" => "hangupHandler"
				)
		   );
}

function hangupHandler($event)
{
	global $callID;
	_log("User hungup");
	logger($callID, "hangupHandler,event=" . $event->value);
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
			{
				$blockStr .= ("-" . $lang . ".mp3");
				break;
			}
			case Lang::SLO: 
			{ 
				$blockStr .= ("-" . $lang . ".ulaw"); 
				break; 
			}
			default: { break; }
		}
	}

	$file = Sys::AUDIO_BASE_URL . "/" . $blockStr;

	return $file;
}

function logActive()
{
	global $currentCall, $callID;
	if (!$currentCall->isActive())
		logger($callID, "logActive,event=inactive");
	else 
		logger($callID, "logActive,event=active");
}

function blockSay($num, $langSet=true)
{
	global $currentCall, $callID;
	$file = getBlockFile($num, $langSet);
	_log("Saying from file $file");
	logger($callID, "blockSay,num=$num,file=$file");
	logActive();
	say($file);
	logActive();
}

function blockAsk($num, $opts, $handler, $timeoutHandler, $langSet=true)
{
	global $callID;
	$file = getBlockFile($num, $langSet);
	_log("Asking from file $file");
	logger($callID, "blockAsk,num=$num,file=$file");
	opt($file, $opts, $handler, $timeoutHandler);
}

function blockRec($num, $langSet=true)
{
	global $callID;
	$file = getBlockFile($num, $langSet);
	logger($callID, "blockRec,num=$num,file=$file");
	record($file, array(
		   "beep" => true,
		   "timeout" => Config::$INPUT_TIMEOUT,
		   "maxTime" => Config::$MAX_RECORD_TIME,
		   "terminator" => "#",
		   "silenceTimeout" => Config::$RECORD_SILENCE_TIMEOUT,
		   "recordFormat" => "audio/mp3",
		   "recordMethod" => "POST",
		   "recordURI" => Sys::RECORD_URL . "?id=$callID")
	);
}

function langHandler($event)
{
	global $lang, $callID;
	logger($callID, "langHandler,event=" . $event->value);
	switch ($event->value)
	{
		case Lang::ENG: case Lang::SLO: { $lang = $event->value; break; }
		default: { blockSay(2); break; }
	}

}

// Handlers for Station::STATION1
function stationHandler1Timeout($event)
{
	global $callID, $repeats;
	if ($repeats > Config::$MAX_REPEATS-1)
	{
		logger($callID, "stationHandler1Timeout,exit");
		exit;
	}

	logger($callID, "stationHandler1Timeout,event=" . $event->value);
	blockSay(4);
	++$repeats;
	_log("Looping back to blockAsk(2...)");
	blockAsk(2, "" . Station::STATION1 . "," . Station::STATION2 . "",
             "stationHandler1_1", "stationHandler1Timeout");
}
function stationHandler1_1($event)
{
	global $station, $callID;
	logger($callID, "stationHandler1_1,event=" . $event->value);
	_log("stationHandler1_1() value=" . $event->value . " station=$station");
	if ($event->value != $station)
	{
		blockAsk(3, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler1_2", "stationHandler1Timeout");
	}
}
function stationHandler1_2($event)
{
	global $station, $callID;
	logger($callID, "stationHandler1_2,event=" . $event->value);
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
	global $callID, $repeats;
	if ($repeats > Config::$MAX_REPEATS-1)
	{
		logger($callID, "stationHandler1Timeout,exit");
		exit;
	}

	logger($callID, "stationHandler2Timeout,event=" . $event->value);
	blockSay(9);
	++$repeats;
	_log("Looping back to blockAsk(7...)");
    blockAsk(7, "" . Station::STATION1 . "," . Station::STATION2 . "",
             "stationHandler1_1", "stationHandler2Timeout");
}
function stationHandler2_1($event)
{
	global $station, $callID;
	logger($callID, "stationHandler2_1,event=" . $event->value);
	_log("stationHandler2_1() value=" . $event->value . " station=$station");
	if ($event->value != $station)
	{
		blockAsk(8, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler2_2", "stationHandler2Timeout");
	}
}
function stationHandler2_2($event)
{
	global $station, $callID;
	logger($callID, "stationHandler2_2,event=" . $event->value);
	_log("stationHandler2_2() value=" . $event->value . " station=$station");

	if ($event->value == Station::STATION1)
	{
		blockSay(10);
		blockAsk(11, "" . Station::STATION1 . "," . Station::STATION2 . "", 
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
	global $station, $callID;
	logger($callID, "stationHandler2_3,event=" . $event->value);
	$station = Station::STATION1;
	trackCall();
	_log("stationhandler2_3(), fall out to main call with Station::STATION1");
}

// Handlers for Station::STATION2_PART3
function stationHandler2P3Timeout($event)
{
	global $callID, $repeats;	
	if ($repeats > Config::$MAX_REPEATS-1)
	{
		logger($callID, "stationHandler1Timeout,exit");
		exit;
	}

	logger($callID, "stationHandler2P3Timeout,event=" . $event->value);
	blockSay(15);
	++$repeats;
	_log("Looping back to blockAsk(13...)");
    blockAsk(13, "" . Station::STATION2_PART3 . "",
             "stationHandler2P3_1", "stationHandler2P3Timeout");
}
function stationHandler2P3_1($event)
{
	global $station, $callID;
	logger($callID, "stationHandler2P3_1,event=" . $event->value);
	_log("stationHandler2P3_1() value=" . $event->value . " station=$station");
	$station = Station::STATION2_PART3;
	if ($event->value != $station)
	{
		blockAsk(14, "" . Station::STATION2 . "," 
						. Station::STATION2_PART3 . "", 
				 "stationHandler2P3_2", "stationHandler2P3Timeout");
	}
}
function stationHandler2P3_2($event)
{
	global $station, $callID;
	logger($callID, "stationHandler2P3_2,event=" . $event->value);
	_log("stationHandler2P3_2() value=" . $event->value . " station=$station");

	if ($event->value == Station::STATION2)
	{
		blockSay(16);
		blockAsk(17, "" . Station::STATION2 . "", 
				 "stationHandler2P3_3", "stationHandler2P3Timeout");
	}
	else if ($event->value != Station::STATION2_PART3)
	{
		_log("Looping back to blockAsk(13...)");
		blockAsk(13, "" . Station::STATION1 . "," . Station::STATION2 . "", 
				 "stationHandler2P3_1", "stationHandler2P3Timeout");
	}
	
	// Else continue along back to main call
	_log("stationHandler2P3_2(), back to main call, i.e., station 2");
}
function stationHandler2P3_3($event)
{
	global $station, $callID;
	logger($callID, "stationHandler2P3_3,event=" . $event->value);
	$station = Station::STATION2;
	trackCall();
	_log("stationhandler2P3_3(), fall out to main call with Station::STATION2");
}


function trackCall()
{
	global $lang, $station, $callID;
	logger($callID, "trackCall,state=setting,lang=$lang,station=$station");
	_log("trackCall(): globals... lang=$lang, station=$station, id=$callID");

	$url = Sys::CALL_TRACK_URL . "?callID=$callID"; // Note POST and GET vars

	$postData = array();
	$postData["callID"] = "$callID";
	if ($lang != Lang::NOT_SET)
	{
		$postData["lang"] = $lang;
		$url .= "&lang=$lang";
	}
	if ($station != Station::NOT_SET)
	{
		$postData["station"] = $station;
		$url .= "&station=$station";
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);

	$arrayStr = "[";
	foreach (array_keys($postData) as $key)
		$arrayStr .= ("" . $key . " => " . $postData[$key] . ", ");
	$arrayStr .= "]";
	_log("Posted request array $arrayStr to URL $url");
	_log("Response was \"" . $response . "\"");

	if ($response != "")
	{
		$response_ = explode(",", $response);
		$lang = $response_[0];
		$station = $response_[1];
	}

	curl_close($ch);

	logger($callID, "trackCall,state=response,lang=$lang,station=$station");

	_log("Language=$lang, Station=$station");
}


if ($callID != "")
{
	_log("Answering call from \"" . $callID . "\"");
	logger($callID, "answer,state=voice");
	answer();
}
else 
{
	_log("Caught potential SMS callback, type=$type, number=$number");
	logger($number, "answer,state=sms");

	// SMS callback
	if (isset($number) && isset($type))
	{
		global $callID;
		$callID = $number;
			
		$parsedNumber = parseNumber($number);
		trackCall();
		
		// Note: SMSes only work in POST_VISIT state, state is only reset once 
		// user has been sent SMS2
		if ($station == Station::POST_VISIT)
		{
			_log("Triggered SMS callback, creating SMS for number=$number, 
				  type=$type");
			call($parsedNumber, array("network" => "SMS"));
			if ($type == "sms1")
			{
				say(Config::$SMS1);
				logger($number,
					"sms,type=$type,state=sent,number=$parsedNumber,message=\""
					. Config::$SMS1 . "\""
				);
			}
			else if ($type == "sms2")
			{
				say(Config::$SMS2);
				logger($number,
					"sms,type=$type,state=sent,number=$parsedNumber,payload=\""
				    . Config::$SMS2 . "\""
				);
				_log("Resetting user to " . Station::STATION1);
				$station = Station::STATION1;
				trackCall();
			}
			else
				_log("Error selecting SMS type");
		}
	}
	else
		_log("SMS callback failed, number=$number, type=$type");
	exit;
}

if (isset($token))
{
	$numberStr = $callID;
	if (isset($number))
		$numberStr = $number;
	_log("Ignoring SMS / callback request");
	logger($numberStr, "ignored callback");
	exit;
}


_log("Wait for " . Config::$ANSWER_WAIT . " seconds");
wait(Config::$ANSWER_WAIT * 1000);

trackCall();
// Set the default non-administrator language
if ($lang != Lang::ENG)
	$lang = Lang::SLO;

if ($station == Station::NOT_SET)
{
	_log("Initialising station to STATION1");
	$station = Station::STATION1;
	logger($callID, "init");
}
else
	_log("Call tracked, station=$station, lang=$lang");

logger($callID, "station=$station");

function station1()
{
	blockSay(1);
	//blockAsk(2, Lang::ENG . "," . Lang::SLO, "langHandler", false);
	blockAsk(2, "" . Station::STATION1 . "," . Station::STATION2 . "", 
			 "stationHandler1_1", "stationHandler1Timeout");
	trackCall();
	blockSay(5);
	blockSay(6);
}

function station2p2()
{
	blockSay(12);
	blockAsk(13, "" . Station::STATION2_PART3 . "", 
		 	 "stationHandler2P3_1", "stationHandler2P3Timeout");
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

	// Special case of calling up and your state is still STATION2_PART3 
	// (unlikely). Solution: reset state and fall through back to STATION2 stuff
	case Station::STATION2_PART3:
	{
		$station = Station::STATION2;
		trackCall();
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
			station2p2();
			while ($station != Station::STATION2_PART3)
			{
				station2p2();
				trackCall();
			}

			blockSay(18);
	
			$station = Station::POST_VISIT;
			trackCall();

			blockSay(19);
			blockSay(20);
			blockSay(21);
			blockRec(22);

		}
		hangup();
		break;
	}

	case Station::NOT_SET: default:
	{
		_log("Error occured determining station");
		break;
	}

	case Station::POST_VISIT: { break; }
}


if ($station == Station::POST_VISIT)
{
	sms("sms1");
	logger($callID, "station=" . Station::POST_VISIT);
	_log("Waiting for " . Config::$POST_VISIT_WAIT . " seconds before sms2");
	wait(Config::$POST_VISIT_WAIT * 1000);

	sms("sms2");
}
else
{
	hangup();
	exit;
}

?>
