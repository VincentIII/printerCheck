<?php
	session_start();
	set_error_handler("messageError");
	// DEV NOTES---------------------------------------------------------------------------
	//	created by: 	Vincent Agresti
	//	program name:	Consultant Check Program
	//	short summary:	This program allows for administrators to monitor and document the printer checks and towers desk logins with ease.
	// GLOBALS-----------------------------------------------------------------------------
	$connection = new mysqli("localhost","root","","mydb");
	if ($connection->connect_error)
	{
		trigger_error('Database connection failed: '.$connection->connect_error,E_USER_ERROR);
	}
	if (date("I", time()) == TRUE)
	{
		$rightNow = date('Y-m-d H:i:s', time()-21600);	//21600 seconds - 6 hours (DST)
	}
	else
	{
		$rightNow = date('Y-m-d H:i:s', time()-18000);	//18000 seconds - 5 hours
	}
	$messages = "";
	$shiftAddNum = 0;
	$shiftAddSuccess = 0;
	$towersActive = FALSE;
	if (!empty($_SESSION["DMODE"]))
	{
		$displayMode = $_SESSION["DMODE"];
	}
	else
	{
		$displayMode = "AllCons";
	}
	
		//Verify GETs
	if (!empty($_GET["menu"]))
	{
		$displayType = $_GET["menu"];
	}
	else
	{
		$displayType = "";
	}
	if (!empty($_GET["display"]))
	{
		$displayVal = $_GET["display"];
	}
		//Verify POSTs
	if (!empty($_POST["action"]))
	{
		$fAction = $_POST["action"];
	}
	else
	{
		$fAction = "N/A";
	}
	if ($fAction != "Add Shift")
	{
		if (empty($_POST["startDate"]))
		{
			$_POST["startDate"] = "IGNORE";
		}
		if (empty($_POST["endDate"]))
		{
			$_POST["endDate"] = "IGNORE";
		}
	}

	// FUNCTIONS---------------------------------------------------------------------------
	//Display HTML Header
	function displayHead()
	{
		global $displayType;
		if ($displayType == "checks")
		{
			$title = "Browse Checks";
		}
		else if ($displayType == "add")
		{
			$title = "Add New Checks";
		}
		else if ($displayType == "stats")
		{
			$title = "Printer Check Stats";
		}
		else
		{
			$title = "Priority Checks";
		}
		echo "<!DOCTYPE html>
				<head>
					<meta http-equiv='Content-type' content='text/html;charset=UTF-8' />
					<title>Consultant Checks</title>
					<link rel='stylesheet' type='text/css' href='reset.css'/>
					<link rel='stylesheet' type='text/css' href='css.css'/>
					<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js'></script>";
	?>
		<script>
			$(document).ready(function(){
				$(".errorM").click(function(event){
					$(this).hide();
				});
				$(".resultM").click(function(event){
					$(this).hide();
				});
			});
			function clearThis(target){target.value= "";}
		</script>
	<?php
				echo"</head>
				<body>
				<header>$title</header>
					<div class='body'>\n";
	}
	
	//Displays HTML Footer
	function displayFooter()				
	{
		global $displayType;
		global $displayMode;
		echo "</div>
		<footer>
				<div class='footLinks'>
					<a href='checks.php'>[priority]</a> <a href='checks.php?menu=checks'>[checks]</a> <a href='checks.php?menu=add'>[add shifts]</a>
					</div>
					<div class='displayGroups'>";
		if ($displayMode == "LabCons")
		{
			echo "<a href=checks.php?display=AllCons&menu=$displayType>[All *Cons]</a> <em>[LabCons Only]</em> <a href=checks.php?display=TechCons&menu=$displayType>[TechCons Only]</a>\n";
		}
		else if ($displayMode == "TechCons")
		{
			echo "<a href=checks.php?display=AllCons&menu=$displayType>[All *Cons]</a> <a href=checks.php?display=LabCons&menu=$displayType>[LabCons Only]</a> <em>[TechCons Only]</em>\n";
		}
		else
		{
			echo "<em>[All *Cons]</em> <a href=checks.php?display=LabCons&menu=$displayType>[LabCons Only]</a> <a href=checks.php?display=TechCons&menu=$displayType>[TechCons Only]</a>\n";
		}
		echo "</div>
		</footer>
			</body>
			</html>";
	}
	
	//Custom Error Handler
	function messageError($errno,$errstr)
	{
		global $messages;
		$errorTrimmed = str_replace(':','-',$errstr);
		$messages .= "ERROR:[$errno] $errorTrimmed::";
	}
	
	//Displays Success & Error Messages
	function displayMessages()
	{
		global $messages;
		echo "<div class='messages'>";
		$continue = TRUE;
		while ($continue == TRUE)
		{
			$end = strpos($messages, "::");
			if ($end === FALSE)
			{
				$continue = FALSE;
			}
			else
			{
				$thisMessage = strstr($messages,"::",TRUE);
				$messages = strstr($messages,"::",FALSE);
				$messages = substr($messages,2);
				$type = strstr($thisMessage,":",TRUE);
				$message = strstr($thisMessage,":",FALSE);
				$message = substr($message,1);
				if ($type == "ERROR")
				{
					echo "<div class='errorM'>$message</div>";
				}
				else if ($type == "RESULT")
				{
					echo "<div class='resultM'>$message</div>";
				}
				else{}
			}
		}
		echo "</div>";
	}
	
	//Toggles Between All, LabCons and TechCons
	function displayModeToggle()
	{
		global $displayMode;
		global $displayVal;
		if (!empty($displayVal))
		{
			if ($displayVal == "LabCons")
			{
				$_SESSION["DMODE"] = "LabCons";
			}
			else if ($displayVal == "TechCons")
			{
				$_SESSION["DMODE"] = "TechCons";
			}
			else
			{
				$_SESSION["DMODE"] = "AllCons";
			}
			$displayMode = $_SESSION["DMODE"];
		}
	}
	
	//Creates Check Search Form
	function checksSearch()					
	{
		global $displayType;
		echo "<h1>Search</h1>\n
				<form action='checks.php?menu=$displayType' id='searchForm' method='post'>
				<table class='forms'>
				<tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",NULL);
		echo "</td></tr>\n<tr><td class='sideTH'>Location</td><td class='formOp'>";
		echo generateDropDowns("location",NULL);
		echo "</td></tr>\n<tr><td class='sideTH'>Timespan</td><td class='formOp'><input type='date' name='startDate'><input type='date' name='endDate'></td>";
		echo "</td></tr>\n<tr><td class='sideTH'>Note</td><td class='formOp'>";
		echo generateDropDowns("noteS",NULL);
		echo "</td></tr>\n<tr><td class='sideTH'>Active Employee</td><td class='formOp'>Yes <input type='radio' name='active' value='1' checked> No <input type='radio' name='active' value='0'></td></tr>\n<tr><td colspan='2' class='formOp'><input type='submit' name='action' value='Search'/></td></tr>
		</table></form>\n";
	}
	
	//Makes the drop-down form selection boxes for the Search Form
	function generateDropDowns($section,$id)	
	{
		global $displayMode;
		global $connection;
		if ($section == "location")
		{
			$name = "location";
			if ($displayMode == "LabCons")
			{
				$query = "SELECT locationCode AS code, locationName AS name FROM locations WHERE locationType = 1 AND active = 1 ORDER BY locationCode ASC ";
			}
			else if ($displayMode == "TechCons")
			{
				$query = "SELECT locationCode AS code, locationName AS name FROM locations WHERE locationType != 1  AND active = 1  ORDER BY locationCode ASC ";
			}
			else
			{
				$query = "SELECT locationCode AS code, locationName AS name FROM locations WHERE active = 1 ORDER BY locationCode ASC ";
			}
		}
		else if ($section == "employees")
		{
			$name = "employees".$id;
			if ($displayMode == "LabCons")
			{
				$query = "SELECT username AS code, CONCAT_WS(', ', lastName, firstName) AS name FROM employees WHERE type = 1 AND active = 1 ORDER BY lastName ASC ";
			}
			else if ($displayMode == "TechCons")
			{
				$query = "SELECT username AS code, CONCAT_WS(', ', lastName, firstName) AS name FROM employees WHERE type = 2 AND active = 1 ORDER BY lastName ASC ";
			}
			else
			{
				$query = "SELECT username AS code, CONCAT_WS(', ', lastName, firstName) AS name FROM employees WHERE active = 1 ORDER BY lastName ASC ";
			}
		}
		else if ($section == "shifts")
		{
			$name = "shifts";
			$query = NULL;
			if ($displayMode == "LabCons")
			{
				$sectArray = array(0=>"Alumni",1=>"Benedum",2=>"Hillman");
			}
			else if ($displayMode == "TechCons")
			{
				$sectArray = array(3=>"WPU, Towers & Bruce");
			}
			else
			{
				$sectArray = array(0=>"Alumni",1=>"Benedum",2=>"Hillman",3=>"WPU, Towers & Bruce");
			}
		}
		else if ($section == "status")
		{
			$name = "status";
			$query = NULL;
			$sectArray = array(0=>"All",1=>"Not Completed",2=>"Completed",3=>"Missed");
		}
		else if ($section == "noteS")
		{
			$name = "note";
			$query = NULL;
			$sectArray = array("All"=>"All","None"=>"None","Completed"=>"Completed","Missed"=>"Missed","Email Sent"=>"Email Sent","Called Off"=>"Called Off","Supervisor Excused"=>"Supervisor Excused","Single Coverage"=>"Single Coverage","Location Error"=>"Location Error","Program Error"=>"Program Error","Printer Error"=>"Printer Error","Other Notes"=>"Other Notes","Deletion"=>"Deletion");
		}
		else if ($section == "noteU")
		{
			$name = "note".$id;
			$query = NULL;
			$sectArray = array("IGNORE"=>"","Missed"=>"Missed","Email Sent"=>"Email Sent","Called Off"=>"Called Off","Supervisor Excused"=>"Supervisor Excused","Single Coverage"=>"Single Coverage","Location Error"=>"Location Error","Program Error"=>"Program Error","Printer Error"=>"Printer Error","Other..."=>"Other...","Deletion"=>"Deletion");
		}
		else{}
		
		$dropDownExport = "<select name='$name'>\n";
		if ($query != NULL)
		{
			if ($stmtDD = $connection->prepare($query))
			{
				$dropDownExport .= "<option value='IGNORE'>All</option>\n";
				$stmtDD->execute();
				$stmtDD->store_result();
				$stmtDD->bind_result($code,$name);
				while ($stmtDD->fetch())
				{
					if (!empty($_POST[$name]) && $code == $_POST[$name])
					{
						$dropDownExport .= "<option value='$code' selected>$name</option>\n";
					}
					else
					{
						$dropDownExport .= "<option value='$code'>$name</option>\n";
					}
				}
			}
		}
		else
		{
			foreach ($sectArray as $key => $value)
			{
				if (!empty($_POST[$name]) && $key == $_POST[$name])
				{
					$dropDownExport .= "<option value='$key' selected>$value</option>\n";
				}
				else
				{
					$dropDownExport .= "<option value='$key'>$value</option>\n";
				}
			}
		}
		$dropDownExport .= "</select><br/>\n";
		return $dropDownExport;
	}	
		
	//Updates the checks for basic-user changes (setting completion time & no-shows)
	function updateResults()
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
	function checksDisplay($dateStart,$dateEnd,$employee,$location,$note,$active)	
	{
		global $connection;
		global $displayType;
		global $displayMode;
		global $messages;
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
		echo "<h1>Results</h1>\n<form action='checks.php?menu=$displayType' id='updateCheck' method='post'>";
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
	
	//Creates Multiple Printer Shift Adding Form
	function addMPShiftForm()
	{
		global $displayMode;
		echo "<h1>Add Print Shifts</h1>\n
				<form action='checks.php?menu=add' id='addMPShiftForm' method='post'>
				<table class='forms'>
				<tr><td class='sideTH'>Shift</td><td class='formOp'>";
		echo generateDropDowns("shifts",NULL);
		echo "</td><td class='sideTH'>Date</td><td class='formOp'>";
		if (!empty($_POST['appDate']))
		{
			$oldFormVar = $_POST["appDate"];
			echo "<input type='date' name='appDate' value='$oldFormVar'>";
		}
		else
		{
			echo "<input type='date' name='appDate'>";
		}
		echo"<tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",1);
		echo "</td><td class='sideTH'>Time</td><td class='formOp'>";
		if (!empty($_POST['startTime1']) && $_POST['startTime1'] != "IGNORE")
		{
			$oldFormVar1 = $_POST["startTime1"];
			echo "<input type='time' name='startTime1' value='$oldFormVar1'>\n";
		}
		else
		{
			echo "<input type='time' name='startTime1'>\n";
		}		
		echo"</td></tr><tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",2);
		echo "</td><td class='sideTH'>Time</td><td class='formOp'>";
		if (!empty($_POST['startTime2']) && $_POST['startTime2'] != "IGNORE")
		{
			$oldFormVar2 = $_POST["startTime2"];
			echo "<input type='time' name='startTime2' value='$oldFormVar2'>\n";
		}
		else
		{
			echo "<input type='time' name='startTime2'>\n";
		}		
		echo"</td></tr><tr><td class='sideTH'>Employee</td><td class='formOp'>";		
		echo generateDropDowns("employees",3);
		echo "</td><td class='sideTH'>Time</td><td class='formOp'>";
		if (!empty($_POST['startTime3']) && $_POST['startTime3'] != "IGNORE")
		{
			$oldFormVar3 = $_POST["startTime3"];
			echo "<input type='time' name='startTime3' value='$oldFormVar3'>\n";
		}
		else
		{
			echo "<input type='time' name='startTime3'>\n";
		}		
		echo"</td></tr><tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",4);
		echo "</td><td class='sideTH'>Time</td><td class='formOp'>";
		if (!empty($_POST['startTime4']) && $_POST['startTime4'] != "IGNORE")
		{
			$oldFormVar4 = $_POST["startTime4"];
			echo "<input type='time' name='startTime4' value='$oldFormVar4'>\n";
		}
		else
		{
			echo "<input type='time' name='startTime4'>\n";
		}	
		echo "</td></tr><tr><td colspan='4' class='formOp'><input type='submit' name='action' value='Add Print Shifts'/></td></tr>
		</table>
		</form><br/><br/>";
	}
	
	//Creates Multiple Towers Shift Adding Form
	function addMTShiftForm()
	{
		global $displayMode;
		echo "<h1>Add Towers Shifts</h1>\n
				<form action='checks.php?menu=add' id='addMTShiftForm' method='post'>
				<table class='forms'>
				<tr><td class='sideTH' colspan='2'>Towers Only</td><td class='sideTH'>Date</td><td class='formOp'><input type='hidden' name='shifts' value='6'>";
		if (!empty($_POST['appDate']))
		{
			$oldFormVar = $_POST["appDate"];
			echo "<input type='date' name='appDate' value='$oldFormVar'>";
		}
		else
		{
			echo "<input type='date' name='appDate'>";
		}
		echo"<tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",1);
		echo "</td><td class='sideTH'>Time</td><td class='formOp'><input type='time' name='startTime1'><input type='time' name='endTime1'>\n</td></tr><tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",2);
		echo "</td><td class='sideTH'>Time</td><td class='formOp'><input type='time' name='startTime2'><input type='time' name='endTime2'>\n</td></tr><tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",3);
		echo "</td><td class='sideTH'>Time</td><td class='formOp'><input type='time' name='startTime3'><input type='time' name='endTime3'>\n</td></tr><tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",4);
		echo "</td><td class='sideTH'>Time</td><td class='formOp'><input type='time' name='startTime4'><input type='time' name='endTime4'>\n</td></tr><tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",5);
		echo "</td><td class='sideTH'>Time</td><td class='formOp'><input type='time' name='startTime5'><input type='time' name='endTime5'>\n</td></tr>";
		
		echo "</td></tr><tr><td colspan='4' class='formOp'><input type='submit' name='action' value='Add Towers Shifts'/></td></tr>
		</table>
		</form><br/><br/>";
	}
	
	//Creates Custom Shift Form (Good for Vacations/Non-Standard Shifts)
	function addCustomShiftForm()
	{
		global $displayMode;
		global $db_info;
		global $connection;
		echo "<h1>Add Custom Shift</h1>\n
				<form action='checks.php?menu=add' id='addCustomShiftForm' method='post'>
				<table class='forms'>
				<tr><td class='sideTH'>Employee</td><td class='formOp'>";
		echo generateDropDowns("employees",NULL);
		echo "</td><td class='sideTH'>Date/Time</td><td class='formOp'>";
		if (!empty($_POST['appDate']))
		{
			$oldFormVar = $_POST["appDate"];
			echo "<input type='date' name='appDate' value='$oldFormVar'>";
		}
		else
		{
			echo "<input type='date' name='appDate'>";
		}
		
		if (!empty($_POST['startTime']) && $_POST['startTime'] != "IGNORE")
		{
			$oldFormVar = $_POST["startTime"];
			echo "<input type='time' name='startTime' value='$oldFormVar'>\n";
		}
		else
		{
			echo "<input type='time' name='startTime'>\n";
		}		
		echo "<br/>\n</td></tr><tr><td class='sideTH' colspan='4'>Locations</td></tr><tr>";
		if ($displayMode == "LabCons")
		{
			$query = "SELECT locationCode, locationName FROM locations WHERE locationType = 1 AND active = 1 ORDER BY locationCode ASC ";
		}
		else if ($displayMode == "TechCons")
		{
			$query = "SELECT locationCode, locationName FROM locations WHERE locationType != 1 AND active = 1 ORDER BY locationCode ASC ";
		}
		else
		{
			$query = "SELECT locationCode, locationName FROM locations WHERE active = 1 ORDER BY locationCode ASC ";
		}
		$jumpDown=1;
		if ($stmt = $connection->prepare($query))
		{
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($code,$name);
			while ($stmt->fetch())
			{
				if ($name != "Towers Help Desk")
				{
					if (!empty($_POST[$code]))
					{
						echo "<td class='formOp'><input type='checkbox' name='$code' value='yes' id='$code' checked/><label for='$code'>$name</label></td>";
					}
					else
					{
						echo "<td class='formOp'><input type='checkbox' name='$code' value='yes' id='$code'/><label for='$code'>$name</label></td>";
					}
					if ($jumpDown == 4)
					{
						echo "</tr><tr>";
						$jumpDown = 0;
					}
					$jumpDown++;
				}
			}
		}
		while ($jumpDown != 5)
		{
			echo "<td class='formOp'></td>";
			$jumpDown++;
		}
		echo "</tr><tr><td colspan='4' class='formOp'><input type='submit' name='action' value='Add Custom Shift'/></td></tr>
		</table>
		</form><br/><br/>";
	}
	
	//Adds Multiple Shifts At Once
	function addMShift()
	{
		global $shiftAddSuccess;
		global $shiftAddNum;
		global $messages;
		if ($_POST["shifts"] == 6)
		{
			if (!empty($_POST["employees5"]) && !empty($_POST["startTime5"]) && !empty($_POST["endTime5"]))
			{
				addShift($_POST["employees5"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime5"],$_POST["endTime5"]);
			}
			if (!empty($_POST["employees4"]) && !empty($_POST["startTime4"]) && !empty($_POST["endTime4"]))
			{
				addShift($_POST["employees4"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime4"],$_POST["endTime4"]);
			}
			if (!empty($_POST["employees3"]) && !empty($_POST["startTime3"]) && !empty($_POST["endTime3"]))
			{
				addShift($_POST["employees3"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime3"],$_POST["endTime3"]);
			}
			if (!empty($_POST["employees2"]) && !empty($_POST["startTime2"]) && !empty($_POST["endTime2"]))
			{
				addShift($_POST["employees2"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime2"],$_POST["endTime2"]);
			}		
			if (!empty($_POST["employees1"]) && !empty($_POST["startTime1"]) && !empty($_POST["endTime1"]))
			{
				addShift($_POST["employees1"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime1"],$_POST["endTime1"]);
			}	
		}
		else
		{
			if (!empty($_POST["employees4"]) && !empty($_POST["startTime4"]))
			{
				addShift($_POST["employees4"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime4"],NULL);
			}
			if (!empty($_POST["employees3"]) && !empty($_POST["startTime3"]))
			{
				addShift($_POST["employees3"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime3"],NULL);
			}
			if (!empty($_POST["employees2"]) && !empty($_POST["startTime2"]))
			{
				addShift($_POST["employees2"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime2"],NULL);
			}		
			if (!empty($_POST["employees1"]) && !empty($_POST["startTime1"]))
			{
				addShift($_POST["employees1"],$_POST["shifts"],$_POST["appDate"],$_POST["startTime1"],NULL);
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
	function addCustomShift()
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
	function addShift($employee,$shift,$date,$startTime,$endTime)
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
	
	// PAGE RUN-----------------------------------------------------------------------------
	displayModeToggle();
	displayHead();
	if ($displayType == "checks")
	{
		checksSearch();
		if ($fAction == "Search")
		{
			checksDisplay($_POST["startDate"],$_POST["endDate"],$_POST["employees"],$_POST["location"],$_POST["note"],$_POST["active"]);
		}
		else if (!empty($fAction) && $fAction != "Search")
		{
			updateResults();
		}
		else{}
	}
	else if ($displayType == "add")
	{
		if ($fAction == "Add Print Shifts" || $fAction == "Add Towers Shifts")
		{
			addMShift();
		}
		else if ($fAction == "Add Custom Shift")
		{
			addCustomShift();
		}
		else{}
		if ($displayMode == "TechCons" || $displayMode == "LabCons")
		{
			addMPShiftForm();
			if ($displayMode == "TechCons" && $towersActive == TRUE)	
			{		
				addMTShiftForm();
			}
			addCustomShiftForm();
		}
		else
		{
			echo "<h1>Please Select a Con Type</h1><br/><a href=checks.php?display=LabCons&menu=add><strong>LabCons Only</strong></a><br/><a href=checks.php?display=TechCons&menu=add><strong>TechCons Only</strong></a>";
		}
	}
	else
	{
		if ($fAction == "Update")
		{
			updateResults();
		}
		checksDisplay("IGNORE",$rightNow,"IGNORE","IGNORE",NULL,1);
	}
	displayMessages();
	displayFooter();
?>