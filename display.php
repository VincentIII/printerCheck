<?php
	//Creates Check Search Form
	function checkSearchForm()					
	{
		global $displayType;
		global $pageName;
		echo "<h1>Search</h1>\n
				<form action='$pageName?menu=$displayType' id='searchForm' method='post'>
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
	
	//Creates Multiple Printer Shift Adding Form
	function addPrinterCheckForm()
	{
		global $displayMode;
		global $pageName;
		echo "<h1>Add Print Shifts</h1>\n
				<form action='$pageName?menu=add' id='addMPShiftForm' method='post'>
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
	function addTowerCheckForm()
	{
		global $displayMode;
		global $pageName;
		echo "<h1>Add Towers Shifts</h1>\n
				<form action='$pageName?menu=add' id='addMTShiftForm' method='post'>
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
	function addCustomCheckForm()
	{
		global $displayMode;
		global $db_info;
		global $connection;
		global $pageName;
		echo "<h1>Add Custom Shift</h1>\n
				<form action='$pageName?menu=add' id='addCustomShiftForm' method='post'>
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
	
?>