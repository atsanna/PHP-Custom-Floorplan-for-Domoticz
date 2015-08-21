<?php
//error_reporting(E_ALL);ini_set("display_errors", "on");
$domoticzurl='http://ip:port/json.htm?';
$authenticated = false;
$authenticated = true;
setlocale(LC_ALL,'nl_NL.UTF-8');setlocale(LC_ALL, 'nld_nld');date_default_timezone_set('Europe/Brussels');$time=time();
function uservariable($name, $type, $value) {
	global $domoticzurl; //$type = 0 = Integer, 1 = Float(met komma),2 = String,3 = Date in format DD/MM/YYYY,4 = Time in 24 hr format HH:MM
	$reply = json_decode(file_get_contents($domoticzurl.'type=command&param=updateuservariable&vname='.$name.'&vtype='.$type.'&vvalue='.$value), true);
	print_r($reply);
	if($reply['status']=='OK') $response = 'OK'; 
	else {
		$replys = json_decode(file_get_contents($domoticzurl.'type=command&param=saveuservariable&vname='.$name.'&vtype='.$type.'&vvalue='.$value), true);
		$response = $replys['status'];
	}
	return $response;
}
function ios($msg) {
	global $appleid, $applepass, $appledevice;
	include ("findmyiphone.php");
	$fmi = new FindMyiPhone($appleid, $applepass);
	$fmi->playSound($appledevice,$msg);
}
function sms($msg, $device) {
	file_get_contents('http://api.clickatell.com/http/sendmsg?user='.$smsuser.'&password='.$smspassword.'&api_id='.$smsapi.'&to='.$smstofrom.'&text='.urlencode($msg).'&from='.$smstofrom.'');
}
function domlog($msg) {
	global $domoticzurl;
	file_get_contents($domoticzurl.'type=command&param=addlogmessage&message='.urlencode($msg));
}
function telegram($msg) {
	global $telegrambot, $telegramchatid;
	$url = 'https://api.telegram.org/bot'.$telegrambot.'/sendMessage';
	$data = array('chat_id' => $telegramchatid, 'text' => $msg);
	$options = array('http' => array('method'  => 'POST','header' => "Content-Type: application/x-www-form-urlencoded\r\n",'content' => http_build_query($data),),);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	return $result;
}
function Schakel($idx, $cmd) {
	global $domoticzurl, $tekst;
	$reply = json_decode(file_get_contents($domoticzurl.'type=command&param=switchlight&idx='.$idx.'&switchcmd='.$cmd), true);
	if($reply['status']=='OK') $reply = 'OK'; else $reply = 'ERROR';
	return $reply;
}
function Scene($idx) {
	global $domoticzurl, $tekst;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=switchscene&idx='.$idx.'&switchcmd=On'), true);
	if($reply['status']=='OK') $reply = 'OK'; else $reply = 'ERROR';
	return $reply;
}	
function Dim($idx, $level) {
	global $domoticzurl, $tekst;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=switchlight&idx='.$idx.'=&switchcmd=Set%20Level&level='.$level), true);
	if($reply['status']=='OK') $reply = 'OK'; else $reply = 'ERROR';
	return $reply;
}	
function Udevice($idx, $nvalue, $svalue) {
	global $domoticzurl, $tekst;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=udevice&idx='.$idx.'&nvalue='.$nvalue.'&svalue='.$svalue), true);
	if($reply['status']=='OK') $reply = 'OK'; else $reply = 'ERROR';
	return $reply;
}
function Textdevice($idx, $text) {
	global $domoticzurl, $tekst;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$text), true);
	if($reply['status']=='OK') $reply = 'OK'; else $reply = 'ERROR';
	return $reply;
}
function ResetSmoke($idx, $cmd) {
	global $domoticzurl, $tekst;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=resetsecuritystatus&idx='.$idx.'&switchcmd='.$cmd), true);
	if($reply['status']=='OK') $reply = 'OK'; else $reply = 'ERROR';
	return $reply;
}
function Thermometer($name, $size, $boven, $links) {
	global ${'Temp'.$name},${'Tempidx'.$name},${'Tempbat'.$name},${'TempTime'.$name}, $time;
	$hoogte = ${'Temp'.$name}*$size*0.0275;
	if($hoogte > $size*0.85) $hoogte = $size*0.85; else if ($hoogte < 0) $hoogte = 0;
	$top = $size*0.8-$hoogte;if($top<0) $top=0;
	$top = $top+$size*0.1;
	if (${'Tempbat'.$name}==0 && ${'TempTime'.$name} > $time - 300) $battery = 100;
	else $battery = ${'Tempbat'.$name};
	switch (${'Temp'.$name}) {
		case ${'Temp'.$name} >= 22: $color='f00';break;
		case ${'Temp'.$name} >= 20: $color='d12';break;
		case ${'Temp'.$name} >= 18: $color='b24';break;
		case ${'Temp'.$name} >= 15: $color='946';break;
		case ${'Temp'.$name} >= 10: $color='76a';break;
		default: $color='4ab0f6';
	}
	echo '<form action="temp.php" method="POST"><div style="position: absolute;top: '.$boven.'px;left: '.$links.'px;cursor:pointer;z-index:10;" onclick="this.form.submit()">
		<input type="hidden" name="sensor" value="'.${'Tempidx'.$name}.'">
		<input type="hidden" name="naam" value="'.$name.'">
		<div style="position: absolute;top: '.$top.'px;left: '.$size*0.07.'px; width:'.$size*0.27.'px; height:'.$hoogte.'px;background:linear-gradient(to bottom, #'.$color.', #4ab0f6);background-repeat:no-repeat;border-radius:6px;z-index:-1;"></div>
		<input type="image" src="images/temp.png" height="'.$size.'px" width="auto"/>
	</form>';
	echo $battery<20 ? '<div style="position: absolute;top:'.$size*0.72.'px;left:'.$size*0.05.'px; width:'.$size*0.3.'px;font-size:'.$size*1.15.'%;background:rgba(255, 50, 50, 0.7);border-radius:10px; padding:0px;">'
	                 : '<div style="position: absolute;top:'.$size*0.72.'px;left:'.$size*0.05.'px; width:'.$size*0.3.'px;font-size:'.$size*1.15.'%;background:rgba(255, 255, 255, 0.7);border-radius:10px; padding:0px;">';
	echo number_format(${'Temp'.$name},1).'</div></form></div>';
}
function Schakelaar($name,$kind,$size,$boven,$links) {
	global ${'Switch'.$name},${'Switchidx'.$name},${'SwitchTime'.$name};
	echo '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;" title="'.strftime("%a %e %b %k:%M:%S", ${'SwitchTime'.$name}).'"><form method="POST"><input type="hidden" name="Schakel" value="'.${'Switchidx'.$name}.'">';
	echo ${'Switch'.$name}=='Off' ? '<input type="hidden" name="Actie" value="On"><input type="hidden" name="Naam" value="'.$name.'"><input type="image" src="images/'.$kind.'_Off.png" alt="Submit" height="'.$size.'px" width="auto">' 
	                              : '<input type="hidden" name="Actie" value="Off"><input type="hidden" name="Naam" value="'.$name.'"><input type="image" src="images/'.$kind.'_On.png" alt="Submit" height="'.$size.'px" width="auto">';
	echo '</form></div>';
}
function Dimmer($name,$size,$boven,$links) {
	global ${'Dimmer'.$name},${'Dimmeridx'.$name},${'Dimmerlevel'.$name},${'DimmerTime'.$name};
	echo '<div id="'.$name.'" class="dimmer" style="display:none;z-index:1000;">
		<form method="POST">
        <output id="dimlevel'.$name.'" class="'.${'Dimmer'.$name}.'">'.$name.'</output><input type="hidden" name="Naam" value="'.$name.'">
		<input name="dimlevel'.$name.'" id="dimlevel'.$name.'" type ="range" min ="0" max="100" step ="1" value ="'.${'Dimmerlevel'.$name}.'" onchange="this.form.submit()"/><br/><br/>
        </form>
		<div style="position: absolute;top: 5px;right: 5px;z-index:1000;" title="Close"><a href=""><img src="images/close.png" width="48px" height="48px" alt="Close" title="Close"/></a></div>
	</div>
	<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;" title="'.strftime("%a %e %b %k:%M:%S", ${'DimmerTime'.$name}).'">
	<a href="#" onclick="toggle_visibility(\''.$name.'\');" style="text-decoration:none">';
	echo ${'Dimmer'.$name}=='Off' ? '<input type="image" src="images/Light_Off.png" alt="Submit" height="'.$size.'px" width="auto">'
								  : '<input type="image" src="images/Light_On.png"  alt="Submit" height="'.$size.'px" width="auto">';
	echo '</a></div>';
}
function Smokedetector($name,$size,$boven,$links) {
	global ${'Switch'.$name},${'Switchidx'.$name},${'Switchbat'.$name},${'SwitchTime'.$name};
	echo '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;z-index:-10;" title="'.strftime("%a %e %b %k:%M:%S", ${'SwitchTime'.$name}).'">
	<form method="POST"><input type="hidden" name="Schakel" value="'.${'Switchidx'.$name}.'"><input type="hidden" name="Naam" value="'.$name.'">';
	echo ${'Switch'.$name}=='Off' ? '<img src="images/smokeoff.png" width="36" height="36">'
	                                   :'<input type="hidden" name="Actie" value="Off"><input type="image" src="images/smokeon.png" alt="Submit" height="'.$size.'px" width="auto">';
	echo '</form></div>';
	echo ${'Switchbat'.$name} < 40 ? '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;width:36px;height:36px;background:rgba(255, 0, 0, 0.7);z-index:-11;"></div>':'';
}
function Radiator($name,$draai,$boven,$links) {
	global ${'Rad'.$name},${'Radbat'.$name},${'RadTime'.$name};
	echo ${'Radbat'.$name}<40 ? '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;width:40px; background:rgba(255, 50, 50, 0.6);border-radius:10px; padding:0px;transform: rotate('.$draai.'deg); -webkit-transform: rotate('.$draai.'deg);"  title="'.strftime("%a %e %b %k:%M:%S", ${'RadTime'.$name}).'">'.number_format(${'Rad'.$name},0).'</div>'
							  : '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;width:40px; background:rgba(150, 150, 150, 0.6);border-radius:10px; padding:0px;transform: rotate('.$draai.'deg);-webkit-transform: rotate('.$draai.'deg);" title="'.strftime("%a %e %b %k:%M:%S", ${'RadTime'.$name}).'">'.number_format(${'Rad'.$name},0).'</div>';
}
function Setpoint($name,$size,$boven,$links) {
	global ${'Rad'.$name},${'Radidx'.$name},${'RadTime'.$name},${'Temp'.$name};
	echo '<div id="Verwarming'.$name.'" style="position: absolute;top: 2px;left: 80px;width:300px;padding:50px;display:none;background:rgba(233, 233, 233, 0.9);border-radius:10px; z-index:100;">
			<form method="POST">
				<input type="hidden" name="Setpoint" value="'.${'Radidx'.$name}.'" >
				<input type="hidden" name="Naam" value="'.$name.'">
				<input id="Actie'.$name.'" type="text" name="Actie'.$name.'" value="'.number_format(${'Rad'.$name},0).'" style="width: 52px; padding: 0px; border: 0px;font-size: 48px; background: transparent;">
				<a id="popup_temp_up"><img src="images/arrowup.png" onclick="SetpointUp'.$name.'()" ></a>
				<a id="popup_temp_down"><img src="images/arrowdown.png" onclick="SetpointDown'.$name.'()"></a>
				<input type="image" src="images/flame.png" alt="Submit">
			</form>
			<script type="text/javascript">
				function SetpointUp'.$name.'() {
					var curValue=parseFloat($(\'#Actie'.$name.'\').val());
					curValue+=1;
					curValue=Math.round(curValue / 0.5) * 0.5;
					var curValueStr=curValue.toFixed(1);
					$(\'#Actie'.$name.'\').val(curValueStr);}
				function SetpointDown'.$name.'() {
					var curValue=parseFloat($(\'#Actie'.$name.'\').val());
					curValue-=1;
					curValue=Math.round(curValue / 0.5) * 0.5;
					if (curValue<0) {curValue=0;}var curValueStr=curValue.toFixed(1);
					$(\'#Actie'.$name.'\').val(curValueStr);}
			</script>
			<div style="position: absolute;top: 5px;right: 5px;z-index:100;" title="Close"><a href="#"><img src="images/close.png" width="48px" height="48px" alt="Close" title="Close"/></a></div>
		</div>';
		echo '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;"><a href="#" onclick="toggle_visibility(\'Verwarming'.$name.'\');" style="text-decoration:none">';
		echo ${'Rad'.$name}>=${'Temp'.$name} ? '<input type="image" src="images/flame.png" alt="Submit" height="'.$size.'px" width="auto">':'<input type="image" src="images/flamegrey.png" alt="Submit" height="'.$size.'px" width="auto">';
		echo '</a><div style="position: absolute;top: 40px;left: 7px; width:30px; background:#dadada;border-radius:10px; padding:0px;font-size:'.$size*2 .'%;">'.number_format(${'Rad'.$name},0).'</div>';
		echo '</div>';
}
function Timestamp($name,$draai,$boven,$links) {
	global ${'Switch'.$name},${'SwitchTime'.$name};
	echo '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px; width:30px; background:#dadada;border-radius:4px; padding:0px 3px 0px 3px;transform: rotate('.$draai.'deg);-webkit-transform: rotate('.$draai.'deg);">'.strftime("%k:%M",${'SwitchTime'.$name}).'</div>';
}
function Secured($boven, $links, $breed, $hoog) {
	echo '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;width:'.$breed.'px;height:'.$hoog.'px;background: repeating-linear-gradient(135deg,rgba(180, 0, 0, 0),rgba(180, 0, 0, 0) 7px,rgba(180, 0, 0, 0) 8px,rgba(180, 0, 0, 0.2) 15px);z-index:-100;"></div>';
}
function Motion($boven, $links, $breed, $hoog) {
	global $SwitchThuis, $SwitchSlapen;
	if($SwitchThuis == 'Off' || $SwitchSlapen == 'On') echo '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;width:'.$breed.'px;height:'.$hoog.'px;background:rgba(255, 0, 0, 0.5);z-index:-10;"></div>';
												  else echo '<div style="position: absolute;top:'.$boven.'px;left:'.$links.'px;width:'.$breed.'px;height:'.$hoog.'px;background:rgba(255, 0, 0, 0.1);z-index:-10;"></div>';
}
function RefreshZwave($node) {
	file_get_contents('http://127.0.0.1:1602/#/Hardware');
	$zwaveurl = 'http://127.0.0.1:1602/ozwcp/refreshpost.html';
	$zwavedata = array('fun' => 'racp', 'node' => $node);
	$zwaveoptions = array('http' => array('header' => 'Content-Type: application/x-www-form-urlencoded\r\n','method'  => 'POST','content' => http_build_query($zwavedata),),);
	$zwavecontext  = stream_context_create($zwaveoptions);
	for ($k = 1 ; $k <= 3; $k++){sleep(2);$result = file_get_contents($zwaveurl, false, $zwavecontext);echo $result.PHP_EOL;if($result=='OK') $k = 4;}
}