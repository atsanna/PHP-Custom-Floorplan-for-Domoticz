<?php 
require_once "secure/header.php"; 
require_once "secure/functions.php"; 
//require_once "scripts/chart.php";
if($authenticated == true) {
	
	$dbd = new SQLite3('/home/pi/domoticz/domoticz.db');
	if(isset($_POST['limit'])) { $limit = $_POST['limit']; } else { $limit = 30;}
	$sql = "SELECT date AS date, Total as mm FROM Rain_Calendar ORDER BY date DESC LIMIT 0,$limit";
	if(!$result = $dbd->query($sql)){ die('There was an error running the query [' . $dbd->error . ']');}
	while($row = $result->fetchArray()){$dagen[] = $row;}
	//$dagen = array_reverse($dagen);
	$sql = "SELECT substr(date,1,7) AS date, sum(Total) as mm, count(Total) as dagen FROM Rain_Calendar where Total > 0 GROUP BY substr(date,1,7) ORDER BY date DESC LIMIT 0,$limit";
	if(!$result = $dbd->query($sql)){ die('There was an error running the query [' . $dbd->error . ']');}
	while($row = $result->fetchArray()){$maanden[] = $row;}
	//$maanden = array_reverse($maanden);
	$sql = "SELECT substr(date,1,7) AS date, sum(Total) as mm, count(Total) as dagen FROM Rain_Calendar where Total > 0 GROUP BY substr(date,1,4) ORDER BY date DESC LIMIT 0,$limit";
	if(!$result = $dbd->query($sql)){ die('There was an error running the query [' . $dbd->error . ']');}
	while($row = $result->fetchArray()){$jaren[] = $row;}
	//$jaren = array_reverse($jaren);
	
	echo '<div class="threecolumn">
	<form method="post" name="filter" id="filter">
	<select name="limit" class="abutton settings gradient" onChange="this.form.submit()">';
	if(isset($_POST['limit'])) print '<option selected>'.$_POST['limit'].'</option>';
	print '<option>30</option>
	<option>50</option>
	<option>100</option>
	<option>500</option>
	<option>1000</option>
	<option>5000</option>
	<option>10000</option>
	<option>50000</option>
	<option>100000</option>
	</select>
	</form>
	<div class="isotope">
	<div class="item temprain gradient"><h2>Regen per dag</h2>';
	//$args = array('chart'=>'ColumnChart','width'=>295,'height'=>200,'hide_legend'=>true,'responsive'=>true,'background_color'=>'#E5E5E5','chart_div'=>'dagen','margins'=>array(10,5,5,20),);
	//$chart = array_to_chart(array_reverse($dagen),$args);
	//echo $chart['script'];
	//echo $chart['div'];
	echo '<table id="table" align="center"><thead><tr><th scope="col"></th><th scope="col">mm</th></tr></thead><tbody>';
	foreach($dagen as $dag) {
		echo '<tr>
		<td align="right" style="padding-right:10px">'.strftime("%a %e %b",strtotime($dag['date'])).'</td>
		<td align="right" style="padding-right:10px">'.$dag['mm'].' mm</td>
		</tr>';
	}
	echo "</tbody></table></div>";
	
	echo '<div class="item temprain gradient"><h2>Regen per maand</h2>';
	//$args = array('chart'=>'ColumnChart','width'=>295,'height'=>200,'hide_legend'=>true,'responsive'=>true,'background_color'=>'#E5E5E5','chart_div'=>'maanden','margins'=>array(10,5,5,20),'format_strings'=>array('mm'=>'fractionDigits: 0'));
	//$chart = array_to_chart(array_reverse($maanden),$args);
	//echo $chart['script'];
	//echo $chart['div'];
	echo '<table id="table_day" align="center"><thead><tr><th></th><th>mm</th><th>dagen</th></thead><tbody>';
	foreach($maanden as $maand) {
		echo '<tr>
		<td align="right" style="padding-right:10px">'.strftime("%B %Y",strtotime($maand['date'])).'</td>
		<td align="right" style="padding-right:10px">'.round($maand['mm'],2).' mm</td>
		<td align="right" style="padding-right:10px">'.$maand['dagen'].'</td>
		</tr>';
	}
	echo "</tbody></table></div>";
	
	echo '<div class="item temprain gradient"><h2>Regen per jaar</h2>';
	//$args = array('chart'=>'ColumnChart','width'=>295,'height'=>200,'hide_legend'=>true,'responsive'=>true,'background_color'=>'#E5E5E5','chart_div'=>'jaren','margins'=>array(10,5,5,20),'format_strings'=>array('mm'=>'fractionDigits: 0'));
	//$chart = array_to_chart(array_reverse($jaren),$args);
	//echo $chart['script'];
	//echo $chart['div'];
	echo '<table id="table_day" align="center"><thead><tr><th></th><th>mm</th><th>dagen</th></thead><tbody>';
	foreach($jaren as $jaar) {
		echo '<tr>
		<td align="right" style="padding-right:10px">'.strftime("%Y",strtotime($jaar['date'])).'</td>
		<td align="right" style="padding-right:10px">'.round($jaar['mm'],2).' mm</td>
		<td align="right" style="padding-right:10px">'.$jaar['dagen'].'</td>
		</tr>';
	}
	echo "</tbody></table></div></div></div>";
}