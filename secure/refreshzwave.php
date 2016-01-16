#!/usr/bin/php
<?php 
include 'functions.php';
$devices=json_decode(file_get_contents($domoticzurl.'json.htm?type=openzwavenodes&idx='.$zwaveidx),true); //Change IDX to IDX of your zwave controller
function RefreshZwave2($node,$name) {
	global $domoticzurl;
	$zwaveurl=$domoticzurl.'ozwcp/refreshpost.html';
	$zwavedata=array('fun'=>'racp','node'=>$node);
	$zwaveoptions = array('http'=>array('header'=>'Content-Type: application/x-www-form-urlencoded\r\n','method'=>'POST','content'=>http_build_query($zwavedata),),);
	$zwavecontext=stream_context_create($zwaveoptions);
	for ($k=1;$k<=5;$k++){
		sleep(1);
		$result=file_get_contents($zwaveurl,false,$zwavecontext);
		logwrite('RefreshZwave node '.$node.' '.$name.' '.$result);
		if($result=='OK') break;
		sleep(1);
	}
}
if(!empty($argv[1])&&!empty($argv[2])) {
	RefreshZwave2($argv[1],$argv[2]);
	$mc->set('RefreshZwave'.$argv[1], $time);
}
else {
	logwrite("RefreshZwave: no idx or name defined");
}
sleep(3);
if($mc->get('deadnodes')<$tienmin) {
	foreach($devices as $node=>$data) {
		if ($node == "result") {
		foreach($data as $index=>$eltsNode) {
		  if ($eltsNode["State"] == "Dead") {
			  $reply=json_decode(file_get_contents($domoticzurl.'json.htm?type=command&param=zwavenodeheal&idx='.$zwaveidx.'&node='.$eltsNode['NodeID']),true);
			  logwrite('Node '.$eltsNode['NodeID'].' '.$eltsNode['Description'].' ('.$eltsNode['Name'].') marked as dead, healing command = '.$reply['status']);
			  telegram('Node '.$eltsNode['NodeID'].' '.$eltsNode['Description'].' ('.$eltsNode['Name'].') marked as dead, healing command = '.$reply['status'].'.Errors='.$preverrors);
//			  $errors=$errors+1;
		  }
		}   
	 }
	}
	$mc->set('deadnodes',$time);
}
//$totalerrors=$preverrors+$errors;if($totalerrors!=$preverrors) $mc->set('errors',$totalerrors);
