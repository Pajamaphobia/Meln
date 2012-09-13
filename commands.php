<?php
	function process()
	{
		global $output, $core, $buffer, $socket;

		$output['exp'] = explode(" ", $output['text'], 2);
		if (strstr($output['exp'][0], $core['prefix']))
		{
			$output['command'] = strtolower($output['exp'][0]); //the command, ie: <prefix>say
			$output['data'] = $output['exp'][1]; //the data, ie: <text> from <prefix>say test
			$output['args'] = explode(" ", $output['data']);
			$output['cmd'] = str_replace($core['prefix'], "", $output['command']);
			$output['channel'] == $buffer['channel'];
			
			//get's the arguments, and if there's an argument it changes the command so it doesn't have the argument in it
			if(strstr($output['command'], "."))
			{
				$output['argument'] = explode(".", $output['command']);
				$output['command'] = $output['argument'][0];
				$output['cmd'] = str_replace($core['prefix'], "", $output['command']);
				$output['argument'] = trim_array($output['argument']); //get rid of that trailing space
			}
		}		
		$data = $output['data'];
		$cmd = $output['cmd']; // command without the prefix
		
		switch($output['command'])
		{
			//admin command, sees if you're an admin
			case $core['prefix']."admin":
				$core['cmd']['admin'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."admin", 'admin' => "0");
				if ($core['cmd']['admin']['module'] == null) { $core['cmd']['admin']['module'] = "loaded"; }

				if ($data != null)
				{
					if ($core['admin'][$data]['lvl'] != null) { $msg = $data." has level ".$core['admin'][$data]['lvl']." admin access to me."; }
					if ($core['admin'][$data]['lvl'] == null) { $msg = $data." doesn't have admin status"; }
				}
				if ($data == null)
				{
					$admin = $output['username'];
					if ($core['admin'][$admin]['lvl'] != null) { $msg = "You have level ".$core['admin'][$admin]['lvl']." admin access to me."; }
					if ($core['admin'][$admin]['lvl'] == null) { $msg = $data." doesn't have admin status"; }
				}
				break;

				
			//commands command
			case $core['prefix']."commands":
				$core['cmd']['commands'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."commands", 'admin' => "0");
				if ($core['cmd']['commands']['module'] == null) { $core['cmd']['commands']['module'] = "loaded"; }

				if ($core['cmd']['commands']['module'] == "loaded")
				{
					$modfile = file_get_contents(__DIR__."/modules.php");
					preg_match_all('/\$core\[\'\w+\'\]\[\'(\w+)\'\]\[\'\w+\'\].+==.+\"(\w+)\";/', $modfile, $cmds);

					$total = count($cmds[0]) - 1;
					$msg[0] = "Commands: ";

					for ($i=0; $i < $total; $i++)
					{ 
						if ($i != $total && $cmds[2][$i] == "loaded")
						{
							$msg[0] .= $cmds[1][$i].", ";
						}
						if ($i == $total && $cmds[2][$i] == "loaded")
						{
							$msg[0] .= $cmds[1][$i].".";
						}
					}
					$msg[] = "For help use: ".$core['prefix']."<command>.help";

/*					$cmdfile = file_get_contents(__FILE__);
					preg_match_all('/case \$core\[\'prefix\'\]\.\"(\w+)\"\:/', $cmdfile, $cmds);
					$cmdlist = $cmds[1];
					sort($cmdlist);
					$cmdstr = implode(', ', $cmdlist);  
					$msg = $cmdstr.".";*/
				}
				elseif ($core['cmd']['commands']['module'] == "unloaded")
				{
					$msg = "commands module not loaded";
				}
				break;

			//load command
			case $core['prefix']."load":
				$core['cmd']['load'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."load <module(s)>", 'admin' => "20");

				if ($data != null)
				{
					$modfile = __DIR__."/modules.php";

					$modwrite[] = "<\?php\n\n\tglobal \$core;\n\n";
					$msg = "Unloaded: ";

					if($mods = explode(' ', $data))
					{
						$total = count($mods);
						for ($i=0; $i < $total; $i++)
						{
							if ($i != $total)
							{
								$mod = $mods[$i];
								$core['cmd'][$i]['module'] = "Unloaded";
								$modwrite[] = "\t\$core['cmd'][".$mod."]['module'] == \"loaded\";\n";
								$msg .= $mod.", ";
							}
							elseif ($i == $total)
							{
								$mod = $mods[$i];
								$core['cmd'][$mod]['module'] = "Unloaded";
								$modwrite[] = "\t\$core['cmd'][".$mod."]['module'] == \"loaded\";";
								$msg .= $mod.".";
							}
						}
					}
					
					$modwrite[] = "\n\n?>";

					$writefinal = implode("", $modwrite);
					file_put_contents($modfile, $writefinal);
				}
				else { $msg = "Specify which module you would like unloaded."; }

				break;


			//unload command
			case $core['prefix']."unload":
				$core['cmd']['unload'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."unload <module(s)", 'admin' => "20");


				if ($data != null)
				{
					$mods = explode(' ', $data);
					$msg = "Unloaded: ";
					$ulcnt = count($mods);

					$modfile = __DIR__."/modules.php";
					$modwrite[] = "<?php\n\n\tglobal \$core;\n\n";
					$msg = "Unloaded: ";

					if ($ulcnt > 1)
					{
						$modfilecontent = file_get_contents($modfile);
						for ($i=0; $i < $ulcnt; $i++)
						{
							$mod = $mods[$i];

							$core['cmd'][$mod]['module'] = "unloaded";
							if ($i = $ulcnt) { $msg .= $mod."."; }
							else { $msg .= $mod.", "; }
							$modunload = preg_replace('/\$core\[\'\w+\'\]\[\'$mod\']\[\'\w+\'\].+==.+\"(\w+)\";/', "unloaded", $modfilecontent);
							var_dump($modunload);
							file_put_contents($modfile, $modunload);
							echo "\n\nmulti\n\n";
						}
					}
					else
					{
						$modfilecontent = file_get_contents($modfile);
						$core['cmd'][$data]['module'] = "unloaded";
						$msg .= $data.".";
						$count = 0;
						$modunload = preg_replace('/\[\''.preg_quote($data).'\'\]\[\'\w+\'\].+==.+\"(\w+)\";/', 'unloaded', $modfilecontent, $count);
						var_dump($modfilecontent);
						var_dump($modunload);
						file_put_contents($modfile, $modunload);
						echo "\n\nsingle: ".$count." : ".$data."\n\n";
					}

				}



/*				if ($data != null)
				{
					$modfile = __DIR__."/modules.php";

					$modwrite[] = "<?php\n\n\tglobal \$core;\n\n";
					$msg = "Unloaded: ";

					$mods = explode(' ', $data);

					if(count($mods) > 1)
					{
						$total = count($mods);
						for ($i=0; $i < $total; $i++)
						{
							if ($i != $total)
							{
								$core['cmd'][$mod]['module'] = "unloaded";
								$modwrite[] = "\t\$core['cmd'][".$mod."]['module'] == \"unloaded\";\n";
								$msg .= $mod.", ";
							}
							elseif ($i == $total)
							{
								$core['cmd'][$mod]['module'] = "unloaded";
								$modwrite[] = "\t\$core['cmd'][".$mod."]['module'] == \"unloaded\";";
								$msg .= $mod.".";
							}
						}
					}
					else
					{
						$core['cmd'][$mod]['module'] = "unloaded";
						$modwrite[] = "\t\$core['cmd'][".$mod."]['module'] == \"unloaded\";\n";
						$msg .= $mod.", ";
					}
					
					$modwrite[] = "\n\n?>";

					$writefinal = implode("", $modwrite);
					echo $writefinal;
					//file_put_contents($modfile, $writefinal);
				}
				else { $msg = "Specify which module you would like unloaded."; }*/

				break;
				
			//nick command
			case $core['prefix']."nick":
				$core['cmd']['nick'] = array('exec' => "raw", 'help' => "Usage: ".$core['prefix']."nick <new nick>", 'admin' => "10");
				if ($core['cmd']['nick']['module'] == null) { $core['cmd']['nick']['module'] = "loaded"; }
				$newnick = $output['args'][1];

				if ($core['cmd']['nick']['module'] == "loaded")
				{
					if ($newnick == $core['nick'])
					{
						$msg[] = "NICK :". $output['args'][1];
						$msg[] = "PRIVMSG NickServ IDENTIFY ". $core['nspass'];
					}
					
					else
					{
						$msg = "NICK :". $output['args'][1];
					}
				}

				elseif ($core['cmd']['nick']['module'] == "unloaded")
				{
					$msg = "nick module not loaded";
				}
				break;


			//todo command
			case $core['prefix']."todo":
				$core['cmd']['todo'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."todo <add/rm/list> <new todo>", 'admin' => "0");
				if ($core['cmd']['todo']['module'] == null) { $core['cmd']['todo']['module'] = "loaded"; }

				if ($core['cmd']['todo']['module'] == "loaded")
				{
					$owner = $output['username'];
					$todofile = __DIR__."/data/todo.".$owner.".txt";
					$todolimit = "10";

					if ($output['argument'][1] == "add")
					{
						if (!file_exists($todofile))
						{
							$todo = $data."\n";
							file_put_contents($todofile, $todo);
							$msg = "Task #1 added to your todo list, ".$owner;
						}
						else
						{
							$count = substr_count(file_get_contents($todofile), "\n");
							$todofilecontents = file_get_contents($todofile);
							$todolist = explode("\n", $todofilecontents);
							if (strlen($todolist[$count]) == 0) { unset($todolist[$count]); }

							if ($count == $todolimit)
							{
								$msg = "You already have ".$todolimit." goals, please accomplish/remove some of those first.";
							}
							else
							{
								$count++;
								$todo = $data."\n";
								file_put_contents($todofile, $todo, FILE_APPEND);
								$msg = "Task #".$count." added to your todo list, ".$owner;
							}
						}

					}

					if ($output['argument'][1] == "rm")
					{
						if (file_exists($todofile))
						{
							$count = substr_count(file_get_contents($todofile), "\n");
							$todofilecontents = file_get_contents($todofile);
							$todolist = explode("\n", $todofilecontents);
							$listcount = $data - 1;
							if (strlen($todolist[$count]) == "0") { unset($todolist[$count]); }

							if (isset($todolist[$listcount]))
							{
								$msg = "Task #".$data." was removed.";
								unset($todolist[$listcount]);
								$todo = implode("\n", $todolist);
								$todo .= "\n";
								file_put_contents($todofile, $todo);
							}
						}
						else { $msg = "Your todo list is empty. Use ".$core['prefix']."todo.add <task> to add something to do."; }
					}

					if ($output['argument'][1] == "list")
					{
						if (file_exists($todofile))
						{
							$count = substr_count(file_get_contents($todofile), "\n");
							$todofilecontents = file_get_contents($todofile);
							$todolist = explode("\n", $todofilecontents);
							if (strlen($todolist[$count]) == "0") { unset($todolist[$count]); }
							
							if ($count > 3 && is_chan($output['channel']))
							{
								$x = "1";
								for ($i=0; $i < 3; $i++) { 
									$msg[] = "#".$x.": ".$todolist[$i];
									$x++;
								}

								$msg[] = "Your todo list is longer than 3 items, please msg me for your full list.";
							}
							
							if ($count <= 3 && is_chan($output['channel']))
							{
								$x = "1";
								for ($i=0; $i < $count; $i++) { 
									$msg[] = "#".$x.": ".$todolist[$i];
									$x++;
								}
							}

							if (($count >= 3 && !is_chan($output['channel'])) || ($count <= 3 && !is_chan($output['channel'])))
							{
								$x = "1";
								for ($i=0; $i < $count; $i++) { 
									$msg[] = "#".$x.": ".$todolist[$i];
									$x++;
								}
							}
						}
						else { $msg = "Your todo list is empty. Use ".$core['prefix']."todo.add <task> to add something to do."; }
					}

					if ($output['argument'][1] == "clear")
					{
						if ($data = "-y" || $data = "-yes" || $data = "yes")
						{
							unlink($todofile);
							$msg = "Your todolist has been cleared.";
						}
						else { $msg = "Please use ".$core['prefix']."todo.clear <-y/-yes/yes> to confirm deletion."; }
					}
				}

				elseif ($core['cmd']['todo']['module'] == "unloaded")
				{
					$msg = "admin module not loaded";
				}
				break;

			//join command
			case $core['prefix']."join":
				$core['cmd']['join'] = array('exec' => "raw", 'help' => "Usage: ".$core['prefix']."join <channel>", 'admin' => "10");
				if ($core['cmd']['join']['module'] == null) { $core['cmd']['join']['module'] = "loaded"; }

				if ($core['cmd']['join']['module'] == "loaded")
				{
					$msg = "JOIN :".$data;
				}
				elseif ($core['cmd']['join']['module'] == "unloaded")
				{
					$msg = "join module not loaded";
				}
				break;

			//part command
			case $core['prefix']."part":
				$core['cmd']['join'] = array('exec' => "raw", 'help' => "Usage: ".$core['prefix']."part <channel>", 'admin' => "10");
				if ($core['cmd']['part']['module'] == null) { $core['cmd']['part']['module'] = "loaded"; }

				if ($core['cmd']['part']['module'] == "loaded")
				{
					if ($data != null)
					{
						$msg = "PART :".$data;
					}

					else
					{
						$msg = "PART :".$output['channel'];
					}
				}
				elseif ($core['cmd']['part']['module'] == "unloaded")
				{
					$msg = "part module not loaded";
				}
				break;

			//say command
			case $core['prefix']."say":
				$core['cmd']['say'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."say <text>", 'admin' => "10");
				if ($core['cmd']['say']['module'] == null) { $core['cmd']['say']['module'] = "loaded"; }
				
				if ($core['cmd']['say']['module'] == "loaded")
				{
					$msg = $data;
				}
				elseif ($core['cmd']['say']['module'] == "unloaded")
				{
					$msg = "say module not loaded";
				}
				break;

			//tell command
			case $core['prefix']."tell":
				$core['cmd']['tell'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."tell <person> <note>", 'admin' => "0");
				if ($core['cmd']['tell']['module'] == null) { $core['cmd']['tell']['module'] = "loaded"; }
				
				if ($core['cmd']['tell']['module'] == "loaded")
				{
					$tempdata = explode(" ", $data, 2);
					$recepient = strtolower($tempdata[0]);
					$tellmessage = $tempdata[1];

					if (strlen($tellmessage) > 240)
					{
						$msg = "Your message is too long to be saved.";
					}
					else
					{
						$core['user'][$recepient]['tell']['msg'][] = $tellmessage;
						$core['user'][$recepient]['tell']['sender'][] = $output['username'];
						$msg = "Message to ".$recepient." saved. They'll receive it when they next login.";
					}
				}
				elseif ($core['cmd']['tell']['module'] == "unloaded")
				{
					$msg = "tell module not loaded";
				}
				break;


			//Russian Roulette command
			case $core['prefix']."rr":
				$core['cmd']['rr'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."rr <text>", 'admin' => "10");
				if ($core['cmd']['rr']['module'] == null) { $core['cmd']['rr']['module'] = "loaded"; }
				
				if ($core['cmd']['rr']['module'] == "loaded")
				{
					$barrel = rand(1,6);
					$bullet = rand(1,6);
					if($barrel == $bullet) { raw("KICK ".$output['channel']." ".$output['username']." :BANG!"); }
					else { msg("Click..."); }
				}
				elseif ($core['cmd']['rr']['module'] == "unloaded")
				{
					$msg = "rr module not loaded";
				}
				break;

			//Black Jack command
			case $core['prefix']."blackjack":
				$core['cmd']['blackjack'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."blackjack <play, hit, stand>", 'admin' => "10");
				if ($core['cmd']['blackjack']['module'] == null) { $core['cmd']['blackjack']['module'] = "loaded"; }
				
				if ($core['cmd']['blackjack']['module'] == "loaded")
				{
					$defaultdeck =
						array("a", "2", "3", "4", "5", "6", "7", "8", "9", "j", "q", "k",
						"a", "2", "3", "4", "5", "6", "7", "8", "9", "j", "q", "k",
						"a", "2", "3", "4", "5", "6", "7", "8", "9", "j", "q", "k",
						"a", "2", "3", "4", "5", "6", "7", "8", "9", "j", "q", "k");
					$gameowner = $output['username'];


					if($output['argument'][1] == "play")
					{
						$core['games']['blackjack'][$gameowner]['active'] = true;
						$core['games']['blackjack'][$gameowner]['deck'] = $defaultdeck;
						shuffle($core['games']['blackjack'][$gameowner]['deck']);
						$deck = $core['games']['blackjack'][$gameowner]['deck'];
						$core['games']['blackjack'][$gameowner]['playerscards'][0] = array_shift($deck);
						$core['games']['blackjack'][$gameowner]['playerscards'][1] = array_shift($deck);
						$core['games']['blackjack'][$gameowner]['dealerscards'][0] = array_shift($deck);
						$core['games']['blackjack'][$gameowner]['dealerscards'][1] = array_shift($deck);


						$msg[] = "Dealer's cards are: [".$core['games']['blackjack'][$gameowner]['dealerscards'][1]."][*]";
						if (cardvalues($core['games']['blackjack'][$gameowner]['dealerscards']) == "21") { $msg[] = "Dealer has BLACKJACK!"; $core['games']['blackjack'][$gameowner]['dealerscore'] = "21"; }
						if (cardvalues($core['games']['blackjack'][$gameowner]['dealerscards']) > "21") { $msg[] = "Dealer has BUST!"; $core['games']['blackjack'][$gameowner]['dealerscore'] = "BUST"; }

						$msg[] = "Your cards are: [".$core['games']['blackjack'][$gameowner]['playerscards'][0]."][".$core['games']['blackjack'][$gameowner]['playerscards'][1]."]";

						if (cardvalues($core['games']['blackjack'][$gameowner]['playerscards']) == "21")
						{
							$msg[] = "Player has BLACKJACK!"; $core['games']['blackjack'][$gameowner]['playerscore'] = "21";
							$core['games']['blackjack'][$gameowner]['active'] = false;
						}

						if (cardvalues($core['games']['blackjack'][$gameowner]['playerscards']) > "21")
						{
							$msg[] = "Player has BUST!"; $core['games']['blackjack'][$gameowner]['playerscore'] = "BUST";
							$core['games']['blackjack'][$gameowner]['active'] = false;
						}

						if ($core['games']['blackjack'][$gameowner]['playerscore'] == "21" && $core['games']['blackjack'][$gameowner]['dealerscore'] == "21")
						{
							$msg[] = "DRAW! Both dealer and player have Blackjack. Fuck your odds.";
							$core['games']['blackjack'][$gameowner]['active'] = false;
						}

						if (hasactivegame($gameowner, 'blackjack'))
						{
							$msg[] = "Hit or Stand.";
						}
					}

					if($output['argument'][1] == "hit")
					{
						if (hasactivegame($gameowner, 'blackjack'))
						{
							$deck = $core['games']['blackjack'][$gameowner]['deck'];
							$core['games']['blackjack'][$gameowner]['playerscards'][] = array_shift($deck);
							$msg[] = "You have drawn: [".end($core['games']['blackjack'][$gameowner]['playerscards'])."]";
							foreach ($core['games']['blackjack'][$gameowner]['playerscards'] as $playerscard) { $currenthand .= "[".$playerscard."]"; }
							$msg[] = "Your hand: ".$currenthand;

							if (cardvalues($core['games']['blackjack'][$gameowner]['playerscards']) == "21")
							{
								$msg[] = "Player has BLACKJACK!"; $core['games']['blackjack'][$gameowner]['playerscore'] = "21";
								$core['games']['blackjack'][$gameowner]['active'] = false;
							}

							if (cardvalues($core['games']['blackjack'][$gameowner]['playerscards']) > "21")
							{
								$msg[] = "Player has BUST!"; $core['games']['blackjack'][$gameowner]['playerscore'] = "BUST";
								$core['games']['blackjack'][$gameowner]['active'] = false;
							}

							if (hasactivegame($gameowner, 'blackjack'))
							{
								$msg[] = "Hit or Stand.";
							}
						}
						else { $msg = "You currently don't have an active game. Type ".$core['prefix']."bj.play to start!"; }
					}

					if($output['argument'][1] == "stand")
					{
						if (cardvalues($core['games']['blackjack'][$gameowner]['dealerscards']) >= "16") { $core['games']['blackjack'][$gameowner]['active'] = false; }
						if (hasactivegame($gameowner, 'blackjack'))
						{
							$core['games']['blackjack'][$gameowner]['dealerscards'][] = array_shift($deck);
							$msg[] = "Dealer has drawn: [".end($core['games']['blackjack'][$gameowner]['dealerscards'])."]";

							if (cardvalues($core['games']['blackjack'][$gameowner]['dealerscards']) == "21")
							{
								$msg[] = "Player has BLACKJACK!"; $core['games']['blackjack'][$gameowner]['dealerscore'] = "21";
								$core['games']['blackjack'][$gameowner]['active'] = false;
							}

							if (cardvalues($core['games']['blackjack'][$gameowner]['dealercards']) > "21")
							{
								$msg[] = "Player has BUST!"; $core['games']['blackjack'][$gameowner]['dealerscore'] = "BUST";
								$core['games']['blackjack'][$gameowner]['active'] = false;
							}

							if (cardvalues($core['games']['blackjack'][$gameowner]['dealerscards']) >= "16" && cardvalues($core['games']['blackjack'][$gameowner]['dealerscards']) <= "21" && hasactivegame($gameowner, 'blackjack'))
							{
								$msg[] = "Dealer stands.";
								foreach ($core['games']['blackjack'][$gameowner]['dealerscards'] as $dealerscard) { $currenthand .= "[".$dealerscard."]"; }
								$msg[] = "Dealer's hand: ".$currenthand;
								if (cardvalues($core['games']['blackjack'][$gameowner]['dealercards']) > cardvalues($core['games']['blackjack'][$gameowner]['playerscards']))
								{
									$msg[] = "Dealer wins";
									$core['games']['blackjack'][$gameowner]['active'] = false;
								}
								if (cardvalues($core['games']['blackjack'][$gameowner]['dealercards']) < cardvalues($core['games']['blackjack'][$gameowner]['playerscards']))
								{
									$msg[] = "Player wins";
									$core['games']['blackjack'][$gameowner]['active'] = false;
								}
							}
						}
					}
				}
				elseif ($core['cmd']['blackjack']['module'] == "unloaded")
				{
					$msg = "blackjack module not loaded";
				}
				break;

			//minecraft command
			case $core['prefix']."mc":
				$core['cmd']['mc'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."mc <ID/Name>", 'admin' => "0");
				if ($core['cmd']['mc']['module'] == null) { $core['cmd']['mc']['module'] = "loaded"; }
				
				if ($core['cmd']['mc']['module'] == "loaded")
				{
					if (strlen($data) > 0)
					{
						if ($output['argument'][1] == "add" && admin("20")) { $mc = minecraft($data, "add"); }
						if ($output['argument'][1] == "add" && !admin("20")) { $mc = "You don't have admin status"; }
						else { $mc = minecraft($data); }
						$msg = $mc;
					}
					else
					{
						$msg = "Usage: ".$core['prefix']."mc <ID/Name>";
					}
				}
				elseif ($core['cmd']['mc']['module'] == "unloaded")
				{
					$msg = "minecraft module not loaded";
				}
				break;

			//names command
			case $core['prefix']."names":
				$core['cmd']['names'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."names", 'admin' => "0");
				if ($core['cmd']['names']['module'] == null) { $core['cmd']['names']['module'] = "loaded"; }
				
				if ($core['cmd']['names']['module'] == "loaded")
				{
					$i == 0;
					while(strlen($rawnames) < 4)
					{
						raw("NAMES #quarantinecraft");
						$rawnames = socket_read($socket, 4048);
						if (strlen($rawnames) < 4 && !isset($rawnames))
						{
							$rawnames = socket_read($socket, 4048);
						}
						if ($i <= 4) { break; }
						$i++;
					}

					$namesx = explode("\n", $rawnames);
					$namesex = explode(" :", $namesx[0]);
					$nicklist = explode(" ", $namesex[1]);

					$i == 0;
					foreach($nicklist as $nick)
					{
						$nick = preg_replace('/^(\~|\@|\%|\+)/', '', $nick);
						if ($i == 0) { $msg = $output['channel'].": ".$nick.", "; }
						if ($i == count($nicklist)) { $msg .= $nick; }
						if ($i > 0 && $i < count($nicklist)) { $msg .= $nick.", "; }
						$i++;
					}

				}
				elseif ($core['cmd']['names']['module'] == "unloaded")
				{
					$msg = "names module not loaded";
				}
				break;


			//boobs command
			case $core['prefix']."boobs":
				$core['cmd']['boobs'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."boobs <text>", 'admin' => "10");
				if ($core['cmd']['boobs']['module'] == null) { $core['cmd']['boobs']['module'] = "loaded"; }
				
				if ($core['cmd']['boobs']['module'] == "loaded")
				{
					$msg = array(".__                __..__                __.", "|                    ||                    |", "|                    ||                    |", "|__       O        __||__        O       __|");
				}
				elseif ($core['cmd']['boobs']['module'] == "unloaded")
				{
					$msg = "boobs module not loaded";
				}
				break;

			//Last.fm command
			case $core['prefix']."lastfm":
				$core['cmd']['lastfm'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."lastfm  <last played song number (1-10)> <last.fm username>", 'admin' => "0");
				if ($core['cmd']['lastfm']['module'] == null) { $core['cmd']['lastfm']['module'] = "loaded"; }
				
				if ($core['cmd']['lastfm']['module'] == "loaded")
				{

					$args = explode(" ", $data);
					if (!is_array($args)) { $number = $data; }
					else { $number = $args[0]; }

					if ($number <= 10 && !is_numeric($args[1])) { $user = $args[0]; }
					if (isset($args[1]) && !isset($args[1])) { $user = $args[0]; }
					if ($number <= 10 && !isset($args[1])) { $user = $core['lastfm']; }
				//	if (!isset($args[1])) { $user = $core['lastfm']; }

					$lastfm = simplexml_load_file("http://ws.audioscrobbler.com/1.0/user/".$user."/recenttracks.xml");
					$songs = $lastfm->track;

					if (isset($args[1]))
					{ 
							$n = $number - 1;
							$artist = $songs[$n]->artist;
							$track = $songs[$n]->name;
					}
					if (!isset($args[1]) && is_numeric($number))
					{
							$n = $number - 1;
							$artist = $songs[$n]->artist;
							$track = $songs[$n]->name;
					}
					else
					{
							$artist = $songs[0]->artist;
							$track = $songs[0]->name;
					}

					$msg = $artist." - ".$track;
				}
				elseif ($core['cmd']['lastfm']['module'] == "unloaded")
				{
					$msg = "lastfm module not loaded";
				}
				break;

			//roll command
			case $core['prefix']."roll":
				$core['cmd']['roll'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."roll <number of die/dice sides> <if two numbers dice sides>", 'admin' => "0");
				if ($core['cmd']['roll']['module'] == null) { $core['cmd']['roll']['module'] = "loaded"; }
				
				if ($core['cmd']['roll']['module'] == "loaded")
				{
					if(preg_match('/(\d+)\s(\d+)/', $data, $die))
					{
						if ($die[1] > 100 || $die[0] > 10000) { $msg = "Roll less than 100 seperate die, and no more than 10,000 sides."; }
						else
						{
							$i = 0;
							while($i < $die[1])
							{
								if($i != $die[1] && $i != $die[1]-1)
								{
									$diceresult = rand(1, $die[2]);
									$msg .= $diceresult.", ";
								}
								if ($i == $die[1]-1)
								{
									$diceresult = rand(1, $die[2]);
									$msg .= $diceresult;
								}
								$i++;
							}
						}
					}
					else
					{
						$msg = rand(0, $data);
					}
					
				}
				elseif ($core['cmd']['roll']['module'] == "unloaded")
				{
					$msg = "roll module not loaded";
				}
				break;


			//decide command
			case $core['prefix']."decide":
				$core['cmd']['decide'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."decide <options seperated by \",\" or \" or \">", 'admin' => "0");
				if ($core['cmd']['decide']['module'] == null) { $core['cmd']['decide']['module'] = "loaded"; }
				
				if ($core['cmd']['decide']['module'] == "loaded")
				{
					if (strstr($data, ",")) { $decisions = explode(", ", $data); }
					elseif (strstr($data, "or")) { $decisions = explode(" or ", $data); }
					if (!isset($decisions)) { $msg = $core['cmd']['decide']['help']; break; }
					if (strstr($decisions[0], "?"))
					{
						$decis = explode("? ", $decisions[0]);
						$decisions[0] = $decis[1]; 
					}
					$decount = count($decisions);
					$decount--;
					if (strstr($decisions[$decount], "?")) { $decisions[$decount] = str_replace("?", "", $decisions[$decount]); }
					$decrand = rand (0, $decount);
					shuffle($decisions);
					$finaldecision = $decisions[$decrand];
					$msg = $finaldecision;
				}
				elseif ($core['cmd']['decide']['module'] == "unloaded")
				{
					$msg = "decide module not loaded";
				}
				break;

			//8ball command
			case $core['prefix']."8ball":
				$core['cmd']['8ball'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."8ball <text>", 'admin' => "0");
				if ($core['cmd']['8ball']['module'] == null) { $core['cmd']['8ball']['module'] = "loaded"; }
				
				if ($core['cmd']['8ball']['module'] == "loaded")
				{
					if (!isset($data))
					{
						$msg = "You forgot to ask a question, idiot.";
					}
					else
					{
						if(isset($output['argument']))
						{
							foreach($output['argument'] as $args)
							{
								if ($args == "wacky")
								{
									if(isset($eightballrespons))
									{
										array_push($eightballrespons, "Fuck no.", "Fuck yes.", "Fuck YOU.", "DO IT NAO!", "NEVER!", "THE POWER OF THIEVE COMPELS YOU!", "What the fucking fuck is fucking wrong with you? Fuck!", "YOU GET NOTHING; I SAID GOOD DAY, SIR!", "Fuck you, I'm tired, ask again later, fucker.", "FUCK YEAH DO THAT SHIT!", "42", "BACON!", "Seriously?", "God just killed a kitten because you asked me that.", "OH YEAH SHAKE ME HARDER!", "I am not a Shake Weight!", "Not sure if lonely, or just illiterate.", "wat", "AWWWWW YEAAAAAHHHHHHH", "Hell no.", "Ask again later.", "Try again later.", "01101101011000010111100101100010011001010000110100001010", "Why would you even ask that?", "I think you already know the answer.", "NO! GOD NO!", "Clever girl.", "That's a negative ghostrider, the pattern is full.");
									}
									else
									{
										$eightballrespons = array("Fuck no.", "Fuck yes.", "Fuck YOU.", "DO IT NAO!", "NEVER!", "THE POWER OF THIEVE COMPELS YOU!", "What the fucking fuck is fucking wrong with you? Fuck!", "YOU GET NOTHING; I SAID GOOD DAY, SIR!", "Fuck you, I'm tired, ask again later, fucker.", "FUCK YEAH DO THAT SHIT!", "42", "BACON!", "Seriously?", "God just killed a kitten because you asked me that.", "OH YEAH SHAKE ME HARDER!", "I am not a Shake Weight!", "Not sure if lonely, or just illiterate.", "wat", "AWWWWW YEAAAAAHHHHHHH", "Hell no.", "Ask again later.", "Try again later.", "01101101011000010111100101100010011001010000110100001010", "Why would you even ask that?", "I think you already know the answer.", "NO! GOD NO!", "Clever girl.", "That's a negative ghostrider, the pattern is full.");
									}	
								}
								elseif ($args == "zen") {
									if(isset($eightballrespons))
									{
										array_push($eightballrespons, "Search your soul, you know it to be true.", "The butterfly graces your answer with his presence.");
									}
									else
									{
										$eightballrespons = array("Search your soul, you know it to be true.", "The butterfly graces your answer with his presence.");
									}
								}
								elseif ($args == "reg")
								{
									if(isset($eightballrespons))
									{
										array_push($eightballrespons, "It is decidedly so.", "It is certain.", "Yes – definitely.", "You may rely on it.", "As I see it, yes.", "Most likely.", "Outlook good.", "Signs point to yes.", "Yes.", "Reply hazy, try again.", "Ask again later.", "Better not tell you now.", "Cannot predict now.", "Concentrate and ask again.", "Don't count on it.", "My reply is no.", "My sources say no.", "Outlook not so good.", "Very doubtful.");
									}
									else
									{
										$eightballrespons = array("It is decidedly so.", "It is certain.", "Yes – definitely.", "You may rely on it.", "As I see it, yes.", "Most likely.", "Outlook good.", "Signs point to yes.", "Yes.", "Reply hazy, try again.", "Ask again later.", "Better not tell you now.", "Cannot predict now.", "Concentrate and ask again.", "Don't count on it.", "My reply is no.", "My sources say no.", "Outlook not so good.", "Very doubtful.");
									}
								}
							}
						}
						else
						{
							$eightballrespons = array("It is decidedly so.", "It is certain.", "Yes – definitely.", "You may rely on it.", "As I see it, yes.", "Most likely.", "Outlook good.", "Signs point to yes.", "Yes.", "Reply hazy, try again.", "Ask again later.", "Better not tell you now.", "Cannot predict now.", "Concentrate and ask again.", "Don't count on it.", "My reply is no.", "My sources say no.", "Outlook not so good.", "Very doubtful.");
						}
						$eightballcnt = count($eightballrespons);
						$eightballcnt--;
						$eightballrand = rand(0, $eightballcnt);
						$msg = $eightballrespons[$eightballrand];
					}
				}
				elseif ($core['cmd']['8ball']['module'] == "unloaded")
				{
					$msg = "8ball module not loaded";
				}
				break;

			//hl command
			case $core['prefix']."hl":
				$core['cmd']['hl'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."hl<.play to start><.higher><.lower> <number for difficulty>.", 'admin' => "0");
				if ($core['cmd']['hl']['module'] == null) { $core['cmd']['hl']['module'] = "loaded"; }
				
				if ($core['cmd']['hl']['module'] == "loaded")
				{
					$gameowner = $output['username'];
					$hlfile = "data/highlow.txt";
					if($output['argument'][1] == "play" || $core['games']['highlow'][$gameowner]['active'] == true)
					{
						if ($core['games']['highlow'][$gameowner]['active'] == true && $core['games']['highlow'][$gameowner]['chances'] != 0) { $msg = "Game is already in progress."; }
						elseif($core['games']['highlow'][$gameowner]['active'] == false || !isset($core['games']['highlow'][$gameowner]['active']))
						{

							$core['games']['highlow'][$gameowner]['active'] = true;

							if(!isset($core['games']['highlow'][$gameowner]['score'])){ $core['games']['highlow'][$gameowner]['score'] = 0; }
							if($core['games']['highlow'][$gameowner]['active'] == true && !isset($core['games']['highlow'][$gameowner]['firstturn']))
							{
								$core['games']['highlow'][$gameowner]['firstturn'] = false;
								$core['games']['highlow'][$gameowner]['chances'] = 3;
								$players = getplayers($gameowner, $hlfile);
								if(isset($players[$gameowner]))
								{	
									$core['games']['highlow'][$gameowner]['score'] = $players["score"];
									$core['games']['highlow'][$gameowner]['difficulty'] = $players["difficulty"];
								}
								else
								{
									if(is_numeric($data))
									{
										$core['games']['highlow'][$gameowner]['difficulty'] = $data;
										$core['games']['highlow'][$gameowner]['currentturn']['basenumber'] = rand(0,$core['games']['highlow'][$gameowner]['difficulty']);
										$core['games']['highlow'][$gameowner]['currentturn']['actualnumber'] = rand(0,$core['games']['highlow'][$gameowner]['difficulty']);
									}
									else
									{
										$core['games']['highlow'][$gameowner]['currentturn']['basenumber'] = rand(0,100);
										$core['games']['highlow'][$gameowner]['currentturn']['actualnumber'] = rand(0,100);
									}
								}
								$msg = "Base number is: ".$core['games']['highlow'][$gameowner]['currentturn']['basenumber'].". You have 3 chances to guess. Use: ".$core['prefix']."hl.higher to guess if it's higher than the base number. Use: ".$core['prefix']."hl.lower to guess if it's lower than the base number.";
							}
						}
					}
					if($core['games']['highlow'][$gameowner]['active'] == true && $core['games']['highlow'][$gameowner]['firstturn'] == false)
					{
						if($output['argument'][1] == "higher" && $core['games']['highlow'][$gameowner]['chances'] != 0)
						{
							if($core['games']['highlow'][$gameowner]['currentturn']['basenumber'] > $core['games']['highlow'][$gameowner]['currentturn']['actualnumber'])
							{
								$core['games']['highlow'][$gameowner]['score']++;
								$msg = "Congrats! You've guessed correctly! Your current streak is now: ".$core['games']['highlow'][$gameowner]['score'].". Type ".$core['prefix']."hl.play to continue.";
								$core['games']['highlow'][$gameowner]['stop'] = true;
							}
							else
							{
								$core['games']['highlow'][$gameowner]['chances']--;
								if(isset($core['games']['highlow'][$gameowner]['difficulty']))
								{
									$core['games']['highlow'][$gameowner]['currentturn']['basenumber'] = rand(0,$core['games']['highlow'][$gameowner]['difficulty']);
								}
								else
								{
									$core['games']['highlow'][$gameowner]['currentturn']['basenumber'] = rand(0,100);
								}
								if($core['games']['highlow'][$gameowner]['chances'] == 0)
								{
									$msg = "You were wrong. Actual number was ".$core['games']['highlow'][$gameowner]['currentturn']['actualnumber'].". You have no chances remaining.";
								}
								else
								{
									if ($core['games']['highlow'][$gameowner]['chances'] == "1") { $chance = "chance"; }
									else { $chance = "chances"; }
									$msg = "You were wrong, new base number is: ".$core['games']['highlow'][$gameowner]['currentturn']['basenumber'].". You have ".$core['games']['highlow'][$gameowner]['chances']." ".$chance." remaining.";
								}
							}
						}
						if($output['argument'][1] == "lower" && $core['games']['highlow'][$gameowner]['chances'] != 0)
						{
							if($core['games']['highlow'][$gameowner]['currentturn']['basenumber'] < $core['games']['highlow'][$gameowner]['currentturn']['actualnumber'])
							{
								$core['games']['highlow'][$gameowner]['score']++;
								$msg = "Congrats! You've guessed correctly! Your current streak is now: ".$core['games']['highlow'][$gameowner]['score'].". Type ".$core['prefix']."hl.play to continue.";
								$core['games']['highlow'][$gameowner]['stop'] = true;
							}
							else
							{
								$core['games']['highlow'][$gameowner]['chances']--;
								if(isset($core['games']['highlow'][$gameowner]['difficulty']))
								{
									$core['games']['highlow'][$gameowner]['currentturn']['basenumber'] = rand(0,$core['games']['highlow'][$gameowner]['difficulty']);
								}
								else
								{
									$core['games']['highlow'][$gameowner]['currentturn']['basenumber'] = rand(0,100);
								}
								if($core['games']['highlow'][$gameowner]['chances'] == 0)
								{
									$msg = "You were wrong. Actual number was ".$core['games']['highlow'][$gameowner]['currentturn']['actualnumber'].". You have no chances remaining.";
								}
								else
								{
									if ($core['games']['highlow'][$gameowner]['chances'] == "1") { $chance = "chance"; }
									else { $chance = "chances"; }
									$msg = "You were wrong, new base number is: ".$core['games']['highlow'][$gameowner]['currentturn']['basenumber'].". You have ".$core['games']['highlow'][$gameowner]['chances']." ".$chance." remaining.";
								}
							}
						}
						if($output['argument'][1] == "lower" && $core['games']['highlow'][$gameowner]['chances'] == 0)
						{
							$msg = "It appears you have run out of turns, your highest streak was: ".$core['games']['highlow'][$gameowner]['score'].". Type ".$core['prefix']."hl.play to begin again!";
							$core['games']['highlow'][$gameowner]['stop'] = true;
						}
						if($output['argument'][1] == "score") { $msg = "Your highest streak was: ".$core['games']['highlow'][$gameowner]['score']; }
						if($output['argument'][1] == "stop")
						{
							unset($core['games']['highlow'][$gameowner]['active']);
							unset($core['games']['highlow'][$gameowner]['firstturn']);
							putplayers($gameowner, $core['games']['highlow'][$gameowner]['score'], $core['games']['highlow'][$gameowner]['difficulty'], $hlfile);
							$msg = "Ending the game, your streak was ".$core['games']['highlow'][$gameowner]['score'].". ";
						}
					}
					if($core['games']['highlow'][$gameowner]['active'] == false || !isset($core['games']['highlow'][$gameowner]['active']) && $core['games']['highlow'][$gameowner]['stop'] == true)
					{
						$msg .= "Type ".$core['prefix']."hl.play to begin.";
						unset($core['games']['highlow'][$gameowner]['stop']);
					}
					if($core['games']['highlow'][$gameowner]['firstturn'] == true && $core['games']['highlow'][$gameowner]['active'] == true) { $core['games']['highlow'][$gameowner]['firstturn'] == false; }
					if($core['games']['highlow'][$gameowner]['stop'] == true)
					{
						putplayers($gameowner, $core['games']['highlow'][$gameowner]['score'], $core['games']['highlow'][$gameowner]['difficulty'], $hlfile);
						unset($core['games']['highlow'][$gameowner]['active']);
						unset($core['games']['highlow'][$gameowner]['firstturn']);
						unset($core['games']['highlow'][$gameowner]['stop']);
					}
				}
				elseif ($core['cmd']['hl']['module'] == "unloaded")
				{
					$msg = "hl module not loaded";
				}
				break;

			//quote command
			case $core['prefix']."quote":
				$core['cmd']['quote'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."quote(.add|.rand) <text/nick/number>", 'admin' => "0");
				if ($core['cmd']['quote']['module'] == null) { $core['cmd']['quote']['module'] = "loaded"; }
				
				if ($core['cmd']['quote']['module'] == "loaded")
				{
					$quotesfile = "data/quotes.txt";

					if ($output['argument'][1] == "add")
					{
						$quotenumber = substr_count($contents = file_get_contents($quotesfile), "\n\r");
						echo $quotenumber;
						if ($quotenumber >= "1") { $quotenumber++; }
						else { $quotenumber = "1"; }
						$quote = ":".$quotenumber.":".$data."#0#<".$buffer['username'].">*".date("m/d/Y")."*\n\r";
						file_put_contents($quotesfile, $quote, FILE_APPEND);
						echo "test";
					}
					if ($output['argument'][1] == "rand")
					{
		//				$lines = substr_count($contents = file_get_contents($quotesfile), "\n");
						
					}
					if ($output['argument'][1] == "vote")
					{
						$contents = file_get_contents($quotesfile);
						$quotesplit = explode("\n\r", $contents);
						$qn = "1";
						foreach ($quotesplit as $quoted)
						{
							 preg_match_all('/:(?<number>\d+):(?<quote>.*)#(?<votes>\d+)#/', $quoted, $quotesplitdos[$qn]);
							$qn++;
						}
						preg_match_all('/(?P<number>\d+)(?P<vote>\W+)/', $data, $votesplit);
						$votenum = $votesplit["number"][0];
						$votedir = $votesplit["vote"][0];

						if (is_numeric($votenum))
						{
							if ($votedir == "--")
							{
								$votenum--;
								$oldvote = $quotesplitdos[$votenum]["votes"][0];
								echo $oldvote." ";
								$newvote = $oldvote-"1";
								echo $newvote;
								$quotesplit[$votenum] = str_replace("#".$oldvote."#", "#".$newvote."#", $quotesplit[$votenum]);
								$quotewrite = implode("\n\r", $quotesplit);
								file_put_contents($quotesfile, $quotewrite);

							}
							if ($votedir == "++")
							{
								$votenum--;
								$oldvote = $quotesplitdos[$votenum]["votes"][0];
								echo $oldvote." ";
								$newvote = $oldvote+"1";
								echo $newvote;
								$quotesplit[$votenum] = str_replace("#".$oldvote."#", "#".$newvote."#", $quotesplit[$votenum]);
								$quotewrite = implode("\n\r", $quotesplit);
								file_put_contents($quotesfile, $quotewrite);
								$oldvote++;
							}
						}
//						var_dump($quotesplit);
//						var_dump($quotesplitdos);
					}

				//	$msg = ;
				}
				elseif ($core['cmd']['quote']['module'] == "unloaded")
				{
					$msg = "Quote module not loaded";
				}
				break;

			//eval command
			case $core['prefix']."eval":
				$core['cmd']['eval'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."eval <php>", 'admin' => "20");
				if ($core['cmd']['eval']['module'] == null) { $core['cmd']['eval']['module'] = "loaded"; }
				
				if ($core['cmd']['eval']['module'] == "loaded")
				{
					eval($data);
				}
				elseif ($core['cmd']['eval']['module'] == "unloaded")
				{
					$msg = "eval module not loaded";
				}
				break;

			//makes temp admins
			case $core['prefix']."mkadmin":
				$core['cmd']['mkadmin'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."mkadmin <nick> <lvl>", 'admin' => "20");
				if ($core['cmd']['mkadmin']['module'] == null) { $core['cmd']['mkadmin']['module'] = "loaded"; }
				
				if ($core['cmd']['mkadmin']['module'] == "loaded")
				{
					if (admin("20"))
					{
						if (count($output['args']) == 2)
						{
							$nick = $output['args'][0];
							$adminlevel = $output['args'][1];

							$i == 0;
							while(strlen($rawwho) < 4)
							{
								raw("WHOIS :".$nick);
								$rawwho = socket_read($socket, 4048);
								if (strlen($rawwho) < 4 && !isset($rawwho))
								{
									$rawwho = socket_read($socket, 4048);
								}
								if ($i <= 2) { break; }
								$i++;
							}

							$whox = explode("\n", $rawwho);
							$whoex = explode(" ", $whox[0]);

							$adminnick = $whoex[3];
							$adminhost = $whoex[5];

							$core['admin'][$adminnick]['host'] = $adminhost;
							$core['admin'][$adminnick]['lvl'] = $adminlevel;
							pm($adminnick, "You now have level ".$adminlevel." to me.");
						}
						else
						{
							$msg = "Usage: ".$core['prefix']."mkadmin <nick> <lvl>";
						}
					}
					else
					{
						$msg = "You're not admin level ".$core['cmd']['mkadmin']['admin'].".";
					}
				}

				elseif ($core['cmd']['mkadmin']['module'] == "unloaded")
				{
					$msg = "mkadmin module not loaded";
				}
				break;
				
			//removes temp admins
			case $core['prefix']."rmadmin":
				$core['cmd']['rmadmin'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."rmadmin <nick>", 'admin' => "20");
				if ($core['cmd']['rmadmin']['module'] == null) { $core['cmd']['rmadmin']['module'] = "loaded"; }
				
				if ($core['cmd']['rmadmin']['module'] == "loaded")
				{
					if (admin('20'))
					{
						$adminick = $output['args'][0];
						unset($core['admin'][$adminick]);
						pm($adminick, "Your administrative privledges have been revoked.");
					}
					else
					{
						$msg = "You're not admin level ".$core['cmd']['rmadmin']['admin'].".";
					}
				}
				
				elseif ($core['cmd']['rmadmin']['module'] == "unloaded")
				{
					$msg = "rmadmin module not loaded";
				}
				break;

			//steam daily deals
			case $core['prefix']."gd":
				$core['cmd']['gd'] = array('exec' => "msg", 'help' => "Usage: ".$core['prefix']."gd <date>", 'admin' => "0");
				if ($core['cmd']['gd']['module'] == null) { $core['cmd']['gd']['module'] = "loaded"; }
				
				if ($core['cmd']['gd']['module'] == "loaded")
				{
					$gamedeals = gamedeals($output['argument'][1], $data);
					if (is_array($gamedeals)) { $msg = $gamedeals["title"].' - '.$gamedeals["link"]; }
					else { $msg = $gamedeals; }
				}
				elseif ($core['cmd']['gd']['module'] == "unloaded")
				{
					$msg = "Steam module not loaded";
				}
				break;

			//quit command
			case $core['prefix']."quit":
				$core['cmd']['quit'] = array('exec' => "bdie", 'help' => "Usage: ".$core['prefix']."quit <text (optional)>", 'admin' => "20");
				if ($core['cmd']['quit']['module'] == null) { $core['cmd']['quit']['module'] = "loaded"; }
				
				if ($core['cmd']['quit']['module'] == "loaded")
				{
					if ($output['argument'] != "help")
					{
						if($data != null)
						{
							$msg = $data;
						}

						else
						{
							$msg = "Le Quit!";
						}
					}
				}
				
				elseif ($core['cmd']['eval']['module'] == "unloaded")
				{
					$msg = "quit module not loaded";
				}
				break;
		}
	
		//arguments, modify $msg to do cool stuff.
		if (is_array($output['argument']))
		{
			foreach ($output['argument'] as $output['argument'])
			{
				if ($output['argument'] == "low") { $msg = strtolower($msg); }
				if ($output['argument'] == "up") { $msg = strtoupper($msg); }
				if ($output['argument'] == "rev") { $msg = strrev($msg); }
				if ($output['argument'] == "len") { $msg = strlen($msg); }
				if ($output['argument'] == "md5") { $msg = md5($msg); }
				if ($output['argument'] == "sha1") { $msg = sha1($msg); }
				if ($output['argument'] == "crypt") { $msg = crypt($msg); }
				if ($output['argument'] == "var")
				{
					$core['var'][$cmd] = $msg;
					$msg = $msg;
				}
				if ($output['argument'] == "help")
				{
					$msg = $core['cmd'][$cmd]['help'];
					if (($admin == true) || (!isset($admin))) { $msg .= ", You have access to this command"; }
					if (($admin == false) && (isset($admin))) { $msg .= ", You don't have access to this command"; }
				}
				if (($output['command'] == $core['prefix']."".$core['nick']) && ($output['argument'] == "ident"))
				{
					pm("NICKSERV", "identify ".$core['nspass']);
				}
				if (($output['command'] == $core['prefix']."".$core['nick']) && ($output['argument'] == "uptime"))
				{
					$now = strtotime("now");
					$uptime = $now - $core['starttime'];
					$uptime = uptary($uptime);
					$msg = "I've been up for";
					if (isset($uptime['years'])) { $msg .= " ".$uptime['years']. " years"; }
					if (isset($uptime['weeks'])) { $msg .= " ".$uptime['weeks']. " weeks"; }
					if (isset($uptime['days'])) { $msg .= " ".$uptime['days']. " days"; }
					if (isset($uptime['minutes'])) { $msg .= " ".$uptime['minutes']. " minutes"; }
					if (isset($uptime['seconds'])) { $msg .= " ".$uptime['seconds']. " seconds"; }
				}
			}
		}
		if ($msg != null)
		{
			if(($core['cmd'][$cmd]['admin'] == "0") || (admin($core['cmd'][$cmd]['admin'])))
			{
				if (is_array($msg) && $core['cmd'][$cmd]['exec'] == "msg")
				{
					foreach ($msg as $msgs)
					{
						$core['cmd'][$cmd]['exec']($msgs);
					}
				}
				elseif (is_array($msg) && $core['cmd'][$cmd]['exec'] != "msg")
				{
					foreach ($msg as $msgs)
					{
						$core['cmd'][$cmd]['exec']($msgs);
					}
				}
				else
				{
					$core['cmd'][$cmd]['exec']($msg);
				}
			}
			else
			{
				msg("You don't have admin status");
			}
		}
		if ($core['debug'] == true)
		{
			var_dump($output);
			var_dump($core);
			var_dump($msg);
		}
	}
?>
