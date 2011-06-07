<?php
$fileName = "/tmp/counter.txt";

$buf = file($fileName);
if (!isset($_GET['increment']))
{
	echo "Increment var is not set, counter = " . $buf[0];
	exit(0);
}

$fh = fopen($fileName, 'w') or die("Can't open file for writing");
if ($fh)
{
	$out = 0;
	if (strlen($buf[0]) > 0)
		$out = $buf[0] + 1;
	else
		$out = 1;
	fputs($fh, "$out");
	fclose($fh);
}

echo "counter is " . $out;
exit(0);
?>
