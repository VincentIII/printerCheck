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
					$query = "DELETE FROM checks WHERE cId=?' ";
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
	
	//Adds Multiple Shifts At Once
	function addMultipleChecksFunc()
	{
		global $shiftAddSuccess;
		global $shiftAddNum;
		global $messages;
		if ($_POST["shifts"] == 6)
		{
			if (!empty($_POST["employees5"]) && !empty($_POST["startTime5"]) && !empty($_POST["endTime5"]))
			{
				addCheckFunc($_POST["employees5"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime5"],$_POST["endTime5"]);
			}
			if (!empty($_POST["employees4"]) && !empty($_POST["startTime4"]) && !empty($_POST["endTime4"]))
			{
				addCheckFunc($_POST["employees4"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime4"],$_POST["endTime4"]);
			}
			if (!empty($_POST["employees3"]) && !empty($_POST["startTime3"]) && !empty($_POST["endTime3"]))
			{
				addCheckFunc($_POST["employees3"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime3"],$_POST["endTime3"]);
			}
			if (!empty($_POST["employees2"]) && !empty($_POST["startTime2"]) && !empty($_POST["endTime2"]))
			{
				addCheckFunc($_POST["employees2"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime2"],$_POST["endTime2"]);
			}		
			if (!empty($_POST["employees1"]) && !empty($_POST["startTime1"]) && !empty($_POST["endTime1"]))
			{
				addCheckFunc($_POST["employees1"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime1"],$_POST["endTime1"]);
			}	
		}
		else
		{
			if (!empty($_POST["employees4"]) && !empty($_POST["startTime4"]))
			{
				addCheckFunc($_POST["employees4"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime4"],NULL);
			}
			if (!empty($_POST["employees3"]) && !empty($_POST["startTime3"]))
			{
				addCheckFunc($_POST["employees3"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime3"],NULL);
			}
			if (!empty($_POST["employees2"]) && !empty($_POST["startTime2"]))
			{
				addCheckFunc($_POST["employees2"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime2"],NULL);
			}		
			if (!empty($_POST["employees1"]) && !empty($_POST["startTime1"]))
			{
				addCheckFunc($_POST["employees1"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime1"],NULL);
			}	
		}
		//Outputs Success of Adding Shifts
		if ($shiftAddNum != 0 && $shiftAddNum == $shiftAddSuccess)
		{
			$messages .= "RESULT:All Shifts Added Successfully::";
		}
		else if ($shiftAddNum != 0 && $shiftAddSuccess != 0 && $shiftAddNum != $shiftAddSuccess)
		{
			$messages .= "ERROR:Only $shiftAddSuccess Shifts Added Successfully::";
		}
		else if ($shiftAddNum != 0 && $shiftAddSuccess == 0 && $shiftAddNum != $shiftAddSuccess)
		{
			$messages .= "ERROR:All Shift Adding Failed::";
		}
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
	
	//Add Shift
	function addCheckFunc($employee,$shift,$date,$startTime,$endTime)
	{
		global $connection;
		global $shiftAddNum;
		global $shiftAddSuccess;
		global $messages;
		$shiftAddNum++;
		$runInsert = TRUE;
		//Error Checking
		if ($employee == "IGNORE")
		{
			$messages .= "ERROR:Please select an employee for shift $shiftAddNum::";
			$runInsert = FALSE;
		}
		if ($shift == 6 && $startTime >= $endTime)
		{
			$messages .= "ERROR:Please enter a correct Towers Shift end time for shift $shiftAddNum::";
			$runInsert = FALSE;
		}
		if (empty($date) || empty($startTime))
		{
			$messages .= "ERROR:Please enter a correct date for shift $shiftAddNum::";
			$runInsert = FALSE;
		}
		//Create List of Inserts based on Location, Time and Day
		$insertsNeeded = array();
		
		$day = date('D', strtotime($date));
		$selectionError = FALSE;
		
		//Location Selection Goes Here (Switch each Semester)
		if ($shift == 0)			//Alumni
		{
			if ($day == "Mon" || $day == "Tue" || $day == "Wed" || $day == "Thu" || $day == "Fri")
			{
				$insertsNeeded[] = "infosci-ss1";
				$insertsNeeded[] = "ohara-ss1";
			}
			else
			{
				$selectionError = TRUE;
			}
		}
		else if ($shift == 1)		//Benedum
		{
			if ($day == "Mon" || $day == "Tue" || $day == "Wed" || $day == "Thu" || $day == "Fri")
			{
				if ($startTime < "18:00")
				{
					$insertsNeeded[] = "parran-ss1";
					$insertsNeeded[] = "victoria-ss1";
					$insertsNeeded[] = "victoria-ss2";
					$insertsNeeded[] = "scaife-ss1";
					$insertsNeeded[] = "salk-ss1";
					$insertsNeeded[] = "salk-ss2";
				}
				else
				{
					$insertsNeeded[] = "victoria-ss1";
					$insertsNeeded[] = "victoria-ss2";
					$insertsNeeded[] = "scaife-ss1";
				}
			}
			else
			{
				$selectionError = TRUE;
			}
		}
		else if ($shift == 2)		//Hillman Lab
		{
			if ($day == "Mon" || $day == "Tue" || $day == "Wed" || $day == "Thu" || $day == "Fri")
			{
				if ($startTime < "18:00")
				{
					$insertsNeeded[] = "posvarsoe-ss7";
					$insertsNeeded[] = "phout-ss6";
					$insertsNeeded[] = "law-ss1";
					$insertsNeeded[] = "sensq-ss1";
					$insertsNeeded[] = "forbestower-ss1";
				}
				else
				{
					$insertsNeeded[] = "posvarsoe-ss7";
					$insertsNeeded[] = "phout-ss6";
					$insertsNeeded[] = "sensq-ss1";
				}
			}
			else if ($day == "Sat")
			{
				if ($startTime < "14:00")
				{
					$insertsNeeded[] = "phout-ss6";
					$insertsNeeded[] = "law-ss1";
					$insertsNeeded[] = "sensq-ss1";
					$insertsNeeded[] = "parran-ss1";
					$insertsNeeded[] = "scaife-ss1";
				}
				else
				{
					$insertsNeeded[] = "phout-ss6";
					$insertsNeeded[] = "law-ss1";
					$insertsNeeded[] = "sensq-ss1";
					$insertsNeeded[] = "scaife-ss1";
					$insertsNeeded[] = "infosci-ss1";
				}
			}
			else if ($day == "Sun")
			{
				if ($startTime < "17:00")
				{
					$insertsNeeded[] = "phout-ss6";
					$insertsNeeded[] = "sensq-ss1";
					$insertsNeeded[] = "parran-ss1";
					$insertsNeeded[] = "scaife-ss1";
				}
				else
				{
					$insertsNeeded[] = "phout-ss6";
					$insertsNeeded[] = "sensq-ss1";
					$insertsNeeded[] = "scaife-ss1";
				}
			}
			else
			{
				$selectionError = TRUE;
			}
		}
		else if ($shift == 3)		//WPU, Towers & Bruce
		{
			if ($day == "Mon" || $day == "Tue" || $day == "Wed" || $day == "Thu" || $day == "Fri")
			{
				$insertsNeeded[] = "wpu-ss1";
				$insertsNeeded[] = "towers-ss1";
				$insertsNeeded[] = "towers-ss2";
				$insertsNeeded[] = "towers-ss3";
				$insertsNeeded[] = "bruce-ss1";
			}
			else
			{
				$insertsNeeded[] = "towers-ss1";
				$insertsNeeded[] = "towers-ss2";
				$insertsNeeded[] = "towers-ss3";
				$insertsNeeded[] = "bruce-ss1";
			}
		}
		else
		{
			$selectionError = TRUE;
		}
		
		//Second round of error checking
		if ($selectionError == TRUE)
		{
			$messages .= "ERROR:Shift was not able to selected correct.  Please try another combination of time & location $shiftAddNum::";
			$runInsert = FALSE;
		}
		
		//Convert POSTs
		$formattedDate = strtotime($startTime);
		if ($shift < 3)
		{
			$cEnd = date('H:i:s', strtotime('+2 hours', $formattedDate));
		}
		else if ($shift >= 3 && $shift < 6)
		{
			$cEnd = date('H:i:s', strtotime('+1 hours', $formattedDate));
		}
		else
		{
			$cStart1 = date('H:i:s', strtotime('-15 minutes', $formattedDate));
			$cEnd1 = date('H:i:s', strtotime('+15 minutes', $formattedDate));
			$formattedEnd = strtotime($endTime);
			$cStart2 = date('H:i:s', strtotime('-15 minutes', $formattedEnd));
			$cEnd2 = date('H:i:s', strtotime('+15 minutes', $formattedEnd));
		}
		$cStart = date('H:i:s', $formattedDate);
		if ($shift == 6)
		{
			$cStartShift = array();
			$cEndShift = array();
			$cStartShift[] = $date." ".$cStart1;
			$cEndShift[] = $date." ".$cEnd1;
			$cStartShift[] = $date." ".$cStart2;
			$cEndShift[] = $date." ".$cEnd2;
		}
		else
		{
			$cStartShift = $date." ".$cStart;
			$cEndShift = $date." ".$cEnd;
		}
		//Do inserts if all checks pass
		if ($runInsert == TRUE)
		{
			$total = 0;
			$success = 0;
			$stmt = $connection->prepare("INSERT INTO checks (locationCode, username, startTime, endTime) VALUES (?,?,?,?)");
			$stmt->bind_param('ssss',$location,$employee,$startDT,$endDT);
			for ($i=0; $i < count($insertsNeeded); $i++)
			{
				$total++;
				$location = $insertsNeeded[$i];
				if ($shift == 6)
				{
					$startDT = $cStartShift[$i];
					$endDT = $cEndShift[$i];
				}
				else
				{
					$startDT = $cStartShift;
					$endDT = $cEndShift;
				}
				$stmt->execute();
				$success++;
			}
			if ($total != 0 && $total == $success)
			{
				$shiftAddSuccess++;
			}
			else if ($total != 0 && $success != 0 && $total != $success)
			{
				$messages .= "ERROR:Only $success/$total Locations in Shift $shiftAddNum Added Successfully::";
			}
			else if ($total != 0 && $success == 0 && $total != $success)
			{
				$messages .= "ERROR:All $total Locations in Shift $shiftAddNum Failed::";
			}
		}
	}	
	
?>