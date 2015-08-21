# PHP Custom Floorplan for Domoticz.

Goal: Using 1 page to view and control everything from my domoticz installation + using 1 script to automate everything else.
Requirements: PHP enabled webserver, I use Apache on the same RPi. Xcache recommended for storing timestamps of sent notifications.
Authentication in Domoticz must be disabled for 127.0.0.1

The page:<br>
<img src="http://i.imgur.com/09PpGwB.png"/><br>
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
With the green home button and the sleepy smiley I switch the system in states 'Home/Away' or 'Sleeping'. Depending on those states lots of things happen in the script.  

The script 'hw2domoticz.php':
The first goal of this script was to import the Smartwares weather sensors wich are connected to a Homewizard. Very soon I started using it for all kinds of other stuff. 
Since the script is programmed in PHP there aren't any limitations, only your skills and imagination. 
I execute the script by cron every minute. The script itself runs 12 times in a loop. So reaction time is 5 seconds. This number is easily adjustable, could be that a higher rate is possible now I have the variables in domoticz instead of a seperate SQLite database. 
Some things this script does for me:
- Switching off lights after x seconds of movement
- Switch everything off when away or a sleep
- Controls heating
- Sends alerts with telegram, sms or with high priority to iOS. Alerts can be anything like 'Movement detected in living room' while not at home to 'Watch out, bedroom is 10° hotter than rest of the house'.
- Import homewizard data
- ...

Installation:

Place the files on a PHP enabled webserver. Protect the secure folder for external access. 
Adjust variables in secure/functions.php

in secure/functions.php you'll see this:
<pre>$authenticated = false;
$authenticated = true;</pre><br/>
It's up to you to create something for authentication (or don't publish on the net).

If you put this in /home/pi/domoticz/scripts/domoticz_main you'll have instant reaction of the hw2domoticz script.
<pre>
#!/bin/sh
curl 'http://127.0.0.1:1603/secure/hw2domoticz.php'
</pre><br>
