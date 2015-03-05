<?php

use Core\Model;

namespace Sharding\Core\Env\Helper;

use Core\Utils as _U;

trait THelper
{
	/**
	 * Set default connection for a non-sharded models
	 * 
	 * @access public
	 */
	public function useDefaultConnection()
	{
		$this -> destinationDb = $this -> app -> getDefaultConnection();
		
		$this -> setReadDestinationDb();
		$this -> setWriteDestinationDb();
	}

	
	/**
	 * Select destination shard by shard id
	 * 
	 * @param int $objectId
	 * @access public
	 */
	public function setShardById($objectId)
	{
		$shardId = $this -> parseShardId($objectId);
		$this -> selectModeStrategy();

		if ($this -> modeStrategy) {
			$this -> modeStrategy -> selectShardById($shardId);
				
			self::$targetShardCriteria = $this -> modeStrategy -> getCriteria();
			
			$this -> destinationId = $this -> modeStrategy -> getId();
			$this -> destinationDb = $this -> modeStrategy -> getDbName();
			$this -> destinationTable = $this -> modeStrategy -> getTableName();
			$this -> setRelationShard();

			$this -> setDestinationSource();
		} else {
			$this -> useDefaultConnection();
		}
		
		$this -> setReadDestinationDb();
		$this -> setWriteDestinationDb();
	}
	

	public function setShardByDefault($relation)
	{
		$this -> destinationTable = $relation -> baseTable;
		$this -> setDestinationSource();
	}	

	
	
	/**
	 * Select destination shard by criteria
	 * 
	 * @param int|string $criteria
	 * @access public
	 */
	public function setShardByCriteria($criteria)
	{
		self::$targetShardCriteria = $criteria;
		$this -> selectModeStrategy();
	
		if ($this -> modeStrategy) {
			$this -> modeStrategy -> selectShardByCriteria(self::$targetShardCriteria);
			
			$this -> destinationId = $this -> modeStrategy -> getId();
			$this -> destinationDb = $this -> modeStrategy -> getDbName();
			$this -> destinationTable = $this -> modeStrategy -> getTableName();
			$this -> setRelationShard();

			$this -> setDestinationSource();
		} else {
			$this -> useDefaultConnection();
		}
		
		$this -> setReadDestinationDb();
		
		return $this;
	}
	
	
	/**
	 * Set shard
	 *
	 * @param array $criteria
	 * @access public
	 */
	public function setShard($params)
	{
		self::$targetShardCriteria = true;

		if (isset($params['conneciton']) && !empty($params['connection'])) {
			$this -> destinationDb = $params['connection'];
		} else {
			$this -> destinationDb = $this -> app -> getMasterConnection();
		}
		if (isset($params['source']) && !empty($params['source'])) { 
			$this -> destinationTable = $params['source'];
			
			$this -> setDestinationSource();
			$this -> setReadDestinationDb();
			$this -> setWriteDestinationDb();
			
			return $this;
		} else {
			return false;
		}
	}
	

	
	/**
	 * Parse shard id from object's primary key.
	 * For sharded models only 
	 *
	 * @access public 
	 * @param string $objectId 
	 * @return int|string
	 */
	public function parseShardId($objectId)
	{
		$separator = $this -> app -> getShardIdSeparator();
		
		$idParts = explode($separator, $objectId);
		if ($idParts && count($idParts) > 1) {
			return $idParts[1];
		} else {
			return false;
		}
	}
	
	
	/**
	 * Return all sharded criteria for entity
	 *
	 * @access public
	 */
	public function getShardedCriteria()
	{
		$this -> selectModeStrategy();

		if ($this -> modeStrategy) {
			$criteria = $this -> modeStrategy -> selectAllCriteria(); 
		}
		
		return $criteria;
	}

	
	/**
	 * Return all available shards for entity
	 *
	 * @access public
	 */
	public function getAvailableShards()
	{
		$this -> selectModeStrategy();
	
		if ($this -> modeStrategy) {
			$shards = $this -> modeStrategy -> selectAllShards();
		} 

		return $shards;
	}
	
	
	/**
	 * Select strategy mode (Loadbalance, Limitbatch) for 
	 * specific model by default calling class
	 *
	 * @access public 
	 */
	public function selectModeStrategy()
	{
		if (!$this -> relationOf) {
			$object = new \ReflectionClass(__CLASS__);
			$entityName = $object -> getShortName();
		} else {
			$entityName = $this -> relationOf;
		}

		if ($shardModel = $this -> app -> loadShardModel($entityName)) {
			$modeName = '\Sharding\Core\Mode\\' . ucfirst($shardModel -> shardType) . '\Strategy';
			$this -> modeStrategy = new $modeName($this -> app);
			$this -> modeStrategy -> setShardEntity($entityName);
			$this -> modeStrategy -> setShardModel($shardModel);
		}
	}

	
	/**
	 * Check relations for shardable models by object
	 *
	 * @access public
	 * @return boolean
	 */
	public function getRelationByObject()
	{
		$className = get_class($this);

		foreach ($this -> app -> config -> shardModels as $model => $data) {
			if (isset($data -> relations)) {
				foreach ($data -> relations as $obj => $rel) {
					$objects = [trim($this -> app -> config -> nsConvertation . '\\' . $obj, '\\'),
								trim($rel -> namespace . '\\' . $obj, '\\')];	

					if (in_array(trim($className, '\\'), $objects)) {
						$this -> relationOf = $model;
						
						return $rel;
					}
				}
			}
		}
	
		return false;
	}
	
	
	/**
	 * Check relations for shardable models by alias
	 *
	 * @access public
	 * @return boolean
	 */
	public function getRelationByProperty($alias)
	{
		$className = get_class($this);
	
		foreach ($this -> app -> config -> shardModels as $model => $data) {
			isset($data -> namespace) ? $fullBasePath = trim($data -> namespace, '\\') . '\\' . $model : $fullBasePath = $model;
			
			if (trim($className, '\\') == $fullBasePath) {
				if (isset($data -> relations)) {
					foreach ($data -> relations as $obj => $rel) {
						if ($rel -> relationName == $alias) {
							return true;
						}
					}
				}
			}
		}
	
		return false;
	}
	
	
	protected function setRelationShard()
	{
		if ($relation = $this -> getRelationByObject()) {
			$parts = explode('_', $this -> destinationTable);
			$sep = $this -> app -> config -> shardIdSeparator;
			$this -> destinationTable = implode($sep . $relation -> relationName . $sep, $parts);
		} 
		
		return;
	} 

	
	public function unsetNeedShard($param = false)
	{
		self::$needTargetShard = $param;
	}
	
	
	public function setConvertationMode($mode = true)
	{
		self::$convertationMode = $mode;
	}
	

	public function getShardTable()
	{
		return $this -> destinationTable;	
	}
	
	
	public function getShardDb()
	{
		return $this -> destinationDb;
	}
	
	
	/**
	 *  Just test, nothing else
	 */	
	public function testIsHere()
	{
		die('yep, your model supports sharding');
	}
}