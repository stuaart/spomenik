<?php

// Note: this is so that the Tropo server can execute the script
header('content-type: text/plain');
echo '<?php';
?>

define("LANG_ENG", 1);
define("LANG_SLO", 2);
define("LANG_NONE", 0);

// Consts to configure
class Config
{
	const SMS_URL = "http://api.tropo.com/1.0/sessions?action=create&token=<TOKEN>";
	const ANSWER_WAIT = 3000;
	const ERROR_MESSAGE = "http://url.to.mp3";
	const AUDIO_BASE_URL = "http://url.to.mp3/audio";
}

// Globals
$lang = LANG_NONE;

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
					 "onBadChoice" => "errorMsg"
				)
		   );
}

function blockSay($num)
{
	global $lang;
	$blockStr = "block" . $num;
	switch ($lang)
	{
		case LANG_ENG: case LANG_SLO: { $blockStr .= ("-" . $lang); break; }
		default: { break; }
	}

	say(Config::AUDIO_BASE_URL . "/" . $blockStr . ".mp3");
}

function blockAsk($num, $opts, $handler)
{
	global $lang;
	//wait();
	$blockStr = "block" . $num;
	switch ($lang)
	{
		case LANG_ENG: case LANG_SLO: { $blockStr .= ("-" . $lang); break; }
		default: { break; }
	}

	opt(Config::AUDIO_BASE_URL . "/" . $blockStr . ".mp3", $opts, $handler);
}

function langHandler($event)
{
	global $lang;
	switch ($event->value)
	{
		case LANG_ENG: { $lang = LANG_ENG; break; }
		case LANG_SLO: { $land = LANG_SLO; break; }
		default: { block2(); break; }
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
blockSay(1);
blockAsk(2, LANG_ENG . "," . LANG_SLO, "langHandler");
block(5, $lang);
block(6, $lang);
hangup();

?>
