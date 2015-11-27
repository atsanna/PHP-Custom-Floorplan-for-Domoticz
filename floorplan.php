<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml">
<head><title>Floorplan</title>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<meta name="HandheldFriendly" content="true" /><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="viewport" content="width=device-width,height=device-height,user-scalable=yes,minimal-ui" />
<link rel="icon" type="image/png" href="images/domoticzphp48.png">
<link rel="shortcut icon" href="images/domoticzphp48.png" /><link rel="apple-touch-startup-image" href="images/domoticzphp450.png">
<link rel="apple-touch-icon" href="images/domoticzphp48.png" />
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="images/domoticzphp48.png">
<meta name="msapplication-config" content="browserconfig.xml">
<meta name="mobile-web-app-capable" content="yes"><link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#ffffff">
<link href="floorplan.css" rel="stylesheet" type="text/css" />
</head><body>
<?php $start=microtime(true);$time=$_SERVER['REQUEST_TIME'];$offline=$time-300;$eendag=$time-82800;include "secure/functions.php";
if($authenticated){
if(isset($_POST['Schakel'])){if(Schakel($_POST['Schakel'],$_POST['Actie'])=='ERROR') echo '<div id="message" class="balloon">'.$_POST['Naam'].' '.$_POST['Actie'].'<br/>ERROR</div>';if($_POST['Schakel']==572){xcache_set('Heating',2);}}
else if(isset($_POST['Setpoint'])){foreach($_POST as $key=>$value){$value=round($value,0);if(substr($key,0,5)=="Actie"){if(Udevice($_POST['Setpoint'],0,$value)=='ERROR') echo '<div id="message" class="balloon">'.$_POST['Naam'].' '.$value.'<br/>ERROR</div>';else xcache_set('setpoint'.$_POST['Setpoint'],2);}}}
else if(isset($_POST['Udevice'])){if(Udevice($_POST['Udevice'])=='ERROR') echo '<div id="message" class="balloon">'.$_POST['Naam'].' '.$_POST['Actie'].'<br/>ERROR</div>'; }
else if(isset($_POST['dimmer'])){
	if(isset($_POST['dimlevelon_x'])) {if(Dim($_POST['dimmer'],100)=='ERROR') echo '<div id="message" class="balloon">Dimmer '.$_POST['Naam'].' level '.$_POST['dimlevel'].'<br/>ERROR</div>';xcache_set('dimtime'.$_POST['Naam'],$time);xcache_set('dimsleep'.$_POST['Naam'],0);}
	else if(isset($_POST['dimleveloff_x'])) {if(Dim($_POST['dimmer'],0)=='ERROR') echo '<div id="message" class="balloon">Dimmer '.$_POST['Naam'].' level '.$_POST['dimlevel'].'<br/>ERROR</div>';xcache_set('dimtime'.$_POST['Naam'],$time);xcache_set('dimsleep'.$_POST['Naam'],0);}
	else if(isset($_POST['dimsleep_x'])) {xcache_set('dimsleep'.$_POST['Naam'],$_POST['dimlevel']);}
	else {if(Dim($_POST['dimmer'],$_POST['dimlevel'])=='ERROR') echo '<div id="message" class="balloon">Dimmer '.$_POST['Naam'].' level '.$_POST['dimlevel'].'<br/>ERROR</div>';xcache_set('dimtime'.$_POST['Naam'],$time);xcache_set('dimsleep'.$_POST['Naam'],0);}
	}
else if(isset($_POST['Scene'])){if(Scene($_POST['Scene'])=='ERROR') echo '<div id="message" class="balloon">Scene '.$_POST['Naam'].' activeren'.'<br/>ERROR</div>';if($_POST['Scene']==5) xcache_set('dimtimeEettafel',$time);}
if(isset($_POST['denon'])){$denon_address='http://192.168.0.2';$ctx=stream_context_create(array('http'=>array('timeout'=>2,)));
	$denonmain=simplexml_load_string(file_get_contents($denon_address.'/goform/formMainZone_MainZoneXml.xml?_='.$time,false,$ctx));
	$denonmain=json_encode($denonmain);$denonmain=json_decode($denonmain,TRUE);usleep(10000);
	if($denonmain){
		$denonmain['MasterVolume']['value']=='--'?$setvalue=-80:$setvalue=$denonmain['MasterVolume']['value'];
		$_POST['denon']=='up'?$setvalue=$setvalue+3:$setvalue=$setvalue-3;
		if($setvalue>-10) $setvalue=-10;if($setvalue<-80) $setvalue=-80;
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutMasterVolumeSet/'.$setvalue.'.0');}}
$domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&used=true&plan=2'),true);$domotime=microtime(true)-$start;
if($domoticz){
	foreach($domoticz['result'] as $dom) {
		(isset($dom['Type'])?$Type=$dom['Type']:$Type='None');
		(isset($dom['SwitchType'])?$SwitchType=$dom['SwitchType']:$SwitchType='None');
		(isset($dom['SubType'])?$SubType=$dom['SubType']:$SubType='None');
		$name=$dom['Name'];
		if($Type=='Temp + Humidity'||$Type=='Temp'){${'T'.$name}=$dom['Temp'];${'TI'.$name}=$dom['idx'];${'TT'.$name}=strtotime($dom['LastUpdate']);}
		else if($SwitchType=='Dimmer'){${'DI'.$name}=$dom['idx'];$dom['Status']=='Off'?${'D'.$name}='Off':${'D'.$name}='On';$dom['Status']=='Off'?${'Dlevel'.$name}=0:${'Dlevel'.$name}=$dom['Level'];${'DT'.$name}=strtotime($dom['LastUpdate']);}
		else if($Type=='Usage'&&$dom['SubType']=='Electric') ${'P'.$name}=substr($dom['Data'],0,-5);
		else if($Type=='Radiator 1'||$Type=='Thermostat') {${'RI'.$name}=$dom['idx'];${'R'.$name}=$dom['Data'];${'RT'.$name}=strtotime($dom['LastUpdate']);}
		else {
			if(substr($dom['Data'],0,2)=='On') ${'S'.$name}='On';
			else if(substr($dom['Data'],0,3)=='Off') ${'S'.$name}='Off';
			else if(substr($dom['Data'],0,4)=='Open') ${'S'.$name}='Open';
			else ${'S'.$name}=$dom['Data'];${'SI'.$name}=$dom['idx'];${'ST'.$name}=strtotime($dom['LastUpdate']);${'SB'.$name}=$dom['BatteryLevel'];}
	}
	unset($domoticz,$dom);
if(isset($_POST['Schakel'])){
	if($_POST['Schakel']==6||$_POST['Schakel']==48){
		if($SRaamLiving=='Open') echo '<script language="javascript">alert("WARNING:Raam living open!")</script>';
		if($SAchterdeur=='Open') echo '<script language="javascript">alert("WARNING:Achterdeur open!")</script>';
		if($Spoort=='Open') echo '<script language="javascript">alert("WARNING:Poort open!")</script>';}}
echo '<div style="position:absolute;top:5px;left:278px;width:130px;text-align:right;"><a href="" style="padding:4px 4px;font-size:33px;font-weight:500;text-align:right;letter-spacing:-2px" title="refresh">'.strftime("%k:%M:%S",$time).'</a></div>
<div class="box" style="top:0px;height:306px;" >
<div class="box2" style="top:240px;left:11px;" ><img src="images/'.xcache_get('weatherimg').'.png"/ width="60px" height="auto"></div>
<div class="box2" style="top:290px;left:13px;background:rgba(222,222,222,0.8);" >'.xcache_get('averagerain').'</div>
<div class="box" style="top:319px;height:505px;">
<form method="POST"><input type="hidden" name="denon" value="up"><input type="image" src="images/arrowup.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="denon" value="down"><input type="image" src="images/arrowdown.png" width="48px" height="48px"></form>
<br/><form action="denon.php" method="POST"><input type="image" src="images/denon.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="2"><input type="hidden" name="Naam" value="Radio luisteren"><input type="image" src="images/Amp_On.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="3"><input type="hidden" name="Naam" value="TV Kijken"><input type="image" src="images/TV_On.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="4"><input type="hidden" name="Naam" value="Kodi kijken"><input type="image" src="images/kodi.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="5"><input type="hidden" name="Naam" value="Eten"><input type="image" src="images/eten.png" width="48px" height="48px"></form>
</div>';
Dimmer('Zithoek',48,125,130);
Dimmer('Eettafel',48,125,270);
Schakelaar('TV','TV',48,44,88);	
Schakelaar('Denon','Amp',48,44,144);
Schakelaar('Kodi','Kodi',36,50,196);	
Schakelaar('iMac','imac',40,50,235);	
Schakelaar('DiskStation','nas',16,28,267);	
Schakelaar('Kristal','Light',36,8,98);
Schakelaar('TVLed','Light',36,8,158);
Schakelaar('Lamp_Bureel','Light',36,8,214);
Schakelaar('Licht_Inkom','Light',40,60,360);
Schakelaar('Keuken','Light',48,157,390);
//Schakelaar('Keuken','Light',30,150,340); //gootsteen
//Schakelaar('Tuin','Light',30,115,375); //fornuis
//Schakelaar('Werkblad','Light',30,216,420); //werkblad
Schakelaar('LichtBadkamer1','Light',40,440,368); //badkamer
Schakelaar('LichtBadkamer2','Light',20,480,348); //badkamer
//Schakelaar('Keuken','Light',40,550,350); //kamer
Schakelaar('Tobi','Light',40,420,131); //kamer Tobi
//Schakelaar('Keuken','Light',40,575,170); //kamer Julius
Schakelaar('Licht_Voordeur','Light',40,60,441);
Schakelaar('Licht_Hall','Light',42,416,252);	
Schakelaar('Licht_Hall_Auto','Clock',36,420,299);		
Schakelaar('Licht_Garage','Light',48,305,209);	
Schakelaar('ZolderG','Light',30,315,140);	
Schakelaar('Licht_Garage_Auto','Clock',36,312,299);	
Schakelaar('Pluto','Laptop',25,370,217);	
Schakelaar('Thuis','Home',48,268,428);	
Schakelaar('Slapen','Sleepy',48,335,428);	
Schakelaar('Meldingen','Alarm',48,5,16);	
Schakelaar('Terras','Light',48,77,15);
Schakelaar('Brander','Fire',48,765,260);
Schakelaar('Licht_Zolder','Light',48,705,260);
Schakelaar('Bureel_Tobi','Plug',36,780,380);
Schakelaar('Heating','Fire',48,778,16);
Thermometer('Buiten',110,140,17);
Thermometer('Living',70,178,232);
Thermometer('Badkamer',70,432,447);
Thermometer('Kamer',65,578,447);
Thermometer('KamerTobi',65,470,137);
Thermometer('KamerJulius',65,578,137);
Thermometer('Zolder',70,707,135);
Setpoint('Living',40,189,190);
Setpoint('Badkamer',40,443,405);
Setpoint('Kamer',40,586,405);
Setpoint('KamerTobi',40,479,95);
Setpoint('KamerJulius',40,586,95);
Radiator('LivingZZ',-90,221,77);
Radiator('BadkamerZ',0,403,349);
Radiator('KamerTobiZ',-90,463,77); //tobi
Radiator('KamerJuliusZ',-90,583,77); //julius
Radiator('KamerZ',90,542,456); //kamer

if($SThuis=='Off'||$SSlapen=='On'){Secured(52,88,250,196);Secured(50,345,129,57);Secured(255,88,316,141);}
if($SThuis=='Off'){Secured(404,212,129,65);Secured(469,214,45,66);}
if($SPIR_Living!='Off') Motion(52,88,250,196);
if($SPIR_Inkom!='Off') Motion(50,345,129,57);
if($SPIR_Garage!='Off') Motion(255,88,316,141);
if($SPIR_Hall!='Off'){Motion(404,212,129,65);Motion(469,214,45,66);}
if($STDeurbel>$eendag) Timestamp('Deurbel',-90,17,462);
if($STPIR_Garage>$eendag) Timestamp('PIR_Garage',0,255,216);
if($STPIR_Living>$eendag) Timestamp('PIR_Living',0,233,120);
if($STPIR_Inkom>$eendag) Timestamp('PIR_Inkom',0,92,395);
if($STPIR_Hall>$eendag) Timestamp('PIR_Hall',0,403,215);
if($STAchterdeur>$eendag) Timestamp('Achterdeur',-90,280,79);
if($STpoort>$eendag) Timestamp('poort',90,315,381);
if($STBrander>$eendag) Timestamp('Brander',0,812,265);
if($STLicht_Zolder>$eendag) Timestamp('Licht_Zolder',0,688,266);
if($STBureel_Tobi>$eendag) Timestamp('Bureel_Tobi',0,782,433);
if($Spoort!='Closed') echo '<div style="position:absolute;top:262px;left:404px;width:25px;height:128px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SAchterdeur!='Closed') echo '<div style="position:absolute;top:264px;left:81px;width:30px;height:48px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SRaamLiving!='Closed') echo '<div style="position:absolute;top:46px;left:81px;width:8px;height:165px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SRaamKamerTobi!='Closed') echo '<div style="position:absolute;top:449px;left:81px;width:7px;height:43px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SRaamKamerJulius!='Closed') echo '<div style="position:absolute;top:569px;left:81px;width:7px;height:43px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SRaamKamer!='Closed') echo '<div style="position:absolute;top:586px;left:481px;width:7px;height:43px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SDeurBadkamer!='Closed') echo '<div style="position:absolute;top:421px;left:341px;width:7px;height:46px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SBureel_Tobi!='Off') echo'<div style="position:absolute;top:800px;left:425px;width:50px;cursor:pointer;text-align:center;">'.$PP_Bureel_Tobi.'</div>';


$execution= microtime(true)-$start;
$phptime=$execution-$domotime;
echo '<div style="position:absolute;top:652px;left:90px;width:400px;text-align:left;font-size:12px" >
'.$TInternal_Temperature.'°C|'.$SCPU_Usage.'|'.$SMemory_Usage.'M|D'.round($domotime,3).'|P'.round($phptime,3).'|T'.round($execution,3).'<br/></div>';

} else echo '<div style="background:#ddd;"><a href="">Geen verbinding met Domoticz</a></div>';	
} else {header("Location:index.php");die("Redirecting to:index.php");}
?>
<script type="text/javascript">
function toggle_visibility(id){var e=document.getElementById(id);if(e.style.display=='inherit') e.style.display='none';else e.style.display='inherit';}
setTimeout('window.location.href=window.location.href;',5850);
</script>
</body></html>