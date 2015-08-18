<?php 
$aantalrunsperminuut = 15;
$authenticated = false;
include "functions.php";
if($authenticated) {error_reporting(E_ALL);ini_set("display_errors", "on");$aantalrunsperminuut = 2;}
$variables = file_get_contents($domoticzurl.'type=command&param=getuservariables');$variables = json_decode($variables,true);
	foreach($variables['result'] as $var) {
		$name = str_replace(' ', '_', $var['Name']);
		$name = str_replace('/', '_', $name);
		global ${$name};
		${$name} = $var['Value'];
	}
for ($k = 1 ; $k < $aantalrunsperminuut; $k++){ 
$time = time();$timeout = $time - 170;
$domoticz = file_get_contents($domoticzurl.'type=devices');$domoticz = json_decode($domoticz,true);
if($domoticz) {
	foreach($domoticz['result'] as $dom) {
		(isset($dom['Type']) ? $Type = $dom['Type'] : $Type = 'None');
		(isset($dom['SwitchType']) ? $SwitchType = $dom['SwitchType'] : $SwitchType = 'None');
		(isset($dom['SubType']) ? $SubType = $dom['SubType'] : $SubType = 'None');
		$nametemp = str_replace(' ', '_', $dom['Name']);
		$name = str_replace('/', '_', $nametemp);
		if($Type == 'Temp + Humidity' || $Type == 'Temp') {${'Temp'.$name} = $dom['Temp'];${'Tempidx'.$name} = $dom['idx'];${'Tempbat'.$name} = $dom['BatteryLevel'];${'TempTime'.$name} = strtotime($dom['LastUpdate']);}
		else if($SwitchType == 'Dimmer') {${'Dimmeridx'.$name} = $dom['idx'];$dom['Status']=='Off' ? ${'Dimmer'.$name} = 'Off':${'Dimmer'.$name} = 'On';$dom['Status']=='Off' ? ${'Dimmerlevel'.$name} = 0:${'Dimmerlevel'.$name} = $dom['Level'];${'DimmerTime'.$name} = strtotime($dom['LastUpdate']);}
		else if($Type == 'Rain') {${'Rainidx'.$name} = $dom['idx'];${'Rain'.$name} = $dom['Rain'];${'Rainbat'.$name} = $dom['BatteryLevel'];${'RainTime'.$name} = strtotime($dom['LastUpdate']);}
		else if($Type == 'General' && $dom['SubType'] == 'Text') {${'Textidx'.$name} = $dom['idx'];${'Text'.$name} = $dom['Data'];${'TextTime'.$name} = strtotime($dom['LastUpdate']);}
		else if($Type == 'Usage' && $dom['SubType'] == 'Electric') {${'Poweridx'.$name} = $dom['idx'];${'Power'.$name} = substr($dom['Data'], 0, -5);${'PowerTime'.$name} = strtotime($dom['LastUpdate']);}
		else if($Type == 'Scene' || $Type == 'Group' || $Type == 'Wind') {}
		else if($Type == 'Radiator 1' || $Type == 'Thermostat') {${'Radidx'.$name} = $dom['idx'];${'Rad'.$name} = $dom['Data'];${'RadTime'.$name} = strtotime($dom['LastUpdate']);${'Radbat'.$name} = $dom['BatteryLevel'];}
		else {if(substr($dom['Data'],0,2)=='On') {${'Switch'.$name} = 'On';}
			else if(substr($dom['Data'],0,3)=='Off') {${'Switch'.$name} = 'Off';}
			else if(substr($dom['Data'],0,4)=='Open') {${'Switch'.$name} = 'Open';}
			else {${'Switch'.$name} = $dom['Data'];}
			${'Switchidx'.$name} = $dom['idx'];${'SwitchTime'.$name} = strtotime($dom['LastUpdate']);${'Switchbat'.$name} = $dom['BatteryLevel'];}
	}
}
if($variables && $domoticz) {
	if($domoticzconnection!='true') {uservariable('domoticzconnection',2, 'true');telegram('Verbinding met Domoticz hersteld');$domoticzconnection = 'true';}
	//Zon op / zon onder
	$zonop = strtotime($domoticz['Sunrise']);
	$zononder = strtotime($domoticz['Sunset']);

	//Meldingen
	if($SwitchMeldingen != 'On' && $SwitchTimeMeldingen < $time - 43200) Schakel($SwitchidxMeldingen, 'On');
	if(($SwitchThuis == 'Off' || $SwitchSlapen == 'On') && $SwitchTimeThuis < $timeout && $SwitchMeldingen == 'On') {
		if($Switchpoort!='Closed') {$msg = 'Poort Open om '.strftime("%H:%M:%S", $SwitchTimepoort);
			if($telegrampoort < $time - 60) {telegram($msg);uservariable('telegrampoort',0, $time); $telegrampoort = $time;}
			if($sms==true && $smspoort < $time - 900) {sms($msg);uservariable('smspoort',0, $time); $smspoort = $time;}
			if($iospoort < $time - 600) {ios($msg);uservariable('iospoort',0, $time); $iospoort = $time;}
			if($SwitchMeldingen == 'On') $SwitchDeurbel='Off';schakel($SwitchidxDeurbel, 'On');Udevice($SwitchidxDeurbel, 0, 'Off');;
		}
		if($SwitchAchterdeur!='Closed') {$msg = 'Achterdeur Open om '.strftime("%H:%M:%S", $SwitchTimeAchterdeur);
			if($telegramAchterdeur < $time - 60) {telegram($msg);uservariable('telegramAchterdeur',0, $time); $telegramAchterdeur = $time;}
			if($sms==true && $smsAchterdeur < $time - 900) {sms($msg);uservariable('smsAchterdeur',0, $time); $smsAchterdeur = $time;}
			if($iosAchterdeur < $time - 600) {ios($msg);uservariable('iosAchterdeur',0, $time); $iosAchterdeur = $time;}  
			if($SwitchMeldingen == 'On') $SwitchDeurbel='Off';schakel($SwitchidxDeurbel, 'On');Udevice($SwitchidxDeurbel, 0, 'Off');;
		}
		if($SwitchPIR_Garage!='Off' && $SwitchTimeSlapen < $timeout) {$msg = 'Beweging gedecteerd in garage om '.strftime("%H:%M:%S", $SwitchTimePIR_Garage);
			if($telegramPIR_Garage < $time - 60) {telegram($msg);uservariable('telegramPIR_Garage',0, $time); $telegramPIR_Garage = $time;} 
			if($sms==true && $smsPIR_Garage < $time - 120) {sms($msg);uservariable('smsPIR_Garage',0, $time); $smsPIR_Garage = $time;}
			if($iosPIR_Garage < $time - 600) {ios($msg);uservariable('iosPIR_Garage',0, $time); $iosPIR_Garage = $time;} 
			if($SwitchMeldingen == 'On') $SwitchDeurbel='Off';schakel($SwitchidxDeurbel, 'On');Udevice($SwitchidxDeurbel, 0, 'Off');;
		}
		if($SwitchPIR_Living!='Off' && $SwitchTimeSlapen < $timeout) {$msg = 'Beweging gedecteerd in living om '.strftime("%H:%M:%S", $SwitchTimePIR_Living);
			if($telegramPIR_Living < $time - 90) {telegram($msg);uservariable('telegramPIR_Living',0, $time); $telegramPIR_Living = $time;} 
			if($sms==true && $smsPIR_Living < $time - 900) {sms($msg);uservariable('smsPIR_Living',0, $time); $smsPIR_Living = $time;}
			if($iosPIR_Living < $time - 600) {ios($msg);uservariable('iosPIR_Living',0, $time); $iosPIR_Living = $time;} 
			if($SwitchMeldingen == 'On') $SwitchDeurbel='Off';schakel($SwitchidxDeurbel, 'On');Udevice($SwitchidxDeurbel, 0, 'Off');;
		}
		if($SwitchPIR_Inkom!='Off' && $SwitchTimeSlapen < $timeout) {$msg = 'Beweging gedecteerd in inkom om '.strftime("%H:%M:%S", $SwitchTimePIR_Living);
			if($telegramPIR_Inkom < $time - 90) {telegram($msg);uservariable('telegramPIR_Inkom',0, $time); $telegramPIR_Inkom = $time;} 
			if($sms==true && $smsPIR_Inkom < $time - 900) {sms($msg);uservariable('smsPIR_Inkom',0, $time); $smsPIR_Inkom = $time;}
			if($iosPIR_Inkom < $time - 600) {ios($msg);uservariable('iosPIR_Inkom',0, $time); $iosPIR_Inkom = $time;} 
			if($SwitchMeldingen == 'On') $SwitchDeurbel='Off';schakel($SwitchidxDeurbel, 'On');Udevice($SwitchidxDeurbel, 0, 'Off');;
		}
	}
	if($SwitchThuis == 'Off' && $SwitchTimeThuis < $timeout && $SwitchMeldingen == 'On') {
		if($SwitchPIR_Hall!='Off') {$msg = 'Beweging gedecteerd in hall om '.strftime("%H:%M:%S", $SwitchTimePIR_Living);
			if($telegramPIR_Hall < $time - 90) {telegram($msg);uservariable('telegramPIR_Hall',0, $time); $telegramPIR_Hall = $time;} 
			if($sms==true && $smsPIR_Hall < $time - 900) {sms($msg);uservariable('smsPIR_Hall',0, $time); $smsPIR_Hall = $time;}
			if($iosPIR_Hall < $time - 600) {ios($msg);uservariable('iosPIR_Hall',0, $time); $iosPIR_Hall = $time;} 
			if($SwitchMeldingen == 'On') $SwitchDeurbel='Off';schakel($SwitchidxDeurbel, 'On');Udevice($SwitchidxDeurbel, 0, 'Off');;
		}
	}
	if($SwitchMeldingen == 'On') {
		//Gemiddelde temperatuur binnen
		$thermometers=array('Living','Badkamer','Slaapkamer','Slaapkamer_Tobi','SD_Hall_Temperatuur','SD_Zolder_Temperatuur');
		$avgtemperatuur=0;$aantalthermometers=0;
		foreach($thermometers as $thermometer) {$avgtemperatuur=$avgtemperatuur+${'Temp'.$thermometer};$aantalthermometers=$aantalthermometers+1;}
		$avgtemperatuur = $avgtemperatuur / $aantalthermometers;
		foreach($thermometers as $thermometer) {
			if(${'Temp'.$thermometer} > $avgtemperatuur + 5) {$msg = 'Temperatuur '.$thermometer.' = '.${'Temp'.$thermometer}.'. Gemiddelde = '.$avgtemperatuur;
				if(${'telegramtemp'.$thermometer} < $time - 600) {telegram($msg);uservariable('telegramtemp'.$thermometer,0, $time); ${'telegramtemp'.$thermometer} = $time;}
				if($sms==true && ${'smstemp'.$thermometer} < $time - 3600) {sms($msg);uservariable('smstemp'.$thermometer,0, $time); ${'smstemp'.$thermometer} = $time;}
				if(${'iostemp'.$thermometer} < $time - 1800) {ios($msg);uservariable('iostemp'.$thermometer,0, $time); ${'iostemp'.$thermometer} = $time;}
			}
		}
		if($PowerP_Bureel_Tobi > 500 && $telegrampowerbureeltobi < $time - 1800) {$msg = 'Verbruik bureel Tobi = '.$PowerP_Bureel_Tobi;telegram($msg);uservariable('telegrampowerbureeltobi',0, $time); $telegrampowerbureeltobi = $time;}
	}
	if($SwitchDeurbel!='Off' && $SwitchMeldingen == 'On' ) {
		$msg = 'Deurbel om '.strftime("%H:%M:%S", $SwitchTimeDeurbel);
		if($telegramsentDeurbel < $time - 30) telegram($msg);uservariable('telegramsentDeurbel',0, $time);
		if($iossentDeurbel < $time - 30) ios($msg);uservariable('iossentDeurbel',0, $time);
		Udevice($SwitchidxDeurbel, 0, 'Off');
	}
	if($Switchpoort!='Closed' && $SwitchTimepoort < $time - 900 && $telegramsentpoort < $time - 900 && $SwitchMeldingen == 'On') {$msg = 'Poort Open sinds '.strftime("%H:%M:%S", $SwitchTimepoort);$telegramsentpoort = time();uservariable('telegramsentpoort',0, $telegramsentpoort);telegram($msg);}
	//Living
	$SetLiving = 16.0;
	if($TempBuiten<20 && $SwitchThuis=='On') {
		if($time>=(strtotime('7:00')) && $time<strtotime('8:30')) $SetLiving = 18.0;
		else if($time>=(strtotime('8:30')) && $time<strtotime('18:30')) $SetLiving = 20.0;
		else if($time>=(strtotime('18:30')) && $time<=strtotime('22:00')) $SetLiving = 21.0;
	}
	if(($SetLiving != $RadLiving && $RadTimeLiving < $time - 3600)||($SetLiving >  $RadLiving && $SwitchTimeThuis  > $time - 3600 && $SwitchThuis=='On')||($SetLiving != $RadLiving && $SwitchTimeThuis > $time - 3600 && $SwitchThuis=='Off')){Udevice($RadidxLiving, 0, $SetLiving);$RadLiving = $SetLiving;}
	if($RadLiving-$TempLiving>0) $SetLiving = round($RadLiving + (ceil(($RadLiving-$TempLiving)*4)),0); else $SetLiving = round($RadLiving + (ceil(($RadLiving-$TempLiving)*4)),0);
	if($SetLiving>28) $SetLiving=28;else if ($SetLiving<4) $SetLiving=4;
	if($RadLivingZZ != $SetLiving) {Udevice($RadidxLivingZZ, 0, $SetLiving);$RadLivingZZ = $SetLiving;}
	if($RadLivingZE != $SetLiving) {Udevice($RadidxLivingZE, 0, $SetLiving);$RadLivingZE = $SetLiving;}
	if($RadTimeLiving < $time - 43200) {Udevice($RadidxLiving, 0, $RadLiving);}
	//Badkamer
	$SetBadkamer = 16.0;
	if($TempBuiten<21 && $SwitchThuis=='On') {
		if($TempBadkamer<21) $voorwarmen = ceil((21-$TempBadkamer)*(21-$TempBuiten)*30); else $voorwarmen = 0;
		if(in_array(date('N', $time), array(1,2,3,4,5)) && $time>=(strtotime('6:00')-$voorwarmen) && $time<=(strtotime('7:40'))) $SetBadkamer = 21.0;
		else if(in_array(date('N', $time), array(6,7)) && $time>=(strtotime('7:30')-$voorwarmen) && $time<=(strtotime('9:30'))) $SetBadkamer = 20.0;
	}
	if(($SetBadkamer != $RadBadkamer && $RadTimeBadkamer < $time - 3600)||($SetBadkamer >  $RadBadkamer && $SwitchTimeThuis  > $time - 3600 && $SwitchThuis=='On')||($SetBadkamer != $RadBadkamer && $SwitchTimeThuis > $time - 3600 && $SwitchThuis=='Off')){Udevice($RadidxBadkamer, 0, $SetBadkamer);$RadBadkamer = $SetBadkamer;}
	if($RadBadkamer-$TempBadkamer>0) $SetBadkamer = round($RadBadkamer + (ceil(($RadBadkamer-$TempBadkamer)*6)),0); else $SetBadkamer = round($RadBadkamer + (ceil(($RadBadkamer-$TempBadkamer)*6)),0);
	if($SetBadkamer>28) $SetBadkamer=28;else if ($SetBadkamer<4) $SetBadkamer=4;
	if($RadBadkamerZ != $SetBadkamer) {Udevice($RadidxBadkamerZ, 0, $SetBadkamer);$RadBadkamerZ = $SetBadkamer;}
	if($RadTimeBadkamer < $time - 43200) {Udevice($RadidxBadkamer, 0, $RadBadkamer);}
	//Slaapkamer
	$SetSlaapkamer = 10;
	if(($SetSlaapkamer != $RadSlaapkamer) && $RadTimeSlaapkamer < $time - 3600) {Udevice($RadidxSlaapkamer, 0, $SetSlaapkamer);$RadSlaapkamer = $SetSlaapkamer;}
	if($RadTimeSlaapkamer < $time - 86340) {Udevice($RadidxSlaapkamer, 0, $SetSlaapkamer);$RadSlaapkamer = $SetSlaapkamer;}
	//Slaapkamer Tobi
	$SetSlaapkamer = 10;
	if(($SetSlaapkamer != $RadSlaapkamer_Tobi) && $RadTimeSlaapkamer_Tobi < $time - 3600) {Udevice($RadidxSlaapkamer_Tobi, 0, $SetSlaapkamer);$RadSlaapkamer_Tobi = $SetSlaapkamer;}
	if($RadTimeSlaapkamer_Tobi < $time - 86340) {Udevice($RadidxSlaapkamer_Tobi, 0, $SetSlaapkamer);$RadSlaapkamer_Tobi = $SetSlaapkamer;}
	//Subwoofer
	if($SwitchDenon=='On' && $SwitchSubwoofer!='On') Schakel($SwitchidxSubwoofer, 'On');
	if($SwitchDenon=='Off' && $SwitchSubwoofer!='Off') Schakel($SwitchidxSubwoofer, 'Off');
	//Brander
	if(($TempLiving<$RadLiving || $TempBadkamer<$RadBadkamer || $TempSlaapkamer<$RadSlaapkamer || $TempSlaapkamer_Tobi<$RadSlaapkamer_Tobi) && $SwitchBrander == "Off" && $SwitchTimeBrander < $time - 240) Schakel($SwitchidxBrander, 'On');
	else if($TempLiving>=$RadLiving-0.3 && $TempBadkamer>=$RadBadkamer-0.3 && $TempSlaapkamer>=$RadSlaapkamer-0.3 && $TempSlaapkamer_Tobi>=$RadSlaapkamer_Tobi-0.3 && $SwitchBrander == "On" && $SwitchTimeBrander < $time - 240) Schakel($SwitchidxBrander, 'Off');
	//PIR Living resetten
	if($SwitchPIR_Living != 'Off' && $SwitchTimePIR_Living < $timeout) Schakel($SwitchidxPIR_Living, 'Off');
	//PIR Garage resetten
	if($SwitchPIR_Garage != 'Off' && $SwitchTimePIR_Garage < $timeout) Schakel($SwitchidxPIR_Garage, 'Off');
	//Licht Garage uitschakelen
	if($SwitchPIR_Garage == 'Off' && $Switchpoort == 'Closed' && $SwitchTimePIR_Garage < $time - 180  && $SwitchTimepoort < $timeout && $SwitchTimeLicht_Garage < $time - 170 && $SwitchLicht_Garage == 'On' && $SwitchLicht_Garage_Auto == 'On') Schakel($SwitchidxLicht_Garage, 'Off');
	//Licht Inkom Uitschakelen
	if($SwitchTimeLicht_Inkom < $time - 180 && $SwitchLicht_Inkom == 'On' && $SwitchLicht_Hall_Auto == 'On') Schakel($SwitchidxLicht_Inkom, 'Off');
	//Licht Hall Uitschakelen
	if($SwitchTimeLicht_Hall < $time - 210 && $SwitchLicht_Hall == 'On' && $SwitchLicht_Hall_Auto == 'On') Schakel($SwitchidxLicht_Hall, 'Off');
	//Licht garage automatisch
	if($time > $zononder-3600 && $SwitchTimeLicht_Garage_Auto < $time - 14400 && $SwitchLicht_Garage_Auto != 'On') Schakel($SwitchidxLicht_Garage_Auto, 'On');
	if($time > $zonop+5400 && $SwitchTimeLicht_Garage_Auto < $time - 14400 && $SwitchLicht_Garage_Auto != 'Off' && $SwitchLicht_Garage == 'Off') Schakel($SwitchidxLicht_Garage_Auto, 'Off');
	//Licht Hall-Inkom automatisch
	if($time > $zononder-1800 && $SwitchTimeLicht_Hall_Auto < $time - 14400 && $SwitchLicht_Hall_Auto != 'On') Schakel($SwitchidxLicht_Hall_Auto, 'On');
	if($time > $zonop+1800 && $SwitchTimeLicht_Hall_Auto < $time - 14400 && $SwitchLicht_Hall_Auto != 'Off' && $SwitchLicht_Hall == 'Off' && $SwitchLicht_Inkom == 'Off') Schakel($SwitchidxLicht_Hall_Auto, 'Off');
	//Slapen - niet thuis bij geen beweging
	if($SwitchTimePIR_Living < $time - 14400 && $SwitchTimePIR_Garage < $time - 14400 && $SwitchTimePIR_Inkom < $time - 14400 && $SwitchTimePIR_Hall < $time - 14400 && $SwitchTimeSlapen < $time - 14400 && $SwitchTimeThuis < $time - 14400 && $SwitchThuis=='On' && $SwitchSlapen=="Off") {Schakel($SwitchidxSlapen, 'On');telegram('Slapen ingeschakeld na 4 uur geen beweging');}
	if($SwitchTimePIR_Living < $time - 43200 && $SwitchTimePIR_Garage < $time - 43200  && $SwitchTimePIR_Inkom < $time - 43200 && $SwitchTimePIR_Hall < $time - 43200 && $SwitchTimeSlapen < $time - 28800 && $SwitchTimeThuis < $time - 43200 && $SwitchThuis=='On' && $SwitchSlapen=="On") {Schakel($SwitchidxSlapen, 'Off');Schakel($SwitchidxThuis, 'Off');telegram('Thuis uitgeschakeld na 12 uur geen beweging');}
	//Alles uitschakelen
	if($SwitchThuis=='Off' || $SwitchSlapen=="On") {
		$timeout = $time - 30;
		if($SwitchTV!='Off' && $SwitchTimeTV < $time) Schakel($SwitchidxTV, 'Off');
		if($SwitchDenon!='Off' && $SwitchTimeDenon < $time) Schakel($SwitchidxDenon, 'Off');
		if($SwitchLamp_Bureel!='Off' && $SwitchTimeLamp_Bureel < $timeout) Schakel($SwitchidxLamp_Bureel, 'Off');
		if($SwitchTerras!='Off' && $SwitchTimeTerras < $time) Schakel($SwitchidxTerras, 'Off');
		if($SwitchLicht_Garage!='Off' && $SwitchTimeLicht_Garage < $timeout) Schakel($SwitchidxLicht_Garage, 'Off');
		if($SwitchLicht_Voordeur!='Off' && $SwitchTimeLicht_Voordeur < $time) Schakel($SwitchidxLicht_Voordeur, 'Off');
		if($SwitchLicht_Inkom!='Off' && $SwitchTimeLicht_Inkom < $timeout) Schakel($SwitchidxLicht_Inkom, 'Off');
		if($SwitchLicht_Zolder!='Off' && $SwitchTimeLicht_Zolder < $time) Schakel($SwitchidxLicht_Zolder, 'Off');
		if($SwitchBureel_Tobi!='Off' && $SwitchTimeBureel_Tobi < $time) Schakel($SwitchidxBureel_Tobi, 'Off');
		if($DimmerEettafel!='Off' && $DimmerTimeEettafel < $timeout) Schakel($DimmeridxEettafel, 'Off');
		if($DimmerZithoek!='Off' && $DimmerTimeZithoek < $timeout) Schakel($DimmeridxZithoek, 'Off');
	}
	//End Acties
} else {if($domoticzconnection=='true') {uservariable('domoticzconnection',2, 'false');telegram('Geen verbinding met Domoticz');$domoticzconnection='false';}
}
if($Read_Homewizard < $time - 58 ) {
	$homewizard = file_get_contents($hwurl.'get-sensors');
	$homewizard = json_decode($homewizard,true);
	if(!$homewizard) {if($homewizardconnection=='true'){uservariable('homewizardconnection',2, 'false');telegram('Geen verbinding met Homewizard');$homewizardconnection='false';}
	} else {
		if($homewizardconnection!='true'){uservariable('homewizardconnection',2, 'true');telegram('Verbinding met Homewizard hersteld');$homewizardconnection='true';}
		foreach($homewizard['response']['thermometers'] as $temp) {
			if(isset($temp['te'])) {
				$TEMP=str_replace(',', '.', $temp['te']);
				$DUNIT=$temp['id'];
				if($DUNIT==1) $IDX = 36;
				else if($DUNIT==4) $IDX = 37;
				else if($DUNIT==5) $IDX = 38;
				else if($DUNIT==6) $IDX = 39;
				else if($DUNIT==7) $IDX = 40;
				if($temp['lowBattery']=='yes') $bat = 0; else $bat = 100;
				$URL = $domoticzurl.'type=command&param=udevice&idx='.$IDX.'&nvalue=0&svalue='.$TEMP.'&battery='.$bat;
				file_get_contents($URL);
			}
		}
		foreach($homewizard['response']['rainmeters'] as $rain) {
			if(isset($rain['mm']) && strftime('%H:%M', $time) > '0:06' && strftime('%H:%M', $time) < '23:54') {
				$DAY=str_replace(',', '.', $rain['mm']);
				//$THREE=round(str_replace(',', '.', $rain['3h'])/3, 1);
				if($rain['lowBattery']=='yes') $bat = 0; else $bat = 100;
				$URL = $domoticzurl.'type=command&param=udevice&idx=52&nvalue=0&svalue='.$DAY.';0&battery='.$bat;
				file_get_contents($URL);
			}
		}	
		foreach($homewizard['response']['windmeters'] as $wind) {
			if(isset($wind['ws'])) {
				$WD=preg_replace("/[^A-Za-z]+/", "", $wind['dir']);
				$WB=$int = filter_var($wind['dir'], FILTER_SANITIZE_NUMBER_INT);
				$WS=$wind['ws'];$WG=$wind['gu'];$WC=$wind['wc'];$WT=$wind['te'];
				if($WS > $WG){$WGb = $WG; $WG = $WS; $WS = $WGb;}
				$DUNIT=$rain['id'];
				$SVALUE=$WB.';'.$WD.';'.($WS*6).';'.($WG*6).';'.$WC.';'.$WT;
				$IDX = 53;
				$URL = $domoticzurl.'type=command&param=udevice&hid=2&did=4000&idx='.$IDX.'&dunit='.$DUNIT.'&dtype=86&dsubtype=1&nvalue=0&svalue='.$SVALUE;
				file_get_contents($URL);
			};
		}
		uservariable('Read_Homewizard',0, $time);$Read_Homewizard=$time;
	}
}
uservariable('hw2domoticz',0, $time);$hw2domoticz=$time;
if($authenticated==false) if($aantalrunsperminuut > 1) sleep(60/$aantalrunsperminuut);
}
if($authenticated) {echo '<pre>';print_r(get_defined_vars());echo '</pre>';}
