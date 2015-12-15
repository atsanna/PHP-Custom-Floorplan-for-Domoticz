#!/usr/bin/php
<?php
error_reporting(E_ALL);ini_set("display_errors", "on");
if(isset($_REQUEST['tag'])&&isset($_REQUEST['value'])) {
	$url = 'https://dweet.io/dweet/for/egregius';
	$data = array($_REQUEST['tag'] => $_REQUEST['value']);
}
else {
	echo "Nothing sent";
	exit;
}
$options = array(
	'http' => array(
		'method'  => 'POST',
		'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
		'content' => http_build_query($data),
	),
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
echo '<pre>'.$result.'</pre>';
