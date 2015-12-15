<?php $start=microtime(true);
$authenticated=false;
include "functions.php";
if($authenticated) {error_reporting(E_ALL);ini_set("display_errors", "on");}
$time=$_SERVER['REQUEST_TIME'];$timeout=$time-170;
isset($_GET['all'])?$all=true:$all=false;
$domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&used=true&plan=5'),true);
if($all) $domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&used=true'),true);
$domotime=microtime(true)-$start;
if($domoticz) {
	foreach($domoticz['result'] as $dom) {
		isset($dom['Type'])?$Type=$dom['Type']:$Type='None';
		isset($dom['SwitchType'])?$SwitchType=$dom['SwitchType']:$SwitchType='None';
		isset($dom['SubType'])?$SubType=$dom['SubType']:$SubType='None';
		$name=$dom['Name'];
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
	if($SThuis=='On') {
		if($SSlapen=='Off') {
			if(($SPIR_Garage!='Off'||$Spoort!='Closed')&&$SLicht_Garage=='Off'&&$SLicht_Garage_Auto=='On') Schakel($SILicht_Garage, 'On');
			if($SPIR_Inkom!='Off'&&$SLicht_Inkom=='Off'&&$SLicht_Hall_Auto=='On') {Schakel($SILicht_Inkom, 'On');Schakel($SILicht_Hall, 'On');} 
			if($SPIR_Hall!='Off'&&($SLicht_Hall=='Off'||$SLicht_Inkom=='Off')&&$SLicht_Hall_Auto=='On') {Schakel($SILicht_Hall, 'On');Schakel($SILicht_Inkom, 'On');}
			if($SPIR_Living!='Off'&&$STV=='Off'&&$SDenon=='Off'&&$SLamp_Bureel=='Off'&&$SLicht_Hall_Auto=='On'&&$DEettafel=='Off') {
				if($SWerkblad!='On') Schakel($SIWerkblad, 'On');
				if($DEettafel<9) Dim($DIEettafel, 9);
				if($SKerstboom=='Off') Schakel($SIKerstboom, 'On');
				if($time > strtotime('6:00') && $time < strtotime('7:15')) {
					shell_exec('wakeonlan 3c:07:54:22:34:17');
					shell_exec('wakeonlan 00:11:32:2c:b7:21');
					if($SiMac=='Off') Schakel($SIiMac, 'On');
					if($SLamp_Bureel=='Off') Schakel($SILamp_Bureel, 'On');
				}
			}
			if($SDeurBadkamer=='Open'&&$STDeurBadkamer>$time-10&&$STLichtBadkamer1<$time-60&&$SLichtBadkamer1!='On'&&$STLichtBadkamer2<$time-60&&$SLichtBadkamer2=='Off'&&$SLicht_Hall_Auto=='On') Schakel($SILichtBadkamer1, 'On');
		} else if ($SSlapen=='On') {
			if($SPIR_Inkom!='Off'&&$SLicht_Inkom=='Off'&&$SLicht_Hall_Auto=='On') Schakel($SILicht_Inkom, 'On');
			if($SPIR_Hall!='Off'&&$SLicht_Inkom=='Off'&&$SLicht_Hall_Auto=='On') Schakel($SILicht_Inkom, 'On');
			if($SDeurBadkamer=='Open'&&$STDeurBadkamer>$time-10&&$STLichtBadkamer2<$time-60&&$SLichtBadkamer2!='On'&&$STLichtBadkamer1<$time-60&&$SLichtBadkamer1=='Off') {
				if($time > strtotime('6:00') && $time < strtotime('12:00')) Schakel($SILichtBadkamer1, 'On'); else Schakel($SILichtBadkamer2, 'On'); 
			}
		}
	}
	//KODI
/*	if($SLicht_Hall_Auto=='On'&&$SKodi=='On'&&$STV=='On') {
		$urltitle = urlencode('{"jsonrpc": "2.0", "method": "Player.GetItem", "params": { "properties": [], "playerid": 1 }, "id": "VideoGetItem"}');
		$urlspeed = urlencode('{"jsonrpc": "2.0", "method": "Player.GetProperties", "params": { "playerid": 1,"properties": ["speed"] }, "id": 1}');
			$title = json_decode(curl('http://192.168.0.7:1597/jsonrpc?request='.$urltitle));
			if(isset($title->result->item->label)) {
				if($title->result->item->label!='') {
					$speed = json_decode(curl('http://192.168.0.7:1597/jsonrpc?request='.$urlspeed));
					if($speed->result->speed==0) {
						if($DlevelZithoek<15) Dim($DIZithoek, 14);
						if($DlevelEettafel<4) Dim($DIEettafel, 3);
					} else {
						if($DlevelZithoek == 15) Schakel($DIZithoek, 'Off');
						if($DlevelEettafel == 4) Schakel($DIEettafel, 'Off');
					}
				}
			} else {
				if($DlevelZithoek<20) Dim($DIZithoek, 19);
				if($DlevelEettafel<24) Dim($DIEettafel, 23);
			}
	}*/
	//Meldingen
	$deurbel=false;
	if(($SThuis=='Off'||$SSlapen=='On') && $STThuis<$timeout && $SMeldingen=='On') {
		if($Spoort!='Closed') {$msg='Poort open om '.strftime("%H:%M:%S", $STpoort);$deurbel=true;if($mc->get('alertpoort')<$time-60) {$mc->set('alertpoort', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SAchterdeur!='Closed') {$msg='Achterdeur open om '.strftime("%H:%M:%S", $STAchterdeur);$deurbel=true;if($mc->get('alertAchterdeur')<$time-60) {$mc->set('alertAchterdeur', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SRaamLiving!='Closed') {$msg='Raam Living open om '.strftime("%H:%M:%S", $STRaamLiving);$deurbel=true;if($mc->get('alertRaamLiving')<$time-60) {$mc->set('alertRaamLiving', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SPIR_Garage!='Off'&&$STSlapen<$timeout) {$msg='Beweging gedecteerd in garage om '.strftime("%H:%M:%S", $STPIR_Garage);$deurbel=true;if($mc->get('alertPIR_Garage')<$time-60) {$mc->set('alertPIR_Garage', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SPIR_Living!='Off'&&$STSlapen<$timeout) {$msg='Beweging gedecteerd in living om '.strftime("%H:%M:%S", $STPIR_Living);$deurbel=true;if($mc->get('alertPIR_Living')<$time-90) {$mc->set('alertPIR_Living', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($SPIR_Inkom!='Off'&&$STSlapen<$timeout) {$msg='Beweging gedecteerd in inkom om '.strftime("%H:%M:%S", $STPIR_Living);$deurbel=true;if($mc->get('alertPIR_Living')<$time-90) {$mc->set('alertPIR_Living', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	if($SThuis=='Off' && $STThuis<$timeout && $SMeldingen=='On') {
		if($SPIR_Hall!='Off') {$msg='Beweging gedecteerd in hall om '.strftime("%H:%M:%S", $STPIR_Living);$deurbel=true;if($mc->get('telegramPIR_Hall')<$time-90) {$mc->set('alertAchterdeur', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	if($SDeurbel!='Off') {$msg='Deurbel';if($mc->get('alertDeurbel')<$time-60) {$mc->set('alertDeurbel',$time);ios($msg);} Udevice($SIDeurbel,0,'Off');if($SLicht_Hall_Auto=='On') {Schakel($SILicht_Voordeur, 'On');$mc->set('BelLichtVoordeur',2);}}
	if($deurbel&&$STDeurbel<$time-5) {schakel($SIDeurbel, 'On');Udevice($SIDeurbel, 0, 'Off');}
	
	//Refresh Zwave node
	//if($STTV>$eenmin||$STSubwoofer>$eenmin||$STLamp_Bureel>$eenmin) RefreshZwave(61);
	if($STLichtTerrasGarage>$vijfsec) RefreshZwave(16);
	if($STLichtHallZolder>$vijfsec) RefreshZwave(20);
	if($STLichtInkomVoordeur>$vijfsec) RefreshZwave(23);
	if($STKeukenZolder>$vijfsec) RefreshZwave(56);
	if($STWerkbladTuin>$vijfsec) RefreshZwave(68);
	if($STWasbakKookplaat>$vijfsec) RefreshZwave(69);
	if($STLichtBadkamer>$vijfsec) RefreshZwave(65); 

if($all) {
	if($mc->get('domoticzconnection')!=1) {$mc->set('domoticzconnection',1); if(date('G')!=3) telegram('Verbinding met Domoticz hersteld');}
	//Meldingen
	if($SMeldingen=='On') {
		$thermometers=array('Living','Badkamer','KamerTobi','KamerJulius','Zolder');
		$avg=0;
		foreach($thermometers as $thermometer) $avg=$avg+${'T'.$thermometer};
		$avg=$avg / 6;
		foreach($thermometers as $thermometer) {
			if(${'T'.$thermometer}>$avg + 5 && ${'T'.$thermometer} > 25) {$msg='T '.$thermometer.'='.${'T'.$thermometer}.'°C. AVG='.round($avg,1).'°C';
				if($mc->get('alerttemp'.$thermometer)<$time-600) {telegram($msg);ios($msg);if($sms==true) sms($msg);$mc->set('alerttemp'.$thermometer, $time);}
			}
			if(${'SSD'.$thermometer}!='Off') {$msg='Rook gedecteerd in '.$thermometer.'!';telegram($msg);ios($msg);if($sms==true) sms($msg);}
		}
		if($PP_Bureel_Tobi>500) {if($mc->get('alertpowerbureeltobi')<$halfuur) {$msg='Verbruik bureel Tobi='.$PP_Bureel_Tobi;telegram($msg);$mc->set('alertpowerbureeltobi', $time);}}
	}
	
	//Sleep dimmers
	$dimmers = array('Eettafel','Zithoek','Tobi','Kamer');
	foreach($dimmers as $dimmer) {
		if(${'D'.$dimmer}!='Off') {
			$action = $mc->get('dimmer'.$dimmer);
			if($action == 1) {
				$level = floor(${'Dlevel'.$dimmer}*0.8);
				Dim(${'DI'.$dimmer},$level);
				if($level==0) $mc->set('dimmer'.$dimmer,0);
			} else if($action == 2) {
				echo $dimmer;
				$level = ${'Dlevel'.$dimmer}+1;
				if($level>25) $level = 25;
				Dim(${'DI'.$dimmer},$level);
				if($level==25) $mc->set('dimmer'.$dimmer,0);
			} 
		}
	}
	
	//Heating on/off
	if($SThuis=='Off') {if($SHeating!='Off'&&$STHeating<$eenuur) {Schakel($SIHeating, 'Off');$SHeating = 'Off';}
	} else {if($SHeating!='On') {Schakel($SIHeating, 'On');$SHeating = 'On';}}
	
	// 0 = auto, 1 = voorwarmen, 2 = manueel
	//Living
	$Set=16.0;
	$setpointLiving = $mc->get('setpoint130');
	if($setpointLiving!=0 && $RTLiving < $tweeuur) {$mc->set('setpoint130',0);$setpointLiving=0;}
	if($setpointLiving!=2) {
		if($TBuiten<20 && $SHeating=='On' && $SRaamLiving=='Closed') {
				$voorwarmen = voorwarmen($TLiving,20,60);
			     if($time>=( strtotime('6:20')-$voorwarmen) && $time < strtotime('8:30')) $SSlapen=='Off'?$Set=19.0:$Set=18.0;
			else if($time>=( strtotime('8:30')-$voorwarmen) && $time < strtotime('19:00')) $SSlapen=='Off'?$Set=20.0:$Set=18.0;
			else if($time>=(strtotime('19:00')-$voorwarmen) && $time < strtotime('22:00')) $SSlapen=='Off'?$Set=20.0:$Set=18.0;
		}
		if($RLiving != $Set) {Udevice($RILiving,0,$Set);}
		if($RTLiving < $drieuur) Udevice($RILiving,0,$Set);
	}
	$Set = setradiator($TLiving, $RLiving);
	if($RLivingZZ!=$Set) {Udevice($RILivingZZ, 0, $Set);}
	if($RLivingZE!=$Set) {Udevice($RILivingZE, 0, $Set);}
	if($RLivingZB!=$Set) {Udevice($RILivingZB, 0, $Set);}
	
	//Badkamer
	$Set=17.0;
	$setpointBadkamer = $mc->get('setpoint111');
	if($setpointBadkamer!=0 && $RTBadkamer < $eenuur) {$mc->set('setpoint111',0);$setpointBadkamer=0;}
	if($setpointBadkamer!=2) {
		if($TBuiten<21 && $SHeating=='On') {
			$voorwarmen = voorwarmen($TBadkamer,22,140);
			if(in_array(date('N',$time), array(1,2,3,4,5)) && $time>=(strtotime('6:00')-$voorwarmen) && $time<=(strtotime('7:20'))) $Set=21.0;
		     else if(in_array(date('N',$time), array(6,7)) && $time>=(strtotime('7:30')-$voorwarmen) && $time<=(strtotime('9:30'))) $Set=20.0;
		}
		if($SDeurBadkamer!='Closed' && $STDeurBadkamer < $time - 180) $Set=14.0;
		if($RBadkamer != $Set) {Udevice($RIBadkamer,0,$Set);$RBadkamer=$Set;}
		if($RTBadkamer < $drieuur) Udevice($RIBadkamer,0,$Set);
	}
	$Set = setradiator($TBadkamer, $RBadkamer);
	if(in_array(date('N',$time), array(1,2,3,4,5)) && in_array(date('G',$time), array(4,5,6)) && $Set < 21) $Set = 21.0;
	if($RBadkamerZ!=$Set) {Udevice($RIBadkamerZ,0,$Set);}
	
	//Slaapkamer
	$Set = 8.0;
	$setpointKamer = $mc->get('setpoint97');
	if($setpointKamer!=0 && $RTKamer < $eenuur) {$mc->set('setpoint97',0);$setpointKamer=0;}
	if($setpointKamer!=2) {
		if($TBuiten<15 && $SRaamKamer=='Closed' && $SHeating=='On' ) {
			$Set = 14.0;
			if($time < strtotime('8:00') || $time > strtotime('20:00')) $Set = 16.0;
		}
	}
	if($RKamer != $Set) {Udevice($RIKamer,0,$Set);$RKamer=$Set;}
	if($RTKamer < $drieuur) Udevice($RIKamer,0,$Set);
	$Set = setradiator($TKamer, $RKamer);
	if($RKamerZ!=$Set) {Udevice($RIKamerZ,0,$Set);}
	
	//Slaapkamer Tobi
	$Set = 8.0;
	$setpointKamerTobi = $mc->get('setpoint548');
	if($setpointKamerTobi!=0 && $RTKamerTobi < $eenuur) {$mc->set('setpoint549',0);$setpointKamerTobi=0;}
	if($setpointKamerTobi!=2) {
		if($TBuiten<15 && $SRaamKamerTobi=='Closed' && $SHeating=='On') {
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
	if($RKamerTobi != $Set) {Udevice($RIKamerTobi,0,$Set);$RKamerTobi=$Set;}
	if($RTKamerTobi < $time - 8600) Udevice($RIKamerTobi,0,$Set);
	$Set = setradiator($TKamerTobi, $RKamerTobi);
	if($RKamerTobiZ!=$Set) {Udevice($RIKamerTobiZ,0,$Set);}
	
	//Slaapkamer Julius
	$Set = 8.0;
	$setpointKamerJulius = $mc->get('setpoint549');
	if($setpointKamerJulius!=0 && $RTKamerJulius < $eenuur) {$mc->set('setpoint549',0);$setpointKamerJulius=0;}
	if($setpointKamerJulius!=2) {
		if($TBuiten<15 && $SRaamKamerJulius=='Closed' && $SHeating=='On') {
			$Set = 14.0;
		}
	}
	if($RKamerJulius != $Set) {Udevice($RIKamerJulius,0,$Set);$RKamerJulius=$Set;}
	if($RTKamerJulius < $time - 8600) Udevice($RIKamerJulius,0,$Set);
	$Set = setradiator($TKamerJulius, $RKamerJulius);
	if($RKamerJuliusZ!=$Set) {Udevice($RIKamerJuliusZ,0,$Set);}
	
	//Brander
	if(($TLiving < $RLiving || $TBadkamer < $RBadkamer || $TKamer < $RKamer || $TKamerTobi < $RKamerTobi || $TKamerJulius < $RKamerJulius ) && $SBrander == "Off" && $STBrander < $time-250) Schakel($SIBrander, 'On');
	if($TLiving >= $RLiving-0.2 && $TBadkamer >= $RBadkamer-0.4 && $TKamer >= $RKamer-0.2 && $TKamerTobi >= $RKamerTobi-0.2 && $TKamerJulius >= $RKamerJulius-0.2 && $SBrander == "On" && $STBrander < $time-250) Schakel($SIBrander, 'Off');
	//if($STBrander<$time-600) Schakel($SIBrander, $SBrander);
	
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
	if($SLicht_Voordeur!='Off') {if($mc->get('BelLichtVoordeur')==2) Schakel($SILicht_Voordeur,'Off');} 
	
	//Slapen-niet thuis bij geen beweging
	if($STPIR_Living<$time-14400&&$STPIR_Garage<$time-14400&&$STPIR_Inkom<$time-14400&&$STPIR_Hall<$time-14400&&$STSlapen<$time-14400&&$STThuis<$time-14400&&$SThuis=='On'&&$SSlapen=="Off") {Schakel($SISlapen,'On');telegram('Slapen ingeschakeld na 4 uur geen beweging');}
	if($STPIR_Living<$time-43200&&$STPIR_Garage<$time-43200&&$STPIR_Inkom<$time-43200&&$STPIR_Hall<$time-43200&&$STSlapen<$time-28800&&$STThuis<$time-43200&&$SThuis=='On'&&$SSlapen=="On") {Schakel($SISlapen, 'Off');Schakel($SIThuis, 'Off');telegram('Thuis uitgeschakeld na 12 uur geen beweging');}
	
	//Laptop Pluto zonnepanelen
	if($time > $zononder - 4000 && $time < $zononder + 4000) {
		if($SPluto=='Off') Schakel($SIPluto, 'On');
	} else {
		if($SPluto=='On') Schakel($SIPluto, 'Off');
	}
	
	//Lichten uitschakelen na X uur. 
	
	if($DTobi!='Off'&&$DTTobi<$drieuur) Schakel($DITobi, 'Off');
	if($SLicht_Garage!='Off'&&$STLicht_Garage<$tweeuur) Schakel($SILicht_Garage, 'Off');
	if($SLicht_Voordeur!='Off'&&$STLicht_Voordeur<$tweeuur) Schakel($SILicht_Voordeur, 'Off');
	if($SLicht_Hall!='Off'&&$STLicht_Hall<$tweeuur) Schakel($SILicht_Hall, 'Off');
	if($SLicht_Inkom!='Off'&&$STLicht_Inkom<$tweeuur) Schakel($SILicht_Inkom, 'Off');
	if($SKeuken!='Off'&&$STKeuken<$tweeuur) Schakel($SIKeuken, 'Off');
	if($SWasbak!='Off'&&$STWasbak<$tweeuur) Schakel($SIWasbak, 'Off');
	if($SKookplaat!='Off'&&$STKookplaat<$tweeuur) Schakel($SIKookplaat, 'Off');
	if($SWerkblad!='Off'&&$STWerkblad<$tweeuur) Schakel($SIWerkblad, 'Off');
	if($SZolderG!='Off'&&$STZolderG<$tweeuur) Schakel($SIZolderG, 'Off');
	if($SLichtBadkamer1!='Off'&&$STLichtBadkamer1<$tweeuur) Schakel($SILichtBadkamer1, 'Off');
	if($SLichtBadkamer2!='Off'&&$STLichtBadkamer2<$tweeuur) Schakel($SILichtBadkamer2, 'Off');
		
	//Meldingen inschakelen indien langer dan 12 uur uit. 
	if($SMeldingen!='On' && $STMeldingen<$time-43200) Schakel($SIMeldingen, 'On');
	
	//KODI
	if($SKodi=='On'&&$STKodi<$timeout) {
		$status = pingDomain('192.168.0.7', 1597);
		if(is_int($status)) Schakel($SIKodi, 'Off');
	}
	//DiskStation
	if($SDiskStation=='On'&&$STDiskStation<$timeout) {
		$status = pingDomain('192.168.0.10', 1600);
		if(is_int($status)) {
			Schakel($SIDiskStation, 'Off');
			//telegram('Diskstion powered off');
		}
	} else if($SDiskStation=='Off' &&($SBureel_Tobi=='On'||$SKodi=='On'||$SiMac=='On')) {
		shell_exec('wakeonlan 00:11:32:2c:b7:21');
		//telegram('WOL sent to Diskstation');
	}
	
	//Alles uitschakelen
	if($SThuis=='Off'||$SSlapen=="On") {
		if($STV!='Off'&&$STTV<$eenmin) Schakel($SITV, 'Off');
		if($SKristal!='Off'&&$STKristal<$eenmin) Schakel($SIKristal, 'Off');
		if($STVLed!='Off'&&$STTVLed<$eenmin) Schakel($SITVLed, 'Off');
		if($SDenon!='Off'&&$STDenon<$eenmin) Schakel($SIDenon, 'Off');
		if($SLamp_Bureel!='Off'&&$STLamp_Bureel<$eenmin) Schakel($SILamp_Bureel, 'Off');
		if($STerras!='Off'&&$STTerras<$eenmin) Schakel($SITerras, 'Off');
		if($SLicht_Garage!='Off'&&$STLicht_Garage<$eenmin) Schakel($SILicht_Garage, 'Off');
		if($SLicht_Voordeur!='Off'&&$STLicht_Voordeur<$eenmin) Schakel($SILicht_Voordeur, 'Off');
		if($SLicht_Hall!='Off'&&$STLicht_Hall<$eenmin&&$STPIR_Hall<$eenmin) Schakel($SILicht_Hall, 'Off');
		if($SLicht_Inkom!='Off'&&$STLicht_Inkom<$eenmin&&$STPIR_Inkom<$eenmin) Schakel($SILicht_Inkom, 'Off');
		if($SLicht_Zolder!='Off'&&$STLicht_Zolder<$eenmin) Schakel($SILicht_Zolder, 'Off');
		if($SBureel_Tobi!='Off'&&$STBureel_Tobi<$eenmin) Schakel($SIBureel_Tobi, 'Off');
		if($DKamer!='Off'&&$DTKamer<$eenuur) Schakel($DIEettafel, 'Off');
		if($DEettafel!='Off'&&$DTEettafel<$eenmin) Schakel($DIEettafel, 'Off');
		if($DZithoek!='Off'&&$DTZithoek<$eenmin) Schakel($DIZithoek, 'Off');
		if($setpointLiving!=0 && $RTLiving<$eenuur) $mc->set('setpoint130',0);
		if($setpointBadkamer!=0 && $RTBadkamer<$eenuur) $mc->set('setpoint111',0);
		if($setpointKamer!=0 && $RTKamer<$eenuur) $mc->set('setpoint97',0);
		if($setpointKamerTobi!=0 && $RTKamerTobi<$eenuur) $mc->set('setpoint548',0);
		if($setpointKamerJulius!=0 && $RTKamerJulius<$eenuur) $mc->set('setpoint549',0);
		if($SKeuken!='Off'&&$STKeuken<$eenmin) Schakel($SIKeuken, 'Off');
		if($SWasbak!='Off'&&$STWasbak<$eenmin) Schakel($SIWasbak, 'Off');
		if($SKookplaat!='Off'&&$STKookplaat<$eenmin) Schakel($SIKookplaat, 'Off');
		if($SWerkblad!='Off'&&$STWerkblad<$eenmin) Schakel($SIWerkblad, 'Off');
		if($SZolderG!='Off'&&$STZolderG<$eenmin) Schakel($SIZolderG, 'Off');
		if($SKerstboom!='Off'&&$STKerstboom<$eenmin) Schakel($SIKerstboom, 'Off');
		if($SiMac!='Off'&&$STiMac<$time-300) Schakel($SIiMac, 'Off');
	}	
	if($SThuis=='Off') {
		if($SLichtBadkamer1!='Off'&&$STBadkamer1<$eenmin) Schakel($SIBadkamer1, 'Off');
		if($SLichtBadkamer2!='Off'&&$STBadkamer2<$eenmin) Schakel($SIBadkamer1, 'Off');
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
if($authenticated) 	echo '<hr>Number of vars: '.count(get_defined_vars()).'<br/><pre>';print_r(get_defined_vars());echo '</pre>';
//$execution= microtime(true)-$start;$phptime=$execution-$domotime;if($all) $msg='D'.round($domotime,3).'|P'.round($phptime,3).'|T'.round($execution,3).'|All';else $msg='D'.round($domotime,3).'|P'.round($phptime,3).'|T'.round($execution,3);telegram($msg);
