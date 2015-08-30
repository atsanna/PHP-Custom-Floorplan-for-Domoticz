<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Floorplan</title>
<meta http-equiv="refresh" content="30; ipad.php" ><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="HandheldFriendly" content="true" /><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="viewport" content="width=device-width,height=device-height, user-scalable=yes, minimal-ui" />
<link rel="icon" type="image/png" href="images/domoticzphp48.png"><link rel="shortcut icon" href="images/domoticzphp48.png" /><link rel="apple-touch-icon" href="images/domoticzphp48.png"/><link rel="icon" sizes="196x196" href="images/domoticzphp48.png"><link rel="icon" sizes="192x192" href="images/domoticzphp48.png">
<link href="ipad.css" rel="stylesheet" type="text/css" />
</head>
<body>
<?php $time = time();$offline = $time - 300;$eendag = $time - 82800;include "secure/functions.php";
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
$domoticz = file_get_contents($domoticzurl.'type=devices&filter=all&used=true&plan=2');$domoticz = json_decode($domoticz,true);
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

echo '<div style="position: absolute;top: 12px;left: 405px; width:180px;"><a href="" style="padding:12px 42px;border:none;background:none;font-size:16px; font-weight:500;"><font size="+3" style="font-weight:500">'.strftime("%k:%M:%S", $time).'</font></a></div>
<div style="position: absolute;top: 0px;left: 0px; width:80px; height:390px; background:#ddd;border-radius:10px; padding-top:10px;" >
<div style="position: absolute;top: 320px;left: 12px; width:55px;padding:0px;cursor: pointer;" onclick="location.href=\'rain.php\'"><img src="images/rain.png"/ title="'.strftime("%a %e %b %k:%M:%S", $RainTimeRegen).'" style="cursor:pointer" onclick="location.href=\'rain.php\'"></div>
<div style="position: absolute;top: 355px;left: 13px; width:55px;background:rgba(222, 222, 222, 0.8); padding:0px;cursor: pointer;" onclick="location.href=\'rain.php\'">'.$RainRegen.' / '.$TextBuienradar.'</div></div>
<div style="position: absolute;top: 410px;left: 0px; width:80px; background:#ddd;border-radius:10px; padding-top:10px;padding-bottom:10px;">
<form method="POST"><input type="hidden" name="denon" value="up"><input type="image" src="images/arrowup.png" alt="up" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="denon" value="down"><input type="image" src="images/arrowdown.png" alt="down" width="48px" height="48px"></form>
<br/><form action="denon.php" method="POST"><input type="image" src="images/denon.png" alt="Denon" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="2"><input type="hidden" name="Naam" value="Radio luisteren"><input type="image" src="images/Amp_On.png" alt="Radio" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="3"><input type="hidden" name="Naam" value="TV Kijken"><input type="image" src="images/TV_On.png" alt="TV" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="4"><input type="hidden" name="Naam" value="Kodi kijken"><input type="image" src="images/kodi.png" alt="Kodi" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="5"><input type="hidden" name="Naam" value="Eten"><input type="image" src="images/eten.png" alt="Eten" width="48px" height="48px"></form>
<br/><form method="POST"><input type="hidden" name="Scene" value="1"><input type="hidden" name="Naam" value="Alles uit"><input type="image" src="images/allesuit.png" alt="Alles uit" width="48px" height="48px"></form>
</div>';
Dimmer('Zithoek',72,130,135);
Dimmer('Eettafel',72,130,380);
Schakelaar('Lamp_Bureel','Light', 72, 12,294);
Schakelaar('TV','TV', 72, 2,94);	
Schakelaar('Denon','Amp', 72, 6,190);
Schakelaar('Licht_Inkom','Light', 60, 65,550);
Schakelaar('Licht_Voordeur','Light', 60, 65,700);
Schakelaar('Licht_Hall','Light', 63, 500,370);	
Schakelaar('Licht_Hall_Auto','Clock', 54, 504,445);		
Schakelaar('Licht_Garage','Light', 72, 345,350);	
Schakelaar('Licht_Garage_Auto','Clock', 54, 352,250);	
Schakelaar('Pluto','Laptop', 45, 430,300);	
Schakelaar('Thuis','Home', 72, 320,675);	
Schakelaar('Slapen','Sleepy', 72, 400,675);	
Schakelaar('Meldingen','Alarm', 72, 10,7);	
Schakelaar('Terras','Light', 72, 90,5);
Schakelaar('Brander','Fire', 72, 890,360);
Schakelaar('Licht_Zolder','Light', 72, 805,360);
Schakelaar('Bureel_Tobi','Plug', 54, 910,560);
Smokedetector('SD_Hall_General',54,500,325);
Smokedetector('SD_Zolder_General',54,830,500);
Thermometer('Buiten',130,175,13);
Thermometer('Living',100,150,310);
Thermometer('Badkamer',100,500,690);
Thermometer('Slaapkamer',100,660,690);
Thermometer('Slaapkamer_Tobi',100,540,150);
Thermometer('Slaapkamer_Tobi',100,670,150);
Thermometer('SD_Hall_Temperatuur',100,540,320);
Thermometer('SD_Zolder_Temperatuur',100,850,150);
Setpoint('Living',50,183,240);
Setpoint('Badkamer',50,533,620);
Setpoint('Slaapkamer',50,693,620);
Setpoint('Slaapkamer_Tobi',50,573,95);
Setpoint('Slaapkamer_Tobi',50,703,95);
Radiator('LivingZE',90,200,487);
Radiator('LivingZZ',-90,273,81);
Radiator('BadkamerZ',0,487,533);
if($SwitchThuis == 'Off' || $SwitchSlapen == 'On') {Secured(63,94,421,237);Secured(63,527,218,68);Secured(309,94,532,170);}
if($SwitchThuis == 'Off') {Secured(486,306,214,79);Secured(565,306,75,81);}
if($SwitchPIR_Living != 'Off') Motion(63,94,421,237);
if($SwitchPIR_Inkom  != 'Off') Motion(63,527,218,68);
if($SwitchPIR_Garage  != 'Off') Motion(309,94,532,170);
if($SwitchPIR_Hall  != 'Off') {Motion(486,306,214,79);Motion(565,306,75,81);}
if($SwitchTimeDeurbel>$eendag) Timestamp('Deurbel',-90,25,734);
if($SwitchTimePIR_Garage>$eendag) Timestamp('PIR_Garage',0,309,320);
if($SwitchTimePIR_Living>$eendag) Timestamp('PIR_Living',0,284,320);
if($SwitchTimePIR_Inkom>$eendag) Timestamp('PIR_Inkom',0,45,560);
if($SwitchTimePIR_Hall>$eendag) Timestamp('PIR_Hall',0,487,306);
if($SwitchTimeAchterdeur>$eendag) Timestamp('Achterdeur',-90,340,82);
if($SwitchTimepoort>$eendag) Timestamp('poort',90,385,600);
if($SwitchTimeBrander>$eendag) Timestamp('Brander',0,890,378);
if($SwitchTimeLicht_Zolder>$eendag) Timestamp('Licht_Zolder',0,790,378);
if($SwitchTimeBureel_Tobi>$eendag) Timestamp('Bureel_Tobi',0,910,640);
if($Switchpoort != 'Closed') echo '<div style="position:absolute;top:320px;left:626px;width:30px;height:144px;background:rgba(255,0,0,1);z-index:-10;"></div>';
if($SwitchAchterdeur != 'Closed') echo '<div style="position:absolute;top:320px;left:81px;width:35px;height:56px;background:rgba(255,0,0,1);z-index:-10;"></div>';

if($SwitchBureel_Tobi!='Off') echo'<div style="position:absolute;top:930px;left:640px;width:50px;cursor:pointer;text-align:center;">'.$PowerP_Bureel_Tobi.'</div>';
echo '<div style="position: absolute;top: 937px;left: 4px; width:80px; text-align:left;" id="cpuinfo">
<font color="#CCCCCC">CPU '.$SwitchCPU_Usage.'<br>Mem '.$SwitchMemory_Usage.'<br>SD '.$SwitchHDD__.'<br>'.$TempInternal_Temperature.'°C</font></div>';
} else echo '<div style="background:#ddd;"><a href="">Geen verbinding met Domoticz</a></div>';	
} else {header("Location: index.php");die("Redirecting to: index.php");}
?>
<script src="scripts/jquery-2.1.4.min.js"></script>
<script type="text/javascript">
function toggle_visibility(id) {var e = document.getElementById(id);if(e.style.display == 'inherit') e.style.display = 'none';else e.style.display = 'inherit';}
setTimeout('window.location.href=window.location.href;', 4880);
</script>
</body></html>