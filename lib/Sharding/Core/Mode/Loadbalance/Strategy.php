<?php

namespace Sharding\Core\Mode\Loadbalance;

use Sharding\Core\Mode\StrategyAbstract,
	Core\Utils as _U,
	Sharding\Core\Mode\Loadbalance\Map as Map,
	Sharding\Core\Mode\Loadbalance\Shard as Shard;


class Strategy extends StrategyAbstract
{
	protected $shardsAvailable		= [];
	protected $shardDbname			= false;
	protected $shardTblname			= false;
	protected $shardId				= false;
	protected $shardCriteria		= false;

	
	/**
	 * Search shard by criteria. If shard not found (was passed new criteria), 
	 * then compare available shards and return the pair connection+table
	 * with min records.
	 * 
	 * @access public
	 * @param int|string $arg
	 * @return array
	 */
	public function selectShardByCriteria($arg)
	{
		$mapper = new Map($this -> app);
		$mapper -> setEntity($this -> shardEntity);
		$mapper -> useConnection($this -> app -> getMasterConnection());
		$mapper -> findShard('criteria', $arg);

		// create new shard or use existed
		if ($mapper -> id) {
			$this -> shardDbname = $mapper -> dbname;
			$this -> shardTblname = $mapper -> tblname;
			$this -> shardId = $mapper -> id;
			$this -> shardCriteria = $mapper -> criteria;
		} else {
			$sharder = new Shard($this -> app);
			
			// check number of rows in all tables for each available connection
			foreach ($this -> shardModel -> shards as $conn => $data) {
				$sharder -> useConnection($conn);
				$this -> shardsAvailable[] = ['connection' => $conn,
											  'table' => $sharder -> getMinTable($data)];
			}

			// select optimal shard with minimum rows
			// TODO: add comparison between connections
			$newShard = $this -> shardsAvailable[0];
			$newShard['criteria'] = $arg;
			// add record about new location of criteria to the map table
			$this -> addShard($newShard);
		} 

		return;
	}
	
	/**
	 * Search shard by shard id
	 * 
	 * @access public
	 * @param int $arg
	 * @return array
	 */
	public function selectShardById($arg)
	{
		$mapper = new Map($this -> app);
		$mapper -> setEntity($this -> shardEntity);
		$mapper -> useConnection($this -> app -> getMasterConnection());
		$mapper -> findShard('id', $arg);

		// create new shard or use existed
		if ($mapper -> id) {
			$this -> shardDbname = $mapper -> dbname;
			$this -> shardTblname = $mapper -> tblname;
			$this -> shardId = $mapper -> id;
			$this -> shardCriteria = $mapper -> criteria;
		} 
		
		return;
	}
	

	/**
	 * Search all sharded criteria for current entity
	 *
	 * @access public
	 * @return array
	 */
	public function selectAllCriteria()
	{
		$result = [];
		
		$mapper = new Map($this -> app);
		$mapper -> setEntity($this -> shardEntity);
		$mapper -> useConnection($this -> app -> getMasterConnection());
		$criteria = $mapper -> findCriteria();

		if ($criteria) {
			foreach ($criteria as $rec) {
				$result[] = $rec['criteria'];
			}
		}
		
		return $result;
	}
	
	
	/**
	 * Search all available shards
	 *
	 * @access public
	 * @return array
	 */
	public function selectAllShards()
	{
		return $this -> app -> getAllShards(ucfirst($this -> shardEntity));	
	}
	

	
	/**
	 * Create new record to the mapping table
	 * 
	 * @access private
	 * @param array @arg
	 * @return int 
	 */
	private function addShard($newShard)
	{
		$mapper = new Map($this -> app);
		$mapper -> setEntity($this -> shardEntity);
		$mapper -> useConnection($this -> app -> getMasterConnection());
			
		$mapper -> criteria = $newShard['criteria'];
		$mapper -> dbname = $newShard['connection'];
		$mapper -> tblname = $newShard['table'];
		
		$result = $mapper -> save();
		if ($result) {
			$this -> shardDbname = $mapper -> dbname;
			$this -> shardTblname = $mapper -> tblname;
			$this -> shardId = $mapper -> id;
		} 
		
		return;
	}
	
	/** Return shardDbname
	 * 
	 * @access public
	 * @return string 
	 */
	public function getDbName()
	{
		return $this -> shardDbname; 	
	} 

	/** Return shardTblname
	 * 
	 * @access public
	 * @return string 
	 */
	public function getTableName()
	{
		return $this -> shardTblname;
	}

	/** Return shardId
	 * 
	 * @access public
	 * @return string 
	 */
	public function getId()
	{
		return $this -> shardId;
	}

	/** Return criteria
	 * 
	 * @access public
	 * @return string 
	 */
	public function getCriteria()
	{
		return $this -> shardCriteria;
	}
}

