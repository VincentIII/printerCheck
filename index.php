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
	$pageName = "index.php";
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
		global $pageName;
		echo "</div>
		<footer>
				<div class='footLinks'>
					<a href='$pageName'>[priority]</a> <a href='$pageName?menu=checks'>[checks]</a> <a href='$pageName?menu=add'>[add shifts]</a> <a href='$pageName?menu=admin'>[admin]</a>
					</div>
					<div class='displayGroups'>";
		if ($displayMode == "LabCons")
		{
			echo "<a href=$pageName?display=AllCons&menu=$displayType>[All *Cons]</a> <em>[LabCons Only]</em> <a href=$pageName?display=TechCons&menu=$displayType>[TechCons Only]</a>\n";
		}
		else if ($displayMode == "TechCons")
		{
			echo "<a href=$pageName?display=AllCons&menu=$displayType>[All *Cons]</a> <a href=$pageName?display=LabCons&menu=$displayType>[LabCons Only]</a> <em>[TechCons Only]</em>\n";
		}
		else
		{
			echo "<em>[All *Cons]</em> <a href=$pageName?display=LabCons&menu=$displayType>[LabCons Only]</a> <a href=$pageName?display=TechCons&menu=$displayType>[TechCons Only]</a>\n";
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

	// INCLUDES-----------------------------------------------------------------------------
	require 'display.php';		//Display Functions
	require 'action.php';		//Form-Action Functions
	
	// PAGE RUN-----------------------------------------------------------------------------
	displayModeToggle();
	displayHead();
	if ($displayType == "checks")
	{
		checkSearchForm();
		if ($fAction == "Search")
		{
			checkSearchFunc($_POST["startDate"],$_POST["endDate"],$_POST["employees"],$_POST["location"],$_POST["note"],$_POST["active"]);
		}
		else if (!empty($fAction) && $fAction != "Search")
		{
			updateChecksFunc();
		}
		else{}
	}
	else if ($displayType == "add")
	{
		if ($fAction == "Add Custom Shift")
		{
			addCustomCheckFunc();
		}
		else if ($fAction == "Upload Checks XML")
		{
			parseChecksXML();
		}
		else{}
		if ($displayMode == "TechCons" || $displayMode == "LabCons")
		{
			addXMLChecksForm();
			addCustomCheckForm();
		}
		else
		{
			echo "<h1>Please Select a Con Type</h1><br/><a href=$pageName?display=LabCons&menu=add><strong>LabCons Only</strong></a><br/><a href=$pageName?display=TechCons&menu=add><strong>TechCons Only</strong></a>";
		}
	}
	else if ($displayType == "admin")
	{
		if ($fAction == "Upload Consultant XML")
		{
			parseConsultantXML();
		}
		consultantAdminForm();
		//printerAdminForm();
	}
	else
	{
		if ($fAction == "Update")
		{
			updateChecksFunc();
		}
		checkSearchFunc("IGNORE",$rightNow,"IGNORE","IGNORE",NULL,1);
	}
	displayMessages();
	displayFooter();
?>