#!/usr/bin/php
<?php $start=microtime(true);
$authenticated=false;
include "functions.php";
if($authenticated) {error_reporting(E_ALL);ini_set("display_errors", "on");}
$time=$_SERVER['REQUEST_TIME'];$timeout=$time-170;
isset($_GET['all'])||isset($argv[1])?$all=true:$all=false;
$domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&used=true&plan=5'),true);
if($all) $domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&used=true'),true);
$domotime=microtime(true)-$start;
if($domoticz) {
	foreach($domoticz['result'] as $dom) {
		isset($dom['Type'])?$Type=$dom['Type']:$Type='None';
		isset($dom['SwitchType'])?$SwitchType=$dom['SwitchType']:$SwitchType='None';
		isset($dom['SubType'])?$SubType=$dom['SubType']:$SubType='None';
		$name=strtolower($dom['Name']);
		if($Type=='Temp'){${'T'.$name}=$dom['Temp'];${'TI'.$name}=$dom['idx'];${'TT'.$name}=strtotime($dom['LastUpdate']);}
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
	//Zon op / zon onder
	$zonop=strtotime($domoticz['Sunrise']);$zononder=strtotime($domoticz['Sunset']);
	$vijfsec=$time-5;$eenmin=$time-55;$tweemin=$time-115;$driemin=$time-175;$vijfmin=$time-295;$halfuur=$time-1795;$eenuur=$time-3595;$tweeuur=$time-7195;$drieuur=$time-10795;
	
	//Automatische lichten inschakelen
	if($Sthuis=='On') {
		if($Sslapen=='Off') {
			if(($Spir_garage!='Off'||$Spoort!='Closed')&&$Slicht_garage=='Off'&&$Slicht_garage_auto=='On') Schakel($SIlicht_garage, 'On');
			if($Spir_inkom!='Off'&&$Slicht_inkom=='Off'&&$Slicht_hall_auto=='On') {Schakel($SIlicht_inkom, 'On');Schakel($SIlicht_hall, 'On');} 
			if($Spir_hall!='Off'&&($Slicht_hall=='Off'||$Slicht_inkom=='Off')&&$Slicht_hall_auto=='On') {Schakel($SIlicht_hall, 'On');Schakel($SIlicht_inkom, 'On');}
			if($Spir_living!='Off'&&$Stv=='Off'&&$Sdenon=='Off'&&$Slamp_bureel=='Off'&&$Slicht_hall_auto=='On'&&$Deettafel=='Off') {
				if($Swerkblad!='On') Schakel($SIwerkblad, 'On');
				if($Deettafel<9) Dim($DIeettafel, 9);
				if($Skerstboom=='Off') Schakel($SIkerstboom, 'On');
				if($time > strtotime('6:00') && $time < strtotime('7:15')) {
					shell_exec('wakeonlan 3c:07:54:22:34:17');
					shell_exec('wakeonlan 00:11:32:2c:b7:21');
					if($Simac=='Off') Schakel($SIimac, 'On');
					if($Slamp_bureel=='Off') Schakel($SIlamp_bureel, 'On');
				}
			}
			if($Sdeurbadkamer=='Open'&&$STdeurbadkamer>$time-10&&$STlichtbadkamer1<$time-60&&$Slichtbadkamer1!='On'&&$STlichtbadkamer2<$time-60&&$Slichtbadkamer2=='Off'&&$Slicht_hall_auto=='On') Schakel($SIlichtbadkamer1, 'On');
		} else if ($Sslapen=='On') {
			if($Spir_inkom!='Off'&&$Slicht_inkom=='Off'&&$Slicht_hall_auto=='On') Schakel($SIlicht_inkom, 'On');
			if($Spir_hall!='Off'&&$Slicht_inkom=='Off'&&$Slicht_hall_auto=='On') Schakel($SIlicht_inkom, 'On');
			if($Sdeurbadkamer=='Open'&&$STdeurbadkamer>$time-10&&$STlichtbadkamer2<$time-60&&$Slichtbadkamer2!='On'&&$STlichtbadkamer1<$time-60&&$Slichtbadkamer1=='Off') {
				if($time > strtotime('6:00') && $time < strtotime('12:00')) Schakel($SIlichtbadkamer1, 'On'); else Schakel($SIlichtbadkamer2, 'On'); 
			}
		}
	}
	
	//meldingen
	$deurbel=false;
	if(($Sthuis=='Off'||$Sslapen=='On') && $STthuis<$timeout && $Smeldingen=='On') {
		if($Spoort!='Closed') {$msg='Poort open om '.strftime("%H:%M:%S", $STpoort);$deurbel=true;if($mc->get('alertpoort')<$time-60) {$mc->set('alertpoort', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SAchterdeur!='Closed') {$msg='Achterdeur open om '.strftime("%H:%M:%S", $STAchterdeur);$deurbel=true;if($mc->get('alertAchterdeur')<$time-60) {$mc->set('alertAchterdeur', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Sraamliving!='Closed') {$msg='raam living open om '.strftime("%H:%M:%S", $STraamliving);$deurbel=true;if($mc->get('alertraamliving')<$time-60) {$mc->set('alertraamliving', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Spir_garage!='Off'&&$STslapen<$timeout) {$msg='Beweging gedecteerd in garage om '.strftime("%H:%M:%S", $STpir_garage);$deurbel=true;if($mc->get('alertpir_garage')<$time-60) {$mc->set('alertpir_garage', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Spir_living!='Off'&&$STslapen<$timeout) {$msg='Beweging gedecteerd in living om '.strftime("%H:%M:%S", $STpir_living);$deurbel=true;if($mc->get('alertpir_living')<$time-90) {$mc->set('alertpir_living', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Spir_inkom!='Off'&&$STslapen<$timeout) {$msg='Beweging gedecteerd in inkom om '.strftime("%H:%M:%S", $STpir_living);$deurbel=true;if($mc->get('alertpir_living')<$time-90) {$mc->set('alertpir_living', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	if($Sthuis=='Off' && $STthuis<$timeout && $Smeldingen=='On') {
		if($Spir_hall!='Off') {$msg='Beweging gedecteerd in hall om '.strftime("%H:%M:%S", $STpir_living);$deurbel=true;if($mc->get('telegrampir_hall')<$time-90) {$mc->set('alertAchterdeur', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	if($Sdeurbel!='Off') {$msg='Deurbel';if($mc->get('alertDeurbel')<$time-60) {$mc->set('alertDeurbel',$time);ios($msg);} Udevice($SIdeurbel,0,'Off');if($Slicht_hall_auto=='On') {Schakel($SIlicht_voordeur, 'On');$mc->set('Bellichtvoordeur',2);}}
	if($deurbel&&$STdeurbel<$time-5) {schakel($SIdeurbel, 'On');Udevice($SIdeurbel, 0, 'Off');}
	
	//Refresh Zwave node
	//if($STtv>$eenmin||$STsubwoofer>$eenmin||$STlamp_bureel>$eenmin) RefreshZwave(61);
	if($STlichtterrasgarage>$vijfsec) RefreshZwave(16);
	if($STlichthallzolder>$vijfsec) RefreshZwave(20);
	if($STlichtinkomvoordeur>$vijfsec) RefreshZwave(23);
	if($STkeukenzolder>$vijfsec) RefreshZwave(56);
	if($STwerkbladtuin>$vijfsec) RefreshZwave(68);
	if($STwasbakkookplaat>$vijfsec) RefreshZwave(69);
	if($STlichtbadkamer>$vijfsec) RefreshZwave(65); 

if($all) {
	if($mc->get('domoticzconnection')!=1) {$mc->set('domoticzconnection',1); if(date('G')!=3) telegram('Verbinding met Domoticz hersteld');}
	//meldingen
	if($Smeldingen=='On') {
		$thermometers=array('living','badkamer','kamertobi','kamerjulius','zolder');
		$avg=0;
		foreach($thermometers as $thermometer) $avg=$avg+${'T'.$thermometer};
		$avg=$avg / 6;
		foreach($thermometers as $thermometer) {
			if(${'T'.$thermometer}>$avg + 5 && ${'T'.$thermometer} > 25) {$msg='T '.$thermometer.'='.${'T'.$thermometer}.'°C. AVG='.round($avg,1).'°C';
				if($mc->get('alerttemp'.$thermometer)<$time-600) {telegram($msg);ios($msg);if($sms==true) sms($msg);$mc->set('alerttemp'.$thermometer, $time);}
			}
			if(${'Ssd'.$thermometer}!='Off') {$msg='Rook gedecteerd in '.$thermometer.'!';telegram($msg);ios($msg);if($sms==true) sms($msg);}
		}
		if($Pp_bureel_tobi>500) {if($mc->get('alertpowerbureeltobi')<$halfuur) {$msg='Verbruik bureel tobi='.$Pp_bureel_tobi;telegram($msg);$mc->set('alertpowerbureeltobi', $time);}}
	}
	
	//Sleep dimmers
	$dimmers = array('eettafel','zithoek','tobi','kamer');
	foreach($dimmers as $dimmer) {
		if(${'D'.$dimmer}!='Off') {
			$action = $mc->get('dimmer'.$dimmer);
			if($action == 1) {
				$level = floor(${'Dlevel'.$dimmer}*0.95);
				Dim(${'DI'.$dimmer},$level);
				if($level==0) $mc->set('dimmer'.$dimmer,0);
			} else if($action == 2&&date('i')%2==1) {
				echo $dimmer;
				$level = ${'Dlevel'.$dimmer}+1;
				if($level>30) $level = 30;
				Dim(${'DI'.$dimmer},$level);
				if($level==30) $mc->set('dimmer'.$dimmer,0);
			} 
		}
	}
	
	//heating on/off
	if($Sthuis=='Off') {if($Sheating!='Off'&&$STheating<$eenuur) {Schakel($SIheating, 'Off');$Sheating = 'Off';}
	} else {if($Sheating!='On') {Schakel($SIheating, 'On');$Sheating = 'On';}}
	
	// 0 = auto, 1 = voorwarmen, 2 = manueel
	//living
	$Set=16.0;
	$setpointliving = $mc->get('setpoint130');
	if($setpointliving!=0 && $RTliving < $tweeuur) {$mc->set('setpoint130',0);$setpointliving=0;}
	if($setpointliving!=2) {
		if($Tbuiten<20 && $Sheating=='On' && $Sraamliving=='Closed') {
				$voorwarmen = voorwarmen($Tliving,20,60);
			     if($time>=( strtotime('6:20')-$voorwarmen) && $time < strtotime('8:30')) $Sslapen=='Off'?$Set=19.0:$Set=18.0;
			else if($time>=( strtotime('8:30')-$voorwarmen) && $time < strtotime('19:00')) $Sslapen=='Off'?$Set=20.0:$Set=18.0;
			else if($time>=(strtotime('19:00')-$voorwarmen) && $time < strtotime('22:00')) $Sslapen=='Off'?$Set=20.0:$Set=18.0;
		}
		if($Rliving != $Set) {Udevice($RIliving,0,$Set);}
		if($RTliving < $drieuur) Udevice($RIliving,0,$Set);
	}
	$Set = setradiator($Tliving, $Rliving);
	if($Rlivingzz!=$Set) {Udevice($RIlivingzz, 0, $Set);}
	if($Rlivingze!=$Set) {Udevice($RIlivingze, 0, $Set);}
	if($Rlivingzb!=$Set) {Udevice($RIlivingzb, 0, $Set);}
	
	//badkamer
	$Set=17.0;
	$setpointbadkamer = $mc->get('setpoint111');
	if($setpointbadkamer!=0 && $RTbadkamer < $eenuur) {$mc->set('setpoint111',0);$setpointbadkamer=0;}
	if($setpointbadkamer!=2) {
		if($Tbuiten<21 && $Sheating=='On') {
			$voorwarmen = voorwarmen($Tbadkamer,22,140);
			if(in_array(date('N',$time), array(1,2,3,4,5)) && $time>=(strtotime('6:00')-$voorwarmen) && $time<=(strtotime('7:20'))) $Set=21.0;
		     else if(in_array(date('N',$time), array(6,7)) && $time>=(strtotime('7:30')-$voorwarmen) && $time<=(strtotime('9:30'))) $Set=20.0;
		}
		if($Sdeurbadkamer!='Closed' && $STdeurbadkamer < $time - 180) $Set=14.0;
		if($Rbadkamer != $Set) {Udevice($RIbadkamer,0,$Set);$Rbadkamer=$Set;}
		if($RTbadkamer < $drieuur) Udevice($RIbadkamer,0,$Set);
	}
	$Set = setradiator($Tbadkamer, $Rbadkamer);
	if(in_array(date('N',$time), array(1,2,3,4,5)) && in_array(date('G',$time), array(4,5,6)) && $Set < 21) $Set = 21.0;
	if($Rbadkamerz!=$Set) {Udevice($RIbadkamerz,0,$Set);}
	
	//Slaapkamer
	$Set = 8.0;
	$setpointkamer = $mc->get('setpoint97');
	if($setpointkamer!=0 && $RTkamer < $eenuur) {$mc->set('setpoint97',0);$setpointkamer=0;}
	if($setpointkamer!=2) {
		if($Tbuiten<15 && $Sraamkamer=='Closed' && $Sheating=='On' ) {
			$Set = 14.0;
			if($time < strtotime('8:00') || $time > strtotime('20:00')) $Set = 16.0;
		}
	}
	if($Rkamer != $Set) {Udevice($RIkamer,0,$Set);$Rkamer=$Set;}
	if($RTkamer < $drieuur) Udevice($RIkamer,0,$Set);
	$Set = setradiator($Tkamer, $Rkamer);
	if($Rkamerz!=$Set) {Udevice($RIkamerz,0,$Set);}
	
	//Slaapkamer tobi
	$Set = 8.0;
	$setpointkamertobi = $mc->get('setpoint548');
	if($setpointkamertobi!=0 && $RTkamertobi < $eenuur) {$mc->set('setpoint549',0);$setpointkamertobi=0;}
	if($setpointkamertobi!=2) {
		if($Tbuiten<15 && $Sraamkamertobi=='Closed' && $Sheating=='On') {
			$Set = 14.0;
			if (date('W')%2==1) {
					 if (date('N') == 3) { if($time > strtotime('20:00')) $Set = 16.0;}
				else if (date('N') == 4) { if($time < strtotime('8:00') || $time > strtotime('20:00')) $Set = 16.0;}
				else if (date('N') == 5) { if($time < strtotime('8:00')) $Set = 16.0;}
			} else {
					 if (date('N') == 3) { if($time > strtotime('20:00')) $Set = 16.0;}
				else if (in_array(date('N'),array(4,5,6))) { if($time < strtotime('8:00') || $time > strtotime('20:00')) $Set = 16.0;}
				else if (date('N') == 7) { if($time < strtotime('8:00')) $Set = 16.0;}
			}
			//if($time < strtotime('8:00') || $time > strtotime('20:00')) $Set = 16.0;
		}
	}
	if($Rkamertobi != $Set) {Udevice($RIkamertobi,0,$Set);$Rkamertobi=$Set;}
	if($RTkamertobi < $time - 8600) Udevice($RIkamertobi,0,$Set);
	$Set = setradiator($Tkamertobi, $Rkamertobi);
	if($Rkamertobiz!=$Set) {Udevice($RIkamertobiz,0,$Set);}
	
	//Slaapkamer julius
	$Set = 8.0;
	$setpointkamerjulius = $mc->get('setpoint549');
	if($setpointkamerjulius!=0 && $RTkamerjulius < $eenuur) {$mc->set('setpoint549',0);$setpointkamerjulius=0;}
	if($setpointkamerjulius!=2) {
		if($Tbuiten<15 && $Sraamkamerjulius=='Closed' && $Sheating=='On') {
			$Set = 14.0;
		}
	}
	if($Rkamerjulius != $Set) {Udevice($RIkamerjulius,0,$Set);$Rkamerjulius=$Set;}
	if($RTkamerjulius < $time - 8600) Udevice($RIkamerjulius,0,$Set);
	$Set = setradiator($Tkamerjulius, $Rkamerjulius);
	if($Rkamerjuliusz!=$Set) {Udevice($RIkamerjuliusz,0,$Set);}
	
	//brander
	if(($Tliving < $Rliving || $Tbadkamer < $Rbadkamer || $Tkamer < $Rkamer || $Tkamertobi < $Rkamertobi || $Tkamerjulius < $Rkamerjulius ) && $Sbrander == "Off" && $STbrander < $time-250) Schakel($SIbrander, 'On');
	if($Tliving >= $Rliving-0.2 && $Tbadkamer >= $Rbadkamer-0.4 && $Tkamer >= $Rkamer-0.2 && $Tkamertobi >= $Rkamertobi-0.2 && $Tkamerjulius >= $Rkamerjulius-0.2 && $Sbrander == "On" && $STbrander < $time-250) Schakel($SIbrander, 'Off');
	//if($STbrander<$time-600) Schakel($SIbrander, $Sbrander);
	
	//subwoofer
	if($Sdenon=='On' && $Ssubwoofer!='On')  Schakel($SIsubwoofer,'On');
	else if($Sdenon=='Off'&& $Ssubwoofer!='Off') Schakel($SIsubwoofer,'Off');
	
	//PIRS resetten
	if($Spir_living!='Off'&&$STpir_living<$time-30) Schakel($SIpir_living,'Off');
	if($Spir_garage!='Off'&&$STpir_garage<$timeout) Schakel($SIpir_garage,'Off');
	if($Spir_inkom!='Off'&&$STpir_inkom<$timeout) Schakel($SIpir_inkom,'Off');
	if($Spir_hall!='Off'&&$STpir_hall<$timeout) Schakel($SIpir_hall,'Off');
	
	//Automatische lichten uitschakelen
	if($STlicht_garage_auto < $time-7200) {
		if($time>$zonop+10800 && $time<$zononder-10800) {if($Slicht_garage_auto=='On' && $Slicht_garage=='Off') Schakel($SIlicht_garage_auto,'Off');}
		else if($Slicht_garage_auto=='Off') Schakel($SIlicht_garage_auto,'On');}
	if($STlicht_hall_auto < $time-7200) {
		if($time>$zonop+1800 && $time<$zononder-1800) {if($Slicht_hall_auto=='On' && $Slicht_hall=='Off' && $Slicht_inkom=='Off') Schakel($SIlicht_hall_auto,'Off');}
		else if($Slicht_hall_auto=='Off') Schakel($SIlicht_hall_auto,'On');}
	if($Spir_living=='Off'&&$Stv=='Off'&&$Sdenon=='Off'&&$Deettafel!='Off'&&$STpir_living<$time-600&&$DTeettafel<$time-600) Schakel($DIeettafel,'Off');
	if($Spir_garage=='Off'&&$Spoort=='Closed'&&$STpir_garage<$time-180&&$STpoort<$timeout&&$STlicht_garage<$time-60&&$Slicht_garage=='On'&&$Slicht_garage_auto=='On') Schakel($SIlicht_garage,'Off');
	if($STpir_inkom<$time-120&&$STpir_hall<$time-120&&$STlicht_inkom<$time-60&&$STlicht_hall<$time-60&&$Slicht_hall_auto=='On') {
		if($Slicht_inkom=='On') Schakel($SIlicht_inkom,'Off');
		if($Slicht_hall=='On') Schakel($SIlicht_hall,'Off');}
	if($Slicht_voordeur!='Off') {if($mc->get('Bellichtvoordeur')==2) Schakel($SIlicht_voordeur,'Off');} 
	
	//slapen-niet thuis bij geen beweging
	if($STpir_living<$time-14400&&$STpir_garage<$time-14400&&$STpir_inkom<$time-14400&&$STpir_hall<$time-14400&&$STslapen<$time-14400&&$STthuis<$time-14400&&$Sthuis=='On'&&$Sslapen=="Off") {Schakel($SIslapen,'On');telegram('slapen ingeschakeld na 4 uur geen beweging');}
	if($STpir_living<$time-43200&&$STpir_garage<$time-43200&&$STpir_inkom<$time-43200&&$STpir_hall<$time-43200&&$STslapen<$time-28800&&$STthuis<$time-43200&&$Sthuis=='On'&&$Sslapen=="On") {Schakel($SIslapen, 'Off');Schakel($SIthuis, 'Off');telegram('thuis uitgeschakeld na 12 uur geen beweging');}
	
	//Laptop pluto zonnepanelen
	if($time > $zononder - 4000 && $time < $zononder + 4000) {
		if($Spluto=='Off') Schakel($SIpluto, 'On');
	} else {
		if($Spluto=='On') Schakel($SIpluto, 'Off');
	}
	
	//lichten uitschakelen na X uur. 
	
	if($Dtobi!='Off'&&$DTtobi<$drieuur) Schakel($DItobi, 'Off');
	if($Slicht_garage!='Off'&&$STlicht_garage<$tweeuur) Schakel($SIlicht_garage, 'Off');
	if($Slicht_voordeur!='Off'&&$STlicht_voordeur<$tweeuur) Schakel($SIlicht_voordeur, 'Off');
	if($Slicht_hall!='Off'&&$STlicht_hall<$tweeuur) Schakel($SIlicht_hall, 'Off');
	if($Slicht_inkom!='Off'&&$STlicht_inkom<$tweeuur) Schakel($SIlicht_inkom, 'Off');
	if($Skeuken!='Off'&&$STkeuken<$tweeuur) Schakel($SIkeuken, 'Off');
	if($Swasbak!='Off'&&$STwasbak<$tweeuur) Schakel($SIwasbak, 'Off');
	if($Skookplaat!='Off'&&$STkookplaat<$tweeuur) Schakel($SIkookplaat, 'Off');
	if($Swerkblad!='Off'&&$STwerkblad<$tweeuur) Schakel($SIwerkblad, 'Off');
	if($Szolderg!='Off'&&$STzolderg<$tweeuur) Schakel($SIzolderg, 'Off');
	if($Slichtbadkamer1!='Off'&&$STlichtbadkamer1<$tweeuur) Schakel($SIlichtbadkamer1, 'Off');
	if($Slichtbadkamer2!='Off'&&$STlichtbadkamer2<$tweeuur) Schakel($SIlichtbadkamer2, 'Off');
		
	//meldingen inschakelen indien langer dan 12 uur uit. 
	if($Smeldingen!='On' && $STmeldingen<$time-43200) Schakel($SImeldingen, 'On');
	
	//KODI
	if($Skodi=='On'&&$STkodi<$timeout) {
		$status = pingDomain('192.168.0.7', 1597);
		if(is_int($status)) Schakel($SIkodi, 'Off');
	}
	//diskstation
	if($Sdiskstation=='On'&&$STdiskstation<$timeout) {
		$status = pingDomain('192.168.0.10', 1600);
		if(is_int($status)) {
			Schakel($SIdiskstation, 'Off');
			//telegram('Diskstion powered off');
		}
	} else if($Sdiskstation=='Off' &&($Sbureel_tobi=='On'||$Skodi=='On'||$Simac=='On')) {
		shell_exec('wakeonlan 00:11:32:2c:b7:21');
		//telegram('WOL sent to Diskstation');
	}
	
	//Alles uitschakelen
	if($Sthuis=='Off'||$Sslapen=="On") {
		if($Stv!='Off'&&$STtv<$vijfmin) Schakel($SItv, 'Off');
		if($Skristal!='Off'&&$STkristal<$vijfmin) Schakel($SIkristal, 'Off');
		if($StvLed!='Off'&&$STtvLed<$vijfmin) Schakel($SItvLed, 'Off');
		if($Sdenon!='Off'&&$STdenon<$vijfmin) Schakel($SIdenon, 'Off');
		if($Slamp_bureel!='Off'&&$STlamp_bureel<$vijfmin) Schakel($SIlamp_bureel, 'Off');
		if($Sterras!='Off'&&$STterras<$vijfmin) Schakel($SIterras, 'Off');
		if($Slicht_garage!='Off'&&$STlicht_garage<$vijfmin) Schakel($SIlicht_garage, 'Off');
		if($Slicht_voordeur!='Off'&&$STlicht_voordeur<$vijfmin) Schakel($SIlicht_voordeur, 'Off');
		if($Slicht_hall!='Off'&&$STlicht_hall<$vijfmin&&$STpir_hall<$vijfmin) Schakel($SIlicht_hall, 'Off');
		if($Slicht_inkom!='Off'&&$STlicht_inkom<$vijfmin&&$STpir_inkom<$vijfmin) Schakel($SIlicht_inkom, 'Off');
		if($Slicht_Zolder!='Off'&&$STlicht_Zolder<$vijfmin) Schakel($SIlicht_Zolder, 'Off');
		if($Sbureel_tobi!='Off'&&$STbureel_tobi<$vijfmin) Schakel($SIbureel_tobi, 'Off');
		if($Dkamer!='Off'&&$DTkamer<$vijfmin) Schakel($DIeettafel, 'Off');
		if($Deettafel!='Off'&&$DTeettafel<$vijfmin) Schakel($DIeettafel, 'Off');
		if($Dzithoek!='Off'&&$DTzithoek<$vijfmin) Schakel($DIzithoek, 'Off');
		if($setpointliving!=0 && $RTliving<$eenuur) $mc->set('setpoint130',0);
		if($setpointbadkamer!=0 && $RTbadkamer<$eenuur) $mc->set('setpoint111',0);
		if($setpointkamer!=0 && $RTkamer<$eenuur) $mc->set('setpoint97',0);
		if($setpointkamertobi!=0 && $RTkamertobi<$eenuur) $mc->set('setpoint548',0);
		if($setpointkamerjulius!=0 && $RTkamerjulius<$eenuur) $mc->set('setpoint549',0);
		if($Skeuken!='Off'&&$STkeuken<$vijfmin) Schakel($SIkeuken, 'Off');
		if($Swasbak!='Off'&&$STwasbak<$vijfmin) Schakel($SIwasbak, 'Off');
		if($Skookplaat!='Off'&&$STkookplaat<$vijfmin) Schakel($SIkookplaat, 'Off');
		if($Swerkblad!='Off'&&$STwerkblad<$vijfmin) Schakel($SIwerkblad, 'Off');
		if($Szolderg!='Off'&&$STzolderg<$vijfmin) Schakel($SIzolderg, 'Off');
		if($Skerstboom!='Off'&&$STkerstboom<$vijfmin) Schakel($SIkerstboom, 'Off');
		if($Simac!='Off'&&$STimac<$vijfmin) Schakel($SIimac, 'Off');
	}	
	if($Sthuis=='Off') {
		if($Slichtbadkamer1!='Off'&&$STbadkamer1<$vijfmin) Schakel($SIbadkamer1, 'Off');
		if($Slichtbadkamer2!='Off'&&$STbadkamer2<$vijfmin) Schakel($SIbadkamer1, 'Off');
	}
	
	//Buienradar - Openweathermap
	if (date('i')%2==1) {
		$rains=file_get_contents('http://gps.buienradar.nl/getrr.php?lat=50.892880&lon=3.112568');
		$rains=str_split($rains, 11);$totalrain=0;$aantal=0;
		foreach($rains as $rain) {$aantal=$aantal+1;$totalrain=$totalrain+substr($rain,0,3);$averagerain=round($totalrain/$aantal,0);if($aantal==12) break;}
		if($averagerain>=0) $mc->set('averagerain',$averagerain);
		$openweathermap=file_get_contents('http://api.openweathermap.org/data/2.5/weather?id=2787891&APPID=ac3485b0bf1a02a81d2525db6515021d&units=metric');
		$openweathermap=json_decode($openweathermap,true);
		if(isset($openweathermap['weather']['0']['icon'])) {
			$mc->set('weatherimg',$openweathermap['weather']['0']['icon']);
			file_get_contents($domoticzurl.'type=command&param=udevice&idx=36&nvalue=0&svalue='.round($openweathermap['main']['temp'],1));
		}
	}
	include('gcal/gcal.php');
	unset($domoticz,$dom,$rain,$rains,$thermometers,$thermometer,$applepass,$appleid,$appledevice,$domoticzurl,$smsuser,$smsapi,$smspassword,$smstofrom,$user,$users,$db,$avg,$dimmer,$dimmers,$Set,$openweathermap,$Type,$SwitchType,$SubType,$name,$http_response_header,$_SERVER,$_FILES,$_COOKIE,$_POST);
		
} //END ALL
//End Acties
} else {
	$domoticzconnection = $mc->get('domoticzconnection');
	$domoticzconnection = $domoticzconnection + 1;
	$mc->set('domoticzconnection',$domoticzconnection);
	if($domoticzconnection==2) if(date('G')!=3) telegram('Geen verbinding met Domoticz');
	if($domoticzconnection>15) {
		$mc->set('domoticzconnection',0);
		$output = shell_exec('/var/www/secure/restart_domoticz');
		telegram($output);
	}

}
//if($authenticated) 	echo '<hr>Number of vars: '.count(get_defined_vars()).'<br/><pre>';print_r(get_defined_vars());echo '</pre>';
//$execution= microtime(true)-$start;$phptime=$execution-$domotime;if($all) $msg='D'.round($domotime,3).'|P'.round($phptime,3).'|T'.round($execution,3).'|All';else $msg='D'.round($domotime,3).'|P'.round($phptime,3).'|T'.round($execution,3);telegram($msg);
