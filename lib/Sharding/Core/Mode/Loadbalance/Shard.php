<?php 

namespace Sharding\Core\Mode\Loadbalance;

use Core\Utils as _U;

class Shard
{
	public $entity;
	public $connection;
	public $app;

	
	public function __construct($app)
	{
		$this -> app = $app;
	}
	
	/**
	 * Return simple table name with min records after comparison
	 * 
	 * @access public
	 * @param object $shard
	 * @return string 
	 */	
	public function getMinTable($shard)
	{
		$result = $this -> compareShardTables($shard);
		return $result['min']['table'];
	}
	
	
	/**
	 * Return simple table name with max records after comparison
	 *
	 * @access public
	 * @param object $shard
	 * @return string
	 */
	public function getMaxTable($shard)
	{
		$result = $this -> compareShardTables($shard);
		return $result['max']['table'];
	}
	
	
	/**
	 * Compare count of rows in each shard(table) 
	 * 
	 * @access public
	 * @param object $shard
	 * @return array  
	 */
	public function compareShardTables($shard)
	{
		$shards = [];
		for ($i = 1; $i <= $shard -> tablesMax; $i++) {
			$tblName = $shard -> baseTablePrefix . $i;
			$shards[$tblName] = $this -> connection -> setTable($tblName) -> getRowsCount();
		}
		asort($shards);
		
		$getTables = $getRows = $shards;
		$result['max']['rows'] = array_pop($getTables);
		$result['min']['rows'] = array_shift($getTables);

		$getRows = array_flip($shards);
		if (count($getRows) < 2) {
			$getRows = array_keys($shards);
		}
		$result['max']['table'] = array_pop($getRows);
		$result['min']['table'] = array_shift($getRows);
		
		return $result;
	}

	
	/**
	 * Set connection
	 * 
	 * @access public
	 * @param string $conn
	 */
	public function useConnection($conn)
	{
		$this -> connection = $this -> app -> connections -> $conn;
	}
	
	
	/**
	 * Set sharding entity 
	 * 
	 * @access public
	 * @param string $entity
	 */
	public function setEntity($entity)
	{
		$this -> entity = $entity;
	}
}
