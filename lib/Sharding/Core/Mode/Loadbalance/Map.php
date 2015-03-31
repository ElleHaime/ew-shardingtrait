<?php 

namespace Sharding\Core\Mode\Loadbalance;

class Map
{
	public $id					= false;
	public $criteria			= false;
	public $dbname				= false;
	public $tblname				= false;
	
	public $entity;
	public $connection;
	public $app;
	
	
	public function __construct($app)
	{
		$this -> app = $app;
	}
	
	/**
	 * Search shard by criteria in map table
	 * 
	 * @access public
	 * @param string $param
	 * @param int|string $value
	 * @return Map object | false 
	 */
	public function findShard($param, $value)
	{
		$result = $this -> connection -> setTable($this -> entity)
									  -> addCondition($this -> entity . '.' . $param . ' = ' . $value)
									  -> fetchOne();
		
		if ($result) {
			$this -> id = $result -> id;
			$this -> dbname = $result -> dbname;
			$this -> tblname = $result -> tblname;
			$this -> criteria = $result -> criteria;
		} 
		
		return;
	}
	
	
	
	/**
	 * Search all criteria in map table
	 *
	 * @access public
	 * @param string $param
	 * @param int|string $value
	 * @return Map object | false
	 */
	public function findCriteria()
	{
		$result = $this -> connection -> setTable($this -> entity)
									  -> fetch();

		return $result;
	}
	

	
	/**
	 * Search distinct all shards in map table
	 *
	 * @access public
	 * @param string $param
	 * @param int|string $value
	 * @return Map object | false
	 */
	public function findShards()
	{
		$result = $this -> connection -> setTable($this -> entity)
									  -> fetch();
		return $result;
	}
	
	

	/**
	 * Save new shard to the map table
	 * 
	 * @access public
	 * @return Map object | false 
	 */
	public function save()
	{
		$data = ['criteria' => $this -> criteria,
				 'dbname' => $this -> dbname,
				 'tblname' => $this -> tblname];

		$result = $this -> connection -> setTable($this -> entity)
									  -> saveRec($data);
		if ($result) {
			$this -> id = $result;
			return $this;
		} else {
			return false;
		}
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
	 * Set mapping entity 
	 * 
	 * @access public
	 * @param string $entity
	 */
	public function setEntity($entity)
	{
		$prefix = $this -> app -> getMapPrefix();
		$this -> entity = $prefix . $entity;
	}
	
	/**
	 * Set mapping criteria 
	 * 
	 * @access public
	 * @param string $criteria
	 */
	public function setCriteria($criteria)
	{
		$this -> criteria = $criteria;
	}
}