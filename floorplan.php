<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml">
<head><title>Floorplan</title>
<meta http-equiv="refresh" content="30;floorplan.php" ><meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<meta name="HandheldFriendly" content="true" /><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="viewport" content="width=device-width,height=device-height,user-scalable=yes,minimal-ui" />
<link rel="icon" type="image/png" href="images/domoticzphp48.png">
<link rel="shortcut icon" href="images/domoticzphp48.png" />
<link rel="apple-touch-icon" href="images/domoticzphp48.png" />
<link href="floorplan.css" rel="stylesheet" type="text/css" />
</head><body>
<?php $start = microtime(true);$time=time();$offline=$time-300;$eendag=$time-82800;include "secure/functions.php";
if($authenticated){
$schakelen = true;
if(isset($_POST['Schakel'])){
	if($_POST['Schakel']==6 || $_POST['Schakel']==48){
		if($SwitchAchterdeur=='Open'){$schakelen=false;echo '<script language="javascript">alert("WARNING:Achterdeur open!")</script>';}
		if($Switchpoort=='Open'){$schakelen=false;echo '<script language="javascript">alert("WARNING:Poort open!")</script>';}}
	if($schakelen==true){if(Schakel($_POST['Schakel'],$_POST['Actie'])=='ERROR') echo '<div id="message" class="balloon">'.$_POST['Naam'].' '.$_POST['Actie'].'<br/>ERROR</div>';}}
else if(isset($_POST['Setpoint'])){foreach($_POST as $key => $value){$value = round($value,0);if(substr($key,0,5)=="Actie"){if(Udevice($_POST['Setpoint'],0,$value)=='ERROR') echo '<div id="message" class="balloon">'.$_POST['Naam'].' '.$value.'<br/>ERROR</div>';}}}
else if(isset($_POST['Udevice'])){if(Udevice($_POST['Udevice'])=='ERROR') echo '<div id="message" class="balloon">'.$_POST['Naam'].' '.$_POST['Actie'].'<br/>ERROR</div>';}
else if(isset($_POST['dimlevelEettafel'])){if(Dim(163,$_POST['dimlevelEettafel'])=='ERROR') echo '<div id="message" class="balloon">Dimmer eettafel level '.$_POST['dimlevelEettafel'].'<br/>ERROR</div>';}
else if(isset($_POST['dimlevelZithoek'])){if(Dim(159,$_POST['dimlevelZithoek'])=='ERROR') echo '<div id="message" class="balloon">Dimmer eettafel level '.$_POST['dimlevelEettafel'].'<br/>ERROR</div>';}
else if(isset($_POST['Scene'])){if(Scene($_POST['Scene'])=='ERROR') echo '<div id="message" class="balloon">Scene '.$_POST['Naam'].' activeren'.'<br/>ERROR</div>';}
if(isset($_POST['denon'])){$denon_address = 'http://192.168.0.2';$ctx = stream_context_create(array('http'=>array('timeout' => 2,)));
	$denonmain = simplexml_load_string(file_get_contents($denon_address.'/goform/formMainZone_MainZoneXml.xml?_='.$time,false,$ctx));
	$denonmain = json_encode($denonmain);$denonmain = json_decode($denonmain,TRUE);usleep(10000);
	if($denonmain){
		$denonmain['MasterVolume']['value']=='--' ? $setvalue = -80 :$setvalue = $denonmain['MasterVolume']['value'];
		$_POST['denon']=='up' ? $setvalue = $setvalue+3 :$setvalue = $setvalue-3;
		if($setvalue > -10) $setvalue = -10;if($setvalue < -80) $setvalue = -80;
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutMasterVolumeSet/'.$setvalue.'.0');
	}
}
$domoticz = file_get_contents($domoticzurl.'type=devices&filter=all&used=true&plan=2');$domotime = microtime(true) - $start;$domoticz = json_decode($domoticz,true);
if($domoticz){
	foreach($domoticz['result'] as $dom){
		(isset($dom['Type'])?$Type=$dom['Type']:$Type='None');
		(isset($dom['SwitchType'])?$SwitchType=$dom['SwitchType']:$SwitchType='None');
		(isset($dom['SubType'])?$SubType=$dom['SubType']:$SubType='None');
		$name=str_replace(' ','_',$dom['Name']);$name=str_replace('/','_',$name);
		if($Type=='Temp + Humidity'||$Type=='Temp'){${'Temp'.$name}=$dom['Temp'];${'Tempidx'.$name}=$dom['idx'];${'Tempbat'.$name}=$dom['BatteryLevel'];${'TempTime'.$name}=strtotime($dom['LastUpdate']);}
		else if($SwitchType=='Dimmer'){${'Dimmeridx'.$name} = $dom['idx'];$dom['Status']=='Off' ? ${'Dimmer'.$name} = 'Off':${'Dimmer'.$name} = 'On';$dom['Status']=='Off' ? ${'Dimmerlevel'.$name} = 0:${'Dimmerlevel'.$name} = $dom['Level'];${'DimmerTime'.$name} = strtotime($dom['LastUpdate']);}
		else if($Type=='Rain'){${'Rainidx'.$name} = $dom['idx'];${'Rain'.$name} = $dom['Rain'];${'Rainbat'.$name} = $dom['BatteryLevel'];${'RainTime'.$name} = strtotime($dom['LastUpdate']);}
		else if($Type=='General' && $dom['SubType']=='Text'){${'Textidx'.$name} = $dom['idx'];${'Text'.$name} = $dom['Data'];${'TextTime'.$name} = strtotime($dom['LastUpdate']);}
		else if($Type=='Usage' && $dom['SubType']=='Electric'){${'Poweridx'.$name} = $dom['idx'];${'Power'.$name} = substr($dom['Data'],0,-5);${'PowerTime'.$name} = strtotime($dom['LastUpdate']);}
		else if($Type=='Scene' || $Type=='Group' || $Type=='Wind'){}
		else if($Type=='Radiator 1' || $Type=='Thermostat'){${'Radidx'.$name} = $dom['idx'];${'Rad'.$name} = $dom['Data'];${'RadTime'.$name} = strtotime($dom['LastUpdate']);${'Radbat'.$name} = $dom['BatteryLevel'];}
		else {if(substr($dom['Data'],0,2)=='On'){${'Switch'.$name} = 'On';}
			else if(substr($dom['Data'],0,3)=='Off'){${'Switch'.$name} = 'Off';}
			else if(substr($dom['Data'],0,4)=='Open'){${'Switch'.$name} = 'Open';}
			else {${'Switch'.$name} = $dom['Data'];}
			${'Switchidx'.$name} = $dom['idx'];${'SwitchTime'.$name} = strtotime($dom['LastUpdate']);${'Switchbat'.$name} = $dom['BatteryLevel'];}
	}

echo '<div style="position:absolute;top:5px;left:237px;width:180px;"><a href="" style="padding:12px 42px;font-size:33px;font-weight:500;">'.strftime("%k:%M:%S",$time).'</a></div>
<div class="box" style="top:0px;height:306px;" >
<div class="box2" style="top:255px;left:12px;" onclick="location.href=\'rain.php\'"><img src="images/rain.png"/ title="'.strftime("%a %e %b %k:%M:%S",$RainTimeRegen).'" style="cursor:pointer" onclick="location.href=\'rain.php\'"></div>
<div class="box2" style="top:290px;left:13px;background:rgba(222,222,222,0.8);" onclick="location.href=\'rain.php\'">'.$RainRegen.' / '.$TextBuienradar.'</div></div>
<div class="box" style="top:319px;height:505px;">
<form method="POST"><input type="hidden" name="denon" value="up"><input type="image" src="images/arrowup.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="denon" value="down"><input type="image" src="images/arrowdown.png" width="48px" height="48px"></form>
<br/><form action="denon.php" method="POST"><input type="image" src="images/denon.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="2"><input type="hidden" name="Naam" value="Radio luisteren"><input type="image" src="images/Amp_On.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="3"><input type="hidden" name="Naam" value="TV Kijken"><input type="image" src="images/TV_On.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="4"><input type="hidden" name="Naam" value="Kodi kijken"><input type="image" src="images/kodi.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="5"><input type="hidden" name="Naam" value="Eten"><input type="image" src="images/eten.png" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="1"><input type="hidden" name="Naam" value="Alles uit"><input type="image" src="images/allesuit.png" width="48px" height="48px"></form>
</div>';
Dimmer('Zithoek',48,115,125);
Dimmer('Eettafel',48,115,269);
Schakelaar('Lamp_Bureel','Light',40,8,209);
Schakelaar('TV','TV',48,3,92);	
Schakelaar('Denon','Amp',48,6,154);
Schakelaar('Licht_Inkom','Light',40,60,360);
Schakelaar('Licht_Voordeur','Light',40,60,441);
Schakelaar('Licht_Hall','Light',42,416,252);	
Schakelaar('Licht_Hall_Auto','Clock',36,420,299);		
Schakelaar('Licht_Garage','Light',48,305,216);	
Schakelaar('Licht_Garage_Auto','Clock',36,312,180);	
Schakelaar('Pluto','Laptop',30,365,266);	
Schakelaar('Thuis','Home',48,268,428);	
Schakelaar('Slapen','Sleepy',48,335,428);	
Schakelaar('Meldingen','Alarm',48,5,16);	
Schakelaar('Terras','Light',48,77,15);
Schakelaar('Brander','Fire',48,765,260);
Schakelaar('Licht_Zolder','Light',48,705,260);
Schakelaar('Bureel_Tobi','Plug',36,780,380);
Smokedetector('SD_Hall_General',36,434,218);
Smokedetector('SD_Zolder_General',36,710,320);
Thermometer('Buiten',110,140,17);
Thermometer('Living',80,141,225);
Thermometer('Badkamer',70,426,435);
Thermometer('Slaapkamer',70,568,435);
Thermometer('Slaapkamer_Tobi',70,458,135);
Thermometer('Slaapkamer_Tobi',70,568,135);
Thermometer('SD_Hall_Temperatuur',60,470,224);
Thermometer('SD_Zolder_Temperatuur',60,707,160);
Setpoint('Living',48,160,172);
Setpoint('Badkamer',40,436,385);
Setpoint('Slaapkamer',40,579,385);
Setpoint('Slaapkamer_Tobi',40,469,90);
Setpoint('Slaapkamer_Tobi',40,579,90);
Radiator('LivingZE',90,160,310);
Radiator('LivingZZ',-90,221,76);
Radiator('BadkamerZ',0,403,349);
if($SwitchThuis=='Off' || $SwitchSlapen=='On'){Secured(52,88,250,196);Secured(50,345,129,57);Secured(255,88,316,141);}
if($SwitchThuis=='Off'){Secured(404,212,129,65);Secured(469,214,45,66);}
if($SwitchPIR_Living != 'Off') Motion(52,88,250,196);
if($SwitchPIR_Inkom  != 'Off') Motion(50,345,129,57);
if($SwitchPIR_Garage  != 'Off') Motion(255,88,316,141);
if($SwitchPIR_Hall  != 'Off'){Motion(404,212,129,65);Motion(469,214,45,66);}
if($SwitchTimeDeurbel>$eendag) Timestamp('Deurbel',-90,17,456);
if($SwitchTimePIR_Garage>$eendag) Timestamp('PIR_Garage',0,255,223);
if($SwitchTimePIR_Living>$eendag) Timestamp('PIR_Living',0,233,223);
if($SwitchTimePIR_Inkom>$eendag) Timestamp('PIR_Inkom',0,40,360);
if($SwitchTimePIR_Hall>$eendag) Timestamp('PIR_Hall',0,403,215);
if($SwitchTimeAchterdeur>$eendag) Timestamp('Achterdeur',-90,280,79);
if($SwitchTimepoort>$eendag) Timestamp('poort',90,315,377);
if($SwitchTimeBrander>$eendag) Timestamp('Brander',0,812,265);
if($SwitchTimeLicht_Zolder>$eendag) Timestamp('Licht_Zolder',0,688,266);
if($SwitchTimeBureel_Tobi>$eendag) Timestamp('Bureel_Tobi',0,782,433);
if($Switchpoort != 'Closed') echo '<div style="position:absolute;top:262px;left:404px;width:25px;height:128px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SwitchAchterdeur != 'Closed') echo '<div style="position:absolute;top:264px;left:81px;width:30px;height:48px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SwitchBureel_Tobi!='Off') echo'<div style="position:absolute;top:18px;left:56px;width:50px;cursor:pointer;text-align:center;">'.$PowerP_Bureel_Tobi.'</div>';
$execution= microtime(true) - $start;
$phptime = $execution - $domotime;
echo '<div style="position:absolute;top:840px;left:4px;width:500px;text-align:left;" id="cpuinfo">
<font color="#CCCCCC">CPU '.$SwitchCPU_Usage.' - Memory '.$SwitchMemory_Usage.' - SD '.$SwitchHDD__.' - '.$TempInternal_Temperature.'°C<br/>'.shell_exec('uptime').'<br/>
Domoticz: '.round($domotime,6).' - PHP: '.round($phptime,6).' - Total: '.round($execution,6).'</font></div>';
} else echo '<div style="background:#ddd;"><a href="">Geen verbinding met Domoticz</a></div>';	
} else {header("Location:index.php");die("Redirecting to:index.php");}
?>
<script type="text/javascript">
function toggle_visibility(id){var e = document.getElementById(id);if(e.style.display=='inherit') e.style.display = 'none';else e.style.display = 'inherit';}
setTimeout('window.location.href=window.location.href;',4880);
</script>
</body></html>