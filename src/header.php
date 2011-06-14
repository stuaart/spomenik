<?
class MySQL
{
	const USER = "root";
	const HOST = "localhost";
	const DBNAME = "spomenik";
	const PASSWD_FILE = "/home/stuart/mysql-passwd.txt";
}

function readConfig()
{
	$user = MySQL::USER;
	$host = MySQL::HOST;
	$password = file_get_contents(MySQL::PASSWD_FILE);
	$database = MySQL::DBNAME;

	if (!mysql_connect($host, $user, $password))
	{
		return "Unable to connect to database: " . mysql_error();
	}

	if (!mysql_select_db($database))
	{
		return "Unable to select database: " . mysql_error();
	}

	if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'config'")))
	{
		mysql_query("CREATE TABLE config (id VARCHAR(50) NOT NULL, 
										  value TEXT NOT NULL)");
		mysql_query(
			"INSERT INTO config 
				VALUES('sms1', '" . Config::$SMS1 . "'),
				('sms2', '" . Config::$SMS2 . "'),
				('answer_wait', '" . Config::$ANSWER_WAIT . "'),
				('post_visit_wait', '" . Config::$POST_VISIT_WAIT . "'),
				('max_record_time', '" . Config::$MAX_RECORD_TIME . "'),
				('record_silence_timeout', '" 
					. Config::$RECORD_SILENCE_TIMEOUT . "'),
				('input_timeout', '" . Config::$INPUT_TIMEOUT . "'),
				('max_repeats', '" . Config::$MAX_REPEATS . "')"
		);

	}
	else
	{
		$res = mysql_query("SELECT * FROM config");
		while ($row = mysql_fetch_assoc($res))
		{
			switch ($row['id'])
			{
				case "sms1": { Config::$SMS1 = $row['value']; break; }
				case "sms2": { Config::$SMS2 = $row['value']; break; }
				case "answer_wait": 
				{ 
					Config::$ANSWER_WAIT = $row['value']; break; 
				}
				case "post_visit_wait": 
				{ 
					Config::$POST_VISIT_WAIT = $row['value']; break; 
				}
				case "max_record_time": 
				{ 
					Config::$MAX_RECORD_TIME = $row['value']; break; 
				}
				case "record_silence_timeout": 
				{
					Config::$RECORD_SILENCE_TIMEOUT = $row['value']; break;
				}
				case "input_timeout": 
				{ 
					Config::$INPUT_TIMEOUT = $row['value']; break; 
				}
			}
		}
	}

	mysql_close();

	return;
}
?>
