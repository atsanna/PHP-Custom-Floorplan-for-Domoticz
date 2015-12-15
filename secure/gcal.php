#!/usr/bin/php
<?php
error_reporting(E_ALL);ini_set("display_errors", "on");
require '/home/pi/vendor/autoload.php';
include 'functions.php';
$domoticz=json_decode(file_get_contents($domoticzurl.'type=devices&used=true'),true);
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
define('APPLICATION_NAME', 'Domoticz');
define('CREDENTIALS_PATH', '/home/pi/.credentials/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', '/var/www/secure/gcal.json');
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR_READONLY)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfigFile(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = file_get_contents($credentialsPath);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->authenticate($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, $accessToken);
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->refreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, $client->getAccessToken());
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);

// Print the next 10 events on the user's calendar.
$calendarId = 'primary';
$optParams = array(
  'maxResults' => 5,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => date('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);
$now = time()+60;
if (count($results->getItems()) > 0) {
  foreach ($results->getItems() as $event) {
    $start = strtotime($event->start->dateTime);
    if (empty($start)) {
      $start = strtotime($event->start->date);
    }
	$datetime = strftime("%a %e %b %k:%M:%S", $start);
	printf("Event: %s at %s\n", $event->getSummary(), $datetime);
	if($start<=$now){
		if(substr($event->getSummary(),0,4) == 'Domo') {
			$item = explode(" ", substr($event->getSummary(),5,40));
			$action = $item[0];
			$place = $item[1];
			if(isset($item[2])) $detail = $item[2];
			if($action=="Wake") {
				if(${'Dlevel'.$place}<1) {
					$mc->set('dimmer'.$place,2);telegram("GCal Wake ".$place." = ".Dim(${'DI'.$place},${'Dlevel'.$place}+1));
				}
			} else if($action=="Sleep") {
				if(${'Dlevel'.$place}>1) {
					$mc->set('dimmer'.$place,1);telegram("GCal Sleep ".$place." = ".Dim(${'DI'.$place},${'Dlevel'.$place}-1));
				}
			} else if($action=="Dimmer") {
				if(${'Dlevel'.$place}<1) {
					telegram("GCal Dimmer ".$place." ".Dim(${'DI'.$place},$detail));
				}
			} else if($action=="Licht") {
				if(${'S'.$place}!=$detail) {
					telegram("GCal Schakel ".$place." ".$detail." = ".Schakel(${'SI'.$place},$detail));
				}
			} else if($action=="Setpoint") {
				if(${'R'.$place}!=$detail) {
					$mc->set('setpoint'.${'RI'.$place},2);telegram("GCal Setpoint ".$place." ".$detail." = ".Udevice(${'RI'.$place},0,$detail));
				}
			}
		}
	}
  }
}
}