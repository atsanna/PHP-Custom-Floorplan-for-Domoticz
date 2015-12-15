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
$mc = new Memcached();$mc->addServer("localhost", 11211);

function ios($msg) {global $appleid,$applepass,$appledevice;include ("findmyiphone.php");$fmi=new FindMyiPhone($appleid,$applepass);$fmi->playSound($appledevice,$msg);sleep(2);}
function sms($msg,$device) {file_get_contents('http://api.clickatell.com/http/sendmsg?user='.$smsuser.'&password='.$smspassword.'&api_id='.$smsapi.'&to='.$smstofrom.'&text='.urlencode($msg).'&from='.$smstofrom.'');}
function domlog($msg) {global $domoticzurl;file_get_contents($domoticzurl.'type=command&param=addlogmessage&message='.urlencode($msg));usleep(50000);}
function telegram($msg) {global $telegrambot,$telegramchatid;$url='https://api.telegram.org/bot'.$telegrambot.'/sendMessage';$data=array('chat_id'=>$telegramchatid,'text'=>$msg);$options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);$context=stream_context_create($options);$result=file_get_contents($url,false,$context);return $result;}
function Schakel($idx,$cmd) {global $domoticzurl,$user;$reply=json_decode(file_get_contents($domoticzurl.'type=command&param=switchlight&idx='.$idx.'&switchcmd='.$cmd),true);if($reply['status']=='OK') $reply='OK';else $reply='ERROR';if($user=="Tobi") telegram('Tobi Schakel '.$idx.' '.$cmd);usleep(50000);return $reply;}
function Scene($idx) {global $domoticzurl,$user;$reply=json_decode(file_get_contents($domoticzurl.'type=command&param=switchscene&idx='.$idx.'&switchcmd=On'),true);if($reply['status']=='OK') $reply='OK';else $reply='ERROR';if($user=="Tobi") telegram('Tobi Scene '.$idx);usleep(50000);return $reply;}	
function Dim($idx,$level) {global $domoticzurl,$user;if($level>0&&$level<100) $level=$level+1;$reply=json_decode(file_get_contents($domoticzurl.'type=command&param=switchlight&idx='.$idx.'=&switchcmd=Set%20Level&level='.$level),true);if($reply['status']=='OK') $reply='OK';else $reply='ERROR';if($user=="Tobi") telegram('Tobi dimmer '.$idx.' '.$cmd);usleep(50000);return $reply;}	
function Udevice($idx, $nvalue, $svalue) {global $domoticzurl,$user;$reply=json_decode(file_get_contents($domoticzurl.'type=command&param=udevice&idx='.$idx.'&nvalue='.$nvalue.'&svalue='.$svalue),true);if($reply['status']=='OK') $reply='OK';else $reply='ERROR';if($user=="Tobi") telegram('Tobi Udevice '.$idx.' '.$nvalue.' '.$snvalue);usleep(50000);return $reply;}
function Textdevice($idx,$text) {global $domoticzurl;$reply=json_decode(file_get_contents($domoticzurl.'type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$text),true);if($reply['status']=='OK') $reply='OK';else $reply='ERROR';usleep(50000);return $reply;}
function percentdevice($idx,$value) {global $domoticzurl;$reply=json_decode(file_get_contents($domoticzurl.'type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$value),true);if($reply['status']=='OK') $reply='OK';else $reply='ERROR';usleep(50000);return $reply;}
function voorwarmen($temp, $settemp,$seconds) {
	global $TBuiten;
	if($temp<$settemp) $voorwarmen = ceil(($settemp-$temp) + ($settemp-$TBuiten)) * $seconds; else $voorwarmen = 0;
	if($voorwarmen>7200) $voorwarmen=7200;
	return $voorwarmen;
}
function setradiator($temp,$setpoint) {
	if($setpoint-$temp>0) $setpointSet=round($setpoint + (ceil(($setpoint-$temp)*5)),0); else $setpointSet=round($setpoint + (ceil(($setpoint-$temp)*5)),0);
	if($setpointSet>28) $setpointSet=28;else if ($setpointSet<4) $setpointSet=4;
	return $setpointSet;
}
function Thermometer($name, $size, $boven, $links) {
	global ${'T'.$name},${'TI'.$name},${'TT'.$name}, $time;
	$hoogte=${'T'.$name}*$size*0.0275;
	if($hoogte>$size*0.85) $hoogte=$size*0.85;else if ($hoogte<0) $hoogte=0;
	$top=$size*0.8-$hoogte;if($top<0) $top=0;
	$top=$top+$size*0.1;
	switch (${'T'.$name}) {
		case ${'T'.$name}>=22:$tcolor='f00';$dcolor='aa7076';break;
		case ${'T'.$name}>=20:$tcolor='d12';$dcolor='8a8096';break;
		case ${'T'.$name}>=18:$tcolor='b24';$dcolor='6a90b6';break;
		case ${'T'.$name}>=15:$tcolor='946';$dcolor='4aa0d6';break;
		case ${'T'.$name}>=10:$tcolor='76a';$dcolor='2ab0f6';break;
		default:$tcolor='56a';$dcolor='2ab0f6';}
	echo '<form action="temp.php" method="POST"><div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;cursor:pointer;z-index:10;" onclick="this.form.submit()">
		<input type="hidden" name="sensor" value="'.${'TI'.$name}.'">
		<input type="hidden" name="naam" value="'.$name.'">
		<div class="tmpbg" style="top:'.$top.'px;left:'.$size*0.07.'px;width:'.$size*0.27.'px;height:'.$hoogte.'px;background:linear-gradient(to bottom, #'.$tcolor.', #'.$dcolor.');"></div>
		<input type="image" src="images/temp.png" height="'.$size.'px" width="auto"/>
	</form>';
	echo '<div class="grey" style="top:'.$size*0.71.'px;left:'.$size*0.035.'px;width:'.$size*0.3.'px;font-size:'.$size*1.25.'%;">';
	echo number_format(${'T'.$name},1).'</div></form></div>';
}
function Schakelaar($name,$kind,$size,$boven,$links) {
	global ${'S'.$name},${'SI'.$name},${'ST'.$name};
	echo '<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;" title="'.strftime("%a %e %b %k:%M:%S", ${'ST'.$name}).'"><form method="POST"><input type="hidden" name="Schakel" value="'.${'SI'.$name}.'">';
	echo ${'S'.$name}=='Off'?'<input type="hidden" name="Actie" value="On"><input type="hidden" name="Naam" value="'.$name.'"><input type="image" src="images/'.$kind.'_Off.png" height="'.$size.'px" width="auto">' 
	               :'<input type="hidden" name="Actie" value="Off"><input type="hidden" name="Naam" value="'.$name.'"><input type="image" src="images/'.$kind.'_On.png" height="'.$size.'px" width="auto">';
	echo '</form></div>';
}
function Dimmer($name,$size,$boven,$links) {
	global ${'D'.$name},${'DI'.$name},${'Dlevel'.$name},${'DT'.$name};
	echo '<div id="D'.$name.'" class="dimmer" style="display:none;">
				<form method="POST" action="floorplan.php" oninput="level.value = dimlevel.valueAsNumber">
    			<div style="position:absolute;top:-5px;left:30px;z-index:1000;"><h2>'.$name.': '.round(${'Dlevel'.$name},0).'%</h2><input type="hidden" name="Naam" value="'.$name.'"><input type="hidden" name="dimmer" value="'.${'DI'.$name}.'"></div>
				<div style="position:absolute;top:100px;left:28px;z-index:1000;"><input type="image" name="dimleveloff" value ="0" src="images/off.png" width="90px" height="90px"/></div>
				<div style="position:absolute;top:100px;left:150px;z-index:1000;"><input type="image" name="dimwake" value ="100" src="images/Wakeup.png" width="90px" height="90px"/><input type="hidden" name="dimwakelevel" value="'.${'Dlevel'.$name}.'"></div>
				<div style="position:absolute;top:100px;left:265px;z-index:1000;"><input type="image" name="dimsleep" value ="100" src="images/Sleepy.png" width="90px" height="90px"/></div>
				<div style="position:absolute;top:100px;left:387px;z-index:1000;"><input type="image" name="dimlevelon" value ="100" src="images/on.png" width="90px" height="90px"/></div>
				<div style="position:absolute;top:210px;left:10px;z-index:1000;">
					<input type="submit" name="dimlevel" value="1"/>
					<input type="submit" name="dimlevel" value="2"/>
					<input type="submit" name="dimlevel" value="3"/>
					<input type="submit" name="dimlevel" value="4"/>
					<input type="submit" name="dimlevel" value="5"/>
					<input type="submit" name="dimlevel" value="6"/>
					<input type="submit" name="dimlevel" value="7"/>
					<input type="submit" name="dimlevel" value="8"/>
					<input type="submit" name="dimlevel" value="9"/>
					<input type="submit" name="dimlevel" value="10"/>
					<input type="submit" name="dimlevel" value="12"/>
					<input type="submit" name="dimlevel" value="14"/>
					<input type="submit" name="dimlevel" value="16"/>
					<input type="submit" name="dimlevel" value="18"/>
					<input type="submit" name="dimlevel" value="20"/>
					<input type="submit" name="dimlevel" value="22"/>
					<input type="submit" name="dimlevel" value="24"/>
					<input type="submit" name="dimlevel" value="26"/>
					<input type="submit" name="dimlevel" value="28"/>
					<input type="submit" name="dimlevel" value="30"/>
					<input type="submit" name="dimlevel" value="32"/>
					<input type="submit" name="dimlevel" value="35"/>
					<input type="submit" name="dimlevel" value="40"/>
					<input type="submit" name="dimlevel" value="45"/>
					<input type="submit" name="dimlevel" value="50"/>
					<input type="submit" name="dimlevel" value="55"/>
					<input type="submit" name="dimlevel" value="60"/>
					<input type="submit" name="dimlevel" value="65"/>
					<input type="submit" name="dimlevel" value="70"/>
					<input type="submit" name="dimlevel" value="75"/>
					<input type="submit" name="dimlevel" value="80"/>
					<input type="submit" name="dimlevel" value="85"/>
					<input type="submit" name="dimlevel" value="90"/>
					<input type="submit" name="dimlevel" value="95"/>
					<input type="submit" name="dimlevel" value="100"/>
				</div>
    			</form>
				<div style="position:absolute;top:5px;right:5px;z-index:1000;"><a href=""><img src="images/close.png" width="72px" height="72px"/></a></div>
			</div>
	<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;">
	<a href="#" onclick="toggle_visibility(\'D'.$name.'\');" style="text-decoration:none">';
	echo ${'D'.$name}=='Off'?'<input type="image" src="images/Light_Off.png" height="'.$size.'px" width="auto">'
							:'<input type="image" src="images/Light_On.png" height="'.$size.'px" width="auto"><div style="position:absolute;top:6px;right:16px;">'.${'Dlevel'.$name}.'</div>';
	echo '</a></div>';
}
function Setpoint($name,$size,$boven,$links) {
	global ${'R'.$name},${'RI'.$name},${'RT'.$name},${'T'.$name};
	echo '<div id="S'.$name.'" class="dimmer" style="display:none;">
	
		<form method="POST" action="floorplan.php" oninput="level.value = Actie.valueAsNumber"><input type="hidden" name="Setpoint" value="'.${'RI'.$name}.'" >
    <h2>'.$name.' = '.${'T'.$name}.'°C</h2><input type="hidden" name="Naam" value="'.$name.'"><input type="hidden" name="setpoint" value="'.${'RI'.$name}.'">
		<div style="position:absolute;top:150px;left:10px;z-index:1000;">';
		$temps=array(5,8,10,12,14,15,16,17,18,19,20,21,22,23,24);
		foreach($temps as $temp) {
			if(${'R'.$name}==$temp) echo '<input type="submit" name="Actie" value="'.$temp.'"/ style="background-color:#5fed5f;background:linear-gradient(to bottom, #5fed5f, #017a01);">';
			else echo '<input type="submit" name="Actie" value="'.$temp.'"/>';
		}
		echo '</div>						
    </form>
		<div style="position:absolute;top:5px;right:5px;z-index:1000;"><a href=""><img src="images/close.png" width="72px" height="72px"/></a></div>
	</div>';
	
	echo '<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;"><a href="#" onclick="toggle_visibility(\'S'.$name.'\');" style="text-decoration:none">';
	echo ${'R'.$name}>${'T'.$name}?'<img src="images/flame.png" height="'.$size.'px" width="auto">':'<img src="images/flamegrey.png" height="'.$size.'px" width="auto">';
	echo '<div class="setpoint" style="font-size:'.$size*2.3 .'%;width:'.$size*0.7 .'px;">'.round(${'R'.$name},0).'</div></a></div>';
}
function Smokedetector($name,$size,$boven,$links) {
	global ${'S'.$name},${'SI'.$name},${'SB'.$name},${'ST'.$name};
	echo '<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;z-index:-10;" title="'.strftime("%a %e %b %k:%M:%S", ${'ST'.$name}).'">
	<form method="POST"><input type="hidden" name="Schakel" value="'.${'SI'.$name}.'"><input type="hidden" name="Naam" value="'.$name.'">';
	echo ${'S'.$name}=='Off'?'<img src="images/smokeoff.png" width="36" height="36">'
	                  :'<input type="hidden" name="Actie" value="Off"><input type="image" src="images/smokeon.png" height="'.$size.'px" width="auto">';
	echo '</form></div>';
	echo ${'SB'.$name}<40?'<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;width:36px;height:36px;background:rgba(255, 0, 0, 0.7);z-index:-11;"></div>':'';
}
function Radiator($name,$draai,$boven,$links) {
	global ${'R'.$name},${'RT'.$name};
	echo '<div style="position:absolute;top:'.$boven.'px;left:'.$links.'px;width:35px;background:rgba(150, 150, 150, 0.5);border-radius:10px;padding:0px;letter-spacing:-1px;transform:rotate('.$draai.'deg);-webkit-transform:rotate('.$draai.'deg);">'.number_format(${'R'.$name},0).'</div>';
}

function Timestamp($name,$draai,$boven,$links) {
	global ${'S'.$name},${'ST'.$name};
	echo '<div class="stamp" style="padding:0px;letter-spacing:-1px;top:'.$boven.'px;left:'.$links.'px;transform:rotate('.$draai.'deg);-webkit-transform:rotate('.$draai.'deg);">'.strftime("%k:%M",${'ST'.$name}).'</div>';
}
function Secured($boven, $links, $breed, $hoog) {
	echo '<div class="secured" style="top:'.$boven.'px;left:'.$links.'px;width:'.$breed.'px;height:'.$hoog.'px;"></div>';
}
function Motion($boven, $links, $breed, $hoog) {
	global $SThuis, $SSlapen;
	if($SThuis=='Off'||$SSlapen=='On') echo '<div class="motionr" style="top:'.$boven.'px;left:'.$links.'px;width:'.$breed.'px;height:'.$hoog.'px;"></div>';
												 else echo '<div class="motion" style="top:'.$boven.'px;left:'.$links.'px;width:'.$breed.'px;height:'.$hoog.'px;"></div>';
}
function RefreshZwave($node) {
	global $domoticzurl;
	file_get_contents($domoticzurl.'type=openzwavenodes&idx=5');
	$zwaveurl='http://127.0.0.1:1602/ozwcp/refreshpost.html';
	$zwavedata=array('fun'=>'racp','node'=>$node);
	$zwaveoptions = array('http'=>array('header'=>'Content-Type: application/x-www-form-urlencoded\r\n','method'=>'POST','content'=>http_build_query($zwavedata),),);
	$zwavecontext=stream_context_create($zwaveoptions);
	for ($k=1;$k<=5;$k++){sleep(2);$result=file_get_contents($zwaveurl,false,$zwavecontext);if($result=='OK') break;}
}
function curl($url){
    $headers = array(
	    'Content-Type: application/json',
	);
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function pingDomain($domain, $port){
    $starttime = microtime(true);
    $file      = fsockopen ($domain, $port, $errno, $errstr, 1);
    $stoptime  = microtime(true);
    $status    = 0;
    if (!$file) $status = -1;  // Site is down
    else {
        fclose($file);
        $status = floor(($stoptime - $starttime) * 1000);
    }
    return $status;
}
