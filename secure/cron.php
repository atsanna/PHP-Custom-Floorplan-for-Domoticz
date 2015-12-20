#!/usr/bin/php
<?php 
$authenticated=false;
include "functions.php";
if($authenticated) {error_reporting(E_ALL);ini_set("display_errors", "on");}
$time=$_SERVER['REQUEST_TIME'];$starttime=microtime(true);
isset($_GET['all'])||isset($argv[1])?$all=true:$all=false;
if($all) {$domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&used=true'),true);}
else {$domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&used=true&plan=5'),true);}
$domotime=microtime(true)-$starttime;
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
	$vijfsec=$time-5;$eenmin=$time-55;$tweemin=$time-115;$driemin=$time-175;$vijfmin=$time-295;$tienmin=$time-595;$halfuur=$time-1795;$eenuur=$time-3595;$tweeuur=$time-7195;$drieuur=$time-10795;$twaalfuur=$time-43195;
	
	//Automatische lichten inschakelen
	if($Sthuis=='On') {
		if($Sslapen=='Off') {
			if(($Spir_garage!='Off'||$Spoort!='Closed')&&$Slicht_garage=='Off'&&$Slicht_garage_auto=='On') {Schakel($SIlicht_garage, 'On', 'licht garage');}
			if($Spir_inkom!='Off'&&$Slicht_inkom=='Off'&&$Slicht_hall_auto=='On') {Schakel($SIlicht_inkom, 'On', 'licht inkom');Schakel($SIlicht_hall, 'On','licht hall');} 
			if($Spir_hall!='Off'&&($Slicht_hall=='Off'||$Slicht_inkom=='Off')&&$Slicht_hall_auto=='On') {Schakel($SIlicht_hall, 'On','licht hall');Schakel($SIlicht_inkom, 'On','licht inkom');}
			if($Spir_living!='Off'&&$Stv=='Off'&&$Sdenon=='Off'&&$Slamp_bureel=='Off'&&$Slicht_hall_auto=='On'&&$Deettafel=='Off') {
				if($Swerkblad!='On') {Schakel($SIwerkblad, 'On','licht werkblad');}
				if($Deettafel<9) {Dim($DIeettafel, 9,'eettafel');}
				if($Skerstboom=='Off') {Schakel($SIkerstboom, 'On','kerstboom');}
				if($time > strtotime('6:00') && $time < strtotime('7:15')) {
					shell_exec('wakeonlan 3c:07:54:22:34:17');
					shell_exec('wakeonlan 00:11:32:2c:b7:21');
					if($Simac=='Off') Schakel($SIimac, 'On','imac');
					if($Slamp_bureel=='Off') Schakel($SIlamp_bureel, 'On','lamp bureel');
				}
			}
			if($Sdeurbadkamer=='Open'&&$STdeurbadkamer>$time-10&&$STlichtbadkamer1<$eenmin&&$Slichtbadkamer1!='On'&&$STlichtbadkamer2<$eenmin&&$Slichtbadkamer2=='Off'&&$Slicht_hall_auto=='On') Schakel($SIlichtbadkamer1, 'On','licht badkamer1');
		} else if ($Sslapen=='On') {
			if($Spir_inkom!='Off'&&$Slicht_inkom=='Off'&&$Slicht_hall_auto=='On') Schakel($SIlicht_inkom, 'On','licht inkom');
			if($Spir_hall!='Off'&&$Slicht_inkom=='Off'&&$Slicht_hall_auto=='On') Schakel($SIlicht_inkom, 'On','licht inkom');
			if($Sdeurbadkamer=='Open'&&$STdeurbadkamer>$time-10&&$STlichtbadkamer2<$eenmin&&$Slichtbadkamer2!='On'&&$STlichtbadkamer1<$eenmin&&$Slichtbadkamer1=='Off') {
				if($time > strtotime('6:00') && $time < strtotime('12:00')) Schakel($SIlichtbadkamer1, 'On','licht badkamer1'); else Schakel($SIlichtbadkamer2, 'On','licht badkamer2'); 
			}
		}
	}
	
	//meldingen
	$deurbel=false;
	if(($Sthuis=='Off'||$Sslapen=='On') && $STthuis<$driemin && $Smeldingen=='On') {
		if($Spoort!='Closed') {$msg='Poort open om '.strftime("%H:%M:%S", $STpoort);$deurbel=true;if($mc->get('alertpoort')<$eenmin) {$mc->set('alertpoort', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Sachterdeur!='Closed') {$msg='Achterdeur open om '.strftime("%H:%M:%S", $STachterdeur);$deurbel=true;if($mc->get('alertAchterdeur')<$eenmin) {$mc->set('alertAchterdeur', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Sraamliving!='Closed') {$msg='raam living open om '.strftime("%H:%M:%S", $STraamliving);$deurbel=true;if($mc->get('alertraamliving')<$eenmin) {$mc->set('alertraamliving', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Spir_garage!='Off'&&$STslapen<$driemin) {$msg='Beweging gedecteerd in garage om '.strftime("%H:%M:%S", $STpir_garage);$deurbel=true;if($mc->get('alertpir_garage')<$eenmin) {$mc->set('alertpir_garage', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Spir_living!='Off'&&$STslapen<$driemin) {$msg='Beweging gedecteerd in living om '.strftime("%H:%M:%S", $STpir_living);$deurbel=true;if($mc->get('alertpir_living')<$time-90) {$mc->set('alertpir_living', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Spir_inkom!='Off'&&$STslapen<$driemin) {$msg='Beweging gedecteerd in inkom om '.strftime("%H:%M:%S", $STpir_living);$deurbel=true;if($mc->get('alertpir_living')<$time-90) {$mc->set('alertpir_living', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	if($Sthuis=='Off' && $STthuis<$driemin && $Smeldingen=='On') {
		if($Spir_hall!='Off') {$msg='Beweging gedecteerd in hall om '.strftime("%H:%M:%S", $STpir_living);$deurbel=true;if($mc->get('telegrampir_hall')<$time-90) {$mc->set('alertAchterdeur', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	if($Sdeurbel!='Off') {$msg='Deurbel';if($mc->get('alertDeurbel')<$eenmin) {$mc->set('alertDeurbel',$time);ios($msg);} Udevice($SIdeurbel,0,'Off','deurbel');if($Slicht_hall_auto=='On') {Schakel($SIlicht_voordeur, 'On','licht voordeur');$mc->set('Bellichtvoordeur',2);}}
	if($deurbel&&$STdeurbel<$time-5) {schakel($SIdeurbel, 'On','deurbel');Udevice($SIdeurbel, 0, 'Off','deurbel');}
	
	//Refresh Zwave node
	if($STlichtterrasgarage>$vijfsec) RefreshZwave(16,'TerrasGarage');
	if($STlichthallzolder>$vijfsec) RefreshZwave(20,'HallZolder');
	if($STlichtinkomvoordeur>$vijfsec) RefreshZwave(23,'InkomVoordeur');
	if($STkeukenzolder>$vijfsec) RefreshZwave(56,'KeukenZolder');
	if($STwerkbladtuin>$vijfsec) RefreshZwave(68,'WerkbladTuin');
	if($STwasbakkookplaat>$vijfsec) RefreshZwave(69,'WasbakKookplaat');
	if($STlichtbadkamer>$vijfsec) RefreshZwave(65,'LichtBadkamer'); 
	if($STkerstboom>$vijfsec) RefreshZwave(70,'Kerstboom');

	if($all) {
		if($mc->get('domoticzconnection')!=1) {$mc->set('domoticzconnection',1); telegram('Verbinding met Domoticz hersteld');}
		//meldingen
		if($Smeldingen=='On') {
			$thermometers=array('living','badkamer','tobi','julius','zolder');
			$avg=0;
			foreach($thermometers as $thermometer) $avg=$avg+${'T'.$thermometer};
			$avg=$avg / 6;
			foreach($thermometers as $thermometer) {
				if(${'T'.$thermometer}>$avg + 5 && ${'T'.$thermometer} > 25) {$msg='T '.$thermometer.'='.${'T'.$thermometer}.'°C. AVG='.round($avg,1).'°C';
					if($mc->get('alerttemp'.$thermometer)<$tienmin) {telegram($msg);ios($msg);if($sms==true) sms($msg);$mc->set('alerttemp'.$thermometer, $time);}
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
					Dim(${'DI'.$dimmer},$level,$dimmer);
					if($level==0) $mc->set('dimmer'.$dimmer,0);
				} else if($action == 2&&date('i')%2==1) {
					echo $dimmer;
					$level = ${'Dlevel'.$dimmer}+1;
					if($level>30) $level = 30;
					Dim(${'DI'.$dimmer},$level,$dimmer);
					if($level==30) $mc->set('dimmer'.$dimmer,0);
				} 
			}
		}
		
		//heating on/off
		if($Sthuis=='Off') {if($Sheating!='Off'&&$STheating<$eenuur) {Schakel($SIheating, 'Off','heating');$Sheating = 'Off';}
		} else {if($Sheating!='On') {Schakel($SIheating, 'On','heating');$Sheating = 'On';}}
		
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
			if($Rliving != $Set) Udevice($RIliving,0,$Set,'Rliving');
			if($RTliving < $drieuur) Udevice($RIliving,0,$Set,'Rliving');
		}
		$Set = setradiator($Tliving, $Rliving);
		if($Rlivingzz!=$Set) {Udevice($RIlivingzz,0,$Set,'RlivingZZ');}
		if($Rlivingze!=$Set) {Udevice($RIlivingze,0,$Set,'RlivingZE');}
		if($Rlivingzb!=$Set) {Udevice($RIlivingzb,0,$Set,'RlivingZB');}
		
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
			if($Rbadkamer != $Set) {Udevice($RIbadkamer,0,$Set,'Rbadkamer');$Rbadkamer=$Set;}
			if($RTbadkamer < $drieuur) Udevice($RIbadkamer,0,$Set,'Rbadkamer');
		}
		$Set = setradiator($Tbadkamer, $Rbadkamer);
		if(in_array(date('N',$time), array(1,2,3,4,5)) && in_array(date('G',$time), array(4,5,6)) && $Set < 21) $Set = 21.0;
		if($Rbadkamerz!=$Set) {Udevice($RIbadkamerz,0,$Set,'RbadkamerZ');}
		
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
		if($Rkamer != $Set) {Udevice($RIkamer,0,$Set,'Rkamer');$Rkamer=$Set;}
		if($RTkamer < $drieuur) Udevice($RIkamer,0,$Set,'Rkamer');
		$Set = setradiator($Tkamer, $Rkamer);
		if($Rkamerz!=$Set) {Udevice($RIkamerz,0,$Set,'RkamerZ');}
		
		//Slaapkamer tobi
		$Set = 8.0;
		$setpointtobi = $mc->get('setpoint548');
		if($setpointtobi!=0 && $RTtobi < $eenuur) {$mc->set('setpoint549',0);$setpointtobi=0;}
		if($setpointtobi!=2) {
			if($Tbuiten<15 && $Sraamtobi=='Closed' && $Sheating=='On') {
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
		if($Rtobi != $Set) {Udevice($RItobi,0,$Set,'Rtobi');$Rtobi=$Set;}
		if($RTtobi < $time - 8600) Udevice($RItobi,0,$Set,'Rtobi');
		$Set = setradiator($Ttobi, $Rtobi);
		if($Rtobiz!=$Set) {Udevice($RItobiz,0,$Set,'RtobiZ');}
		
		//Slaapkamer julius
		$Set = 8.0;
		$setpointjulius = $mc->get('setpoint549');
		if($setpointjulius!=0 && $RTjulius < $eenuur) {$mc->set('setpoint549',0);$setpointjulius=0;}
		if($setpointjulius!=2) {
			if($Tbuiten<15 && $Sraamjulius=='Closed' && $Sheating=='On') {
				$Set = 14.0;
			}
		}
		if($Rjulius != $Set) {Udevice($RIjulius,0,$Set,'Rjulius');$Rjulius=$Set;}
		if($RTjulius < $time - 8600) Udevice($RIjulius,0,$Set,'Rjulius');
		$Set = setradiator($Tjulius, $Rjulius);
		if($Rjuliusz!=$Set) {Udevice($RIjuliusz,0,$Set,'RjuliusZ');}
		
		//brander
		if(($Tliving < $Rliving || $Tbadkamer < $Rbadkamer || $Tkamer < $Rkamer || $Ttobi < $Rtobi || $Tjulius < $Rjulius ) && ($Sbrander == "Off"||$STbrander < $time-1800) && $STbrander < $time-250) Schakel($SIbrander, 'On', 'brander');
		if($Tliving >= $Rliving-0.2 && $Tbadkamer >= $Rbadkamer-0.4 && $Tkamer >= $Rkamer-0.2 && $Ttobi >= $Rtobi-0.2 && $Tjulius >= $Rjulius-0.2 && ($Sbrander == "On"||$STbrander < $time-1800) && $STbrander < $time-250) Schakel($SIbrander, 'Off', 'brander');
		//if($STbrander<$tienmin) Schakel($SIbrander, $Sbrander);
		
		//subwoofer
		if($Sdenon=='On' && $Ssubwoofer!='On') Schakel($SIsubwoofer,'On','subwoofer');
		else if($Sdenon=='Off'&& $Ssubwoofer!='Off') Schakel($SIsubwoofer,'Off','subwoofer');
		
		//PIRS resetten
		if($Spir_living!='Off'&&$STpir_living<$eenmin) Schakel($SIpir_living,'Off','PIR living');
		//if($Spir_garage!='Off'&&$STpir_garage<$driemin) Schakel($SIpir_garage,'Off','PIR garage');
		//if($Spir_inkom!='Off'&&$STpir_inkom<$driemin) Schakel($SIpir_inkom,'Off','PIR inkom');
		//if($Spir_hall!='Off'&&$STpir_hall<$driemin) Schakel($SIpir_hall,'Off','PIR hall');
		
		//Automatische lichten uitschakelen
		if($STlicht_garage_auto < $time-7200) {
			if($time>$zonop+10800 && $time<$zononder-10800) {if($Slicht_garage_auto=='On' && $Slicht_garage=='Off') Schakel($SIlicht_garage_auto,'Off','licht garage');}
			else if($Slicht_garage_auto=='Off') Schakel($SIlicht_garage_auto,'On','licht garage');}
		if($STlicht_hall_auto < $time-7200) {
			if($time>$zonop+1800 && $time<$zononder-1800) {if($Slicht_hall_auto=='On' && $Slicht_hall=='Off' && $Slicht_inkom=='Off') Schakel($SIlicht_hall_auto,'Off','hall auto');}
			else if($Slicht_hall_auto=='Off') Schakel($SIlicht_hall_auto,'On','hall auto');}
		if($Spir_living=='Off'&&$Stv=='Off'&&$Sdenon=='Off'&&$Deettafel!='Off'&&$STpir_living<$tienmin&&$DTeettafel<$tienmin) Schakel($DIeettafel,'Off','eettafel');
		if($Spir_garage=='Off'&&$Spoort=='Closed'&&$STpir_garage<$driemin&&$STpoort<$driemin&&$STlicht_garage<$eenmin&&$Slicht_garage=='On'&&$Slicht_garage_auto=='On') Schakel($SIlicht_garage,'Off','licht garage');
		if($STpir_inkom<$tweemin&&$STpir_hall<$tweemin&&$STlicht_inkom<$eenmin&&$STlicht_hall<$eenmin&&$Slicht_hall_auto=='On') {
			if($Slicht_inkom=='On') Schakel($SIlicht_inkom,'Off','licht inkom');
			if($Slicht_hall=='On') Schakel($SIlicht_hall,'Off','licht hall');}
		if($Slicht_voordeur!='Off') {if($mc->get('Bellichtvoordeur')==2) Schakel($SIlicht_voordeur,'Off','licht voordeur');} 
		
		//slapen-niet thuis bij geen beweging
		if($STpir_living<$time-14400&&$STpir_garage<$time-14400&&$STpir_inkom<$time-14400&&$STpir_hall<$time-14400&&$STslapen<$time-14400&&$STthuis<$time-14400&&$Sthuis=='On'&&$Sslapen=="Off") {Schakel($SIslapen,'On','slapen');telegram('slapen ingeschakeld na 4 uur geen beweging');}
		if($STpir_living<$twaalfuur&&$STpir_garage<$twaalfuur&&$STpir_inkom<$twaalfuur&&$STpir_hall<$twaalfuur&&$STslapen<$time-28800&&$STthuis<$twaalfuur&&$Sthuis=='On'&&$Sslapen=="On") {Schakel($SIslapen, 'Off','slapen');Schakel($SIthuis, 'Off','thuis');telegram('thuis uitgeschakeld na 12 uur geen beweging');}
		
		//Laptop pluto zonnepanelen
		if($time > $zononder - 4000 && $time < $zononder + 4000) {if($Spluto=='Off') Schakel($SIpluto, 'On','pluto');
		} else {if($Spluto=='On') Schakel($SIpluto, 'Off','pluto');}
		
		//lichten uitschakelen na X uur. 
		if($Dtobi!='Off'&&$DTtobi<$tweeuur) Schakel($DItobi, 'Off','licht tobi 2u');
		if($Slicht_garage!='Off'&&$STlicht_garage<$tweeuur) Schakel($SIlicht_garage, 'Off','licht garage 2u');
		if($Slicht_voordeur!='Off'&&$STlicht_voordeur<$tweeuur) Schakel($SIlicht_voordeur, 'Off','licht voordeur 2u');
		if($Slicht_hall!='Off'&&$STlicht_hall<$tweeuur) Schakel($SIlicht_hall, 'Off','licht hall 2u');
		if($Slicht_inkom!='Off'&&$STlicht_inkom<$tweeuur) Schakel($SIlicht_inkom, 'Off','licht inkom 2u');
		if($Skeuken!='Off'&&$STkeuken<$tweeuur) Schakel($SIkeuken, 'Off','licht keuken 2u');
		if($Swasbak!='Off'&&$STwasbak<$tweeuur) Schakel($SIwasbak, 'Off','licht wasbak 2u');
		if($Skookplaat!='Off'&&$STkookplaat<$tweeuur) Schakel($SIkookplaat, 'Off','licht kookplaat 2u');
		if($Swerkblad!='Off'&&$STwerkblad<$tweeuur) Schakel($SIwerkblad, 'Off','licht werkblad 2u');
		if($Szolderg!='Off'&&$STzolderg<$tweeuur) Schakel($SIzolderg, 'Off','licht zolder garage 2u');
		if($Slichtbadkamer1!='Off'&&$STlichtbadkamer1<$tweeuur) Schakel($SIlichtbadkamer1, 'Off','licht badkamer1 2u');
		if($Slichtbadkamer2!='Off'&&$STlichtbadkamer2<$tweeuur) Schakel($SIlichtbadkamer2, 'Off','licht badkamer2 2u');
			
		//meldingen inschakelen indien langer dan 12 uur uit. 
		if($Smeldingen!='On' && $STmeldingen<$twaalfuur) Schakel($SImeldingen, 'On','meldingen');
		
		//KODI
		if($Skodi=='On'&&$STkodi<$driemin) {
			$status = pingDomain('192.168.0.7', 1597);
			if(is_int($status)) Schakel($SIkodi, 'Off','kodi');
		}
		//diskstation
		if($Sdiskstation=='On'&&$STdiskstation<$driemin) {
			$status = pingDomain('192.168.0.10', 1600);
			if(is_int($status)) Schakel($SIdiskstation, 'Off','diskstation');
		} else if($Sdiskstation=='Off' &&($Sbureel_tobi=='On'||$Skodi=='On'||$Simac=='On')) shell_exec('wakeonlan 00:11:32:2c:b7:21');
		
		//Alles uitschakelen
		if($Sthuis=='Off'||$Sslapen=="On") {
			if($STthuis>$eenmin) $uit = $eenmin; else $uit = $halfuur;
			if($Stv!='Off'&&$STtv<$uit) Schakel($SItv, 'Off','tv weg/slapen');
			if($Skristal!='Off'&&$STkristal<$uit) Schakel($SIkristal, 'Off','kristal weg/slapen');
			if($Stvled!='Off'&&$STtvLed<$uit) Schakel($SItvled, 'Off','tvled weg/slapen');
			if($Sdenon!='Off'&&$STdenon<$uit) Schakel($SIdenon, 'Off','denon weg/slapen');
			if($Slamp_bureel!='Off'&&$STlamp_bureel<$uit) Schakel($SIlamp_bureel, 'Off','lamp bureel weg/slapen');
			if($Sterras!='Off'&&$STterras<$uit) Schakel($SIterras, 'Off','terras weg/slapen');
			if($Slicht_garage!='Off'&&$STlicht_garage<$uit) Schakel($SIlicht_garage, 'Off','licht garage weg/slapen');
			if($Slicht_voordeur!='Off'&&$STlicht_voordeur<$uit) Schakel($SIlicht_voordeur, 'Off','licht voordeur weg/slapen');
			if($Slicht_hall!='Off'&&$STlicht_hall<$uit) Schakel($SIlicht_hall, 'Off','licht hall weg/slapen');
			if($Slicht_inkom!='Off'&&$STlicht_inkom<$uit) Schakel($SIlicht_inkom, 'Off','licht inkom weg/slapen');
			if($Slicht_zolder!='Off'&&$STlicht_Zolder<$uit) Schakel($SIlicht_zolder, 'Off','licht zolder weg/slapen');
			if($Sbureel_tobi!='Off'&&$STbureel_tobi<$uit) Schakel($SIbureel_tobi, 'Off','bureel tobi weg/slapen');
			if($Dkamer!='Off'&&$DTkamer<$uit) Schakel($DIkamer, 'Off','kamer weg/slapen');
			if($Deettafel!='Off'&&$DTeettafel<$uit) Schakel($DIeettafel, 'Off','eettafel weg/slapen');
			if($Dzithoek!='Off'&&$DTzithoek<$uit) Schakel($DIzithoek, 'Off','zithoek weg/slapen');
			if($setpointliving!=0 && $RTliving<$uit) $mc->set('setpoint130',0);
			if($setpointbadkamer!=0 && $RTbadkamer<$uit) $mc->set('setpoint111',0);
			if($setpointkamer!=0 && $RTkamer<$uit) $mc->set('setpoint97',0);
			if($setpointtobi!=0 && $RTtobi<$uit) $mc->set('setpoint548',0);
			if($setpointjulius!=0 && $RTjulius<$uit) $mc->set('setpoint549',0);
			if($Skeuken!='Off'&&$STkeuken<$uit) Schakel($SIkeuken, 'Off','keuken weg/slapen');
			if($Swasbak!='Off'&&$STwasbak<$uit) Schakel($SIwasbak, 'Off','wasbak weg/slapen');
			if($Skookplaat!='Off'&&$STkookplaat<$uit) Schakel($SIkookplaat, 'Off','kookplaat weg/slapen');
			if($Swerkblad!='Off'&&$STwerkblad<$uit) Schakel($SIwerkblad, 'Off','werkblad weg/slapen');
			if($Szolderg!='Off'&&$STzolderg<$uit) Schakel($SIzolderg, 'Off','zolder garage weg/slapen');
			if($Skerstboom!='Off'&&$STkerstboom<$uit) Schakel($SIkerstboom, 'Off','kerstboom weg/slapen');
			if($Simac!='Off'&&$STimac<$uit) Schakel($SIimac, 'Off','imac weg/slapen');
		}	
		if($Sthuis=='Off') {
			if($Slichtbadkamer1!='Off'&&$STbadkamer1<$vijfmin) Schakel($SIbadkamer1, 'Off','licht badkamer1 weg');
			if($Slichtbadkamer2!='Off'&&$STbadkamer2<$vijfmin) Schakel($SIbadkamer1, 'Off','licht badkamer2 weg');
		}
		//Buienradar - Openweathermap
		if ($mc->get('buienradar')<$vijfmin) {
			$rains=file_get_contents('http://gps.buienradar.nl/getrr.php?lat=50.892880&lon=3.112568');
			$rains=str_split($rains, 11);$totalrain=0;$aantal=0;
			foreach($rains as $rain) {$aantal=$aantal+1;$totalrain=$totalrain+substr($rain,0,3);$averagerain=round($totalrain/$aantal,0);if($aantal==12) break;}
			if($averagerain>=0) $mc->set('averagerain',$averagerain);
			$openweathermap=file_get_contents('http://api.openweathermap.org/data/2.5/weather?id=2787891&APPID=ac3485b0bf1a02a81d2525db6515021d&units=metric');
			$openweathermap=json_decode($openweathermap,true);
			if(isset($openweathermap['weather']['0']['icon'])) {
				$mc->set('weatherimg',$openweathermap['weather']['0']['icon']);
				file_get_contents($domoticzurl.'type=command&param=udevice&idx=36&nvalue=0&svalue='.round($openweathermap['main']['temp'],1));
				$mc->set('buienradar',$time);
			}
		}
		$rpimem=number_format(get_server_memory_usage(),2);$rpicpu=number_format(get_server_cpu_usage(),2);
		if($rpimem>75||$rpicpu>1||$preverrors>100||(date('G') == 3&&($rpimem>50||$preverrors>10))) shell_exec('/var/www/secure/reboot');
		if(php_sapi_name()=='cli'&&$mc->get('gcal')<$vijfmin) {include('gcal/gcal.php');unset($client,$results,$optParams,$service,$event);}
		unset($rain,$rains,$thermometers,$thermometer,$avg,$dimmer,$dimmers,$Set,$openweathermap,$Type,$SwitchType,$SubType,$name);
			
	} //END ALL
unset($domoticz,$dom,$applepass,$appleid,$appledevice,$domoticzurl,$smsuser,$smsapi,$smspassword,$smstofrom,$user,$users,$db,$http_response_header,$_SERVER,$_FILES,$_COOKIE,$_POST);
$totalerrors=$preverrors+$errors;if($totalerrors!=$preverrors) $mc->set('errors',$totalerrors);
//End Acties
} else {
	if($all) {
		$domoticzconnection = $mc->get('domoticzconnection');
		$domoticzconnection = $domoticzconnection + 1;
		$mc->set('domoticzconnection',$domoticzconnection);
		if($domoticzconnection==2) telegram('Geen verbinding met Domoticz');
		if($domoticzconnection==4) {
			telegram(shell_exec('/var/www/secure/restart_domoticz'));
		} else if($domoticzconnection>5) {
			telegram(shell_exec('/var/www/secure/reboot'));
		}
	}
}
//if($authenticated) 	echo '<hr>Number of vars: '.count(get_defined_vars()).'<br/><pre>';print_r(get_defined_vars());echo '</pre>';
if($actions>0) {
	$execution= microtime(true)-$starttime;
	$phptime=$execution-$domotime;
	if($all) $msg='D'.number_format($domotime,3).'|P'.number_format($phptime,3).'|T'.number_format($execution,3).'|M'.$rpimem.'|C'.$rpicpu.'|E'.$errors.'|TE'.$totalerrors.'|All > '.$actions.' actions';
	else $msg='D'.number_format($domotime,3).'|P'.number_format($phptime,3).'|T'.number_format($execution,3).'|E'.$errors.'|TE'.$totalerrors.' > '.$actions.' actions';
	logwrite($msg);
}