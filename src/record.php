<?php

include "header.php";

$user = Config::MYSQL_USER;
$password = file_get_contents("/home/stuart/mysql-passwd.txt");
$database = Config::MYSQL_DB;

if (isset($_GET['id']))
	$id = cleanVar($_GET['id']);
else if (isset($_POST['id']))
	$id = cleanVar($_POST['id']);


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

if (!isset($_FILES['filename']) || !isset($id))
{
	echo "No ID or invalid filename";
	exit;
}


if (file_exists(Config::UPLOAD_DIR) && is_dir(Config::UPLOAD_DIR))
{
	$ext = strrchr(basename($_FILES['filename']['name']), ".");
	$file = Config::UPLOAD_DIR . uniqid() . $ext;

	echo '<pre>';
	if (is_uploaded_file($_FILES['filename']['tmp_name']))
	{
		if (move_uploaded_file($_FILES['filename']['tmp_name'], $file))
		{
//			echo "File is valid, and was successfully uploaded, file=$file.\n";
			$res = mysql_query("SELECT * FROM user WHERE id = '$id'");
			$row = mysql_fetch_assoc($res);
			if (mysql_query("UPDATE user SET recording = '$file' 
							 WHERE id = '$id'"))
			{
				echo "File successfully added as recording";
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

//echo 'Here is some more debugging info:';
//print_r($_FILES);

print "</pre>";

?>
