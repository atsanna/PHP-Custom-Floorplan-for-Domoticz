#!/usr/bin/php
<?php 
include 'functions.php';
$devices=file_get_contents('http://127.0.0.1:1602/json.htm?type=openzwavenodes&idx=5'); //Change IDX to IDX of your zwave controller
function RefreshZwave2($node,$name) {
   $zwaveurl='http://127.0.0.1:1602/ozwcp/refreshpost.html';
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
   }
else {
   logwrite("RefreshZwave: no idx or name defined");
   }
