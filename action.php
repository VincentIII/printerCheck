<?php
	//Updates the checks for basic-user changes (setting completion time & no-shows)
	function updateChecksFunc()
	{
		global $connection;
		global $displayType;
		global $fAction;
		global $messages;
		$total = 0;
		$success = 0;
		foreach ($_POST as $key => $value)
		{
			$actionDetect = strpos($key,'ow');
			$updateTrigger = TRUE;
			if ($actionDetect == 1)
			{
				$properKey = substr($key,3);
				if ($fAction == "Clear Values")
				{
					$total++;
					$query = "UPDATE checks SET completedTime=NULL,note=NULL WHERE cId=? ";
					if ($stmt=$connection->prepare($query))
					{
						$stmt->bind_param("i",$properKey);
						$stmt->execute();
						$stmt->store_result();
						$success++;
					}
				}
				else if ($fAction == "Delete")
				{
					$total++;
					$query = "DELETE FROM checks WHERE cId=? ";
					if ($stmt=$connection->prepare($query))
					{
						$stmt->bind_param("i",$properKey);
						$stmt->execute();
						$stmt->store_result();
						$success++;
					}
				}
				else if ($fAction == "Update")
				{
					$query = "UPDATE checks SET";
					if (!empty($_POST['time'.$properKey]))
					{
						$tValue = $_POST['time'.$properKey];
						$dValue = $_POST['date'.$properKey];
						$value = $dValue." ".$tValue;
						$query .= " completedTime=?";
					}
					else if (empty($_POST['note'.$properKey]))
					{
						$updateTrigger = FALSE;
					}
					else if ($_POST['note'.$properKey] != "IGNORE")
					{
						$value = $_POST['note'.$properKey];
						$query .= " note=?";
					}
					else
					{
						$updateTrigger = FALSE;
					}
					
					if ($updateTrigger == TRUE)
					{
						$total++;
						$query .= " WHERE cId=? ";
						if ($stmt=$connection->prepare($query))
						{
							$stmt->bind_param("si", $value, $properKey);
							$stmt->execute();
							$stmt->store_result();
							$success++;
						}
					}
				}
			}
		}
		if ($total != 0 && $total == $success)
		{
			$messages .= "RESULT:All $success Shifts Updated Successfully::";
		}
		else if ($total != 0 && $success != 0 && $total != $success)
		{
			$messages .= "ERROR:Only $success/$total Shifts Updated Successfully::";
		}
		else if ($total != 0 && $success == 0 && $total != $success)
		{
			$messages .= "ERROR:All $total Shifts Failed Update::";
		}
		else {}
	}
		
	//Displays all checks within a specified query
	function checkSearchFunc($dateStart,$dateEnd,$employee,$location,$note,$active)	
	{
		global $connection;
		global $displayType;
		global $displayMode;
		global $messages;
		global $pageName;
		$query = "SELECT checks.cId, checks.locationCode, locations.locationName, checks.username, employees.firstName, employees.lastName, checks.startTime, checks.endTime, checks.completedTime, checks.note, locations.locationType, employees.type FROM checks INNER JOIN locations ON checks.locationCode = locations.locationCode INNER JOIN employees ON checks.username = employees.username WHERE";
		$completeNeeded = FALSE;
		$bindString = "";
		$andTrigger = FALSE;
		
		//Establish Query Perimeters and Search Restraints
		
			//Date
		if ($dateEnd == "IGNORE" && $dateStart != "IGNORE")
		{
			$dateStart = $dateStart." 00:00:00";
			$usedVariables[] = $dateStart;
			$query .= " checks.startTime>=?";
			$andTrigger = TRUE;
			$bindString .= "s";
		}
		else if ($dateEnd != "IGNORE" && $dateStart != "IGNORE")
		{
			$dateStart = $dateStart." 00:00:00";
			$dateEnd = $dateEnd." 23.59.59";
			$usedVariables[] = $dateStart;
			$usedVariables[] = $dateEnd;
			$query .= " checks.startTime>=? AND checks.endTime<=?";
			$andTrigger = TRUE;
			$bindString .= "ss";
		}
		else if ($dateEnd != "IGNORE" && $dateStart == "IGNORE")
		{
			$dateEnd = $dateEnd." 23.59.59";
			$usedVariables[] = $dateEnd;
			$query .= " checks.endTime<=?";
			$andTrigger = TRUE;
			$bindString .= "s";
		}
		else{}
		
			//Username
		if ($employee != "IGNORE")
		{
			if ($andTrigger == TRUE)
			{
				$query .= " AND";
			}
			$usedVariables[] = $employee;
			$query .=" checks.username=?";
			$andTrigger = TRUE;
			$bindString .= "s";
		}
		
			//ConType
		if ($displayMode == "LabCons")
		{
			if ($andTrigger == TRUE)
			{
				$query .= " AND";
			}
			$query .=" employees.type = '1'";
			$andTrigger = TRUE;
		}
		else if ($displayMode == "TechCons")
		{
			if ($andTrigger == TRUE)
			{
				$query .= " AND";
			}
			$query .=" employees.type = '2'";
			$andTrigger = TRUE;
		}
		else{}
		
			//Location
		if ($location != "IGNORE")
		{
			if ($andTrigger == TRUE)
			{
				$query .= " AND";
			}
			$usedVariables[] = $location;
			$query .=" checks.locationCode=?";
			$andTrigger = TRUE;
			$bindString .= "s";
		}
		
			//Note
		if (!empty($note))
		{
			if ($andTrigger == TRUE && $note != "All")
			{
				$query .= " AND";
			}
			
			if ($note == "None")
			{
				$query .= " checks.note IS NULL AND checks.completedTime IS NULL";
				$andTrigger = TRUE;
			}
			else if ($note == "Other Notes")
			{
				$query .= " checks.note LIKE 'OTHER%'";
				$andTrigger = TRUE;
			}
			else if ($note == "All"){}
			else if ($note == "Completed")
			{
				$query .= " checks.note IS NULL AND checks.completedTime IS NOT NULL";
				$andTrigger = TRUE;
			}
			else
			{
				$usedVariables[] = $note;
				$query .= " checks.note = ?";
				$andTrigger = TRUE;
				$bindString .= "s";
			}
		}
		else
		{
			if ($andTrigger == TRUE)
			{
				$query .= " AND";
			}
			$query .= " checks.note IS NULL AND checks.completedTime IS NULL";
			$andTrigger = TRUE;
		}
		if ($andTrigger == TRUE)
		{
			$query .= " AND";
		}
		$usedVariables[] = $active;
		$query .= " employees.active = ? ORDER BY checks.username ASC, checks.startTime DESC";
		$bindString .="i";
		//Create the results tables, separated by category
		$resultsTCounts = 0;
		$resultsPCounts = 0;
		echo "<h1>Results</h1>\n<form action='$pageName?menu=$displayType' id='updateCheck' method='post'>";
		$printerTable = "<h2>Printers</h2><table><tr><th>Location</th><th>Consultant</th><th>Shift Time</th><th>Complete Time</th><th>Notes</th></tr>\n";
		$towersDTable = "<h2>Towers Desk</h2><table><tr><th>Location</th><th>Consultant</th><th>Shift Time</th><th>Complete Time</th><th>Notes</th></tr>\n";
		$standardPClass = "standardA";
		$blankedPClass = "blankedA";
		$standardTClass = "standardA";
		$blankedTClass = "blankedA";
		$oldName = "";
		$oldSTime = "";
		$oldSDate = "";
		$alternateP = FALSE;
		$alternateT = FALSE;
		$towersSwitcher = 0;
		if ($stmt = $connection->prepare($query))
		{
			//Based on how many variables are used in search, bind the parameters correctly
			if (count($usedVariables) == 6)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1],$usedVariables[2],$usedVariables[3],$usedVariables[4],$usedVariables[5]);
			}
			else if (count($usedVariables) == 5)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1],$usedVariables[2],$usedVariables[3],$usedVariables[4]);
			}
			else if (count($usedVariables) == 4)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1],$usedVariables[2],$usedVariables[3]);
			}
			else if (count($usedVariables) == 3)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1],$usedVariables[2]);
			}
			else if (count($usedVariables) == 2)
			{
				$stmt->bind_param($bindString,$usedVariables[0],$usedVariables[1]);
			}
			else if (count($usedVariables) == 1)
			{
				$stmt->bind_param($bindString,$usedVariables[0]);
			}
			else{}
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($cId, $locationCode, $locationName, $username, $firstName, $lastName, $startTime, $endTime, $completedTime, $note, $locationType, $type);
			while ($stmt->fetch())
			{
				$checkDate = substr($startTime,0,10);
				$startTime = substr($startTime,11,5);
				$endTime = substr($endTime,11,5);
				
				//CSS Switcher
				if ($locationType != 3)
				{
					if ($oldName != $username || $oldSTime != $startTime || $oldSDate != $checkDate)
					{
						$oldName = $username;
						$oldSTime = $startTime;
						$oldSDate = $checkDate;
						if ($standardPClass == "standardA")
						{
							$standardPClass = "standardB";
							$blankedPClass = "blankedB";
						}
						else
						{
							$standardPClass = "standardA";
							$blankedPClass = "blankedA";
						}
					}
					$standardClass = $standardPClass;
					$blankedClass = $blankedPClass;
				}
				else
				{
					if ($towersSwitcher == 2 || $oldName != $username)
					{
						$oldName = $username;
						if ($standardTClass == "standardA")
						{
							$standardTClass = "standardB";
							$blankedTClass = "blankedB";
						}
						else
						{
							$standardTClass = "standardA";
							$blankedTClass = "blankedA";
						}
						$towersSwitcher = 0;
					}
					$towersSwitcher++;
					$standardClass = $standardTClass;
					$blankedClass = $blankedTClass;
				}
				
				$rowID = "row".$cId;
				$dateID = "date".$cId;
				$timeID = "time".$cId;
				
				$tableRow = "<tr><td class='$standardClass'><input type='hidden' name='$rowID' value='$cId'><a title='$locationCode'>$locationName</a></td><td class='$standardClass'>$firstName $lastName<br/><em>$username</em></td><td class='$standardClass'>$checkDate<br/>($startTime)-($endTime)</td>";
				
				if ($completedTime == NULL && $note == NULL)	//Set Completed Time
				{
					$tableRow .= "<td class='$standardClass'><input type='time' name='$timeID'><input type='hidden' name='$dateID' value='$checkDate'></td>";
				}
				else if ($completedTime == NULL && $note != NULL)
				{
					$tableRow .= "<td class='$blankedClass'></td>";
				}
				else
				{
					$completedTime = substr($completedTime,11,5);
					$tableRow .= "<td class='$standardClass'>$completedTime</td>";
				}
				
				if ($note == NULL && $completedTime == NULL)	//Set Notes
				{
					$tableRow .= "<td class='$standardClass'>";
					$tableRow .= generateDropDowns("noteU",$cId);
					$tableRow .= "</td>";
				}
				else if ($note == NULL && $completedTime != NULL)
				{
					$tableRow .= "<td class='$blankedClass'></td>";
				}
				else if ($note == "Missed")
				{
					$tableRow .= "<td class='$standardClass'><em>Missed</em><br/>";
					$tableRow .= generateDropDowns("noteU",$cId);
					$tableRow .= "</td>";
				}
				else
				{
					$tableRow .= "<td class='$standardClass'><p>$note</p></td>";
				}
				
				if ($locationType == 3)	//Sort into correct output table
				{
					$towersDTable .= $tableRow;
					$resultsTCounts++;
				}
				else
				{
					$printerTable .= $tableRow;
					$resultsPCounts++;
				}
				$tableRow = "";
			}
			$towersDTable .= "</table>\n";
			$printerTable .= "</table>\n";
			if ($resultsPCounts != 0)
			{
				echo $printerTable."<br/>";
			}
			if ($resultsTCounts != 0)
			{
				if ($displayMode != "LabCons")
				{
					echo $towersDTable."<br/>";
				}
			}
			if ($resultsTCounts == 0 && $resultsPCounts == 0)
			{
				$messages .= "RESULT:No checks found::";
			}
		}
		else
		{
			$error = $connection->error;
			$messages .= "ERROR: $error::";
		}
		echo"<input type='submit' name='action' value='Clear Values'/><input type='submit' name='action' value='Delete'/><input type='submit' name='action' value='Update'/>\n";
		echo "</form><br/>\n";
	}
	
	//Adds Custom Shift
	function addCustomCheckFunc()
	{
		global $displayMode;
		global $connection;
		global $messages;
		$locations = array();
		$employee = $_POST['employees'];
		$date = $_POST['appDate'];
		$time = $_POST['startTime'];
		$runInsert = TRUE;
		foreach ($_POST as $key => $value)
		{
			if ($value == "yes")
			{
				$locations[] = "$key";
			}
		}
		//Error Checking
		if (empty($locations))
		{
			$messages .= "ERROR:Please select at least one location for custom shift::";
			$runInsert = FALSE;
		}
		if ($employee == "IGNORE")
		{
			$messages .= "ERROR:Please select an employee for custom shift::";
			$runInsert = FALSE;
		}
		if (empty($date) || empty($time))
		{
			$messages .= "ERROR:Please enter a correct date for custom shift::";
			$runInsert = FALSE;
		}
		
		//Convert POSTs
		$formattedTime = strtotime($time);
		$start = date('H:i:s', $formattedTime);
		if ($displayMode == "LabCons")
		{
			$end = date('H:i:s', strtotime('+2 hours', $formattedTime));
		}
		else if ($displayMode == "TechCons")
		{
			$end= date('H:i:s', strtotime('+1 hours', $formattedTime));
		}
		else{}
		$startDT = $date." ".$start;
		$endDT = $date." ".$end;
	
		//Do inserts if all checks pass
		if ($runInsert == TRUE)
		{
			$total = 0;
			$success = 0;
			$stmt = $connection->prepare("INSERT INTO checks (locationCode, username, startTime, endTime) VALUES (?,?,?,?)");
			$stmt->bind_param('ssss',$location,$employee,$startDT,$endDT);
			for ($i=0; $i < count($locations); $i++)
			{
				$total++;
				$location = $locations[$i];
				$stmt->execute();
				$success++;
			}
			if ($total != 0 && $total == $success)
			{
				$messages .= "RESULT:All $success Locations in Custom Shift Added Successfully::";
			}
			else if ($total != 0 && $success != 0 && $total != $success)
			{
				$messages .= "ERROR:Only $success/$total Locations in Custom Shift Added Successfully::";
			}
			else if ($total != 0 && $success == 0 && $total != $success)
			{
				$messages .= "ERROR:All $total Locations in Custom Shift Failed::";
			}
			else {}
		}
	}
	
	//Parses Consultant XML info and use it as the "active employee set"
	function parseConsultantXML()
	{
		global $displayMode;
		global $connection;
		global $messages;
		if (!empty($_FILES))
		{
			$employees = simplexml_load_file($_FILES['xmlFile']['tmp_name']);
			$stmt = $connection->prepare("INSERT INTO employees (username, firstName, lastName, type, active) VALUES (?,?,?,?,?)");
			$active = 1;
			$stmt->bind_param('sssii',$employeeUserName,$employeeFirstName,$employeeLastName,$_POST['team'],$active);
			foreach ($employees as $employeeInfo)
			{
				$name = (string)$employeeInfo->item[0];
				if (!empty($name))
				{
					$nameparts = explode(", ",$name);
					$employeeLastName = $nameparts[0];
					$employeeFirstName = $nameparts[1];
					$employeeEmail = (string)$employeeInfo->item[1];
					$employeeUserName = strstr($employeeEmail, '@', true);
					$stmt->execute();
				}
			}
		}
	}
	
	//Parses Checks XML info and attempts to insert them into database
	function parseChecksXML()
	{
		global $displayMode;
		if (!empty($_FILES))
		{
			$checks = simplexml_load_file($_FILES['xmlFile']['tmp_name']);
			$userName = array();
			$date = array();
			$startTime = array();
			$location = array();
			if ($displayMode == "TechCons")
			{
				$endTime = array();
			}
			foreach ($checks as $checkInfo)
			{
				if ($displayMode == "LabCons")
				{
					$note = (string)$checkInfo->item[4];
					if (!empty($note))
					{
						$pos1 = strpos($note,"check:");
						$pos2 = strpos($note,"Get on the shuttle by ");
						if ($pos1 === false && $pos2 === false){}
						else
						{
							if ($pos1 !== false)
							{
								$locations = substr($note,strpos($note,": ")+2);
								while (strpos($locations,","))
								{
									$name = (string)$checkInfo->item[0];
									$userName[] = strstr($name, '@', true);
									$date[] = (string)$checkInfo->item[1];
									$startTime[] = ltrim(strchr($note," check: ",true),"At ");
									$location[] = substr($locations,0,strpos($locations,","));
									$locations = substr($locations,strpos($locations,",")+2);
								}
								$name = (string)$checkInfo->item[0];
								$userName[] = strstr($name, '@', true);
								$date[] = (string)$checkInfo->item[1];
								$startTime[] = ltrim(strchr($note," check: ",true),"At ");
								$location[] = substr($locations,0,-1);
							}
							else if ($pos2 !== false)
							{
								$name = (string)$checkInfo->item[0];
								$userName[] = strstr($name, '@', true);
								$date[] = (string)$checkInfo->item[1];
								$startTime[] = ltrim(strchr($note,",",true),"Get on the shuttle by ");
								$location[] = "2nd Ave";
							}
							else{}
						}
					}
				}
				else if ($displayMode == "TechCons")
				{
					$note = (string)$checkInfo->item[4];
					if (!empty($note))
					{
						if ($note == "Printer Checks")
						{
							$locations = (string)$checkInfo->item[5];
							while (strpos($locations,","))
							{
								$name = (string)$checkInfo->item[0];
								$userName[] = strstr($name, '@', true);
								$date[] = (string)$checkInfo->item[1];
								$startTime[] = (string)$checkInfo->item[2];
								$endTime[] = (string)$checkInfo->item[3];
								$location[] = substr($locations,0,strpos($locations,","));
								$locations = substr($locations,strpos($locations,",")+2);
							}
							$name = (string)$checkInfo->item[0];
							$userName[] = strstr($name, '@', true);
							$date[] = (string)$checkInfo->item[1];
							$startTime[] = (string)$checkInfo->item[2];
							$endTime[] = (string)$checkInfo->item[3];
							$location[] = substr($locations,0);
						}
						else if ($note == "Towers Lobby")
						{
							$name = (string)$checkInfo->item[0];
							$userName[] = strstr($name, '@', true);
							$date[] = (string)$checkInfo->item[1];
							$startTime[] = (string)$checkInfo->item[2];
							$endTime[] = (string)$checkInfo->item[3];
							$location[] = "Towers Lobby";
							$towersNote = (string)$checkInfo->item[5];
							if (!empty($towersNote))
							{
								$time = substr($towersNote,-4)." PM";
								$name = (string)$checkInfo->item[0];
								$userName[] = strstr($name, '@', true);
								$date[] = (string)$checkInfo->item[1];
								$startTime[] = $time;
								$endTime[] = $time;
								$location[] = "Towers Printers";
							}
						}
						else{}
					}
				}
				else
				{}
			}
			print_r($userName);
			echo "<br><br>";
			print_r($date);
			echo "<br><br>";
			print_r($startTime);
			echo "<br><br>";
			print_r($location);
			echo "<br><br>";
		}
	}
?>