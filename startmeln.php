<?php
	
	date_default_timezone_set('America/Detroit'); // Set your Timezone
	ini_set('error_reporting', E_ALL & ~E_NOTICE); // Turns off error spam, comment out if debugging.

	$core = array();
	$core['cwd'] == __DIR__; //Sets the current working folder.
	$core['starttime'] = strtotime("now"); //for uptime, I set it in here, and first so it's set exactly as it's started so it has an acurate uptime, leave this as is.
	$core['server'] = 'irc.network.com'; // Server's address
	$core['port'] = 6667; // Port to connect on
	$core['channels'] = array("#channel"); // Channel(s) to join
	$core['nick'] = 'BotNick'; // Bot's nick
	$core['name'] = 'meln'; // Bot's name
	$core['admin'] = array("YourNick" => array('host' => "your.host.name", 'lvl' => "20")); //admins, follow template for more than one
	$core['prefix'] = ':'; // Command prefix, eg. :say, ':' would be the prefix
	$core['nspass'] = 'BotNSPass'; // Nickserv password for the bot
	$core['googlekey'] = ""; // Google api key
	$core['udkey'] = ""; // UrbanDictionary api key
	$core['lastfm'] = "YourLastFMName"; // Last.fm username
	$core['debug'] = false; // Turns on debug mode, spits out what's in variables, keep this off unless you're developing.

	// Includes the rest of the bot
	include('core.php');
	include('modules.php');

?>


