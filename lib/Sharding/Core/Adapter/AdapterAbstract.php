<?php 

namespace Sharding\Core\Adapter;

abstract class AdapterAbstract
{
	protected $connection;
	protected $errors;
	protected $writeable;
	
	protected $host;
	protected $port;
	protected $user;
	protected $password;
	protected $database;
	
	protected $queryTable	= false;
	protected $limit		= false;
	protected $offset		= false;
	protected $conditions	= [];
	protected $fields 		= [];
	protected $queryExpr 	= '';
	protected $fetchFormat	= 'OBJECT';
	protected $fetchClass	= false;
	
	
	public function __construct($data)
	{
		$this -> host = $data -> host;
		$this -> port = $data -> port;
		$this -> user = $data -> user;
		$this -> password = $data -> password;
		$this -> database = $data -> database;
		$this -> writable = $data -> writable; 

		$this -> connect();
	}
	
	abstract function connect();
	
	abstract function getDriver();
	
	abstract function createShardMap($tblName, $data);
	
	abstract function createTableBySample($tblName);
	
	abstract function tableExists($tableName);
}