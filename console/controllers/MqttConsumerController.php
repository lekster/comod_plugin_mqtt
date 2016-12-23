<?php


namespace console\controllers;

use yii;
use yii\console\Controller;
use yii\console\Exception;
use yii\helpers\Console;
use common\models\DeviceType;
use common\models\Device;
use common\models\Properties;
use common\models\PValues;
use common\models\Objects;
use src\helpers\SysHelper;

#require_once(PROJ_DIR . '/bootstrap.php');
chdir(GIRAR_BASE_DIR);

require_once( __DIR__ . "/../../src/phpMQTT.php");

	


date_default_timezone_set('Europe/Moscow');
set_time_limit(0);

class MqttConsumerController extends AbstractCronController
{

	const EVENT_PORTVAL = "PortVal";
	const EVENT_ERROR = "ERROR";
	//const MQTT_HOST = "vps172202180.mtu.immo"; //192.168.1.150

	protected function gen_uuid() {
	    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	        // 32 bits for "time_low"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

	        // 16 bits for "time_mid"
	        mt_rand( 0, 0xffff ),

	        // 16 bits for "time_hi_and_version",
	        // four most significant bits holds version number 4
	        mt_rand( 0, 0x0fff ) | 0x4000,

	        // 16 bits, 8 bits for "clk_seq_hi_res",
	        // 8 bits for "clk_seq_low",
	        // two most significant bits holds zero and one for variant DCE1.1
	        mt_rand( 0, 0x3fff ) | 0x8000,

	        // 48 bits for "node"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	    );
	}


	/*
		events

		PortVal:a0/2
		ERROR:port not exists|a02


	*/

	/**
		@daemon
	*/
	public function actionConsume()
	{
		$mqttHost = SysHelper::getPluginSetting("Mqtt", "mqtt_host");
		$mqtt = new \phpMQTT($mqttHost, 1883, "MQTT_consumer_controller"); //Change client name to something unique


		if(!$mqtt->connect()){
			exit(1);
		}

		//$topics['ferries/IOW/#'] = array("qos"=>0, "function"=>"procmsg");
		$topics['devices/+/event'] = array("qos"=>0, "function"=>array($this, "handleDevicesEvent"));

		$mqtt->subscribe($topics,0);

		while($mqtt->proc()){
			sleep(1);
		}

		$mqtt->close();
	}

	public function handleDevicesEvent($topic,$msg)
	{
		list($null, $uuid, $null) = explode("/", $topic, 3);
		Yii::trace("uuid|$uuid");
		Yii::trace("Msg Recieved: ".date("r")."|Topic:{$topic}|$msg");

		list($cmd, $val) = explode(":", $msg, 2);
		switch ($cmd)
		{
			case self::EVENT_PORTVAL:
				list($port, $val) = explode("/", $val, 2);
				Yii::trace("port-val|$port|$val");
				try
				{
					$dev = Device::find()->where(['device_uuid' => $uuid])->one();
				}
				catch(\Exception $e)
				{
					Yii::warning("Exception|" . $e->getMessage());
				}

				if (@$dev)
				{
					//find main object with linked device
					$value = PValues::find()->where(['device_id' => $dev->device_id, 'device_port_name' => $port])->one();
					if (@$value)
					{
						//if obj val != val - update all objects and linked devices
						$obj = Objects::find()->where(['id' => $value->object_id])->one();
						$obj->setValueByPropertyIdAndDevice($value->property_id, $val, false, null);
					}
					else
					{
						Yii::warning("Pvalue not found for device|$uuid");
					}
				}
				else
				{
					Yii::warning("device not found|$uuid");
				}
				break;

			case self::EVENT_ERROR:

				Yii::error("ERROR MQTT detected|$topic|$msg");
				
				break;
			
			default:
				# code...
				break;
		}

	}

}


