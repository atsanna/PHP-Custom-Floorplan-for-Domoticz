<?php 
$authenticated=false;
include "functions.php";
if($authenticated) {error_reporting(E_ALL);ini_set("display_errors", "on");}
$time=$_SERVER['REQUEST_TIME'];$timeout=$time-170;
$domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&filter=all&used=true&plan=3'),true);
if($domoticz) {
	foreach($domoticz['result'] as $dom) {
		isset($dom['Type'])?$Type=$dom['Type']:$Type='None';
		isset($dom['SwitchType'])?$SwitchType=$dom['SwitchType']:$SwitchType='None';
		isset($dom['SubType'])?$SubType=$dom['SubType']:$SubType='None';
		$name=$dom['Name'];
		if($Type=='Temp + Humidity'||$Type=='Temp'){${'T'.$name}=$dom['Temp'];${'TI'.$name}=$dom['idx'];$dom['BatteryLevel']>100?${'TB'.$name}=100:${'TB'.$name}=$dom['BatteryLevel'];${'TT'.$name}=strtotime($dom['LastUpdate']);}
		else if($SwitchType=='Dimmer'){${'DI'.$name}=$dom['idx'];$dom['Status']=='Off'?${'D'.$name}='Off':${'D'.$name}='On';$dom['Status']=='Off'?${'Dlevel'.$name}=0:${'Dlevel'.$name}=$dom['Level'];${'DT'.$name}=strtotime($dom['LastUpdate']);}
		else if($dom['HardwareName']=='Kodi') {$Kodi=$dom;}
		else if($Type=='Rain') $Regen=$dom['Rain'];
		else if($Type=='Usage'&&$dom['SubType']=='Electric') ${'P'.$name}=substr($dom['Data'],0,-5);
		else if($Type=='Radiator 1'||$Type=='Thermostat') {${'RI'.$name}=$dom['idx'];${'R'.$name}=$dom['Data'];${'RT'.$name}=strtotime($dom['LastUpdate']);$dom['BatteryLevel']>100?${'RB'.$name}=100:${'RB'.$name}=$dom['BatteryLevel'];}
		else {
			if(substr($dom['Data'],0,2)=='On') ${'S'.$name}='On';
			else if(substr($dom['Data'],0,3)=='Off') ${'S'.$name}='Off';
			else if(substr($dom['Data'],0,4)=='Open') ${'S'.$name}='Open';
			else ${'S'.$name}=$dom['Data'];
			${'SI'.$name}=$dom['idx'];
			${'ST'.$name}=strtotime($dom['LastUpdate']);
			$dom['BatteryLevel']>100?${'SB'.$name}=100:${'SB'.$name}=$dom['BatteryLevel'];
		}
	}
	if(xcache_get('domoticzconnection')>0) {xcache_set('domoticzconnection',0);telegram('Verbinding met Domoticz hersteld');}
	
	//Zon op / zon onder
	$zonop=strtotime($domoticz['Sunrise']);$zononder=strtotime($domoticz['Sunset']);
	unset($domoticz,$dom);
	
	//Automatische lichten inschakelen
	if(($SPIR_Garage!='Off'||$Spoort!='Closed')&&$SLicht_Garage=='Off'&&$SLicht_Garage_Auto=='On') Schakel($SILicht_Garage, 'On');
	if($SPIR_Inkom!='Off'&&$SLicht_Inkom=='Off'&&$SLicht_Hall_Auto=='On') ($SSlapen=="Off") ?Scene(6):Schakel($SILicht_Inkom, 'On');
	if($SPIR_Hall!='Off'&&($SLicht_Hall=='Off'||$SLicht_Inkom=='Off')&&$SLicht_Hall_Auto=='On') ($SSlapen=="Off") ?Scene(6):Schakel($SILicht_Inkom, 'On');
	if($SPIR_Living!='Off'&&$STV=='Off'&&$SDenon=='Off'&&$SLamp_Bureel=='Off'&&$SLicht_Hall_Auto=='On'&&$DEettafel=='Off'&&$SSlapen=="Off") Dim($DIEettafel, 40);
	
	//Meldingen
	$deurbel=false;
	if($SMeldingen!='On' && $STMeldingen<$time-43200) Schakel($SIMeldingen, 'On');
	if(($SThuis=='Off'||$SSlapen=='On') && $STThuis<$timeout && $SMeldingen=='On') {
		if($Spoort!='Closed') {$msg='Poort open om '.strftime("%H:%M:%S", $STpoort);$deurbel=true;if(xcache_get('alertpoort')<$time-60) {xcache_set('alertpoort', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SAchterdeur!='Closed') {$msg='Achterdeur open om '.strftime("%H:%M:%S", $STAchterdeur);$deurbel=true;if(xcache_get('alertAchterdeur')<$time-60) {xcache_set('alertAchterdeur', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SRaamLiving!='Closed') {$msg='Raam Living open om '.strftime("%H:%M:%S", $STRaamLiving);$deurbel=true;if(xcache_get('alertRaamLiving')<$time-60) {xcache_set('alertRaamLiving', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SPIR_Garage!='Off'&&$STSlapen<$timeout) {$msg='Beweging gedecteerd in garage om '.strftime("%H:%M:%S", $STPIR_Garage);$deurbel=true;if(xcache_get('alertPIR_Garage')<$time-60) {xcache_set('alertPIR_Garage', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SPIR_Living!='Off'&&$STSlapen<$timeout) {$msg='Beweging gedecteerd in living om '.strftime("%H:%M:%S", $STPIR_Living);$deurbel=true;if(xcache_get('alertPIR_Living')<$time-90) {xcache_set('alertPIR_Living', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SPIR_Inkom!='Off'&&$STSlapen<$timeout) {$msg='Beweging gedecteerd in inkom om '.strftime("%H:%M:%S", $STPIR_Living);$deurbel=true;if(xcache_get('alertPIR_Living')<$time-90) {xcache_set('alertPIR_Living', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	if($SThuis=='Off' && $STThuis<$timeout && $SMeldingen=='On') {
		if($SPIR_Hall!='Off') {$msg='Beweging gedecteerd in hall om '.strftime("%H:%M:%S", $STPIR_Living);$deurbel=true;if(xcache_get('telegramPIR_Hall')<$time-90) {xcache_set('alertAchterdeur', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	if($deurbel&&$STDeurbel<$time-5) {schakel($SIDeurbel, 'On');Udevice($SIDeurbel, 0, 'Off');}
	
	//Meldingen
	if($SMeldingen=='On') {
		$thermometers=array('Living','Badkamer','KamerTobi','KamerJulius','Zolder');
		$avg=0;
		foreach($thermometers as $thermometer) $avg=$avg+${'T'.$thermometer};
		$avg=$avg / 6;
		foreach($thermometers as $thermometer) {
			if(${'T'.$thermometer}>$avg + 5 && ${'T'.$thermometer} > 25) {$msg='T '.$thermometer.'='.${'T'.$thermometer}.'°C. AVG='.round($avg,1).'°C';
				if(xcache_get('alerttemp'.$thermometer)<$time-600) {xcache_set('alerttemp'.$thermometer, $time);telegram($msg);ios($msg);if($sms==true) sms($msg);}
			}
		}
		unset($thermometers,$thermometer);
		if($PP_Bureel_Tobi>500) {if(xcache_get('alertpowerbureeltobi')<$time-1800) {$msg='Verbruik bureel Tobi='.$PP_Bureel_Tobi;telegram($msg);xcache_set('alertpowerbureeltobi', $time);}}
		if($Spoort!='Closed') {if(xcache_get('alertpoort')<$time-900&&$STpoort<$time-900) {$msg='Poort Open sinds '.strftime("%H:%M:%S", $STpoort);xcache_set('alertpoort',$time);telegram($msg);}}
		if($SSD_Zolder_Smoke!= 'Off') {$msg='Rook gedecteerd op de zolder!';if(xcache_get('alertSD_Zolder_Smoke')<$time-90) {xcache_set('alertSD_Zolder_Smoke',$time);telegram($msg);ios($msg);if($sms==true) sms($msg);}}
		if($SDeurbel!='Off') {$msg='Deurbel';if(xcache_get('alertDeurbel')<$time-30) {xcache_set('alertDeurbel',$time);telegram($msg);ios($msg);} Udevice($SIDeurbel,0,'Off');if($SLicht_Hall_Auto=='On') {Schakel($SILicht_Voordeur, 'On');xcache_set('BelLichtVoordeur',2);}
		}
	}
	
	//Heating on/off
	if($SThuis=='Off') {
		if($SHeating!='Off'&&$STHeating<$time-3600) {Schakel($SIHeating, 'Off');$SHeating = 'Off';}
	} else {
		if($SHeating!='On') {Schakel($SIHeating, 'On');$SHeating = 'On';}
	}
	
	// 0 = auto, 1 = voorwarmen, 2 = manueel
	//Living
	$Set=16.0;
	$setpointLiving = xcache_get('setpoint130');
	if($setpointLiving!=0 && $RTLiving < $time - 43200) {xcache_set('setpoint130',0);$setpointLiving=0;}
	if($setpointLiving!=2) {
		if($TBuiten<20 && $SHeating=='On' && $SRaamLiving=='Closed') {
				$voorwarmen = voorwarmen($TLiving,20,60);
			     if($time>=( strtotime('6:20')-$voorwarmen) && $time < strtotime('8:30')) $SSlapen=='Off'?$Set=19.0:$Set=18.0;
			else if($time>=( strtotime('8:30')-$voorwarmen) && $time < strtotime('19:00')) $SSlapen=='Off'?$Set=20.0:$Set=18.0;
			else if($time>=(strtotime('19:00')-$voorwarmen) && $time < strtotime('22:00')) $SSlapen=='Off'?$Set=21.0:$Set=18.0;
		}
		if($RLiving != $Set) {Udevice($RILiving,0,$Set);}
		if($RTLiving < $time - 8600) Udevice($RILiving,0,$Set);
	}
	$Set = setradiator($TLiving, $RLiving);
	if($RLivingZZ!=$Set) {Udevice($RILivingZZ, 0, $Set);}
	if($RLivingZE!=$Set) {Udevice($RILivingZE, 0, $Set);}
	if($RLivingZB!=$Set) {Udevice($RILivingZB, 0, $Set);}
	
	//Badkamer
	$Set=16.0;
	$setpointBadkamer = xcache_get('setpoint111');
	if($setpointBadkamer!=0 && $RTBadkamer < $time - 3600) {xcache_set('setpoint111',0);$setpointBadkamer=0;}
	if($setpointBadkamer!=2) {
		if($TBuiten<21 && $SHeating=='On') {
			$voorwarmen = voorwarmen($TBadkamer,22,120);
			if(in_array(date('N',$time), array(1,2,3,4,5)) && $time>=(strtotime('5:30')-$voorwarmen) && $time<=(strtotime('6:20'))) $Set=20.0;
			else if(in_array(date('N',$time), array(1,2,3,4,5)) && $time>=(strtotime('6:20')-$voorwarmen) && $time<=(strtotime('7:30'))) $Set=22.0;
			 else if(in_array(date('N',$time), array(6,7)) && $time>=(strtotime('7:30')-$voorwarmen) && $time<=(strtotime('9:30'))) $Set=20.0;
		}
		if($SDeurBadkamer!='Closed' && $STDeurBadkamer < $time - 180) $Set=14.0;
		if($RBadkamer != $Set) {Udevice($RIBadkamer,0,$Set);$RBadkamer=$Set;}
		if($RTBadkamer < $time - 8600) Udevice($RIBadkamer,0,$Set);
	}
	$Set = setradiator($TBadkamer, $RBadkamer);
	if(in_array(date('N',$time), array(1,2,3,4,5)) && in_array(date('G',$time), array(4,5,6)) && $Set < 20) $Set = 20.0;
	if($RBadkamerZ!=$Set) {Udevice($RIBadkamerZ,0,$Set);}
	
	//Slaapkamer
	/*$Set = 4.0;
	$setpointKamer = xcache_get('setpoint97');
	if($setpointKamer!=0 && $RTKamer < $time - 3600) {xcache_set('setpoint97',0);$setpointKamer=0;}
	if($setpointKamer!=2) {
		if($TBuiten<15 && $SRaamKamer=='Closed' && $SHeating=='On' ) {
			$Set = 12.0;
			if($time < strtotime('8:00') || $time > strtotime('20:00')) $Set = 16.0;
		}
	}
	if($RKamer != $Set) {Udevice($RIKamer,0,$Set);$RKamer=$Set;}
	if($RTKamer < $time - 8600) Udevice($RIKamer,0,$Set);
	$Set = setradiator($TKamer, $RKamer);
	if($RKamerZ!=$Set) {Udevice($RIKamerZ,0,$Set);}*/
	
	//Slaapkamer Tobi
	$Set = 4.0;
	$setpointKamerTobi = xcache_get('setpoint548');
	if($setpointKamerTobi!=0 && $RTKamerTobi < $time - 3600) {xcache_set('setpoint549',0);$setpointKamerTobi=0;}
	if($setpointKamerTobi!=2) {
		if($TBuiten<15 && $SRaamKamerTobi=='Closed' && $SHeating=='On') {
			$Set = 12.0;
			if (date('W')%2==1) {
					 if (date('N') == 3) { if($SThuis=='On' && $time > strtotime('20:00')) $Set = 16.0; }
				else if (date('N') == 4) { if($SThuis=='On' && ($time < strtotime('8:00') || $time > strtotime('20:00'))) $Set = 16.0; }
				else if (date('N') == 5) { if($SThuis=='On' && $time < strtotime('8:00')) $Set = 16.0; }
			} else {
					 if (date('N') == 3) { if($SThuis=='On' && $time > strtotime('20:00')) $Set = 16.0; }
				else if (in_array(date('N'),array(4,5,6))) { if($SThuis=='On' && ($time < strtotime('8:00') || $time > strtotime('20:00'))) $Set = 16.0; }
				else if (date('N') == 7) { if($SThuis=='On' && $time < strtotime('8:00')) $Set = 16.0; }
			}
		}
	}
	if($RKamerTobi != $Set) {Udevice($RIKamerTobi,0,$Set);$RKamerTobi=$Set;}
	if($RTKamerTobi < $time - 8600) Udevice($RIKamerTobi,0,$Set);
	$Set = setradiator($TKamerTobi, $RKamerTobi);
	if($RKamerTobiZ!=$Set) {Udevice($RIKamerTobiZ,0,$Set);}
	
	//Slaapkamer Julius
	$Set = 4.0;
	$setpointKamerJulius = xcache_get('setpoint549');
	if($setpointKamerJulius!=0 && $RTKamerJulius < $time - 3600) {xcache_set('setpoint549',0);$setpointKamerJulius=0;}
	if($setpointKamerJulius!=2) {
		if($TBuiten<15 && $SRaamKamerJulius=='Closed' && $SHeating=='On') {
			$Set = 12.0;
		}
	}
	if($RKamerJulius != $Set) {Udevice($RIKamerJulius,0,$Set);$RKamerJulius=$Set;}
	if($RTKamerJulius < $time - 8600) Udevice($RIKamerJulius,0,$Set);
	$Set = setradiator($TKamerJulius, $RKamerJulius);
	if($RKamerJuliusZ!=$Set) {Udevice($RIKamerJuliusZ,0,$Set);}
	
	//Brander
	if(($TLiving < $RLiving || $TBadkamer < $RBadkamer || /*$TKamer < $RKamer || */$TKamerTobi < $RKamerTobi || $TKamerJulius < $RKamerJulius ) && $SBrander == "Off" && $STBrander < $time-230) Schakel($SIBrander, 'On');
	if($TLiving >= $RLiving-0.3 && $TBadkamer >= $RBadkamer - 0.7 && /*$TKamer >= $RKamer-0.3 && */$TKamerTobi >= $RKamerTobi-0.2 && $TKamerJulius >= $RKamerJulius-0.2 && $SBrander == "On" && $STBrander < $time-230) Schakel($SIBrander, 'Off');
	
	//Subwoofer
	if($SDenon=='On' && $SSubwoofer!='On')  Schakel($SISubwoofer,'On');
	else if($SDenon=='Off'&& $SSubwoofer!='Off') Schakel($SISubwoofer,'Off');
	
	//KODI
	if($SLicht_Hall_Auto=='On') {
		if($Kodi['Status']=='Video') {
			if($DlevelZithoek == 20) Schakel($DIZithoek, 'Off');
			if($DlevelEettafel == 24) Schakel($DIEettafel, 'Off');
		}
		else if($Kodi['Status']=='Paused' && $SThuis=='On' && $SSlapen=="Off") {
			if($DlevelZithoek<20) Dim($DIZithoek, 21);
			if($DlevelEettafel<24) Dim($DIEettafel, 25);
		}
	}
	
	//PIRS resetten
	if($SPIR_Living!='Off'&&$STPIR_Living<$time-30) Schakel($SIPIR_Living,'Off');
	if($SPIR_Garage!='Off'&&$STPIR_Garage<$timeout) Schakel($SIPIR_Garage,'Off');
	if($SPIR_Inkom!='Off'&&$STPIR_Inkom<$timeout) Schakel($SIPIR_Inkom,'Off');
	if($SPIR_Hall!='Off'&&$STPIR_Hall<$timeout) Schakel($SIPIR_Hall,'Off');
	
	//Automatische lichten uitschakelen
	if($STLicht_Garage_Auto < $time-7200) {
		if($time>$zonop+10800 && $time<$zononder-10800) {if($SLicht_Garage_Auto=='On' && $SLicht_Garage=='Off') Schakel($SILicht_Garage_Auto,'Off');}
		else if($SLicht_Garage_Auto=='Off') Schakel($SILicht_Garage_Auto,'On');}
	if($STLicht_Hall_Auto < $time-7200) {
		if($time>$zonop+1800 && $time<$zononder-1800) {if($SLicht_Hall_Auto=='On' && $SLicht_Hall=='Off' && $SLicht_Inkom=='Off') Schakel($SILicht_Hall_Auto,'Off');}
		else if($SLicht_Hall_Auto=='Off') Schakel($SILicht_Hall_Auto,'On');}
	if($SPIR_Living=='Off'&&$STV=='Off'&&$SDenon=='Off'&&$DEettafel!='Off'&&$STPIR_Living<$time-600&&$DTEettafel<$time-600) Schakel($DIEettafel,'Off');
	if($SPIR_Garage=='Off'&&$Spoort=='Closed'&&$STPIR_Garage<$time-180&&$STpoort<$timeout&&$STLicht_Garage<$time-60&&$SLicht_Garage=='On'&&$SLicht_Garage_Auto=='On') Schakel($SILicht_Garage,'Off');
	if($STPIR_Inkom<$time-120&&$STPIR_Hall<$time-120&&$STLicht_Inkom<$time-60&&$STLicht_Hall<$time-60&&$SLicht_Hall_Auto=='On') {
		if($SLicht_Inkom=='On') Schakel($SILicht_Inkom,'Off');
		if($SLicht_Hall=='On') Schakel($SILicht_Hall,'Off');}
	if($SLicht_Voordeur!='Off') {if(xcache_get('BelLichtVoordeur')==2) Schakel($SILicht_Voordeur,'Off');} 
	
	//Slapen-niet thuis bij geen beweging
	if($STPIR_Living<$time-14400&&$STPIR_Garage<$time-14400&&$STPIR_Inkom<$time-14400&&$STPIR_Hall<$time-14400&&$STSlapen<$time-14400&&$STThuis<$time-14400&&$SThuis=='On'&&$SSlapen=="Off") {Schakel($SISlapen,'On');telegram('Slapen ingeschakeld na 4 uur geen beweging');}
	if($STPIR_Living<$time-43200&&$STPIR_Garage<$time-43200&&$STPIR_Inkom<$time-43200&&$STPIR_Hall<$time-43200&&$STSlapen<$time-28800&&$STThuis<$time-43200&&$SThuis=='On'&&$SSlapen=="On") {Schakel($SISlapen, 'Off');Schakel($SIThuis, 'Off');telegram('Thuis uitgeschakeld na 12 uur geen beweging');}
	
	//Laptop Pluto zonnepanelen
	if($time > $zonop + 3600 && $time < $zononder + 4000) {
		if($SPluto=='Off') Schakel($SIPluto, 'On');
	} else {
		if($SPluto=='On') Schakel($SIPluto, 'Off');
	}
	
	//Buienradar
	if(xcache_get('buienradar')<$time-290) {
		$rains=file_get_contents('http://gps.buienradar.nl/getrr.php?lat=50.892880&lon=3.112568');
		$rains=str_split($rains, 11);$totalrain=0;$aantal=0;
		foreach($rains as $rain) {$aantal=$aantal+1;$totalrain=$totalrain+substr($rain,0,3);$averagerain=round($totalrain/$aantal,0);if($aantal==12) break;}
		echo 'rain : '.$averagerain.'<br/>';
		xcache_set('averagerain',$averagerain);xcache_set('buienradar', $time);}
	
	//Openweathermap
	if(xcache_get('openweathermap')<$time-110) {
		$openweathermap=file_get_contents('http://api.openweathermap.org/data/2.5/weather?id=2787891&APPID=ac3485b0bf1a02a81d2525db6515021d&units=metric');
		$openweathermap=json_decode($openweathermap,true);
		if(isset($openweathermap['weather']['0']['icon'])) {
			xcache_set('weatherimg',$openweathermap['weather']['0']['icon']);
			xcache_set('openweathermap',$time);
			file_get_contents($domoticzurl.'type=command&param=udevice&idx=36&nvalue=0&svalue='.round($openweathermap['main']['temp'],1));
		}
	}
	
	//Refresh Zwave node
	if($STTV>$time-15) RefreshZwave(10);
	if($STLichtHallZolder>$time-15) RefreshZwave(20);
	if($STLichtInkomVoordeur>$time-15) RefreshZwave(23);
	if($STLichtTerrasGarage>$time-15) RefreshZwave(16);
	
	//Diskstation starten als bureel Tobi aan ligt
	if($SBureel_Tobi!='Off'||$STV!='Off') {if(xcache_get('wakediskstation')<$time-900) {xcache_set('wakediskstation',$time);Schakel(72, 'On');}}
	
	//Sleep dimmers
	$dimmers = array('Eettafel','Zithoek');
	foreach($dimmers as $dimmer) {
		if(${'D'.$dimmer}!='Off') {
			$sleep = xcache_get('dimsleep'.$dimmer);
			echo 'level='.$sleep.'<br/>';
			$dim = 60/$sleep<5?5:60/$sleep;
			$dim = $dim>30?30:$dim;
			echo 'dim='.$dim.'<br/>';
			if($sleep > 0 && ${'DT'.$dimmer}<$time-($dim)) {
				$sleep = $sleep - 1;
				Dim(${'DI'.$dimmer},$sleep);
				xcache_set('dimsleep'.$dimmer,$sleep);
			}
		}
	}
	//Alles uitschakelen
	if($SThuis=='Off'||$SSlapen=="On") {
		if($STV!='Off'&&$STTV<$time-20) Schakel($SITV, 'Off');
		if($SDenon!='Off'&&$STDenon<$time-20) Schakel($SIDenon, 'Off');
		if($SLamp_Bureel!='Off'&&$STLamp_Bureel<$time-20) Schakel($SILamp_Bureel, 'Off');
		if($STerras!='Off'&&$STTerras<$time-20) Schakel($SITerras, 'Off');
		if($SLicht_Garage!='Off'&&$STLicht_Garage<$time-20) Schakel($SILicht_Garage, 'Off');
		if($SLicht_Voordeur!='Off'&&$STLicht_Voordeur<$time-20) Schakel($SILicht_Voordeur, 'Off');
		if($SLicht_Hall!='Off'&&$STLicht_Hall<$time-50&&$STPIR_Hall<$time-50) Schakel($SILicht_Hall, 'Off');
		if($SLicht_Inkom!='Off'&&$STLicht_Inkom<$time-30&&$STPIR_Inkom<$time-50) Schakel($SILicht_Inkom, 'Off');
		if($SLicht_Zolder!='Off'&&$STLicht_Zolder<$time-20) Schakel($SILicht_Zolder, 'Off');
		if($SBureel_Tobi!='Off'&&$STBureel_Tobi<$time-20) Schakel($SIBureel_Tobi, 'Off');
		if($DEettafel!='Off'&&$DTEettafel<$time-20) Schakel($DIEettafel, 'Off');
		if($DZithoek!='Off'&&$DTZithoek<$time-20) Schakel($DIZithoek, 'Off');
		if($setpointLiving!=0 && $RTLiving<$time-3600) xcache_set('setpoint130',0);
		if($setpointBadkamer!=0 && $RTBadkamer<$time-3600) xcache_set('setpoint111',0);
		if($setpointKamer!=0 && $RTKamer<$time-3600) xcache_set('setpoint97',0);
		if($setpointKamerTobi!=0 && $RTKamerTobi<$time-3600) xcache_set('setpoint548',0);
		if($setpointKamerJulius!=0 && $RTKamerJulius<$time-3600) xcache_set('setpoint549',0);
	}	
	
	//battery levels
	if(xcache_get('batteries')<$time-3600) {
		if($SBPIRInkombat>=0&&$SBPIRInkombat<=100) {percentdevice(398,$SBPIRInkombat);if($SBPIRInkombat<20) telegram('Bat PIRInkom low');}
		if($SBPIRHallbat>=0&&$SBPIRHallbat<=100) {percentdevice(399,$SBPIRHallbat);if($SBPIRHallbat<20) telegram('Bat PIRIHall low');}
		if($SBPIRGaragebat>=0&&$SBPIRGaragebat<=100) {percentdevice(400,$SBPIRGaragebat);if($SBPIRGaragebat<20) telegram('Bat PIRGarage low');}
		if($RBLivingZE>=0&&$RBLivingZE<=100) {percentdevice(401,$RBLivingZE);if($RBLivingZE<20) telegram('Bat LivingZE low');}
		if($RBLivingZZ>=0&&$RBLivingZZ<=100) {percentdevice(402,$RBLivingZZ);if($RBLivingZZ<20) telegram('Bat LivingZZ low');}
		if($RBLivingZB>=0&&$RBLivingZB<=100) {percentdevice(402,$RBLivingZB);if($RBLivingZB<20) telegram('Bat LivingZB low');}
		if($RBBadkamerZ>=0&&$RBBadkamerZ<=100) {percentdevice(403,$RBBadkamerZ);if($RBBadkamerZ<20) telegram('Bat BadkamerZ low');}
		if($SBAchterdeurbat>=0&&$SBAchterdeurbat<=100) {percentdevice(404,$SBAchterdeurbat);if($SBAchterdeurbat<20) telegram('Bat Achterdeur low');}
		if($SBDeurBadkamerbat>=0&&$SBDeurBadkamerbat<=100) {percentdevice(568,$SBDeurBadkamer);if($SBDeurBadkamer<20) telegram('Bat Deur Badkamer low');}
		if($TBZolder>=0&&$TBZolder<=100) {percentdevice(406,$TBZolder);if($TBZolder<20) telegram('Bat SD Zolder low');}
		if($TBLiving>=0&&$TBLiving<=100) {percentdevice(552,$TBLiving);if($TBLiving<20) telegram('Bat SD Living low');}
		//if($TBKamer>=0&&$TBKamer<=100) {percentdevice(552,$TBKamer);if($TBKamer<20) telegram('Bat SD Kamer low');}
		if($TBKamerTobi>=0&&$TBKamerTobi<=100) {percentdevice(553,$TBKamerTobi);if($TBKamerTobi<20) telegram('Bat SD Kamer Tobi low');}
		if($TBKamerJulius>=0&&$TBKamerJulius<=100) {percentdevice(554,$TBKamerJulius);if($TBKamerJulius<20) telegram('Bat SD Kamer Julius low');}
		if($SBRaamLiving>=0&&$SBRaamLiving<=100) {percentdevice(567,$SBRaamLiving);if($SBRaamLiving<20) telegram('Bat Raam Living low');}
		if($SBRaamKamer>=0&&$SBRaamKamer<=100) {percentdevice(569,$SBRaamKamer);if($SBRaamKamer<20) telegram('Bat Raam Kamer low');}
		if($SBRaamKamerTobi>=0&&$SBRaamKamerTobi<=100) {percentdevice(570,$SBRaamKamerTobi);if($SBRaamKamerTobi<20) telegram('Bat Raam Kamer Tobi low');}
		if($SBRaamKamerJulius>=0&&$SBRaamKamerJulius<=100) {percentdevice(571,$SBRaamKamerJulius);if($SBRaamKamerJulius<20) telegram('Bat Raam Kamer Julius low');}
		RefreshZwave(10);
		RefreshZwave(20);
		RefreshZwave(23);
		RefreshZwave(16);
		xcache_set('batteries',$time);
	}
	
	//End Acties
} else {
	$domoticzconnection = xcache_get('domoticzconnection');
	$domoticzconnection = $domoticzconnection + 1;
	xcache_set('domoticzconnection',$domoticzconnection);
	if($domoticzconnection==1) telegram('Geen verbinding met Domoticz');
	if($domoticzconnection>5) {
		xcache_set('domoticzconnection',0);
		$output = shell_exec('/var/www/secure/restart_domoticz');
		telegram($output);
	}
}

if($authenticated) {
	echo '<hr>Number of vars: '.count(get_defined_vars()).'<br/><pre>';print_r(get_defined_vars());echo '</pre>';
}
