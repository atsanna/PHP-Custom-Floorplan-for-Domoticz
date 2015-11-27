<?php 
require_once "secure/header.php"; 
require_once "secure/functions.php"; 
require_once "scripts/chart.php";
if($authenticated == true) {
	
	$dbd = new SQLite3('/home/pi/domoticz/domoticz.db');
	if(isset($_POST['sensor'])) { $sensor = $_POST['sensor']; $sensornaam = $_POST['naam'];} 
	else {$sensor = 38; $sensornaam = 'Living';}
	echo '<div class="isotope">';
	switch($sensor) {
		case 329:$setpoint = 549;break;//Slaapkamer Julius
		case 541:$setpoint = 548;break;//Slaapkamer Tobi
		case 543:$setpoint = 130;break;//Living
		case 566:$setpoint = 111;break;//Badkamer
		case 582:$setpoint = 97;break;//Kamer
		default:$setpoint = 0;break;
	}
	if($setpoint>0) {
		$sql = "
			SELECT t.Date, t.Temperature as Temperature, s.Temperature as Setpoint 
			FROM Temperature t 
			JOIN Temperature s ON t.Date = s.Date
			WHERE t.DeviceRowID = $sensor AND s.DeviceRowID = $setpoint
			ORDER BY t.Date DESC";
		if(!$result = $dbd->query($sql)){ echo('There was an error running the query [' . $dbd->error . ']');}
		$times = array();
		while($row = $result->fetchArray()){
			array_push($times, array('Date' => $row['Date'], 'Temperature' => $row['Temperature'], 'Setpoint' => $row['Setpoint']));
		}
	} else {
		$sql = "SELECT Date, Temperature FROM Temperature WHERE DeviceRowID = $sensor ORDER BY Date DESC";
		if(!$result = $dbd->query($sql)){ echo('There was an error running the query [' . $dbd->error . ']');}
		$times = array();
		while($row = $result->fetchArray()){
			array_push($times, array('Date' => $row['Date'], 'Temperature' => $row['Temperature']));
		}
	}
	$timeschart = array_reverse($times);
	$sql = "SELECT Date, Temp_Min, Temp_Max FROM Temperature_Calendar WHERE DeviceRowID = $sensor ORDER BY Date DESC LIMIT 0,30";
	if(!$result = $dbd->query($sql)){ echo('There was an error running the query [' . $dbd->error . ']');}
	$dagen = array();
	while($row = $result->fetchArray()){
		array_push($dagen, array('Date' => $row['Date'], 'min' => $row['Temp_Min'], 'max' => $row['Temp_Max']));
	}
	$dagen = array_reverse($dagen);
	$sql = "SELECT substr(Date,0,8) AS Date, min(Temp_Min) as min, max(Temp_Max) as max FROM Temperature_Calendar WHERE DeviceRowID = $sensor GROUP BY substr(Date,0,8) ORDER BY Date DESC LIMIT 0,48";
	if(!$result = $dbd->query($sql)){ echo('There was an error running the query [' . $dbd->error . ']');}
	$maanden = array();
	while($row = $result->fetchArray()){
		array_push($maanden, array('Date' => $row['Date'], 'min' => $row['min'], 'max' => $row['max']));
	}
	$maanden = array_reverse($maanden);
		
	echo '<div class="item temprain gradient" style="min-width:315px"><h2>'.$sensor.' - '.$sensornaam.'</h2>';
	$args = array('chart'=>'AreaChart','width'=>464,'height'=>650,'hide_legend'=>false,'responsive'=>true,'background_color'=>'#E5E5E5','chart_div'=>'times','margins'=>array(30,10,15,35),);
	$chart = array_to_chart($timeschart,$args);
	echo $chart['script'];
	echo $chart['div'];
	echo "</div>";

	echo "<div class='item temprain gradient' style='min-width:315px'><h2>Laatste 30 dagen</h2>";
	$args = array('chart'=>'AreaChart','width'=>464,'height'=>650,'hide_legend'=>true,'responsive'=>true,'background_color'=>'#E5E5E5','chart_div'=>'dagen','margins'=>array(30,10,15,35),);
	$chart = array_to_chart($dagen,$args);
	echo $chart['script'];
	echo $chart['div'];
	echo "</div>";
	
	echo "<div class='item temprain gradient' style='min-width:315px'><h2>Per maanden</h2>";
	$args = array('chart'=>'AreaChart','width'=>464,'height'=>650,'hide_legend'=>true,'responsive'=>true,'background_color'=>'#E5E5E5','chart_div'=>'maanden','margins'=>array(30,10,15,35),);
	$chart = array_to_chart($maanden,$args);
	echo $chart['script'];
	echo $chart['div'];
}
echo "</div></div></div>";
require_once "secure/footer.php";