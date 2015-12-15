<?php 
$dbd = new SQLite3('/home/pi/domoticz/domoticz.db');
$tables = array('LightingLog','Meter','Meter_Calendar','MultiMeter','MultiMeter_Calendar','Temperature','Temperature_Calendar','Percentage','Percentage_Calendar');
foreach($tables as $table) {
	$sql = 'delete FROM '.$table.' WHERE "DeviceRowID" not in (select ID from DeviceStatus where Used = 1)';
	if(!$result = $dbd->exec($sql)){ die('There was an error running the query [' . $db->error . ']');}
	echo $table.' : '. $dbd->changes().'<br>';
}