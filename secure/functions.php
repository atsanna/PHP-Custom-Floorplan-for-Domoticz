<?php
//error_reporting(E_ALL);ini_set("display_errors", "on");
$authenticated = false;
$domoticzurl='http://ip:port/json.htm?';
$hwurl = 'http://ip:port/password/';
$applepass = '***';
$appledevice = '***';
$appleid = '***';
$telegrambot = '***';
$telegramchatid = 12345678;
$sms = false;
$smsuser = '***';
$smspassword = '***';
$smsapi = 12345678;
$smstofrom = 32123456789;

$authenticated = true;

setlocale(LC_ALL,'nl_NL.UTF-8');setlocale(LC_ALL, 'nld_nld');date_default_timezone_set('Europe/Brussels');$time=time();

setlocale(LC_ALL,'nl_NL.UTF-8');setlocale(LC_ALL, 'nld_nld');date_default_timezone_set('Europe/Brussels');$time=time();

function ios($msg) {
	global $appleid, $applepass, $appledevice;
	include ("findmyiphone.php");
	$fmi = new FindMyiPhone($appleid, $applepass);
	$fmi->playSound($appledevice,$msg);
	sleep(2);
}
function sms($msg, $device) {
	file_get_contents('http://api.clickatell.com/http/sendmsg?user='.$smsuser.'&password='.$smspassword.'&api_id='.$smsapi.'&to='.$smstofrom.'&text='.urlencode($msg).'&from='.$smstofrom.'');
}
function domlog($msg) {
	global $domoticzurl;
	file_get_contents($domoticzurl.'type=command&param=addlogmessage&message='.urlencode($msg));usleep(50000);
}
function telegram($msg) {
	global $telegrambot, $telegramchatid;
	$url = 'https://api.telegram.org/bot'.$telegrambot.'/sendMessage';
	$data = array('chat_id' => $telegramchatid, 'text' => $msg);
	$options = array('http' => array('method'  => 'POST','header' => "Content-Type:application/x-www-form-urlencoded\r\n",'content' => http_build_query($data),),);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	return $result;
}
function Schakel($idx, $cmd) {
	global $domoticzurl;
	$reply = json_decode(file_get_contents($domoticzurl.'type=command&param=switchlight&idx='.$idx.'&switchcmd='.$cmd), true);
	if($reply['status']=='OK') $reply = 'OK';else $reply = 'ERROR';usleep(50000);
	return $reply;
}
function Scene($idx) {
	global $domoticzurl;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=switchscene&idx='.$idx.'&switchcmd=On'), true);
	if($reply['status']=='OK') $reply = 'OK';else $reply = 'ERROR';usleep(50000);
	return $reply;
}	
function Dim($idx, $level) {
	global $domoticzurl;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=switchlight&idx='.$idx.'=&switchcmd=Set%20Level&level='.$level), true);
	if($reply['status']=='OK') $reply = 'OK';else $reply = 'ERROR';usleep(50000);
	return $reply;
}	
function Udevice($idx, $nvalue, $svalue) {
	global $domoticzurl;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=udevice&idx='.$idx.'&nvalue='.$nvalue.'&svalue='.$svalue), true);
	if($reply['status']=='OK') $reply = 'OK';else $reply = 'ERROR';usleep(50000);
	return $reply;
}
function Textdevice($idx, $text) {
	global $domoticzurl;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$text), true);
	if($reply['status']=='OK') $reply = 'OK';else $reply = 'ERROR';usleep(50000);
	return $reply;
}
function percentdevice($idx, $value) {
	global $domoticzurl;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$value), true);
	if($reply['status']=='OK') $reply = 'OK';else $reply = 'ERROR';usleep(50000);
	return $reply;
}
function ResetSmoke($idx, $cmd) {
	global $domoticzurl;
	$reply =  json_decode(file_get_contents($domoticzurl.'type=command&param=resetsecuritystatus&idx='.$idx.'&switchcmd='.$cmd), true);
	if($reply['status']=='OK') $reply = 'OK';else $reply = 'ERROR';usleep(50000);
	return $reply;
}
function Thermometer($name, $size, $boven, $links) {
	global ${'Temp'.$name},${'Tempidx'.$name},${'Tempbat'.$name},${'TempTime'.$name}, $time;
	$hoogte = ${'Temp'.$name}*$size*0.0275;
	if($hoogte > $size*0.85) $hoogte = $size*0.85;else if ($hoogte < 0) $hoogte = 0;
	$top = $size*0.8-$hoogte;if($top<0) $top=0;
	$top = $top+$size*0.1;
	if (${'Tempbat'.$name}==0 && ${'TempTime'.$name} > $time - 300) $battery = 100;
	else $battery = ${'Tempbat'.$name};
	switch (${'Temp'.$name}) {
		case ${'Temp'.$name} >= 22:$color='f00';break;
		case ${'Temp'.$name} >= 20:$color='d12';break;
		case ${'Temp'.$name} >= 18:$color='b24';break;
		case ${'Temp'.$name} >= 15:$color='946';break;
		case ${'Temp'.$name} >= 10:$color='76a';break;
		default:$color='4ab0f6';
	}
	echo '<form action="temp.php" method="POST"><div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;cursor:pointer;z-index:10;" onclick="this.form.submit()">
		<input type="hidden" name="sensor" value="'.${'Tempidx'.$name}.'">
		<input type="hidden" name="naam" value="'.$name.'">
		<div class="tmpbg" style="top:'.$top.'px;left:'.$size*0.07.'px;width:'.$size*0.27.'px;height:'.$hoogte.'px;background:linear-gradient(to bottom, #'.$color.', #4ab0f6);"></div>
		<input type="image" src="images/temp.png" height="'.$size.'px" width="auto"/>
	</form>';
	echo $battery<20 ? '<div class="red" style="top:'.$size*0.71.'px;left:'.$size*0.035.'px;width:'.$size*0.3.'px;font-size:'.$size*1.2.'%;">'
	                 :'<div class="grey" style="top:'.$size*0.71.'px;left:'.$size*0.035.'px;width:'.$size*0.3.'px;font-size:'.$size*1.2.'%;">';
	echo number_format(${'Temp'.$name},1).'</div></form></div>';
}
function Schakelaar($name,$kind,$size,$boven,$links) {
	global ${'Switch'.$name},${'Switchidx'.$name},${'SwitchTime'.$name};
	echo '<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;" title="'.strftime("%a %e %b %k:%M:%S", ${'SwitchTime'.$name}).'"><form method="POST"><input type="hidden" name="Schakel" value="'.${'Switchidx'.$name}.'">';
	echo ${'Switch'.$name}=='Off' ? '<input type="hidden" name="Actie" value="On"><input type="hidden" name="Naam" value="'.$name.'"><input type="image" src="images/'.$kind.'_Off.png" height="'.$size.'px" width="auto">' 
	                              :'<input type="hidden" name="Actie" value="Off"><input type="hidden" name="Naam" value="'.$name.'"><input type="image" src="images/'.$kind.'_On.png" height="'.$size.'px" width="auto">';
	echo '</form></div>';
}
function Dimmer($name,$size,$boven,$links) {
	global ${'Dimmer'.$name},${'Dimmeridx'.$name},${'Dimmerlevel'.$name},${'DimmerTime'.$name};
	echo '<div id="'.$name.'" class="dimmer" style="display:none;">
		<form method="POST">
        <output id="dimlevel'.$name.'" class="'.${'Dimmer'.$name}.'">'.$name.'</output><input type="hidden" name="Naam" value="'.$name.'">
		<input name="dimlevel'.$name.'" id="dimlevel'.$name.'" type ="range" min ="0" max="100" step ="1" value ="'.${'Dimmerlevel'.$name}.'" onchange="this.form.submit()"/><br/><br/>
        </form>
		<div style="position:absolute;top:5px;right:5px;z-index:1000;"><a href=""><img src="images/close.png" width="48px" height="48px"/></a></div>
	</div>
	<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;" title="'.strftime("%a %e %b %k:%M:%S", ${'DimmerTime'.$name}).'">
	<a href="#" onclick="toggle_visibility(\''.$name.'\');" style="text-decoration:none">';
	echo ${'Dimmer'.$name}=='Off' ? '<input type="image" src="images/Light_Off.png" height="'.$size.'px" width="auto">'
								  :'<input type="image" src="images/Light_On.png"  height="'.$size.'px" width="auto">';
	echo '</a></div>';
}
function Smokedetector($name,$size,$boven,$links) {
	global ${'Switch'.$name},${'Switchidx'.$name},${'Switchbat'.$name},${'SwitchTime'.$name};
	echo '<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;z-index:-10;" title="'.strftime("%a %e %b %k:%M:%S", ${'SwitchTime'.$name}).'">
	<form method="POST"><input type="hidden" name="Schakel" value="'.${'Switchidx'.$name}.'"><input type="hidden" name="Naam" value="'.$name.'">';
	echo ${'Switch'.$name}=='Off' ? '<img src="images/smokeoff.png" width="36" height="36">'
	                                   :'<input type="hidden" name="Actie" value="Off"><input type="image" src="images/smokeon.png" height="'.$size.'px" width="auto">';
	echo '</form></div>';
	echo ${'Switchbat'.$name} < 40 ? '<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;width:36px;height:36px;background:rgba(255, 0, 0, 0.7);z-index:-11;"></div>':'';
}
function Radiator($name,$draai,$boven,$links) {
	global ${'Rad'.$name},${'Radbat'.$name},${'RadTime'.$name};
	echo ${'Radbat'.$name}<40 ? '<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;width:40px;background:rgba(255, 50, 50, 0.6);border-radius:10px;padding:0px;transform:rotate('.$draai.'deg);-webkit-transform:rotate('.$draai.'deg);"  title="'.strftime("%a %e %b %k:%M:%S", ${'RadTime'.$name}).'">'.number_format(${'Rad'.$name},0).'</div>'
							  :'<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;width:40px;background:rgba(150, 150, 150, 0.6);border-radius:10px;padding:0px;transform:rotate('.$draai.'deg);-webkit-transform:rotate('.$draai.'deg);" title="'.strftime("%a %e %b %k:%M:%S", ${'RadTime'.$name}).'">'.number_format(${'Rad'.$name},0).'</div>';
}
function Setpoint($name,$size,$boven,$links) {
	global ${'Rad'.$name},${'Radidx'.$name},${'RadTime'.$name},${'Temp'.$name};
	echo '<div id="Verwarming'.$name.'" style="position:absolute;top:2px;left:80px;width:300px;padding:50px;display:none;background:rgba(233, 233, 233, 0.9);border-radius:10px;z-index:100;">
			<form method="POST" action="">
				<input type="hidden" name="Setpoint" value="'.${'Radidx'.$name}.'" >
				<input type="hidden" name="Naam" value="'.$name.'">
				<select name="Actie'.$name.'" class="abutton" onChange="this.form.submit()" style="margin-top:4px">
					<option '.number_format(${'Rad'.$name},0).') selected>'.number_format(${'Rad'.$name},0).'</option>
					<option>12</option>
					<option>18</option>
					<option>19</option>
					<option>20</option>
					<option>21</option>
					<option>22</option>
					<option>23</option>
					<option>24</option>
				</select>
			</form>
			<div style="position:absolute;top:5px;right:5px;z-index:100;"><a href=""><img src="images/close.png" width="48px" height="48px"/></a></div>
		</div>';
		echo '<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;"><a href="#" onclick="toggle_visibility(\'Verwarming'.$name.'\');" style="text-decoration:none">';
		echo ${'Rad'.$name}>=${'Temp'.$name} ? '<input type="image" src="images/flame.png" height="'.$size.'px" width="auto">':'<input type="image" src="images/flamegrey.png" height="'.$size.'px" width="auto">';
		echo '</a><div class="setpoint" style="font-size:'.$size*2.3 .'%;width:'.$size*0.7 .'px;">'.number_format(${'Rad'.$name},0).'</div>';
		echo '</div>';
}
function Timestamp($name,$draai,$boven,$links) {
	global ${'Switch'.$name},${'SwitchTime'.$name};
	echo '<div class="stamp" style="top:'.$boven.'px;left:'.$links.'px;transform:rotate('.$draai.'deg);-webkit-transform:rotate('.$draai.'deg);">'.strftime("%k:%M",${'SwitchTime'.$name}).'</div>';
}
function Secured($boven, $links, $breed, $hoog) {
	echo '<div class="secured" style="top:'.$boven.'px;left:'.$links.'px;width:'.$breed.'px;height:'.$hoog.'px;"></div>';
}
function Motion($boven, $links, $breed, $hoog) {
	global $SwitchThuis, $SwitchSlapen;
	if($SwitchThuis == 'Off' || $SwitchSlapen == 'On') echo '<div class="motionr" style="top:'.$boven.'px;left:'.$links.'px;width:'.$breed.'px;height:'.$hoog.'px;"></div>';
												  else echo '<div class="motion" style="top:'.$boven.'px;left:'.$links.'px;width:'.$breed.'px;height:'.$hoog.'px;"></div>';
}
function RefreshZwave($node) {
	global $domoticzurl;
	file_get_contents($domoticzurl.'type=openzwavenodes&idx=5');
	$zwaveurl = 'http://127.0.0.1:1602/ozwcp/refreshpost.html';
	$zwavedata = array('fun'=>'racp','node'=>$node);
	$zwaveoptions = array('http'=>array('header'=>'Content-Type:application/x-www-form-urlencoded\r\n','method'=>'POST','content'=>http_build_query($zwavedata),),);
	$zwavecontext  = stream_context_create($zwaveoptions);
	for ($k = 1 ;$k <= 5;$k++){sleep(2);$result = file_get_contents($zwaveurl,false,$zwavecontext);if($result=='OK') break;}
}