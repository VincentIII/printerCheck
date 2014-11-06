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
	
	function consultantAdminForm()
	{
		echo "<h1>Add Consultant</h1>";
		echo "<h1>Edit Consultant</h1>";
		echo "<h1>Delete Consultant</h1>";
		echo "<h1>Import Consultant XML</h1>";
	}
	
	function printerAdminForm()
	{
		echo "<h1>Add Printer</h1>";
		echo "<h1>Edit Printer</h1>";
		echo "<h1>Delete Printer</h1>";
	}
?>