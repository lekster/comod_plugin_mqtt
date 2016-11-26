<?php

require_once( __DIR__ . "/../src/phpMQTT.php");



class FakeDevice
{
	protected $uuid;
	protected $mqtt;
	protected $ports = [
		'a0' => ['AccessType' => 'R', 'PortReal' => 'temperature10'],
	];

	const EVENT_ERROR = "ERROR";
	const EVENT_PORT_CHANGE = "PortChange";
	const EVENT_PORT_VAL = "PortVal";

	const FG_BLACK  = 30;
    const FG_RED    = 31;
    const FG_GREEN  = 32;
    const FG_YELLOW = 33;
    const FG_BLUE   = 34;
    const FG_PURPLE = 35;
    const FG_CYAN   = 36;
    const FG_GREY   = 37;

    const BG_BLACK  = 40;
    const BG_RED    = 41;
    const BG_GREEN  = 42;
    const BG_YELLOW = 43;
    const BG_BLUE   = 44;
    const BG_PURPLE = 45;
    const BG_CYAN   = 46;
    const BG_GREY   = 47;

    const RESET       = 0;
    const NORMAL      = 0;
    const BOLD        = 1;
    const ITALIC      = 3;
    const UNDERLINE   = 4;
    const BLINK       = 5;
    const NEGATIVE    = 7;
    const CONCEALED   = 8;
    const CROSSED_OUT = 9;
    const FRAMED      = 51;
    const ENCIRCLED   = 52;
    const OVERLINED   = 53;



	public static function ansiFormat($string)
    {
	    $args = func_get_args();
	    array_shift($args);
        
        $code = implode(';', $args);

        return "\033[0m" . ($code !== '' ? "\033[" . $code . "m" : '') . $string . "\033[0m";
    }



	public function __construct($uuid)
	{
		$this->uuid = $uuid;
		$this->mqtt = new phpMQTT("192.168.1.150", 1883, "remote_device_{$this->uuid}"); //Change client name to something unique
	}

	public function procmsg($topic,$msg)
	{
		echo self::ansiFormat("RECIEVED: ".date("r")."\nTopic:{$topic}\n$msg\n\n", self::FG_GREEN, self::BOLD);
		//$mqtt->publish("devices/","Hello World! at ".date("r"),0);
		list($null, $uuid, $cmd, $arg1, $null) = explode("/", $topic,5);
		echo ("$uuid, $cmd, $arg1, $null \n");
		//$mqtt->publish("devices/123/ports/a0/12313123/1111","Hello World! at ".date("r"),0);
		if ($cmd == 'ports')
		{
			$portName = $arg1;
			if (isset($this->ports[$portName]))
			{
				list($msgCmd, $msgArgs) = explode(":", $msg, 2);
				echo ("$msgCmd, $msgArgs \n");
				switch ($msgCmd) {
					case 'get':
						$val = $this->getPortValTempl($portName);
						$this->generateEvent(FakeDevice::EVENT_PORT_VAL, $portName, $val);
						break;
					
					case 'set':
						$this->setPortValTempl($portName, $msgArgs);
						$this->generateEvent(FakeDevice::EVENT_PORT_VAL, $portName, $msgArgs);
						break;

					default:
						$this->generateEvent(FakeDevice::EVENT_ERROR, "unknown command|$cmd, $arg1, $msgCmd, $msgArgs");
						break;
				}

				//$this->setPortValTempl($arg1, )
			}
			else
			{
				//generate error event
				$this->generateEvent(phpMQTT::EVENT_ERROR, "port not exists|$arg1");
			}
		}
	}

	protected function mqtt_publish($topic, $msg)
	{
		echo self::ansiFormat("PUBLISH MQTT - $topic, $msg \n", self::FG_RED, self::BOLD);
		$this->mqtt->publish($topic, $msg,0);
	}

	protected function generateEvent($type, ...$args)
	{
		//portChanged, Error
		switch ($type) {
			case phpMQTT::EVENT_ERROR:
				$errText = $args[0];
				$pubMsg = "$type:$errText";
				$this->mqtt_publish("devices/{$this->uuid}/event",$pubMsg);
				break;
			case phpMQTT::EVENT_PORT_CHANGE:
				$port = $args[0];
				$val = $args[1];
				$pubMsg = "$type:$port/$val" ;
				$this->mqtt_publish("devices/{$this->uuid}/event",$pubMsg);
				break;
			case phpMQTT::EVENT_PORT_VAL:
				$port = $args[0];
				$val = $args[1];
				$pubMsg = "$type:$port/$val" ;
				$this->mqtt_publish("devices/{$this->uuid}/event",$pubMsg);
				break;
			
			default:
				# code...
				break;
		}
		
	}

	public function handle()
	{
		if(!$this->mqtt->connect()){
			exit(1);
		}

		$topics["devices/{$this->uuid}/ports/#"] = array("qos"=>0, "function"=>[$this, "procmsg"]);

		$this->mqtt->subscribe($topics,0);

		while($this->mqtt->proc(false)){
			var_dump("SLEEP");
			sleep(1);
		}


		$this->mqtt->close();
	}

	protected function setPortValTempl($port, $val)
	{
		$fileName = "/tmp/dev_mqtt_ports_" . $this->uuid;
		$res = @file_get_contents($fileName);
		$ret = is_array(@json_decode($res, true)) ? @json_decode($res, true) : array();
		$ret[$port] = $val;
		file_put_contents($fileName, json_encode($ret));

		return true;
	}

	protected function getPortValTempl($port)
	{
		$fileName = "/tmp/dev_mqtt_ports_" . $this->uuid;
		$res = @file_get_contents($fileName);
		$ret = is_array(@json_decode($res, true)) ? @json_decode($res, true) : array();
		if (is_array($ret))
		{
			return @$ret[$port];
		}
		return null;
	}

}

$d =  new FakeDevice("123");
$d->handle();