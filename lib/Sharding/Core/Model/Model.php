<?php 

namespace Sharding\Core\Model;

class Model
{
	public $app;
	public $entity;
	public $connection;
	public $errors			= false;
	
	private $fields;
	private $id			= false;
	
	
	public function __construct($app)
	{
		$this -> app = $app;
	}
	

	public function save($data, $shardId)
	{
		$data = $this -> composeNewId($data, $shardId);
		
		$result = $this -> connection -> setTable($this -> entity)
									  -> saveRecord($data);
		if ($result) {
			return $this -> id;
		} else {
			$this -> errors = $this -> connection -> getErrors();
			return false;
		}
	}
	
	
	public function update($data, $shardId)
	{
		$result = $this -> connection -> setTable($this -> entity)
									  -> updateRecord($data);
		if ($result) {
			return $result;
		} else {
			$this -> errors = $this -> connection -> getErrors();
			return false;
		}
	}
	
	
	/**
	 * Compose primary id for new records in the shard model.
	 * Based on last inserted primary
	 *
	 * @access public 
	 * @param Model object $object
	 * @return int|string $id
	 */
	public function composeNewId($data, $shardId)
	{
		$separator = $this -> app -> getShardIdSeparator();
		$entityId = $this -> connection -> setTable($this -> entity)
										-> getLastId();
		if (!$entityId['lastId']) {
			$data[$entityId['key']]['value'] = '1' . $separator . $shardId; 
		} else {
			$data[$entityId['key']]['value'] = (int)$entityId['lastId'] + 1 . $separator . $shardId;  
		}
		$this -> id = $data[$entityId['key']]['value'];

		return $data;
	}
	
	
	public function getEntityStructure()
	{
		$structure = $this -> connection -> setTable($this -> entity)
		-> getTableStructure();
		return $structure;
	}
	
	
	public function getReflectionFieldsValues($modelObject = false)
	{
		if ($modelObject) {
			$reflectionFields = $this -> getEntityStructure();
				
			foreach(get_object_vars($modelObject) as $prop => $value) {
				if (isset($reflectionFields[$prop])) {
					if ($value == '') {
						$value = NULL;
					}
					$reflectionFields[$prop]['value'] = $value;
				}
			}
			return $reflectionFields;
		} else {
			throw new \Exception('Empty model object');
		}
	}
	
	
	public function setConnection($conn)
	{
		$this -> connection = $this -> app -> connections -> $conn;
		return $this;
	}
	
	public function setEntity($entity)
	{
		$this -> entity = $entity;
	}
	
	public function getErrors()
	{
		return $this -> errors;
	}
}