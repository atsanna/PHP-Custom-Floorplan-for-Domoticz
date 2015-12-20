#!/bin/bash
#~ Specify the ZWAVE NodeID to refresh
devid="$1"
name="$2"
#~ Check whether the refresh is already running for this device and only fire when not.
chk=`sudo ps x | grep "refreshzwave.php $devid" | grep -cv grep`
if  [ "$chk" = "0" ] ; then
   sudo echo "$(date +%Y-%m-%d) $(date +%X.%3N) RefreshZwave started for node $devid ($name)" >> /var/log/floorplan.log 2>&1 &
   php /var/www/secure/refreshzwave.php $devid $name >> /var/log/floorplan.log 2>&1 &
else
   sudo echo "$(date +%Y-%m-%d) $(date +%X.%3N) RefreshZwave already running for node $devid ($name)" >> /var/log/floorplan.log 2>&1 &
fi
