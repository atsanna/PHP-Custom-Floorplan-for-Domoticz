#!/bin/sh
sudo truncate -s 0 /var/log/home.egregius.be.access.log
sudo truncate -s 0 /var/log/home.egregius.be.error.log
sudo truncate -s 0 /var/log/apache-error.log
sudo truncate -s 0 /var/log/domoticz.log
sudo chown pi:pi /var/log/domoticz.log
sudo chown www-data:www-data /var/log/home.egregius.be.access.log
sudo chown www-data:www-data /var/log/home.egregius.be.error.log
sudo chown www-data:www-data /var/log/apache-error.log
sudo kill -HUP `pgrep domoticz`
sudo kill -HUP `pgrep apache`