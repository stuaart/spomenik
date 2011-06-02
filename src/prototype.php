<?php

// Note: this is so that the Tropo server can execute the script
header('content-type: text/plain');
echo '<?php';
?>

$voiceToken = "00e629d88333c54b8a4fd05a221f0a3b3a6585f8ea09e8669f3a48fa7f0559cff26fe5bd5e93bca31409cd45";
$smsToken = "00e63a663584c54481de8c285b38f1093e702a9095793689ebd382a8b82e190081456b006d124b61988c2843";

$smsURL = "http://api.tropo.com/1.0/sessions?action=create&token=" . $smsToken;

$callbackURL = "https://api.tropo.com/1.0/sessions?action=create&token=" 
			   . $voiceToken;

$counterURL = "http://horizab1.miniserver.com/~stuart/record.php?increment=true";

$callbackDelay = 60000; // 1 minute

$introStr = "Welcome to the Spomenik Holocaust Museum prototype. Here you will be able to use your mobile phone to find out even more about the memorials at the centre.";

$introOptStr = "Press 1 to continue, Press 2 to go back.";
$introOptChoices = "1,2";

$part1aStr = "Your journey starts when many others ended at the railyard. Millions were transported to their ends this way and this serves as a reminder of not only this end but also of hopefully the end of such events.";

$part1bStr = "Please walk down the path to the rose garden.";

$part1OptStr = "Press 1 to continue, Press 2 to go back.";
$part1OptChoices = "1,2";

$part2Str = "This part of the Rose Garden is dedicated to Rachel Rapaport who died in the Holocaust.";

$part2OptStr = "Press 1 to find out about Rachel, Press 2 to continue and Press 3 to go back.";
$part2OptChoices = "1,2,3";

$storyStr = "Rachel had such an amazing story and we only wish she was still here to tell it...";

$part3Str = "Thanks for continuing. Take your time in the garden and then walk to the pile of stones. I will call you back shortly.";

$part4Str = "This is the Childrens Memorial. Here you can participate in the ancient Jewish ritual of grieving and commemorate those lost in the Holocaust. Please throw a stone to do so.";

$storySMSStr = "Link to page about Rachel";
$endSMSStr = "You've commemorated the struggle to remember the Holocaust. To find out more please click the .link. to find out more.";

// UK only
function parseNumber($num)
{
	if (substr($num, 0, 1) == 0)
		$out = substr($num, 1, strlen($num));

	return "+44" . $out; 
}

if (isset($numberToDial))
{
	_log("Attempting callback");
	call(parseNumber($numberToDial));
	wait(1000);
	say($part4Str);
	say("I am now texting you with further information. Your visit to the memorial centre has also been recorded online. Goodbye.");

	$ch = curl_init($counterURL);
	curl_exec($ch);
	curl_close($ch);

	$ch = curl_init($smsURL . "&type=end&numberToSMS=$numberToDial");
	curl_exec($ch);
	curl_close($ch);

	hangup();
	exit(0);
} 
else if (isset($numberToSMS) && isset($type))
{
	_log("attempting SMS, number=$numberToSMS, type=$type");
	call(parseNumber($numberToSMS), array("network" => "SMS"));
	if ($type == "story")
		say($storySMSStr);
	else if ($type == "end")
		say($endSMSStr);
	else
		_log("Error selecting SMS type");
	exit(0);
} 
else if (isset($token))
{
	_log("Ignoring SMS / callback request");
	exit(0);
}


_log("call initiated from " . $currentCall->callerID);

answer();
wait(3000);

intro();

part1();

$story = false;
story();
if ($story)
{
	say($storyStr);
	say("We are now sending you a text message with futher information");
	$ch = curl_init($smsURL . "&type=story&numberToSMS=" . $currentCall->callerID);
	curl_exec($ch);
	curl_close($ch);
}

say($part3Str); wait(1000);

hangup();

wait($callbackDelay);

_log("Starting callback, url being hit=".$callbackURL . "&numberToDial=" . $currentCall->callerID);

$ch = curl_init($callbackURL . "&numberToDial=" . $currentCall->callerID);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
curl_close($ch);

exit(0);

function intro()
{
	global $introStr, $introOptStr, $introOptChoices;
	wait(1000);
	say($introStr);
	opt($introOptStr, $introOptChoices, "introSel");
}

function part1()
{
	global $part1aStr, $part1bStr, $part1OptStr, $part1OptChoices;
	wait(1000);
	say($part1aStr); wait(1000); say($part1bStr);
	opt($part1OptStr, $part1OptChoices, "part1Sel");
}

function story()
{
	global $part2Str, $part2OptStr, $part2OptChoices;
	wait(1000);
	say($part2Str);
	opt($part2OptStr, $part2OptChoices, "storySel"); 
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

function introSel($event)
{
	switch ($event->value)
	{
		case 1: { break; }
		case 2:
		default: { intro(); break; }
	}
}

function part1Sel($event)
{
	switch ($event->value)
	{
		case 1: { break; }
		case 2:
		default: { part1(); break; }
	}
}

function storySel($event)
{
	global $story;
	switch ($event->value)
	{
		case 1: { $story = true; break; }
		case 2: { break; }
		case 3:
		default: { story(); break; }
	}
}

function errorMsg($event) 
{
	say("I didn't understand that. Please try again.");
}

?>
