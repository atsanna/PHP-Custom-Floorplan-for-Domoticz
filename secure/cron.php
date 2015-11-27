<?php 
$authenticated=false;
include "functions.php";
if($authenticated) {error_reporting(E_ALL);ini_set("display_errors", "on");}
$time=$_SERVER['REQUEST_TIME'];$timeout=$time-170;
$domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&used=true'),true);
if($domoticz) {
	foreach($domoticz['result'] as $dom) {
		isset($dom['Type'])?$Type=$dom['Type']:$Type='None';
		isset($dom['SwitchType'])?$SwitchType=$dom['SwitchType']:$SwitchType='None';
		isset($dom['SubType'])?$SubType=$dom['SubType']:$SubType='None';
		$name=$dom['Name'];
		if($Type=='Temp + Humidity'||$Type=='Temp'){${'T'.$name}=$dom['Temp'];${'TI'.$name}=$dom['idx'];${'TT'.$name}=strtotime($dom['LastUpdate']);}
		else if($SwitchType=='Dimmer'){${'DI'.$name}=$dom['idx'];$dom['Status']=='Off'?${'D'.$name}='Off':${'D'.$name}='On';$dom['Status']=='Off'?${'Dlevel'.$name}=0:${'Dlevel'.$name}=$dom['Level'];${'DT'.$name}=strtotime($dom['LastUpdate']);}
		else if($Type=='Usage'&&$dom['SubType']=='Electric') ${'P'.$name}=substr($dom['Data'],0,-5);
		else if($Type=='Radiator 1'||$Type=='Thermostat') {${'RI'.$name}=$dom['idx'];${'R'.$name}=$dom['Data'];${'RT'.$name}=strtotime($dom['LastUpdate']);}
		else {
			if(substr($dom['Data'],0,2)=='On') ${'S'.$name}='On';
			else if(substr($dom['Data'],0,3)=='Off') ${'S'.$name}='Off';
			else if(substr($dom['Data'],0,4)=='Open') ${'S'.$name}='Open';
			else ${'S'.$name}=$dom['Data'];
			${'SI'.$name}=$dom['idx'];
			${'ST'.$name}=strtotime($dom['LastUpdate']);
		}
	}
	if(xcache_get('domoticzconnection')>0) {xcache_set('domoticzconnection',0);telegram('Verbinding met Domoticz hersteld');}
	
	//Zon op / zon onder
	$zonop=strtotime($domoticz['Sunrise']);$zononder=strtotime($domoticz['Sunset']);
	unset($domoticz,$dom);
	
	//Automatische lichten inschakelen
	if(($SPIR_Garage!='Off'||$Spoort!='Closed')&&$SLicht_Garage=='Off'&&$SLicht_Garage_Auto=='On'&&$SThuis=='On'&&$SSlapen=='Off') Schakel($SILicht_Garage, 'On');
	if($SPIR_Inkom!='Off'&&$SLicht_Inkom=='Off'&&$SLicht_Hall_Auto=='On') if($SSlapen=="Off") {Schakel($SILicht_Inkom, 'On');Schakel($SILicht_Hall, 'On');} else Schakel($SILicht_Inkom, 'On');
	if($SPIR_Hall!='Off'&&($SLicht_Hall=='Off'||$SLicht_Inkom=='Off')&&$SLicht_Hall_Auto=='On') if($SSlapen=="Off") {Schakel($SILicht_Hall, 'On');Schakel($SILicht_Inkom, 'On');} else Schakel($SILicht_Inkom, 'On');
	if($SPIR_Living!='Off'&&$STV=='Off'&&$SDenon=='Off'&&$SLamp_Bureel=='Off'&&$SLicht_Hall_Auto=='On'&&$DEettafel=='Off'&&$SSlapen=="Off"&&$SThuis=='On') {
		Dim($DIEettafel, 31);Schakel($SIKeuken, 'On');
		if($time > strtotime('6:00') && $time < strtotime('7:15') && $SiMac=='Off') {Schakel($SIiMac, 'On');Schakel($SILamp_Bureel, 'On');}
	}
	
	//Meldingen
	$deurbel=false;
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
		if($SDeurbel!='Off') {$msg='Deurbel';if(xcache_get('alertDeurbel')<$time-60) {xcache_set('alertDeurbel',$time);ios($msg);} Udevice($SIDeurbel,0,'Off');if($SLicht_Hall_Auto=='On') {Schakel($SILicht_Voordeur, 'On');xcache_set('BelLichtVoordeur',2);}
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
			$voorwarmen = voorwarmen($TBadkamer,22,140);
			if(in_array(date('N',$time), array(1,2,3,4,5)) && $time>=(strtotime('6:00')-$voorwarmen) && $time<=(strtotime('7:20'))) $Set=22.0;
		     else if(in_array(date('N',$time), array(6,7)) && $time>=(strtotime('7:30')-$voorwarmen) && $time<=(strtotime('9:30'))) $Set=20.0;
		}
		if($SDeurBadkamer!='Closed' && $STDeurBadkamer < $time - 180) $Set=14.0;
		if($RBadkamer != $Set) {Udevice($RIBadkamer,0,$Set);$RBadkamer=$Set;}
		if($RTBadkamer < $time - 8600) Udevice($RIBadkamer,0,$Set);
	}
	$Set = setradiator($TBadkamer, $RBadkamer);
	if(in_array(date('N',$time), array(1,2,3,4,5)) && in_array(date('G',$time), array(4,5,6)) && $Set < 21) $Set = 21.0;
	if($RBadkamerZ!=$Set) {Udevice($RIBadkamerZ,0,$Set);}
	
	//Slaapkamer
	$Set = 4.0;
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
	if($RKamerZ!=$Set) {Udevice($RIKamerZ,0,$Set);}
	
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
	if(($TLiving < $RLiving || $TBadkamer < $RBadkamer || $TKamer < $RKamer || $TKamerTobi < $RKamerTobi || $TKamerJulius < $RKamerJulius ) && $SBrander == "Off" && $STBrander < $time-230) Schakel($SIBrander, 'On');
	if($TLiving >= $RLiving-0.3 && $TBadkamer >= $RBadkamer - 0.7 && $TKamer >= $RKamer-0.3 && $TKamerTobi >= $RKamerTobi-0.2 && $TKamerJulius >= $RKamerJulius-0.2 && $SBrander == "On" && $STBrander < $time-230) Schakel($SIBrander, 'Off');
	
	//Subwoofer
	if($SDenon=='On' && $SSubwoofer!='On')  Schakel($SISubwoofer,'On');
	else if($SDenon=='Off'&& $SSubwoofer!='Off') Schakel($SISubwoofer,'Off');
	
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
	
	//Lichten uitschakelen na 3 uur. 
	$uit = $time - 10800;
	if($STobi!='Off'&&$STTobi<$uit) Schakel($SITobi, 'Off');
	
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
	
	//Diskstation starten als bureel Tobi aan ligt
	if($SDiskStation!='On' &&($SBureel_Tobi!='Off'||$SKodiPower!='Off'||$SiMac!='Off')) {if(xcache_get('wakediskstation')<$time-900) {xcache_set('wakediskstation',$time);Schakel(72, 'On');}}
	
	//Sleep dimmers
	$dimmers = array('Eettafel','Zithoek');
	foreach($dimmers as $dimmer) {
		if(${'D'.$dimmer}!='Off') {
			$sleep = xcache_get('dimsleep'.$dimmer);
			if($sleep > 0) {
				echo 'level='.$sleep.'<br/>';
				$dim = 60/$sleep<5?5:60/$sleep;
				$dim = $dim>30?30:$dim;
				echo 'dim='.$dim.'<br/>';
				if(${'DT'.$dimmer}<$time-($dim)) {
					$sleep = $sleep - 1;
					Dim(${'DI'.$dimmer},$sleep);
					xcache_set('dimsleep'.$dimmer,$sleep);
				}
			}
		}
	}
	//Alles uitschakelen
	if($SThuis=='Off'||$SSlapen=="On") {
		if($STV!='Off'&&$STTV<$time-50) Schakel($SITV, 'Off');
		if($SKristal!='Off'&&$STKristal<$time-50) Schakel($SIKristal, 'Off');
		if($STVLed!='Off'&&$STTVLed<$time-50) Schakel($SITVLed, 'Off');
		if($SDenon!='Off'&&$STDenon<$time-50) Schakel($SIDenon, 'Off');
		if($SLamp_Bureel!='Off'&&$STLamp_Bureel<$time-50) Schakel($SILamp_Bureel, 'Off');
		if($STerras!='Off'&&$STTerras<$time-50) Schakel($SITerras, 'Off');
		if($SLicht_Garage!='Off'&&$STLicht_Garage<$time-50) Schakel($SILicht_Garage, 'Off');
		if($SLicht_Voordeur!='Off'&&$STLicht_Voordeur<$time-50) Schakel($SILicht_Voordeur, 'Off');
		if($SLicht_Hall!='Off'&&$STLicht_Hall<$time-50&&$STPIR_Hall<$time-50) Schakel($SILicht_Hall, 'Off');
		if($SLicht_Inkom!='Off'&&$STLicht_Inkom<$time-50&&$STPIR_Inkom<$time-50) Schakel($SILicht_Inkom, 'Off');
		if($SLicht_Zolder!='Off'&&$STLicht_Zolder<$time-50) Schakel($SILicht_Zolder, 'Off');
		if($SBureel_Tobi!='Off'&&$STBureel_Tobi<$time-50) Schakel($SIBureel_Tobi, 'Off');
		if($DEettafel!='Off'&&$DTEettafel<$time-50) Schakel($DIEettafel, 'Off');
		if($DZithoek!='Off'&&$DTZithoek<$time-50) Schakel($DIZithoek, 'Off');
		if($setpointLiving!=0 && $RTLiving<$time-3600) xcache_set('setpoint130',0);
		if($setpointBadkamer!=0 && $RTBadkamer<$time-3600) xcache_set('setpoint111',0);
		if($setpointKamer!=0 && $RTKamer<$time-3600) xcache_set('setpoint97',0);
		if($setpointKamerTobi!=0 && $RTKamerTobi<$time-3600) xcache_set('setpoint548',0);
		if($setpointKamerJulius!=0 && $RTKamerJulius<$time-3600) xcache_set('setpoint549',0);
		if($SKeuken!='Off'&&$STKeuken<$time-50) Schakel($SIKeuken, 'Off');
		if($SZolderG!='Off'&&$STZolderG<$time-50) Schakel($SIZolderG, 'Off');
		//if($SWerkblad!='Off'&&$STWerkblad<$time-50) Schakel($SIWerkblad, 'Off');
		if($SiMac!='Off'&&$STiMac<$time-300) Schakel($SIiMac, 'Off');
	}	
	if($SThuis=='Off') {
		if($SLichtBadkamer1!='Off'&&$STBadkamer1<$time-50) Schakel($SIBadkamer1, 'Off');
	}
	if($STobi!='Off'&&$STTobi<time-7200) Schakel($SITobi, 'Off');
	
	//Meldingen inschakelen indien langer dan 12 uur uit. 
	if($SMeldingen!='On' && $STMeldingen<$time-43200) Schakel($SIMeldingen, 'On');

	//Refresh Zwave node
	//if($STTV>$time-5||$STSubwoofer>$time-5||$STLamp_Bureel>$time-5) RefreshZwave(61);
	if($STLichtTerrasGarage>$time-5) RefreshZwave(16);
	if($STLichtHallZolder>$time-5) RefreshZwave(20);
	if($STLichtInkomVoordeur>$time-5) RefreshZwave(23);
	if($STBureel_Tobi>$time-5) RefreshZwave(24);
	if($STPluto>$time-5) RefreshZwave(53);
	if($STKeukenZolder>$time-5) RefreshZwave(56);
	//if($STKeukenTuin>$time-5) RefreshZwave(59);
	if($STLichtBadkamer>$time-5) RefreshZwave(65);
	
	//KODI
	if($SLicht_Hall_Auto=='On'&&$SKodi=='On'&&$STV=='On') {
		$urltitle = urlencode('{"jsonrpc": "2.0", "method": "Player.GetItem", "params": { "properties": [], "playerid": 1 }, "id": "VideoGetItem"}');
		$urlspeed = urlencode('{"jsonrpc": "2.0", "method": "Player.GetProperties", "params": { "playerid": 1,"properties": ["speed"] }, "id": 1}');
//		for ($k = 0 ; $k < 15; $k++){
			$title = json_decode(curl('http://192.168.0.7:1597/jsonrpc?request='.$urltitle));
			if(isset($title->result->item->label)) {
				if($title->result->item->label!='') {
					$speed = json_decode(curl('http://192.168.0.7:1597/jsonrpc?request='.$urlspeed));
					if($speed->result->speed==0) {
						if($DlevelZithoek<20) {Dim($DIZithoek, 21);$DlevelZithoek=20;}
						if($DlevelEettafel<24) {Dim($DIEettafel, 25);$DlevelEettafel=24;}
					} else {
						if($DlevelZithoek == 20) {Schakel($DIZithoek, 'Off');$DlevelZithoek=0;}
						if($DlevelEettafel == 24) {Schakel($DIEettafel, 'Off');$DlevelEettafel=0;}
					}
				}
			} else {
				if($DlevelZithoek<20) {Dim($DIZithoek, 21);$DlevelZithoek=20;}
				if($DlevelEettafel<24) {Dim($DIEettafel, 25);$DlevelEettafel=24;}
			}
//			sleep(2);
//		}
	}
		
	
	//End Acties
} else {
	$domoticzconnection = xcache_get('domoticzconnection');
	$domoticzconnection = $domoticzconnection + 1;
	xcache_set('domoticzconnection',$domoticzconnection);
	if($domoticzconnection==1) telegram('Geen verbinding met Domoticz');
	if($domoticzconnection>15) {
		xcache_set('domoticzconnection',0);
		$output = shell_exec('/var/www/secure/restart_domoticz');
		telegram($output);
	}
}

if($authenticated) {
	echo '<hr>Number of vars: '.count(get_defined_vars()).'<br/><pre>';print_r(get_defined_vars());echo '</pre>';
}
