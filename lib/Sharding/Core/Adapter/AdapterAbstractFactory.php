<?php 

namespace Sharding\Core\Adapter;

abstract class AdapterAbstractFactory
{
	abstract function addConnection($data);
}