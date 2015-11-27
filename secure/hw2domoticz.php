<?php 
$authenticated = false;
include "functions.php";
if($authenticated) {error_reporting(E_ALL);ini_set("display_errors", "on");}
$time = time();$timeout = $time - 170;

if(xcache_get('Read_Homewizard') < $time - 58 ) {
	$homewizard = file_get_contents($hwurl.'get-sensors');
	$homewizard = json_decode($homewizard,true);
	if(!$homewizard) {if(xcache_get('homewizardconnection')!='down'){xcache_set('homewizardconnection','down');telegram('Geen verbinding met Homewizard');}
	} else {
		if(xcache_get('homewizardconnection')!='up'){xcache_set('homewizardconnection','up');telegram('Verbinding met Homewizard hersteld');}
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
		xcache_set('Read_Homewizard',$time);
	}
}
if($authenticated) {echo '<pre>';print_r(get_defined_vars());echo '</pre>';}
