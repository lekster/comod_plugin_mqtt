<?php

require_once (PROJ_DIR . "/htdocs/console/controllers/AbstractDevice.php");

abstract class AbstractMqttDevice extends AbstractDevice
{
	protected function setPortValTempl($port, $val)
	{
		//отправка запроса в mqtt, вопрос Ответ ждем синхронно?? или как закрывать таск? идея отправлять taskId и оставлять в состоянии выполняется, а асинхронный приемник
		//получая ответ ищет Task_id и закрывает его
		
		return true;
	}

	protected function getPortValTempl($port)
	{
		//отправка запроса в mqtt
		//прием аналогично методу  setPortValTempl
		return null;
	}
	
	public function ping()
	{
		//отправка запроса в mqtt
		//асинхронный приемник получает ответ 
		return true;
	}

	public function discovery()
	{
		//смотрит из объекта какого класса она вызвана и генерит событие для отправки в mqtt
		//асинхронный приемник получает ответ и уже создает новые девайсы
	}
} 