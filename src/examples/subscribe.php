<?php

require_once( __DIR__ . "/../phpMQTT.php");

	
$mqtt = new phpMQTT("192.168.1.150", 1883, "phpMQTT Server"); //Change client name to something unique

if(!$mqtt->connect()){
	exit(1);
}

$topics['ferries/IOW/#'] = array("qos"=>0, "function"=>"procmsg");
$topics['devices/#'] = array("qos"=>0, "function"=>"procmsg");

$mqtt->subscribe($topics,0);

while($mqtt->proc()){
	sleep(1);
}


$mqtt->close();

function procmsg($topic,$msg){
		echo "Msg Recieved: ".date("r")."\nTopic:{$topic}\n$msg\n";
}
	


?>
