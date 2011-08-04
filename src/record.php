<?php // header.php

include_once("header.php");
include_once("header_shared.php");

$user = MySQL::USER;
$password = file_get_contents(MySQL::PASSWD_FILE);
$database = MySQL::DBNAME;



if (!mysql_connect("localhost", $user, $password))
{
	echo "Unable to connect to database: " . mysql_error();
	exit;
}

if (!mysql_select_db($database))
{
	echo "Unable to select database: " . mysql_error();
	exit;
}


if (isset($_GET['id']))
	$id = mysql_real_escape_string($_GET['id']);
else if (isset($_POST['id']))
	$id = mysql_real_escape_string($_POST['id']);

if (!isset($_FILES['filename']) || !isset($id))
{
	logger($id, "recording,state=error");
	echo "No ID or invalid filename";
	exit;
}

if (file_exists(Sys::UPLOAD_DIR) && is_dir(Sys::UPLOAD_DIR))
{
	$ext = strrchr(basename($_FILES['filename']['name']), ".");
	$file = Sys::UPLOAD_DIR . uniqid() . $ext;

	echo '<pre>';
	if (is_uploaded_file($_FILES['filename']['tmp_name']))
	{
		if (move_uploaded_file($_FILES['filename']['tmp_name'], $file))
		{
//			echo "File is valid, and was successfully uploaded, file=$file.\n";
			$res = mysql_query("SELECT * FROM user WHERE id = '$id'");
			$row = mysql_fetch_assoc($res);
			if (mysql_query("UPDATE user SET recording = '$file',
											 recording_timestamp = NOW()
							 WHERE id = '$id'"))
			{
				echo "File successfully added as recording";
				logger($id, "recording,state=uploaded");
			}
		}
		else
			echo "Possible file upload attack for file=$file\n";
	}
	else
		echo "An error occured...\n";

}
else
	echo "Upload dir doesn't exist or is not a directory\n";

mysql_close();

print "</pre>";

?>
