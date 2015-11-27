#!/bin/bash
    DomoticzState=`sudo service domoticz.sh status`

    if [[ $DomoticzState == "domoticz is running." ]]
            then
                    echo 'Domoticz is running. Nothing to do.'
    elif [[ $DomoticzState == "domoticz is not running ... failed!" ]]
            then
                    echo 'Domoticz is not running. Restarting Domoticz...'
                    sudo service domoticz.sh restart
                    echo 'Domoticz restarted.'
    fi