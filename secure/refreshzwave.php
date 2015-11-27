<?php 
file_get_contents('http://192.168.0.8:1602/json.htm?type=openzwavenodes&idx=5');
function RefreshZwave($node) {
	$zwaveurl='http://127.0.0.1:1602/ozwcp/refreshpost.html';
	$zwavedata=array('fun'=>'racp','node'=>$node);
	$zwaveoptions = array('http'=>array('header'=>'Content-Type: application/x-www-form-urlencoded\r\n','method'=>'POST','content'=>http_build_query($zwavedata),),);
	$zwavecontext=stream_context_create($zwaveoptions);
	for ($k=1;$k<=5;$k++){sleep(2);$result=file_get_contents($zwaveurl,false,$zwavecontext);if($result=='OK') break;}
}
RefreshZwave(16);//garageterras
RefreshZwave(20);//HallZolder
RefreshZwave(23);//InkomVoordeur
RefreshZwave(24);//BureelTobi
RefreshZwave(53);//Pluto
//RefreshZwave(56);//KeukenZolder
RefreshZwave(60);//DimmerTobi
RefreshZwave(61);//Media Greenwave blok
RefreshZwave(65);//Licht Badkamer
