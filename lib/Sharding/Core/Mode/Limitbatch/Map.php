<?php 

namespace Sharding\Core\Mode\Limitbatch;

class Map
{
	public $id				= false;
	public $criteria_min	= false;
	public $criteria_max	= false;
	public $dbname			= false;
	public $tblname			= false;
	
	public $entity;
	public $connection;
	public $app;
	
	public function __construct($app)
	{
		$this -> app = $app;
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
	 * Set mapping minimum criteria 
	 * 
	 * @access public
	 * @param int $criteria
	 */
	public function setCriteriaMin($criteria)
	{
		$this -> criteria_min = $criteria;
	}
	
	/**
	 * Set mapping maximum criteria 
	 * 
	 * @access public
	 * @param int $criteria
	 */
	public function setCriteriaMax($criteria)
	{
		$this -> criteria_max = $criteria;
	}
}