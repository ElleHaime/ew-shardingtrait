<?php 

namespace Sharding\Core\Adapter\Mysql;

use Sharding\Core\Adapter\AdapterAbstractWritable;

class MysqlWritable extends AdapterAbstractWritable
{
	use \Sharding\Core\Adapter\Mysql\TMysql;

	
	//!!!TODO: remove saveRec; rewrite Map.php work with saveRecord() 
	public function saveRec($fields = [])
	{
		if (!empty($fields)) {
			$this -> queryExpr = 'INSERT INTO `' . $this -> queryTable . '` (';

			$i = 1;
			$cFields = count($fields);
			foreach ($fields as $fieldName => $fieldVal) {
				$this -> queryExpr .= $fieldName;
				if ($i < $cFields) {
					$this -> queryExpr .= ', ';
				}
				$i++;				
			}
			
			$this -> queryExpr .= ') VALUES (';
			
			$i = 1;
			foreach ($fields as $fieldName => $fieldVal) {
				if (is_integer($fieldVal)) {
					$this -> queryExpr .= $fieldVal;
				} else {
					$this -> queryExpr .= '"' . $fieldVal . '"';
				}
				if ($i < $cFields) {
					$this -> queryExpr .= ', ';
				}
				$i++;
			}
			
			$this -> queryExpr .= ')';
		}

		try {
			$this -> connection -> query($this -> queryExpr);
			$lastId = $this -> connection -> lastInsertId();
			$this -> clearQuery();	
			return $lastId;
			
		} catch(\PDOException $e) {
			$this -> errors = $e -> getMessage();
			$this -> clearQuery();
			return false;
		}
	}
	
	
	public function saveRecord($fields = [])
	{
		if (!empty($fields)) {
			$this -> queryExpr = 'INSERT INTO `' . $this -> queryTable . '` (';
			
			$i = 1;
			$cFields = count($fields);
			foreach ($fields as $fieldName => $fieldVal) {
				$this -> queryExpr .= $fieldName;
				if ($i < $cFields) {
					$this -> queryExpr .= ', ';
				}
				$i++;
			}
				
			$this -> queryExpr .= ') VALUES (';
				
			$i = 1;
			foreach ($fields as $fieldName => $fieldVal) {
				if (!$fieldVal['isnull'] && is_null($fieldVal['value'])) {
					return false;
				} elseif ($fieldVal['isnull'] && is_null($fieldVal['value'])) {
					if ($fieldName == 'location_id') {
						$this -> queryExpr .= '0';
					} else {
						$this -> queryExpr .= 'NULL';
					}
				} else {
					if ($fieldVal['type'] == 'int') {
						if (is_null($fieldVal['value'])) {
							$this -> queryExpr .= '0';
						} else {
							$this -> queryExpr .= $fieldVal['value'];
						}
					} else {
						$val = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.-]*(\?\S+)?)?)?)@', '<a href="$1" target="_blank">$1</a>', $fieldVal['value']);

						if (!is_array($val)) {
							$this -> queryExpr .= '"' . addslashes($val) . '"';
						} else {
							$this -> queryExpr .= '""';
						}
					}
				}
				if ($i < $cFields) {
					$this -> queryExpr .= ', ';
				}
				$i++;
			}


			$this -> queryExpr .= ')';

			try {
				$this -> connection -> query($this -> queryExpr);
				
				if ($lastId = $this -> connection -> lastInsertId()) {
					$this -> clearQuery();
					return $lastId;
				} else {
					$this -> clearQuery();
					return true;
				}
			} catch(\PDOException $e) {
				$this -> errors = $e -> getMessage();
				$this -> clearQuery();
				return false;
			}
		}	
	}
	
	
	public function updateRecord($fields = [])
	{
		$primary = $this -> getPrimaryKey();
		if (!isset($fields[$primary]) || empty($fields[$primary])) {
			throw new \Exception('Unable update record: primary key required');
		}

		$this -> queryExpr = 'UPDATE `' . $this -> queryTable . '` SET ';
		
		$i = 2;
		$cFields = count($fields);
		foreach ($fields as $fieldName => $fieldVal) {
			if ($fieldName != $primary) {
				$this -> queryExpr .= $fieldName . ' = ';				

				if (!$fieldVal['isnull'] && is_null($fieldVal['value'])) {
					return false;
				} elseif ($fieldVal['isnull'] && is_null($fieldVal['value'])) {
					$this -> queryExpr .= 'NULL';
				} else {
					if ($fieldVal['type'] == 'int') {
						$this -> queryExpr .= $fieldVal['value'];
					} else {
						$val = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.-]*(\?\S+)?)?)?)@', '<a href="$1" target="_blank">$1</a>', $fieldVal['value']);
						$this -> queryExpr .= '"' . addslashes($val) . '"';
					}
				}
				if ($i < $cFields) {
					$this -> queryExpr .= ', ';
				}
				$i++;
			}
		}
		
		$this -> queryExpr .= ' WHERE ' . $primary . ' = "' . $fields[$primary]['value'] . '"';

		try {
			$this -> connection -> query($this -> queryExpr);
			$this -> clearQuery();
			return $fields[$primary]['value'];
		} catch(\PDOException $e) {
			$this -> errors = $e -> getMessage();
			$this -> clearQuery();
			return false;
		}

		return;
	}
	
	
	public function createShardMap($tblName, $data)
	{
		if ($this -> writable) {
			$query = str_replace('$tableName', $tblName, $data);
			/* validation, big heap of validations */
			/* and create this fucking table*/
			try {
				$this -> connection -> query($query);
			} catch(\Exception $e) {
				throw new \Exception('Unable to create mapping table');
			}
			
			return;
		}
	}

	
	public function createTableBySample($tblName)
	{
		if ($this -> tableExists($tblName)) {
			return;
		}
		
		$structure = $this -> getTableScheme();

		if ($structure) {
			if (!empty($structure[0]['Create Table'])) {
				$query = str_replace("`" . $structure[0]['Table'] . "`", "`" . $tblName . "`", $structure[0]['Create Table']);
				try {
					$this -> connection -> query($query);
				} catch (\PDOException $e) {
					$this -> errors = $e -> getMessage();
				}
			}
		}
		
		return;
	}
} 
