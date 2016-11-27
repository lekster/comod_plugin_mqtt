<?php

require_once( __DIR__ . "/../phpMQTT.php");

	
$mqtt = new phpMQTT("192.168.1.150", 1883, "phpMQTT Pub Example23"); //Change client name to something unique

if ($mqtt->connect()) {
	$mqtt->publish("devices/123/0/ports/a0/12313123/1111","set:123111",0);
	$mqtt->close();
}

$portseq = 0;
$msg = "
correlation_id:1234
##
set:123122:$portseq
";

?>
