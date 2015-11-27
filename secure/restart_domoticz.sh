#!/bin/sh

sudo service domoticz.sh stop
sleep 3
sudo killall domoticz
sleep 3
sudo service domoticz.sh start
