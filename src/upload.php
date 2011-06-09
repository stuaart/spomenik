<?php

include "header.php";

if (!isset($_FILES['filename']))
{
	echo "Invalid filename";
	exit;
}

$file = cleanVar($_POST['block']);

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
