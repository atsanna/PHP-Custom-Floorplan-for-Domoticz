#!/bin/sh
#sudo rm /var/log/ping_192.168.0.*
sudo truncate -s 0 /var/log/home.egregius.be.access.log
sudo truncate -s 0 /var/log/home.egregius.be.error.log
sudo truncate -s 0 /var/log/apache-error.log
sudo truncate -s 0 /var/log/domoticz.log
sudo chown pi:pi /var/log/domoticz.log
sudo chown www-data:www-data /var/log/home.egregius.be.access.log
sudo chown www-data:www-data /var/log/home.egregius.be.error.log
sudo chown www-data:www-data /var/log/apache-error.log
sudo ln -s /var/log/ozwcp.poll.XXXXXX.xml /home/pi/domoticz/ozwcp.poll.XXXXXX.xml
sudo ln -s /var/log/OZW_Log.txt /home/pi/domoticz/Config/OZW_Log.txt
sudo service domoticz.sh restart
sudo service apache2 reload