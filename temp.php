<?php 
require_once "secure/header.php"; 
require_once "secure/functions.php"; 
require_once "scripts/chart.php";
if($authenticated == true) {
	
	$dbd = new SQLite3('/home/pi/domoticz/domoticz.db');
	if(isset($_POST['sensor'])) { $sensor = $_POST['sensor']; $sensornaam = $_POST['naam'];} 
	echo '<div class="isotope">';
	switch($sensor) {
		case 37:
			$setpoint = 138;
			break;
		default:
			$setpoint = 0;
			break;
	}
	if($setpoint>0) {
		$sql = "SELECT Date, Temperature FROM Temperature WHERE DeviceRowID = $sensor ORDER BY Date DESC";
		if(!$result = $dbd->query($sql)){ echo('There was an error running the query [' . $dbd->error . ']');}
		$times = array();
		while($row = $result->fetchArray()){
			array_push($times, array('Date' => $row['Date'], 'Temperature' => $row['Temperature']));
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
		
		//GRAPHS
		//Nieuwe array voor times om vochtigheid eruit te halen.
		//foreach ($timeschart as $i => $item) {
 		//   foreach ($item as $key => $value) {
    	//    if (!in_array($key,array('Date','Temperature'))) { unset($timeschart[$i][$key]); }
    	//	}
		//}
	echo '<div class="item temprain gradient" style="min-width:315px"><h2>'.$_POST['naam'].'</h2>';
	$args = array('chart'=>'AreaChart','width'=>465,'height'=>300,'hide_legend'=>true,'responsive'=>true,'background_color'=>'#E5E5E5','chart_div'=>'times','margins'=>array(30,10,15,35),);
	$chart = array_to_chart($timeschart,$args);
	echo $chart['script'];
	echo $chart['div'];
	echo "</div>";

	echo "<div class='item temprain gradient' style='min-width:315px'><h2>Laatste 30 dagen</h2>";
	$args = array('chart'=>'AreaChart','width'=>465,'height'=>300,'hide_legend'=>true,'responsive'=>true,'background_color'=>'#E5E5E5','chart_div'=>'dagen','margins'=>array(30,10,15,35),);
	$chart = array_to_chart($dagen,$args);
	echo $chart['script'];
	echo $chart['div'];
	echo "</div>";
	
	echo "<div class='item temprain gradient' style='min-width:315px'><h2>Per maanden</h2>";
	$args = array('chart'=>'AreaChart','width'=>465,'height'=>300,'hide_legend'=>true,'responsive'=>true,'background_color'=>'#E5E5E5','chart_div'=>'maanden','margins'=>array(30,10,15,35),);
	$chart = array_to_chart($maanden,$args);
	echo $chart['script'];
	echo $chart['div'];
}
echo "</div></div></div>";
require_once "secure/footer.php";