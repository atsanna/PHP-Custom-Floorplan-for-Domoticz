<?php
define("ZWAVE_HOST", "10.0.0.10");
define("ZWAVE_PORT", 6004);

function __autoload($classname) {
    require_once("lib/" . $classname . ".php");
}

if (isset($_REQUEST["command"])) {
    switch ($_REQUEST["command"]) {
        case "rooms":
            $zwaveServer = new ZwaveServer(ZWAVE_HOST, ZWAVE_PORT);
            $zwaveServer->send("ALIST");
            $list = $zwaveServer->read();
            $list = substr($list, 0, strlen($list) - 1);
            $devicesList = explode("#", $list);
            $zones = array();
            foreach ($devicesList as $device) {
                $device = explode("~", $device);
                if ($device[3])
                    $zones[] = $device["3"];
            }
            $zones = array_unique($zones);
            $strZones = implode("~", $zones);
            echo $strZones;
            break;

        case "devices":
            $zwaveServer = new ZwaveServer(ZWAVE_HOST, ZWAVE_PORT);
            $zwaveServer->send("ALIST");
            $list = $zwaveServer->read();
            $list = substr($list, 0, strlen($list) - 1);
            //echo $list;
            $devicesList = explode("#", $list);
            $devices = "";
            foreach ($devicesList as $device) {
                $device = explode("~", $device);
                $devices .= $device[1] . "~" . $device[2] . "~" . $device[3] . "~" . $device[4] . "#";
            }

            $devices = substr($devices, 0, strlen($devices) - 1);
            echo $devices;

            $zwaveServer->close();

            break;

        case "control":
            if (isset($_REQUEST["type"])) {
                switch ($_REQUEST["type"]) {
                    case ($_REQUEST["type"] == "binary" || $_REQUEST["type"] == "Binary Switch" || $_REQUEST["type"] == "Binary Power Switch"):
                        $zwaveServer = new ZwaveServer(ZWAVE_HOST, ZWAVE_PORT);
                        $zwaveServer->send("DEVICE~" . $_REQUEST["node"] . "~" . $_REQUEST["level"] . "~Binary Switch");
                        break;

                    case ($_REQUEST["type"] == "Multilevel Power Switch" || $_REQUEST["type"] == "Multilevel Switch"):
                        $zwaveServer = new ZwaveServer(ZWAVE_HOST, ZWAVE_PORT);
                        $zwaveServer->send("DEVICE~" . $_REQUEST["node"] . "~" . $_REQUEST["level"] . "~Multilevel Power Switch");
                        break;
                }
                echo $zwaveServer->read();
            } else {
                echo "Type not specified!";
            }
            break;
        default:
            echo "undefined";
            break;
    }
}
else
    echo "Nothing to process!";
?>
