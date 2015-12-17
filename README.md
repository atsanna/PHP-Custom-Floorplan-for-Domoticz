# PHP Custom Floorplan for Domoticz.

Goal: Using 1 page to view and control everything from my domoticz installation + using 1 script to automate everything else.
Requirements: PHP enabled webserver, I use Apache on the same RPi. memcached recommended for storing timestamps of sent notifications and other temporary variables.
Authentication in Domoticz must be disabled for 127.0.0.1

##The Floorplan:<br>
<img src="http://i.imgur.com/us2iu3E.png"/><br>
The page fits perfect on a iPhone5 and can be added on startscreen as an application for full screen viewing.
It's built by using a background image with the layout of the house, on top of that lot's of fixed positioned DIVs.

On the left side: from top to bottom:
- Alerts: enable/disable notifications and alerts
- Light outside
- Temperature outside
- Rain meter/rain expectation (buienradar.be)
- Arrows to control volume of my Denon amplifier
- Buttons for scenes/groups like 'Listening to radio', 'Watch TV', 'Watch Kodi', 'Diner' and 'Switch everything off'.
On the plan we see all thermometers, setpoints, radiator valves, smoke detectors, lights, open doors, open port, timestamp of motion sensors,...
With the green home button and the sleepy smiley I switch the system in states 'Home/Away' or 'Sleeping'. Depending on those states lots of things happen in the cron script.  

Dimmers are controlled by buttons: Instant On/off or a % value. <br>
Also a wake and sleep function is available. These dims the light slowly on or off. <br>
<img src="http://i.imgur.com/EaWXP91.png"/><br>

Setpoints are controlled by buttons. Current room temperature is shown and current setpoint is marked in green.<br>
<img src="http://i.imgur.com/0EVOxUb.png"/><br>



##The script secure/hw2domoticz.php:
Can be used to import the Smartwares weather sensors wich are connected to a Homewizard. 

##The script secure/cron.php:
Since the script is programmed in PHP there aren't any limitations, only your skills and imagination. 
I execute the script by cron every minute.  
Some things this script does for me:
- Switching off lights after x minutes of no movement
- Switch everything off when away or a sleep
- Controls heating
- Sends alerts with telegram, sms or with high priority to iOS. Alerts can be anything like 'Movement detected in living room' while not at home to 'Watch out, bedroom is 10° hotter than rest of the house'.
- Read Google Calendar
- ...

##Installation:
Place the files on a PHP enabled webserver. Protect the secure folder for external access. 
Adjust variables in secure/functions.php

in secure/functions.php you'll see this; it's up to you to create something for authentication (or don't publish on the net).
<pre>$authenticated = false;
$authenticated = true;</pre><br/>


If you put this in /home/pi/domoticz/scripts/domoticz_main you'll have instant reaction of the cron script.
<pre>
#!/bin/sh
/var/www/secure/cron.php
</pre><br>

Create a cron job with 'sudo crontab -e'. When the option 'all' is added more stuf is done. Without it it only runs time critical things like switching lights on when movement is detected. 
<pre>
* * * * * /var/www/secure/cron.php all >/dev/null 2>&1
</pre>

##The variables
All switch states, thermometers are stored in variables. Here's a list of used variables, note that 'name' is the name of device in domoticz, converted to lowercase.
- Tname = temperature, TIname = IDX of thermometer, TTname = last timestamp
- Dname = state of dimmer, Dlevelname = Dimlevel of dimmer, DIname = IDX of dimmer, DTname = last timestamp of dimmer
- Uname = power usage
- Rname = radiator valve or setpoint value, RIname = IDX of device, RTname = last timestamp
All other devices are stored with S:
- Sname = switch state, SIname = IDX of device, STname = last timestamp

##The Functions secure/functions.php
###ios($msg)
Sends $msg to your iOS device. Messages are delivered with high priority. That means that they make sound even when iPhone is on silent or 'do not disturb' mode
###sms($msg)
Sends $msg thru Clickatells SMS gateway
###telegram($msg)
Sends $msg using telegram bot
###Schakel($idx,$cmd)
Switches $idx. $cmd mostly 'On' or 'Off'.
###Scene($idx)
Activates scene $idx
###Dim($idx,$level)
Set dimlevel of dimmer $idx to $level
###Udevice($idx, $nvalue, $svalue)
Updates device $idx with $nvalue and $svalue
###Textdevice($idx,$text)
Updates textdevice $idx with $text
###percentdevice($idx,$value)
Updates percetage device $idx with $value
###voorwarmen($temp, $settemp,$seconds)
Calculates a time period depending on $temp and $settemp multiplied by $seconds per degree
###setradiator($temp,$setpoint)
Calculates the setpoint for a radiatorvalve depending on current room temperature $temp and desired $setpoint. Ex: Room temperature = 19°, setpoint = 20° -> Set valve to 24°.
###RefreshZwave($node)
'Refreshes' the zwave $node. I like to call this custom polling.
###pingDomain($domain, $port)
Check availability of $port at $domain (pinger or system alive checker)
