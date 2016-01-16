<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="HandheldFriendly" content="true" />
<meta name="viewport" content="width=device-width,height=device-height, user-scalable=yes, minimal-ui" />
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">

<title>home.egregius.be</title>
<?php 
error_reporting(E_ALL);ini_set("display_errors", "on");
if(isset($_COOKIE["HomeEgregius"])) $gebruiker = $_COOKIE["HomeEgregius"]; else $gebruiker = 'none';

?>
<link href="css.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div class="header">
    <a href="floorplan.php" class="abutton home gradient" style="padding:10px 0px;">Floorplan</a>
</div>