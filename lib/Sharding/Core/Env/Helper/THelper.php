<?php

use Core\Model;

namespace Sharding\Core\Env\Helper;

use Sharding\Core\Model\Model as Model;


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
	 * Set default shard and connection for sharded models
	 *
	 * @access public
	 */
	public function useDefaultShard()
	{
		if (!$this -> relationOf) {
			$object = new \ReflectionClass(get_class($this));
			$entityName = $object -> getShortName();
		} else {
			$entityName = $this -> relationOf;
		}
	
		$entityShards = $this -> app -> getAllShards($entityName);
		if (!empty($entityShards)) {
			self::$targetShardCriteria = true;
			$this -> destinationTable = $entityShards[0]['source'];
			$this -> destinationDb = $this -> app -> getDefaultConnection();
				
			$this -> setupShard();
			
			return $this;
		} else {
			throw new \Exception('Model doesn\'t support sharding');
			return false;
		}
	}

	
	/**
	 * Select destination shard by shard id
	 * 
	 * @param int $objectId
	 * @access public
	 */
	public function setShardById($objectId)
	{
		if (!isset($objectId) || $objectId === '') {
			throw new \Exception('Criteria couldn\'t be NULL');
			return false;
		}
		
		$shardId = $this -> parseShardId($objectId);
		$this -> selectModeStrategy();
		
		if ($this -> modeStrategy) {
			$this -> modeStrategy -> selectShardById($shardId);
			self::$targetShardCriteria = true;
			
			$this -> destinationId = $this -> modeStrategy -> getId();
			$this -> destinationDb = $this -> modeStrategy -> getDbName();
			$this -> destinationTable = $this -> modeStrategy -> getTableName();
			
			$this -> setupShard();
			
			return $this;
		} else {
			$this -> useDefaultConnection();
		}
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
		if (!isset($criteria) || $criteria === '') {
			throw new \Exception('Criteria couldn\'t be NULL');
			return false;
		}

		$this -> selectModeStrategy();

		if ($this -> modeStrategy) {
            self::$targetShardCriteria = true;
			$this -> modeStrategy -> selectShardByCriteria($criteria);

			$this -> destinationId = $this -> modeStrategy -> getId();
			$this -> destinationDb = $this -> modeStrategy -> getDbName();
			$this -> destinationTable = $this -> modeStrategy -> getTableName();

			$this -> setupShard();
			
			return $this;
		} else {
			throw new \Exception('bu! No shards by criteria');
			return false;
			
			print_r("\n\rbu! No shard by criteria\n\r");
			$this -> useDefaultConnection();
		}
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

		if (isset($params['source']) && !empty($params['source'])) {
			$this -> destinationTable = $params['source'];
			if (isset($params['connection']) && !empty($params['connection'])) {
				$this -> destinationDb = $params['connection'];
			} else {
				$this -> destinationDb = $this -> app -> getMasterConnection();
			}
			$this -> setupShard();
			
			return $this;
		} else {
			$this -> useDefaultShard();			
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
			$object = new \ReflectionClass(get_class($this));
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
		$reflection = new \ReflectionClass($className);
		
		foreach ($this -> app -> config -> shardModels as $model => $data) {
			if (isset($data -> relations)) {
				foreach ($data -> relations as $obj => $rel) {
					if ($obj == $reflection -> getShortName()) {
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
	
	
	public function getShardId()
	{
		return $this -> destinationId;
	}
	

	public function saveObject()
	{
		if (self::$targetShardCriteria === false) {
			throw new Exception('Shard criteria must be setted');
			return false;
		}
		
		$reflection = new Model($this -> app);
		$reflection -> setConnection($this -> destinationDb);
		$reflection -> setEntity($this -> destinationTable);
		$reflectionFields = $reflection -> getReflectionFieldsValues($this);
		
		if ($newObject = $reflection -> save($reflectionFields, $this -> destinationId)) {
			$this -> id = $newObject;
			return true;
		} else {
			$this -> errors = $reflection -> getErrors();
			return false;
		}
	}
	
	
	public function updateObject()
	{
		if (self::$targetShardCriteria === false) {
			throw new Exception('Shard criteria must be setted');
			return false;
		}
		
		$reflection = new Model($this -> app);
		$reflection -> setConnection($this -> destinationDb);
		$reflection -> setEntity($this -> destinationTable);
		$reflectionFields = $reflection -> getReflectionFieldsValues($this);

		$currentShard = $this -> getShardId();
		$this -> setShardById($this -> id);
		$oldShard = $this -> getShardId();
/*echo '<pre>';
var_dump(get_class($this));
echo '</pre>';
//die();*/		
		if ($currentShard != $oldShard) {
			$oldObject = clone $this;
			$this -> setShardByCriteria($this -> location_id);
				
			if ($newObject = $reflection -> save($reflectionFields, $this -> destinationId)) {
				$this -> id = $newObject;
		
				$oldObject -> setShardById($oldObject -> id);
				$oldObject -> delete();
		
				return true;
			} else {
				$this -> errors = $reflection -> getErrors();
				return false;
			}
		} else {
			if ($object = $reflection -> update($reflectionFields, $this -> destinationId)) {
				$this -> id = $object;
				return true;
			} else {
				$this -> errors = $reflection -> getErrors();
				return false;
			}
		}
	} 
	
	
	private function resetModelsManager()
	{
		$mngr = parent::getModelsManager();
		$mngr -> __destruct();
		$mngr -> setModelSource($this, $this -> destinationTable);
	}
	
	
	private function setupShard()
	{
		$this -> setRelationShard();
		$this -> setDestinationSource();
		$this -> setReadDestinationDb();
		$this -> setWriteDestinationDb();
		$this -> resetModelsManager();
		
		return;
	}
	
	/**
	 *  Just test, nothing else
	 	*/
	public function testIsHere()
	{
		die('yep, your model supports sharding');
	}
}