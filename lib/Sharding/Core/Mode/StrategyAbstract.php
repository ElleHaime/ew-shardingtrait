<?php 

namespace Sharding\Core\Mode;

use Core\Utils as _U;

abstract class StrategyAbstract
{
	public $app;
	protected $shardModel			= false;
	protected $shardEntity			= false;

	
	public function __construct($app)
	{
		$this -> app = $app;
	}

	public function setShardModel($model)
	{
		$this -> shardModel = $model;
	}
	
	public function setShardEntity($entity)
	{
		$this -> shardEntity = strtolower($entity);
	}
	
	public function getShardModel()
	{
		return $this -> shardModel;
	}
	
	abstract public function selectShardByCriteria($arg);
	
	abstract public function selectShardById($arg);
	
	abstract public function selectAllCriteria(); 
}