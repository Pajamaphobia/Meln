<?php

	set_time_limit(0);
	include('commands.php'); // include the commands file
	init(); // starts the bot

	function init()
	{
		global $socket, $output, $core, $modules, $mods;

		$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP); // create the socket
		$connection = socket_connect($socket,$core['server'],$core['port']); // connect
		if (!$connection) { print ("Could not connect to: ".$core['server']." on port ".$core['port']); } // if you don't connect you'll get this
		
		else
		{
			raw("USER ".$core['name']." ".$core['name']." ".$core['name']." :".$core['name'], "no");
			raw("NICK ".$core['nick'], "no");

			while ($output['all'] = trim(socket_read($socket,4048)))
			{
				if (strstr($output['all'], "001 ".$core['nick'])) //so it only does this once, and after it's fully connected
				{
					if ($core['nspass'] != null)
					{
						pm("NickServ", "IDENTIFY ". $core['nspass']);

					}
					else
					{
						foreach ($core['channels'] as $channels) { raw("JOIN ". $channels); } // join all the channels from start.php
					}
					 raw("mode ".$core['nick']." +B", "no");
				}

				if (strstr($output['all'], "Password accepted - you are now recognized.")) #makes the bot wait until authed to join (in case of ops)
				{
					echo "\n\noutput: ".strstr($output['all'], "Password accepted - you are now recognized.")."\n\n";
					foreach ($core['channels'] as $channels) { raw("JOIN ". $channels); } // join all the channels from start.php
				}

				buffer(); //makes the data all perrty
				process(); //executes the commands when people wanna execute them
			}
		}
	}

	function raw($command, $show = "yes")
	{
		global $socket, $output, $core;
		socket_write($socket, $command."\n\r");
		if ($show == "yes") { echo "Raw: ".$command."\n"; }
	}

	function msg($message)
	{
		global $socket, $output, $core;
		$time = date('g:i:s');
		$command = "PRIVMSG ".$output['channel']." :".$message;
		socket_write($socket, $command."\n\r");
		echo $time." <".$core['nick']."> ".$message."\n";
	}
	
	function bdie($message)
	{
		global $socket, $output, $core;
		$command = "QUIT :".$message;
		socket_write($socket, $command."\n\r");
		echo "Quiting: ".$message."\n";
	}

	function search($array, $key, $value)
	{
		$results = array();
		
		if (is_array($array) && !is_numeric($value))
		{
			$value = strtolower($value);
			if (fnmatch("*".$value."*", strtolower($array[$key]))) { $results[] = $array; }
			foreach ($array as $subarray) { $results = array_merge($results, search($subarray, $key, $value)); }
		}
		elseif (is_array($array) && is_numeric($value))
		{
			if (fnmatch($value.":*", strtolower($array[$key]))) { $results[] = $array; }
			elseif ($array[$key] == $value) { $results[] = $array; }
			foreach ($array as $subarray) { $results = array_merge($results, search($subarray, $key, $value)); }
		}
	    return $results;
	}


	function whois($nick)
	{
		global $socket, $output, $core;
		$command = "WHOIS :".$nick;
		socket_write($socket, $command."\n\r");
		echo "WHOIS: ".$nick."\n";
		return $output['all'];
	}

	function minecraft($mcdataid, $mcedit = "print")
	{
		include("data/mcarray.php");
		if ($mcedit == "print")
		{
			$undmg = str_replace(":", "", $mcdataid);
			if (is_numeric($mcdataid) || is_numeric($undmg))
			{
				$mcids = search($mcarray, "id", $mcdataid);
				$mcnt = count($mcids);
				$mcnt = $mcnt-1;
				$mctcnt = 0;

				if (is_array($mcids))
				{
					foreach ($mcids as $mcidmulti)
					{
						if ($mcnt != $mctcnt)
						{
							$mcresult .= $mcidmulti["name"].": ".$mcidmulti["id"].", ";
							$mctcnt++;
						} else { $mcresult .= $mcidmulti["name"].": ".$mcidmulti["id"]; }
					}
				}
				else
				{
					$mcresult = $mcidmulti["name"].": ".$mcidmulti["id"];
				}
			}
			elseif (!is_numeric($mcdataid))
			{
				$mcids = search($mcarray, "name", $mcdataid);
				$mcnt = count($mcids);
				$mcnt = $mcnt-1;
				$mctcnt = 0;

				if (is_array($mcids))
				{
					foreach ($mcids as $mcidmulti)
					{
						if ($mcnt != $mctcnt)
						{
							$mcresult .= $mcidmulti["name"].": ".$mcidmulti["id"].", ";
							$mctcnt++;
						} else { $mcresult .= $mcidmulti["name"].": ".$mcidmulti["id"]; }
					}
				}
				else
				{
					$mcresult = $mcidmulti["name"].": ".$mcidmulti["id"];
				}
			}
			$returnmsg = $mcresult;
		}
		if ($mcedit == "add")
		{
			$editdata = explode(", ", $mcdataid);
			foreach($editdata as $newids)
			{
				$formatedids = explode(":", $newids);
				$formatedids[1] = str_replace(",", "", $formatedids[1]);
				$formated[] = array("id" => $formatedids[0], "name" => $formatedids[1]);

				$nameexists = search($formatedids, "name", $mcarray);
				foreach ($nameexists as $resultexists) {
					var_dump($nameexists);
					var_dump($resultexists);
				}
				$idexists = search($formatedids, "id", $mcarray);
				foreach ($idexists as $resultexists) {
					var_dump($idexists);
					var_dump($resultexists);
				}
				$formatedmsg .= $formatedids[1].": ".$formatedids[0]." ";
			}

			$mcarraynew = array_merge($mcarray, $formated);
			unset($mcarray);
			$mcarray = $mcarraynew;

			$finalwrite = "<?php\r\n\$mcarray = array(\r\n";
			$wac = 0;
			$wact = count($mcarray);
			$wact = $wact-1;
			foreach($mcarray as $writearray)
			{
				if ($wac != $wact)
				{
					$finalwrite .= $wac." => array(\"id\" => \"".$writearray["id"]."\", \"name\" => \"".$writearray["name"]."\"),\r\n";
					$wac++;
				}
				elseif ($wac == $wact)
				{
					$finalwrite .= $wac." => array(\"id\" => \"".$writearray["id"]."\", \"name\" => \"".$writearray["name"]."\")\r\n);\r\n?>";
				}
			}
//			file_put_contents("mcarraytest.php", $finalwrite);
			$formatedmsg .= "added.";
			$returnmsg = $formatedmsg;
		}
		if (!isset($mcresult) && $mcedit == "print") { $returnmsg = "Couldn't find: ".$mcdataid.", please try refining your search."; }
		return $returnmsg;
	}

	function pm($nick, $message)
	{
		global $socket, $output, $core;
		$command = "PRIVMSG ".$nick." :".$message;
		socket_write($socket, $command."\n\r");
		echo "PM: to <".$nick."> ".$message."\n";
	}

	function uptary($seconds,  $periods = null)
	{		
		if (!is_array($periods))
		{
			$periods = array (
				'years'	 => 31556926,
				'months'	=> 2629743,
				'weeks'	 => 604800,
				'days'	  => 86400,
				'hours'	 => 3600,
				'minutes'   => 60,
				'seconds'   => 1
				);
		}

		$seconds = (int) $seconds;
		foreach ($periods as $period => $value)
		{
			$count = floor($seconds / $value);

			if ($count == 0)
			{
				continue;
			}

			$values[$period] = $count;
			$seconds = $seconds % $value;
		}

		if (empty($values))
		{
			$values = null;
		}

		return $values;
	}

	function cardvalues($cards)
	{
		if (is_array($cards))
		{
			$cvalue = array("a" => "a", "2" => "2", "3" => "3", "4" => "4", "5" => "5", "6" => "6", "7" => "7", "8" => "8",  "9" => "9", "j" => "10", "q" => "10", "k" => "10");
//			var_dump($cards);

			foreach ($cards as $card => $cpoint)
			{
				if ($card == "a") { $cardscore[] = "11"; }
				else { $cardscore[] = $cvalue[$cpoint]; }

			}

			foreach ($cardscore as $cscore)
			{
				if (!isset($cardstotal))
				{
					$cardstotal = $cscore;
					echo "\n".$cscore;
				}
				else
				{
					$cardstotal = $cardstotal + $cscore;
				}
			}
/*			foreach ($cards as $card)
			{
				if ($card = "a") { $cardnum[] = "11"; }
				else { $cardnum[] = $cvalue[$card]; }				
			}
			foreach ($cardnum as $num)
			{
				if (!isset($cardtotal))
				{
					$cardtotal = $num;
				}
				else
				{
					$cardtotal = $cardtotal + $num;
//					echo "\n\n cardtotal:\n";
					var_dump($cardtotal);
//					echo "\n\n num:\n";
//					var_dump($num);
				}
			}
//			echo "\n\n cardnum:\n";
//			var_dump($cardnum);*/
			if ($cardstotal > "21")
			{
				if ($cardnum["a"] = "11")
				{
					$cardnum["a"] = "1";
					$cardtotal = $cardtotal - 10;
				}
			}
			return $cardtotal;
		}
	}

	function hasactivegame($owner, $game)
	{
		global $core;
		if ($core['games'][$game][$owner]['active']) { return true; }
		else { return false; }
	}

	function gamedeals($site, $date)
	{
		// define url and files
		if (isset($site) && $site == "gmg") { $url = "http://www.steamgamesales.com/rss/?region=us&stores=greenmangaming"; $deals = "data/gmgdeals.xml"; }
		else { $url = "http://www.steamgamesales.com/rss/?region=us&stores=steam"; $deals = "data/steamdeals.xml"; }
	
		// Create if it doesn't exist
		if (!file_exists($deals))
		{
		//	$core['cmd']['steam']['dailyreset'] = time();
			$dealsget = file_get_contents($url);
			file_put_contents($deals, $dealsget);
			if (is_writable($deals)) { echo "Save sucessful!"; }
			else { echo "You dun goofed!"; }
		}

		// If local file is older than one day, grab the new daily deal
		if (file_exists($deals) && (date("mdy", time()) > date("mdy", filemtime($deals))))
		{
		//	$core['cmd']['steam']['dailyreset'] = time();
			$dealsget = file_get_contents($url);
			file_put_contents($deals, $dealsget);
			if (is_writable($deals)) { echo "Save sucessful!"; }
			else { echo "You dun goofed!"; }
		}

		$xml = simplexml_load_file($deals);
		$dealdata = array("title" => $xml->channel->item[0]->title, "link" => (string)$xml->channel->item[0]->link);
		$steamsale = array("today" => $xml->channel->item[0]->pubDate, "yesterday" => $xml->channel->item[1]->pubDate);

		// Check for Steam sale
		if (date("d", strtotime($steamsale["today"])) == date("d", strtotime($steamsale["yesterday"])))
		{
			$dealdata = "Multiple daily deals, possibly steam sale.";

/*			foreach($xml->channel->item as $games)
			{
				preg_replace('/\s\$\d+\.\d{2}\s/', $games->title, $games->title);
				array_multisort(SORT_ASC)
			}

				foreach($games->title as $gamescost)
				{
					preg_replace('/\s\$\d+\.\d{2}\s/', $gamescost, $gamecost);
				}

				asort($gamecost);
				var_dump($gamecost);

				preg_match('/\s\$\d\.\d\s/', $games->title, $gamecost);
				if ($games->pubDate == $steamsale["today"])
				{
				}			
			} */
		}

		// Checks to see if user specified specific deal date
		if (isset($date))
		{
			$cdate = date("m/j", strtotime($date));
			foreach ($xml->channel->item as $games)
			{
				$cddate = date("m/j", strtotime($games->pubDate));
				if ($cddate == $cdate) {
					echo "worked";
					$dealdata = array("title" => $games->title,"link" => $games->link);
					break;
				}
			}
			if(is_string($dealdata)) { $dealdata = "Deal was too long ago."; }
		}

		if (is_array($dealdata)) { $dealdata["title"] = str_replace(" USD", "", $dealdata["title"]); }
		if (substr_count($dealdata["link"], "anrdoezrs") > "0") 
		{
			$dealdata["link"] = str_replace("http://www.anrdoezrs.net/click-5464205-10912384?url=", "", $dealdata["link"]);
			$dealdata["link"] = str_replace("%3Fgmgr%3Dvadarevu", "", $dealdata["link"]);
			$dealdata["link"] = rawurldecode($dealdata["link"]);
		}

		var_dump($dealdata);
		return $dealdata;
	}

	function getplayers($name, $file)
	{
  		$lines = count(file($file));
		$players = file_get_contents($file);
		if($lines > 1)
		{
			$playersdata = explode("\r\n", $players);
			foreach($playersdata as $playerdata)
			{
				$playerexdata = explode(":", $playerdata);
				$playername = $playerdata[0];
				$returnddata[$playername] .= array("score" => $playerdata[1], "difficulty" => $playerdata[2]);
			}
		}
		else
		{
			str_replace("\r\n", "", $players);
			$playersdata = explode(":", $players);
			$returnddata[$playername] = array("score" => $playerdata[1], "difficulty" => $playerdata[2]);
		}
		return $retundata;
	}

	function putplayers($name, $score, $difficulty="100", $file)
	{
		if (filesize($file) > "1")
		{
			$players = file_get_contents($file);
			$playersdata = explode("\r\n", $players);
			$fileoutput = "";
			foreach($playersdata as $playerdata)
			{
				$playerexdata = explode(":", $playerdata);
				if($playerdata[0] == $name) { $fileoutput .= $name.":".$score.":".$difficulty."\r\n"; }
				else { $fileoutput .= $playerdata[0].":".$playerdata[1].":".$playerdata[2]; }
			}
		}
		else
		{
			$fileoutput .= $name.":".$score.":".$difficulty."\r\n";
		}
		if (file_put_contents($file, $fileoutput)) { return true; }
		else { return false; }
	}

	function todo_clean($file)
	{
		$todofilecontents = file_get_contents($file);
		$todolist = explode("\n", $todofilecontents);

		foreach ($todolist as $todoitem) {
			if (strlen($todoitem) == "0") { unset($todoitem); }
		}
	}

	function is_chan($source)
	{
		if (strstr($source, "#")) { return true; }
		else { return false; }
	}

	function trim_array($x)
	{
		if (is_array($x))
		{
			return array_map('trim_array', $x);
		}
		return trim($x);
	}

	function admin($req = null)
	{
		global $output, $core;

		foreach ($core['admin'] as $owner => $details)
		{
			if (($owner == $output['username']) && ($details['host'] == $output['hostname']))
			{
				if ($details['lvl'] >= $req) { return true; }
			}
		}
		return false;
	}

	function buffer()
	{

		global $output, $core, $buffer;
		
		$time = date('g:i:s');	
		$buffer = $output['all'];
	//	echo $buffer;
		$buffer = explode(" ", $buffer, 4);
		$buffer['username'] = substr($buffer[0], 1, strpos($buffer['0'], "!")-1);


		$posExcl = strpos($buffer[0], "!");
		$posAt = strpos($buffer[0], "@");
		$buffer['identd'] = substr($buffer[0], $posExcl+1, $posAt-$posExcl-1); 
		$buffer['hostname'] = substr($buffer[0], strpos($buffer[0], "@")+1);
		$buffer['user_host'] = substr($buffer[0],1);
		$buffer['action'] = $buffer[1];
		if ($buffer[0] == "PING") { $buffer['action'] = "PING"; }
		
		switch (strtoupper($buffer['action']))
		{
			case "JOIN":
			   	$buffer['text'] = "*JOINS: ". $buffer['username']." ( ".$buffer['user_host']." )";
				$buffer['command'] = "JOIN";
				$buffer['channel'] = $core['channel'];
				$uname = strtolower($buffer['username']);
				if (isset($core['user'][$uname]['tell']))
				{
					$x = count($core['user'][$uname]['tell']['msg']);
					for ($i=0; $i < $x; $i++) { 
						pm($uname, $core['user'][$uname]['tell']['msg'][$i]);
						pm($core['user'][$uname]['tell']['sender'][$i], "Messsage to ".$uname.": \"".$core['user'][$uname]['tell']['msg'][$i]."\" sent.");
					}
					
					//unset($core['user'][$uname]['tell']);
				}
				echo $time." Joins: ".$buffer['username']."\n";
			   	break;

			case "QUIT":
			   	$buffer['text'] = "*QUITS: ". $buffer['username']." ( ".$buffer['user_host']." )";
				$buffer['command'] = "QUIT";
				$buffer['channel'] = $core['channel'];
				echo $time." Quit: ".$buffer['username']."\n";
			   	break;

			case "NOTICE":
				if (strstr($buffer[0], "**"))
				{
					echo "Notice: ".$buffer[0]." - Hostname found\n";
				}
				else
				{
					echo $time." Notice: ".$buffer['username']." - ".substr($buffer[3], 1)."\n";
				}
			   	$buffer['text'] = "*NOTICE: ". $buffer['username'];
				$buffer['command'] = "NOTICE";
				$buffer['channel'] = $buffer[2];

			   	break;

			case "PART":
			  	$buffer['text'] = "*PARTS: ". $buffer['username']." (".$buffer['user_host'].")";
				$buffer['command'] = "PART";
				$buffer['channel'] = $core['channel'];
				echo $time." Parts: ".$buffer['username']."\n";
			  	break;

			case "MODE":
			  	$buffer['text'] = $buffer['username']." sets mode: ".$buffer[3];
				$buffer['command'] = "MODE";
				$buffer['channel'] = $buffer[2];
				echo $time." ".$buffer['text']."\n";
				break;

			case "NICK":
				$buffer['text'] = "*NICK: ".$buffer['username']." => ".substr($buffer[2], 1)." (".$buffer['user_host'].")";
				$buffer['command'] = "NICK";
				$buffer['channel'] = $core['channel'];

				$uname = strtolower($buffer['username']);
				if (isset($core['user'][$uname]['tell']))
				{
					$x = count($core['user'][$uname]['tell']['msg']);
					for ($i=0; $i < $x; $i++) { 
						pm($uname, $core['user'][$uname]['tell']['msg'][$i]);
						pm($core['user'][$uname]['tell']['sender'][$i], "Messsage to ".$uname.": \"".$core['user'][$uname]['tell']['msg'][$i]."\" sent.");
					}
					
					//unset($core['user'][$uname]['tell']);
				}
				echo $time." ".$buffer['text']."\n";
				break;

			case "PING":
				raw('PONG :'.substr($output['all'], 6), "no");	
				break;

			case "PRIVMSG":
				$buffer['command'] = $buffer[1];
				if (is_chan($buffer[2]))
				{
					$buffer['channel'] = $buffer[2];
				}
				elseif (!is_chan($buffer[2]))
				{
					$buffer['channel'] = $buffer['username'];
				}
				
				$buffer['text'] = substr($buffer[3], 1);
				echo $time." <".$buffer['username']."> ".$buffer['text']."\n";
				break;
		}
		$output = $buffer;
	}
?>
