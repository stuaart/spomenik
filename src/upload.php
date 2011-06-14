<?php // upload.php

include_once("header.php");
include_once("header_shared.php");

$user = MySQL::USER;
$host = MySQL::HOST;
$password = file_get_contents(MySQL::PASSWD_FILE);

if (!isset($_FILES['filename']))
{
	echo "Invalid filename";
	exit;
}

if (!mysql_connect($host, $user, $password))
{
	echo "Unable to connect to database: " . mysql_error();
	exit;
}

$file = mysql_real_escape_string($_POST['block']);

mysql_close();

echo "<pre>";
if (file_exists(Sys::AUDIO_DIR) && is_dir(Sys::AUDIO_DIR))
{
	if (is_uploaded_file($_FILES['filename']['tmp_name']))
	{
		if (move_uploaded_file($_FILES['filename']['tmp_name'], 
			Sys::AUDIO_DIR . "/" . $file))
		{
			echo "File $file updated";
		}
		else
			echo "Error moving file";
	}
}

print_r($_FILES);
print_r($_POST);

?>
