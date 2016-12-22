<?php

require_once (__DIR__ . "/../../src/class.AbstractMqttDevice.php");


class mqtt_DS18B20 extends AbstractMqttDevice
{
	
	public function getPortsConf()
	{
		return array(
			'a0' => ['AccessType' => 'R', 'PortReal' => 'temperature10'],
		);
	}

	public function getVersion() {return "0.0.1"; }

}