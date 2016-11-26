<?php

require_once (PROJ_DIR . "/htdocs/console/controllers/AbstractDevice.php");

class mqtt_DS18B20 extends AbstractDevice
{
	
	protected $MNT_DIR = '/mnt/1wire/';
	protected $TYPES = ['DS18B20'];

	protected function findAll1wireDevAddr()
	{
		$str = "find {$this->MNT_DIR} -maxdepth 1  -type d  | grep -E '[0-9A-F]{2}\.'";
		$ret = [];
		exec($str, $ret);
		return $ret;
	} 

	public function discovery()
	{
		$ret = [];
		$paths = $this->findAll1wireDevAddr();
		foreach ($paths as $path)
		{
			$addr = substr($path, strrpos($path, "/") + 1);
			$type = exec("cat {$this->MNT_DIR}/$addr/type");
			if (in_array($type, $this->TYPES))
				$ret[] = $addr;
		}
		return $ret;
	}
	
	public function getPortsConf()
	{
		return array(
			'a0' => ['AccessType' => 'R', 'PortReal' => 'temperature10'],
		);
	}

	protected function setPortValTempl($port, $val)
	{
		return true;
	}

	protected function getPortValTempl($port)
	{
		$str = "cat {$this->MNT_DIR}/{$this->_addr}/$port";
		return exec($str);
	}
	
	public function ping()
	{
		return true;
	}

	public function getVersion() {return "0.0.1"; }

}