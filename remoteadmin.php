<?php 
	header("Content-Type: text/plain");

	$adminsnos=0;
	$failed=0;	
	$total=0;	
	$Groups = array();
	$Admins = "";
	$failedlinenos = "";
	$GroupPerms= "";
	$noofretries = 1;
	$csheetretreive = false;
	$isheetretreive = false;
	$APIKey = getenv('API_KEY');
	#$APIKey = $_SERVER['API_KEY'];

	if((isset($_GET['config'])) && (!(empty($_GET['config']))))
	{
		if(strlen($_GET['config']) == 86) {
			$CType = 'PublicID';
			$url = 'https://docs.google.com/spreadsheets/d/e/' . urlencode($_GET['config']) . '/pub?output=csv';
		} elseif (strlen($_GET['config']) == 44) {
			$CType = 'SheetID';
			if(!(empty($_GET['csheet']))) {
				$csheet = $_GET['csheet'];
			} else {
				$url = 'https://sheets.googleapis.com/v4/spreadsheets/' . urlencode($_GET['config']) . '?key=' . $APIKey;
				if(isset($url)) {
					if(isset($_GET['DB']) && ($_GET['DB'] == 'true')) {
						echo "// DB:lookup   : " . (str_replace($APIKey, 'YOUR_API_KEY', $url)) . "\n";
					}
					$arrContextOptions=array(
						  "ssl"=>array(
								"verify_peer"=>false,
								"verify_peer_name"=>false,
							),
						);  
					$retries = 0;
					$response = null;
					$responsecode = "";
					do{
						$retries++;
						$response = file_get_contents($url, false, stream_context_create($arrContextOptions));
						$responsecode = $http_response_header[0];
						if(isset($_GET['RC']) && ($_GET['RC'] == 'true')) {
							$responsecode = $http_response_header[0];
							echo "// RC:lookup(" . $retries . "): " . $responsecode . "\n";
						}
					} while(!$response && $responsecode == "HTTP/1.0 404 Not Found" && $retries < $noofretries);
					if(isset($response)) {
						$rawJSON = json_decode($response, true);
						if(isset($rawJSON)) {
							$csheet = $rawJSON['sheets'][0]['properties']['title'];
							if((isset($_GET['DB']) && $_GET['DB'] == 'true')) {
								echo "// DB:lookup   : Retrieved sheet name is (" . $csheet . ")\n";
							}
							if($responsecode == 'HTTP/1.0 200 OK') {
								$csheetretreive = true;
							}
						}
					}
				}
				if(!(isset($csheet))) {
					$csheet = 'Sheet1';
				}
			}
			if((isset($APIKey)) && (isset($csheet))) {
				$url = 'https://sheets.googleapis.com/v4/spreadsheets/' . urlencode($_GET['config']) . '/values/' . urlencode($csheet) . '?key=' . $APIKey;
			}
			
		}
		if(isset($url)) {
			if(isset($_GET['DB']) && ($_GET['DB'] == 'true')) {
				echo "// DB:config   : " . (str_replace($APIKey, 'YOUR_API_KEY', $url)) . "\n";
			}
			$arrContextOptions=array(
				  "ssl"=>array(
						"verify_peer"=>false,
						"verify_peer_name"=>false,
					),
				);  
			$retries = 0;
			$response = null;
			$responsecode = "";
			do{
				$retries++;
				$response = file_get_contents($url, false, stream_context_create($arrContextOptions));
				$responsecode = $http_response_header[0];
				if((isset($_GET['RC']) && $_GET['RC'] == 'true')) {
					$responsecode = $http_response_header[0];
					echo "// RC:config(" . $retries . "): " . $responsecode . "\n";
				}
			} while(!$response && $responsecode == "HTTP/1.0 404 Not Found" && $retries < $noofretries);
			if(isset($response)) {
				if($CType == 'PublicID') {
					if(strstr($response, PHP_EOL)) {
						$SplitByLine = explode(PHP_EOL, $response);
						foreach( $SplitByLine as $number => $Line ) {
							if(strstr($Line, ',')) {
								$ParsedResponseCF[] = str_getcsv( $Line, ",", '"');
							}
							
						}
					}
				} elseif($CType == 'SheetID') {
					$rawJSON = json_decode($response, true);
					$ParsedResponseCF = $rawJSON['values'];
				}
				foreach( $ParsedResponseCF as $number => $Line ) {
					if($number == 0) {
						$Headings = $Line;
					} else {
						if($Line[0] == $_GET['id']) {
							$GroupPerms .= 'Group=' . (trim($Line[1])) . ':' . $Line[2] . "\n";
							$SpreadheetIDs[] = (trim($Line[0]));
							$Groups[] = (trim($Line[1]));
							#var_dump($Line);
						}
					}
				}
			}
			$GroupPerms .= "\n";
		}
	}
	
	if((!(empty($_GET['id'])))) {


		if(strlen($_GET['id']) == 86) {
			$IType = 'PublicID';
			$url = 'https://docs.google.com/spreadsheets/d/e/' . urlencode($_GET['id']) . '/pub?output=csv';
		} elseif (strlen($_GET['id']) == 44) {
			$IType = 'SheetID';
			if(!(empty($_GET['isheet']))) {
				$isheet = $_GET['isheet'];
			} else {
				$url = 'https://sheets.googleapis.com/v4/spreadsheets/' . urlencode($_GET['id']) . '?key=' . $APIKey;
				if(isset($url)) {
					if(isset($_GET['DB']) && ($_GET['DB'] == 'true')) {
						echo "// DB:lookup   : " . (str_replace($APIKey, 'YOUR_API_KEY', $url)) . "\n";
					}
					$arrContextOptions=array(
						  "ssl"=>array(
								"verify_peer"=>false,
								"verify_peer_name"=>false,
							),
						);  
					$retries = 0;
					$response = null;
					$responsecode = "";
					do{
						$retries++;
						$response = file_get_contents($url, false, stream_context_create($arrContextOptions));
						$responsecode = $http_response_header[0];
						if(isset($_GET['RC']) && ($_GET['RC'] == 'true')) {
							$responsecode = $http_response_header[0];
							echo "// RC:lookup(" . $retries . "): " . $responsecode . "\n";
						}
					} while(!$response && $responsecode == "HTTP/1.0 404 Not Found" && $retries < $noofretries);
					if(isset($response)) {
						$rawJSON = json_decode($response, true);
						if(isset($rawJSON)) {
							$isheet = $rawJSON['sheets'][0]['properties']['title'];
							if(isset($_GET['DB']) && ($_GET['DB'] == 'true')) {
								echo "// DB:lookup   : Retrieved sheet name is (" . $isheet . ")\n";
							}
							if($responsecode == 'HTTP/1.0 200 OK') {
								$isheetretreive = true;
							}
						}
					}
				}
				if(!(isset($isheet))) {
					$isheet = 'Sheet1';
				}
			}
			if((isset($APIKey)) && (isset($isheet))) {
				$url = 'https://sheets.googleapis.com/v4/spreadsheets/' . urlencode($_GET['id']) . '/values/' . urlencode($isheet) . '?key=' . $APIKey;
			}
			
		}
		
		if(isset($url)) {
			if(isset($_GET['DB']) && ($_GET['DB'] == 'true')) {
				echo "// DB:id       : " . (str_replace($APIKey, 'YOUR_API_KEY', $url)) . "\n";
			}
			$arrContextOptions=array(
				  "ssl"=>array(
						"verify_peer"=>false,
						"verify_peer_name"=>false,
					),
				);  
			$retries = 0;
			$response = null;
			$responsecode = "";
			do{
				$retries++;
				$response = file_get_contents($url, false, stream_context_create($arrContextOptions));
				$responsecode = $http_response_header[0];
				if(isset($_GET['RC']) && ($_GET['RC'] == 'true')) {
					$responsecode = $http_response_header[0];
					echo "// RC:id    (" . $retries . "): " . $responsecode . "\n";
				}
			} while(!$response && $responsecode == "HTTP/1.0 404 Not Found" && $retries < $noofretries);
			if(isset($response)) {
				if($IType == 'PublicID') {
					if(strstr($response, PHP_EOL)) {
						$SplitByLine = explode(PHP_EOL, $response);
						//$total = (count($SplitByLine)-1);
						foreach( $SplitByLine as $number => $Line ) {
							if(strstr($Line, ',')) {
								$ParsedResponseID[] = str_getcsv( $Line, ",", '"');
							}
						}
					}
				} elseif($IType == 'SheetID') {
					$rawJSON = json_decode($response, true);
					$ParsedResponseID = $rawJSON['values'];
				}
				
				//$total = (count($ParsedResponseID)-1);
				foreach( $ParsedResponseID as $number => $Line ) {
					if($number == 0) {
						$Headings = $Line;
						$UserIDIndex = array_search('username', array_map('strtolower', $Headings));
						if($UserIDIndex == false) { $UserIDIndex = 1; }
						$GroupIndex = array_search('group', array_map('strtolower', $Headings));
						if($GroupIndex == false) { $GroupIndex = 2; }
						$SteamIDIndex = array_search('steamid', array_map('strtolower', $Headings));
						if($SteamIDIndex == false) { $SteamIDIndex = 3; }
					} else {
						if(isset($Line)) {
							$total++;
							if (isset($Line[$SteamIDIndex]) && (substr($Line[$SteamIDIndex], 0, 4) == '7656') && (is_numeric($Line[$SteamIDIndex])) && (in_array((trim($Line[$GroupIndex])), $Groups))) {
								if(isset($ClanData)) { $ClanData = ''; }
								if ((isset($Line[0])) && ($Line[0] != '')) {
									$ClanData = $Line[0] . ' - ';
								}
								if(!(isset($ClanData))) { $ClanData = ''; }
								$Admins .= 'Admin=' . (trim($Line[$SteamIDIndex])) . ':' . (trim($Line[$GroupIndex])) . ' // ' . $ClanData . (trim($Line[$UserIDIndex])) . "\n";
								$adminsnos++;
							} else {
								$failed++;
								// plus 2 because starting 0 and spreadsheet heading
								$failedlinenos .= $number+2 . ',';
							}
						}
					}
				}
			}
		}
		
		if(((!(isset($_GET['csheet'])) && $CType == 'SheetID') || (!(isset($_GET['isheet'])) && $IType == 'SheetID')) && isset($csheet) && $csheetretreive && isset($isheet) && $isheetretreive && isset($GroupPerms) && isset($Admins))
		{
			print("// Use this URL next time. It's faster:\n");
			$betterurl = "https://remoteadminlist.com/remoteadmin.php?config=" . ($_GET['config']) . "&id=" . ($_GET['id']) . "&csheet=" . $csheet . "&isheet=" . $isheet;
			print("// " . $betterurl . "\n");
		}
		
		if($failed > 0)
		{
			print("// " . $adminsnos . " admins loaded (Total: " . $total . "). (" . $failed . " failed - failed line numbers (" . rtrim($failedlinenos,",") . "))." . "\n");
		} else {
			print("// " . $adminsnos . " admins loaded (Total: " . $total . "). (" . $failed . " failed)." . "\n");
		}
		print($GroupPerms);	
		print($Admins);
	} else {
		print("You're missing one or both of the parameters.....\n\n");

		print("The remoteadminlist.com is a free to use tool for Squad and Post Scriptum that translates your google sheets into remote admin lists.\n");
		print("These can then be entered into your servers RemoteAdminListHost.cfg files.\n\n");
		
		print("\n\n\n");
		print("****************************************UPDATED PLEASE READ****************************************\n\n");
		print("Following an update from Google the original endpoint used by this tool was retired.\n");
		print("https://spreadsheets.google.com/feeds/list/1_6RcFNbPNaZ2jViKAV3dMfc-Jx7BqPS-uaawq7JEb4A/1/public/values?alt=json\n");
		print("The tool has been rewritten and that means some changes for the URL, Config Sheet, IDs or Permissions.\n\n");
		print("There are two endpoints you can use that have slightly different requirements:\n\n");
		print("***************************************************************************************************\n\n");
		print("1. Via the API - this method uses the original Google Sheet ID used all along. \n\n");
		print("If you've used this all along then the only change you need to make now is in the Share permissions:\n");
		print("-In the top right in Google Sheets select Share\n");
		print("-At the bottom of the Share window under Get a Link select Change\n");
		print("-Set this option to 'Anyone on the Internet with this link can view'\n");
		print("-Two optional additional parameters have been provided for the sheet names (config = csheet / id = isheet)\n\n");
		print("https://docs.google.com/spreadsheets/d/1_6RcFNbPNaZ2jViKAV3dMfc-Jx7BqPS-uaawq7JEb4A/edit#gid=0\n");
		print("https://docs.google.com/spreadsheets/d/1_6RcFNbPNaZ2jViKAV3dMfc-Jx7BqPS-uaawq7JEb4A/edit#gid=0\n");
		print("https://remoteadminlist.com/remoteadmin.php?config=1_6RcFNbPNaZ2jViKAV3dMfc-Jx7BqPS-uaawq7JEb4A&id=1MUTPM3KIIVkHdkgbEwlTwxY7TMPidKZ66MsebjY-HSA&csheet=Sheet1&isheet=Sheet1\n\n");
		print("***************************************************************************************************\n\n");
		print("2. Via the 'Published to the web' endpoints. - this method uses the orignal share permissions.\n\n");
		print("If you've used this for some time the only change you need to make is to the IDs (URL & Config Sheet)\n");
		print("-In the File menu select 'Publish to the Web'\n");
		print("-Ensure the Google Sheet is Published.\n");
		print("-In the Link provided select the ID between 'https://docs.google.com/spreadsheets/d/e/' and '/pubhtml'\n");
		print("-For the ID sheet ensure you update the first column of the config sheet with the new ID\n\n");
		print("https://docs.google.com/spreadsheets/d/e/2PACX-1vRNUOdGKLRRmEJ1mQ-4MTJ-BekMj-zjDApoB3U6EeP9XlzYT8gDJcHgX7qSnu7miycrYLQhENyErLx8/pubhtml\n");
		print("https://docs.google.com/spreadsheets/d/e/2PACX-1vSslC7T6YSx1XxoEq2pxZp6RWCDis4Cc7gFtCpHm6eiVj87CV25ElpV8KjpY8tMj4gcTPD0z8aMcN7D/pubhtml\n");
		print("http://remoteadminlist.com/remoteadmin.php?config=2PACX-1vRNUOdGKLRRmEJ1mQ-4MTJ-BekMj-zjDApoB3U6EeP9XlzYT8gDJcHgX7qSnu7miycrYLQhENyErLx8&id=2PACX-1vSslC7T6YSx1XxoEq2pxZp6RWCDis4Cc7gFtCpHm6eiVj87CV25ElpV8KjpY8tMj4gcTPD0z8aMcN7D\n\n");
		print("****************************************UPDATED PLEASE READ****************************************\n\n");
		print("\n\n\n\n\n");
		
		print("The php script takes 2 GET parameters (referenced in the URL), config and id, referring to 2 Google Sheets. e.g.\n");
		print("http://remoteadminlist.com/remoteadmin.php?config=1_6RcFNbPNaZ2jViKAV3dMfc-Jx7BqPS-uaawq7JEb4A&id=1MUTPM3KIIVkHdkgbEwlTwxY7TMPidKZ66MsebjY-HSA\n");
		print("Contains 2 GET parameters\n");
		print("1. a Config parameter:\n");
		print("1_6RcFNbPNaZ2jViKAV3dMfc-Jx7BqPS-uaawq7JEb4A\n");
		print("2. and an ID parameter:\n");
		print("1MUTPM3KIIVkHdkgbEwlTwxY7TMPidKZ66MsebjY-HSA\n\n");

		print("Therefore you will need 2 corresponding Google Sheets:\n\n");
		print("1. The Config Sheet - This sheet holds the settings for the way the users defined in the ID Google Sheet will be mapped into roles in the RemoteAdminList.\n");
		print("https://docs.google.com/spreadsheets/d/1_6RcFNbPNaZ2jViKAV3dMfc-Jx7BqPS-uaawq7JEb4A/edit#gid=0\n");
		print("It contains 4 columns:\n");
		print("-the reference of the ID spreadsheet these roles will be applied to\n");
		print("-the role group name\n");
		print("-the access permissions granted to the role group https://squad.gamepedia.com/Server_Administration\n");
		print("-a free text field to remind you what the sheet is (usually the title of the ID sheet)\n\n");

		print("2. The ID Sheet - This sheet contains a list of the users that will be mapped to the roles defined in the corresponding Config Google Sheet.\n");
		print("https://docs.google.com/spreadsheets/d/1MUTPM3KIIVkHdkgbEwlTwxY7TMPidKZ66MsebjY-HSA/edit#gid=0\n");
		print("It also contains 4 columns:\n");
		print("-users clan\n");
		print("-users name\n");
		print("-users group (must be correspond to the group defined in the Config Google Sheet)\n");
		print("-users steam id (starting 7656...)\n\n");

		print("IMPORTANT\n");
		print("1. Once you have drafted these Google Sheets you need to publish them (Go to the 'File' menu and click on 'Publish to the Web').\n");
		print("If you do not then the permissions will restrict the php script being able to extract the information in your Google Sheets.\n");
		print("This does not affect edit rights which can be defined as per usual in Google Sheets. \n");
		print("2. The reference of the ID sheet must be added to the Config Google Sheet next to the role groups created in relation to it.\n");
		print("You can have one master Config Google Sheet for multiple ID Google Sheets.\n\n");

		print("This is a great way to get users to add permissions for themselves for one off events (such as one life events)\n");
		print("as you can control what permissions they are allowed to assign themselves to. It's also a great way to provide whitelists\n");
		print("and admin permissions to partner and stakeholder clans in your servers so that they can add new members and remove old members\n");
		print("without asking the server owner every time.\n\n");

		print("It's free to use and there's a second version hosted at:\n");
		print("http://internetthingy.co.uk/remoteadmin.php\n");
		print("If you which to host a version yourself you can download it from GitHub.\n\n");

		print("http://remoteadminlist.com/remoteadmin.php\n");
		print("https://docs.google.com/spreadsheets/d/1_6RcFNbPNaZ2jViKAV3dMfc-Jx7BqPS-uaawq7JEb4A/edit#gid=0\n");
		print("https://docs.google.com/spreadsheets/d/1MUTPM3KIIVkHdkgbEwlTwxY7TMPidKZ66MsebjY-HSA/edit#gid=0\n");
		print("http://remoteadminlist.com/remoteadmin.php?config=1_6RcFNbPNaZ2jViKAV3dMfc-Jx7BqPS-uaawq7JEb4A&id=1MUTPM3KIIVkHdkgbEwlTwxY7TMPidKZ66MsebjY-HSA\n");		
	}
?>