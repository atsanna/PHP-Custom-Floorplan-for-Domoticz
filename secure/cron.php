<?php 
$authenticated=false;
include "functions.php";
//if($authenticated) {error_reporting(E_ALL);ini_set("display_errors", "on");}
$time=$_SERVER['REQUEST_TIME'];$starttime=microtime(true);
$all=false;
if(isset($_GET['all'])) if($_GET['all']==1) $all=true;
if(isset($argv[1])) if($argv[1]=='all') $all=true;
if($all){$domoticz=json_decode(file_get_contents($domoticzurl.'json.htm?type=devices&used=true'),true);}
else {$domoticz=json_decode(file_get_contents($domoticzurl.'json.htm?type=devices&used=true&plan=3'),true);}
$domotime=microtime(true)-$starttime;
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
	//Automatische lichten inschakelen
	if($Sslapen=='Off') {
		if(($Spirgarage!='Off'||$Spoort!='Closed')&&$Sgarage=='Off'&&$Sgarage_auto=='On') {Schakel($SIgarage, 'On', 'licht garage');}
		if($Spirinkom!='Off'&&$Sinkom=='Off'&&$Shall_auto=='On') {Schakel($SIinkom, 'On', 'licht inkom');Schakel($SIhall, 'On','licht hall');} 
		if($Spirhall!='Off'&&($Shall=='Off'||$Sinkom=='Off')&&$Shall_auto=='On') {Schakel($SIhall, 'On','licht hall');Schakel($SIinkom, 'On','licht inkom');}
		if(($Spirliving!='Off'||$SpirlivingR!='Off')&&$Sdenon=='Off'&&$Sbureel=='Off'&&$STslapen<$eenmin) {
			if($Shall_auto=='On') {if($Sbureel=='Off') Schakel($SIbureel, 'On','lamp bureel');if($Deettafel<8) {Dim($DIeettafel, 8,'eettafel');}}
			if($Sdenon=='Off') Udevice(439,1,'On','Radio luisteren');
		}
		if($Spirkeuken!='Off' && $Swerkblad=='Off' && $Skookplaat=='Off' && $Swasbak=='Off' && $Skeuken=='Off' && $Shall_auto=='On') {Schakel($SIwerkblad, 'On', 'werkblad door pir');Schakel($SIkookplaat, 'On', 'kookplaat door pir');}
		if($Sdeurbadkamer=='Open'&&$STdeurbadkamer>$time-10&&$STlichtbadkamer1<$eenmin&&$Slichtbadkamer1!='On'&&$STlichtbadkamer2<$eenmin&&$Slichtbadkamer2=='Off'&&$Shall_auto=='On') Schakel($SIlichtbadkamer1, 'On','badkamer1 door deur');
	} else {
		if($Spirinkom!='Off'&&$Sinkom=='Off'&&$Shall_auto=='On') Schakel($SIinkom, 'On','licht inkom');
		if($Spirhall!='Off'&&$Sinkom=='Off'&&$Shall_auto=='On') Schakel($SIinkom, 'On','licht inkom');
		if($Sdeurbadkamer=='Open'&&$STdeurbadkamer>$time-10&&$STlichtbadkamer2<$eenmin&&$Slichtbadkamer2!='On'&&$STlichtbadkamer1<$eenmin&&$Slichtbadkamer1=='Off') {
			if($time > strtotime('6:00') && $time < strtotime('12:00')) Schakel($SIlichtbadkamer1, 'On','licht badkamer1'); else Schakel($SIlichtbadkamer2, 'On','badkamer2 door deur'); 
		}
	}
	//meldingen
	$sirene=false;
	if(($Sweg=='On'||$Sslapen=='On') && $STweg<$driemin && $Smeldingen=='On') {
		if($Spoort!='Closed') {$msg='Poort open om '.strftime("%H:%M:%S", $STpoort);$sirene=true;if($mc->get('alertpoort')<$eenmin) {$mc->set('alertpoort', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Sachterdeur!='Closed') {$msg='Achterdeur open om '.strftime("%H:%M:%S", $STachterdeur);$sirene=true;if($mc->get('alertAchterdeur')<$eenmin) {$mc->set('alertAchterdeur', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Sraamliving!='Closed') {$msg='raam living open om '.strftime("%H:%M:%S", $STraamliving);$sirene=true;if($mc->get('alertraamliving')<$eenmin) {$mc->set('alertraamliving', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		if($Spirgarage!='Off'&&$STslapen<$driemin) {$msg='Beweging gedecteerd in garage om '.strftime("%H:%M:%S", $STpirgarage);$sirene=true;if($mc->get('alertpirgarage')<$eenmin) {$mc->set('alertpirgarage', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
		/*if(($Spirliving!='Off'||$SpirlivingR!='Off'||$Spirkeuken!='Off')&&$STslapen<$driemin) {
			//file_get_contents('http://picam1:8080/0/action/snapshot');
			Dim($DIzithoek,100,'living alarm');
			Dim($DIeettafel,100,'living alarm');
			Schakel($SIbureel, 'On', 'living alarm');
			Schakel($SIwerkblad, 'On', 'living alarm');
			Schakel($SIkookplaat, 'On', 'living alarm');
			Schakel($SIwasbak, 'On', 'living alarm');
			$msg='Beweging gedecteerd in living om '.strftime("%H:%M:%S", $STpirliving);
			$sirene=true;
			//file_get_contents('http://picam1:8080/0/action/snapshot');
			if($mc->get('alertpirliving')<$time-90) {
				$mc->set('alertpirliving', $time);
				telegram($msg);
				ios($msg);
				if($sms==true) smd($msg);
			}
		}*/
		if($Spirinkom!='Off'&&$STslapen<$driemin) {$msg='Beweging gedecteerd in inkom om '.strftime("%H:%M:%S", $STpirinkom);$sirene=true;if($mc->get('alertinkom')<$time-90) {$mc->set('alertpirinkom', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	if($Sweg=='On' && $STweg<$driemin && $Smeldingen=='On') {
		if($Spirhall!='Off') {$msg='Beweging gedecteerd in hall om '.strftime("%H:%M:%S", $STpirhall);$sirene=true;if($mc->get('telegrampirhall')<$time-90) {$mc->set('alertpirhall', $time);telegram($msg);ios($msg);if($sms==true) smd($msg);}}
	}
	/*if($STdeurbel>$vijfsec) {
		$msg='Deurbel';
		if($mc->get('alertDeurbel')<$eenmin) {
			$mc->set('alertDeurbel',$time);
			ios($msg);
		} 
		//Udevice($SIdeurbel,0,'Off','deurbel');
		if($Shall_auto=='On') {
			Schakel($SIvoordeur, 'On','licht voordeur');
			$mc->set('Bellichtvoordeur',2);
		}
		
	}*/
	//minimote living
	$ctx = stream_context_create(array('http'=>array('timeout' => 2,)));
	if($Sminiliving1s=='On'&&$STminiliving1s>$tweesec) {
		if($Sdenon!='On') Schakel($SIdenon,'On','mini Denon');
		Schakel(48,'On','mini TV');
		if($Shall_auto=='On') {
			Schakel(51,'On','mini Kristal');
			Schakel(52,'On','mini TVled');
			if($Sbureel!='On') Schakel($SIbureel,'On','mini Bureel');
		}
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_OnOff%2FON&cmd1=aspMainZone_WebUpdateStatus%2F',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_InputFunction/SAT/CBL',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutMasterVolumeSet/-50.0',false, $ctx);
		Schakel(53,'On','mini Subwoofer');
		Udevice($SIminiliving1s, 'Off');
	}
	if($Sminiliving1l=='On'&&$STminiliving1l>$tweesec) {
		Schakel(48,'Off','mini TV');
		Schakel(51,'Off','mini Kristal');
		Schakel(52,'Off','mini TVLed');
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_OnOff%2FON&cmd1=aspMainZone_WebUpdateStatus%2F',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutMasterVolumeSet/-55.0',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_InputFunction/TUNER',false, $ctx);
		Udevice($SIminiliving1l, 'Off');
	}
	if($Sminiliving2s=='On'&&$STminiliving2s>$tweesec) {
		if($Sdenon!='On') Schakel($SIdenon,'On','mini Denon');
		Schakel(48,'On','mini TV');
		Schakel(54,'On','mini Kodi');
		if($Shall_auto=='On') {
			Schakel(51,'On','mini Kristal');
			Schakel(52,'On','mini TVLed');
			if($Sbureel!='On') Schakel($SIbureel,'On','mini Bureel');
		}
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_OnOff%2FON&cmd1=aspMainZone_WebUpdateStatus%2F',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutMasterVolumeSet/-45.0',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_InputFunction/DVD',false, $ctx);
		Schakel(53,'On','mini Subwoofer');
		Udevice($SIminiliving2s, 'Off');
	}
	if($Sminiliving2l=='On'&&$STminiliving2l>$tweesec) {
		Schakel(48,'Off','mini TV');
		Schakel(51,'Off','mini Kristal');
		Schakel(52,'Off','mini TVled');
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_OnOff%2FON&cmd1=aspMainZone_WebUpdateStatus%2F',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutMasterVolumeSet/-55.0',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_InputFunction/TUNER',false, $ctx);
		Udevice($SIminiliving2l, 'Off');
	}
	if($Sminiliving3s=='On'&&$STminiliving3s>$tweesec) {
		Dim(425,9,'mini Eettafel');
		Schakel(48,'Off','mini TV');
		Schakel(51,'Off','mini kristal');
		Schakel(52,'Off','mini TVled');
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_OnOff%2FON&cmd1=aspMainZone_WebUpdateStatus%2F',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutMasterVolumeSet/-55.0',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_InputFunction/TUNER',false, $ctx);
		Schakel(53,'On','mini Subwoofer');
		Udevice($SIminiliving3s, 'Off');
	}
	if($Sminiliving3l=='On'&&$STminiliving3l>$tweesec) {
		if($Sdenon!='On') Schakel($SIdenon,'On','mini Denon');
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_OnOff%2FON&cmd1=aspMainZone_WebUpdateStatus%2F',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutMasterVolumeSet/-55.0',false, $ctx);
		file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutZone_InputFunction/TUNER',false, $ctx);
		Schakel(53,'On','mini Subwoofer');
		Schakel(48,'Off','mini TV');
		Schakel(51,'Off','mini kristal');
		Schakel(52,'Off','mini TVled');
		Udevice($SIminiliving3l, 'Off');
	}
	if($Sminiliving4s=='On'&&$STminiliving4s>$tweesec) {
		if($Spirkeuken!='Off') Udevice($SIpirkeuken, 'Off');
		if($Spirgarage!='Off') Udevice($SIpirgarage, 'Off');
		if($Spirinkom!='Off') Udevice($SIpirinkom, 'Off');
		if($Spirhall!='Off') Udevice($SIpirhall, 'Off');
		Schakel(74,'Off','mini Zithoek');
		Schakel(425,'Off','mini Eettafel');
		Schakel(184,'Off','mini Werkblad');
		Schakel(176,'Off','mini Kookplaat');
		Schakel(175,'Off','mini Wasbak');
		Schakel(212,'Off','mini garage');
		Schakel(202,'Off','mini inkom');
		Schakel(272,'Off','mini hall');
		Udevice($SIminiliving4s, 'Off');
	}
	if($Sminiliving4l=='On'&&$STminiliving4l>$tweesec) {
		Schakel(212,'On','mini garage');
		Udevice($SIminiliving4l, 'Off');
	}
	
	if($sirene&&$SSirene=='Off') {
		Schakel($SISirene, 'On','ALARM');
		sleep(1);
		Schakel($SISirene, 'Off', 'ALARM');
	} else if ($SSirene=='On') Schakel($SISirene,'Off','Reset sirene');
	//Refresh Zwave node
	if($STkeukenzolderg>$tweesec) RefreshZwave(20,'KeukenZolder');
	if($STwasbakkookplaat>$tweesec) RefreshZwave(21,'WasbakKookplaat');
	if($STwerkbladtuin>$tweesec) RefreshZwave(22,'WerkbladTuin');
	if($STinkomvoordeur>$tweesec) RefreshZwave(23,'InkomVoordeur');
	if($STgarageterras>$tweesec) RefreshZwave(24,'TerrasGarage');
	if($STlichtbadkamer>$tweesec) RefreshZwave(25,'LichtBadkamer'); 
	if($SThallzolder>$tweesec) RefreshZwave(35,'HallZolder');
	
	if(($Sweg=='On'&&$STweg>$tweesec)||($Sslapen=='On'&&$STslapen>$tweesec)) shell_exec('/usr/bin/php /var/www/secure/cron.php all > /dev/null 2>&1 &');
	if($all) {
		if($mc->get('domoticzconnection')!=1) {$mc->set('domoticzconnection',1); telegram('Verbinding met Domoticz hersteld');}
		//meldingen
		if($Smeldingen=='On') {
			$items=array('living','badkamer','tobi','julius','zolder');
			$avg=0;
			foreach($items as $item) $avg=$avg+${'T'.$item};
			$avg=$avg / 6;
			foreach($items as $item) {
				if(${'T'.$item}>$avg + 5 && ${'T'.$item} > 25) {$msg='T '.$item.'='.${'T'.$item}.'°C. AVG='.round($avg,1).'°C';
					if($mc->get('alerttemp'.$item)<$tienmin) {telegram($msg);ios($msg);if($sms==true) sms($msg);$mc->set('alerttemp'.$item, $time);}
				}
				if(${'SSD'.$item}!='Off') {
					$msg='Rook gedecteerd in '.$item.'!';
					telegram($msg);
					ios($msg);
					if($sms==true) sms($msg);
					if(${'STSD'.$item}<$tweemin) resetsecurity(${'SISD'.$item},$item);
				}
			}
			//if($Pbureeltobi>500) {if($mc->get('alertpowerbureeltobi')<$eenuur) {$msg='Verbruik bureel tobi='.$Pbureeltobi;telegram($msg);$mc->set('alertpowerbureeltobi', $time);}}
		}
		//Sleep dimmers
		$items = array('eettafel','zithoek','tobi','kamer');
		foreach($items as $item) {
			if(${'D'.$item}!='Off') {
				$action = $mc->get('dimmer'.$item);
				if($action == 1) {
					$level = floor(${'Dlevel'.$item}*0.95);
					Dim(${'DI'.$item},$level,$item);
					if($level==0) $mc->set('dimmer'.$item,0);
				} else if($action == 2&&date('i')%2==1) {
					$level = ${'Dlevel'.$item}+1;
					if($level>30) $level = 30;
					Dim(${'DI'.$item},$level,$item);
					if($level==30) $mc->set('dimmer'.$item,0);
				} 
			}
		}
		//heating on/off
		if($Sweg=='On') {if($Sheating!='Off'&&$STheating<$eenuur) {Schakel($SIheating, 'Off','heating');$Sheating = 'Off';}
		} else {if($Sheating!='On') {Schakel($SIheating, 'On','heating');$Sheating = 'On';}}
		// 0 = auto, 1 = voorwarmen, 2 = manueel
		//living
		$Set=16.0;
		$setpointliving = $mc->get('setpointliving');
		if($setpointliving!=0 && $RTliving < $tweeuur) {$mc->set('setpointliving',0);$setpointliving=0;}
		if($setpointliving!=2) {
			if($Tbuiten<20 && $Sheating=='On'/* && $Sraamliving=='Closed'*/) {
					 if($time>= strtotime('5:00') && $time <  strtotime('7:00')) $Sslapen=='Off'?$Set=19.0:$Set=18.0;
				else if($time>= strtotime('7:00') && $time <  strtotime('8:30')) $Sslapen=='Off'?$Set=19.0:$Set=19.0;
				else if($time>= strtotime('8:30') && $time < strtotime('19:00')) $Sslapen=='Off'?$Set=20.0:$Set=19.0;
				else if($time>=strtotime('19:00') && $time < strtotime('22:00')) $Sslapen=='Off'?$Set=20.0:$Set=19.0;
			}
			if($Rliving != $Set) {Udevice($RIliving,0,$Set,'Rliving');$Rliving=$Set;}
			if($RTliving < $drieuur) Udevice($RIliving,0,$Set,'Rliving');
		}
		$Set = setradiator($Tliving, $Rliving);
		if($RlivingZZ!=$Set) {Udevice($RIlivingZZ,0,$Set,'RlivingZZ');}
		if($RlivingZE!=$Set) {Udevice($RIlivingZE,0,$Set,'RlivingZE');}
		if($RlivingZB!=$Set) {Udevice($RIlivingZB,0,$Set,'RlivingZB');}
		//badkamer
		$Set=16.0;
		$setpointbadkamer = $mc->get('setpointbadkamer');
		if($setpointbadkamer!=0 && $RTbadkamer < $eenuur) {$mc->set('setpointbadkamer',0);$setpointbadkamer=0;}
		if($setpointbadkamer!=2) {
			if($Tbuiten<21 && $Sheating=='On') {
				if(in_array(date('N',$time), array(1,2,3,4,5)) && $time>=strtotime('5:00') && $time<=strtotime('6:00')) $Set=19.0;
				else if(in_array(date('N',$time), array(1,2,3,4,5)) && $time>=strtotime('6:00') && $time<=strtotime('7:20')) $Set=21.0;
				else if(in_array(date('N',$time), array(6,7)) && $time>=strtotime('7:30') && $time<=strtotime('9:30')) $Set=20.0;
				else if($time>=strtotime('9:30') && $time<=strtotime('23:59') && $Sslapen=='Off') $Set=18.0;

			}
			if($Sdeurbadkamer!='Closed' && $STdeurbadkamer < $time - 180) $Set=14.0;
			if($Rbadkamer != $Set) {Udevice($RIbadkamer,0,$Set,'Rbadkamer');$Rbadkamer=$Set;}
			if($RTbadkamer < $drieuur) Udevice($RIbadkamer,0,$Set,'Rbadkamer');
		}
		$Set = setradiator($Tbadkamer, $Rbadkamer);
		if(in_array(date('N',$time), array(1,2,3,4,5)) && in_array(date('G',$time), array(4,5,6)) && $Set < 21) $Set = 21.0;
		if($RbadkamerZ!=$Set) {Udevice($RIbadkamerZ,0,$Set,'RbadkamerZ');}
		//Slaapkamer
		$Set = 8.0;
		$setpointkamer = $mc->get('setpointkamer');
		if($setpointkamer!=0 && $RTkamer < $eenuur) {$mc->set('setpointkamer',0);$setpointkamer=0;}
		if($setpointkamer!=2) {
			if($Tbuiten<15 && $Sraamkamer=='Closed' && $Sheating=='On' ) {
				$Set = 12.0;
				if($time < strtotime('8:00') || $time > strtotime('20:00')) $Set = 16.0;
			}
		}
		if($Rkamer != $Set) {Udevice($RIkamer,0,$Set,'Rkamer');$Rkamer=$Set;}
		if($RTkamer < $drieuur) Udevice($RIkamer,0,$Set,'Rkamer');
		$Set = setradiator($Tkamer, $Rkamer);
		if($RkamerZ!=$Set) {Udevice($RIkamerZ,0,$Set,'RkamerZ');}
		//Slaapkamer tobi
		$Set = 8.0;
		$setpointtobi = $mc->get('setpointtobi');
		if($setpointtobi!=0 && $RTtobi < $eenuur) {$mc->set('setpointtobi',0);$setpointtobi=0;}
		if($setpointtobi!=2) {
			if($Tbuiten<15 && $Sraamtobi=='Closed' && $Sheating=='On') {
				$Set = 12.0;
				if (date('W')%2==1) {
						 if (date('N') == 3) { if($time > strtotime('20:00')) $Set = 16.0;}
					else if (date('N') == 4) { if($time < strtotime('8:00') || $time > strtotime('20:00')) $Set = 16.0;}
					else if (date('N') == 5) { if($time < strtotime('8:00')) $Set = 16.0;}
				} else {
						 if (date('N') == 3) { if($time > strtotime('20:00')) $Set = 16.0;}
					else if (in_array(date('N'),array(4,5,6))) { if($time < strtotime('8:00') || $time > strtotime('20:00')) $Set = 16.0;}
					else if (date('N') == 7) { if($time < strtotime('8:00')) $Set = 16.0;}
				}
			}
			if($Rtobi != $Set) {Udevice($RItobi,0,$Set,'Rtobi');$Rtobi=$Set;}
			if($RTtobi < $time - 8600) Udevice($RItobi,0,$Set,'Rtobi');
		}
		$Set = setradiator($Ttobi, $Rtobi);
		if($RtobiZ!=$Set) {Udevice($RItobiZ,0,$Set,'RtobiZ');}
		//Slaapkamer julius
		$Set = 8.0;
		$setpointjulius = $mc->get('setpointjulius');
		if($setpointjulius!=0 && $RTjulius < $eenuur) {$mc->set('setpointjulius',0);$setpointjulius=0;}
		if($setpointjulius!=2) {
			if($Tbuiten<15 && $Sraamjulius=='Closed' && $Sheating=='On') {
				$Set = 10.0;
			}
			if($Rjulius != $Set) {Udevice($RIjulius,0,$Set,'Rjulius');$Rjulius=$Set;}
			if($RTjulius < $time - 8600) Udevice($RIjulius,0,$Set,'Rjulius');
		}
		$Set = setradiator($Tjulius, $Rjulius);
		if($RjuliusZ!=$Set) {Udevice($RIjuliusZ,0,$Set,'RjuliusZ');}
		//brander
		if(($Tliving < $Rliving || $Tbadkamer < $Rbadkamer || $Tkamer < $Rkamer || $Ttobi < $Rtobi || $Tjulius < $Rjulius ) && $Sbrander == "Off" && $STbrander < $time-250) Schakel($SIbrander, 'On', 'brander');
		if($Tliving >= $Rliving-0.1 && $Tbadkamer >= $Rbadkamer-0.2 && $Tkamer >= $Rkamer-0.1 && $Ttobi >= $Rtobi-0.1 && $Tjulius >= $Rjulius-0.1 && $Sbrander == "On" && $STbrander < $time-190) Schakel($SIbrander, 'Off', 'brander');
		//if($STbrander<$eenuur) Schakel($SIbrander, $Sbrander,'Brander update');
		//Denon - Subwoofer
		$denonstatus = json_decode(json_encode(simplexml_load_string(file_get_contents($denon_address.'/goform/formMainZone_MainZoneXml.xml?_='.$time,false, stream_context_create(array('http'=>array('timeout' => 2,)))))), TRUE);
		if($Sdenon=='On') {
			if($Ssubwoofer!='On') Schakel($SIsubwoofer,'On','subwoofer');
			if($denonstatus['Power']['value']!='ON') file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutSystem_OnStandby%2FON&cmd1=aspMainZone_WebUpdateStatus%2F');
		}
		else if($Sdenon=='Off') {
			if($Ssubwoofer!='Off') Schakel($SIsubwoofer,'Off','subwoofer');
			if($denonstatus['Power']['value']!='OFF') file_get_contents($denon_address.'/MainZone/index.put.asp?cmd0=PutSystem_OnStandby%2FSTANDBY&cmd1=aspMainZone_WebUpdateStatus%2F');
		}
		//PIRS resetten
		if($Spirliving!='Off'&&$STpirliving<$eenmin) Schakel($SIpirliving,'Off','PIR living');
		
		//Automatische lichten uitschakelen
		if($STgarage_auto < $time-7200) {
			if($time>$zonop+10800 && $time<$zononder-10800) {if($Sgarage_auto=='On' && $Sgarage=='Off') Schakel($SIgarage_auto,'Off','licht garage');}
			else if($Sgarage_auto=='Off') Schakel($SIgarage_auto,'On','licht garage');}
		if($SThall_auto < $time-7200) {
			if($time>$zonop+1800 && $time<$zononder-1800) {if($Shall_auto=='On' && $Shall=='Off' && $Sinkom=='Off') Schakel($SIhall_auto,'Off','hall auto');}
			else if($Shall_auto=='Off') Schakel($SIhall_auto,'On','hall auto');}
		if($Spirgarage=='Off'&&$Spoort=='Closed'&&$STpirgarage<$driemin&&$STpoort<$driemin&&$STgarage<$eenmin&&$Sgarage=='On'&&$Sgarage_auto=='On') Schakel($SIgarage,'Off','licht garage');
		if($STpirinkom<$tweemin&&$STpirhall<$tweemin&&$STinkom<$eenmin&&$SThall<$eenmin&&$Shall_auto=='On') {
			if($Sinkom=='On') Schakel($SIinkom,'Off','licht inkom');
			if($Shall=='On') Schakel($SIhall,'Off','licht hall');
		}
		if($STpirkeuken<$driemin&&$Spirkeuken=='Off'&&$Swasbak=='Off'&&($Swerkblad=='On'||$Skookplaat=='On')) {
			Schakel($SIkookplaat,'Off','pir keuken');
			Schakel($SIwerkblad,'Off','pir keuken');
		}
		if($Svoordeur!='Off') {if($mc->get('Bellichtvoordeur')==2) Schakel($SIvoordeur,'Off','licht voordeur');} 
		//slapen-weg bij geen beweging
		if($STpirliving<$time-14400&&$STpirlivingR<$time-14400&&$STpirgarage<$time-14400&&$STpirinkom<$time-14400&&$STpirhall<$time-14400&&$STslapen<$time-14400&&$STweg<$time-14400&&$Sweg=='Off'&&$Sslapen=="Off") {Schakel($SIslapen,'On','slapen');telegram('slapen ingeschakeld na 4 uur geen beweging');}
		if($STpirliving<$twaalfuur&&$STpirlivingR<$twaalfuur&&$STpirgarage<$twaalfuur&&$STpirinkom<$twaalfuur&&$STpirhall<$twaalfuur&&$STslapen<$time-28800&&$STweg<$twaalfuur&&$Sweg=='Off'&&$Sslapen=="On") {Schakel($SIslapen, 'Off','slapen');Schakel($SIweg, 'On','weg');telegram('weg ingeschakeld na 12 uur geen beweging');}
		
		//meldingen inschakelen indien langer dan 3 uur uit. 
		if($Smeldingen!='On' && $STmeldingen<$drieuur) Schakel($SImeldingen, 'On','meldingen');
		//Alles uitschakelen
		if($Sweg=='On'||$Sslapen=='On') {
			$STweg>$tweemin||$STslapen>$tweemin?$uit=$driemin:$uit=$drieuur;
			if($STweg>$tweemin&&$Sweg=='On') {
				$items=array('bureel','werkblad','kookplaat','voordeur','keuken','wasbak','zolderg','lichtbadkamer1','lichtbadkamer2','tv','tvled','kristal','denon','subwoofer','terras','zolder','bureeltobi');
				$msg='pas weg';
			}
			else if($STweg<$tweemin&&$Sweg=='On') {
				$items=array('bureel','werkblad','kookplaat','voordeur','keuken','wasbak','zolderg','lichtbadkamer1','lichtbadkamer2','tv','tvled','kristal','denon','subwoofer','terras','zolder','bureeltobi');
				$msg='1h weg';
			}
			else if($STslapen>$tweemin&&$Sslapen=='On') {
				$items=array('bureel','werkblad','kookplaat','voordeur','keuken','wasbak','zolderg','tv','tvled','kristal','denon','subwoofer','terras','zolder','bureeltobi');
				$msg='pas gaan slapen';
			}
			else if($STslapen<$tweemin&&$Sslapen=='On') {
				$items=array('bureel','werkblad','kookplaat','voordeur','keuken','wasbak','zolderg','tv','tvled','kristal','denon','subwoofer','terras','zolder','bureeltobi');
				$msg='1h slapen';
			} 
			foreach ($items as $item) {
				if(${'ST'.$item}<$uit-mt_rand(0,600)) Schakel(${'SI'.$item},'Off',$item.' '.$msg);
			}
			$items = array('eettafel','zithoek');
			foreach ($items as $item) {
				if(${'DT'.$item}<$uit-mt_rand(0,600)) Schakel(${'DI'.$item},'Off',$item.' '.$msg);
			}
			$items = array('living','badkamer','kamer','tobi','julius');
			foreach ($items as $item) {
				${'setpoint'.$item} = $mc->get('setpoint'.$item);
				if(${'setpoint'.$item}!=0&&${'RT'.$item}<$uit) $mc->set('setpoint'.$item,0);
			}
		}
		$rpimem=number_format(get_server_memory_usage(),2);
		$rpicpu=number_format(get_server_cpu_usage(),2);
		if(date('G') == 3) {
			if($rpimem>70) {
				telegram('PiDomoticz '.$rpimem.'% memory usage, clearing');
				shell_exec('/var/www/secure/freememory.sh');
				sleep(5);
				$rpimem=number_format(get_server_memory_usage(),2);
			}
			if($rpimem>70||$rpicpu>2||$preverrors>10){
				telegram('Rebooting Domoticz: '.$preverrors.' errors, '.$rpimem.' memory, '.$rpicpu.' cpu');
				if($rpimem>90) shell_exec('/var/www/secure/reboot');
			}
		} else {
			if($rpimem>70) {
				telegram('PiDomoticz '.$rpimem.'% memory usage, clearing');
				shell_exec('/var/www/secure/freememory.sh');
				sleep(5);
				$rpimem=number_format(get_server_memory_usage(),2);
			}
			if($rpimem>90||$rpicpu>2||$preverrors>50){
				telegram('Rebooting Domoticz: '.$preverrors.' errors, '.$rpimem.' memory, '.$rpicpu.' cpu');
				$rpimem=number_format(get_server_memory_usage(),2);
				if($rpimem>90) shell_exec('/var/www/secure/reboot');
			}
		}
		$rains=file_get_contents('http://gps.buienradar.nl/getrr.php?lat=50.892880&lon=3.112568');
		$rains=str_split($rains, 11);$totalrain=0;$aantal=0;
		foreach($rains as $rain) {$aantal=$aantal+1;$totalrain=$totalrain+substr($rain,0,3);$averagerain=round($totalrain/$aantal,0);if($aantal==12) break;}
		if($averagerain>=0) $mc->set('averagerain',$averagerain);
		$openweathermap=file_get_contents('http://api.openweathermap.org/data/2.5/weather?id=2787891&APPID=ac3485b0bf1a02a81d2525db6515021d&units=metric');
		$openweathermap=json_decode($openweathermap,true);
		if(isset($openweathermap['weather']['0']['icon'])) {
			$mc->set('weatherimg',$openweathermap['weather']['0']['icon']);
			$newtemp = round(($openweathermap['main']['temp']+$Tbuiten)/2,1);
			if($newtemp!=$Tbuiten) Udevice(22,0,$newtemp,'Buiten = '.$openweathermap['main']['temp']);
		}
		//KODI
		if($Skodi=='On'&&$STkodi<$driemin) {
			$status = pingDomain('192.168.0.7', 1597);
			if(is_int($status)) {
				sleep(5);
				$status2 = pingDomain('192.168.0.7', 1597);
				if(is_int($status2)) Schakel($SIkodi, 'Off','kodi');
			}
		}
		//diskstation
		if($Sdiskstation=='On'&&$STdiskstation<$driemin) {
			$status = pingDomain('192.168.0.10', 1600);
			if(is_int($status)) {
				sleep(5);
				$status2 = pingDomain('192.168.0.10', 1600);
				if(is_int($status2)) Schakel($SIdiskstation, 'Off','diskstation');
			}
		} else if($Sdiskstation=='Off' &&($Sbureeltobi=='On'||$Skodi=='On')) shell_exec('wakeonlan 00:11:32:2c:b7:21');
		if($mc->get('RefreshZwave20')<$tienmin-mt_rand(0,600)) RefreshZwave(20,'cron');
		if($mc->get('RefreshZwave21')<$tienmin-mt_rand(0,600)) RefreshZwave(21,'cron');
		if($mc->get('RefreshZwave22')<$tienmin-mt_rand(0,600)) RefreshZwave(22,'cron');
		if($mc->get('RefreshZwave23')<$tienmin-mt_rand(0,600)) RefreshZwave(23,'cron');
		if($mc->get('RefreshZwave24')<$tienmin-mt_rand(0,600)) RefreshZwave(24,'cron');
		if($mc->get('RefreshZwave25')<$tienmin-mt_rand(0,600)) RefreshZwave(25,'cron');
		if($mc->get('RefreshZwave35')<$tienmin-mt_rand(0,600)) RefreshZwave(35,'cron');
		if($mc->get('RefreshZwave79')<$tienmin-mt_rand(0,600)) RefreshZwave(79,'cron');
		
		//Motion detection on PiCam
		//if(($Sweg=='On'||$Sslapen=='On')&&($STweg>$tweemin||$STslapen>$tweemin||$STmeldingen<$eenuur)) curl('http://picam1:8080/0/detection/start');
		//else if ($Sweg=='Off'&&$Sslapen=='Off'&&($STweg>$tweemin||$STslapen>$tweemin||$STmeldingen<$eenuur)) curl('http://picam1:8080/0/detection/pause');
		if($mc->get('gcal')<$vijfmin) {
			if(php_sapi_name()=='cli')include('gcal/gcal.php');
			$mc->set('gcal', $time);
		}
		unset($rain,$rains,$avg,$Set,$openweathermap,$Type,$SwitchType,$SubType,$name,$client,$results,$optParams,$service,$event,$calendarId);
	} //END ALL
$execution= microtime(true)-$starttime;$phptime=$execution-$domotime;
unset($domoticz,$dom,$applepass,$appleid,$appledevice,$domoticzurl,$smsuser,$smsapi,$smspassword,$smstofrom,$user,$users,$db,$http_response_header,$_SERVER,$_FILES,$_COOKIE,$_POST);
$totalerrors=$preverrors+$errors;if($totalerrors!=$preverrors) $mc->set('errors',$totalerrors);
//End Acties
} else {
	if($all) {
		$domoticzconnection = $mc->get('domoticzconnection');
		$domoticzconnection = $domoticzconnection + 1;
		$mc->set('domoticzconnection',$domoticzconnection);
		if($domoticzconnection==2) telegram('Geen verbinding met Domoticz');
		if($domoticzconnection==4) {telegram('Domoticzonnection = '.$domoticzconnection.', restarting domoticz');telegram(shell_exec('/var/www/secure/restart_domoticz > /dev/null 2>&1 &'));}
		else if($domoticzconnection>5) {telegram('Domoticzonnection = '.$domoticzconnection.', rebooting domoticz');telegram(shell_exec('/var/www/secure/reboot > /dev/null 2>&1 &'));}
	}
}
if($authenticated) 	echo '<hr>Number of vars: '.count(get_defined_vars()).'<br/><pre>';print_r(get_defined_vars());echo '</pre>';
if($actions>=1) {
	if(isset($argv[1])&&$arg[1]=="All") $msg='D'.number_format($domotime,2).'|P'.number_format($phptime,2).'|T'.number_format($execution,2).'|M'.$rpimem.'|C'.$rpicpu.'|E'.$errors.'|TE'.$totalerrors.'|'.$argv[1].' -> '.$actions.' actions';
	else if(isset($argv[1])) $msg='D'.number_format($domotime,2).'|P'.number_format($phptime,2).'|T'.number_format($execution,2).'|E'.$errors.'|TE'.$totalerrors.'|'.$argv[1].' -> '.$actions.' actions';
	else $msg='D'.number_format($domotime,2).'|P'.number_format($phptime,2).'|T'.number_format($execution,2).'|E'.$errors.'|TE'.$totalerrors.' -> '.$actions.' actions';
	logwrite($msg);
}