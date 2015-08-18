<?php
for ($k = 1 ; $k <= 2; $k++){ 
	sleep(1);
	$zwaveurl = 'http://192.168.0.8:1602/ozwcp/refreshpost.html';
	$zwavedata = array('fun' => 'racp', 'node' => '20');
	$zwaveoptions = array(
		'http' => array(
			'method'  => 'POST',
			'content' => http_build_query($zwavedata),
		),
	);
	$zwavecontext  = stream_context_create($zwaveoptions);
	$zwaveresult = file_get_contents($zwaveurl, false, $zwavecontext);
	echo '<pre>'.$zwaveresult.'</pre>';
	sleep(1);
}