<?php 

namespace Sharding\Core\Adapter\Mysql;

use Sharding\Core\Adapter\AdapterAbstractReadonly;

class MysqlReadonly extends AdapterAbstractReadonly
{
	use \Sharding\Core\Adapter\Mysql\TMysql;
} 