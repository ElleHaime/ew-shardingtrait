<?php 

use Core\Model;

namespace Sharding\Core\Env\Converter;

use Sharding\Core\Loader as Loader,
	Sharding\Core\Model\Model as Model,
	Sharding\Core\Env\Helper\THelper as Helper;

	
class PhalconTask extends \Phalcon\CLI\Task
{
	public $convertLoader;
	public $shconfig;
	public $serviceConfig;

	
	public function structureAction()
	{
		$this -> initLoader();

		$shardMapPrefix = $this -> convertLoader -> getMapPrefix();
		foreach ($this -> convertLoader -> config -> shardModels as $model => $data) {
			if ($data -> shards) {
				foreach ($data -> shards as $db => $shard) {
					foreach ($this -> convertLoader -> connections as $conn) {
						if (!$conn -> tableExists($shardMapPrefix . strtolower($model))) {
							$shardType = $data -> shardType;
							$driver = $conn -> getDriver();
							$mapperName = $shardMapPrefix . strtolower($model);

							$conn -> createShardMap($mapperName,
									$this -> convertLoader -> serviceConfig -> mode -> $shardType -> schema -> $driver);
							print_r("Created " . $mapperName . "\n\r\n\r"); 
						}
					}
				}
			}
		}
		
		
		$master = $this -> convertLoader -> getMasterConnection();
		$masterConn = $this -> convertLoader -> connections -> $master;

		foreach ($this -> convertLoader -> config -> shardModels as $model => $data) {
			if ($data -> shards) {
				foreach ($data -> shards as $db => $shard) {
					for($i = 1; $i <= $shard -> tablesMax; $i++) {
						$tblName = $shard -> baseTablePrefix . $i;
						$masterConn -> setTable($data -> baseTable) -> createTableBySample($tblName);
						print_r("Created " . $tblName . "\n\r\n\r");
						
						if (isset($data -> relations)) {
							foreach ($data -> relations as $relation => $elem) {
								$tblRelName = $elem -> baseTablePrefix . $i;
								$masterConn -> setTable($elem -> baseTable) -> createTableBySample($tblRelName);
								print_r("Created " . $tblRelName . "\n\r\n\r");
							}
						}
					}
				}
			}
		}		
		print_r("ready\n\r\n\r");
		die();
	}
	

	
	public function dataAction()
	{
		$this -> initLoader(); 
		$nsObject = $this -> convertLoader -> config -> nsConvertation;

		foreach ($this -> convertLoader -> config -> shardModels as $object => $data) {

			$objRelationScope = [];
			if ($data -> relations) {
				foreach ($data -> relations as $relName => $relData) {
					$objRelationScope[$nsObject . '\\' . $relName] = $relData;
				}
			}
			$objFileScope = [];
			if (isset($data -> files)) {
				foreach ($data -> files as $relName => $relData) {
					$objFileScope[$relName] = $relData;
				}
			}
			$objName = $nsObject . '\\' . $object;
			
			$objTableName = $data -> baseTable;
			$objPrimary = $data -> primary;
			$objCriteria = $data -> criteria;
			$obj = new $objName;
			
			$obj -> setConvertationMode();
			$items = $obj::find(['limit' => ['number' => 10, 'offset' => 10]]);
			
			//$items = $obj -> getModelsManager() -> executeQuery('SELECT * FROM ' . $objName);
			/*$conn = $obj -> getReadConnection();
			$read = $conn -> query('SELECT * FROM ' . $objTableName);
			$items = $read -> fetchAll(); */

			foreach ($items as $e) {
print_r("\n\r\n\r");				
print_r("Old ID: " . $e -> id . " | ". $e -> name . "\n\r");	
				$oldId = $e -> $objPrimary;
				
				if (is_null($e -> $objCriteria) or empty($e -> $objCriteria) or $e -> $objCriteria === false) {
					$e -> $objCriteria = 0;
				}
				
				$e -> setShardByCriteria($e -> $objCriteria);
print_r("..to shard " . $e -> getShardTable() . "\n\r");			
				if ($newObj = $e -> save()) {
print_r("..with ID " . $newObj -> id . "\n\r");

					if (!empty($objFileScope)) {
						foreach ($objFileScope as $fileRel => $fileData) {
							if (is_dir($fileData -> path . DIRECTORY_SEPARATOR . $oldId)) {
								$oldPathName = $fileData -> path . DIRECTORY_SEPARATOR . $oldId;
								$newPathName = str_replace(DIRECTORY_SEPARATOR . $oldId, DIRECTORY_SEPARATOR . $newObj -> id, $oldPathName);

								try {
									rename($oldPathName, $newPathName);
								} catch(\Exception $e) {
									echo('ooooooooooops, can\'t rename folder ' . $oldPathName . '<br>');									
								}
							}
						}
					} 
					
					$hasOneRelations = $e -> getModelsManager() -> getHasOne(new $objName);
					if (!empty($hasOneRelations)) {
						foreach ($hasOneRelations as $index => $rel) {
							$relOption = $rel -> getOptions();
							$relField = $rel -> getReferencedFields();
							$relations = $e -> $relOption['alias'];
								
							if ($relations) {
								foreach ($relations as $obj) {
									$obj -> $relField = $newObj -> id;
									$obj -> update();
								}
							}
						}
					}
					
					$hasManyRelations = $e -> getModelsManager() -> getHasMany(new $objName);
					if (!empty($hasManyRelations)) {
print_r("..many relations: \n\r");						
						foreach ($hasManyRelations as $index => $rel) {
							$relOption = $rel -> getOptions();
							$relField = $rel -> getReferencedFields();
							$relModel = $rel -> getReferencedModel();
							
							if (array_key_exists($relModel, $objRelationScope)) {
print_r("....model " . $relModel . "\n\r");									
								$dest = new $relModel;
								$dest -> setConvertationMode();

								$relations = $dest::find($relField . ' = "' . $oldId . '"');
								if ($relations) {
									foreach ($relations as $relObj) {
										$relObj -> $relField = $newObj -> id;
										$relObj -> setShardById($newObj -> id);
//print_r("....to shard " . $relObj -> getShardTable() . "\n\r");										
										$relObj -> save();
//print_r("....with id " . $relObj -> id . "\n\r");										
									}
								}
							} else {
								$relations = $e -> $relOption['alias'];
								if ($relations) {
									foreach ($relations as $obj) {
										$obj -> $relField = $newObj -> id;
										$obj -> update();
									}
								}
							}
						}
					} 

					$hasManyToManyRelations = $e -> getModelsManager() -> getHasManyToMany(new $objName);

					if (!empty($hasManyToManyRelations)) {
print_r("..many-to-many relations: \n\r");						
						foreach ($hasManyToManyRelations as $index => $rel) {
							$relOption = $rel -> getOptions();
							$relModel = $rel -> getIntermediateModel();
							$relField = $rel -> getIntermediateFields(); 

							if (array_key_exists($relModel, $objRelationScope)) {
print_r("....model" . $relModel . "\n\r");								
								$dest = new $relModel;
								$dest -> setConvertationMode();

								$relations = $dest::find($relField . ' = "' . $oldId . '"');
								if ($relations) {
									foreach ($relations as $relObj) {
										$relObj -> $relField = $newObj -> id;
										$relObj -> setShardById($newObj -> id);
//print_r("....to shard " . $relObj -> getShardTable() . "\n\r");											
										$relObj -> save();
//print_r("....with id " . $relObj -> id . "\n\r");											
									}
								}
							} else {
								$relations = $relModel::find($relField . ' = ' . $oldId);
								if ($relations) {
									foreach ($relations as $obj) {
										$obj -> $relField = $newObj -> id;
										$obj -> update();
									}
								}
							}
						}
					}
				}
			}

		}
		
print_r("\n\rthis is the end\n\r");
die();		
	}
	
	
	public function initLoader()
	{
		if (!$this -> shconfig = $this -> getDi() -> get('shardingConfig')) {
			throw new Exception('Sharding config not found');
			return false; 
		}
		if (!$this -> serviceConfig = $this -> getDi() -> get('shardingServiceConfig')) {
			throw new Exception('Sharding service config not found');
			return false; 
		}
		
		$this -> convertLoader = new Loader($this -> shconfig, $this -> serviceConfig);
		$this -> loadConnections();
	}
	
	
	protected function loadConnections()
	{
		$di = $this -> getDI();
		$connections = $this -> convertLoader -> config -> connections;
		
		foreach ($connections as $name => $conn) {
			if (!$di -> has($name)) {
				$adapter = '\Phalcon\Db\Adapter\Pdo\\' . $conn -> adapter;
				
				$di -> set($name,
					function () use ($conn, $adapter) {
						$connection = new $adapter(
							array('host' => $conn -> host,
								  'username' => $conn -> user,
								  'password' => $conn -> password,
								  'dbname' => $conn -> database,
								  'port' => $conn -> port
							)
						);

						return $connection;
					} 
				);
				
				if ($name == 'dbMaster') {
					$di -> set('db', $di -> get($name));
				}
			}
		}
	}
}